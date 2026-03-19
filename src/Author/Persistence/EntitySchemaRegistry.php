<?php

namespace GisClient\Author\Persistence;

class EntitySchemaRegistry
{
    /**
     * @var array<string,EntitySchema>|null
     */
    private static $schemas;

    public static function schemaForType(string $type): EntitySchema
    {
        self::initialize();

        if (!isset(self::$schemas[$type])) {
            throw new \RuntimeException(sprintf("No entity schema registered for resource type '%s'", $type));
        }

        return self::$schemas[$type];
    }

    private static function initialize(): void
    {
        if (self::$schemas !== null) {
            return;
        }

        self::$schemas = [
            'project' => self::buildProject(),
            'project_srs' => self::buildProjectSrs(),
            'theme' => self::buildTheme(),
            'layergroup' => self::buildLayergroup(),
            'layer' => self::buildLayer(),
            'class' => self::buildClass(),
            'style' => self::buildStyle(),
            'field' => self::buildField(),
            'catalog' => self::buildCatalog(),
            'link' => self::buildLink(),
            'mapset' => self::buildMapset(),
            'mapset_layergroup' => self::buildMapsetLayergroup(),
        ];
    }

    private static function buildProject(): EntitySchema
    {
        return EntitySchema::entity('project', 'project_name', 'string')
            ->filterable(['project_name', 'project_title', 'default_language_id'])
            ->sortable(['project_name', 'project_title'], 'project_name')
            ->addAttribute('project_title', 'string')
            ->addAttribute('xc', 'float')
            ->addAttribute('yc', 'float')
            ->addAttribute('project_srid', 'int')
            ->addAttribute('max_extent_scale', 'float')
            ->addAttribute('charset_encodings_id', 'int', null, true, true, [
                'lookup' => [
                    'table' => 'e_charset_encodings',
                    'column' => 'charset_encodings_id',
                ],
            ])
            ->addAttribute('default_language_id', 'string', null, true, true, [
                'lookup' => [
                    'table' => 'e_language',
                    'column' => 'language_id',
                ],
            ])
            ->addAttribute('base_path', 'string')
            ->addAttribute('base_url', 'string')
            ->addAttribute('imagelabel_text', 'string')
            ->addAttribute('imagelabel_position', 'string')
            ->addAttribute('imagelabel_offset_x', 'int')
            ->addAttribute('imagelabel_offset_y', 'int')
            ->addAttribute('imagelabel_font', 'string')
            ->addAttribute('imagelabel_size', 'int')
            ->addAttribute('imagelabel_color', 'string')
            ->addAttribute('icon_h', 'int')
            ->addAttribute('icon_w', 'int')
            ->addAttribute('project_note', 'string');
    }

    private static function buildProjectSrs(): EntitySchema
    {
        return EntitySchema::entity('project_srs', 'id', 'int')
            ->filterable(['id', 'project_name', 'srid'])
            ->sortable(['id', 'srid'], 'srid')
            ->addAttribute('srid', 'int')
            ->addAttribute('projparam', 'string')
            ->addRelationship('project', 'project_name');
    }

    private static function buildTheme(): EntitySchema
    {
        return EntitySchema::entity('theme', 'theme_id', 'int')
            ->filterable(['theme_id', 'project_name', 'theme_name', 'theme_title'])
            ->sortable(['theme_id', 'theme_order', 'theme_title', 'theme_name'], 'theme_order')
            ->addAttribute('theme_name', 'string')
            ->addAttribute('theme_title', 'string')
            ->addAttribute('theme_order', 'int')
            ->addAttribute('copyright_string', 'string')
            ->addAttribute('symbol_name', 'string', null, true, true, [
                'lookup' => [
                    'table' => 'symbol',
                    'column' => 'symbol_name',
                ],
            ])
            ->addAttribute('theme_single', 'float')
            ->addAttribute('radio', 'float')
            ->addRelationship('project', 'project_name');
    }

