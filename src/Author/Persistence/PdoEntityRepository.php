<?php

namespace GisClient\Author\Persistence;

use GisClient\Author\Persistence\Exception\ForeignKeyConstraintViolationException;
use GisClient\Author\Persistence\Exception\InvalidPersistedDataException;
use GisClient\Author\Persistence\Exception\RepositoryOperationException;
use GisClient\Author\Persistence\Exception\UniqueConstraintViolationException;

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
            $selectSql .= sprintf(' ORDER BY %s %s', $sortField, $sortDirection);
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

            return $row === false ? null : $this->mapRowToEntity($schema, $row);
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

        $this->executeSafely(function () use ($sql, $params): void {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        });

        return $this->findById(new EntityRef($entity->getType(), $attributes[$pk]));
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

        $this->executeSafely(function () use ($sql, $params): void {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        });

        return $this->findById(new EntityRef($entity->getType(), $entity->getId()));
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

    private function rethrowDatabaseException(\PDOException $exception)
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
