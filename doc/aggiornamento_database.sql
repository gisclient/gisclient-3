--VERSIONE 3.0

--Aggingere definizione di scale in config.php
--define('SCALE','1000000,500000,250000,100000,50000,25000,10000,7500,5000,4000,3000,2000,1000,900,800,700,600,500,400,300,200,100,50');
--risoluzioni gmap:
--156543.0339,78271.51695,39135.758475,19567.8792375,9783.93961875,4891.969809375,2445.9849046875,1222.99245234375,611.496226171875,305.7481130859375,152.87405654296876,76.43702827148438,38.21851413574219,19.109257067871095,9.554628533935547,4.777314266967774,2.388657133483887,1.1943285667419434,0.5971642833709717,0.29858214168548586,0.14929107084274293,0.07464553542137146
--Settare il path su ows.php
--merc. sferico
--Importare i servizi (google ecc...) verificare epsg:900913 su postgres e mapserver
 
--svn co http://gisclient.net/svn/gisclient/branches/sandbox-openlayer gisclient-3.0
--svn co svn+ssh://r3gis-svn/data/subversion/gisclient/branches/sandbox-openlayer gisclient-3.0


-- SCHEMA GISCLIENT 30 : DA UN DUMP DI GISCLIENT_21 RINOMINARE TUTTE LE OCCORENZE DI gisclient_21 IN gisclient_30 E CARICARE LO SCHEMA SU DB DOPO APPORTARE LE SEGUENTI MODIFICHE 



SET search_path = gisclient_32, pg_catalog;

ALTER TABLE e_level DROP CONSTRAINT e_level_parent_id_fkey;
DELETE FROM form_level;
DELETE FROM e_level;
DELETE FROM e_form;
DROP TRIGGER depth ON e_level CASCADE;
DROP TRIGGER leaf ON e_level CASCADE;


INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (1, 'root', NULL, 1, NULL, NULL, 0, 0, NULL, NULL, 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (2, 'project', 'project', 2, 1, 0, 0, 1, 1, 'project', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (3, 'groups', 'groups', 7, 1, 0, 0, 0, 1, 'groups', 1);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (4, 'users', 'users', 6, 1, 0, 0, 0, 1, 'users', 1);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (5, 'theme', 'theme', 3, 2, 1, 0, 5, 2, 'theme', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (6, 'project_srs', 'project_srs', 4, 2, 1, 1, 1, 2, 'project_srs', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (7, 'catalog', 'catalog', 13, 2, 1, 1, 2, 2, 'catalog', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (8, 'mapset', 'mapset', 15, 2, 1, 0, 6, 2, 'mapset', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (9, 'link', 'link', 15, 2, 1, 1, 4, 2, 'link', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (10, 'layergroup', 'layergroup', 4, 5, 2, 0, 1, 5, 'layergroup', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (11, 'layer', 'layer', 5, 10, 3, 0, 1, 10, 'layer', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (12, 'class', 'class', 6, 11, 4, 0, 1, 11, 'class', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (14, 'style', 'style', 7, 12, 5, 1, 1, 12, 'style', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (16, 'qtrelation', 'qtrelation', 10, 11, 4, 1, 1, 11, 'qtrelation', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (17, 'qtfield', 'qtfield', 11, 11, 4, 1, 2, 11, 'qtfield', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (22, 'mapset_layergroup', 'mapset_layergroup', 17, 8, 2, 1, 1, 8, 'mapset_layergroup', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (27, 'selgroup', 'selgroup', NULL, 2, 1, 0, 8, 2, 'selgroup', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (28, 'selgroup_layergroup', 'selgroup', NULL, 27, 2, 1, 1, 27, 'selgroup_layergroup', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (33, 'project_admin', 'project_admin', 15, 2, 1, 1, 0, 2, 'project_admin', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (45, 'group_users', 'user_groups', NULL, 4, 2, 1, 0, 4, 'user_group', 1);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (46, 'user_groups', 'group_users', NULL, 3, 2, 1, 0, 3, 'user_group', 1);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (32, 'user_project', 'project', 8, 2, 1, 1, 0, 2, 'user_project', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (47, 'layer_groups', 'layer_groups', NULL, 11, 4, 1, 1, 11, 'layer_groups', 2);

INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (2, 'progetto', 'project', 0, 2, NULL, NULL, NULL, NULL, NULL, 'project_name');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (3, 'progetto', 'project', 1, 2, '', NULL, NULL, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (5, 'mapset', 'mapset', 0, 8, NULL, NULL, NULL, NULL, NULL, 'title');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (6, 'progetto', 'project', 2, 2, '', 'project', NULL, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (7, 'progetto', 'project', 1, 2, NULL, 'project', NULL, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (8, 'temi', 'theme', 0, 5, NULL, NULL, NULL, NULL, NULL, 'theme_order,theme_title');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (9, 'temi', 'theme', 1, 5, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (10, 'temi', 'theme', 1, 5, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (11, 'temi', 'theme', 2, 5, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (12, 'project_srs', 'project_srs', 0, 6, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (13, 'project_srs', 'project_srs', 1, 6, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (14, 'project_srs', 'project_srs', 2, 6, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (16, 'user', 'user', 4, 4, NULL, 'user', 2, NULL, 'user', NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (18, 'user', 'user', 50, 4, NULL, 'user', 2, NULL, 'user', NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (20, 'group', 'group', 4, 3, NULL, 'group', 2, NULL, 'group', NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (23, 'group', 'group', 50, 3, NULL, 'group', 2, NULL, 'group', NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (26, 'mapset', 'mapset', 1, 8, '', NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (27, 'mapset', 'mapset', 1, 8, NULL, 'mapset', 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (28, 'mapset', 'mapset', 2, 2, NULL, 'mapset', 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (34, 'layer', 'layer', 0, 11, NULL, NULL, 10, NULL, NULL, 'layer_order,layer_name');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (35, 'layer', 'layer', 1, 11, NULL, 'layer', 10, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (36, 'layer', 'layer', 1, 11, NULL, 'layer', 10, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (37, 'layer', 'layer', 2, 11, NULL, 'layer', 10, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (38, 'classi', 'class', 0, 12, NULL, NULL, 11, NULL, NULL, 'class_order');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (39, 'classi', 'class', 1, 12, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (40, 'classi', 'class', 1, 12, NULL, 'class', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (41, 'classi', 'class', 2, 12, NULL, 'class', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (42, 'stili', 'style', 0, 14, NULL, NULL, 12, NULL, NULL, 'style_order');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (43, 'stili', 'style', 1, 14, NULL, NULL, 12, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (44, 'stili', 'style', 1, 14, NULL, 'style', 12, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (45, 'stili', 'style', 2, 14, NULL, 'style', 12, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (50, 'catalog', 'catalog', 0, 7, NULL, NULL, 2, NULL, NULL, 'catalog_name');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (51, 'catalog', 'catalog', 1, 7, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (52, 'catalog', 'catalog', 1, 7, NULL, 'catalog', 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (53, 'catalog', 'catalog', 2, 7, NULL, 'catalog', 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (70, 'links', 'link', 0, 9, '', NULL, 2, NULL, NULL, 'link_order,link_name');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (72, 'links', 'link', 1, 9, '', NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (73, 'links', 'link', 1, 9, '', NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (74, 'links', 'link', 2, 9, '', NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (105, 'selgroup', 'selgroup', 0, 27, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (106, 'selgroup', 'selgroup', 1, 27, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (107, 'selgroup', 'selgroup', 1, 27, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (133, 'project_admin', 'admin_project', 2, 33, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (134, 'project_admin', 'admin_project', 5, 33, NULL, 'admin_project', 6, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (151, 'user_groups', 'user_groups', 4, 46, NULL, 'user_groups', 4, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (152, 'user_groups', 'user_groups', 5, 46, NULL, 'user_groups', 4, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (75, 'qt_relation', 'qt_relation_addnew', 0, 16, NULL, NULL, 13, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (30, 'layergroup', 'layergroup', 0, 10, NULL, 'layergroup', 5, NULL, NULL, 'layergroup_order,layergroup_title');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (31, 'layergroup', 'layergroup', 1, 10, NULL, 'layergroup', 5, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (32, 'layergroup', 'layergroup', 1, 10, NULL, 'layergroup', 5, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (33, 'layergroup', 'layergroup', 2, 10, NULL, 'layergroup', 5, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (84, 'map_layer', 'mapset_layergroup', 4, 22, NULL, 'mapset_layergroup', 8, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (85, 'map_layer', 'mapset_layergroup', 5, 22, NULL, 'mapset_layergroup', 8, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (86, 'map_layer', 'mapset_layergroup', 0, 22, NULL, 'mapset_layergroup', 8, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (58, 'qt_relation', 'qtrelation', 0, 16, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (59, 'qt_relation', 'qtrelation', 1, 16, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (60, 'qt_relation', 'qtrelation', 1, 16, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (61, 'qt_relation', 'qtrelation', 2, 16, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (62, 'qt_fields', 'qtfield', 0, 17, NULL, NULL, 11, NULL, NULL, 'qtrelationtype_id,qtrelation_name,field_header,qtfield_name');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (63, 'qt_fields', 'qtfield', 1, 17, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (64, 'qt_fields', 'qtfield', 1, 17, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (65, 'qt_fields', 'qtfield', 2, 17, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (170, 'layer_groups', 'layer_groups', 4, 47, NULL, 'layer_groups', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (171, 'layer_groups', 'layer_groups', 5, 47, NULL, 'layer_groups', 11, NULL, NULL, NULL);

INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (1, 1, 3, 2, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (2, 2, 0, 3, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (4, 2, 3, 8, 5, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (5, 2, 3, 5, 8, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (7, 2, 1, 7, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (8, 2, 2, 6, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (14, 2, 3, 12, 3, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (15, 6, 1, 13, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (16, 6, 2, 13, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (17, 6, 0, 13, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (19, 8, 0, 26, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (20, 8, 1, 27, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (21, 8, 2, 28, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (22, 5, 0, 9, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (23, 5, 1, 10, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (24, 5, 2, 11, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (25, 5, 3, 30, 3, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (26, 10, 0, 31, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (27, 10, 1, 32, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (28, 10, 2, 33, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (29, 10, 3, 34, 3, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (30, 11, 0, 35, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (31, 11, 1, 36, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (32, 11, 2, 37, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (34, 12, 0, 39, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (35, 12, 1, 40, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (36, 12, 2, 41, 2, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (37, 12, 3, 42, 3, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (38, 14, 0, 43, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (39, 14, 1, 44, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (40, 14, 2, 45, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (45, 2, 3, 50, 4, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (46, 7, 0, 51, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (47, 7, 1, 52, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (48, 7, 2, 53, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (54, 16, 0, 59, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (55, 16, 1, 60, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (56, 16, 2, 61, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (57, 17, 0, 63, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (58, 17, 1, 64, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (59, 17, 2, 65, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (63, 2, 3, 70, 7, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (64, 9, 0, 72, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (65, 9, 1, 73, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (66, 9, 2, 74, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (77, 8, 3, 84, 6, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (78, 22, 1, 85, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (98, 2, 3, 105, 6, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (99, 27, 1, 106, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (101, 27, 0, 107, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (127, 33, 1, 134, 15, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (131, 2, 3, 133, 15, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (132, 27, 2, 106, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (164, 1, 3, 16, 3, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (165, 4, 0, 18, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (166, 4, 1, 18, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (167, 4, 2, 18, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (168, 1, 3, 20, 2, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (169, 3, 0, 23, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (170, 3, 1, 23, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (171, 3, 2, 23, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (175, 4, 3, 151, 2, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (176, 46, 1, 152, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (79, 22, -1, 86, 2, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (69, 16, 1, 75, 2, 0);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (100, 27, 2, 105, 2, 0);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (163, 27, 3, 151, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (33, 11, 3, 38, 3, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (51, 11, 3, 58, 4, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (52, 11, 3, 62, 5, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (200, 11, 0, 170, 7, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (201, 47, 1, 171, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (202, 47, 3, 171, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (203, 47, 2, 171, 1, 1);

CREATE TABLE e_owstype
(
  owstype_id smallint NOT NULL,
  owstype_name character varying NOT NULL,
  owstype_order smallint,
  CONSTRAINT e_owstype_pkey PRIMARY KEY (owstype_id)
);
CREATE OR REPLACE VIEW seldb_owstype AS SELECT e_owstype.owstype_id AS id, e_owstype.owstype_name AS opzione FROM e_owstype;

INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (1, 'OWS', 1);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (2, 'Google', 2);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (3, 'VirtualEarth', 3);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (4, 'Yahoo', 4);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (5, 'OSM', 5);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (6, 'TMS', 6);

CREATE TABLE e_tiletype (
    tiletype_id smallint NOT NULL,
    tiletype_name character varying NOT NULL,
    tiletype_order smallint,
	CONSTRAINT e_tiletype_pkey PRIMARY KEY (tiletype_id)
);
INSERT INTO e_tiletype (tiletype_id, tiletype_name, tiletype_order) VALUES (0, 'no Tiles', 1);
INSERT INTO e_tiletype (tiletype_id, tiletype_name, tiletype_order) VALUES (1, 'WMS Tiles', 2);
INSERT INTO e_tiletype (tiletype_id, tiletype_name, tiletype_order) VALUES (2, 'Cache Tiles', 3);
CREATE OR REPLACE VIEW seldb_tiletype AS SELECT e_tiletype.tiletype_id AS id, e_tiletype.tiletype_name AS opzione FROM e_tiletype;

DROP TABLE e_outputformat cascade;
CREATE TABLE e_outputformat (
    outputformat_id smallint NOT NULL,
    outputformat_name character varying NOT NULL,
    outputformat_driver character varying NOT NULL,
    outputformat_mimetype character varying NOT NULL,
    outputformat_imagemode character varying NOT NULL,
    outputformat_extension character varying NOT NULL,
    outputformat_option character varying,
    outputformat_order smallint
);

INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (1, 'AGG PNG 24 bit', 'AGG/PNG', 'image/png; mode=24bit', 'RGB', 'png', NULL, NULL);
INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (2, 'AGG PNG', 'AGG/PNG', 'image/png', 'PC256', 'png', NULL, NULL);
INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (3, 'AGG JPG', 'AGG/JPG', 'image/jpg', 'RGB', 'jpg', NULL, NULL);
INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (4, 'PNG 8 bit', 'GD/PNG', 'image/png', 'PC256', 'png', NULL, NULL);
INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (5, 'PNG 24 bit', 'GD/PNG', 'image/png', 'RGB', 'png', NULL, NULL);
INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (6, 'PNG 32 bit Trasp', 'GD/PNG', 'image/png', 'RGBA', 'png', NULL, NULL);
INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (7, 'AGG Q', 'AGG/PNG', 'image/png; mode=8bit', 'RGB', 'png', '    FORMATOPTION "QUANTIZE_FORCE=ON"
    FORMATOPTION "QUANTIZE_DITHER=OFF"
    FORMATOPTION "QUANTIZE_COLORS=256"', NULL);
ALTER TABLE ONLY e_outputformat
    ADD CONSTRAINT e_outputformat_pkey PRIMARY KEY (outputformat_id);
CREATE OR REPLACE VIEW seldb_outputformat AS 
 SELECT e_outputformat.outputformat_id AS id, e_outputformat.outputformat_name AS opzione
                   FROM e_outputformat
                  ORDER BY e_outputformat.outputformat_order;

UPDATE e_layertype set layertype_id = 10 WHERE layertype_id = 11;
UPDATE e_layertype set layertype_name = 'tileraster', layertype_ms = 100 WHERE layertype_id = 10;	 
DELETE FROM e_layertype where layertype_name = 'tileindex';
--INSERT INTO e_layertype (layertype_id, layertype_name, layertype_ms, layertype_order) VALUES (11, 'tileraster', 100, NULL);
DELETE FROM e_layertype where layertype_ms = 99;

DROP VIEW seldb_sizeunits;
CREATE OR REPLACE VIEW seldb_sizeunits AS 
 SELECT e_sizeunits.sizeunits_id AS id, e_sizeunits.sizeunits_name AS opzione
   FROM e_sizeunits;

				  
ALTER TABLE project ADD COLUMN xc numeric;
ALTER TABLE project ADD COLUMN yc numeric;
ALTER TABLE project ADD COLUMN include_outputformats character varying;
ALTER TABLE project ADD COLUMN include_legend character varying;
ALTER TABLE project ADD COLUMN include_metadata character varying;
ALTER TABLE project ADD COLUMN max_extent_scale numeric;

ALTER TABLE project_srs RENAME param  TO projparam;

ALTER TABLE theme ADD COLUMN single numeric(1,0) DEFAULT 0;
ALTER TABLE theme ADD COLUMN radio numeric(1,0) DEFAULT 0;

ALTER TABLE layergroup ADD COLUMN isbaselayer numeric(1) default 0;
ALTER TABLE layergroup ADD COLUMN tiletype_id numeric(1) default 0;
ALTER TABLE layergroup ADD COLUMN attribution character varying;
ALTER TABLE layergroup ADD COLUMN sld character varying;
ALTER TABLE layergroup ADD COLUMN style character varying;
ALTER TABLE layergroup ADD COLUMN url character varying;
ALTER TABLE layergroup ADD COLUMN owstype_id numeric(1) default 0;
ALTER TABLE layergroup ADD COLUMN outputformat_id numeric(1) default 0;
ALTER TABLE layergroup ADD COLUMN layers character varying;
ALTER TABLE layergroup ADD COLUMN parameters character varying;
ALTER TABLE layergroup ADD COLUMN gutter numeric(1) default 0;
ALTER TABLE layergroup ADD COLUMN transition numeric(1,0) default 0;
ALTER TABLE layergroup ADD COLUMN group_by character varying;
ALTER TABLE layergroup ADD COLUMN layergroup_description character varying;
ALTER TABLE layergroup ADD COLUMN buffer numeric(1,0) default 0;
ALTER TABLE layergroup ADD COLUMN tiles_extent character varying;
ALTER TABLE layergroup ADD COLUMN tiles_extent_srid integer;

ALTER TABLE layer DROP COLUMN mapset_filter;
ALTER TABLE layer DROP COLUMN locked;
ALTER TABLE layer DROP COLUMN static;
DROP TRIGGER chk_layer ON layer;
ALTER TABLE layer ADD COLUMN queryable numeric(1,0) default 0;
ALTER TABLE layer ADD COLUMN layer_title character varying;
ALTER TABLE layer ADD COLUMN zoom_buffer numeric; 
ALTER TABLE layer ADD COLUMN group_object numeric(1,0) default 0;
ALTER TABLE layer ADD COLUMN selection_color character varying;
ALTER TABLE layer ADD COLUMN papersize_id numeric;
ALTER TABLE layer ADD COLUMN toleranceunits_id numeric(1,0);
ALTER TABLE layer ADD COLUMN hidden numeric(1,0) default 0;
ALTER TABLE layer ADD COLUMN language_id character varying;
ALTER TABLE layer ADD COLUMN selection_width numeric(2,0);
ALTER TABLE layer ADD COLUMN selection_info numeric(1,0);
ALTER TABLE layer RENAME transparency  TO opacity;

ALTER TABLE mapset ADD COLUMN maxscale integer;
ALTER TABLE mapset ADD COLUMN minscale integer;

ALTER TABLE mapset_layergroup ADD COLUMN hide numeric(1,0) DEFAULT 0;

--CALCOLA CENTRO DA ESTENSIONE
UPDATE project SET xc = split_part(project_extent,' ',1)::float + (split_part(project_extent,' ',3)::float - split_part(project_extent,' ',1)::float)/2,yc = split_part(project_extent,' ',2)::float + (split_part(project_extent,' ',4)::float - split_part(project_extent,' ',2)::float)/2;

--TUTTI LAYERS WMS
UPDATE layergroup set owstype_id = 1;
UPDATE layergroup set outputformat_id = 1;


--SETTO QUELLO CHE MANCA IN LAYER
UPDATE layer SET sizeunits_id=1 where sizeunits_id = -1;
UPDATE layer SET queryable=0;


--AGGIORNAMENTO QT => LAYER
ALTER TABLE qtfield ADD COLUMN layer_id integer;
ALTER TABLE qtrelation ADD COLUMN layer_id integer;
UPDATE qtfield SET layer_id=qt.layer_id FROM qt WHERE qtfield.qt_id=qt.qt_id;
UPDATE qtrelation SET layer_id=qt.layer_id FROM qt WHERE qtrelation.qt_id=qt.qt_id;
ALTER TABLE qtfield DROP CONSTRAINT qtfield_qt_id_fkey;
ALTER TABLE qtfield
  ADD CONSTRAINT qtfield_layer_id_fkey FOREIGN KEY (layer_id)
      REFERENCES layer (layer_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE;
	  
ALTER TABLE qtrelation DROP CONSTRAINT qtrelation_qt_id_fkey;
ALTER TABLE qtrelation
  ADD CONSTRAINT qtrelation_layer_id_fkey FOREIGN KEY (layer_id)
      REFERENCES layer (layer_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE;
	  
DROP VIEW vista_qtfield;
CREATE OR REPLACE VIEW vista_qtfield AS 
 SELECT qtfield.qtfield_id, qtfield.layer_id, qtfield.fieldtype_id, qtfield.resultype_id, x.qtrelation_id, qtfield.qtfield_name, qtfield.field_header, qtfield.qtfield_order, COALESCE(qtfield.column_width, 0) AS column_width, x.name AS qtrelation_name, x.qtrelationtype_id, x.qtrelationtype_name
   FROM qtfield
   JOIN e_fieldtype USING (fieldtype_id)
   JOIN ( SELECT y.qtrelationtype_id, y.qtrelation_id, y.name, z.qtrelationtype_name
      FROM (         SELECT 0 AS qtrelation_id, 'Data Layer' AS name, 0 AS qtrelationtype_id
           UNION 
                    SELECT qtrelation.qtrelation_id, COALESCE(qtrelation.qtrelation_name, 'Nessuna Relazione'::character varying) AS name, qtrelation.qtrelationtype_id
                      FROM qtrelation) y
   JOIN (         SELECT 0 AS qtrelationtype_id, '' AS qtrelationtype_name
           UNION 
                    SELECT e_qtrelationtype.qtrelationtype_id, e_qtrelationtype.qtrelationtype_name
                      FROM e_qtrelationtype) z USING (qtrelationtype_id)) x USING (qtrelation_id)
  ORDER BY qtfield.qtfield_id, x.qtrelation_id, x.qtrelationtype_id;

DROP VIEW seldb_qtrelation;
CREATE OR REPLACE VIEW seldb_qtrelation AS 
        SELECT 0 AS id, 'layer' AS opzione, 0 AS layer_id
UNION 
        SELECT qtrelation.qtrelation_id AS id, qtrelation.qtrelation_name AS opzione, qtrelation.layer_id
           FROM qtrelation;

DROP VIEW seldb_language;
CREATE OR REPLACE VIEW seldb_language AS 
 SELECT foo.id, foo.opzione
   FROM (         SELECT ''::text AS id, 'Seleziona ====>' AS opzione
        UNION 
                 SELECT e_language.language_id AS id, e_language.language_name AS opzione
                   FROM e_language) foo
  ORDER BY foo.id;

CREATE OR REPLACE VIEW seldb_layer_layergroup AS 
         SELECT (-1) AS id, 'Seleziona ====>' AS opzione, NULL::unknown AS layergroup_id
UNION 
        ( SELECT DISTINCT layer.layer_id AS id, layer.layer_name AS opzione, layer.layergroup_id
           FROM layer
          WHERE layer.queryable = 1::numeric
          ORDER BY layer.layer_name, layer.layer_id, layer.layergroup_id);
		  	  
CREATE OR REPLACE VIEW seldb_mapset_srid AS 
 SELECT project_srs.srid AS id, project_srs.srid AS opzione, project_srs.project_name
   FROM project_srs;
 


DROP VIEW seldb_field_filter;
CREATE OR REPLACE VIEW seldb_field_filter AS 
         SELECT (-1) AS id, 'Nessuno' AS opzione, 0 AS qtfield_id, 0 AS qt_id
UNION 
        ( SELECT x.qtfield_id AS id, x.field_header AS opzione, y.qtfield_id, x.layer_id
           FROM qtfield x
      JOIN qtfield y USING (layer_id)
     WHERE x.qtfield_id <> y.qtfield_id
     ORDER BY x.qtfield_id, x.qtfield_order); 
ALTER TABLE qtfield DROP COLUMN qt_id CASCADE;
ALTER TABLE qtrelation DROP COLUMN qt_id CASCADE;

  
--AGGIORNAMENTO DEL VALORE DI EXPRESSION IN CLASS (AGGIUNTE LE PARENTESI)
update class set expression='('||expression||')' where (expression like '(''[%' or expression like '''[%'  or expression like '[%' ) and not expression like '(%)';

--GESTISCE IL KEYIMAGE
ALTER TABLE class ADD COLUMN keyimage character varying;
UPDATE class SET keyimage = 'NO' FROM layer where layertype_id=5 and class.layer_id=layer.layer_id;

-- NUOVA FEATURE: wfs_encoding per il tema
CREATE TABLE e_charset_encodings
(
   charset_encodings_id integer,
   charset_encodings_name character varying NOT NULL,
   charset_encodings_order smallint,
    PRIMARY KEY (charset_encodings_id)
)
WITH (
  OIDS = FALSE
)
;
INSERT INTO e_charset_encodings VALUES (1, 'ISO-8859-1', 1) ;
INSERT INTO e_charset_encodings VALUES (2, 'UTF-8', 2) ;
ALTER TABLE theme ADD COLUMN charset_encodings_id integer;
ALTER TABLE theme ADD FOREIGN KEY (charset_encodings_id) REFERENCES e_charset_encodings (charset_encodings_id) ON UPDATE NO ACTION ON DELETE NO ACTION;
CREATE OR REPLACE VIEW seldb_charset_encodings AS 
 SELECT e_charset_encodings.charset_encodings_id AS id, e_charset_encodings.charset_encodings_name AS opzione, e_charset_encodings.charset_encodings_order AS option_order
   FROM e_charset_encodings
  ORDER BY e_charset_encodings.charset_encodings_order;

-- AUTORIZZAZIONI UTENTI LAYER

ALTER TABLE layer ADD COLUMN private numeric(1,0) DEFAULT 0;
UPDATE LAYER SET private=0;

CREATE TABLE layer_groups
(
  layer_id integer,
  groupname character varying NOT NULL,
  wms integer DEFAULT 0,
  wfs integer DEFAULT 0,
  wfst integer DEFAULT 0,
  layer_name character varying,
  CONSTRAINT layer_groups_pkey PRIMARY KEY (layer_id, groupname),
  CONSTRAINT layer_groups_layer_id_fkey FOREIGN KEY (layer_id)
      REFERENCES layer (layer_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);

CREATE INDEX fki_layer_id
  ON layer_groups
  USING btree
  (layer_id);
  
CREATE OR REPLACE FUNCTION set_layer_name()
  RETURNS "trigger" AS
$BODY$
BEGIN
	select into new.layer_name layer_name FROM layer where layer_id=new.layer_id;
	return new;
END
$BODY$
  LANGUAGE 'plpgsql' VOLATILE;

CREATE TRIGGER layername
  BEFORE INSERT OR UPDATE
  ON layer_groups
  FOR EACH ROW
  EXECUTE PROCEDURE set_layer_name();
  
  
ALTER TABLE layergroup ADD COLUMN layergroup_single numeric(1,0) DEFAULT 1;
ALTER TABLE theme RENAME single TO theme_single;
ALTER TABLE layergroup RENAME group_by TO tree_group;


UPDATE e_owstype SET owstype_name='Google v.2' WHERE owstype_id=2;
UPDATE e_owstype SET owstype_name='TMS' WHERE owstype_id=6;
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (7, 'Google v.3', 7);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (8, 'Bing tiles', 8);

DROP TRIGGER check_mapset ON mapset;
ALTER TABLE mapset ALTER COLUMN mapset_extent DROP NOT NULL;
UPDATE project set max_extent_scale = 10000000 WHERE max_extent_scale IS NULL;
ALTER TABLE project ALTER COLUMN max_extent_scale SET NOT NULL;


UPDATE symbol SET symbol_def=replace(symbol_def,'../','../../') WHERE symbol_def LIKE '%PIXMAP%' AND NOT symbol_def LIKE '%../../%';

-- passaggio da "Mostra campo in" a "Mostra campo" per i qtfield
DELETE FROM e_resultype where resultype_id in(2,3);
UPDATE e_resultype set resultype_name='Si' where resultype_id=1;
UPDATE e_resultype set resultype_name='No' where resultype_id=4;
UPDATE qtfield set resultype_id=1 where resultype_id!=4;

-- FD: aggiunto postlabelcache tra i campi del layer
ALTER TABLE layer ADD COLUMN postlabelcache numeric(1,0) DEFAULT 1;

-- DD: constraints to avoid strange problems
UPDATE theme SET theme_name=lower(theme_name);
UPDATE layergroup SET layergroup_name=lower(layergroup_name);
UPDATE qtrelation SET qtrelation_name=lower(qtrelation_name);
UPDATE qtrelation SET table_name=lower(table_name);
ALTER TABLE theme ADD CONSTRAINT theme_name_lower_case CHECK (theme_name=lower(theme_name));
ALTER TABLE layergroup ADD CONSTRAINT layergroup_name_lower_case CHECK (layergroup_name=lower(layergroup_name));
ALTER TABLE qtrelation ADD CONSTRAINT qtrelation_name_lower_case CHECK (qtrelation_name=lower(qtrelation_name));
ALTER TABLE qtrelation ADD CONSTRAINT qtrelation_table_name_lower_case CHECK (table_name=lower(table_name));


update e_outputformat set outputformat_mimetype='image/png; mode=24bit' where outputformat_id=1;
update e_outputformat set outputformat_mimetype='image/png; mode=8bit' where outputformat_id=7;
update e_outputformat set outputformat_imagemode='RGB' where outputformat_id=2;

ALTER TABLE layer ADD COLUMN maxvectfeatures integer;

ALTER TABLE mapset ADD COLUMN mapset_scales character varying;
COMMENT ON COLUMN mapset.mapset_scales IS 'Possible scale list separated with comma';

-- Aggiunto per la gestione dei sistemi proiettivi con parametro
ALTER TABLE project_srs ADD COLUMN custom_srid integer;

-- FD: Aggiunto layer type CHART
DELETE FROM e_layertype WHERE layertype_id=11;
INSERT INTO e_layertype (layertype_id, layertype_name, layertype_ms) values(11, 'chart', 8);

-- DD: Aggiung campo copyright su thema
ALTER TABLE theme ADD COLUMN copyright_string character varying;

-- DD: Rendere obbligatorio i campi che servono per il calcolo del extent sul mapfile
ALTER TABLE project
   ALTER COLUMN xc SET NOT NULL;
ALTER TABLE project
   ALTER COLUMN yc SET NOT NULL;
ALTER TABLE project
   ALTER COLUMN max_extent_scale SET NOT NULL;

-- ESPLICITA IL TIPO DI GEOMETRIA
ALTER TABLE layer ADD COLUMN data_type character varying;
-- AGGIORNAMENTO UN PO GREZZO DEL CAMPO (NOMI DI TABELLE = IN SCHEMI DIVERSI
update layer set data_type=lower(type) FROM public.geometry_columns where data=f_table_name;


-- FD: multilingua
CREATE TABLE i18n_field
(
  i18nf_id serial NOT NULL,
  table_name character varying(255),
  field_name character varying(255),
  CONSTRAINT "18n_field_pkey" PRIMARY KEY (i18nf_id)
)
WITH (
  OIDS=FALSE
);


CREATE TABLE localization
(
  localization_id serial NOT NULL,
  project_name character varying NOT NULL,
  i18nf_id integer,
  pkey_id character varying NOT NULL,
  language_id character(2),
  "value" text,
  CONSTRAINT localization_pkey PRIMARY KEY (localization_id),
  CONSTRAINT i18nfield_fkey FOREIGN KEY (i18nf_id)
      REFERENCES i18n_field (i18nf_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT language_id_fkey FOREIGN KEY (language_id)
      REFERENCES e_language (language_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT localization_project_name_fkey FOREIGN KEY (project_name)
      REFERENCES project (project_name) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);

CREATE TABLE project_languages
(
  project_name character varying NOT NULL,
  language_id character(2) NOT NULL,
  CONSTRAINT project_languages_pkey PRIMARY KEY (project_name, language_id),
  CONSTRAINT language_id_project_name_fkey FOREIGN KEY (project_name)
      REFERENCES project (project_name) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);

drop table language_class;
drop table language_layer;
drop table language_layergroup;
drop table language_mapset;
drop table language_project;
drop table language_selgroup;
drop table language_theme;


alter table layer drop column language_id;
insert into e_language (language_id, language_name, language_order) values ('it', 'Italiano', 5);

INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (1, 'class', 'class_title');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (2, 'class', 'expression');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (3, 'class', 'label_def');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (4, 'class', 'class_text');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (5, 'layer', 'layer_title');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (6, 'layer', 'data_filter');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (7, 'layer', 'layer_def');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (8, 'layer', 'metadata');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (9, 'layer', 'labelitem');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (10, 'layer', 'classitem');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (11, 'layergroup', 'layergroup_title');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (12, 'layergroup', 'sld');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (13, 'qtfield', 'qtfield_name');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (14, 'qtfield', 'field_header');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (15, 'style', 'style_def');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (16, 'theme', 'theme_title');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (17, 'theme', 'copyright_string');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (18, 'mapset', 'mapset_title');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (19, 'mapset', 'mapset_description');


alter table project add column default_language_id character(2); 
update project set default_language_id = 'it';--default 'it'
ALTER TABLE project ALTER COLUMN default_language_id SET NOT NULL;

delete FROM e_form where config_file like 'language_%';

insert into e_level  (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) values (48, 'project_languages', 'project', NULL, 2, 1, 1, 1, 2, 'project_languages', 2);
insert into e_form (id, name, config_file, tab_type, level_destination, parent_level)  values (202, 'project_languages', 'project_languages', 0, 48, 2);
insert into e_form (id, name, config_file, tab_type, level_destination, parent_level)  values (203, 'project_languages', 'project_languages', 1, 48, 2);
insert into form_level values (504, 48, 0, 203, 1, 1);
insert into form_level values (505, 48, 1, 203, 1, 1);
insert into form_level values (506, 48, 2, 203, 1, 1);
insert into form_level values (507, 2, 3, 202, 1, 1);



------ GISCLIENT 3.0 -----------
---- da qui in poi, tutte le modifiche a partire dallo schema di gisclient 3.0

update e_form set tab_type=0 where id in (16,18); -- riabilita l'inserimento di utenti e gruppi di utenti

CREATE TABLE authfilter -- tabella dei filtri
(
  filter_id integer NOT NULL,
  filter_name character varying(100),
  filter_description text,
  filter_priority integer not null default 0,
  CONSTRAINT filter_pkey PRIMARY KEY (filter_id)
)
WITH (
  OIDS=FALSE
);


-- inserimenti in framework per gestione authfilter
insert into e_level  (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id)  values (49, 'authfilter', 'authfilter', 8, 1, 0, 1, 0, 1, 'authfilter', 2);
insert into e_form (id, name, config_file, tab_type, level_destination, parent_level)  values (204, 'authfilter', 'authfilter', 0, 49, 2);
insert into e_form (id, name, config_file, tab_type, level_destination, parent_level)  values (205, 'authfilter', 'authfilter', 1, 49, 2);
insert into form_level values (508, 49, 0, 205, 1, 1);
insert into form_level values (509, 49, 1, 205, 1, 1);
insert into form_level values (510, 49, 2, 205, 1, 1);
insert into form_level values (511, 1, 3, 204, 4, 1);



-- tabella di collegamento tra layer e authfilter
CREATE TABLE layer_authfilter
(
  layer_id integer NOT NULL,
  filter_id integer NOT NULL,
  required smallint DEFAULT 0,
  CONSTRAINT layer_authfilter_pkey PRIMARY KEY (layer_id, filter_id),
  CONSTRAINT layer_authfilter_filter_id_fkey FOREIGN KEY (filter_id)
      REFERENCES authfilter (filter_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT layer_authfilter_layer_id_fkey FOREIGN KEY (layer_id)
      REFERENCES layer (layer_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);


insert into e_level  (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) values (50, 'layer_authfilter', 'layer', 15, 11, 4, 1, 1, 11, 'layer_authfilter', 2);
insert into e_form (id, name, config_file, tab_type, level_destination, save_data, parent_level)  values (206, 'layer_authfilter', 'layer_authfilter', 4, 50, 'layer_authfilter', 11);
insert into e_form (id, name, config_file, tab_type, level_destination, save_data, parent_level)  values (207, 'layer_authfilter', 'layer_authfilter', 5, 50, 'layer_authfilter', 11);
insert into form_level values (512, 11, 3, 206, 8, 1);
insert into form_level values (513, 50, 1, 207, 1, 1);


-- tabella di collegamento tra gruppi e filtri, con definizione dei filtri
CREATE TABLE group_authfilter
(
  groupname character varying NOT NULL,
  filter_id integer NOT NULL,
  filter_expression character varying,
  CONSTRAINT group_authfilter_pkey PRIMARY KEY (groupname, filter_id),
  CONSTRAINT group_authfilter_filter_id_fkey FOREIGN KEY (filter_id)
      REFERENCES authfilter (filter_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT group_authfilter_gropuname_fkey FOREIGN KEY (groupname)
      REFERENCES groups (groupname) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);

-- view per la selezione degli auth filter che non sono stati ancora definiti per il gruppo corrente, con accrocchio per accontentare elenco_selectdb di tabella_v
CREATE OR REPLACE VIEW seldb_group_authfilter AS 
 SELECT authfilter.filter_id AS id, authfilter.filter_name AS opzione, 
        CASE
            WHEN group_authfilter.groupname IS NULL THEN ''::character varying
            ELSE group_authfilter.groupname
        END AS groupname
   FROM authfilter
   LEFT JOIN group_authfilter USING (filter_id);
   

-- view degli authfilters per gruppo
CREATE OR REPLACE VIEW vista_group_authfilter AS 
 SELECT af.filter_id, af.filter_name, gaf.filter_expression, gaf.groupname
   FROM authfilter af
   JOIN group_authfilter gaf USING (filter_id)
  ORDER BY af.filter_name;


insert into e_level  (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) values (51, 'group_authfilter', 'groups', 1, 3, 1, 1, 1, 3, 'group_authfilter', 2);
insert into e_form (id, name, config_file, tab_type, level_destination, parent_level)  values (208, 'group_authfilter', 'group_authfilter', 0, 51, 3);
insert into e_form (id, name, config_file, tab_type, level_destination, parent_level)  values (209, 'group_authfilter', 'group_authfilter', 1, 51, 3);

insert into form_level values (514, 3, 3, 208, 3, 1);
insert into form_level values (515, 51, 0, 209, 1, 1);
insert into form_level values (516, 51, 1, 209, 1, 1);
insert into form_level values (517, 51, 2, 209, 1, 1);


-- UN PO' DI PULIZIA
ALTER TABLE "class" DROP COLUMN class_link;
ALTER TABLE layer DROP COLUMN requires;
ALTER TABLE layer DROP COLUMN labelrequires;
ALTER TABLE layergroup DROP COLUMN attribution;
ALTER TABLE layergroup DROP COLUMN layergroup_link;
--ALTER TABLE layergroup DROP COLUMN layer_default;
ALTER TABLE mapset DROP COLUMN legend_icon_w;
ALTER TABLE mapset DROP COLUMN legend_icon_h;
ALTER TABLE mapset DROP COLUMN geocoord;
ALTER TABLE mapset DROP COLUMN outputformat_id;
ALTER TABLE mapset DROP COLUMN interlace;
ALTER TABLE mapset DROP COLUMN readline_color;
ALTER TABLE theme DROP COLUMN theme_link;

DROP TABLE mapset_groups CASCADE;
DROP TABLE mapset_link CASCADE;
DROP TABLE mapset_qt CASCADE;

delete FROM e_form cascade where config_file='mapset_link';
delete FROM e_form cascade where config_file='mapset_qt';
delete FROM e_form cascade where config_file='qt_links';

update e_form set save_data=null where config_file='project';
update e_form set save_data=null where config_file='mapset_layergroup';
update e_form set save_data=null where config_file='layergroup';
update e_form set save_data=null where config_file='admin_project';

ALTER TABLE qtfield ADD COLUMN editable numeric(1,0) DEFAULT 0;


--AGGIUNTO IL CAMPO FORMULA
ALTER TABLE qtfield ADD COLUMN formula character varying;
UPDATE qtfield SET formula=qtfield_name WHERE qtfield_name LIKE '%(%' OR qtfield_name LIKE '%::%' OR qtfield_name LIKE '%||%';
UPDATE qtfield SET qtfield_name = 'formula_'||qtfield_id WHERE qtfield_name LIKE '%(%' OR qtfield_name LIKE '%::%' OR qtfield_name LIKE '%||%';
--UNICITA' DI FIELDNAME SU LAYER
ALTER TABLE qtfield ADD CONSTRAINT qtfield_qtfield_name_layer_id_key UNIQUE(qtfield_name, layer_id);

--AUTORIZZAZIONI SUI CAMPI
CREATE TABLE qtfield_groups
(
  qtfield_id integer NOT NULL,
  groupname character varying NOT NULL,
  editable numeric(1,0) DEFAULT 0,
  CONSTRAINT qtfield_groups_pkey PRIMARY KEY (qtfield_id, groupname),
  CONSTRAINT qtfield_groups_qtfield_id_fkey FOREIGN KEY (qtfield_id)
      REFERENCES qtfield (qtfield_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE
);

insert into e_level  (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) values (52, 'qtfield_groups', 'qtfield', 1, 17, 5, 1, 0, 17, 'qtfield_groups', 2);
insert into e_form (id, name, config_file, tab_type, level_destination, save_data, parent_level)  values (210, 'qtfield_groups', 'qtfield_groups', 4, 52, 'qtfield_groups', 17);
insert into e_form (id, name, config_file, tab_type, level_destination, save_data, parent_level)  values (211, 'qtfield_groups', 'qtfield_groups', 5, 52, 'qtfield_groups', 17);
insert into e_form (id, name, config_file, tab_type, level_destination, save_data, parent_level)  values (212, 'qtfield_groups', 'qtfield_groups', 0, 52, 'qtfield_groups', 17);
insert into form_level values (518, 17, 0, 210, 1, 1);
insert into form_level values (519, 52, 1, 211, 1, 1);



--PER FINIRE SETTIAMO I VINCOLI SUI NOMI
update theme set theme_name=replace(theme_name,' ','_');
update layergroup set layergroup_name=replace(layergroup_name,' ','_');
update layer set layer_name=replace(layer_name,' ','_');



--AGGIUNTO CAMPO DISPLAYPROJECTION
ALTER TABLE mapset ADD COLUMN displayprojection integer;
--Data di aggiornamento del layer
ALTER TABLE layer ADD COLUMN last_update character varying;

-- campo files_path per data manager
alter table catalog add column files_path character varying;


-- ************** CONTEXT *****************************
CREATE TABLE usercontext
(
  usercontext_id serial,
  username character varying NOT NULL,
  mapset_name character varying NOT NULL,
  title character varying NOT NULL,
  context text,
  CONSTRAINT usercontext_pkey PRIMARY KEY (usercontext_id)
);

--ELIMINO LA TABELLA QT_SELGROUP PERCHE' E' CAMBIATA LA FUNZIONE
-- ATTENZIONE: migrare i gruppi, se presenti nel progetto su gisclient 2
DROP TABLE qt_selgroup;
CREATE TABLE selgroup_layergroup
(
  selgroup_id integer NOT NULL,
  layergroup_id integer NOT NULL,
  CONSTRAINT selgroup_layergroup_pkey PRIMARY KEY (layergroup_id, selgroup_id),
  CONSTRAINT selgroup_layergroup_layergroup_id_fkey FOREIGN KEY (layergroup_id)
      REFERENCES layergroup (layergroup_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT selgroup_layergroup_selgroup_fkey FOREIGN KEY (selgroup_id)
      REFERENCES selgroup (selgroup_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE
);
update e_level set name = 'selgroup_layergroup', "table" = 'selgroup_layergroup' where name = 'qt_selgroup';




--SPOSTO I LINK SUL LAYER COME PER I CAMPI (qtfield)
ALTER TABLE qt_link ADD COLUMN layer_id integer;
UPDATE qt_link set layer_id=qt.layer_id FROM qt WHERE qt.qt_id=qt_link.qt_id;
ALTER TABLE qt_link DROP COLUMN qt_id CASCADE;

ALTER TABLE qt_link RENAME TO qtlink;
ALTER TABLE qtlink ADD CONSTRAINT qtlink_pkey PRIMARY KEY(layer_id, link_id);
ALTER TABLE qtlink ADD CONSTRAINT qtlink_layer_id_fkey FOREIGN KEY (layer_id)
      REFERENCES layer (layer_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE qtlink ADD CONSTRAINT qtlink_link_id_fkey FOREIGN KEY (link_id)
      REFERENCES link (link_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE;
	  
-- delete FROM e_level where id=19;
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (19, 'qtlink', 'layer', 12, 11, 4, 1, 0, 11, 'qtlink', 2);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (66, 'qtlink', 'qtlink', 2, 19, NULL, 'qtlink', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (67, 'qtlink', 'qtlink', 0, 19, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (68, 'qtlink', 'qtlink', 1, 19, NULL, 'qtlink', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (69, 'qtlink', 'qtlink', 110, 19, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (53, 11, 3, 66, 6, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (60, 19, 0, 67, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (61, 19, 1, 68, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (62, 19, 1, 69, 2, 1);



ALTER TABLE layer ADD COLUMN data_extent character varying;

--RIPRISTINO MAPSET SRID DA COMBO
ALTER TABLE project  ALTER COLUMN project_srid SET NOT NULL;
CREATE OR REPLACE VIEW seldb_mapset_srid AS 
         SELECT project.project_srid AS id, project.project_srid AS opzione, project.project_name
           FROM project
UNION 
         SELECT project_srs.srid AS id, project_srs.srid AS opzione, project_srs.project_name
           FROM project_srs
          WHERE NOT (project_srs.project_name::text || project_srs.srid IN ( SELECT project.project_name::text || project.project_srid
                   FROM project))
  ORDER BY 1;
  
--SPOSTATO ENCODINGS SU PROGETTO
ALTER TABLE theme DROP COLUMN charset_encodings_id;
ALTER TABLE project ADD COLUMN charset_encodings_id integer;


-- 2011-11-24: AGGIUNTO resultype_id alla vista vista_qtfield per visualizzare il campo in elenco 
DROP VIEW vista_qtfield;

CREATE OR REPLACE VIEW vista_qtfield AS 
 SELECT qtfield.qtfield_id, qtfield.layer_id, qtfield.fieldtype_id, x.qtrelation_id, qtfield.qtfield_name, qtfield.resultype_id, qtfield.field_header, qtfield.qtfield_order, COALESCE(qtfield.column_width, 0) AS column_width, x.name AS qtrelation_name, x.qtrelationtype_id, x.qtrelationtype_name
   FROM qtfield
   JOIN e_fieldtype USING (fieldtype_id)
   JOIN ( SELECT y.qtrelationtype_id, y.qtrelation_id, y.name, z.qtrelationtype_name
      FROM (         SELECT 0 AS qtrelation_id, 'Data Layer' AS name, 0 AS qtrelationtype_id
           UNION 
                    SELECT qtrelation.qtrelation_id, COALESCE(qtrelation.qtrelation_name, 'Nessuna Relazione'::character varying) AS name, qtrelation.qtrelationtype_id
                      FROM qtrelation) y
   JOIN (         SELECT 0 AS qtrelationtype_id, '' AS qtrelationtype_name
           UNION 
                    SELECT e_qtrelationtype.qtrelationtype_id, e_qtrelationtype.qtrelationtype_name
                      FROM e_qtrelationtype) z USING (qtrelationtype_id)) x USING (qtrelation_id)
  ORDER BY qtfield.qtfield_id, x.qtrelation_id, x.qtrelationtype_id;


--2011-12-14: modifica outputformat da jpg a jpeg
update e_outputformat set outputformat_mimetype = 'image/jpeg' where outputformat_mimetype = 'image/jpg';

-- 2011-12-20: rimuove not null di project_extent
ALTER TABLE project ALTER COLUMN project_extent DROP NOT NULL;

-- 2011-12-20: bugfix gruppo di utenti
UPDATE e_form SET tab_type=0 WHERE id=20;
UPDATE e_form SET tab_type=50 WHERE id=18;


-- 2011-12-20: gestione gruppi di interrogazione
DROP TABLE selgroup_layergroup;
CREATE TABLE selgroup_layer
(
  selgroup_id integer NOT NULL,
  layer_id integer NOT NULL,
  CONSTRAINT selgroup_layer_pkey PRIMARY KEY (layer_id, selgroup_id),
  CONSTRAINT selgroup_layer_layer_id_fkey FOREIGN KEY (layer_id)
      REFERENCES layer (layer_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT selgroup_layer_selgroup_fkey FOREIGN KEY (selgroup_id)
      REFERENCES selgroup (selgroup_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);
UPDATE e_level SET name='selgroup_layer',"table"='selgroup_layer' WHERE id=28;
insert into e_form(id,name,config_file,tab_type,level_destination,save_data,parent_level) values(213,'selgroup_layer','selgroup_layer',4,28,'selgroup_layer',27);
insert into e_form(id,name,config_file,tab_type,level_destination,save_data,parent_level) values(214,'selgroup_layer','selgroup_layer',5,28,'selgroup_layer',27);
insert into form_level(id,level,mode,form,order_fld,visible) values(520,27,3,213,1,1);
insert into form_level(id,level,mode,form,order_fld,visible) values(521,28,1,214,1,1);


-- 2011-12-23: opzioni di visualizzazione campo
DELETE FROM e_resultype;
INSERT INTO e_resultype VALUES (1, 'Mostra sempre', 1);
INSERT INTO e_resultype VALUES (4, 'Nascondi', 2);
INSERT INTO e_resultype VALUES (5, 'Ignora', 3);
INSERT INTO e_resultype VALUES (10, 'Nascondi in tabella', 4);
INSERT INTO e_resultype VALUES (20, 'Nascondi in tooltip', 5);
INSERT INTO e_resultype VALUES (30, 'Nascondi in scheda', 6);

------ GISCLIENT 3.1 -----------
---- da qui in poi, tutte le modifiche a partire dallo schema di gisclient 3.1
SET search_path = gisclient_32, pg_catalog;


-- 2012-01-09: metadata_url per collegamento con geonetwork, trasparenza per gestirla su client
alter table layergroup add column metadata_url character varying;
alter table layergroup add column opacity character varying default 100;


-- 2012-01-16: per la copia, le tabelle child devono avere un campo id
alter table layer_groups drop constraint layer_groups_pkey;
create sequence layer_groups_seq;
alter table layer_groups add column layer_groups_id integer;
alter table layer_groups alter column layer_groups_id set default nextval('layer_groups_seq');
update layer_groups set layer_groups_id = nextval('layer_groups_seq');
ALTER TABLE layer_groups ADD PRIMARY KEY (layer_groups_id);

-- 2012-01-16: searchable su layer: se no, il layer � interrogabile ma non compare nei modelli di ricerca
alter table layer add column searchable numeric(1,0) DEFAULT 0;
update layer set searchable=1 where queryable=1;

-- 2012-01-17: salva le opzioni utente sul database
create table users_options (
	users_options_id serial,
	username character varying NOT NULL,
	option_key character varying NOT NULL,
	option_value character varying NOT NULL,
	CONSTRAINT users_options_pkey PRIMARY KEY(users_options_id)
);
-- TODO: rivedere user, per mettere una fkey sulla username bisogna inserire l'admin in users

------ GISCLIENT 3.2 -----------

-- 2012-01-20: lookup per editing
alter table qtfield add column lookup_table character varying;
alter table qtfield add column lookup_id character varying;
alter table qtfield add column lookup_name character varying;


-- 2012-11-27: campo per filtro a cascata
alter table qtfield add column filter_field_name character varying;
insert into e_searchtype (searchtype_id, searchtype_name) values (6, 'Lista di valori, non WFS');

-- 2012-12-03: aggiorna la view vista_qtfield per permettere la visualizzazione del campo "editable" nei tab
DROP VIEW vista_qtfield;

CREATE OR REPLACE VIEW vista_qtfield AS 
 SELECT qtfield.qtfield_id, qtfield.layer_id, qtfield.fieldtype_id, x.qtrelation_id, qtfield.qtfield_name, qtfield.resultype_id, qtfield.field_header, qtfield.qtfield_order, COALESCE(qtfield.column_width, 0) AS column_width, x.name AS qtrelation_name, x.qtrelationtype_id, x.qtrelationtype_name,qtfield.editable
   FROM qtfield
   JOIN e_fieldtype USING (fieldtype_id)
   JOIN ( SELECT y.qtrelationtype_id, y.qtrelation_id, y.name, z.qtrelationtype_name
      FROM (         SELECT 0 AS qtrelation_id, 'Data Layer'::character varying AS name, 0 AS qtrelationtype_id
           UNION 
                    SELECT qtrelation.qtrelation_id, COALESCE(qtrelation.qtrelation_name, 'Nessuna Relazione'::character varying) AS name, qtrelation.qtrelationtype_id
                      FROM qtrelation) y
   JOIN (         SELECT 0 AS qtrelationtype_id, ''::character varying AS qtrelationtype_name
           UNION 
                    SELECT e_qtrelationtype.qtrelationtype_id, e_qtrelationtype.qtrelationtype_name
                      FROM e_qtrelationtype) z USING (qtrelationtype_id)) x USING (qtrelation_id)
  ORDER BY qtfield.qtfield_id, x.qtrelation_id, x.qtrelationtype_id;

-- 2013-01-08: crea la view vista_project_languages per permettere la visualizzazione del campo "lingua" nei tab  
  CREATE OR REPLACE VIEW vista_project_languages AS 
 SELECT project_languages.project_name, project_languages.language_id, e_language.language_name, e_language.language_order
   FROM project_languages
   JOIN e_language ON project_languages.language_id = e_language.language_id
  ORDER BY e_language.language_order;
  
  -- 2013-01-11: inserisce outputformat AGG PNG default
	  INSERT INTO e_outputformat VALUES (9,'AGG PNG','AGG/PNG','image/png','RGB','png','    FORMATOPTION "QUANTIZE_FORCE=ON"
		FORMATOPTION "QUANTIZE_DITHER=OFF"
		FORMATOPTION "QUANTIZE_COLORS=256"',NULL);

 --2013-02-04: ordina i cataloghi per nome
 CREATE OR REPLACE VIEW seldb_catalog AS 
         SELECT (-1) AS id, 'Seleziona ====>'::character varying AS opzione, '0'::character varying AS project_name
UNION ALL 
         SELECT foo.id, foo.opzione, foo.project_name
           FROM ( SELECT catalog.catalog_id AS id, catalog.catalog_name AS opzione, catalog.project_name
                   FROM catalog
                  ORDER BY catalog.catalog_name) foo;
				  
--2013-10-04: campo per specificare se la geometria dev'essere nascosta nell'interrogazione (per esempio nel caso di interrogazione dei comuni che vanno a coprire inutilmente tutti gli altri oggetti interrogati)
alter table layer add column hide_vector_geom numeric(1,0) default 0;

--2013-10-15: metto fieldtype File per gestire gli upload e i link in interrogazione
update e_fieldtype set fieldtype_name = 'File' where fieldtype_id = 10;
update e_fieldtype set fieldtype_name = 'Immagine' where fieldtype_id = 8;

insert into e_datatype (datatype_id, datatype_name) values (10, 'Immagine'), (15, 'File');


--2013-10-17: postlabelcache di default FALSE
ALTER TABLE layer ALTER COLUMN postlabelcache SET DEFAULT 0;
update layer set postlabelcache = 0;

--2013-11-15: tile origin su layergroup
alter table layergroup add column tile_origin TEXT;

--2013-11-19: server resolutions su layergroup per TMS
alter table layergroup add column tile_resolutions TEXT;

--2014-03-17: ordina i font per nome
CREATE OR REPLACE VIEW seldb_font AS 
         SELECT foo.id, foo.opzione
   FROM (         SELECT ''::character varying AS id, 'Seleziona ====>'::character varying AS opzione
        UNION 
                 SELECT font.font_name AS id, font.font_name AS opzione
                   FROM font) foo
  ORDER BY foo.id;

--2014-03-17: ordina gli outputformat per nome
CREATE OR REPLACE VIEW seldb_outputformat AS 
 SELECT e_outputformat.outputformat_id AS id, e_outputformat.outputformat_name AS opzione
   FROM e_outputformat
  ORDER BY e_outputformat.outputformat_name;

ALTER TABLE seldb_outputformat
  OWNER TO gisclient;
  
--2014-03-17: ordina labelposition per nome
  CREATE OR REPLACE VIEW seldb_lblposition AS 
        SELECT '1'::character varying AS id, 'Seleziona ====>'::character varying AS opzione
                  UNION all
                 SELECT e_lblposition.lblposition_name AS id, e_lblposition.lblposition_name AS opzione FROM e_lblposition where lblposition_name='AUTO'
                 UNION ALL
			SELECT foo.id, foo.opzione FROM (                
                 SELECT e_lblposition.lblposition_name AS id, e_lblposition.lblposition_name AS opzione FROM e_lblposition where lblposition_name!='AUTO'
          ORDER BY e_lblposition.lblposition_order) foo;

ALTER TABLE seldb_font
  OWNER TO gisclient;
  
 --2014-04-16 Finalmente un po di pulizia
alter table layer drop column if exists mapset_filter_ ;
alter table layer drop column if exists locked_ ;
alter table layer drop column if exists static_ ;
alter table layer drop column if exists visible ;
alter table layergroup drop column if exists layer_default ;

--2014-04-17 Aggiunta della versione. DA QUESTO MOMENTO IN POI ESEGUIRE L'INSERT DELLA VERSIONE
CREATE TABLE version
(
  version_id serial NOT NULL,
  version_name character varying NOT NULL,
  version_date date NOT NULL,
  CONSTRAINT version_pkey PRIMARY KEY (version_id )
)
WITH (
  OIDS=FALSE
);

create view vista_version as
select version_id,version_name,version_date FROM version order by version_id desc limit 1;

INSERT INTO version (version_name,version_date) values ('3.2.15','2014-04-17');


-- 2014-05-22 AGGIUNGE CONTROLLO: non possono esistere due campi con lo stesso nome nello stesso layer
-- ELIMINA I campi doppi
DROP INDEX IF EXISTS qtfield_name_unique;
CREATE UNIQUE INDEX qtfield_name_unique
   ON qtfield (layer_id,qtfield_name);
   
   --!!! Se fallisce, eseguire la query di pulizia campi doppi:
        /* delete FROM qtfield where qtfield_id in (select max(qtfield_id) as qtfield_id FROM qtfield where qtfield_name IN
        (select distinct qtfield_name FROM qtfield
        group by qtfield_name,layer_id having count(*) > 1)
        and layer_id in 
        (select distinct layer_id FROM qtfield
        group by qtfield_name,layer_id having count(*) > 1)
        group by layer_id,qtfield_name) */

INSERT INTO version (version_name,version_date) values ('3.2.16','2014-05-22');
   
-- TRIGGER per impostare automaticamente enc_pwd e nascondere quella in chiaro
update users set enc_pwd = md5(pwd) where coalesce(pwd,'') <> '';
update users set pwd = NULL;

CREATE OR REPLACE FUNCTION enc_pwd()
  RETURNS trigger AS
$BODY$
BEGIN
	if (coalesce(new.pwd,'')<>'') then
		new.enc_pwd:=md5(new.pwd);
		new.pwd = null;
	end if;
	return new;
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION enc_pwd()
  OWNER TO gisclient;

DROP TRIGGER if exists set_encpwd ON users;
CREATE TRIGGER set_encpwd
  BEFORE INSERT OR UPDATE
  ON users
  FOR EACH ROW
  EXECUTE PROCEDURE enc_pwd();
  
INSERT INTO version (version_name,version_date) values ('3.2.17','2014-05-22');


--2014-07-02: View bug & trigger bug
CREATE OR REPLACE VIEW seldb_lblposition AS 
SELECT ''::character varying AS id, 'Seleziona ====>'::character varying AS opzione
UNION ALL
SELECT e_lblposition.lblposition_name AS id, e_lblposition.lblposition_name AS opzione FROM e_lblposition where lblposition_name='AUTO'
UNION ALL
SELECT foo.id, foo.opzione FROM (                
    SELECT e_lblposition.lblposition_name AS id, e_lblposition.lblposition_name AS opzione FROM e_lblposition WHERE lblposition_name!='AUTO' ORDER BY e_lblposition.lblposition_order
) AS foo;

ALTER TABLE seldb_lblposition OWNER TO gisclient;
  
CREATE OR REPLACE FUNCTION set_layer_name() RETURNS "trigger" AS
$BODY$
BEGIN
	SELECT INTO NEW.layer_name layer_name FROM gisclient_32.layer WHERE layer_id=NEW.layer_id;
	RETURN NEW;
END
$BODY$
LANGUAGE 'plpgsql' VOLATILE;

ALTER TABLE mapset alter COLUMN private set DEFAULT 0;
  
INSERT INTO version (version_name,version_date) values ('3.2.18','2014-07-02');

-- MS6:
ALTER TABLE layer DROP CONSTRAINT layer_layergroup_id_fkey;
ALTER TABLE layer ADD CONSTRAINT layer_layergroup_id_fkey FOREIGN KEY (layergroup_id) 
    REFERENCES layergroup (layergroup_id) MATCH FULL 
	ON UPDATE CASCADE ON DELETE CASCADE; 

-- *********** SIMBOLOGIA LINEARE: SOSTITUZIONE DI STYLE CON PATTERN *********************
CREATE TABLE e_pattern
(
  pattern_id serial NOT NULL,
  pattern_name character varying NOT NULL,
  pattern_def character varying NOT NULL,
  pattern_order smallint,
  CONSTRAINT e_pattern_pkey PRIMARY KEY (pattern_id )
);
ALTER TABLE style ADD COLUMN pattern_id integer;

ALTER TABLE style  ADD CONSTRAINT pattern_id_fkey FOREIGN KEY (pattern_id)
      REFERENCES e_pattern (pattern_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE NO ACTION;
	  
CREATE INDEX fki_pattern_id_fkey ON style USING btree (pattern_id );

CREATE OR REPLACE VIEW seldb_pattern AS 
         SELECT (-1) AS id, 'Seleziona ====>' AS opzione
UNION ALL 
         SELECT pattern_id AS id, pattern_name AS opzione
           FROM e_pattern;

--UPGRADE DELLA TABELLA DEI SIMBOLI		   
ALTER TABLE symbol ADD COLUMN symbol_type character varying;
ALTER TABLE symbol ADD COLUMN font_name character varying;
ALTER TABLE symbol ADD COLUMN ascii_code integer;
ALTER TABLE symbol ADD COLUMN filled numeric(1,0) DEFAULT 0;
ALTER TABLE symbol ADD COLUMN points character varying;
ALTER TABLE symbol ADD COLUMN image character varying;

ALTER TABLE version ADD COLUMN version_key varchar;
UPDATE version SET version_key='author';
ALTER TABLE version ALTER COLUMN version_key SET NOT NULL;

INSERT INTO version (version_name,version_key, version_date) values ('3.2.19', 'author', '2014-07-14');

--2014-08-19: ordina tipi layer
CREATE OR REPLACE VIEW seldb_layertype AS 
         SELECT foo.id, foo.opzione
   FROM (         SELECT (-1) AS id, 'Seleziona ====>'::character varying AS opzione
        UNION 
                 SELECT e_layertype.layertype_id AS id, e_layertype.layertype_name AS opzione
                   FROM e_layertype) foo
  ORDER BY foo.id;
  
--2014-08-19: ordina tipi di misure
CREATE OR REPLACE VIEW seldb_sizeunits AS 
         SELECT foo.id, foo.opzione
   FROM (         SELECT (-1)::SMALLINT  AS id, 'Seleziona ====>'::character varying AS opzione
        UNION 
                 SELECT e_sizeunits.sizeunits_id AS id, e_sizeunits.sizeunits_name AS opzione
                   FROM e_sizeunits) foo
  ORDER BY foo.id;
  
--2014-08-19: ordina charset_encodings
CREATE OR REPLACE VIEW seldb_charset_encodings AS 
         SELECT foo.id, foo.opzione, foo.option_order
   FROM (         SELECT (-1)  AS id, 'Seleziona ====>'::character varying AS opzione, 0::SMALLINT as option_order
        UNION 
                 SELECT e_charset_encodings.charset_encodings_id AS id, e_charset_encodings.charset_encodings_name AS opzione, e_charset_encodings.charset_encodings_order AS option_order
                   FROM e_charset_encodings) foo
  ORDER BY foo.id;
  
--2014-08-19: ordina tipi di misure
CREATE OR REPLACE VIEW seldb_outputformat AS 
         SELECT foo.id, foo.opzione
   FROM (         SELECT (-1)::SMALLINT  AS id, 'Seleziona ====>'::character varying AS opzione
        UNION 
                 SELECT e_outputformat.outputformat_id AS id, e_outputformat.outputformat_name AS opzione
                   FROM e_outputformat) foo
  ORDER BY foo.id;
 
 --2014-08-19: ordina tipi di outputformat 
  DROP VIEW  seldb_outputformat;
CREATE OR REPLACE VIEW seldb_outputformat AS 
         SELECT foo.id, foo.opzione
   FROM (        SELECT e_outputformat.outputformat_id AS id, e_outputformat.outputformat_name AS opzione
                   FROM e_outputformat where outputformat_id = 7
        UNION ALL
                 SELECT e_outputformat.outputformat_id AS id, e_outputformat.outputformat_name AS opzione
                   FROM e_outputformat where outputformat_id = 1
        UNION ALL
                 SELECT e_outputformat.outputformat_id AS id, e_outputformat.outputformat_name AS opzione
                   FROM e_outputformat where outputformat_id = 9
        UNION ALL
                 SELECT e_outputformat.outputformat_id AS id, e_outputformat.outputformat_name AS opzione
                   FROM e_outputformat where outputformat_id = 3
        UNION ALL
                 SELECT e_outputformat.outputformat_id AS id, e_outputformat.outputformat_name AS opzione
                   FROM e_outputformat where outputformat_id not in (1,3,7,9)) foo;
  
   INSERT INTO version (version_name,version_key, version_date) values ('3.2.20', 'author', '2014-08-19');
   
-- 2014-08-20: support of WMTS layergroup
INSERT INTO e_owstype VALUES (9, 'WMTS', 9) ;
ALTER TABLE layergroup
  ADD COLUMN tile_matrix_set character varying;


INSERT INTO version (version_name,version_key, version_date) values ('3.2.21', 'author', '2014-08-20');

CREATE OR REPLACE VIEW vista_version AS 
 SELECT version.version_id, version.version_name, version.version_date
   FROM version
  WHERE version_key='author'
  ORDER BY version.version_id DESC
 LIMIT 1;

INSERT INTO version (version_name,version_key, version_date) values ('3.2.22', 'author', '2014-08-26');

-- 2014-10-01: crea la vista_layer, utile a sapere se un layer è interrogabile e/o editabile
DROP VIEW IF EXISTS vista_layer;
CREATE OR REPLACE VIEW vista_layer AS 
 SELECT l.*, 
        CASE
          WHEN queryable = 1 and l.hidden = 0 and 
               layer_id IN (SELECT qtfield.layer_id 
                              FROM qtfield 
                              WHERE qtfield.resultype_id != 4)
          THEN 'SI. Config. OK'
          WHEN queryable = 1 and l.hidden = 1 and
               layer_id IN (SELECT qtfield.layer_id 
                              FROM qtfield 
                              WHERE qtfield.resultype_id != 4)
          THEN 'SI. Ma è nascosto'
          WHEN queryable = 1 and 
               layer_id IN (SELECT qtfield.layer_id 
                              FROM qtfield 
                              WHERE qtfield.resultype_id = 4)
          THEN 'NO. Nessun campo nei risultati'
          ELSE 'NO. WFS non abilitato'
        END AS is_queryable, 
        CASE
            WHEN queryable = 1 and layer_id IN ( SELECT qtfield.layer_id
               FROM qtfield
              WHERE qtfield.editable = 1)
            THEN 'SI. Config. OK' 
            WHEN queryable = 1 and layer_id IN ( SELECT qtfield.layer_id
               FROM qtfield
              WHERE qtfield.editable = 0)
            THEN 'NO. Nessun campo è editabile' 
            WHEN queryable = 0 and layer_id IN ( SELECT qtfield.layer_id
               FROM qtfield
              WHERE qtfield.editable = 1)
            THEN 'NO. Esiste un campo editabile ma il WFS non è attivo' 
            ELSE 'NO.'
        END AS is_editable,
        CASE
            WHEN connection_type != 6 then '(i) Controllo non possibile: connessione non PostGIS'
            WHEN substring(c.catalog_path,0,position('/' in c.catalog_path)) != current_database() then '(i) Controllo non possibile: DB diverso'
            WHEN data not in (select table_name FROM information_schema.tables where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)))  THEN '(!) La tabella non esiste nel DB'
            when data_geom not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = data and data_type = 'USER-DEFINED') then '(!) Il campo geometrico del layer non esiste'
            when data_unique not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = data) then '(!) Il campo chiave del layer non esiste'
            when data_srid not in (select srid FROM public.geometry_columns where f_table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and f_table_name=data) then '(!) Lo SRID configurato non è quello corretto'
            when upper(data_type) not in (select type FROM public.geometry_columns where f_table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and f_table_name=data) then '(!) Geometrytype non corretto'
            WHEN labelitem not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = data) then '(!) Il campo etichetta del layer non esiste'
            WHEN labelitem not in (select qtfield_name FROM qtfield where layer_id = l.layer_id) then '(!) Campo etichetta non presente nei campi del layer'
            WHEN labelsizeitem not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = data) then '(!) Il campo altezza etichetta del layer non esiste'
            WHEN labelsizeitem not in (select qtfield_name FROM qtfield where layer_id = l.layer_id) then '(!) Campo altezza etichetta non presente nei campi del layer'
            WHEN layer_name in (select distinct layer_name FROM layer where layergroup_id != lg.layergroup_id and catalog_id in (select catalog_id FROM catalog where project_name = c.project_name)) THEN '(!) Combinazione nome layergroup + nome layer non univoca. Cambiare nome al layer o al layergroup'
            WHEN layer_id not in (select layer_id FROM class) then 'OK (i) Non ci sono classi configurate in questo layer'
            ELSE 'OK'
          END as layer_control
   FROM layer l
JOIN catalog c using (catalog_id)
JOIN e_layertype using (layertype_id)
JOIN layergroup lg using (layergroup_id)
JOIN theme t using (theme_id);

ALTER TABLE vista_layer
 OWNER TO gisclient;  
INSERT INTO version (version_name,version_key, version_date) values ('3.2.23', 'author', '2014-10-01');

-- 2014-10-09: ricrea la vista_qtfield, utile a sapere se un campo è configurato correttamente


CREATE OR REPLACE VIEW vista_qtfield AS 
 SELECT qtfield.qtfield_id, qtfield.layer_id, qtfield.fieldtype_id, x.qtrelation_id, qtfield.qtfield_name, qtfield.resultype_id, qtfield.field_header, qtfield.qtfield_order, COALESCE(qtfield.column_width, 0) AS column_width, x.name AS qtrelation_name, x.qtrelationtype_id, x.qtrelationtype_name, qtfield.editable,
   CASE 
  WHEN qtrelation_id = 0 THEN
  (CASE 
    WHEN c.connection_type != 6 then '(i) Controllo non possibile: connessione non PostGIS'
    WHEN substring(c.catalog_path,0,position('/' in c.catalog_path)) != current_database() then '(i) Controllo non possibile: DB diverso'
    WHEN qtfield_name NOT IN (select column_name FROM information_schema.columns where substring(c.catalog_path,position('/' in c.catalog_path)+1,length(c.catalog_path))=i.table_schema and l.data=i.table_name) then '(!) Il campo non esiste nella tabella'
    ELSE 'OK'
  END)
  ELSE 
  (CASE
    WHEN cr.connection_type != 6 then '(i) Controllo non possibile: connessione non PostGIS'
    WHEN substring(cr.catalog_path,0,position('/' in cr.catalog_path)) != current_database() then '(i) Controllo non possibile: DB diverso'
    WHEN qtfield_name NOT IN (select column_name FROM information_schema.columns where substring(cr.catalog_path,position('/' in cr.catalog_path)+1,length(cr.catalog_path))=i.table_schema and r.table_name=i.table_name) then '(!) Il campo non esiste nella tabella di relazione: '||qtrelation_name
    ELSE 'OK'
  END)
END as qtfield_control
   FROM qtfield
   JOIN e_fieldtype USING (fieldtype_id)
   JOIN ( SELECT y.qtrelationtype_id, y.qtrelation_id, y.name, z.qtrelationtype_name
      FROM (         SELECT 0 AS qtrelation_id, 'Data Layer'::character varying AS name, 0 AS qtrelationtype_id
           UNION 
                    SELECT qtrelation.qtrelation_id, COALESCE(qtrelation.qtrelation_name, 'Nessuna Relazione'::character varying) AS name, qtrelation.qtrelationtype_id
                      FROM qtrelation) y
   JOIN (         SELECT 0 AS qtrelationtype_id, ''::character varying AS qtrelationtype_name
           UNION 
                    SELECT e_qtrelationtype.qtrelationtype_id, e_qtrelationtype.qtrelationtype_name
                      FROM e_qtrelationtype) z USING (qtrelationtype_id)) x USING (qtrelation_id)
  JOIN layer l using (layer_id)
join catalog c using (catalog_id)
lEFT join qtrelation r using (qtrelation_id)
LEFT JOIN catalog cr ON cr.catalog_id=r.catalog_id
LEFT JOIN information_schema.columns i on qtfield_name=i.column_name and substring(c.catalog_path,position('/' in c.catalog_path)+1,length(c.catalog_path))=i.table_schema AND (l.data=i.table_name OR r.table_name=i.table_name) 
  ORDER BY qtfield.qtfield_id, x.qtrelation_id, x.qtrelationtype_id;

ALTER TABLE vista_qtfield
  OWNER TO gisclient;
  
INSERT INTO version (version_name,version_key, version_date) values ('3.2.24', 'author', '2014-10-09');
  
-- CREA VIEW vista_qtrelation e vista_class;

create or replace view vista_qtrelation as 
select r.qtrelation_id, r.catalog_id, r.qtrelation_name, r.qtrelationtype_id, r.data_field_1, r.data_field_2, r.data_field_3, r.table_name, r.table_field_1, r.table_field_2, r.table_field_3, r.language_id, r.layer_id,
CASE
  WHEN connection_type != 6 then '(i) Controllo non possibile: connessione non PostGIS'
  WHEN substring(c.catalog_path,0,position('/' in c.catalog_path)) != current_database() then '(i) Controllo non possibile: DB diverso'
  WHEN layer_name not in (select table_name FROM information_schema.tables where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path))) then '(!) La tabella DB del layer non esiste'
  WHEN table_name not in (select table_name FROM information_schema.tables where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path))) then '(!) tabella DB di JOIN non esiste'
  WHEN data_field_1 is null or table_field_1 is null then '(!) Uno dei campi della JOIN 1 è vuoto'
  WHEN data_field_1 not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = layer_name) then '(!) Il campo chiave layer non esiste'
  WHEN table_field_1 not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = r.table_name) then '(!) Il campo chiave della relazione non esiste'
  WHEN data_field_2 is null AND table_field_2 is null then 'OK'
  WHEN data_field_2 is null or table_field_2 is null then '(!) Uno dei campi della JOIN 2 è vuoto'
  WHEN data_field_2 not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = layer_name) then '(!) Il campo chiave layer della JOIN 2 non esiste'
  WHEN table_field_2 not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = r.table_name) then '(!) Il campo chiave relazione della JOIN 2 non esiste'
  WHEN data_field_3 is null AND table_field_3 is null then 'OK'
  WHEN data_field_3 is null or table_field_3 is null then '(!) Uno dei campi della JOIN 3 è vuoto'
  WHEN data_field_3 not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = layer_name) then '(!) Il campo chiave layer della JOIN 3 non esiste'
  WHEN table_field_3 not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = r.table_name) then '(!) Il campo chiave relazione della JOIN 3 non esiste'
  ELSE 'OK'
  END as qtrelation_control
FROM qtrelation r
JOIN catalog c using (catalog_id)
join layer l using (layer_id)
JOIN e_qtrelationtype rt using (qtrelationtype_id);

ALTER TABLE vista_qtfield
  OWNER TO gisclient;
  
create or replace view vista_class as 
select c.*,
CASE
  WHEN expression is null AND class_order <= (select max(class_order) from class where layer_id=c.layer_id and class_id != c.class_id and expression is not null) then '(!) Classe con espressione vuota, spostare in fondo'
  WHEN legendtype_id = 1 and class_id not in (select class_id from style) then '(!) Mostra in legenda ma nessuno stile presente'
  WHEN label_font is not null and label_color is not null and label_size is not null and label_position is not null and labelitem is null then '(!) Etichetta configurata correttamente, ma nessun campo etichetta configurato sul layer'
  WHEN label_font is not null and label_color is not null and label_size is not null and label_position is not null and labelitem is not null then 'OK. (i) Con etichetta'
  ELSE 'OK'
END as class_control
FROM class c
JOIN layer l USING (layer_id);

ALTER TABLE vista_class
  OWNER TO gisclient;

DROP VIEW vista_style;
CREATE OR REPLACE VIEW vista_style AS 
 SELECT s.*,
 CASE 
WHEN symbol_name not in (select symbol_name from symbol) then '(!) Il simbolo non esiste'
WHEN color is null and outlinecolor is null and bgcolor is null then '(!) Stile senza colore'
WHEN symbol_name is not null and size is null then '(!) Stile senza dimensione'
ELSE 'OK'
END as style_control
   FROM style s
   LEFT JOIN symbol USING (symbol_name)
  ORDER BY s.style_order;

ALTER TABLE vista_style
  OWNER TO gisclient;  
  
  
CREATE OR REPLACE VIEW vista_layergroup AS 
select lg.*,
CASE 
  WHEN tiles_extent_srid is not null and tiles_extent_srid not in (select srid from project_srs where project_name=t.project_name) THEN '(!) SRID estensione tiles non presente nei sistemi di riferimento del progetto'
  WHEN owstype_id=6 and url is null then '(!) Nessuna URL configurata per la chiamata TMS'
  WHEN owstype_id=6 and layers is null then '(!) Nessun layer configurato per la chiamata TMS'
  WHEN owstype_id=9 and url is null then '(!) Nessuna URL configurata per la chiamata WMTS'
  WHEN owstype_id=9 and layers is null then '(!) Nessun layer configurato per la chiamata WMTS'
  WHEN owstype_id=9 and tile_matrix_set is null then '(!) Nessun Tile Matrix configurato per la chiamata WMTS'
  WHEN owstype_id=9 and style is null then '(!) Nessuno stile configurato per la chiamata WMTS'
  WHEN owstype_id=9 and tile_origin is null then '(!) Nessuna origine configurata per la chiamata WMTS'
  WHEN opacity is null or opacity = '0' then '(i) Attenzione: trasparenza totale'
  ELSE 'OK'
END as layergroup_control

from layergroup lg
JOIN theme t USING (theme_id);

ALTER TABLE vista_layergroup
  OWNER TO gisclient;  
  
CREATE OR REPLACE VIEW vista_mapset AS 
select m.*,
  CASE 
    when mapset_name not in (select mapset_name from mapset_layergroup) then '(!) Nessun layergroup presente'
    when 75 <= (select count(layergroup_id) from mapset_layergroup where mapset_name=m.mapset_name group by mapset_name) then '(!) Openlayers non consente di rappresentare più di 75 layergroup alla volta'
    WHEN mapset_scales is null THEN '(!) Nessun elenco di scale configurato'
    WHEN mapset_srid != displayprojection then '(i) Coordinate visualizzate diverse da quelle di mappa'
    WHEN 0 = (select max(refmap) from mapset_layergroup where mapset_name=m.mapset_name group by mapset_name) THEN '(i) Nessuna reference map'
    ELSE 'OK'
  END as mapset_control
from mapset m;

ALTER TABLE vista_mapset
  OWNER TO gisclient;  
  
CREATE VIEW vista_catalog as
select c.*,
CASE
  WHEN connection_type != 6 then '(i) Controllo non possibile: connessione non PostGIS'
  WHEN substring(c.catalog_path,0,position('/' in c.catalog_path)) != current_database() then '(i) Controllo non possibile: DB diverso'
  WHEN substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) not in (select schema_name from information_schema.schemata) THEN '(!) Lo schema configurato non esiste'
  ELSE 'OK'
END as catalog_control
  from catalog c;
 
ALTER TABLE vista_catalog
  OWNER TO gisclient;  
  
  INSERT INTO version (version_name,version_key, version_date) values ('3.2.25', 'author', '2014-10-13');


-- scale type, choose between user defined scales or power of 2 scales
ALTER TABLE mapset
  ADD COLUMN mapset_scale_type integer NOT NULL DEFAULT 0;
--historicaly srid=3857 implide automatic scales
UPDATE mapset SET mapset_scale_type = 1 WHERE mapset_srid=3857;

INSERT INTO version (version_name,version_key, version_date) values ('3.2.26', 'author', '2014-11-21');


-- permette di ordinare i mapset su una pagina secondo l'ordine deciso dal cliente
ALTER TABLE mapset
  ADD COLUMN mapset_order integer NOT NULL DEFAULT 0;
  
INSERT INTO version (version_name,version_key, version_date) values ('3.2.27', 'author', '2014-11-21');

--correzione delle view di controllo se layergroup.layer esiste nello stesso progetto + view link e sizeunits_id not null

DROP VIEW IF EXISTS vista_mapset;
CREATE OR REPLACE VIEW vista_mapset AS 
select m.*,
  CASE 
    when mapset_name not in (select mapset_name from mapset_layergroup) then '(!) Nessun layergroup presente'
    when 75 <= (select count(layergroup_id) from mapset_layergroup where mapset_name=m.mapset_name group by mapset_name) then '(!) '||(select count(layergroup_id) from mapset_layergroup where mapset_name=m.mapset_name group by mapset_name)||' layergroup presenti nel mapset. OpenLayers 2 non consente di rappresentare più di 74 layergroup alla volta'
    WHEN mapset_scales is null THEN '(!) Nessun elenco di scale configurato'
    WHEN mapset_srid != displayprojection then '(i) Coordinate visualizzate diverse da quelle di mappa'
    WHEN 0 = (select max(refmap) from mapset_layergroup where mapset_name=m.mapset_name group by mapset_name) THEN '(i) Nessuna reference map'
    ELSE 'OK'
  END as mapset_control
from mapset m;

ALTER TABLE vista_mapset
  OWNER TO gisclient;  

DROP VIEW IF EXISTS vista_layer;
CREATE OR REPLACE VIEW vista_layer AS 
 SELECT l.*, 
        CASE
          WHEN queryable = 1 and l.hidden = 0 and 
               layer_id IN (SELECT qtfield.layer_id 
                              FROM qtfield 
                              WHERE qtfield.resultype_id != 4)
          THEN 'SI. Config. OK'
          WHEN queryable = 1 and l.hidden = 1 and
               layer_id IN (SELECT qtfield.layer_id 
                              FROM qtfield 
                              WHERE qtfield.resultype_id != 4)
          THEN 'SI. Ma è nascosto'
          WHEN queryable = 1 and 
               layer_id IN (SELECT qtfield.layer_id 
                              FROM qtfield 
                              WHERE qtfield.resultype_id = 4)
          THEN 'NO. Nessun campo nei risultati'
          ELSE 'NO. WFS non abilitato'
        END AS is_queryable, 
        CASE
            WHEN queryable = 1 and layer_id IN ( SELECT qtfield.layer_id
               FROM qtfield
              WHERE qtfield.editable = 1)
            THEN 'SI. Config. OK' 
            WHEN queryable = 1 and layer_id IN ( SELECT qtfield.layer_id
               FROM qtfield
              WHERE qtfield.editable = 0)
            THEN 'NO. Nessun campo è editabile' 
            WHEN queryable = 0 and layer_id IN ( SELECT qtfield.layer_id
               FROM qtfield
              WHERE qtfield.editable = 1)
            THEN 'NO. Esiste un campo editabile ma il WFS non è attivo' 
            ELSE 'NO.'
        END AS is_editable,
        CASE
            WHEN connection_type != 6 then '(i) Controllo non possibile: connessione non PostGIS'
            WHEN substring(c.catalog_path,0,position('/' in c.catalog_path)) != current_database() then '(i) Controllo non possibile: DB diverso'
            WHEN data not in (select table_name FROM information_schema.tables where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)))  THEN '(!) La tabella non esiste nel DB'
            when data_geom not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = data and data_type = 'USER-DEFINED') then '(!) Il campo geometrico del layer non esiste'
            when data_unique not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = data) then '(!) Il campo chiave del layer non esiste'
            when data_srid not in (select srid FROM public.geometry_columns where f_table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and f_table_name=data) then '(!) Lo SRID configurato non è quello corretto'
            when upper(data_type) not in (select type FROM public.geometry_columns where f_table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and f_table_name=data) then '(!) Geometrytype non corretto'
            WHEN labelitem not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = data) then '(!) Il campo etichetta del layer non esiste'
            WHEN labelitem not in (select qtfield_name FROM qtfield where layer_id = l.layer_id) then '(!) Campo etichetta non presente nei campi del layer'
            WHEN labelsizeitem not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = data) then '(!) Il campo altezza etichetta del layer non esiste'
            WHEN labelsizeitem not in (select qtfield_name FROM qtfield where layer_id = l.layer_id) then '(!) Campo altezza etichetta non presente nei campi del layer'
            --WHEN layer_name in (select distinct layer_name FROM layer where layergroup_id != lg.layergroup_id and catalog_id in (select catalog_id FROM catalog where project_name = c.project_name)) THEN '(!) Combinazione nome layergroup + nome layer non univoca. Cambiare nome al layer o al layergroup'
            WHEN t.project_name||'.'||lg.layergroup_name||'.'||l.layer_name IN (select t2.project_name||'.'||lg2.layergroup_name||'.'||l2.layer_name 
										  from layer l2
										  JOIN layergroup lg2 using (layergroup_id)
										  JOIN theme t2 using (theme_id)
										  group by t2.project_name||'.'||lg2.layergroup_name||'.'||l2.layer_name
										  having count(t2.project_name||'.'||lg2.layergroup_name||'.'||l2.layer_name) > 1) 
										THEN '(!) Combinazione nome layergroup + nome layer non univoca. Cambiare nome al layer o al layergroup'
            WHEN layer_id not in (select layer_id FROM class) then 'OK (i) Non ci sono classi configurate in questo layer'

            ELSE 'OK'
          END as layer_control
   FROM layer l
JOIN catalog c using (catalog_id)
JOIN e_layertype using (layertype_id)
JOIN layergroup lg using (layergroup_id)
JOIN theme t using (theme_id);

ALTER TABLE vista_layer
  OWNER TO gisclient;

DROP VIEW IF EXISTS vista_layergroup;  
CREATE OR REPLACE VIEW vista_layergroup AS 
select lg.*,
CASE 
  WHEN tiles_extent_srid is not null and tiles_extent_srid not in (select srid from project_srs where project_name=t.project_name) THEN '(!) SRID estensione tiles non presente nei sistemi di riferimento del progetto'
  WHEN owstype_id=6 and url is null then '(!) Nessuna URL configurata per la chiamata TMS'
  WHEN owstype_id=6 and layers is null then '(!) Nessun layer configurato per la chiamata TMS'
  WHEN owstype_id=9 and url is null then '(!) Nessuna URL configurata per la chiamata WMTS'
  WHEN owstype_id=9 and layers is null then '(!) Nessun layer configurato per la chiamata WMTS'
  WHEN owstype_id=9 and tile_matrix_set is null then '(!) Nessun Tile Matrix configurato per la chiamata WMTS'
  WHEN owstype_id=9 and style is null then '(!) Nessuno stile configurato per la chiamata WMTS'
  WHEN owstype_id=9 and tile_origin is null then '(!) Nessuna origine configurata per la chiamata WMTS'
  WHEN lg.opacity is null or lg.opacity = '0' then '(i) Attenzione: trasparenza totale'
  WHEN (layergroup_id not in (select layergroup_id FROM layer)) AND layers is null then 'OK (i) Non ci sono layer configurati in questo layergroup'
  ELSE 'OK'
END as layergroup_control

from layergroup lg
JOIN theme t USING (theme_id);

ALTER TABLE vista_layergroup
  OWNER TO gisclient;  
 

DROP VIEW IF EXISTS vista_link;
CREATE OR REPLACE VIEW vista_link AS 
select l.*,
  CASE 
    when link_def not like 'http%://%@%@' THEN '(!) Definizione del link non corretta. La sintassi deve essere: http://url@campo@'
    WHEN link_id not in (select link_id from qtlink) then 'OK. Non utilizzato'
    WHEN replace(substring(link_def from '%#"@%@#"%' for '#'),'@','') not in (select qtfield_name from qtfield where layer_id in (select layer_id from qtlink where link_id=l.link_id))   THEN   '(!) Campo non presente nel layer'
    ELSE 'OK. In uso'
  END as link_control
from link l;

ALTER TABLE gisclient_32.layer ALTER COLUMN sizeunits_id SET NOT NULL;

INSERT INTO version (version_name,version_key, version_date) values ('3.2.28', 'author', '2015-02-26');


-- add lookup for wms version
CREATE TABLE gisclient_32.e_wmsversion
(
  wmsversion_id smallint NOT NULL,
  wmsversion_name character varying NOT NULL,
  wmsversion_order smallint,
  CONSTRAINT e_wmsversion_pkey PRIMARY KEY (wmsversion_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE gisclient_32.e_wmsversion
  OWNER TO gisclient;
ALTER TABLE gisclient_32.layergroup
  ADD COLUMN wmsversion_id integer;
ALTER TABLE gisclient_32.layergroup
  ADD FOREIGN KEY (wmsversion_id) REFERENCES gisclient_32.e_wmsversion (wmsversion_id) ON UPDATE NO ACTION ON DELETE NO ACTION;


INSERT INTO gisclient_32.e_wmsversion (wmsversion_id, wmsversion_name, wmsversion_order ) 
VALUES (1, '1.0.0', 100), (2, '1.1.0', 110), (3, '1.1.1', 111), (4, '1.3.0', 130);


DROP VIEW IF EXISTS vista_layergroup;  
CREATE OR REPLACE VIEW vista_layergroup AS 
select lg.*,
CASE 
  WHEN tiles_extent_srid is not null and tiles_extent_srid not in (select srid from project_srs where project_name=t.project_name) THEN '(!) SRID estensione tiles non presente nei sistemi di riferimento del progetto'
  WHEN owstype_id=6 and url is null then '(!) Nessuna URL configurata per la chiamata TMS'
  WHEN owstype_id=6 and layers is null then '(!) Nessun layer configurato per la chiamata TMS'
  WHEN owstype_id=9 and url is null then '(!) Nessuna URL configurata per la chiamata WMTS'
  WHEN owstype_id=9 and layers is null then '(!) Nessun layer configurato per la chiamata WMTS'
  WHEN owstype_id=9 and tile_matrix_set is null then '(!) Nessun Tile Matrix configurato per la chiamata WMTS'
  WHEN owstype_id=9 and style is null then '(!) Nessuno stile configurato per la chiamata WMTS'
  WHEN owstype_id=9 and tile_origin is null then '(!) Nessuna origine configurata per la chiamata WMTS'
  WHEN lg.opacity is null or lg.opacity = '0' then '(i) Attenzione: trasparenza totale'
  WHEN (layergroup_id not in (select layergroup_id FROM layer)) AND layers is null then 'OK (i) Non ci sono layer configurati in questo layergroup'
  ELSE 'OK'
END as layergroup_control
from layergroup lg
JOIN theme t USING (theme_id);

ALTER TABLE vista_layergroup
  OWNER TO gisclient;  

CREATE OR REPLACE VIEW seldb_wmsversion AS 
 SELECT NULL AS id, 'Seleziona ====>' AS opzione, -1 as wmsversion_order
 UNION 
( SELECT wmsversion_id AS id, wmsversion_name AS opzione, wmsversion_order
   FROM e_wmsversion
  )
  ORDER BY wmsversion_order;

INSERT INTO version (version_name,version_key, version_date) values ('3.2.29', 'author', '2015-04-20');

