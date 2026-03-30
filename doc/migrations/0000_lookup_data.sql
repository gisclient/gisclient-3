-- Lookup and system seed data extracted from gisclient_34.dmp
-- This file is executed as Phase 2 of gisclient:dbupgrade on fresh installs.



--
-- Data for Name: authfilter; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--


--
-- Data for Name: e_charset_encodings; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_charset_encodings (charset_encodings_id, charset_encodings_name, charset_encodings_order) VALUES
    ('1', 'ISO-8859-1', '1'),
    ('2', 'UTF-8', '2');




--
-- Data for Name: e_conntype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_conntype (conntype_id, conntype_name, conntype_order) VALUES
    ('7', 'WMS', '4'),
    ('3', 'SDE', '7'),
    ('6', 'Postgis', '2'),
    ('8', 'Oracle Spatial', '3'),
    ('1', 'Local Folder', '1'),
    ('9', 'WFS', '5'),
    ('4', 'OGR', '6');




--
-- Data for Name: e_datatype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_datatype (datatype_id, datatype_name, datatype_order) VALUES
    ('1', 'Stringa di testo', NULL),
    ('2', 'Numero', NULL),
    ('3', 'Data', NULL),
    ('10', 'Immagine', NULL),
    ('15', 'File', NULL);




--
-- Data for Name: e_fieldformat; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_fieldformat (fieldformat_id, fieldformat_name, fieldformat_format, fieldformat_order) VALUES
    ('1', 'intero', '%d', '10'),
    ('2', 'decimale (1 cifra)', '%01.1f', '20'),
    ('3', 'decimale (2 cifre)', '%01.2f', '30');




--
-- Data for Name: e_fieldtype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_fieldtype (fieldtype_id, fieldtype_name, fieldtype_order) VALUES
    ('1', 'Standard', NULL),
    ('3', 'E-mail', NULL),
    ('104', 'Max', NULL),
    ('103', 'Min', NULL),
    ('10', 'File', NULL),
    ('2', 'Link', NULL),
    ('107', 'Variance', NULL),
    ('106', 'Standard Deviation', NULL),
    ('105', 'Count', NULL),
    ('102', 'Average', NULL),
    ('101', 'Sum', NULL),
    ('8', 'Image', NULL);




--
-- Data for Name: e_filetype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_filetype (filetype_id, filetype_name, filetype_order) VALUES
    ('1', 'SQL file', '1'),
    ('2', 'CSV file', '2'),
    ('3', 'Shape file', '3');




--
-- Data for Name: e_level; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES
    ('1', 'root', NULL, '1', NULL, NULL, '0', '0', NULL, NULL, '2'),
    ('2', 'project', 'project', '2', '1', '0', '0', '1', '1', 'project', '2'),
    ('3', 'groups', 'groups', '7', '1', '0', '0', '0', '1', 'groups', '1'),
    ('4', 'users', 'users', '6', '1', '0', '0', '0', '1', 'users', '1'),
    ('5', 'theme', 'theme', '3', '2', '1', '0', '5', '2', 'theme', '2'),
    ('6', 'project_srs', 'project_srs', '4', '2', '1', '1', '1', '2', 'project_srs', '2'),
    ('7', 'catalog', 'catalog', '13', '2', '1', '1', '2', '2', 'catalog', '2'),
    ('8', 'mapset', 'mapset', '15', '2', '1', '0', '6', '2', 'mapset', '2'),
    ('9', 'link', 'link', '15', '2', '1', '1', '4', '2', 'link', '2'),
    ('10', 'layergroup', 'layergroup', '4', '5', '2', '0', '1', '5', 'layergroup', '2'),
    ('11', 'layer', 'layer', '5', '10', '3', '0', '1', '10', 'layer', '2'),
    ('12', 'class', 'class', '6', '11', '4', '0', '1', '11', 'class', '2'),
    ('14', 'style', 'style', '7', '12', '5', '1', '1', '12', 'style', '2'),
    ('22', 'mapset_layergroup', 'mapset_layergroup', '17', '8', '2', '1', '1', '8', 'mapset_layergroup', '2'),
    ('27', 'selgroup', 'selgroup', NULL, '2', '1', '0', '8', '2', 'selgroup', '2'),
    ('33', 'project_admin', 'project_admin', '15', '2', '1', '1', '0', '2', 'project_admin', '2'),
    ('45', 'group_users', 'user_groups', NULL, '4', '2', '1', '0', '4', 'user_group', '1'),
    ('46', 'user_groups', 'group_users', NULL, '3', '2', '1', '0', '3', 'user_group', '1'),
    ('32', 'user_project', 'project', '8', '2', '1', '1', '0', '2', 'user_project', '2'),
    ('47', 'layer_groups', 'layer_groups', NULL, '11', '4', '1', '0', '11', 'layer_groups', '2'),
    ('48', 'project_languages', 'project', NULL, '2', '1', '1', '1', '2', 'project_languages', '2'),
    ('49', 'authfilter', 'authfilter', '8', '1', '0', '1', '0', '1', 'authfilter', '2'),
    ('51', 'group_authfilter', 'groups', '1', '3', '1', '1', '0', '3', 'group_authfilter', '2'),
    ('28', 'selgroup_layer', 'selgroup_layer', NULL, '27', '2', '1', '1', '27', 'selgroup_layer', '2'),
    ('16', 'relation', 'relation', '10', '11', '4', '1', '1', '11', 'relation', '2'),
    ('17', 'field', 'field', '11', '11', '4', '1', '2', '11', 'field', '2'),
    ('52', 'field_groups', 'field', '1', '17', '5', '1', '0', '17', 'field_groups', '2'),
    ('50', 'layer_authfilter', 'layer', '15', '11', '4', '1', '0', '11', 'layer_authfilter', '2'),
    ('19', 'layer_link', 'layer', '12', '11', '4', '1', '0', '11', 'layer_link', '2'),
    ('53', 'mapset_groups', 'mapset', '20', '8', '2', '1', '1', '8', 'mapset_groups', '2');




