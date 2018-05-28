CREATE TABLE gisclient_3.group_properties(id integer NOT NULL, key character varying NOT NULL, value character varying NOT NULL,  groupname character varying NOT NULL);
ALTER TABLE gisclient_3.group_properties ADD PRIMARY KEY (id);
ALTER TABLE gisclient_3.group_properties ADD CONSTRAINT group_properties_unique UNIQUE (key, groupname);
ALTER TABLE gisclient_3.group_properties ADD FOREIGN KEY ("groupname") REFERENCES gisclient_3.groups (groupname) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE gisclient_3.group_properties ADD CONSTRAINT no_empty_property CHECK (trim(key) <> '' AND trim(value) <> '');

INSERT INTO gisclient_3.e_form(id,name,config_file, tab_type, level_destination, form_destination, save_data, parent_level,table_name) VALUES(228, 'group_properties', 'group_properties',0, 55, '','',3,'');
INSERT INTO gisclient_3.e_form(id,name,config_file, tab_type, level_destination, form_destination, save_data, parent_level,table_name) VALUES(229, 'group_properties', 'group_properties',1, 55, '','',3,'');
INSERT INTO gisclient_3.e_form(id,name,config_file, tab_type, level_destination, form_destination, save_data, parent_level,table_name) VALUES(230, 'group_properties', 'group_properties',1, 55, '','',3,'');

INSERT INTO gisclient_3.e_level(id, name, parent_name, parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES(55, 'group_properties', 'groups', 3, 1, 1, 0, 3, 'group_properties', 1);

INSERT INTO gisclient_3.form_level(id, level, mode, form, order_fld, visible) VALUES(538, 55, 0, 230, 1, 1);
INSERT INTO gisclient_3.form_level(id, level, mode, form, order_fld, visible) VALUES (539, 55, 1, 229, 1, 1);
INSERT INTO gisclient_3.form_level(id, level, mode, form, order_fld, visible) VALUES (540, 55, 2, 229, 1, 1);
INSERT INTO gisclient_3.form_level(id, level, mode, form, order_fld, visible) VALUES (541, 3, 0, 228, 1, 1);

