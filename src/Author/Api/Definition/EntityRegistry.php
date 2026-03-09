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
            'theme' => [
                'schema' => DB_SCHEMA,
                'table' => 'theme',
                'primary_key' => 'theme_id',
                'id_type' => 'int',
                'tab_file' => ROOT_PATH . \GCAuthor::getTabDir() . 'theme.tab',
                'required_on_create' => ['project_name', 'theme_name', 'theme_title', 'theme_order'],
                'required_on_put' => ['theme_name', 'theme_title', 'theme_order'],
                'filterable_fields' => ['theme_id', 'project_name', 'theme_name', 'theme_title'],
                'sortable_fields' => ['theme_id', 'theme_order', 'theme_title', 'theme_name'],
                'default_sort' => 'theme_order',
                'relationships' => [
                    'project' => [
                        'type' => 'project',
                        'local_key' => 'project_name',
                    ],
                ],
                'required_relationships_on_write' => ['project'],
            ],
            'layergroup' => [
                'schema' => DB_SCHEMA,
                'table' => 'layergroup',
                'primary_key' => 'layergroup_id',
                'id_type' => 'int',
                'tab_file' => ROOT_PATH . \GCAuthor::getTabDir() . 'layergroup.tab',
                'required_on_create' => [
                    'theme_id',
                    'layergroup_name',
                ],
                'required_on_put' => [
                    'theme_id',
                    'layergroup_name',
                ],
                'filterable_fields' => ['layergroup_id', 'theme_id', 'layergroup_name', 'layergroup_title', 'owstype_id'],
                'sortable_fields' => ['layergroup_id', 'layergroup_order', 'layergroup_name', 'layergroup_title'],
                'default_sort' => 'layergroup_order',
                'relationships' => [
                    'theme' => [
                        'type' => 'theme',
                        'local_key' => 'theme_id',
                    ],
                ],
                'required_relationships_on_write' => ['theme'],
            ],
            'layer' => [
                'schema' => DB_SCHEMA,
                'table' => 'layer',
                'primary_key' => 'layer_id',
                'id_type' => 'int',
                'tab_file' => ROOT_PATH . \GCAuthor::getTabDir() . 'layer.tab',
                'required_on_create' => [
                    'layergroup_id',
                    'layer_name',
                    'layertype_id',
                    'catalog_id',
                ],
                'required_on_put' => [
                    'layergroup_id',
                    'layer_name',
                    'layertype_id',
                    'catalog_id',
                ],
                'filterable_fields' => [
                    'layer_id',
                    'layergroup_id',
                    'catalog_id',
                    'layertype_id',
                    'layer_name',
                    'layer_title',
                    'layer_order',
                    'queryable',
                    'private',
                    'hidden',
                    'searchable_id',
                ],
                'sortable_fields' => ['layer_id', 'layer_order', 'layer_name', 'layer_title', 'layertype_id'],
                'default_sort' => 'layer_order',
                'relationships' => [
                    'layergroup' => [
                        'type' => 'layergroup',
                        'local_key' => 'layergroup_id',
                    ],
                    'catalog' => [
                        'type' => 'catalog',
                        'local_key' => 'catalog_id',
                    ],
                ],
                'required_relationships_on_write' => ['layergroup', 'catalog'],
            ],
            'class' => [
                'schema' => DB_SCHEMA,
                'table' => 'class',
                'primary_key' => 'class_id',
                'id_type' => 'int',
                'tab_file' => ROOT_PATH . \GCAuthor::getTabDir() . 'class.tab',
                'required_on_create' => [
                    'class_name',
                ],
                'required_on_put' => [
                    'class_name',
                ],
                'filterable_fields' => [
                    'class_id',
                    'layer_id',
                    'class_name',
                    'class_title',
                    'legendtype_id',
                    'maxscale',
                    'minscale',
                ],
                'sortable_fields' => ['class_id', 'class_order', 'class_name', 'class_title'],
                'default_sort' => 'class_order',
                'relationships' => [
                    'layer' => [
                        'type' => 'layer',
                        'local_key' => 'layer_id',
                    ],
                ],
                'required_relationships_on_write' => ['layer'],
            ],
            'catalog' => [
                'schema' => DB_SCHEMA,
                'table' => 'catalog',
                'primary_key' => 'catalog_id',
                'id_type' => 'int',
                'tab_file' => ROOT_PATH . \GCAuthor::getTabDir() . 'catalog.tab',
                'required_on_create' => ['project_name', 'catalog_name', 'connection_type', 'catalog_path'],
                'required_on_put' => ['catalog_name', 'connection_type', 'catalog_path'],
                'filterable_fields' => ['catalog_id', 'project_name', 'catalog_name', 'connection_type'],
                'sortable_fields' => ['catalog_id', 'catalog_name', 'connection_type'],
                'default_sort' => 'catalog_name',
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
