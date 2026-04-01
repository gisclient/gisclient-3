<?php

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\CatalogDto;
use GisClient\Author\Api\Dto\LayerDto;
use GisClient\Author\Api\Dto\RelationDto;

class RelationMetadata extends Metadata
{
    public function __construct()
    {
        parent::__construct('relation', RelationDto::class, 'relation_id', 'int');

        $this
            ->requiredOnCreate(['catalog', 'layer', 'relation_name', 'data_field_1', 'table_name', 'table_field_1'])
            ->requiredOnPut(['catalog', 'layer', 'relation_name', 'data_field_1', 'table_name', 'table_field_1'])
            ->filterable(['relation_id', 'catalog_id', 'layer_id', 'relation_name', 'relationtype_id'])
            ->sortable(['relation_id', 'relation_name'], 'relation_name')
            ->addAttribute('relation_name', 'relationName', 'string')
            ->addAttribute('relationtype_id', 'relationtypeId', 'int', false, true, true, null, [
                'lookup' => [
                    'table' => 'e_relationtype',
                    'column' => 'relationtype_id',
                ],
            ])
            ->addAttribute('data_field_1', 'dataField1', 'string')
            ->addAttribute('data_field_2', 'dataField2', 'string', true)
            ->addAttribute('data_field_3', 'dataField3', 'string', true)
            ->addAttribute('table_name', 'tableName', 'string')
            ->addAttribute('table_field_1', 'tableField1', 'string')
            ->addAttribute('table_field_2', 'tableField2', 'string', true)
            ->addAttribute('table_field_3', 'tableField3', 'string', true)
            ->addAttribute('relation_title', 'relationTitle', 'string', true)
            ->addRelationship('catalog', 'catalog', CatalogDto::class, 'catalog', false, true, true, 'catalog_id')
            ->addRelationship('layer', 'layer', LayerDto::class, 'layer', false, true, true, 'layer_id');
    }
}
