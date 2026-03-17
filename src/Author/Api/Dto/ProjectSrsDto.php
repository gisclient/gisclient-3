<?php

namespace GisClient\Author\Api\Dto;

use GisClient\Author\Api\Dto\Schema\FieldDefinition;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;

class ProjectSrsDto extends JsonApiDto
{
    public ?int $id = null;

    public ?string $projparam = null;

    public ProjectDto $project;

    public static function schema(): ResourceSchema
    {
        return ResourceSchema::resource('project_srs', self::class, 'srid', 'int')
            ->requiredOnCreate(['project_name', 'srid'])
            ->requiredOnPut([])
            ->addAttribute(FieldDefinition::attribute('projparam', 'projparam', 'string', true))
            ->addRelationship(FieldDefinition::relationship('project', 'project', ProjectDto::class, 'project', 'project_name'));
    }
}
