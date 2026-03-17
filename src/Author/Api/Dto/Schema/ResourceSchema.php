<?php

namespace GisClient\Author\Api\Dto\Schema;

class ResourceSchema
{
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
     * @var array<int,string>
     */
    private $scopeFields = [];

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

    public function scopedBy(array $fields): self
    {
        $this->scopeFields = array_values($fields);

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

    /**
     * @return array<int,string>
     */
    public function getScopeFields(): array
    {
        return $this->scopeFields;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getDbSchema(): ?string
    {
        return $this->dbSchema;
    }
}
