<?php

namespace GisClient\Author\Persistence;

use GisClient\Author\Shared\Metadata\Metadata;

class EntitySchema
{
    public const DEFAULT_DB_SCHEMA = 'gisclient_34';

    public static function entity(
        string $type,
        string $primaryKey,
        string $idPhpType,
        ?string $table = null,
        ?string $dbSchema = null
    ): self {
        return new self($type, $primaryKey, $idPhpType, $table, $dbSchema);
    }

    public static function fromMetadata(Metadata $metadata): self
    {
        $schema = self::entity(
            $metadata->getType(),
            $metadata->getPrimaryKey(),
            $metadata->getIdPhpType(),
            $metadata->getTable(),
            $metadata->getDbSchema()
        )
            ->filterable($metadata->getFilterableFields())
            ->sortable($metadata->getSortableFields(), $metadata->getDefaultSort());

        foreach ($metadata->getAttributes() as $attribute) {
            $schema->addAttribute(
                $attribute['json_api_name'],
                $attribute['php_type'],
                $attribute['column'],
                $attribute['readable'],
                $attribute['writable'],
                $attribute['rules'],
                $attribute['transform'] ?? null
            );
        }

        foreach ($metadata->getRelationships() as $relationship) {
            $schema->addRelationship(
                $relationship['json_api_name'],
                $relationship['column'],
                $relationship['readable'],
                $relationship['writable'],
                $relationship['collection'] ?? false,
                $relationship['junction_table'] ?? null,
                $relationship['junction_local_key'] ?? null,
                $relationship['junction_foreign_key'] ?? null
            );
        }

        return $schema;
    }

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $primaryKey;

    /**
     * @var string
     */
    private $idPhpType;

    /**
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $dbSchema;

    /**
     * @var array<string,array{column:string,readable:bool,writable:bool,rules:array<string,mixed>,transform:string|null}>
     */
    private $attributes = [];

    /**
     * @var array<string,array{column:string|null,readable:bool,writable:bool,collection:bool,junction_table:string|null,junction_local_key:string|null,junction_foreign_key:string|null}>
     */
    private $relationships = [];

    /**
     * @var array<string,string>
     */
    private $filterColumns = [];

    /**
     * @var array<string,string>
     */
    private $sortColumns = [];

    /**
     * @var string|null
     */
    private $defaultSortColumn;

    public function __construct(
        string $type,
        string $primaryKey,
        string $idPhpType,
        ?string $table = null,
        ?string $dbSchema = null
    ) {
        $this->type = $type;
        $this->primaryKey = $primaryKey;
        $this->idPhpType = $idPhpType;
        $this->table = $table ?? $type;
        $this->dbSchema = $dbSchema ?? self::DEFAULT_DB_SCHEMA;
        $this->defaultSortColumn = $primaryKey;
    }

    /**
     * @param array<string,mixed> $rules
     */
    public function addAttribute(
        string $publicName,
        string $phpType,
        ?string $column = null,
        bool $readable = true,
        bool $writable = true,
        array $rules = [],
        ?string $transform = null
    ): self {
        $column ??= $publicName;
        $type = $this->normalizeRuleType($phpType);

        if ($type !== null) {
            $rules = array_merge([
                'type' => $type,
            ], $rules);
        }

        $this->attributes[$publicName] = [
            'column' => $column,
            'readable' => $readable,
            'writable' => $writable,
            'rules' => $rules,
            'transform' => $transform,
        ];

        return $this;
    }

    public function addRelationship(
        string $relationshipName,
        ?string $column,
        bool $readable = true,
        bool $writable = true,
        bool $collection = false,
        ?string $junctionTable = null,
        ?string $junctionLocalKey = null,
        ?string $junctionForeignKey = null
    ): self {
        $this->relationships[$relationshipName] = [
            'column' => $column,
            'readable' => $readable,
            'writable' => $writable,
            'collection' => $collection,
            'junction_table' => $junctionTable,
            'junction_local_key' => $junctionLocalKey,
            'junction_foreign_key' => $junctionForeignKey,
        ];

        return $this;
    }

    /**
     * @param array<int,string>|array<string,string> $fields
     */
    public function filterable(array $fields): self
    {
        $this->filterColumns = $this->normalizeFieldMappings($fields);

        return $this;
    }