--
-- Data for Name: e_form; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES
    ('213', 'selgroup_layer', 'selgroup_layer', '4', '28', NULL, 'selgroup_layer', '27', NULL, NULL, NULL),
    ('214', 'selgroup_layer', 'selgroup_layer', '5', '28', NULL, 'selgroup_layer', '27', NULL, NULL, NULL),
    ('16', 'user', 'user', '0', '4', NULL, 'user', '2', NULL, 'user', NULL),
    ('2', 'progetto', 'project', '0', '2', NULL, NULL, NULL, NULL, NULL, 'project_name'),
    ('3', 'progetto', 'project', '1', '2', '', NULL, NULL, NULL, NULL, NULL),
    ('5', 'mapset', 'mapset', '0', '8', NULL, NULL, NULL, NULL, NULL, 'title'),
    ('6', 'progetto', 'project', '2', '2', '', 'project', NULL, NULL, NULL, NULL),
    ('7', 'progetto', 'project', '1', '2', NULL, 'project', NULL, NULL, NULL, NULL),
    ('8', 'temi', 'theme', '0', '5', NULL, NULL, NULL, NULL, NULL, 'theme_order,theme_title'),
    ('9', 'temi', 'theme', '1', '5', NULL, NULL, NULL, NULL, NULL, NULL),
    ('10', 'temi', 'theme', '1', '5', NULL, NULL, '2', NULL, NULL, NULL),
    ('11', 'temi', 'theme', '2', '5', NULL, NULL, '2', NULL, NULL, NULL),
    ('12', 'project_srs', 'project_srs', '0', '6', NULL, NULL, '2', NULL, NULL, NULL),
    ('13', 'project_srs', 'project_srs', '1', '6', NULL, NULL, '2', NULL, NULL, NULL),
    ('14', 'project_srs', 'project_srs', '2', '6', NULL, NULL, '2', NULL, NULL, NULL),
    ('23', 'group', 'group', '50', '3', NULL, 'group', '2', NULL, 'group', NULL),
    ('26', 'mapset', 'mapset', '1', '8', '', NULL, '2', NULL, NULL, NULL),
    ('27', 'mapset', 'mapset', '1', '8', NULL, 'mapset', '2', NULL, NULL, NULL),
    ('28', 'mapset', 'mapset', '2', '2', NULL, 'mapset', '2', NULL, NULL, NULL),
    ('34', 'layer', 'layer', '0', '11', NULL, NULL, '10', NULL, NULL, 'layer_order,layer_name'),
    ('35', 'layer', 'layer', '1', '11', NULL, 'layer', '10', NULL, NULL, NULL),
    ('36', 'layer', 'layer', '1', '11', NULL, 'layer', '10', NULL, NULL, NULL),
    ('37', 'layer', 'layer', '2', '11', NULL, 'layer', '10', NULL, NULL, NULL),
    ('38', 'classi', 'class', '0', '12', NULL, NULL, '11', NULL, NULL, 'class_order'),
    ('39', 'classi', 'class', '1', '12', NULL, NULL, '11', NULL, NULL, NULL),
    ('40', 'classi', 'class', '1', '12', NULL, 'class', '11', NULL, NULL, NULL),
    ('41', 'classi', 'class', '2', '12', NULL, 'class', '11', NULL, NULL, NULL),
    ('42', 'stili', 'style', '0', '14', NULL, NULL, '12', NULL, NULL, 'style_order'),
    ('43', 'stili', 'style', '1', '14', NULL, NULL, '12', NULL, NULL, NULL),
    ('44', 'stili', 'style', '1', '14', NULL, 'style', '12', NULL, NULL, NULL),
    ('45', 'stili', 'style', '2', '14', NULL, 'style', '12', NULL, NULL, NULL),
    ('50', 'catalog', 'catalog', '0', '7', NULL, NULL, '2', NULL, NULL, 'catalog_name'),
    ('51', 'catalog', 'catalog', '1', '7', NULL, NULL, '2', NULL, NULL, NULL),
    ('52', 'catalog', 'catalog', '1', '7', NULL, 'catalog', '2', NULL, NULL, NULL),
    ('53', 'catalog', 'catalog', '2', '7', NULL, 'catalog', '2', NULL, NULL, NULL),
    ('70', 'links', 'link', '0', '9', '', NULL, '2', NULL, NULL, 'link_order,link_name'),
    ('72', 'links', 'link', '1', '9', '', NULL, '2', NULL, NULL, NULL),
    ('73', 'links', 'link', '1', '9', '', NULL, '2', NULL, NULL, NULL),
    ('74', 'links', 'link', '2', '9', '', NULL, '2', NULL, NULL, NULL),
    ('105', 'selgroup', 'selgroup', '0', '27', NULL, NULL, '2', NULL, NULL, NULL),
    ('106', 'selgroup', 'selgroup', '1', '27', NULL, NULL, '2', NULL, NULL, NULL),
    ('107', 'selgroup', 'selgroup', '1', '27', NULL, NULL, '2', NULL, NULL, NULL),
    ('133', 'project_admin', 'admin_project', '2', '33', NULL, NULL, '2', NULL, NULL, NULL),
    ('134', 'project_admin', 'admin_project', '5', '33', NULL, 'admin_project', '6', NULL, NULL, NULL),
    ('151', 'user_groups', 'user_groups', '4', '46', NULL, 'user_groups', '4', NULL, NULL, NULL),
    ('152', 'user_groups', 'user_groups', '5', '46', NULL, 'user_groups', '4', NULL, NULL, NULL),
    ('75', 'relation', 'relation_addnew', '0', '16', NULL, NULL, '13', NULL, NULL, NULL),
    ('30', 'layergroup', 'layergroup', '0', '10', NULL, 'layergroup', '5', NULL, NULL, 'layergroup_order,layergroup_title'),
    ('31', 'layergroup', 'layergroup', '1', '10', NULL, 'layergroup', '5', NULL, NULL, NULL),
    ('32', 'layergroup', 'layergroup', '1', '10', NULL, 'layergroup', '5', NULL, NULL, NULL),
    ('33', 'layergroup', 'layergroup', '2', '10', NULL, 'layergroup', '5', NULL, NULL, NULL),
    ('84', 'map_layer', 'mapset_layergroup', '4', '22', NULL, 'mapset_layergroup', '8', NULL, NULL, NULL),
    ('85', 'map_layer', 'mapset_layergroup', '5', '22', NULL, 'mapset_layergroup', '8', NULL, NULL, NULL),
    ('86', 'map_layer', 'mapset_layergroup', '0', '22', NULL, 'mapset_layergroup', '8', NULL, NULL, NULL),
    ('170', 'layer_groups', 'layer_groups', '4', '47', NULL, 'layer_groups', '11', NULL, NULL, NULL),
    ('171', 'layer_groups', 'layer_groups', '5', '47', NULL, 'layer_groups', '11', NULL, NULL, NULL),
    ('202', 'project_languages', 'project_languages', '0', '48', NULL, NULL, '2', NULL, NULL, NULL),
    ('203', 'project_languages', 'project_languages', '1', '48', NULL, NULL, '2', NULL, NULL, NULL),
    ('204', 'authfilter', 'authfilter', '0', '49', NULL, NULL, '2', NULL, NULL, NULL),
    ('205', 'authfilter', 'authfilter', '1', '49', NULL, NULL, '2', NULL, NULL, NULL),
    ('206', 'layer_authfilter', 'layer_authfilter', '4', '50', NULL, 'layer_authfilter', '11', NULL, NULL, NULL),
    ('207', 'layer_authfilter', 'layer_authfilter', '5', '50', NULL, 'layer_authfilter', '11', NULL, NULL, NULL),
    ('208', 'group_authfilter', 'group_authfilter', '0', '51', NULL, NULL, '3', NULL, NULL, NULL),
    ('209', 'group_authfilter', 'group_authfilter', '1', '51', NULL, NULL, '3', NULL, NULL, NULL),
    ('20', 'group', 'group', '0', '3', NULL, 'group', '2', NULL, 'group', NULL),
    ('18', 'user', 'user', '50', '4', NULL, 'user', '2', NULL, 'user', NULL),
    ('58', 'relation', 'relation', '0', '16', NULL, NULL, '11', NULL, NULL, NULL),
    ('59', 'relation', 'relation', '1', '16', NULL, NULL, '11', NULL, NULL, NULL),
    ('60', 'relation', 'relation', '1', '16', NULL, NULL, '11', NULL, NULL, NULL),
    ('61', 'relation', 'relation', '2', '16', NULL, NULL, '11', NULL, NULL, NULL),
    ('63', 'fields', 'field', '1', '17', NULL, NULL, '11', NULL, NULL, NULL),
    ('64', 'fields', 'field', '1', '17', NULL, NULL, '11', NULL, NULL, NULL),
    ('65', 'fields', 'field', '2', '17', NULL, NULL, '11', NULL, NULL, NULL),
    ('62', 'fields', 'field', '0', '17', NULL, NULL, '11', NULL, NULL, 'relationtype_id,relation_name,field_header,field_name'),
    ('210', 'field_groups', 'field_groups', '4', '52', NULL, 'field_groups', '17', NULL, NULL, NULL),
    ('211', 'field_groups', 'field_groups', '5', '52', NULL, 'field_groups', '17', NULL, NULL, NULL),
    ('212', 'field_groups', 'field_groups', '0', '52', NULL, 'field_groups', '17', NULL, NULL, NULL),
    ('66', 'layer_link', 'layer_link', '2', '19', NULL, NULL, '11', NULL, NULL, NULL),
    ('69', 'layer_link', 'layer_link', '110', '19', NULL, NULL, '11', NULL, NULL, NULL),
    ('68', 'layer_link', 'layer_link', '1', '19', NULL, NULL, '11', NULL, NULL, NULL),
    ('67', 'layer_link', 'layer_link', '0', '19', NULL, NULL, '11', NULL, NULL, NULL),
    ('215', 'mapset_groups', 'mapset_groups', '4', '53', NULL, 'mapset_groups', '8', NULL, NULL, NULL),
    ('216', 'mapset_groups', 'mapset_groups', '5', '53', NULL, 'mapset_groups', '8', NULL, NULL, NULL);




