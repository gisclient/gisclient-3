<?php

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\CatalogDto;
use GisClient\Author\Api\Dto\LayerDto;
use GisClient\Author\Api\Dto\LayergroupDto;

class LayerMetadata extends Metadata
{
    public function __construct()
    {
        parent::__construct('layer', LayerDto::class, 'layer_id', 'int');

        $this
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
            ->addAttribute('layer_name', 'layerName', 'string')
            ->addAttribute('layer_title', 'layerTitle', 'string', true)
            ->addAttribute('layer_order', 'layerOrder', 'int', true)
            ->addAttribute('opacity', 'opacity', 'string', true)
            ->addAttribute('layertype_id', 'layertypeId', 'int', false, true, true, null, [
                'lookup' => [
                    'table' => 'e_layertype',
                    'column' => 'layertype_id',
                ],
            ])
            ->addAttribute('data_type', 'dataType', 'string', true)
            ->addAttribute('data', 'data', 'string', true)
            ->addAttribute('data_geom', 'dataGeom', 'string', true)
            ->addAttribute('data_unique', 'dataUnique', 'string', true)
            ->addAttribute('data_srid', 'dataSrid', 'int', true)
            ->addAttribute('maxscale', 'maxscale', 'string', true)
            ->addAttribute('minscale', 'minscale', 'string', true)
            ->addAttribute('symbolscale', 'symbolscale', 'int', true)
            ->addAttribute('sizeunits_id', 'sizeunitsId', 'float', false, true, true, null, [
                'lookup' => [
                    'table' => 'e_sizeunits',
                    'column' => 'sizeunits_id',
                ],
            ])
            ->addAttribute('data_extent', 'dataExtent', 'string', true)
            ->addAttribute('data_filter', 'dataFilter', 'string', true)
            ->addAttribute('layer_def', 'layerDef', 'string', true)
            ->addAttribute('metadata', 'metadata', 'string', true)
            ->addAttribute('labelitem', 'labelitem', 'string', true)
            ->addAttribute('labelsizeitem', 'labelsizeitem', 'string', true)
            ->addAttribute('labelmaxscale', 'labelmaxscale', 'string', true)
            ->addAttribute('labelminscale', 'labelminscale', 'string', true)
            ->addAttribute('postlabelcache', 'postlabelcache', 'float', true)
            ->addAttribute('classitem', 'classitem', 'string', true)
            ->addAttribute('private', 'private', 'float', true)
            ->addAttribute('queryable', 'queryable', 'float', true)
            ->addAttribute('hide_vector_geom', 'hideVectorGeom', 'float', true)
            ->addAttribute('hidden', 'hidden', 'float', true)
            ->addAttribute('searchable_id', 'searchableId', 'float', true, true, true, null, [
                'lookup' => [
                    'table' => 'e_searchable',
                    'column' => 'searchable_id',
                ],
            ])
            ->addAttribute('template', 'template', 'string', true)
            ->addAttribute('header', 'header', 'string', true)
            ->addAttribute('footer', 'footer', 'string', true)
            ->addAttribute('tolerance', 'tolerance', 'int', true)
            ->addAttribute('toleranceunits_id', 'toleranceunitsId', 'float', true, true, true, null, [
                'lookup' => [
                    'table' => 'e_sizeunits',
                    'column' => 'sizeunits_id',
                ],
            ])
            ->addAttribute('selection_width', 'selectionWidth', 'float', true)
            ->addAttribute('selection_color', 'selectionColor', 'string', true)
            ->addAttribute('maxfeatures', 'maxfeatures', 'int', true)
            ->addAttribute('maxvectfeatures', 'maxvectfeatures', 'int', true)
            ->addAttribute('zoom_buffer', 'zoomBuffer', 'float', true)
            ->addAttribute('last_update', 'lastUpdate', 'string', true)
            ->addRelationship('catalog', 'catalog', CatalogDto::class, 'catalog', false, true, true, 'catalog_id')
            ->addRelationship('layergroup', 'layergroup', LayergroupDto::class, 'layergroup', false, true, true, 'layergroup_id');
    }
}