    /**
     * @param array<int,string>|array<string,string> $fields
     */
    public function sortable(array $fields, ?string $defaultSort = null): self
    {
        $this->sortColumns = $this->normalizeFieldMappings($fields);
        $this->defaultSortColumn = $defaultSort === null
            ? $this->primaryKey
            : ($this->sortColumns[$defaultSort] ?? $defaultSort);

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function getIdPhpType(): string
    {
        return $this->idPhpType;
    }

    public function getResolvedTable(): string
    {
        return $this->table;
    }

    public function getResolvedDbSchema(): string
    {
        return $this->dbSchema;
    }

    public function getAttributeColumn(string $publicName): ?string
    {
        return $this->attributes[$publicName]['column'] ?? null;
    }

    public function getAttributeTransform(string $publicName): ?string
    {
        return $this->attributes[$publicName]['transform'] ?? null;
    }

    public function getRelationshipColumn(string $relationshipName): ?string
    {
        return $this->relationships[$relationshipName]['column'] ?? null;
    }

    /**
     * Returns configuration for collection (junction-table) relationships only.
     *
     * @return array<string,array{readable:bool,writable:bool,junction_table:string,junction_local_key:string,junction_foreign_key:string}>
     */
    public function getCollectionRelationships(): array
    {
        $result = [];
        foreach ($this->relationships as $name => $rel) {
            if ($rel['collection'] ?? false) {
                $result[$name] = $rel;
            }
        }

        return $result;
    }

    /**
     * @return array<string,string>
     */
    public function getReadableAttributeColumns(): array
    {
        $columns = [];

        foreach ($this->attributes as $publicName => $attribute) {
            if ($attribute['readable']) {
                $columns[$publicName] = $attribute['column'];
            }
        }

        return $columns;
    }

    /**
     * @return array<string,string>
     */
    public function getReadableRelationshipColumns(): array
    {
        $columns = [];

        foreach ($this->relationships as $relationshipName => $relationship) {
            if ($relationship['readable']) {
                $columns[$relationshipName] = $relationship['column'];
            }
        }

        return $columns;
    }

    /**
     * @return array<int,string>
     */
    public function getReadableDbFields(): array
    {
        $fields = [$this->primaryKey];

        foreach ($this->attributes as $attribute) {
            if ($attribute['readable']) {
                $fields[] = $attribute['column'];
            }
        }

        foreach ($this->relationships as $relationship) {
            if (($relationship['collection'] ?? false) || $relationship['column'] === null) {
                continue;
            }
            if ($relationship['readable']) {
                $fields[] = $relationship['column'];
            }
        }

        return array_values(array_unique($fields));
    }

    /**
     * @return array<int,string>
     */
    public function getWritableDbFields(): array
    {
        $fields = [];

        foreach ($this->attributes as $attribute) {
            if ($attribute['writable']) {
                $fields[] = $attribute['column'];
            }
        }

        foreach ($this->relationships as $relationship) {
            if (($relationship['collection'] ?? false) || $relationship['column'] === null) {
                continue;
            }
            if ($relationship['writable']) {
                $fields[] = $relationship['column'];
            }
        }

        return array_values(array_unique($fields));
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function getAttributeRules(): array
    {
        $rules = [];

        foreach ($this->attributes as $attribute) {
            if ($attribute['rules'] !== []) {
                $rules[$attribute['column']] = $attribute['rules'];
            }
        }

        return $rules;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getAttributeRule(string $field): ?array
    {
        return $this->getAttributeRules()[$field] ?? null;
    }

    public function translateFilterField(string $publicField): string
    {
        return $this->filterColumns[$publicField] ?? $publicField;
    }

    public function translateSortField(string $publicField): string
    {
        return $this->sortColumns[$publicField] ?? $publicField;
    }

    public function getDefaultSortColumn(): string
    {
        return $this->defaultSortColumn ?? $this->primaryKey;
    }

    /**
     * @param array<int,string>|array<string,string> $fields
     * @return array<string,string>
     */
    private function normalizeFieldMappings(array $fields): array
    {
        $normalized = [];

        foreach ($fields as $publicField => $column) {
            if (is_int($publicField)) {
                $normalized[(string) $column] = (string) $column;
                continue;
            }

            $normalized[(string) $publicField] = (string) $column;
        }

        return $normalized;
    }

    private function normalizeRuleType(string $phpType): ?string
    {
        if ($phpType === 'int') {
            return 'integer';
        }

        if ($phpType === 'float') {
            return 'numeric';
        }

        if ($phpType === 'bool') {
            return 'boolean';
        }

        if ($phpType === 'string') {
            return 'string';
        }

        return null;
    }
}