    private static function buildLayergroup(): EntitySchema
    {
        return EntitySchema::entity('layergroup', 'layergroup_id', 'int')
            ->filterable(['layergroup_id', 'theme_id', 'layergroup_name', 'layergroup_title', 'owstype_id'])
            ->sortable(['layergroup_id', 'layergroup_order', 'layergroup_name', 'layergroup_title'], 'layergroup_order')
            ->addAttribute('layergroup_name', 'string')
            ->addAttribute('layergroup_title', 'string')
            ->addAttribute('layergroup_order', 'int')
            ->addAttribute('owstype_id', 'int', null, true, true, [
                'lookup' => [
                    'table' => 'e_owstype',
                    'column' => 'owstype_id',
                ],
            ])
            ->addAttribute('layergroup_maxscale', 'int')
            ->addAttribute('layergroup_minscale', 'int')
            ->addAttribute('opacity', 'string')
            ->addAttribute('outputformat_id', 'int', null, true, true, [
                'lookup' => [
                    'table' => 'e_outputformat',
                    'column' => 'outputformat_id',
                ],
            ])
            ->addAttribute('layers', 'string')
            ->addAttribute('tiles_extent_srid', 'int')
            ->addAttribute('tiles_extent', 'string')
            ->addAttribute('url', 'string')
            ->addAttribute('wmsversion_id', 'int', null, true, true, [
                'lookup' => [
                    'table' => 'e_wmsversion',
                    'column' => 'wmsversion_id',
                ],
            ])
            ->addAttribute('tile_origin', 'string')
            ->addAttribute('tile_resolutions', 'string')
            ->addAttribute('style', 'string')
            ->addAttribute('tile_matrix_set', 'string')
            ->addAttribute('sld', 'string')
            ->addAttribute('metadata_url', 'string')
            ->addAttribute('gutter', 'int')
            ->addAttribute('buffer', 'float')
            ->addAttribute('isbaselayer', 'int')
            ->addAttribute('transition', 'float')
            ->addAttribute('layergroup_single', 'float')
            ->addAttribute('tiletype_id', 'float')
            ->addRelationship('theme', 'theme_id');
    }

    private static function buildLayer(): EntitySchema
    {
        return EntitySchema::entity('layer', 'layer_id', 'int')
            ->filterable([
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
            ])
            ->sortable(['layer_id', 'layer_order', 'layer_name', 'layer_title', 'layertype_id'], 'layer_order')
            ->addAttribute('layer_name', 'string')
            ->addAttribute('layer_title', 'string')
            ->addAttribute('layer_order', 'int')
            ->addAttribute('opacity', 'string')
            ->addAttribute('layertype_id', 'int', null, true, true, [
                'lookup' => [
                    'table' => 'e_layertype',
                    'column' => 'layertype_id',
                ],
            ])
            ->addAttribute('data_type', 'string')
            ->addAttribute('data', 'string')
            ->addAttribute('data_geom', 'string')
            ->addAttribute('data_unique', 'string')
            ->addAttribute('data_srid', 'int')
            ->addAttribute('maxscale', 'string')
            ->addAttribute('minscale', 'string')
            ->addAttribute('symbolscale', 'int')
            ->addAttribute('sizeunits_id', 'float', null, true, true, [
                'lookup' => [
                    'table' => 'e_sizeunits',
                    'column' => 'sizeunits_id',
                ],
            ])
            ->addAttribute('data_extent', 'string')
            ->addAttribute('data_filter', 'string')
            ->addAttribute('layer_def', 'string')
            ->addAttribute('metadata', 'string')
            ->addAttribute('labelitem', 'string')
            ->addAttribute('labelsizeitem', 'string')
            ->addAttribute('labelmaxscale', 'string')
            ->addAttribute('labelminscale', 'string')
            ->addAttribute('postlabelcache', 'float')
            ->addAttribute('classitem', 'string')
            ->addAttribute('private', 'float')
            ->addAttribute('queryable', 'float')
            ->addAttribute('hide_vector_geom', 'float')
            ->addAttribute('hidden', 'float')
            ->addAttribute('searchable_id', 'float', null, true, true, [
                'lookup' => [
                    'table' => 'e_searchable',
                    'column' => 'searchable_id',
                ],
            ])
            ->addAttribute('template', 'string')
            ->addAttribute('header', 'string')
            ->addAttribute('footer', 'string')
            ->addAttribute('tolerance', 'int')
            ->addAttribute('toleranceunits_id', 'float', null, true, true, [
                'lookup' => [
                    'table' => 'e_sizeunits',
                    'column' => 'sizeunits_id',
                ],
            ])
            ->addAttribute('selection_width', 'float')
            ->addAttribute('selection_color', 'string')
            ->addAttribute('maxfeatures', 'int')
            ->addAttribute('maxvectfeatures', 'int')
            ->addAttribute('zoom_buffer', 'float')
            ->addAttribute('last_update', 'string')
            ->addRelationship('catalog', 'catalog_id')
            ->addRelationship('layergroup', 'layergroup_id');
    }

