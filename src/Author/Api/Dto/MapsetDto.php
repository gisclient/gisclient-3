<?php

namespace GisClient\Author\Api\Dto;

use GisClient\Author\Api\Dto\Schema\FieldDefinition;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;

class MapsetDto extends JsonApiDto
{
    public ?string $id = null;

    public ?string $mapsetTitle = null;

    public ?int $maxscale = null;

    public ?int $minscale = null;

    public ?int $mapsetSrid = null;

    public ?int $displayprojection = null;

    public ?int $sizeunitsId = null;

    public ?string $mapsetScales = null;

    public ?string $mapsetExtent = null;

    public ?string $refmapExtent = null;

    public ?string $template = null;

    public int $mapsetScaleType;

    public int $mapsetOrder;

    public ?int $private = null;

    public ?string $mapsetDescription = null;

    public ?string $pageSize = null;

    public ?string $dlImageRes = null;

    public ?string $mapsetDef = null;

    public ?string $metadata = null;

    public ?string $geolocator = null;

    public ?string $bgColor = null;

    public ?int $staticReference = null;

    public ?int $mapsetTiles = null;

    public ProjectDto $project;

    public static function schema(): ResourceSchema
    {
        return ResourceSchema::resource('mapset', self::class, 'mapset_name', 'string')
            ->requiredOnCreate(['project', 'mapset_name', 'mapset_title', 'maxscale', 'mapset_srid', 'mapset_extent'])
            ->requiredOnPut(['mapset_title', 'maxscale', 'mapset_srid', 'mapset_extent'])
            ->filterable(['mapset_name', 'project_name', 'mapset_title', 'mapset_srid', 'displayprojection', 'private', 'mapset_order'])
            ->sortable(['mapset_name', 'mapset_title', 'mapset_order', 'mapset_srid'], 'mapset_order')
            ->addAttribute(FieldDefinition::attribute('mapset_title', 'mapsetTitle', 'string', true))
            ->addAttribute(FieldDefinition::attribute('maxscale', 'maxscale', 'int', true))
            ->addAttribute(FieldDefinition::attribute('minscale', 'minscale', 'int', true))
            ->addAttribute(FieldDefinition::attribute('mapset_srid', 'mapsetSrid', 'int', true))
            ->addAttribute(FieldDefinition::attribute('displayprojection', 'displayprojection', 'int', true))
            ->addAttribute(FieldDefinition::attribute('sizeunits_id', 'sizeunitsId', 'int', true))
            ->addAttribute(FieldDefinition::attribute('mapset_scales', 'mapsetScales', 'string', true))
            ->addAttribute(FieldDefinition::attribute('mapset_extent', 'mapsetExtent', 'string', true))
            ->addAttribute(FieldDefinition::attribute('refmap_extent', 'refmapExtent', 'string', true))
            ->addAttribute(FieldDefinition::attribute('template', 'template', 'string', true))
            ->addAttribute(FieldDefinition::attribute('mapset_scale_type', 'mapsetScaleType', 'int'))
            ->addAttribute(FieldDefinition::attribute('mapset_order', 'mapsetOrder', 'int'))
            ->addAttribute(FieldDefinition::attribute('private', 'private', 'int', true))
            ->addAttribute(FieldDefinition::attribute('mapset_description', 'mapsetDescription', 'string', true))
            ->addAttribute(FieldDefinition::attribute('page_size', 'pageSize', 'string', true))
            ->addAttribute(FieldDefinition::attribute('dl_image_res', 'dlImageRes', 'string', true))
            ->addAttribute(FieldDefinition::attribute('mapset_def', 'mapsetDef', 'string', true))
            ->addAttribute(FieldDefinition::attribute('metadata', 'metadata', 'string', true))
            ->addAttribute(FieldDefinition::attribute('geolocator', 'geolocator', 'string', true))
            ->addAttribute(FieldDefinition::attribute('bg_color', 'bgColor', 'string', true))
            ->addAttribute(FieldDefinition::attribute('static_reference', 'staticReference', 'int', true))
            ->addAttribute(FieldDefinition::attribute('mapset_tiles', 'mapsetTiles', 'int', true))
            ->addRelationship(FieldDefinition::relationship('project', 'project', ProjectDto::class, 'project'));
    }
}
