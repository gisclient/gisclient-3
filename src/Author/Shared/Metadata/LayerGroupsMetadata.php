<?php

declare(strict_types=1);

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\GroupDto;
use GisClient\Author\Api\Dto\LayerDto;
use GisClient\Author\Api\Dto\LayerGroupsDto;

/**
 * Per-group permissions on a layer (one row per layer/group pair, UNIQUE (layer_id, groupname)),
 * as edited from the "Groups" tab of each layer in the admin.
 *
 * The resource type is singular like the others (as `group` for table `groups`). The classes keep
 * the plural name because PHP class names are case-insensitive: LayerGroupDto would clash with LayergroupDto.
 */
class LayerGroupsMetadata extends Metadata
{
    public function __construct()
    {
        parent::__construct('layer_group', LayerGroupsDto::class, 'layer_groups_id', 'int', 'layer_groups');

        $this
            ->requiredOnCreate(['layer', 'group'])
            ->requiredOnPut(['layer', 'group'])
            ->filterable(['layer_groups_id', 'layer_id', 'groupname', 'wms', 'wfs', 'wfst'])
            ->sortable(['layer_groups_id', 'layer_id', 'groupname'], 'layer_groups_id')
            ->addAttribute('wms', 'wms', 'int', true)
            ->addAttribute('wfs', 'wfs', 'int', true)
            ->addAttribute('wfst', 'wfst', 'int', true)
            ->addAttribute('layer_name', 'layerName', 'string', true)
            ->addRelationship('layer', 'layer', LayerDto::class, 'layer', false, true, true, 'layer_id')
            ->addRelationship('group', 'group', GroupDto::class, 'group', false, true, true, 'groupname');
    }
}