    private static function buildClass(): EntitySchema
    {
        return EntitySchema::entity('class', 'class_id', 'int')
            ->filterable(['class_id', 'layer_id', 'class_name', 'class_title', 'legendtype_id', 'maxscale', 'minscale'])
            ->sortable(['class_id', 'class_order', 'class_name', 'class_title'], 'class_order')
            ->addAttribute('class_name', 'string')
            ->addAttribute('class_title', 'string')
            ->addAttribute('class_order', 'int')
            ->addAttribute('expression', 'string')
            ->addAttribute('keyimage', 'string')
            ->addAttribute('legendtype_id', 'int')
            ->addAttribute('maxscale', 'string')
            ->addAttribute('minscale', 'string')
            ->addAttribute('class_template', 'string')
            ->addAttribute('label_font', 'string')
            ->addAttribute('label_maxsize', 'int')
            ->addAttribute('label_minsize', 'int')
            ->addAttribute('label_size', 'string')
            ->addAttribute('class_text', 'string')
            ->addAttribute('label_color', 'string')
            ->addAttribute('label_outlinecolor', 'string')
            ->addAttribute('label_bgcolor', 'string')
            ->addAttribute('label_position', 'string')
            ->addAttribute('label_angle', 'string')
            ->addAttribute('label_def', 'string')
            ->addAttribute('label_force', 'int')
            ->addAttribute('label_priority', 'int')
            ->addAttribute('label_buffer', 'int')
            ->addAttribute('label_antialias', 'int')
            ->addAttribute('label_wrap', 'string')
            ->addRelationship('layer', 'layer_id');
    }

    private static function buildStyle(): EntitySchema
    {
        return EntitySchema::entity('style', 'style_id', 'int')
            ->filterable(['style_id', 'class_id', 'style_name', 'symbol_name', 'pattern_id', 'color', 'outlinecolor'])
            ->sortable(['style_id', 'style_order', 'style_name'], 'style_order')
            ->addAttribute('style_name', 'string')
            ->addAttribute('style_order', 'int')
            ->addAttribute('symbol_name', 'string')
            ->addAttribute('pattern_id', 'int', null, true, true, [
                'lookup' => [
                    'table' => 'e_pattern',
                    'column' => 'pattern_id',
                ],
            ])
            ->addAttribute('color', 'string')
            ->addAttribute('outlinecolor', 'string')
            ->addAttribute('bgcolor', 'string')
            ->addAttribute('maxsize', 'string')
            ->addAttribute('minsize', 'string')
            ->addAttribute('size', 'string')
            ->addAttribute('maxwidth', 'string')
            ->addAttribute('minwidth', 'string')
            ->addAttribute('width', 'string')
            ->addAttribute('angle', 'string')
            ->addAttribute('style_def', 'string')
            ->addRelationship('class', 'class_id');
    }

    private static function buildField(): EntitySchema
    {
        return EntitySchema::entity('field', 'field_id', 'int')
            ->filterable(['field_id', 'layer_id', 'relation_id', 'field_name', 'field_header', 'field_order', 'fieldtype_id', 'datatype_id', 'searchtype_id', 'resultype_id'])
            ->sortable(['field_id', 'field_order', 'field_name', 'field_header'], 'field_order')
            ->addAttribute('relation_id', 'int')
            ->addAttribute('field_name', 'string')
            ->addAttribute('field_header', 'string')
            ->addAttribute('field_order', 'int')
            ->addAttribute('fieldtype_id', 'int', null, true, true, [
                'lookup' => [
                    'table' => 'e_fieldtype',
                    'column' => 'fieldtype_id',
                ],
            ])
            ->addAttribute('datatype_id', 'int', null, true, true, [
                'lookup' => [
                    'table' => 'e_datatype',
                    'column' => 'datatype_id',
                ],
            ])
            ->addAttribute('formula', 'string')
            ->addAttribute('field_format', 'string')
            ->addAttribute('resultype_id', 'int', null, true, true, [
                'lookup' => [
                    'table' => 'e_resultype',
                    'column' => 'resultype_id',
                ],
            ])
            ->addAttribute('searchtype_id', 'int', null, true, true, [
                'lookup' => [
                    'table' => 'e_searchtype',
                    'column' => 'searchtype_id',
                ],
            ])
            ->addAttribute('filter_field_name', 'string')
            ->addAttribute('orderby_id', 'int', null, true, true, [
                'lookup' => [
                    'table' => 'e_orderby',
                    'column' => 'orderby_id',
                ],
            ])
            ->addAttribute('default_op', 'string')
            ->addAttribute('editable', 'float')
            ->addAttribute('mandatory', 'float')
            ->addAttribute('lookup_table', 'string')
            ->addAttribute('lookup_id', 'string')
            ->addAttribute('lookup_name', 'string')
            ->addRelationship('layer', 'layer_id');
    }