--
-- Data for Name: e_formula; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_formula (formula_id, formula_name, formula_format, formula_order) VALUES
    ('12', 'Date (YYYY-MM-DD)', 'to_char({{field_name}}, ''YYYY-MM-DD'')', '120'),
    ('1', '0 decimal places', 'to_char({{field_name}}, ''FM3263299990'')', '10'),
    ('2', '1 decimal places', 'to_char({{field_name}}, ''FM3263299990.0'')', '20'),
    ('3', '2 decimal places', 'to_char({{field_name}}, ''FM3263299990.00'')', '30'),
    ('4', '3 decimal places', 'to_char({{field_name}}, ''FM3263299990.000'')', '40'),
    ('5', '0 decimals with thousands separator', 'to_char({{field_name}}, ''FM9,999,999,990'')', '50'),
    ('6', '1 decimals with thousands separator', 'to_char({{field_name}}, ''FM9,999,999,990.0'')', '60'),
    ('7', '2 decimals with thousands separator', 'to_char({{field_name}}, ''FM9,999,999,990.00'')', '70'),
    ('8', '3 decimals with thousands separator', 'to_char({{field_name}}, ''FM9,999,999,990.000'')', '80'),
    ('9', 'Date (GG/MM/YYYY)', 'to_char({{field_name}}, ''DD/MM/YYYY'')', '110'),
    ('10', 'Date (GG.MM.YYYY)', 'to_char({{field_name}}, ''DD.MM.YYYY'')', '130'),
    ('11', 'Currency (€)', 'to_char({{field_name}}, ''FM€ 3263299990.00'')', '210');




--
-- Data for Name: e_language; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_language (language_id, language_name, language_order) VALUES
    ('en', 'English', '1'),
    ('fr', 'Francais', '2'),
    ('de', 'Deutsch', '3'),
    ('es', 'Espanol', '4'),
    ('it', 'Italiano', '5'),
    ('ru', 'русский (Russian)', '6'),
    ('ua', 'український (Ukrainian)', '7'),
    ('zh', '正體中文 (Chinese [traditional])', '8'),
    ('hu', 'Magyar (Hungarian)', '9'),
    ('he', 'יהודי (Jewish)', '10'),
    ('el', 'Ελληνικά (Greek)', '11');




--
-- Data for Name: e_layertype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_layertype (layertype_id, layertype_name, layertype_ms, layertype_order) VALUES
    ('5', 'annotation', '4', NULL),
    ('1', 'point', '0', NULL),
    ('2', 'line', '1', NULL),
    ('3', 'polygon', '2', NULL),
    ('4', 'raster', '3', NULL),
    ('10', 'tileraster', '100', NULL),
    ('11', 'chart', '8', NULL);




--
-- Data for Name: e_lblposition; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_lblposition (lblposition_id, lblposition_name, lblposition_order) VALUES
    ('1', 'UL', NULL),
    ('2', 'UC', NULL),
    ('3', 'UR', NULL),
    ('4', 'CL', NULL),
    ('5', 'CC', NULL),
    ('6', 'CR', NULL),
    ('7', 'LL', NULL),
    ('8', 'LC', NULL),
    ('9', 'LR', NULL),
    ('10', 'AUTO', NULL);




--
-- Data for Name: e_legendtype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_legendtype (legendtype_id, legendtype_name, legendtype_order) VALUES
    ('1', 'auto', '1'),
    ('0', 'nessuna', '2');




--
-- Data for Name: e_orderby; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_orderby (orderby_id, orderby_name, orderby_order) VALUES
    ('0', 'Nessuno', NULL),
    ('1', 'Crescente', NULL),
    ('2', 'Decresente', NULL);




