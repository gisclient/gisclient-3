<?php

namespace GisClient\Author\Api\Dto;

use GisClient\Author\Api\Dto\Schema\FieldDefinition;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;

class LayergroupDto extends JsonApiDto
{
    public ?int $id = null;

    public string $layergroupName;

    public ?string $layergroupTitle = null;

    public ?int $layergroupOrder = null;

    public ?int $owstypeId = null;

    public ?int $layergroupMaxscale = null;

    public ?int $layergroupMinscale = null;

    public ?string $opacity = null;

    public ?int $outputformatId = null;

    public ?string $layers = null;

    public ?int $tilesExtentSrid = null;

    public ?string $tilesExtent = null;

    public ?string $url = null;

    public ?int $wmsversionId = null;

    public ?string $tileOrigin = null;

    public ?string $tileResolutions = null;

    public ?string $style = null;

    public ?string $tileMatrixSet = null;

    public ?string $sld = null;

    public ?string $metadataUrl = null;

    public ?int $gutter = null;

    public ?float $buffer = null;

    public ?int $isbaselayer = null;

    public ?float $transition = null;

    public ?float $layergroupSingle = null;

    public ?float $tiletypeId = null;

    public ThemeDto $theme;

    public static function schema(): ResourceSchema
    {
        return ResourceSchema::resource('layergroup', self::class, 'layergroup_id', 'int')
            ->requiredOnCreate(['theme_id', 'layergroup_name'])
            ->requiredOnPut(['theme_id', 'layergroup_name'])
            ->addAttribute(FieldDefinition::attribute('layergroup_name', 'layergroupName', 'string'))
            ->addAttribute(FieldDefinition::attribute('layergroup_title', 'layergroupTitle', 'string', true))
            ->addAttribute(FieldDefinition::attribute('layergroup_order', 'layergroupOrder', 'int', true))
            ->addAttribute(FieldDefinition::attribute('owstype_id', 'owstypeId', 'int', true))
            ->addAttribute(FieldDefinition::attribute('layergroup_maxscale', 'layergroupMaxscale', 'int', true))
            ->addAttribute(FieldDefinition::attribute('layergroup_minscale', 'layergroupMinscale', 'int', true))
            ->addAttribute(FieldDefinition::attribute('opacity', 'opacity', 'string', true))
            ->addAttribute(FieldDefinition::attribute('outputformat_id', 'outputformatId', 'int', true))
            ->addAttribute(FieldDefinition::attribute('layers', 'layers', 'string', true))
            ->addAttribute(FieldDefinition::attribute('tiles_extent_srid', 'tilesExtentSrid', 'int', true))
            ->addAttribute(FieldDefinition::attribute('tiles_extent', 'tilesExtent', 'string', true))
            ->addAttribute(FieldDefinition::attribute('url', 'url', 'string', true))
            ->addAttribute(FieldDefinition::attribute('wmsversion_id', 'wmsversionId', 'int', true))
            ->addAttribute(FieldDefinition::attribute('tile_origin', 'tileOrigin', 'string', true))
            ->addAttribute(FieldDefinition::attribute('tile_resolutions', 'tileResolutions', 'string', true))
            ->addAttribute(FieldDefinition::attribute('style', 'style', 'string', true))
            ->addAttribute(FieldDefinition::attribute('tile_matrix_set', 'tileMatrixSet', 'string', true))
            ->addAttribute(FieldDefinition::attribute('sld', 'sld', 'string', true))
            ->addAttribute(FieldDefinition::attribute('metadata_url', 'metadataUrl', 'string', true))
            ->addAttribute(FieldDefinition::attribute('gutter', 'gutter', 'int', true))
            ->addAttribute(FieldDefinition::attribute('buffer', 'buffer', 'float', true))
            ->addAttribute(FieldDefinition::attribute('isbaselayer', 'isbaselayer', 'int', true))
            ->addAttribute(FieldDefinition::attribute('transition', 'transition', 'float', true))
            ->addAttribute(FieldDefinition::attribute('layergroup_single', 'layergroupSingle', 'float', true))
            ->addAttribute(FieldDefinition::attribute('tiletype_id', 'tiletypeId', 'float', true))
            ->addRelationship(FieldDefinition::relationship('theme', 'theme', ThemeDto::class, 'theme', 'theme_id'));
    }
}
