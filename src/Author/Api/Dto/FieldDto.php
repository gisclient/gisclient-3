<?php

namespace GisClient\Author\Api\Dto;

class FieldDto extends JsonApiDto
{
    public ?int $id = null;

    public string $fieldName;

    public string $fieldHeader;

    public int $fieldOrder;

    public int $fieldtypeId;

    public int $datatypeId;

    public ?string $formula = null;

    public ?string $fieldFormat = null;

    public int $resultypeId;

    public int $searchtypeId;

    public ?string $filterFieldName = null;

    public int $orderbyId;

    public ?string $defaultOp = null;

    public ?float $editable = null;

    public ?float $mandatory = null;

    public ?string $lookupTable = null;

    public ?string $lookupId = null;

    public ?string $lookupName = null;

    public ?LayerDto $layer = null;

    public ?RelationDto $relation = null;
}
