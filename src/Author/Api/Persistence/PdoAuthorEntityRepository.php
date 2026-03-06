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

    public function __construct(\PDO $db = null)
    {
        $this->db = $db ?: \GCApp::getDB();
    }

    public function findAll(EntityDefinition $definition, QueryOptions $queryOptions)
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
        $table = sprintf('%s.%s', $definition->getSchema(), $definition->getTable());

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
    }

    public function findById(EntityDefinition $definition, $id)
    {
        $table = sprintf('%s.%s', $definition->getSchema(), $definition->getTable());
        $fields = implode(', ', $definition->getReadableFields());
        $sql = sprintf('SELECT %s FROM %s WHERE %s = :id LIMIT 1', $fields, $table, $definition->getPrimaryKey());
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $this->normalizeId($definition, $id),
        ]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
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

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        } catch (\PDOException $exception) {
            $this->rethrowDatabaseException($exception);
        }

        return $this->findById($definition, $attributes[$pk]);
    }

    public function update(EntityDefinition $definition, $id, array $attributes)
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
        $sql = sprintf(
            'UPDATE %s.%s SET %s WHERE %s = :id',
            $definition->getSchema(),
            $definition->getTable(),
            implode(', ', $assignments),
            $definition->getPrimaryKey()
        );

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        } catch (\PDOException $exception) {
            $this->rethrowDatabaseException($exception);
        }

        return $this->findById($definition, $id);
    }

    public function delete(EntityDefinition $definition, $id)
    {
        $sql = sprintf(
            'DELETE FROM %s.%s WHERE %s = :id',
            $definition->getSchema(),
            $definition->getTable(),
            $definition->getPrimaryKey()
        );

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $this->normalizeId($definition, $id),
            ]);
        } catch (\PDOException $exception) {
            $this->rethrowDatabaseException($exception);
        }
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

        throw new ApiException(500, 'database_error', 'Database Error', $exception->getMessage(), null, $exception);
    }
}
