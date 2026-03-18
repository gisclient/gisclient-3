<?php

namespace GisClient\Author\Api\Dto;

use GisClient\Author\Api\Dto\Schema\FieldDefinition;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;

class FieldDto extends JsonApiDto
{
    public ?int $id = null;

    public int $relationId;

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

    public static function schema(): ResourceSchema
    {
        return ResourceSchema::resource('field', self::class, 'field_id', 'int')
            ->requiredOnCreate(['layer_id', 'field_name', 'field_header'])
            ->requiredOnPut(['layer_id', 'field_name', 'field_header'])
            ->filterable(['field_id', 'layer_id', 'relation_id', 'field_name', 'field_header', 'field_order', 'fieldtype_id', 'datatype_id', 'searchtype_id', 'resultype_id'])
            ->sortable(['field_id', 'field_order', 'field_name', 'field_header'], 'field_order')
            ->addAttribute(FieldDefinition::attribute('relation_id', 'relationId', 'int'))
            ->addAttribute(FieldDefinition::attribute('field_name', 'fieldName', 'string'))
            ->addAttribute(FieldDefinition::attribute('field_header', 'fieldHeader', 'string'))
            ->addAttribute(FieldDefinition::attribute('field_order', 'fieldOrder', 'int'))
            ->addAttribute(FieldDefinition::attribute('fieldtype_id', 'fieldtypeId', 'int')->withLookup([
                'table' => 'e_fieldtype',
                'column' => 'fieldtype_id',
            ]))
            ->addAttribute(FieldDefinition::attribute('datatype_id', 'datatypeId', 'int')->withLookup([
                'table' => 'e_datatype',
                'column' => 'datatype_id',
            ]))
            ->addAttribute(FieldDefinition::attribute('formula', 'formula', 'string', true))
            ->addAttribute(FieldDefinition::attribute('field_format', 'fieldFormat', 'string', true))
            ->addAttribute(FieldDefinition::attribute('resultype_id', 'resultypeId', 'int')->withLookup([
                'table' => 'e_resultype',
                'column' => 'resultype_id',
            ]))
            ->addAttribute(FieldDefinition::attribute('searchtype_id', 'searchtypeId', 'int')->withLookup([
                'table' => 'e_searchtype',
                'column' => 'searchtype_id',
            ]))
            ->addAttribute(FieldDefinition::attribute('filter_field_name', 'filterFieldName', 'string', true))
            ->addAttribute(FieldDefinition::attribute('orderby_id', 'orderbyId', 'int')->withLookup([
                'table' => 'e_orderby',
                'column' => 'orderby_id',
            ]))
            ->addAttribute(FieldDefinition::attribute('default_op', 'defaultOp', 'string', true))
            ->addAttribute(FieldDefinition::attribute('editable', 'editable', 'float', true))
            ->addAttribute(FieldDefinition::attribute('mandatory', 'mandatory', 'float', true))
            ->addAttribute(FieldDefinition::attribute('lookup_table', 'lookupTable', 'string', true))
            ->addAttribute(FieldDefinition::attribute('lookup_id', 'lookupId', 'string', true))
            ->addAttribute(FieldDefinition::attribute('lookup_name', 'lookupName', 'string', true))
            ->addRelationship(FieldDefinition::relationship('layer', 'layer', LayerDto::class, 'layer', 'layer_id', true));
    }
}
