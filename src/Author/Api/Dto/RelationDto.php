<?php

namespace GisClient\Author\Api\Dto;

class RelationDto extends JsonApiDto
{
    public ?int $id = null;

    public string $relationName;

    public int $relationtypeId;

    public string $dataField1;

    public ?string $dataField2 = null;

    public ?string $dataField3 = null;

    public string $tableName;

    public string $tableField1;

    public ?string $tableField2 = null;

    public ?string $tableField3 = null;

    public ?string $relationTitle = null;

    public CatalogDto $catalog;

    public LayerDto $layer;
}
