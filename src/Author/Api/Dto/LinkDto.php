<?php

namespace GisClient\Author\Api\Dto;

use GisClient\Author\Api\Dto\Schema\FieldDefinition;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;

class LinkDto extends JsonApiDto
{
    public ?int $id = null;

    public string $linkName;

    public string $linkDef;

    public ?int $winw = null;

    public ?int $winh = null;

    public ProjectDto $project;

    public static function schema(): ResourceSchema
    {
        return ResourceSchema::resource('link', self::class, 'link_id', 'int')
            ->requiredOnCreate(['project', 'link_name', 'link_def'])
            ->requiredOnPut(['link_name', 'link_def'])
            ->filterable(['link_id', 'project_name', 'link_name', 'link_order'])
            ->sortable(['link_id', 'link_order', 'link_name'], 'link_order')
            ->addAttribute(FieldDefinition::attribute('link_name', 'linkName', 'string'))
            ->addAttribute(FieldDefinition::attribute('link_def', 'linkDef', 'string'))
            ->addAttribute(FieldDefinition::attribute('winw', 'winw', 'int', true))
            ->addAttribute(FieldDefinition::attribute('winh', 'winh', 'int', true))
            ->addRelationship(FieldDefinition::relationship('project', 'project', ProjectDto::class, 'project'));
    }
}
