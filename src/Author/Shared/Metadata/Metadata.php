<?php

namespace GisClient\Author\Shared\Metadata;

class Metadata
{
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
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $dbSchema;

    /**
     * @var array<int,string>
     */
    private $requiredOnCreate = [];

    /**
     * @var array<int,string>
     */
    private $requiredOnPut = [];

    /**
     * @var array<int,string>|array<string,string>
     */
    private $filterableFields = [];

    /**
     * @var array<int,string>|array<string,string>
     */
    private $sortableFields = [];

    /**
     * @var string|null
     */
    private $defaultSort;

    /**
     * @var array<string,array<string,mixed>>
     */
    private $attributes = [];

    /**
     * @var array<string,array<string,mixed>>
     */
    private $relationships = [];

    public function __construct(
        string $type,
        string $dtoClass,
        string $primaryKey,
        string $idPhpType,
        ?string $table = null,
        ?string $dbSchema = null
    ) {
        $this->type = $type;
        $this->dtoClass = $dtoClass;
        $this->primaryKey = $primaryKey;
        $this->idPhpType = $idPhpType;
        $this->table = $table ?? $type;
        $this->dbSchema = $dbSchema ?? 'gisclient_34';
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

    /**
     * @param array<int,string>|array<string,string> $fields
     */
    public function filterable(array $fields): self
    {
        $this->filterableFields = $fields;

        return $this;
    }

    /**
     * @param array<int,string>|array<string,string> $fields
     */
    public function sortable(array $fields, ?string $defaultSort = null): self
    {
        $this->sortableFields = $fields;
        $this->defaultSort = $defaultSort;

        return $this;
    }

    /**
     * @param array<string,mixed> $rules
     */
    public function addAttribute(
        string $jsonApiName,
        string $propertyName,
        string $phpType,
        bool $nullable = false,
        bool $readable = true,
        bool $writable = true,
        ?string $column = null,
        array $rules = []
    ): self {
        $this->attributes[$jsonApiName] = [
            'json_api_name' => $jsonApiName,
            'property_name' => $propertyName,
            'php_type' => $phpType,
            'nullable' => $nullable,
            'readable' => $readable,
            'writable' => $writable,
            'column' => $column ?? $jsonApiName,
            'rules' => $rules,
        ];

        return $this;
    }

    public function addRelationship(
        string $jsonApiName,
        string $propertyName,
        string $targetDtoClass,
        string $targetType,
        bool $nullable = false,
        bool $readable = true,
        bool $writable = true,
        bool $allowIdentifierOnly = true,
        ?string $column = null
    ): self {
        $this->relationships[$jsonApiName] = [
            'json_api_name' => $jsonApiName,
            'property_name' => $propertyName,
            'target_dto_class' => $targetDtoClass,
            'target_type' => $targetType,
            'nullable' => $nullable,
            'readable' => $readable,
            'writable' => $writable,
            'allow_identifier_only' => $allowIdentifierOnly,
            'column' => $column ?? $jsonApiName,
        ];

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

    public function getTable(): string
    {
        return $this->table;
    }

    public function getDbSchema(): string
    {
        return $this->dbSchema;
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

    /**
     * @return array<int,string>|array<string,string>
     */
    public function getFilterableFields(): array
    {
        return $this->filterableFields;
    }

    /**
     * @return array<int,string>|array<string,string>
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
     * @return array<string,array<string,mixed>>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function getRelationships(): array
    {
        return $this->relationships;
    }
}
