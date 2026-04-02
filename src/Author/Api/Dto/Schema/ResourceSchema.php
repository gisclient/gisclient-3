<?php

namespace GisClient\Author\Api\Dto\Schema;

use GisClient\Author\Shared\Metadata\Metadata;

class ResourceSchema
{
    public static function resource(
        string $type,
        string $dtoClass,
        string $primaryKey,
        string $idPhpType
    ): self {
        return new self($type, $dtoClass, $primaryKey, $idPhpType, [], []);
    }

    public static function fromMetadata(Metadata $metadata): self
    {
        $schema = self::resource(
            $metadata->getType(),
            $metadata->getDtoClass(),
            $metadata->getPrimaryKey(),
            $metadata->getIdPhpType()
        )
            ->requiredOnCreate($metadata->getRequiredOnCreate())
            ->requiredOnPut($metadata->getRequiredOnPut())
            ->filterable($metadata->getFilterableFields())
            ->sortable($metadata->getSortableFields(), $metadata->getDefaultSort());

        foreach ($metadata->getAttributes() as $attribute) {
            $schema->addAttribute(FieldDefinition::attribute(
                $attribute['json_api_name'],
                $attribute['property_name'],
                $attribute['php_type'],
                $attribute['nullable'],
                $attribute['readable'],
                $attribute['writable']
            ));
        }

        foreach ($metadata->getRelationships() as $relationship) {
            $schema->addRelationship(FieldDefinition::relationship(
                $relationship['json_api_name'],
                $relationship['property_name'],
                $relationship['target_dto_class'],
                $relationship['target_type'],
                $relationship['nullable'],
                $relationship['readable'],
                $relationship['writable'],
                $relationship['collection'] ?? false
            ));
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

    public function __construct(
        string $type,
        string $dtoClass,
        string $primaryKey,
        string $idPhpType,
        array $requiredOnCreate,
        array $requiredOnPut
    ) {
        $this->type = $type;
        $this->dtoClass = $dtoClass;
        $this->primaryKey = $primaryKey;
        $this->idPhpType = $idPhpType;
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

    public function getFilterableFields(): array
    {
        return $this->filterableFields !== [] ? $this->filterableFields : [$this->primaryKey];
    }

    /**
     * @return array<int,string>
     */
    public function getSortableFields(): array
    {
        return $this->sortableFields !== [] ? $this->sortableFields : [$this->primaryKey];
    }

    public function getDefaultSort(): string
    {
        return $this->defaultSort ?? $this->primaryKey;
    }
}
