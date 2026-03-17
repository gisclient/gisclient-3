<?php

namespace GisClient\Author\Api\Dto;

use GisClient\Author\Api\Dto\Schema\FieldDefinition;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;

class ThemeDto extends JsonApiDto
{
    public ?int $id = null;

    public string $themeName;

    public ?string $themeTitle = null;

    public ?int $themeOrder = null;

    public ?string $copyrightString = null;

    public ?string $symbolName = null;

    public ?float $themeSingle = null;

    public ?float $radio = null;

    public ?ProjectDto $project = null;

    public static function schema(): ResourceSchema
    {
        return ResourceSchema::resource('theme', self::class, 'theme_id', 'int')
            ->requiredOnCreate(['project_name', 'theme_name', 'theme_title', 'theme_order'])
            ->requiredOnPut(['theme_name', 'theme_title', 'theme_order'])
            ->filterable(['theme_id', 'project_name', 'theme_name', 'theme_title'])
            ->sortable(['theme_id', 'theme_order', 'theme_title', 'theme_name'], 'theme_order')
            ->addAttribute(FieldDefinition::attribute('theme_name', 'themeName', 'string'))
            ->addAttribute(FieldDefinition::attribute('theme_title', 'themeTitle', 'string', true))
            ->addAttribute(FieldDefinition::attribute('theme_order', 'themeOrder', 'int', true))
            ->addAttribute(FieldDefinition::attribute('copyright_string', 'copyrightString', 'string', true))
            ->addAttribute(FieldDefinition::attribute('symbol_name', 'symbolName', 'string', true)->withLookup([
                'table' => 'symbol',
                'column' => 'symbol_name',
            ]))
            ->addAttribute(FieldDefinition::attribute('theme_single', 'themeSingle', 'float', true))
            ->addAttribute(FieldDefinition::attribute('radio', 'radio', 'float', true))
            ->addRelationship(FieldDefinition::relationship('project', 'project', ProjectDto::class, 'project', 'project_name', true));
    }
}