--
-- Data for Name: e_outputformat; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES
    ('2', 'AGG PNG', 'AGG/PNG', 'image/png', 'PC256', 'png', NULL, NULL),
    ('4', 'PNG 8 bit', 'GD/PNG', 'image/png', 'PC256', 'png', NULL, NULL),
    ('5', 'PNG 24 bit', 'GD/PNG', 'image/png', 'RGB', 'png', NULL, NULL),
    ('6', 'PNG 32 bit Trasp', 'GD/PNG', 'image/png', 'RGBA', 'png', NULL, NULL),
    ('7', 'AGG Q', 'AGG/PNG', 'image/png; mode=8bit', 'RGB', 'png', E'    FORMATOPTION "QUANTIZE_FORCE=ON"\n    FORMATOPTION "QUANTIZE_DITHER=OFF"\n    FORMATOPTION "QUANTIZE_COLORS=256"', NULL),
    ('1', 'AGG PNG 24 bit', 'AGG/PNG', 'image/png; mode=24bit', 'RGB', 'png', NULL, NULL),
    ('3', 'AGG JPG', 'AGG/JPG', 'jpeg', 'RGB', 'jpg', NULL, NULL),
    ('9', 'AGG PNG', 'AGG/PNG', 'image/png', 'RGB', 'png', E'    FORMATOPTION "QUANTIZE_FORCE=ON"\nFORMATOPTION "QUANTIZE_DITHER=OFF"\nFORMATOPTION "QUANTIZE_COLORS=256"', NULL),
    ('10', 'GEOJSON', 'OGR/GEOJSON', 'application/json; subtype=geojson', 'JSON', 'json', 'FORMATOPTION "STORAGE=stream" FORMATOPTION "FORM=SIMPLE"', NULL);




--
-- Data for Name: e_owstype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_owstype (owstype_id, owstype_name, owstype_order) VALUES
    ('1', 'WMS', '1'),
    ('2', 'WMTS', '2'),
    ('3', 'WMS (tiles in cache di mapproxy)', '3'),
    ('4', 'Yahoo', '3'),
    ('5', 'OSM', '5'),
    ('6', 'TMS', '6'),
    ('7', 'Google', '4'),
    ('8', 'Bing', '6'),
    ('10', 'WFS', '4');




--
-- Data for Name: e_papersize; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_papersize (papersize_id, papersize_name, papersize_size, papersize_orientation, papaersize_order) VALUES
    ('1', 'A4 Verticale', 'A4', 'P', NULL),
    ('2', 'A4 Orizzontale', 'A4', 'L', NULL),
    ('3', 'A3 Verticale', 'A3', 'P', NULL),
    ('4', 'A3 Orizzontale', 'A3', 'L', NULL),
    ('5', 'A2 Verticale', 'A2', 'P', NULL),
    ('6', 'A2 Orizzontale', 'A2', 'L', NULL),
    ('7', 'A1 Verticale', 'A1', 'P', NULL),
    ('8', 'A1 Orizzontale', 'A1', 'L', NULL),
    ('9', 'A0 Verticale', 'A0', 'P', NULL),
    ('10', 'A0 Orizzontale', 'A0', 'L', NULL);




--
-- Data for Name: e_pattern; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_pattern (pattern_id, pattern_name, pattern_def, pattern_order) VALUES
    ('0', 'NO PATTERN', '#PATTERN END', '0'),
    ('1', '1-3', 'PATTERN 1 3 END', '1'),
    ('2', '2-3', 'PATTERN 2 3 END', '2'),
    ('3', '3-3', 'PATTERN 3 3 END', '3'),
    ('4', '5-5', 'PATTERN 5 5 END', '4'),
    ('5', '10-10', 'PATTERN 10 10 END', '5'),
    ('6', '10-3', 'PATTERN 10 3 END', '6'),
    ('7', '3-10', 'PATTERN 3 10 END', '7'),
    ('8', '5-3-1-3', 'PATTERN 5 3 1 3 END', '8'),
    ('9', '5-3-1-3-1-3', 'PATTERN 5 3 1 3 1 3 END', '9'),
    ('10', '5-3-5-3-1-3', 'PATTERN 5 3 5 3 1 3 END', '10'),
    ('11', '1-2-1-6', 'PATTERN 1 2 1 6 END', '11');




--
-- Data for Name: e_relationtype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_relationtype (relationtype_id, relationtype_name, relationtype_order) VALUES
    ('1', 'Dettaglio (1 a 1)', NULL),
    ('2', 'Secondaria (Info 1 a molti)', NULL);




--
-- Data for Name: e_resultype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_resultype (resultype_id, resultype_name, resultype_order) VALUES
    ('1', 'Mostra sempre', '1'),
    ('4', 'Nascondi', '2'),
    ('5', 'Ignora', '3'),
    ('10', 'Nascondi in tabella', '4'),
    ('20', 'Nascondi in tooltip', '5'),
    ('30', 'Nascondi in scheda', '6');




--
-- Data for Name: e_searchable; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_searchable (searchable_id, searchable_name, searchable_order) VALUES
    ('0', 'Non ricercabile', '0'),
    ('1', 'Visualizzato in ricerca', '1'),
    ('2', 'Solo ricerca veloce', '2');




--
-- Data for Name: e_searchtype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_searchtype (searchtype_id, searchtype_name, searchtype_order) VALUES
    ('4', 'Numerico', NULL),
    ('5', 'Data', NULL),
    ('1', 'Testo', NULL),
    ('2', 'Parte di testo', NULL),
    ('3', 'Lista di valori', NULL),
    ('0', 'Nessuno', NULL),
    ('6', 'Lista di valori, non WFS', NULL);




--
-- Data for Name: e_sizeunits; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_sizeunits (sizeunits_id, sizeunits_name, sizeunits_order) VALUES
    ('2', 'feet', NULL),
    ('3', 'inches', NULL),
    ('1', 'pixels', NULL),
    ('4', 'kilometers', NULL),
    ('5', 'meters', NULL),
    ('6', 'miles', NULL),
    ('7', 'dd', NULL);




--
-- Data for Name: e_symbolcategory; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_symbolcategory (symbolcategory_id, symbolcategory_name, symbolcategory_order) VALUES
    ('1', 'MapServer', NULL),
    ('3', 'Campiture', NULL),
    ('4', 'Marker', NULL),
    ('5', 'CatastoCML', NULL),
    ('7', 'Numeri', NULL),
    ('6', 'TechNET', NULL),
    ('13', 'R3-TREES', NULL),
    ('21', 'R3-CARTOGRAPHY', NULL),
    ('91', 'R3-MAPSYMBOLS', NULL);




--
-- Data for Name: e_tiletype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_tiletype (tiletype_id, tiletype_name, tiletype_order) VALUES
    ('0', 'no Tiles', '1'),
    ('1', 'WMS Tiles', '2'),
    ('2', 'Tilecache Tiles', '3');




