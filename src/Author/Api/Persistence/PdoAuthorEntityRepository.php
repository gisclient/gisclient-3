<?php

namespace GisClient\Author\Api\Persistence;

use GisClient\Author\Api\Contract\AuthorEntityRepositoryInterface;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;
use GisClient\Author\Api\Exception\ApiException;
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

    public function findAll(ResourceSchema $schema, QueryOptions $queryOptions)
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
        $whereSql = count($where) > 0 ? (' WHERE ' . implode(' AND ', $where)) : '';
        $table = sprintf('%s.%s', $schema->getResolvedDbSchema(), $schema->getResolvedTable());

        return $this->executeSafely(function () use ($schema, $queryOptions, $params, $whereSql, $table) {
            $countSql = sprintf('SELECT COUNT(*) FROM %s%s', $table, $whereSql);
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = (int) $stmt->fetchColumn();

            $fields = implode(', ', $schema->getReadableDbFields());
            $selectSql = sprintf('SELECT %s FROM %s%s', $fields, $table, $whereSql);

            $sortField = $queryOptions->getSortField() ?: $schema->getEffectiveDefaultSort();
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

    public function findById(ResourceSchema $schema, $id)
    {
        $table = sprintf('%s.%s', $schema->getResolvedDbSchema(), $schema->getResolvedTable());
        $fields = implode(', ', $schema->getReadableDbFields());

        return $this->executeSafely(function () use ($schema, $id, $table, $fields) {
            $params = [
                ':id' => $this->normalizeId($schema, $id),
            ];
            $sql = sprintf('SELECT %s FROM %s WHERE %s = :id LIMIT 1', $fields, $table, $schema->getPrimaryKey());
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $row === false ? null : $row;
        });
    }

    public function create(ResourceSchema $schema, array $attributes)
    {
        $pk = $schema->getPrimaryKey();
        if ($schema->getIdPhpType() === 'int' && empty($attributes[$pk])) {
            $attributes[$pk] = \GCApp::getNewPKey(DB_SCHEMA, $schema->getResolvedDbSchema(), $schema->getResolvedTable(), $pk);
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

        return $this->findById($schema, $attributes[$pk]);
    }

    public function update(ResourceSchema $schema, $id, array $attributes)
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

        $params[':id'] = $this->normalizeId($schema, $id);
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

        return $this->findById($schema, $id);
    }

    public function delete(ResourceSchema $schema, $id)
    {
        $params = [
            ':id' => $this->normalizeId($schema, $id),
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
    private function normalizeId(ResourceSchema $schema, $id)
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
