<?php

namespace GisClient\Author\Api\Dto;

use GisClient\Author\Api\Dto\Schema\FieldDefinition;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;

class CatalogDto extends JsonApiDto
{
    public ?int $id = null;

    public string $catalogName;

    public int $connectionType;

    public ?int $setExtent = null;

    public string $catalogPath;

    public ?string $filesPath = null;

    public ?string $catalogDescription = null;

    public ProjectDto $project;

    public static function schema(): ResourceSchema
    {
        return ResourceSchema::resource('catalog', self::class, 'catalog_id', 'int')
            ->requiredOnCreate(['project_name', 'catalog_name', 'connection_type', 'catalog_path'])
            ->requiredOnPut(['catalog_name', 'connection_type', 'catalog_path'])
            ->filterable(['catalog_id', 'project_name', 'catalog_name', 'connection_type'])
            ->sortable(['catalog_id', 'catalog_name', 'connection_type'], 'catalog_name')
            ->addAttribute(FieldDefinition::attribute('catalog_name', 'catalogName', 'string'))
            ->addAttribute(FieldDefinition::attribute('connection_type', 'connectionType', 'int')->withLookup([
                'table' => 'e_conntype',
                'column' => 'conntype_id',
            ]))
            ->addAttribute(FieldDefinition::attribute('set_extent', 'setExtent', 'int', true))
            ->addAttribute(FieldDefinition::attribute('catalog_path', 'catalogPath', 'string'))
            ->addAttribute(FieldDefinition::attribute('files_path', 'filesPath', 'string', true))
            ->addAttribute(FieldDefinition::attribute('catalog_description', 'catalogDescription', 'string', true))
            ->addRelationship(FieldDefinition::relationship('project', 'project', ProjectDto::class, 'project', 'project_name'));
    }
}
