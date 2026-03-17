<?php

namespace GisClient\Author\Api\Dto;

use GisClient\Author\Api\Dto\Schema\FieldDefinition;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;

class ProjectDto extends JsonApiDto
{
    public ?string $id = null;

    public ?string $projectTitle = null;

    public ?float $xc = null;

    public ?float $yc = null;

    public int $projectSrid;

    public ?float $maxExtentScale = null;

    public ?int $charsetEncodingsId = null;

    public string $defaultLanguageId;

    public ?string $basePath = null;

    public ?string $baseUrl = null;

    public ?string $imagelabelText = null;

    public ?string $imagelabelPosition = null;

    public ?int $imagelabelOffsetX = null;

    public ?int $imagelabelOffsetY = null;

    public ?string $imagelabelFont = null;

    public ?int $imagelabelSize = null;

    public ?string $imagelabelColor = null;

    public ?int $iconH = null;

    public ?int $iconW = null;

    public ?string $projectNote = null;

    public static function schema(): ResourceSchema
    {
        return ResourceSchema::resource('project', self::class, 'project_name', 'string')
            ->requiredOnCreate(['project_name', 'project_title', 'project_srid', 'max_extent_scale', 'charset_encodings_id', 'default_language_id'])
            ->requiredOnPut(['project_title', 'project_srid', 'max_extent_scale', 'charset_encodings_id', 'default_language_id'])
            ->filterable(['project_name', 'project_title', 'default_language_id'])
            ->sortable(['project_name', 'project_title'], 'project_name')
            ->addAttribute(FieldDefinition::attribute('project_title', 'projectTitle', 'string', true))
            ->addAttribute(FieldDefinition::attribute('xc', 'xc', 'float', true))
            ->addAttribute(FieldDefinition::attribute('yc', 'yc', 'float', true))
            ->addAttribute(FieldDefinition::attribute('project_srid', 'projectSrid', 'int'))
            ->addAttribute(FieldDefinition::attribute('max_extent_scale', 'maxExtentScale', 'float', true))
            ->addAttribute(FieldDefinition::attribute('charset_encodings_id', 'charsetEncodingsId', 'int', true)->withLookup([
                'table' => 'e_charset_encodings',
                'column' => 'charset_encodings_id',
            ]))
            ->addAttribute(FieldDefinition::attribute('default_language_id', 'defaultLanguageId', 'string')->withLookup([
                'table' => 'e_language',
                'column' => 'language_id',
            ]))
            ->addAttribute(FieldDefinition::attribute('base_path', 'basePath', 'string', true))
            ->addAttribute(FieldDefinition::attribute('base_url', 'baseUrl', 'string', true))
            ->addAttribute(FieldDefinition::attribute('imagelabel_text', 'imagelabelText', 'string', true))
            ->addAttribute(FieldDefinition::attribute('imagelabel_position', 'imagelabelPosition', 'string', true))
            ->addAttribute(FieldDefinition::attribute('imagelabel_offset_x', 'imagelabelOffsetX', 'int', true))
            ->addAttribute(FieldDefinition::attribute('imagelabel_offset_y', 'imagelabelOffsetY', 'int', true))
            ->addAttribute(FieldDefinition::attribute('imagelabel_font', 'imagelabelFont', 'string', true))
            ->addAttribute(FieldDefinition::attribute('imagelabel_size', 'imagelabelSize', 'int', true))
            ->addAttribute(FieldDefinition::attribute('imagelabel_color', 'imagelabelColor', 'string', true))
            ->addAttribute(FieldDefinition::attribute('icon_h', 'iconH', 'int', true))
            ->addAttribute(FieldDefinition::attribute('icon_w', 'iconW', 'int', true))
            ->addAttribute(FieldDefinition::attribute('project_note', 'projectNote', 'string', true));
    }
}