    private static function buildCatalog(): EntitySchema
    {
        return EntitySchema::entity('catalog', 'catalog_id', 'int')
            ->filterable(['catalog_id', 'project_name', 'catalog_name', 'connection_type'])
            ->sortable(['catalog_id', 'catalog_name', 'connection_type'], 'catalog_name')
            ->addAttribute('catalog_name', 'string')
            ->addAttribute('connection_type', 'int', null, true, true, [
                'lookup' => [
                    'table' => 'e_conntype',
                    'column' => 'conntype_id',
                ],
            ])
            ->addAttribute('set_extent', 'int')
            ->addAttribute('catalog_path', 'string')
            ->addAttribute('files_path', 'string')
            ->addAttribute('catalog_description', 'string')
            ->addRelationship('project', 'project_name');
    }

    private static function buildLink(): EntitySchema
    {
        return EntitySchema::entity('link', 'link_id', 'int')
            ->filterable(['link_id', 'project_name', 'link_name', 'link_order'])
            ->sortable(['link_id', 'link_order', 'link_name'], 'link_order')
            ->addAttribute('link_name', 'string')
            ->addAttribute('link_def', 'string')
            ->addAttribute('winw', 'int')
            ->addAttribute('winh', 'int')
            ->addRelationship('project', 'project_name');
    }

    private static function buildMapset(): EntitySchema
    {
        return EntitySchema::entity('mapset', 'mapset_name', 'string')
            ->filterable(['mapset_name', 'project_name', 'mapset_title', 'mapset_srid', 'displayprojection', 'private', 'mapset_order'])
            ->sortable(['mapset_name', 'mapset_title', 'mapset_order', 'mapset_srid'], 'mapset_order')
            ->addAttribute('mapset_title', 'string')
            ->addAttribute('maxscale', 'int')
            ->addAttribute('minscale', 'int')
            ->addAttribute('mapset_srid', 'int', null, true, true, [
                'lookup' => [
                    'table' => 'seldb_mapset_srid',
                    'column' => 'id',
                    'filters' => [
                        'project_name' => 'from_attribute:project_name',
                    ],
                ],
            ])
            ->addAttribute('displayprojection', 'int', null, true, true, [
                'lookup' => [
                    'table' => 'seldb_mapset_srid',
                    'column' => 'id',
                    'filters' => [
                        'project_name' => 'from_attribute:project_name',
                    ],
                ],
            ])
            ->addAttribute('sizeunits_id', 'int', null, true, true, [
                'lookup' => [
                    'table' => 'e_sizeunits',
                    'column' => 'sizeunits_id',
                ],
            ])
            ->addAttribute('mapset_scales', 'string')
            ->addAttribute('mapset_extent', 'string')
            ->addAttribute('refmap_extent', 'string')
            ->addAttribute('template', 'string')
            ->addAttribute('mapset_scale_type', 'int')
            ->addAttribute('mapset_order', 'int')
            ->addAttribute('private', 'int')
            ->addAttribute('mapset_description', 'string')
            ->addAttribute('page_size', 'string')
            ->addAttribute('dl_image_res', 'string')
            ->addAttribute('mapset_def', 'string')
            ->addAttribute('metadata', 'string')
            ->addAttribute('geolocator', 'string')
            ->addAttribute('bg_color', 'string')
            ->addAttribute('static_reference', 'int')
            ->addAttribute('mapset_tiles', 'int', null, true, true, [
                'lookup' => [
                    'table' => 'seldb_mapset_tiles',
                    'column' => 'id',
                ],
            ])
            ->addRelationship('project', 'project_name');
    }

    private static function buildMapsetLayergroup(): EntitySchema
    {
        return EntitySchema::entity('mapset_layergroup', 'id', 'int')
            ->filterable(['id', 'mapset_name', 'layergroup_id', 'status', 'refmap', 'hide'])
            ->sortable(['id', 'layergroup_id', 'status', 'refmap', 'hide'], 'id')
            ->addAttribute('status', 'int')
            ->addAttribute('refmap', 'int')
            ->addAttribute('hide', 'int')
            ->addRelationship('mapset', 'mapset_name')
            ->addRelationship('layergroup', 'layergroup_id');
    }
}
