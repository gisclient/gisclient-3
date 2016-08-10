INSERT INTO gisclient_34.users SELECT * FROM gisclient_33.users;
INSERT INTO gisclient_34.usercontext SELECT * FROM gisclient_33.usercontext;
INSERT INTO gisclient_34.users_options SELECT * FROM gisclient_33.users_options;
INSERT INTO gisclient_34.groups SELECT * FROM gisclient_33.groups;
INSERT INTO gisclient_34.user_group SELECT * FROM gisclient_33.user_group;
INSERT INTO gisclient_34.symbol SELECT * FROM gisclient_33.symbol;
INSERT INTO gisclient_34.font SELECT * FROM gisclient_33.font;
INSERT INTO gisclient_34.project(
"project_name",
"project_description",
"base_path",
"base_url",
"project_extent",
"sel_user_color",
"sel_transparency",
"imagelabel_font",
"imagelabel_text",
"imagelabel_offset_x",
"imagelabel_offset_y",
"imagelabel_position",
"icon_w",
"icon_h",
"history",
"project_srid",
"imagelabel_size",
"imagelabel_color",
"login_page",
"project_title",
"project_note",
"xc",
"yc",
"include_outputformats",
"include_legend",
"include_metadata",
"max_extent_scale",
"legend_font_size",
"default_language_id",
"charset_encodings_id"
)
SELECT
"project_name",
"project_description",
"base_path",
"base_url",
"project_extent",
"sel_user_color",
"sel_transparency",
"imagelabel_font",
"imagelabel_text",
"imagelabel_offset_x",
"imagelabel_offset_y",
"imagelabel_position",
"icon_w",
"icon_h",
"history",
"project_srid",
"imagelabel_size",
"imagelabel_color",
"login_page",
"project_title",
"project_note",
"xc",
"yc",
"include_outputformats",
"include_legend",
"include_metadata",
"max_extent_scale",
"legend_font_size",
"default_language_id",
"charset_encodings_id"
FROM gisclient_33.project;
INSERT INTO gisclient_34.project_srs SELECT * FROM gisclient_33.project_srs;
INSERT INTO gisclient_34.project_languages SELECT * FROM gisclient_33.project_languages;
INSERT INTO gisclient_34.project_admin SELECT * FROM gisclient_33.project_admin;
INSERT INTO gisclient_34.link SELECT * FROM gisclient_33.link;

INSERT INTO gisclient_34.catalog SELECT * FROM gisclient_33.catalog;
INSERT INTO gisclient_34.theme SELECT * FROM gisclient_33.theme;
INSERT INTO gisclient_34.layergroup SELECT * FROM gisclient_33.layergroup;
INSERT INTO gisclient_34.layer(
"layer_id",
"layergroup_id",
"layer_name",
"layertype_id",
"catalog_id",
"data",
"data_geom",
"data_unique",
"data_srid",
"data_filter",
"classitem",
"labelitem",
"labelsizeitem",
"labelminscale",
"labelmaxscale",
"maxscale",
"minscale",
"symbolscale",
"opacity",
"maxfeatures",
"sizeunits_id",
"layer_def",
"metadata",
"template",
"header",
"footer",
"tolerance",
"layer_order",
"queryable",
"layer_title",
"zoom_buffer",
"group_object",
"selection_color",
"papersize_id",
"toleranceunits_id",
"hidden",
"selection_width",
"selection_info",
"data_extent",
"private",
"postlabelcache",
"maxvectfeatures",
"data_type",
"last_update",
"searchable_id",
"hide_vector_geom"
)
SELECT
"layer_id",
"layergroup_id",
"layer_name",
"layertype_id",
"catalog_id",
"data",
"data_geom",
"data_unique",
"data_srid",
"data_filter",
"classitem",
"labelitem",
"labelsizeitem",
"labelminscale",
"labelmaxscale",
"maxscale",
"minscale",
"symbolscale",
"opacity",
"maxfeatures",
"sizeunits_id",
"layer_def",
"metadata",
"template",
"header",
"footer",
"tolerance",
"layer_order",
"queryable",
"layer_title",
"zoom_buffer",
"group_object",
"selection_color",
"papersize_id",
"toleranceunits_id",
"hidden",
"selection_width",
"selection_info",
"data_extent",
"private",
"postlabelcache",
"maxvectfeatures",
"data_type",
"last_update",
"searchable_id",
"hide_vector_geom"
FROM gisclient_33.layer;
INSERT INTO gisclient_34.class(
"class_id",
"layer_id",
"class_order",
"class_name",
"class_title",
"class_text",
"expression",
"maxscale",
"minscale",
"class_template",
"legendtype_id",
"symbol_ttf_name",
"label_font",
"label_angle",
"label_color",
"label_outlinecolor",
"label_bgcolor",
"label_size",
"label_minsize",
"label_maxsize",
"label_position",
"label_antialias",
"label_free",
"label_priority",
"label_wrap",
"label_buffer",
"label_force",
"label_def",
"locked",
"class_image",
"keyimage"
)
SELECT
"class_id",
"layer_id",
"class_order",
"class_name",
"class_title",
"class_text",
"expression",
"maxscale",
"minscale",
"class_template",
"legendtype_id",
"symbol_ttf_name",
"label_font",
"label_angle",
"label_color",
"label_outlinecolor",
"label_bgcolor",
"label_size",
"label_minsize",
"label_maxsize",
"label_position",
"label_antialias",
"label_free",
"label_priority",
"label_wrap",
"label_buffer",
"label_force",
"label_def",
"locked",
"class_image",
"keyimage"
FROM gisclient_33.class;
delete from gisclient_34.e_pattern;
INSERT INTO gisclient_34.e_pattern SELECT * FROM gisclient_33.e_pattern;
INSERT INTO gisclient_34.style SELECT * FROM gisclient_33.style;
INSERT INTO gisclient_34.relation SELECT * FROM gisclient_33.relation;
INSERT INTO gisclient_34.field SELECT * FROM gisclient_33.field;
INSERT INTO gisclient_34.field_groups SELECT * FROM gisclient_33.field_groups;
INSERT INTO gisclient_34.i18n_field SELECT * FROM gisclient_33.i18n_field;
INSERT INTO gisclient_34.localization SELECT * FROM gisclient_33.localization;
INSERT INTO gisclient_34.layer_link SELECT * FROM gisclient_33.layer_link;
INSERT INTO gisclient_34.layer_groups SELECT * FROM gisclient_33.layer_groups;

