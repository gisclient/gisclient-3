<?php

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\FieldDto;
use GisClient\Author\Api\Dto\LayerDto;
use GisClient\Author\Api\Dto\RelationDto;

class FieldMetadata extends Metadata
{
    public function __construct()
    {
        parent::__construct('field', FieldDto::class, 'field_id', 'int');

        $this
            ->requiredOnCreate(['layer', 'field_name', 'field_header'])
            ->requiredOnPut(['layer', 'field_name', 'field_header'])
            ->filterable(['field_id', 'layer_id', 'relation_id', 'field_name', 'field_header', 'field_order', 'fieldtype_id', 'datatype_id', 'searchtype_id', 'resultype_id'])
            ->sortable(['field_id', 'field_order', 'field_name', 'field_header'], 'field_order')
            ->addAttribute('field_name', 'fieldName', 'string')
            ->addAttribute('field_header', 'fieldHeader', 'string')
            ->addAttribute('field_order', 'fieldOrder', 'int')
            ->addAttribute('fieldtype_id', 'fieldtypeId', 'int', false, true, true, null, [
                'lookup' => [
                    'table' => 'e_fieldtype',
                    'column' => 'fieldtype_id',
                ],
            ])
            ->addAttribute('datatype_id', 'datatypeId', 'int', false, true, true, null, [
                'lookup' => [
                    'table' => 'e_datatype',
                    'column' => 'datatype_id',
                ],
            ])
            ->addAttribute('formula', 'formula', 'string', true)
            ->addAttribute('field_format', 'fieldFormat', 'string', true)
            ->addAttribute('resultype_id', 'resultypeId', 'int', false, true, true, null, [
                'lookup' => [
                    'table' => 'e_resultype',
                    'column' => 'resultype_id',
                ],
            ])
            ->addAttribute('searchtype_id', 'searchtypeId', 'int', false, true, true, null, [
                'lookup' => [
                    'table' => 'e_searchtype',
                    'column' => 'searchtype_id',
                ],
            ])
            ->addAttribute('filter_field_name', 'filterFieldName', 'string', true)
            ->addAttribute('orderby_id', 'orderbyId', 'int', false, true, true, null, [
                'lookup' => [
                    'table' => 'e_orderby',
                    'column' => 'orderby_id',
                ],
            ])
            ->addAttribute('default_op', 'defaultOp', 'string', true)
            ->addAttribute('editable', 'editable', 'float', true)
            ->addAttribute('mandatory', 'mandatory', 'float', true)
            ->addAttribute('lookup_table', 'lookupTable', 'string', true)
            ->addAttribute('lookup_id', 'lookupId', 'string', true)
            ->addAttribute('lookup_name', 'lookupName', 'string', true)
            ->addRelationship('layer', 'layer', LayerDto::class, 'layer', true, true, true, 'layer_id')
            ->addRelationship('relation', 'relation', RelationDto::class, 'relation', true, true, true, 'relation_id');
    }
}
