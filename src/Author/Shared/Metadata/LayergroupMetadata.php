<?php

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\LayergroupDto;
use GisClient\Author\Api\Dto\ThemeDto;

class LayergroupMetadata extends Metadata
{
    public function __construct()
    {
        parent::__construct('layergroup', LayergroupDto::class, 'layergroup_id', 'int');

        $this
            ->requiredOnCreate(['theme', 'layergroup_name'])
            ->requiredOnPut(['theme', 'layergroup_name'])
            ->filterable(['layergroup_id', 'theme_id', 'layergroup_name', 'layergroup_title', 'owstype_id'])
            ->sortable(['layergroup_id', 'layergroup_order', 'layergroup_name', 'layergroup_title'], 'layergroup_order')
            ->addAttribute('layergroup_name', 'layergroupName', 'string')
            ->addAttribute('layergroup_title', 'layergroupTitle', 'string', true)
            ->addAttribute('layergroup_order', 'layergroupOrder', 'int', true)
            ->addAttribute('owstype_id', 'owstypeId', 'int', true, true, true, null, [
                'lookup' => [
                    'table' => 'e_owstype',
                    'column' => 'owstype_id',
                ],
            ])
            ->addAttribute('layergroup_maxscale', 'layergroupMaxscale', 'int', true)
            ->addAttribute('layergroup_minscale', 'layergroupMinscale', 'int', true)
            ->addAttribute('opacity', 'opacity', 'string', true)
            ->addAttribute('outputformat_id', 'outputformatId', 'int', true, true, true, null, [
                'lookup' => [
                    'table' => 'e_outputformat',
                    'column' => 'outputformat_id',
                ],
            ])
            ->addAttribute('layers', 'layers', 'string', true)
            ->addAttribute('tiles_extent_srid', 'tilesExtentSrid', 'int', true)
            ->addAttribute('tiles_extent', 'tilesExtent', 'string', true)
            ->addAttribute('url', 'url', 'string', true)
            ->addAttribute('wmsversion_id', 'wmsversionId', 'int', true, true, true, null, [
                'lookup' => [
                    'table' => 'e_wmsversion',
                    'column' => 'wmsversion_id',
                ],
            ])
            ->addAttribute('tile_origin', 'tileOrigin', 'string', true)
            ->addAttribute('tile_resolutions', 'tileResolutions', 'string', true)
            ->addAttribute('style', 'style', 'string', true)
            ->addAttribute('tile_matrix_set', 'tileMatrixSet', 'string', true)
            ->addAttribute('sld', 'sld', 'string', true)
            ->addAttribute('metadata_url', 'metadataUrl', 'string', true)
            ->addAttribute('gutter', 'gutter', 'int', true)
            ->addAttribute('buffer', 'buffer', 'float', true)
            ->addAttribute('isbaselayer', 'isbaselayer', 'int', true)
            ->addAttribute('transition', 'transition', 'float', true)
            ->addAttribute('layergroup_single', 'layergroupSingle', 'float', true)
            ->addAttribute('tiletype_id', 'tiletypeId', 'float', true)
            ->addRelationship('theme', 'theme', ThemeDto::class, 'theme', false, true, true, true, 'theme_id');
    }
}
