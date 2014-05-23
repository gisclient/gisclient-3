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

SET search_path = gisclient_xx, pg_catalog;

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
  
--AGGIORNAMENTO DEL VALORE DI EXPRESSION IN CLASS (AGGIUNTE LE PARENTESI)
update class set expression='('||expression||')' where (expression like '(''[%' or expression like '''[%'  or expression like '[%' ) and not expression like '(%)';

--GESTISCE IL KEYIMAGE
ALTER TABLE class ADD COLUMN keyimage character varying;
UPDATE class SET keyimage = 'NO' from layer where layertype_id=5 and class.layer_id=layer.layer_id;

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
	select into new.layer_name layer_name from layer where layer_id=new.layer_id;
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
update layer set data_type=lower(type) from public.geometry_columns where data=f_table_name;


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

delete from e_form where config_file like 'language_%';

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


ALTER TABLE qtfield ADD COLUMN editable numeric(1,0) DEFAULT 0;
-- AGGIUNTO IL CAMPO FORMULA
ALTER TABLE qtfield ADD COLUMN formula character varying;

-- SPOSTO NEL CAMPO FORMULA QUELLO CHE INDEBITAMENTE è STATO MESSO IN field_name e assegno un nuovo field_name
UPDATE qtfield SET qtfield_name = 'formula_'||qtfield_id, formula=qtfield_name WHERE qtfield_name LIKE '%(%' OR qtfield_name LIKE '%::%' OR qtfield_name LIKE '%||%';

-- SPOSTO NEL CAMPO FORMULA I CAMPI CHE SONO DUPLICATI DEI MODELLI DI RICERCA e assegno un nuovo field_name
UPDATE qtfield SET qtfield_name = 'formula_'||qtfield.qtfield_id, formula=qtfield.qtfield_name 
FROM (select layer_id,qtfield_name from qtfield where qtrelation_id=0 group by 1,2 having count(qtfield_name)>1) as q
WHERE qtfield.layer_id=q.layer_id AND qtfield.qtfield_name=q.qtfield_name;

--UNICITA' DEI NOMI DEI CAMPI NEI LAYERS
ALTER TABLE qtfield ADD CONSTRAINT qtfield_qtfield_name_layer_id_key UNIQUE(qtfield_name, qtrelation_id, layer_id);

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

ALTER TABLE qtfield DROP COLUMN qt_id CASCADE;
ALTER TABLE qtrelation DROP COLUMN qt_id CASCADE;

delete from e_form cascade where config_file='mapset_link';
delete from e_form cascade where config_file='mapset_qt';
delete from e_form cascade where config_file='qt_links';

update e_form set save_data=null where config_file='project';
update e_form set save_data=null where config_file='mapset_layergroup';
update e_form set save_data=null where config_file='layergroup';
update e_form set save_data=null where config_file='admin_project';


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
	  
-- delete from e_level where id=19;
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

-- 2012-01-16: searchable su layer: se no, il layer è interrogabile ma non compare nei modelli di ricerca
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

-- 2013-08-01: crea la view vista_project_languages per permettere la visualizzazione del campo "lingua" nei tab  
  CREATE OR REPLACE VIEW vista_project_languages AS 
 SELECT project_languages.project_name, project_languages.language_id, e_language.language_name, e_language.language_order
   FROM project_languages
   JOIN e_language ON project_languages.language_id = e_language.language_id
  ORDER BY e_language.language_order;
  
  -- 2013-11-01: inserisce outputformat AGG PNG default
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
				  
---- 2013-07-28   FIX BUGS
UPDATE e_form SET save_data = 'mapset_layergroup' WHERE id=84; 
UPDATE e_form SET save_data = 'mapset_layergroup' WHERE id=85; 
UPDATE e_form SET save_data = 'admin_project' WHERE id=133; 
UPDATE e_form SET save_data = 'admin_project' WHERE id=134; 
			  
				  
				  
				  
------ GISCLIENT 3.2 -----------
---- da qui in poi, tutte le modifiche a partire dallo schema di gisclient 3.2		  
-- 2013-07-28  
--FONT SIZE IN LEGEND
ALTER TABLE project ADD COLUMN legend_font_size integer default 8;

-- 2013-08-09
--ELENCO DEI TILEGRID PER SISTEMA DI RIFERIMENTO (per ora i nomi poi aggiungeremo le definizioni)
CREATE TABLE e_tilegrid
(
  tilegrid_id smallint NOT NULL,
  tilegrid_name character varying NOT NULL,
  tilegrid_title character varying NOT NULL,
  tilegrid_extent character varying NOT NULL,
  tilegrid_resolutions character varying NOT NULL,
  tilegrid_order smallint,
  CONSTRAINT e_tilegrid_pkey PRIMARY KEY (tilegrid_id )
);
CREATE OR REPLACE VIEW seldb_tilegrid AS 
         SELECT (-1) AS id, 'Seleziona ====>' AS opzione
UNION ALL 
         SELECT tilegrid_id AS id, e_tilegrid.tilegrid_title AS opzione
           FROM e_tilegrid;

INSERT INTO e_tilegrid VALUES (1, 'g2', 'GoogleMaps + altre scale', '-20037508.34 -20037508.34 20037508.34 20037508.34', '156543.03390625 78271.516953125 39135.758476562 19567.879238281 9783.9396191406 4891.9698095703 2445.9849047852 1222.9924523926 611.49622619629 305.74811309814 152.87405654907 76.437028274536 38.218514137268 19.109257068634 9.554628534317 4.7773142671585 2.3886571335793 1.1943285667896 0.59716428339481 0.29858214169741 0.1492910708487 0.074645535424352', 1);
INSERT INTO e_tilegrid VALUES (2, 'wgs84ge', 'WGS84 Genova', '8.6765751030525 44.044083099967 9.4893192151913 44.856827212106', '0.0031747816880421 0.001587390844021 0.00079369542201052 0.00031747816880421 0.0001587390844021 0.000079369542201052 0.000031747816880421 0.00001587390844021 0.000012699126752168 0.0000095243450641263 0.0000063495633760842 0.0000031747816880421 0.0000025398253504337 0.0000019048690128253 0.0000012699126752168 6.3495633760842e-7 3.1747816880421e-7', 2);
INSERT INTO e_tilegrid VALUES (3, 'gb', 'Gauss-Boaga Genova', '1461444.46 4876844.46 1551755.53 4967155.53', '352.77758727788 176.38879363894 88.19439681947 35.277758727788 17.638879363894 8.819439681947 3.5277758727788 1.7638879363894 1.4111103491115 1.0583327618336 0.70555517455576 0.35277758727788 0.2822220698223 0.21166655236673 0.14111103491115 0.070555517455576 0.035277758727788', 3);
INSERT INTO e_tilegrid VALUES (4, 'WGS84-UTM', 'UTM32N Genova', '461444.16317528 4876753.697405 551755.22551842 4967064.7597481', '352.77758727788 176.38879363894 88.19439681947 35.277758727788 17.638879363894 8.819439681947 3.5277758727788 1.7638879363894 1.4111103491115 1.0583327618336 0.70555517455576 0.35277758727788 0.2822220698223 0.21166655236673 0.14111103491115 0.070555517455576 0.035277758727788', 4);
INSERT INTO e_tilegrid VALUES (5, 'ED50-UTM', 'ED50 Genova', '461444.46882843 4876844.4688284 551755.53117157 4967155.5311716', '352.77758727788 176.38879363894 88.19439681947 35.277758727788 17.638879363894 8.819439681947 3.5277758727788 1.7638879363894 1.4111103491115 1.0583327618336 0.70555517455576 0.35277758727788 0.2822220698223 0.21166655236673 0.14111103491115 0.070555517455576 0.035277758727788', 5);

ALTER TABLE project_srs ADD COLUMN tilegrid_id smallint;

--RIMOSSO GMap 2
DELETE FROM e_owstype where owstype_id=2;
--RIMOSSO VirtualEarth
DELETE FROM e_owstype where owstype_id=3;
--AGGIUNTO WMTS
INSERT INTO e_owstype VALUES (2, 'WMTS', 2);
--UPDATE TO GMAP V3
UPDATE layergroup SET layers='satellite',owstype_id=7 WHERE layers='G_SATELLITE_MAP';
UPDATE layergroup SET layers='roadmap',owstype_id=7 WHERE layers='G_NORMAL_MAP';
UPDATE layergroup SET layers='hybrid',owstype_id=7 WHERE layers='G_HYBRID_MAP';
UPDATE layergroup SET layers='terrain',owstype_id=7 WHERE layers='G_PHYSICAL_MAP';


--2013-08-20
--UNIVOCITA' DEL NOME DEL LAYERGROUP NEL PROGETTO (CE LO SIAMO PERSO DA QUALCHE PARTE MA SE TENIAMO IL LAYERGROUP COME NOME LAYER QUESTO DEVE ESSERE UNIVOCO SUL MAPFILE)
UPDATE layergroup SET layergroup_name=layergroup_name||'_'||layergroup_id WHERE layergroup_name IN (SELECT layergroup_name FROM layergroup GROUP BY 1 HAVING count(layergroup_name) > 1)
ALTER TABLE layergroup DROP CONSTRAINT layergroup_theme_key;
ALTER TABLE layergroup ADD CONSTRAINT layergroup_unique_key UNIQUE(layergroup_name );

--2013-08-28
--AGGIORNATI NOMI TIPO 
UPDATE e_owstype SET owstype_name='WMS' WHERE owstype_id = 1;
UPDATE e_owstype SET owstype_name='Google' WHERE owstype_id = 7;
UPDATE e_owstype SET owstype_name='Bing' WHERE owstype_id = 8;

-- SET POSTLABELCACHE DEFAULT FALSE
ALTER TABLE layer
   ALTER COLUMN postlabelcache SET DEFAULT 0;
ALTER TABLE layer DROP CONSTRAINT layer_layergroup_id_fkey;
ALTER TABLE layer ADD CONSTRAINT layer_layergroup_id_fkey FOREIGN KEY (layergroup_id)
      REFERENCES layergroup (layergroup_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE;
update layer set postlabelcache = 0;

--2013-10-04: campo per specificare se la geometria dev'essere nascosta nell'interrogazione (per esempio nel caso di interrogazione dei comuni che vanno a coprire inutilmente tutti gli altri oggetti interrogati)
alter table layer add column hide_vector_geom numeric(1,0) default 0;

--2013-10-23: bugfix schema
-- ATTENZIONE AGGIUNGERE UN UTENTE admin AGLI UTENTI
DROP FUNCTION move_layergroup() CASCADE;
CREATE OR REPLACE FUNCTION gisclient_33.set_layer_name()
  RETURNS trigger AS
$BODY$
BEGIN
	select into new.layer_name layer_name from gisclient_33.layer where layer_id=new.layer_id;
	return new;
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION gisclient_33.set_layer_name()
  OWNER TO postgres;

DELETE from e_form where name like '%authfilter';



--2014-5-23 cambio modalità searchable
ALTER TABLE layer RENAME searchable  TO searchable_id;

CREATE TABLE e_searchable
(
  searchable_id smallint NOT NULL,
  searchable_name character varying NOT NULL,
  searchable_order smallint,
  CONSTRAINT e_searchable_pkey PRIMARY KEY (searchable_id)
);

INSERT INTO e_searchable values (0,'Non visualizzato',0);
INSERT INTO e_searchable values (1,'Visualizzato in ricerca',1);
INSERT INTO e_searchable values (2,'Solo ricerca veloce',2);

CREATE OR REPLACE VIEW seldb_searchable AS 
SELECT searchable_id AS id, searchable_name AS opzione
FROM e_searchable;

--2014-5-23 nascosti filtri di autenticazione
UPDATE form_level set visible=0 where id in (511,512,514);