INSERT INTO gisclient_34.selgroup(
"selgroup_id",
"project_name",
"selgroup_name",
"selgroup_order",
"selgroup_title"
) 
SELECT
"selgroup_id",
"project_name",
"selgroup_name",
"selgroup_order",
"selgroup_title"
FROM gisclient_33.selgroup;
INSERT INTO gisclient_34.selgroup_layer SELECT * FROM gisclient_33.selgroup_layer;
INSERT INTO gisclient_34.qt(
"qt_id",
"theme_id",
"layer_id",
"qt_name",
"max_rows",
"papersize_id",
"edit_url",
"groupobject",
"selection_color",
"qt_order",
"zoom_buffer",
"qtresultype_id",
"qt_filter"
)
SELECT
"qt_id",
"theme_id",
"layer_id",
"qt_name",
"max_rows",
"papersize_id",
"edit_url",
"groupobject",
"selection_color",
"qt_order",
"zoom_buffer",
"qtresultype_id",
"qt_filter"
FROM gisclient_33.qt;
INSERT INTO gisclient_34.qt_field(
"qtfield_id",
"qt_id",
"qtrelation_id",
"qtfield_name",
"field_header",
"fieldtype_id",
"searchtype_id",
"resultype_id",
"field_format",
"column_width",
"orderby_id",
"field_filter",
"datatype_id",
"qtfield_order",
"default_op",
"formula"
) 
SELECT
"qtfield_id",
"qt_id",
"qtrelation_id",
"qtfield_name",
"field_header",
"fieldtype_id",
"searchtype_id",
"resultype_id",
"field_format",
"column_width",
"orderby_id",
"field_filter",
"datatype_id",
"qtfield_order",
"default_op",
"formula"
FROM gisclient_33.qtfield;
INSERT INTO gisclient_34.qt_link SELECT * FROM gisclient_33.qt_link;

INSERT INTO gisclient_34.mapset(
"mapset_name",
"project_name",
"mapset_title",
"template",
"mapset_extent",
"page_size",
"filter_data",
"dl_image_res",
"imagelabel",
"bg_color",
"refmap_extent",
"test_extent",
"mapset_srid",
"mapset_def",
"mapset_group",
"private",
"sizeunits_id",
"static_reference",
"metadata",
"mask",
"mapset_description",
"mapset_note",
"maxscale",
"minscale",
"mapset_scales",
"displayprojection",
"mapset_tiles"
) 
SELECT
"mapset_name",
"project_name",
"mapset_title",
"template",
"mapset_extent",
"page_size",
"filter_data",
"dl_image_res",
"imagelabel",
"bg_color",
"refmap_extent",
"test_extent",
"mapset_srid",
"mapset_def",
"mapset_group",
"private",
"sizeunits_id",
"static_reference",
"metadata",
"mask",
"mapset_description",
"mapset_note",
"maxscale",
"minscale",
"mapset_scales",
"displayprojection",
"mapset_tiles"
FROM gisclient_33.mapset;
INSERT INTO gisclient_34.mapset_layergroup SELECT * FROM gisclient_33.mapset_layergroup;



UPDATE gisclient_34.mapset set template='jquery/default.html';



select column_name,table_schema from information_schema.columns where table_name='mapset' and table_schema='gisclient_33' order by ordinal_position;



