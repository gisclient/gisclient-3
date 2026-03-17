<?php

namespace GisClient\Author\Api\Dto;

use GisClient\Author\Api\Dto\Schema\FieldDefinition;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;

class MapsetLayergroupDto extends JsonApiDto
{
    public ?int $id = null;

    public ?int $status = null;

    public ?int $refmap = null;

    public ?int $hide = null;

    public MapsetDto $mapset;

    public LayergroupDto $layergroup;

    public static function schema(): ResourceSchema
    {
        return ResourceSchema::resource('mapset_layergroup', self::class, 'layergroup_id', 'int')
            ->requiredOnCreate(['mapset_name', 'layergroup_id'])
            ->requiredOnPut([])
            ->filterable(['mapset_name', 'layergroup_id', 'status', 'refmap', 'hide'])
            ->sortable(['layergroup_id', 'status', 'refmap', 'hide'], 'layergroup_id')
            ->scopedBy(['mapset_name'])
            ->addAttribute(FieldDefinition::attribute('status', 'status', 'int', true))
            ->addAttribute(FieldDefinition::attribute('refmap', 'refmap', 'int', true))
            ->addAttribute(FieldDefinition::attribute('hide', 'hide', 'int', true))
            ->addRelationship(FieldDefinition::relationship('mapset', 'mapset', MapsetDto::class, 'mapset', 'mapset_name'))
            ->addRelationship(FieldDefinition::relationship('layergroup', 'layergroup', LayergroupDto::class, 'layergroup', 'layergroup_id'));
    }
}
