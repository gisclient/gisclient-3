INSERT INTO gisclient_3.e_level(
	id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id)
	VALUES (56,'qt_field_groups','qt_field_groups',1,53,5,1,0,53,'qt_field_groups',2);
INSERT INTO gisclient_3.e_form(
	id, name, config_file, tab_type, level_destination, save_data, parent_level)
	VALUES (231,'qt_field_groups','qt_field_groups',4,56,'qt_field_groups',53);
INSERT INTO gisclient_3.e_form(
	id, name, config_file, tab_type, level_destination, save_data, parent_level)
	VALUES (232,'qt_field_groups','qt_field_groups',5,56,'qt_field_groups',53);
INSERT INTO gisclient_3.e_form(
	id, name, config_file, tab_type, level_destination, save_data, parent_level)
	VALUES (233,'qt_field_groups','qt_field_groups',0,56,'qt_field_groups',53);
INSERT INTO gisclient_3.form_level(
	id, level, mode, form, order_fld, visible)
	VALUES (542, 53, 0, 231, 1, 1);
INSERT INTO gisclient_3.form_level(
	id, level, mode, form, order_fld, visible)
	VALUES (543, 56, 1, 232, 1, 1);

CREATE TABLE IF NOT EXISTS gisclient_3.qt_field_groups
(
    qt_field_id integer NOT NULL,
    groupname character varying COLLATE pg_catalog."default" NOT NULL,
    CONSTRAINT qt_field_groups_pkey PRIMARY KEY (qt_field_id, groupname),
    CONSTRAINT qt_field_groups_qt_field_id_fkey FOREIGN KEY (qt_field_id)
        REFERENCES gisclient_3.qt_field (qt_field_id) MATCH FULL
        ON UPDATE CASCADE
        ON DELETE CASCADE
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS gisclient_3.qt_field_groups
    OWNER to postgres;

GRANT SELECT ON TABLE gisclient_3.qt_field_groups TO mapserver;

GRANT ALL ON TABLE gisclient_3.qt_field_groups TO postgres;
