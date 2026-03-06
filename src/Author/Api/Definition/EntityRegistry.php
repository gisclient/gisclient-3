<?php

namespace GisClient\Author\Api\Definition;

class EntityRegistry
{
    /**
     * @return array
     */
    public static function getDefinitions()
    {
        return [
            'project' => [
                'schema' => DB_SCHEMA,
                'table' => 'project',
                'primary_key' => 'project_name',
                'id_type' => 'string',
                'tab_file' => ROOT_PATH . \GCAuthor::getTabDir() . 'project.tab',
                'required_on_create' => [
                    'project_name',
                    'project_title',
                    'project_srid',
                    'max_extent_scale',
                    'charset_encodings_id',
                    'default_language_id',
                ],
                'required_on_put' => [
                    'project_title',
                    'project_srid',
                    'max_extent_scale',
                    'charset_encodings_id',
                    'default_language_id',
                ],
                'filterable_fields' => ['project_name', 'project_title', 'default_language_id'],
                'sortable_fields' => ['project_name', 'project_title'],
                'default_sort' => 'project_name',
            ],
            'project_srs' => [
                'schema' => DB_SCHEMA,
                'table' => 'project_srs',
                'primary_key' => 'srid',
                'id_type' => 'int',
                'tab_file' => ROOT_PATH . \GCAuthor::getTabDir() . 'project_srs.tab',
                'required_on_create' => ['project_name', 'srid'],
                'required_on_put' => [],
                'filterable_fields' => ['project_name', 'srid'],
                'sortable_fields' => ['srid'],
                'default_sort' => 'srid',
                'scope_fields' => ['project_name'],
                'relationships' => [
                    'project' => [
                        'type' => 'project',
                        'local_key' => 'project_name',
                    ],
                ],
                'required_relationships_on_write' => ['project'],
            ],
        ];
    }
}
