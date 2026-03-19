<?php

namespace GisClient\Author\Api\Dto;

use GisClient\Author\Api\Dto\Schema\FieldDefinition;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;

class LayerDto extends JsonApiDto
{
    public ?int $id = null;

    public string $layerName;

    public ?string $layerTitle = null;

    public ?int $layerOrder = null;

    public ?string $opacity = null;

    public int $layertypeId;

    public ?string $dataType = null;

    public CatalogDto $catalog;

    public ?string $data = null;

    public ?string $dataGeom = null;

    public ?string $dataUnique = null;

    public ?int $dataSrid = null;

    public ?string $maxscale = null;

    public ?string $minscale = null;

    public ?int $symbolscale = null;

    public float $sizeunitsId;

    public ?string $dataExtent = null;

    public ?string $dataFilter = null;

    public ?string $layerDef = null;

    public ?string $metadata = null;

    public ?string $labelitem = null;

    public ?string $labelsizeitem = null;

    public ?string $labelmaxscale = null;

    public ?string $labelminscale = null;

    public ?float $postlabelcache = null;

    public ?string $classitem = null;

    public ?float $private = null;

    public ?float $queryable = null;

    public ?float $hideVectorGeom = null;

    public ?float $hidden = null;

    public ?float $searchableId = null;

    public ?string $template = null;

    public ?string $header = null;

    public ?string $footer = null;

    public ?int $tolerance = null;

    public ?float $toleranceunitsId = null;

    public ?float $selectionWidth = null;

    public ?string $selectionColor = null;

    public ?int $maxfeatures = null;

    public ?int $maxvectfeatures = null;

    public ?float $zoomBuffer = null;

    public ?string $lastUpdate = null;

    public LayergroupDto $layergroup;

    public static function schema(): ResourceSchema
    {
        return ResourceSchema::resource('layer', self::class, 'layer_id', 'int')
            ->requiredOnCreate(['layergroup', 'layer_name', 'layertype_id', 'catalog'])
            ->requiredOnPut(['layergroup', 'layer_name', 'layertype_id', 'catalog'])
            ->filterable([
                'layer_id',
                'layergroup_id',
                'catalog_id',
                'layertype_id',
                'layer_name',
                'layer_title',
                'layer_order',
                'queryable',
                'private',
                'hidden',
                'searchable_id',
            ])
            ->sortable(['layer_id', 'layer_order', 'layer_name', 'layer_title', 'layertype_id'], 'layer_order')
            ->addAttribute(FieldDefinition::attribute('layer_name', 'layerName', 'string'))
            ->addAttribute(FieldDefinition::attribute('layer_title', 'layerTitle', 'string', true))
            ->addAttribute(FieldDefinition::attribute('layer_order', 'layerOrder', 'int', true))
            ->addAttribute(FieldDefinition::attribute('opacity', 'opacity', 'string', true))
            ->addAttribute(FieldDefinition::attribute('layertype_id', 'layertypeId', 'int'))
            ->addAttribute(FieldDefinition::attribute('data_type', 'dataType', 'string', true))
            ->addAttribute(FieldDefinition::attribute('data', 'data', 'string', true))
            ->addAttribute(FieldDefinition::attribute('data_geom', 'dataGeom', 'string', true))
            ->addAttribute(FieldDefinition::attribute('data_unique', 'dataUnique', 'string', true))
            ->addAttribute(FieldDefinition::attribute('data_srid', 'dataSrid', 'int', true))
            ->addAttribute(FieldDefinition::attribute('maxscale', 'maxscale', 'string', true))
            ->addAttribute(FieldDefinition::attribute('minscale', 'minscale', 'string', true))
            ->addAttribute(FieldDefinition::attribute('symbolscale', 'symbolscale', 'int', true))
            ->addAttribute(FieldDefinition::attribute('sizeunits_id', 'sizeunitsId', 'float'))
            ->addAttribute(FieldDefinition::attribute('data_extent', 'dataExtent', 'string', true))
            ->addAttribute(FieldDefinition::attribute('data_filter', 'dataFilter', 'string', true))
            ->addAttribute(FieldDefinition::attribute('layer_def', 'layerDef', 'string', true))
            ->addAttribute(FieldDefinition::attribute('metadata', 'metadata', 'string', true))
            ->addAttribute(FieldDefinition::attribute('labelitem', 'labelitem', 'string', true))
            ->addAttribute(FieldDefinition::attribute('labelsizeitem', 'labelsizeitem', 'string', true))
            ->addAttribute(FieldDefinition::attribute('labelmaxscale', 'labelmaxscale', 'string', true))
            ->addAttribute(FieldDefinition::attribute('labelminscale', 'labelminscale', 'string', true))
            ->addAttribute(FieldDefinition::attribute('postlabelcache', 'postlabelcache', 'float', true))
            ->addAttribute(FieldDefinition::attribute('classitem', 'classitem', 'string', true))
            ->addAttribute(FieldDefinition::attribute('private', 'private', 'float', true))
            ->addAttribute(FieldDefinition::attribute('queryable', 'queryable', 'float', true))
            ->addAttribute(FieldDefinition::attribute('hide_vector_geom', 'hideVectorGeom', 'float', true))
            ->addAttribute(FieldDefinition::attribute('hidden', 'hidden', 'float', true))
            ->addAttribute(FieldDefinition::attribute('searchable_id', 'searchableId', 'float', true))
            ->addAttribute(FieldDefinition::attribute('template', 'template', 'string', true))
            ->addAttribute(FieldDefinition::attribute('header', 'header', 'string', true))
            ->addAttribute(FieldDefinition::attribute('footer', 'footer', 'string', true))
            ->addAttribute(FieldDefinition::attribute('tolerance', 'tolerance', 'int', true))
            ->addAttribute(FieldDefinition::attribute('toleranceunits_id', 'toleranceunitsId', 'float', true))
            ->addAttribute(FieldDefinition::attribute('selection_width', 'selectionWidth', 'float', true))
            ->addAttribute(FieldDefinition::attribute('selection_color', 'selectionColor', 'string', true))
            ->addAttribute(FieldDefinition::attribute('maxfeatures', 'maxfeatures', 'int', true))
            ->addAttribute(FieldDefinition::attribute('maxvectfeatures', 'maxvectfeatures', 'int', true))
            ->addAttribute(FieldDefinition::attribute('zoom_buffer', 'zoomBuffer', 'float', true))
            ->addAttribute(FieldDefinition::attribute('last_update', 'lastUpdate', 'string', true))
            ->addRelationship(FieldDefinition::relationship('catalog', 'catalog', CatalogDto::class, 'catalog'))
            ->addRelationship(FieldDefinition::relationship('layergroup', 'layergroup', LayergroupDto::class, 'layergroup'));
    }
}
