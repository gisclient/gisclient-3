<?php

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\MapsetDto;
use GisClient\Author\Api\Dto\ProjectDto;

class MapsetMetadata extends Metadata
{
    public function __construct()
    {
        parent::__construct('mapset', MapsetDto::class, 'mapset_name', 'string');

        $this
            ->requiredOnCreate(['project', 'mapset_name', 'mapset_title', 'maxscale', 'mapset_srid', 'mapset_extent'])
            ->requiredOnPut(['mapset_title', 'maxscale', 'mapset_srid', 'mapset_extent'])
            ->filterable(['mapset_name', 'project_name', 'mapset_title', 'mapset_srid', 'displayprojection', 'private', 'mapset_order'])
            ->sortable(['mapset_name', 'mapset_title', 'mapset_order', 'mapset_srid'], 'mapset_order')
            ->addAttribute('mapset_title', 'mapsetTitle', 'string', true)
            ->addAttribute('maxscale', 'maxscale', 'int', true)
            ->addAttribute('minscale', 'minscale', 'int', true)
            ->addAttribute('mapset_srid', 'mapsetSrid', 'int', true, true, true, null, [
                'lookup' => [
                    'table' => 'seldb_mapset_srid',
                    'column' => 'id',
                    'filters' => [
                        'project_name' => 'from_attribute:project_name',
                    ],
                ],
            ])
            ->addAttribute('displayprojection', 'displayprojection', 'int', true, true, true, null, [
                'lookup' => [
                    'table' => 'seldb_mapset_srid',
                    'column' => 'id',
                    'filters' => [
                        'project_name' => 'from_attribute:project_name',
                    ],
                ],
            ])
            ->addAttribute('sizeunits_id', 'sizeunitsId', 'int', true, true, true, null, [
                'lookup' => [
                    'table' => 'e_sizeunits',
                    'column' => 'sizeunits_id',
                ],
            ])
            ->addAttribute('mapset_scales', 'mapsetScales', 'string', true)
            ->addAttribute('mapset_extent', 'mapsetExtent', 'string', true)
            ->addAttribute('refmap_extent', 'refmapExtent', 'string', true)
            ->addAttribute('template', 'template', 'string', true)
            ->addAttribute('mapset_scale_type', 'mapsetScaleType', 'int')
            ->addAttribute('mapset_order', 'mapsetOrder', 'int')
            ->addAttribute('private', 'private', 'int', true)
            ->addAttribute('mapset_description', 'mapsetDescription', 'string', true)
            ->addAttribute('page_size', 'pageSize', 'string', true)
            ->addAttribute('dl_image_res', 'dlImageRes', 'string', true)
            ->addAttribute('mapset_def', 'mapsetDef', 'string', true)
            ->addAttribute('metadata', 'metadata', 'string', true)
            ->addAttribute('geolocator', 'geolocator', 'string', true)
            ->addAttribute('bg_color', 'bgColor', 'string', true)
            ->addAttribute('static_reference', 'staticReference', 'int', true)
            ->addAttribute('mapset_tiles', 'mapsetTiles', 'int', true, true, true, null, [
                'lookup' => [
                    'table' => 'seldb_mapset_tiles',
                    'column' => 'id',
                ],
            ])
            ->addRelationship('project', 'project', ProjectDto::class, 'project', false, true, true, 'project_name');
    }
}
