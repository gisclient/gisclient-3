<?php

namespace GisClient\Author\Api\Model;

class EntityDefinition
{
    private $type;
    private $schema;
    private $table;
    private $primaryKey;
    private $idType;
    private $readableFields;
    private $writableFields;
    private $requiredOnCreate;
    private $requiredOnPut;
    private $filterableFields;
    private $sortableFields;
    private $defaultSort;
    private $attributeRules;
    private $scopeFields;
    private $relationships;
    private $requiredRelationshipsOnWrite;

    public function __construct(
        $type,
        $schema,
        $table,
        $primaryKey,
        $idType,
        array $readableFields,
        array $writableFields,
        array $requiredOnCreate,
        array $requiredOnPut,
        array $filterableFields,
        array $sortableFields,
        $defaultSort,
        array $attributeRules = [],
        array $scopeFields = [],
        array $relationships = [],
        array $requiredRelationshipsOnWrite = []
    ) {
        $this->type = $type;
        $this->schema = $schema;
        $this->table = $table;
        $this->primaryKey = $primaryKey;
        $this->idType = $idType;
        $this->readableFields = array_values(array_unique($readableFields));
        $this->writableFields = array_values(array_unique($writableFields));
        $this->requiredOnCreate = array_values(array_unique($requiredOnCreate));
        $this->requiredOnPut = array_values(array_unique($requiredOnPut));
        $this->filterableFields = array_values(array_unique($filterableFields));
        $this->sortableFields = array_values(array_unique($sortableFields));
        $this->defaultSort = $defaultSort;
        $this->attributeRules = $attributeRules;
        $this->scopeFields = array_values(array_unique($scopeFields));
        $this->relationships = $relationships;
        $this->requiredRelationshipsOnWrite = array_values(array_unique($requiredRelationshipsOnWrite));
    }

    public function getType()
    {
        return $this->type;
    }

    public function getSchema()
    {
        return $this->schema;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function getIdType()
    {
        return $this->idType;
    }

    public function getReadableFields()
    {
        return $this->readableFields;
    }

    public function getWritableFields()
    {
        return $this->writableFields;
    }

    public function getRequiredOnCreate()
    {
        return $this->requiredOnCreate;
    }

    public function getRequiredOnPut()
    {
        return $this->requiredOnPut;
    }

    public function getFilterableFields()
    {
        return $this->filterableFields;
    }

    public function getSortableFields()
    {
        return $this->sortableFields;
    }

    public function getDefaultSort()
    {
        return $this->defaultSort;
    }

    public function getAttributeRules()
    {
        return $this->attributeRules;
    }

    public function getAttributeRule($field)
    {
        return $this->attributeRules[$field] ?? null;
    }

    public function getScopeFields()
    {
        return $this->scopeFields;
    }

    public function getRelationships()
    {
        return $this->relationships;
    }

    public function getRequiredRelationshipsOnWrite()
    {
        return $this->requiredRelationshipsOnWrite;
    }
}
