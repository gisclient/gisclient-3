<?php

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\LayergroupDto;
use GisClient\Author\Api\Dto\MapsetDto;
use GisClient\Author\Api\Dto\MapsetLayergroupDto;

class MapsetLayergroupMetadata extends Metadata
{
    public function __construct()
    {
        parent::__construct('mapset_layergroup', MapsetLayergroupDto::class, 'id', 'int');

        $this
            ->requiredOnCreate(['mapset', 'layergroup'])
            ->requiredOnPut(['mapset', 'layergroup'])
            ->filterable(['id', 'mapset_name', 'layergroup_id', 'status', 'refmap', 'hide'])
            ->sortable(['id', 'layergroup_id', 'status', 'refmap', 'hide'], 'id')
            ->addAttribute('status', 'status', 'int', true)
            ->addAttribute('refmap', 'refmap', 'int', true)
            ->addAttribute('hide', 'hide', 'int', true)
            ->addRelationship('mapset', 'mapset', MapsetDto::class, 'mapset', false, true, true, true, 'mapset_name')
            ->addRelationship('layergroup', 'layergroup', LayergroupDto::class, 'layergroup', false, true, true, true, 'layergroup_id');
    }
}