--
-- Data for Name: e_wmsversion; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.e_wmsversion (wmsversion_id, wmsversion_name, wmsversion_order) VALUES
    ('1', '1.0.0', '100'),
    ('2', '1.1.0', '110'),
    ('3', '1.1.1', '111'),
    ('4', '1.3.0', '130');




--
-- Data for Name: font; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.font (font_name, file_name) VALUES
    ('verdana', 'verdana.ttf'),
    ('verdana-bold', 'verdanab.ttf'),
    ('verdana-italic', 'verdanai.ttf'),
    ('verdana-bold-italic', 'verdanaz.ttf');




--
-- Data for Name: group_authfilter; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--




--
-- Data for Name: groups; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--




--
-- Data for Name: i18n_field; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.i18n_field (i18nf_id, table_name, field_name) VALUES
    ('1', 'class', 'class_title'),
    ('2', 'class', 'expression'),
    ('3', 'class', 'label_def'),
    ('4', 'class', 'class_text'),
    ('5', 'layer', 'layer_title'),
    ('6', 'layer', 'data_filter'),
    ('7', 'layer', 'layer_def'),
    ('8', 'layer', 'metadata'),
    ('9', 'layer', 'labelitem'),
    ('10', 'layer', 'classitem'),
    ('11', 'layergroup', 'layergroup_title'),
    ('12', 'layergroup', 'sld'),
    ('15', 'style', 'style_def'),
    ('16', 'theme', 'theme_title'),
    ('17', 'theme', 'copyright_string'),
    ('18', 'mapset', 'mapset_title'),
    ('19', 'mapset', 'mapset_description'),
    ('14', 'field', 'field_header'),
    ('13', 'field', 'field_name'),
    ('20', 'layer', 'template'),
    ('21', 'layer', 'header'),
    ('22', 'layer', 'footer');




