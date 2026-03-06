<?php

namespace GisClient\Author\Api\Persistence;

use GisClient\Author\Api\Contract\AuthorEntityRepositoryInterface;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Model\EntityDefinition;
use GisClient\Author\Api\Model\PagedResult;
use GisClient\Author\Api\Model\QueryOptions;

class PdoAuthorEntityRepository implements AuthorEntityRepositoryInterface
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

    public function findAll(EntityDefinition $definition, QueryOptions $queryOptions, array $scopeFilters = [])
    {
        $where = [];
        $params = [];
        $paramIndex = 0;

        foreach ($queryOptions->getFilters() as $field => $value) {
            $placeholder = ':f' . $paramIndex;
            $where[] = sprintf('%s = %s', $field, $placeholder);
            $params[$placeholder] = $value;
            $paramIndex++;
        }
        foreach ($scopeFilters as $field => $value) {
            $placeholder = ':s' . $paramIndex;
            $where[] = sprintf('%s = %s', $field, $placeholder);
            $params[$placeholder] = $value;
            $paramIndex++;
        }

        $whereSql = count($where) > 0 ? (' WHERE ' . implode(' AND ', $where)) : '';
        $table = sprintf('%s.%s', $definition->getSchema(), $definition->getTable());

        return $this->executeSafely(function () use ($definition, $queryOptions, $params, $whereSql, $table) {
            $countSql = sprintf('SELECT COUNT(*) FROM %s%s', $table, $whereSql);
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = (int) $stmt->fetchColumn();

            $fields = implode(', ', $definition->getReadableFields());
            $selectSql = sprintf('SELECT %s FROM %s%s', $fields, $table, $whereSql);

            $sortField = $queryOptions->getSortField() ?: $definition->getDefaultSort();
            $sortDirection = $queryOptions->getSortDirection();
            $selectSql .= sprintf(' ORDER BY %s %s', $sortField, $sortDirection);
            $selectSql .= ' LIMIT :limit OFFSET :offset';

            $stmt = $this->db->prepare($selectSql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', (int) $queryOptions->getLimit(), \PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $queryOptions->getOffset(), \PDO::PARAM_INT);
            $stmt->execute();

            return new PagedResult($stmt->fetchAll(\PDO::FETCH_ASSOC), $total, $queryOptions->getLimit(), $queryOptions->getOffset());
        });
    }

    public function findById(EntityDefinition $definition, $id, array $scopeFilters = [])
    {
        $table = sprintf('%s.%s', $definition->getSchema(), $definition->getTable());
        $fields = implode(', ', $definition->getReadableFields());

        return $this->executeSafely(function () use ($definition, $id, $table, $fields, $scopeFilters) {
            $params = [
                ':id' => $this->normalizeId($definition, $id),
            ];
            $where = [sprintf('%s = :id', $definition->getPrimaryKey())];
            $this->appendScopeWhere($scopeFilters, $where, $params);

            $sql = sprintf('SELECT %s FROM %s WHERE %s LIMIT 1', $fields, $table, implode(' AND ', $where));
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $row === false ? null : $row;
        });
    }

    public function create(EntityDefinition $definition, array $attributes)
    {
        $pk = $definition->getPrimaryKey();
        if ($definition->getIdType() === 'int' && empty($attributes[$pk])) {
            $attributes[$pk] = \GCApp::getNewPKey(DB_SCHEMA, $definition->getSchema(), $definition->getTable(), $pk);
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
            $definition->getSchema(),
            $definition->getTable(),
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->executeSafely(function () use ($sql, $params): void {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        });

        return $this->findById($definition, $attributes[$pk], $this->extractScopeFilters($definition, $attributes));
    }

    public function update(EntityDefinition $definition, $id, array $attributes, array $scopeFilters = [])
    {
        $assignments = [];
        $params = [];
        $i = 0;
        foreach ($attributes as $field => $value) {
            $placeholder = ':p' . $i;
            $assignments[] = sprintf('%s = %s', $field, $placeholder);
            $params[$placeholder] = $value;
            $i++;
        }

        $params[':id'] = $this->normalizeId($definition, $id);
        $where = [sprintf('%s = :id', $definition->getPrimaryKey())];
        $this->appendScopeWhere($scopeFilters, $where, $params);
        $sql = sprintf(
            'UPDATE %s.%s SET %s WHERE %s',
            $definition->getSchema(),
            $definition->getTable(),
            implode(', ', $assignments),
            implode(' AND ', $where)
        );

        $this->executeSafely(function () use ($sql, $params): void {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        });

        return $this->findById($definition, $id, $scopeFilters);
    }

    public function delete(EntityDefinition $definition, $id, array $scopeFilters = [])
    {
        $params = [
            ':id' => $this->normalizeId($definition, $id),
        ];
        $where = [sprintf('%s = :id', $definition->getPrimaryKey())];
        $this->appendScopeWhere($scopeFilters, $where, $params);
        $sql = sprintf(
            'DELETE FROM %s.%s WHERE %s',
            $definition->getSchema(),
            $definition->getTable(),
            implode(' AND ', $where)
        );

        $this->executeSafely(function () use ($sql, $params): void {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        });
    }

    /**
     * @param array<string,mixed> $scopeFilters
     * @param array<int,string> $where
     * @param array<string,mixed> $params
     */
    private function appendScopeWhere(array $scopeFilters, array &$where, array &$params)
    {
        $idx = 0;
        foreach ($scopeFilters as $field => $value) {
            $placeholder = ':scope_' . $idx;
            $where[] = sprintf('%s = %s', $field, $placeholder);
            $params[$placeholder] = $value;
            $idx++;
        }
    }

    /**
     * @param array<string,mixed> $attributes
     * @return array<string,mixed>
     */
    private function extractScopeFilters(EntityDefinition $definition, array $attributes)
    {
        $scopeFilters = [];
        foreach ($definition->getScopeFields() as $scopeField) {
            if (array_key_exists($scopeField, $attributes)) {
                $scopeFilters[$scopeField] = $attributes[$scopeField];
            }
        }

        return $scopeFilters;
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
    private function normalizeId(EntityDefinition $definition, $id)
    {
        if ($definition->getIdType() === 'int') {
            return (int) $id;
        }
        return (string) $id;
    }

    private function rethrowDatabaseException(\PDOException $exception)
    {
        $code = (string) $exception->getCode();
        if ($code === '23505') {
            throw new ApiException(409, 'unique_constraint_violation', 'Conflict', 'Duplicate value violates unique constraint', null, $exception);
        }
        if ($code === '23503') {
            throw new ApiException(409, 'foreign_key_violation', 'Conflict', 'Foreign key constraint violation', null, $exception);
        }
        if (strpos($code, '22') === 0 || in_array($code, ['23502', '23514'], true)) {
            throw new ApiException(422, 'invalid_attribute_value', 'Invalid Attribute Value', 'One or more attributes have invalid value or format', null, $exception);
        }

        throw new ApiException(500, 'database_error', 'Database Error', 'An internal error occurred', null, $exception);
    }
}
