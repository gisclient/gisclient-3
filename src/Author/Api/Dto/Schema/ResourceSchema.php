<?php

namespace GisClient\Author\Api\Dto\Schema;

class ResourceSchema
{
    public const DEFAULT_DB_SCHEMA = 'gisclient_34';

    public static function resource(
        string $type,
        string $dtoClass,
        string $primaryKey,
        string $idPhpType,
        bool $clientGeneratedIdAllowed = true
    ): self {
        return new self($type, $dtoClass, $primaryKey, $idPhpType, $clientGeneratedIdAllowed, [], []);
    }

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $dtoClass;

    /**
     * @var string
     */
    private $primaryKey;

    /**
     * @var string
     */
    private $idPhpType;

    /**
     * @var bool
     */
    private $clientGeneratedIdAllowed;

    /**
     * @var array<string,FieldDefinition>
     */
    private $attributes = [];

    /**
     * @var array<string,FieldDefinition>
     */
    private $relationships = [];

    /**
     * @var array<int,string>
     */
    private $requiredOnCreate;

    /**
     * @var array<int,string>
     */
    private $requiredOnPut;

    /**
     * @var array<int,string>
     */
    private $filterableFields = [];

    /**
     * @var array<int,string>
     */
    private $sortableFields = [];

    /**
     * @var string|null
     */
    private $defaultSort;

    /**
     * @var string|null
     */
    private $table;

    /**
     * @var string|null
     */
    private $dbSchema;

    public function __construct(
        string $type,
        string $dtoClass,
        string $primaryKey,
        string $idPhpType,
        bool $clientGeneratedIdAllowed,
        array $requiredOnCreate,
        array $requiredOnPut
    ) {
        $this->type = $type;
        $this->dtoClass = $dtoClass;
        $this->primaryKey = $primaryKey;
        $this->idPhpType = $idPhpType;
        $this->clientGeneratedIdAllowed = $clientGeneratedIdAllowed;
        $this->requiredOnCreate = $requiredOnCreate;
        $this->requiredOnPut = $requiredOnPut;
    }

    public function addAttribute(FieldDefinition $field): self
    {
        $this->attributes[$field->getJsonApiName()] = $field;
        return $this;
    }

    public function addRelationship(FieldDefinition $field): self
    {
        $this->relationships[$field->getJsonApiName()] = $field;
        return $this;
    }

    public function requiredOnCreate(array $fields): self
    {
        $this->requiredOnCreate = array_values($fields);
        return $this;
    }

    public function requiredOnPut(array $fields): self
    {
        $this->requiredOnPut = array_values($fields);
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDtoClass(): string
    {
        return $this->dtoClass;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function getIdPhpType(): string
    {
        return $this->idPhpType;
    }

    public function isClientGeneratedIdAllowed(): bool
    {
        return $this->clientGeneratedIdAllowed;
    }

    /**
     * @return array<string,FieldDefinition>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<string,FieldDefinition>
     */
    public function getRelationships(): array
    {
        return $this->relationships;
    }

    public function getAttribute(string $name): ?FieldDefinition
    {
        return $this->attributes[$name] ?? null;
    }

    public function getRelationship(string $name): ?FieldDefinition
    {
        return $this->relationships[$name] ?? null;
    }

    /**
     * @return array<int,string>
     */
    public function getRequiredOnCreate(): array
    {
        return $this->requiredOnCreate;
    }

    /**
     * @return array<int,string>
     */
    public function getRequiredOnPut(): array
    {
        return $this->requiredOnPut;
    }

    public function filterable(array $fields): self
    {
        $this->filterableFields = array_values($fields);

        return $this;
    }

    public function sortable(array $fields, ?string $defaultSort = null): self
    {
        $this->sortableFields = array_values($fields);
        $this->defaultSort = $defaultSort;

        return $this;
    }

    public function storedAs(?string $table = null, ?string $dbSchema = null): self
    {
        $this->table = $table;
        $this->dbSchema = $dbSchema;

        return $this;
    }

    /**
     * @return array<int,string>
     */
    public function getFilterableFields(): array
    {
        return $this->filterableFields;
    }

    /**
     * @return array<int,string>
     */
    public function getSortableFields(): array
    {
        return $this->sortableFields;
    }

    public function getDefaultSort(): ?string
    {
        return $this->defaultSort;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getDbSchema(): ?string
    {
        return $this->dbSchema;
    }

    public function getResolvedTable(): string
    {
        return $this->table ?? $this->type;
    }

    public function getResolvedDbSchema(?string $default = null): string
    {
        return $this->dbSchema ?? ($default ?? self::DEFAULT_DB_SCHEMA);
    }

    /**
     * @return array<int,string>
     */
    public function getReadableDbFields(): array
    {
        $fields = [$this->primaryKey];

        foreach ($this->attributes as $field) {
            if ($field->isReadable()) {
                $fields[] = $field->getLocalKey() ?? $field->getJsonApiName();
            }
        }

        foreach ($this->relationships as $field) {
            if ($field->getLocalKey() !== null) {
                $fields[] = $field->getLocalKey();
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

        foreach ($this->attributes as $field) {
            if ($field->isWritable()) {
                $fields[] = $field->getLocalKey() ?? $field->getJsonApiName();
            }
        }

        foreach ($this->relationships as $field) {
            if ($field->isWritable() && $field->getLocalKey() !== null) {
                $fields[] = $field->getLocalKey();
            }
        }

        return array_values(array_unique($fields));
    }

    /**
     * @return array<int,string>
     */
    public function getEffectiveFilterableFields(): array
    {
        if ($this->filterableFields !== []) {
            return $this->filterableFields;
        }

        return $this->getReadableDbFields();
    }

    /**
     * @return array<int,string>
     */
    public function getEffectiveSortableFields(): array
    {
        if ($this->sortableFields !== []) {
            return $this->sortableFields;
        }

        return [$this->primaryKey];
    }

    public function getEffectiveDefaultSort(): string
    {
        return $this->defaultSort ?? $this->primaryKey;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function getAttributeRules(): array
    {
        $rules = [];

        foreach ($this->attributes as $field) {
            $localKey = $field->getLocalKey() ?? $field->getJsonApiName();
            $type = $this->normalizeRuleType($field->getPhpType());
            $fieldRules = $field->getRules();

            if ($type !== null) {
                $fieldRules = array_merge([
                    'type' => $type,
                ], $fieldRules);
            }

            if ($fieldRules !== []) {
                $rules[$localKey] = $fieldRules;
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