--
-- Data for Name: symbol; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def, symbol_type, font_name, ascii_code, filled, points, image) VALUES
    ('VIGNETO', '3', '0', NULL, E'Type VECTOR\n  Filled TRUE\n  Points\n\t\t.8 .6\n\t\t.4 .6\n\t\t.6 0\n\t\t.4 0\n\t\t.2 .6\n\t\t.4 .8\n\t\t.6 .8\n\t\t.8 .6\n  END\n\t\t', NULL, NULL, NULL, '0', NULL, NULL),
    ('TENT', '1', '0', NULL, E'TYPE VECTOR\nFILLED TRUE\nPOINTS\n0 1\n.5 0\n1 1\n.75 1\n.5 .5\n.25 1\n0 1\nEND', NULL, NULL, NULL, '0', NULL, NULL),
    ('STAR', '1', '0', NULL, E'TYPE VECTOR\nFILLED TRUE\nPOINTS\n0 .375\n.35 .375\n.5 0\n.65 .375\n1 .375\n.75 .625\n.875 1\n.5 .75\n.125 1\n.25 .625\nEND', NULL, NULL, NULL, '0', NULL, NULL),
    ('TRIANGLE', '1', '0', NULL, E'TYPE VECTOR\nFILLED TRUE\nPOINTS\n0 1\n.5 0\n1 1\n0 1\nEND', NULL, NULL, NULL, '0', NULL, NULL),
    ('SQUARE', '1', '0', NULL, E'TYPE VECTOR\nFILLED TRUE\nPOINTS\n0 1\n0 0\n1 0\n1 1\n0 1\nEND', NULL, NULL, NULL, '0', NULL, NULL),
    ('PLUS', '1', '0', NULL, E'TYPE VECTOR\nPOINTS\n.5 0\n.5 1\n-99 -99\n0 .5\n1 .5\nEND', NULL, NULL, NULL, '0', NULL, NULL),
    ('CROSS', '1', '0', NULL, E'TYPE VECTOR\nPOINTS\n0 0\n1 1\n-99 -99\n0 1\n1 0\nEND', NULL, NULL, NULL, '0', NULL, NULL),
    ('VIVAIO', '3', '0', NULL, E'TYPE Vector\n  POINTS\n\t\t.3 1\n\t\t.7 1\n\t\t.9 .1\n\t\t.1 .1\n\t\t.3 1\n\t\t-99 -99\n\t\t.2 .2\n\t\t.2 .1\n\t\t-99 -99\n\t\t.5 .2\n\t\t.5 .1\n\t\t-99 -99\n\t\t.7 .2\n\t\t.7 .1\n  END\n\t', NULL, NULL, NULL, '0', NULL, NULL),
    ('CIRCLE', '1', '0', NULL, E'TYPE ELLIPSE\nFILLED TRUE\nPOINTS\n1 1\nEND', NULL, NULL, NULL, '0', NULL, NULL),
    ('WATER', '3', '0', NULL, E'Type VECTOR\n  Filled FALSE  \n   Points\n\t\t0 .6\n\t\t.1 .4\n\t\t.2 .4\n\t\t.3 .6\n\t\t.4 .6\n\t\t.5 .4\n\t\t.6 .4\n\t\t.7 .6\n\t\t.8 .6\n\t\t.9 .4\n\t\t1 .4\n\t\t1.1 .6\n  END', NULL, NULL, NULL, '0', NULL, NULL),
    ('CIRCLE_EMPTY', '3', '0', NULL, E'TYPE Vector\n  POINTS\n    0 .5\n\t\t.1 .7\n\t\t.3 .9\n\t\t.5 1\n\t\t.7 .9\n\t\t.9 .7\n\t\t1 .5\n\t\t.9 .3\n\t\t.7 .1\n\t\t.5 0\n\t\t.3 .1\n\t\t.1 .3\n\t\t0 .5\n  END\n\t', NULL, NULL, NULL, '0', NULL, NULL),
    ('CIRCLE_HALF', '3', '0', NULL, E'TYPE Vector\n  POINTS\n    0 .5\n\t\t.1 .7\n\t\t.3 .9\n\t\t.5 1\n\t\t.7 .9\n\t\t.9 .7\n\t\t1 .5\n\t\t0 .5\n  END\n\n\t', NULL, NULL, NULL, '0', NULL, NULL),
    ('BOSCO', '3', '0', NULL, E'TYPE Vector\n  POINTS\n    .5 1\n    .5 0\n\t\t-99 -99\n\t\t.5 0\n\t\t.3 .1 \n\t\t-99 -99\n\t\t.5 .0\n\t\t.7 .1\n\t\t-99 -99\n\t\t.5 .3\n\t\t.2 .4\n\t\t-99 -99\n\t\t.5 .3\n\t\t.8 .4\n\t\t-99 -99\n\t\t.5 .6\n\t\t.1 .8\n\t\t-99 -99\n\t\t.5 .6\n\t\t.9 .8\n  END\n\t', NULL, NULL, NULL, '0', NULL, NULL),
    ('CIMITERO', '3', '0', NULL, E'TYPE VECTOR\nPOINTS\n.5 0\n.5 1\n-99 -99\n.2 .3\n.8 .3\nEND\n', NULL, NULL, NULL, '0', NULL, NULL),
    ('FRUTTETO', '3', '0', NULL, E'Type VECTOR\n  Filled TRUE\n  Points\n\t\t.2 1\n\t\t.2 .8\n\t\t.4 .8 \n\t\t.4 .4\n\t\t0 0\n\t\t.2 0\n\t\t.4 .2\n\t\t.4 0\n\t\t.6 0\n\t\t.6 .2\n\t\t.8 0\n\t\t1 0\n\t\t.6 .4\n\t\t.6 .8\n\t\t.8 .8\n\t\t.8 1\n\t\t.2 1\n  END\n\t\t', NULL, NULL, NULL, '0', NULL, NULL),
    ('INCOLTO', '3', '0', NULL, E'Type VECTOR\n  Filled TRUE\n  Points\n\t0 1\n\t.2 .6\n\t.35 .85\n\t.5 .6\n\t.65 .85\n\t.8 .6 \n\t1 1\n\t.9 1\n\t.8 .8\n\t.7 1\n\t.6 1\n\t.5 .8\n\t.4 1\n\t.3 1\n\t.2 .8\n\t.1 1\n\t0 1\n  END\n\t\t', NULL, NULL, NULL, '0', NULL, NULL),
    ('PASCOLO', '3', '0', NULL, E'  Type VECTOR\n  Filled TRUE\n  Points\n    0 .4\n\t\t.2 1\n\t\t.4 1\n\t\t.2 .4\n\t\t0 .4\n\t\t-99 -99\n\t\t.4 0\n\t\t.6 0 \n\t\t.6 1\n\t\t.4 1\n\t\t.4 0 \n\t   -99 -99\n\t\t .8 .4\n\t\t 1 .4\n\t\t .8 1\n\t\t .6 1\n\t\t .8 .4\t\n  END\n\t\t', NULL, NULL, NULL, '0', NULL, NULL),
    ('RANDOM', '3', '0', NULL, E'  Type VECTOR\n  Filled TRUE\n  Points\n    .1 .1\n\t\t.3 .3\n  -99 -99\n\t\t.5 .2\n\t\t.7 0\n  -99 -99\n\t\t.9 .2\n  -99 -99\n\t\t.7 .3\n  -99 -99\n\t\t.1 .5\t\t\n  -99 -99\n\t\t.6 .5\n\t\t.4 .7\n  -99 -99\n\t\t.3 .8\n  -99 -99\n\t\t.8 .7\n  -99 -99\n\t\t.1 .9\n  -99 -99\n\t\t.6 .8\n\t\t.6 1\n  END\n\t\t', NULL, NULL, NULL, '0', NULL, NULL),
    ('RISAIA', '3', '0', NULL, E'Type VECTOR\n  Filled TRUE\n  Points\n\t\t0 1\n\t\t0 .4\n\t\t.2 .4\n\t\t.2 1\n\t\t0 1\n\t\t-99 -99\n\t\t.4 1\n\t\t.4 0\n\t\t.6 0\n\t\t.6 1\n\t\t.4 1\n\t\t-99 -99 \n\t\t.8 1\n\t\t.8 .4\n\t\t1 .4\n\t\t1 1\n\t\t.8 1 \n  END\n\t\t', NULL, NULL, NULL, '0', NULL, NULL),
    ('RUPESTRE', '3', '0', NULL, E'  Type VECTOR\n  Filled TRUE\n  Points\n    .2 .8\n    .35 .6\n    .65 .6\n    .8 .8\n   -99 -99\n    0 .6\n    .15 .45\n    .35 .45\n    .5 .6\n    .65 .45\n    .85 .45\n    1 .6\t\t\n  END\n\t\t', NULL, NULL, NULL, '0', NULL, NULL),
    ('CIRCLE_FILL', '3', '0', NULL, E'TYPE ELLIPSE\nFILLED TRUE\nPOINTS\n1 1\nEND\n\t', NULL, NULL, NULL, '0', NULL, NULL),
    ('SQUARE_EMPTY', '3', '0', NULL, E'Type VECTOR\n  Points\n\t.1 .1\n\t.1 .9\n\t.9 .9\n\t.9 .1\n\t.1 .1\n  END', NULL, NULL, NULL, '0', NULL, NULL),
    ('TRIANGLE_EMPTY', '3', '0', NULL, E'Type VECTOR\n  Points\n\t.1 .1\n\t.9 .1\n\t.9 .1\n\t.5 .9\n\t.1 .1\n  END', NULL, NULL, NULL, '0', NULL, NULL),
    ('PLUS_FILL', '3', '0', NULL, E'TYPE VECTOR\nPOINTS\n    .1 .3\n    .5 .3\n    -99 -99\n    .3 .1\n    .3 .5\n    -99 -99\n    .5 .7\n    .9 .7\n    -99 -99\n    .7 .5\n    .7 .9\nEND', NULL, NULL, NULL, '0', NULL, NULL),
    ('SNOW', '3', '0', NULL, E'Type VECTOR\n  Points\n\t0 .5\n\t1 .5\n\t-99 -99\n\t.2 0\n\t.8 1\n\t-99 -99\n\t.8 0\n\t.2 1\n  END\n\t\t', NULL, NULL, NULL, '0', NULL, NULL),
    ('HEXAGON_EMPTY', '3', '0', NULL, E'Type VECTOR\n  Points\n\t.3 .1\n\t.8 .1\n\t1 .5\n\t.8 .9\n\t.3 .9\n\t.1 .5\n\t.3 .1\n  END', NULL, NULL, NULL, '0', NULL, NULL),
    ('HEXAGON_BEE', '3', '0', NULL, E'Type VECTOR\n  Points\n\t.1 0\n\t.2 .2\n\t.1 .4\n\t0 .4\n\t-99 -99\n\t.2 .2\n\t.4 .2\n\t-99 -99\n\t.5 0\n\t.4 .2\n\t.5 .4\n\t.6 .4\n  END\n', NULL, NULL, NULL, '0', NULL, NULL),
    ('ICE', '3', '0', NULL, E'Type VECTOR\n  Points\n\t0 .5\n    .5 1\n\t-99 -99\n\t0 0\n    1 .5\n\t-99 -99\n\t.5 0\n    0 1\n    -99 -99\n    .5 0\n    .5 1\n    -99 -99\n    0 0\n    0 .5\n  END\n', NULL, NULL, NULL, '0', NULL, NULL),
    ('HALF_SQUARE', '3', '0', NULL, E'Type VECTOR\n  Points\n\t.2 1.8\n\t1.8 1.8\n\t1.8 .2\n  END', NULL, NULL, NULL, '0', NULL, NULL),
    ('DASH_DASH', '3', '0', NULL, E'Type VECTOR\n  Points\n\t0 .9 \n\t.3 .9\n\t-99 -99\n\t.7 .9\n\t1 .9\n\t-99 -99\n\t.2 .4 \n\t.8 .4\n  END\n', NULL, NULL, NULL, '0', NULL, NULL),
    ('DASH_DASH_VERTICAL', '3', '0', NULL, E'Type VECTOR\n  Points\n\t.9 0 \n\t.9 .3 \n\t-99 -99\n\t.9 .7 \n\t.9 1 \n\t-99 -99\n\t.4 .2 \n\t.4 .8 \n  END\n', NULL, NULL, NULL, '0', NULL, NULL),
    ('DASH_LINE', '3', '0', NULL, E'Type VECTOR\n  Points\n\t0 .9 \n\t1 .9\n\t-99 -99\n\t.2 .4 \n\t.8 .4\n  END\n', NULL, NULL, NULL, '0', NULL, NULL),
    ('STREAMERS', '3', '0', NULL, E'Type VECTOR\n  Points\n\t.1 .1\n    .4 .1\n\t-99 -99\n\t.9 .1\n    .6 .4\n\t-99 -99\n\t.1 .6 \n    .1 .9 \n    -99 -99\n\t.4 .6\n    .7 .9\n  END\n', NULL, NULL, NULL, '0', NULL, NULL),
    ('POINT_LINE_VERTICAL', '3', '0', NULL, E'Type VECTOR\n  Points\n\t.9 0  \n\t.9 1 \n\t-99 -99\n\t .4 .4\n\t .4 .6\n  END\n', NULL, NULL, NULL, '0', NULL, NULL),
    ('DOUBLE_LINE_VERTICAL', '3', '0', NULL, E'Type VECTOR\n  Points\n    .0 0  \n\t.0 1 \n\t-99 -99\n\t.3 0  \n\t.3 1 \n\t-99 -99\n\t1 0  \n\t1 1 \n  END\n', NULL, NULL, NULL, '0', NULL, NULL),
    ('SQUARE_FILL', '3', '0', NULL, E'Type VECTOR\nFILLED TRUE\n  Points\n\t.1 .1\n\t.1 .9\n\t.9 .9\n\t.9 .1\n\t.1 .1\n  END\n\t\t', NULL, NULL, NULL, '0', NULL, NULL),
    ('RIPARIE-CANNETO', '3', '0', NULL, E'TYPE VECTOR\nPOINTS\n.3 0\n.3 1\n.7 1\nEND\n ', NULL, NULL, NULL, '0', NULL, NULL),
    ('VERTEX', '3', '0', NULL, E'TYPE VECTOR\nFILLED TRUE\nPOINTS\n\t1 8\n\t3 8\n\t3 9\n\t1 9\n\t1 8\n-99 -99\n\t7 8\n\t9 8\n\t9 9\n\t7 9\n\t7 8\n-99 -99\n\t4 1\n\t6 1\n\t6 2\n\t4 2\n\t4 1\nEND', NULL, NULL, NULL, '0', NULL, NULL),
    ('T', '3', '0', NULL, E'TYPE VECTOR\nPOINTS\n.5 .5\n.5 1\t\n-99 -99\n0 .5\n1 .5\nEND', NULL, NULL, NULL, '0', NULL, NULL),
    ('DOUBLE_T', '3', '0', NULL, E'TYPE VECTOR\nPOINTS\n.3 .5\n.3 1\t\n-99 -99\n.7 .5\n.7 1\t\n-99 -99\n0 .5\n1 .5\nEND', NULL, NULL, NULL, '0', NULL, NULL),
    ('D', '3', '0', NULL, E'TYPE VECTOR\n FILLED TRUE\nPOINTS\n.5 0\n.5 1\n.3 .9\n.1 .7\n0 .5\n.1 .3\n.3 .1\n.5 0\nEND', NULL, NULL, NULL, '0', NULL, NULL),
    ('MONUMENTO', '3', '0', NULL, E'TYPE VECTOR\nPOINTS\n.5 1\n.2 .3\n.2 .2\n.4 0\n.6 0\n.6 .2\n.6 .3\n.5 1\nEND', NULL, NULL, NULL, '0', NULL, NULL),
    ('VERTICAL', '4', '0', NULL, E'TYPE VECTOR\nPOINTS\n.5 0\n.5 1\nEND', NULL, NULL, NULL, '0', NULL, NULL),
    ('HORIZONTAL', '4', '0', NULL, E'TYPE VECTOR\nPOINTS\n0 .5\n1 .5\nEND', NULL, NULL, NULL, '0', NULL, NULL),
    ('SQUARE_HALF', '1', '0', NULL, E'TYPE VECTOR\nFILLED TRUE\nPOINTS\n0 0\n0 1\n1 0\n0 0\nEND', NULL, NULL, NULL, '0', NULL, NULL),
    ('IDRANTE', '1', '0', NULL, E'TYPE VECTOR\nFILLED TRUE\nPOINTS\n0 1\n1 1\n-99 -99\n.2 1\n.2 .4\n.8 .4\n.8 1\n.2 1\n-99 -99\n.2 .8\n0 .8\n0 .6\n.2 .6\n-99 -99\n.8 .8\n1 .8\n1 .6\n.8 .6\n-99 -99\n0 .4\n1 .4\n.9 .2\n.7 0\n.3 0\n.1 .2\n0 .4\nEND', NULL, NULL, NULL, '0', NULL, NULL),
    ('CONN.T', '6', '0', NULL, E'TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#065;"', NULL, NULL, NULL, '0', NULL, NULL),
    ('SARACINESCA', '6', '0', NULL, E'TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#066;"', NULL, NULL, NULL, '0', NULL, NULL),
    ('SALDATURA', '6', '0', NULL, E'TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#067;"', NULL, NULL, NULL, '0', NULL, NULL),
    ('RIDUTTORE', '6', '0', NULL, E'TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#068;"', NULL, NULL, NULL, '0', NULL, NULL),
    ('ALLACCIAMENTO', '6', '0', NULL, E'TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#069;"', NULL, NULL, NULL, '0', NULL, NULL),
    ('ARCO', '6', '0', NULL, E'TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#070;"', NULL, NULL, NULL, '0', NULL, NULL),
    ('VALVOLA', '6', '0', NULL, E'TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#072;"', NULL, NULL, NULL, '0', NULL, NULL),
    ('TAPPO', '6', '0', NULL, E'TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#073;"', NULL, NULL, NULL, '0', NULL, NULL),
    ('POZZETTO ISP', '6', '0', NULL, E'TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#074;"', NULL, NULL, NULL, '0', NULL, NULL),
    ('IDRANT', '6', '0', NULL, E'TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#075;"', NULL, NULL, NULL, '0', NULL, NULL),
    ('GIUNTO', '6', '0', NULL, E'TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#076;"', NULL, NULL, NULL, '0', NULL, NULL),
    ('CONTATORE', '6', '0', NULL, E'TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#077;"', NULL, NULL, NULL, '0', NULL, NULL),
    ('GENERICO', '6', '0', NULL, E'TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#078;"', NULL, NULL, NULL, '0', NULL, NULL),
    ('VUOTO', '6', '0', NULL, E'TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#079;"', NULL, NULL, NULL, '0', NULL, NULL),
    ('CENTRALINA', '6', '0', NULL, E'TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#080;"', NULL, NULL, NULL, '0', NULL, NULL);


