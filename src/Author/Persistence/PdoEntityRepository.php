<?php

namespace GisClient\Author\Persistence;

use GisClient\Author\Persistence\Exception\ForeignKeyConstraintViolationException;
use GisClient\Author\Persistence\Exception\InvalidPersistedDataException;
use GisClient\Author\Persistence\Exception\RepositoryOperationException;
use GisClient\Author\Persistence\Exception\UniqueConstraintViolationException;
use PDOException;

class PdoEntityRepository implements EntityRepository
{
    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var int
     */
    private $savepointCounter = 0;

    /**
     * Cache of pg_get_serial_sequence() results keyed by "schema.table.column".
     * NULL means the column has no associated sequence.
     *
     * @var array<string, string|null>
     */
    private static $sequenceCache = [];

    public function __construct(\PDO $db = null)
    {
        $this->db = $db ?: \GCApp::getDB();
    }

    public function findAll(EntityQuery $query)
    {
        $schema = $this->schemaForType($query->getType());
        $where = [];
        $params = [];
        $paramIndex = 0;

        foreach ($query->getFilters() as $field => $value) {
            $placeholder = ':f' . $paramIndex;
            $where[] = sprintf('%s = %s', $field, $placeholder);
            $params[$placeholder] = $value;
            $paramIndex++;
        }
        $whereSql = count($where) > 0 ? (' WHERE ' . implode(' AND ', $where)) : '';
        $table = sprintf('%s.%s', $schema->getResolvedDbSchema(), $schema->getResolvedTable());

        return $this->executeSafely(function () use ($schema, $query, $params, $whereSql, $table) {
            $countSql = sprintf('SELECT COUNT(*) FROM %s%s', $table, $whereSql);
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = (int) $stmt->fetchColumn();

            $fields = implode(', ', $schema->getReadableDbFields());
            $selectSql = sprintf('SELECT %s FROM %s%s', $fields, $table, $whereSql);

            $sortField = $query->getSortField() ?: $schema->getDefaultSortColumn();
            $sortDirection = $query->getSortDirection();
            $selectSql .= sprintf(' ORDER BY %s %s, %s ASC', $sortField, $sortDirection, $schema->getPrimaryKey());
            $selectSql .= ' LIMIT :limit OFFSET :offset';

            $stmt = $this->db->prepare($selectSql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $query->getLimit(), \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $query->getOffset(), \PDO::PARAM_INT);
            $stmt->execute();

            $items = [];
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $items[] = $this->mapRowToEntity($schema, $row);
            }
            $items = $this->loadCollectionRelationships($schema, $items);

            return new PagedResult($items, $total, $query->getLimit(), $query->getOffset());
        });
    }

    public function findById(EntityRef $ref)
    {
        $schema = $this->schemaForType($ref->getType());
        $table = sprintf('%s.%s', $schema->getResolvedDbSchema(), $schema->getResolvedTable());
        $fields = implode(', ', $schema->getReadableDbFields());

        return $this->executeSafely(function () use ($schema, $ref, $table, $fields) {
            $params = [
                ':id' => $this->normalizeId($schema, $ref->getId()),
            ];
            $sql = sprintf('SELECT %s FROM %s WHERE %s = :id LIMIT 1', $fields, $table, $schema->getPrimaryKey());
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($row === false) {
                return null;
            }
            $entities = $this->loadCollectionRelationships($schema, [$this->mapRowToEntity($schema, $row)]);

            return $entities[0];
        });
    }

    public function create(Entity $entity)
    {
        $schema = $this->schemaForType($entity->getType());
        $attributes = $entity->getAttributes();
        $pk = $schema->getPrimaryKey();
        if ($schema->getIdPhpType() === 'int' && empty($attributes[$pk])) {
            $attributes[$pk] = \GCApp::getNewPKey(DB_SCHEMA, $schema->getResolvedDbSchema(), $schema->getResolvedTable(), $pk);
        } elseif ($entity->getId() !== null && !array_key_exists($pk, $attributes)) {
            $attributes[$pk] = $entity->getId();
        }

        $columns = array_keys($attributes);
        $placeholders = [];
        $params = [];
        foreach ($columns as $i => $column) {
            $placeholder = ':p' . $i;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $attributes[$column];
        }

        $sql = sprintf(
            'INSERT INTO %s.%s (%s) VALUES (%s)',
            $schema->getResolvedDbSchema(),
            $schema->getResolvedTable(),
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $hasCollections = $entity->getCollectionRelationships() !== [];
        $ownTransaction = $hasCollections && !$this->db->inTransaction();
        if ($ownTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $this->executeSafely(function () use ($sql, $params): void {
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            });

            if ($schema->getIdPhpType() === 'int' && isset($attributes[$pk])) {
                $this->syncSequence(
                    $schema->getResolvedDbSchema(),
                    $schema->getResolvedTable(),
                    $pk,
                    (int) $attributes[$pk]
                );
            }

            $this->syncCollectionRelationships($schema, $attributes[$pk], $entity);

            $result = $this->findById(new EntityRef($entity->getType(), $attributes[$pk]));

            if ($ownTransaction) {
                $this->db->commit();
            }
        } catch (\Throwable $e) {
            if ($ownTransaction) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return $result;
    }

    public function update(Entity $entity)
    {
        $schema = $this->schemaForType($entity->getType());
        $attributes = $entity->getAttributes();
        $assignments = [];
        $params = [];
        $i = 0;
        foreach ($attributes as $field => $value) {
            $placeholder = ':p' . $i;
            $assignments[] = sprintf('%s = %s', $field, $placeholder);
            $params[$placeholder] = $value;
            $i++;
        }

        $params[':id'] = $this->normalizeId($schema, $entity->getId());
        $sql = sprintf(
            'UPDATE %s.%s SET %s WHERE %s = :id',
            $schema->getResolvedDbSchema(),
            $schema->getResolvedTable(),
            implode(', ', $assignments),
            $schema->getPrimaryKey()
        );

        $hasCollections = $entity->getCollectionRelationships() !== [];
        $ownTransaction = $hasCollections && !$this->db->inTransaction();
        if ($ownTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $this->executeSafely(function () use ($sql, $params): void {
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            });

            $this->syncCollectionRelationships($schema, $entity->getId(), $entity);

            $result = $this->findById(new EntityRef($entity->getType(), $entity->getId()));

            if ($ownTransaction) {
                $this->db->commit();
            }
        } catch (\Throwable $e) {
            if ($ownTransaction) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return $result;
    }

    public function delete(EntityRef $ref)
    {
        $schema = $this->schemaForType($ref->getType());
        $params = [
            ':id' => $this->normalizeId($schema, $ref->getId()),
        ];
        $sql = sprintf(
            'DELETE FROM %s.%s WHERE %s = :id',
            $schema->getResolvedDbSchema(),
            $schema->getResolvedTable(),
            $schema->getPrimaryKey()
        );

        $this->executeSafely(function () use ($sql, $params): void {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        });
    }

    /**
     * Loads collection (junction-table) relationships for a set of entities in bulk.
     *
     * @param array<int,Entity> $entities
     * @return array<int,Entity>
     */
    private function loadCollectionRelationships(EntitySchema $schema, array $entities): array
    {
        $collectionConfigs = $schema->getCollectionRelationships();
        if ($collectionConfigs === [] || $entities === []) {
            return $entities;
        }

        $ids = [];
        foreach ($entities as $entity) {
            $ids[] = (string) $entity->getId();
        }

        $collectionData = [];
        foreach ($ids as $id) {
            foreach ($collectionConfigs as $name => $config) {
                $collectionData[$id][$name] = [];
            }
        }

        foreach ($collectionConfigs as $name => $config) {
            if (!($config['readable'] ?? true)) {
                continue;
            }

            $junctionTable = $schema->getResolvedDbSchema() . '.' . $config['junction_table'];
            $localKey = $config['junction_local_key'];
            $foreignKey = $config['junction_foreign_key'];

            $placeholders = implode(', ', array_fill(0, count($ids), '?'));
            $sql = sprintf(
                'SELECT %s, %s FROM %s WHERE %s IN (%s) ORDER BY %s, %s',
                $localKey,
                $foreignKey,
                $junctionTable,
                $localKey,
                $placeholders,
                $localKey,
                $foreignKey
            );
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($ids));

            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $localId = (string) $row[$localKey];
                if (isset($collectionData[$localId])) {
                    $collectionData[$localId][$name][] = $row[$foreignKey];
                }
            }
        }

        $result = [];
        foreach ($entities as $entity) {
            $id = (string) $entity->getId();
            $entityCollections = $collectionData[$id] ?? [];
            $result[] = new Entity(
                $entity->getType(),
                $entity->getOperation(),
                $entity->getId(),
                $entity->getAttributes(),
                $entity->getRelationships(),
                $entityCollections
            );
        }

        return $result;
    }

    /**
     * Syncs junction-table rows for collection relationships declared on the entity.
     *
     * @param int|string|null $id
     */
    private function syncCollectionRelationships(EntitySchema $schema, $id, Entity $entity): void
    {
        $collectionConfigs = $schema->getCollectionRelationships();
        if ($collectionConfigs === []) {
            return;
        }

        $entityCollections = $entity->getCollectionRelationships();

        foreach ($collectionConfigs as $name => $config) {
            if (!($config['writable'] ?? true) || !array_key_exists($name, $entityCollections)) {
                continue;
            }

            $junctionTable = $schema->getResolvedDbSchema() . '.' . $config['junction_table'];
            $localKey = $config['junction_local_key'];
            $foreignKey = $config['junction_foreign_key'];

            $deleteStmt = $this->db->prepare(
                sprintf('DELETE FROM %s WHERE %s = :id', $junctionTable, $localKey)
            );
            $deleteStmt->execute([
                ':id' => $id,
            ]);

            $foreignIds = array_values(array_unique($entityCollections[$name]));
            if ($foreignIds !== []) {
                $insertStmt = $this->db->prepare(
                    sprintf(
                        'INSERT INTO %s (%s, %s) VALUES (:local_id, :foreign_id)',
                        $junctionTable,
                        $localKey,
                        $foreignKey
                    )
                );
                foreach ($foreignIds as $foreignId) {
                    $insertStmt->execute([
                        ':local_id' => $id,
                        ':foreign_id' => $foreignId,
                    ]);
                }
            }
        }
    }

    private function schemaForType(string $type): EntitySchema
    {
        return EntitySchemaRegistry::schemaForType($type);
    }

    /**
     * @param array<string,mixed> $row
     */
    private function mapRowToEntity(EntitySchema $schema, array $row): Entity
    {
        $attributes = [];
        foreach ($schema->getReadableAttributeColumns() as $publicName => $column) {
            if (array_key_exists($column, $row)) {
                $attributes[$column] = $row[$column];
            }
        }

        $relationships = [];
        foreach ($schema->getReadableRelationshipColumns() as $relationshipName => $column) {
            if (!array_key_exists($column, $row)) {
                continue;
            }

            $relationships[$relationshipName] = [
                'type' => null,
                'id' => $row[$column],
            ];
        }

        return new Entity(
            $schema->getType(),
            Entity::OPERATION_READ,
            array_key_exists($schema->getPrimaryKey(), $row) ? $row[$schema->getPrimaryKey()] : null,
            $attributes,
            $relationships
        );
    }

    /**
     * @template T
     * @param callable():T $callback
     * @return T
     */
    private function executeSafely(callable $callback)
    {
        $savepoint = null;

        if ($this->db->inTransaction()) {
            $savepoint = $this->createSavepointName();
            $this->db->exec('SAVEPOINT ' . $savepoint);
        }

        try {
            $result = $callback();
            if ($savepoint !== null) {
                $this->db->exec('RELEASE SAVEPOINT ' . $savepoint);
            }
            return $result;
        } catch (\PDOException $exception) {
            if ($savepoint !== null) {
                $this->clearSavepointAfterFailure($savepoint);
            }
            $this->rethrowDatabaseException($exception);
        }
    }

    /**
     * @param string $savepoint
     */
    private function clearSavepointAfterFailure($savepoint)
    {
        try {
            $this->db->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
            $this->db->exec('RELEASE SAVEPOINT ' . $savepoint);
        } catch (\Throwable $rollbackException) {
            // Ignore rollback cleanup failures, original exception is more relevant.
        }
    }

    /**
     * @return string
     */
    private function createSavepointName()
    {
        $this->savepointCounter++;
        return 'gc_api_sp_' . $this->savepointCounter;
    }

    /**
     * @param mixed $id
     * @return int|string
     */
    private function normalizeId(EntitySchema $schema, $id)
    {
        if ($schema->getIdPhpType() === 'int') {
            return (int) $id;
        }
        return (string) $id;
    }

    private function syncSequence(string $dbSchema, string $table, string $pkColumn, int $id): void
    {
        $cacheKey = "$dbSchema.$table.$pkColumn";

        if (!array_key_exists($cacheKey, self::$sequenceCache)) {
            $stmt = $this->db->prepare('SELECT pg_get_serial_sequence(:qualified_table, :column)');
            $stmt->execute([
                ':qualified_table' => "$dbSchema.$table",
                ':column' => $pkColumn,
            ]);
            $result = $stmt->fetchColumn();
            self::$sequenceCache[$cacheKey] = ($result !== false && $result !== null) ? (string) $result : null;
        }

        $seqName = self::$sequenceCache[$cacheKey];
        if ($seqName === null) {
            return;
        }

        $this->db->exec(sprintf(
            'SELECT setval(%s, GREATEST(%d, (SELECT last_value FROM %s)))',
            $this->db->quote($seqName),
            $id,
            $seqName
        ));
    }

    /**
     * @throws PersistenceException
     * @return never
     */
    private function rethrowDatabaseException(PDOException $exception)
    {
        $code = (string) $exception->getCode();
        if ($code === '23505') {
            throw new UniqueConstraintViolationException('Duplicate value violates unique constraint', 0, $exception);
        }
        if ($code === '23503') {
            throw new ForeignKeyConstraintViolationException('Foreign key constraint violation', 0, $exception);
        }
        if (strpos($code, '22') === 0 || in_array($code, ['23502', '23514'], true)) {
            throw new InvalidPersistedDataException('One or more attributes have invalid value or format', 0, $exception);
        }

        throw new RepositoryOperationException('An internal error occurred', 0, $exception);
    }
}
