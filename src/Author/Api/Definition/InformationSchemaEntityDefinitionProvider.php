<?php

namespace GisClient\Author\Api\Definition;

use GisClient\Author\Api\Contract\EntityDefinitionProviderInterface;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Model\EntityDefinition;

class InformationSchemaEntityDefinitionProvider implements EntityDefinitionProviderInterface
{
    /**
     * @var array
     */
    private $registry;

    /**
     * @var \PDO
     */
    private $db;

    public function __construct(?array $registry = null, \PDO $db = null)
    {
        $this->registry = $registry ?: EntityRegistry::getDefinitions();
        $this->db = $db ?: \GCApp::getDB();
    }

    public function getEntityDefinition($entity)
    {
        if (!isset($this->registry[$entity])) {
            throw new ApiException(404, 'unknown_entity', 'Unknown Entity', sprintf("Entity '%s' is not managed by this API", $entity));
        }

        $config = $this->registry[$entity];
        $schema = $config['schema'];
        $table = $config['table'];

        $columns = $this->loadColumns($schema, $table);
        if (count($columns) === 0) {
            throw new ApiException(500, 'entity_schema_not_found', 'Entity Schema Not Found', sprintf("No columns found for %s.%s", $schema, $table));
        }

        $primaryKey = $config['primary_key'] ?? $this->loadPrimaryKey($schema, $table);
        if (empty($primaryKey)) {
            throw new ApiException(500, 'entity_primary_key_not_found', 'Entity Schema Error', sprintf("Primary key not found for %s.%s", $schema, $table));
        }

        if (!isset($columns[$primaryKey])) {
            throw new ApiException(500, 'entity_primary_key_missing', 'Entity Schema Error', sprintf("Primary key '%s' not found in %s.%s columns", $primaryKey, $schema, $table));
        }

        $readableFields = array_keys($columns);
        $writableFields = array_values(array_filter($readableFields, static fn ($field) => $field !== $primaryKey));
        if (!empty($config['writable_extra'])) {
            $writableFields = array_merge($writableFields, $config['writable_extra']);
        }
        if (!empty($config['readable_extra'])) {
            $readableFields = array_merge($readableFields, $config['readable_extra']);
        }

        $requiredOnCreate = $this->inferRequiredOnCreate($columns);
        $requiredOnPut = $requiredOnCreate;
        $requiredOnPut = array_values(array_filter($requiredOnPut, static fn ($field) => $field !== $primaryKey));

        if (isset($config['required_on_create'])) {
            $requiredOnCreate = $config['required_on_create'];
        }
        if (isset($config['required_on_put'])) {
            $requiredOnPut = $config['required_on_put'];
        }

        $filterableFields = $config['filterable_fields'] ?? [$primaryKey];
        $sortableFields = $config['sortable_fields'] ?? [$primaryKey];
        $defaultSort = $config['default_sort'] ?? $primaryKey;

        $idType = $config['id_type'] ?? ($columns[$primaryKey]['type'] ?? 'string');
        $attributeRules = $columns;

        return new EntityDefinition(
            $entity,
            $schema,
            $table,
            $primaryKey,
            $idType,
            $readableFields,
            $writableFields,
            $requiredOnCreate,
            $requiredOnPut,
            $filterableFields,
            $sortableFields,
            $defaultSort,
            $attributeRules
        );
    }

    /**
     * @param string $schema
     * @param string $table
     * @return array<string,array{type:string,nullable:bool,has_default:bool,db_type:string}>
     */
    private function loadColumns($schema, $table)
    {
        $sql = 'SELECT column_name, data_type, udt_name, is_nullable, column_default ' .
            'FROM information_schema.columns WHERE table_schema = :schema AND table_name = :table ' .
            'ORDER BY ordinal_position';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':schema' => $schema,
            ':table' => $table,
        ]);

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $columns = [];
        foreach ($rows as $row) {
            $name = $row['column_name'];
            $columns[$name] = [
                'type' => $this->mapDbTypeToApiType($row['data_type'], $row['udt_name']),
                'nullable' => strtoupper((string) $row['is_nullable']) === 'YES',
                'has_default' => $row['column_default'] !== null,
                'db_type' => $row['data_type'],
            ];
        }

        return $columns;
    }

    /**
     * @param string $schema
     * @param string $table
     * @return string|null
     */
    private function loadPrimaryKey($schema, $table)
    {
        $sql = 'SELECT kcu.column_name FROM information_schema.table_constraints tc ' .
            'JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name ' .
            'AND tc.table_schema = kcu.table_schema ' .
            'WHERE tc.table_schema = :schema AND tc.table_name = :table AND tc.constraint_type = :type ' .
            'ORDER BY kcu.ordinal_position';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':schema' => $schema,
            ':table' => $table,
            ':type' => 'PRIMARY KEY',
        ]);
        $keys = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);

        if (count($keys) === 0) {
            return null;
        }
        if (count($keys) > 1) {
            throw new ApiException(500, 'unsupported_primary_key', 'Entity Schema Error', sprintf("Composite primary key for %s.%s is not supported", $schema, $table));
        }

        return (string) $keys[0];
    }

    /**
     * @param array<string,array{nullable:bool,has_default:bool}> $columns
     * @return array
     */
    private function inferRequiredOnCreate(array $columns)
    {
        $required = [];
        foreach ($columns as $field => $meta) {
            if ($meta['nullable'] === false && $meta['has_default'] === false) {
                $required[] = $field;
            }
        }
        return $required;
    }

    /**
     * @param string $dataType
     * @param string $udtName
     * @return string
     */
    private function mapDbTypeToApiType($dataType, $udtName)
    {
        $dataType = strtolower((string) $dataType);
        $udtName = strtolower((string) $udtName);

        if (in_array($dataType, ['smallint', 'integer', 'bigint'], true) || in_array($udtName, ['int2', 'int4', 'int8'], true)) {
            return 'integer';
        }
        if (in_array($dataType, ['numeric', 'real', 'double precision', 'decimal'], true) || in_array($udtName, ['numeric', 'float4', 'float8'], true)) {
            return 'numeric';
        }
        if (in_array($dataType, ['boolean'], true) || $udtName === 'bool') {
            return 'boolean';
        }

        return 'string';
    }
}