--
-- Data for Name: form_level; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.form_level (id, level, mode, form, order_fld, visible) VALUES
    ('520', '27', '3', '213', '1', '1'),
    ('521', '28', '1', '214', '1', '1'),
    ('1', '1', '3', '2', '1', '1'),
    ('2', '2', '0', '3', '1', '1'),
    ('5', '2', '3', '5', '8', '1'),
    ('7', '2', '1', '7', '1', '1'),
    ('8', '2', '2', '6', '1', '1'),
    ('14', '2', '3', '12', '3', '1'),
    ('15', '6', '1', '13', '1', '1'),
    ('16', '6', '2', '13', '1', '1'),
    ('17', '6', '0', '13', '1', '1'),
    ('19', '8', '0', '26', '1', '1'),
    ('20', '8', '1', '27', '1', '1'),
    ('21', '8', '2', '28', '1', '1'),
    ('22', '5', '0', '9', '1', '1'),
    ('23', '5', '1', '10', '1', '1'),
    ('24', '5', '2', '11', '1', '1'),
    ('25', '5', '3', '30', '3', '1'),
    ('26', '10', '0', '31', '1', '1'),
    ('27', '10', '1', '32', '1', '1'),
    ('28', '10', '2', '33', '1', '1'),
    ('29', '10', '3', '34', '3', '1'),
    ('30', '11', '0', '35', '1', '1'),
    ('31', '11', '1', '36', '1', '1'),
    ('32', '11', '2', '37', '1', '1'),
    ('34', '12', '0', '39', '1', '1'),
    ('35', '12', '1', '40', '1', '1'),
    ('36', '12', '2', '41', '2', '1'),
    ('37', '12', '3', '42', '3', '1'),
    ('38', '14', '0', '43', '1', '1'),
    ('39', '14', '1', '44', '1', '1'),
    ('40', '14', '2', '45', '1', '1'),
    ('46', '7', '0', '51', '1', '1'),
    ('47', '7', '1', '52', '1', '1'),
    ('48', '7', '2', '53', '1', '1'),
    ('54', '16', '0', '59', '1', '1'),
    ('55', '16', '1', '60', '1', '1'),
    ('56', '16', '2', '61', '1', '1'),
    ('57', '17', '0', '63', '1', '1'),
    ('58', '17', '1', '64', '1', '1'),
    ('59', '17', '2', '65', '1', '1'),
    ('63', '2', '3', '70', '7', '1'),
    ('64', '9', '0', '72', '1', '1'),
    ('65', '9', '1', '73', '1', '1'),
    ('66', '9', '2', '74', '1', '1'),
    ('77', '8', '3', '84', '6', '1'),
    ('78', '22', '1', '85', '1', '1'),
    ('98', '2', '3', '105', '6', '1'),
    ('99', '27', '1', '106', '1', '1'),
    ('101', '27', '0', '107', '1', '1'),
    ('127', '33', '1', '134', '15', '1'),
    ('131', '2', '3', '133', '15', '1'),
    ('132', '27', '2', '106', '1', '1'),
    ('164', '1', '3', '16', '3', '1'),
    ('165', '4', '0', '18', '1', '1'),
    ('166', '4', '1', '18', '1', '1'),
    ('167', '4', '2', '18', '1', '1'),
    ('168', '1', '3', '20', '2', '1'),
    ('169', '3', '0', '23', '1', '1'),
    ('170', '3', '1', '23', '1', '1'),
    ('171', '3', '2', '23', '1', '1'),
    ('176', '46', '1', '152', '1', '1'),
    ('79', '22', '-1', '86', '2', '1'),
    ('69', '16', '1', '75', '2', '0'),
    ('100', '27', '2', '105', '2', '0'),
    ('33', '11', '3', '38', '3', '1'),
    ('51', '11', '3', '58', '4', '1'),
    ('52', '11', '3', '62', '5', '1'),
    ('200', '11', '0', '170', '7', '1'),
    ('201', '47', '1', '171', '1', '1'),
    ('202', '47', '3', '171', '1', '1'),
    ('203', '47', '2', '171', '1', '1'),
    ('504', '48', '0', '203', '1', '1'),
    ('505', '48', '1', '203', '1', '1'),
    ('506', '48', '2', '203', '1', '1'),
    ('507', '2', '3', '202', '1', '1'),
    ('508', '49', '0', '205', '1', '1'),
    ('509', '49', '1', '205', '1', '1'),
    ('510', '49', '2', '205', '1', '1'),
    ('513', '50', '1', '207', '1', '1'),
    ('515', '51', '0', '209', '1', '1'),
    ('516', '51', '1', '209', '1', '1'),
    ('517', '51', '2', '209', '1', '1'),
    ('518', '17', '0', '210', '1', '1'),
    ('519', '52', '1', '211', '1', '1'),
    ('53', '11', '3', '66', '6', '1'),
    ('60', '19', '0', '67', '1', '1'),
    ('61', '19', '1', '68', '1', '1'),
    ('62', '19', '1', '69', '2', '1'),
    ('175', '4', '3', '151', '2', '1'),
    ('163', '27', '3', '151', '1', '0'),
    ('511', '1', '3', '204', '4', '0'),
    ('512', '11', '3', '206', '8', '0'),
    ('514', '3', '3', '208', '3', '0'),
    ('4', '2', '3', '8', '4', '1'),
    ('45', '2', '3', '50', '5', '1'),
    ('522', '8', '0', '215', '10', '0'),
    ('523', '53', '1', '216', '1', '1');



--
-- Data for Name: user_group; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--




--
-- Data for Name: users; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

INSERT INTO gisclient_34.users (username, pwd, enc_pwd, data_creazione, data_scadenza, data_modifica, attivato, ultimo_accesso, cognome, nome, macaddress, ip, host, controllo, userdata, email) VALUES
    ('admin', NULL, '21232f297a57a5a743894a0e4a801fc3', NULL, NULL, '2024-09-16', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);


