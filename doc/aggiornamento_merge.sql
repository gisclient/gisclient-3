--###############################################
--pg_dump -f gc32.sql -n gisclient_32 mydb
--cat gc32.sql | sed 's/gisclient_32/gisclient_3/' > gc33.sql
--psql -f gc33.sql mydb
--psql -f aggiornamento_merge.sql mydb
--###############################################

SET search_path = gisclient_3, pg_catalog;

-- RENAME DI qt* 

ALTER TABLE qtfield RENAME TO field;
ALTER TABLE field DROP CONSTRAINT qtfield_fieldtype_id_fkey;
ALTER TABLE field DROP CONSTRAINT qtfield_layer_id_fkey;
ALTER TABLE field ADD CONSTRAINT field_fieldtype_id_fkey FOREIGN KEY (fieldtype_id)
      REFERENCES e_fieldtype (fieldtype_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE field
  ADD CONSTRAINT field_layer_id_fkey FOREIGN KEY (layer_id)
      REFERENCES layer (layer_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE field RENAME qtfield_id  TO field_id;
ALTER TABLE field RENAME qtrelation_id  TO relation_id;
ALTER TABLE field RENAME qtfield_name  TO field_name;
ALTER TABLE field RENAME qtfield_order  TO field_order;

ALTER TABLE field DROP CONSTRAINT qtfield_pkey CASCADE;
ALTER TABLE field ADD CONSTRAINT field_pkey PRIMARY KEY(field_id);

ALTER TABLE field DROP  CONSTRAINT IF EXISTS qtfield_qtfield_name_layer_id_key ;
ALTER TABLE field
  ADD CONSTRAINT field_field_name_layer_id_key UNIQUE(field_name, relation_id, layer_id);

ALTER TABLE field DROP CONSTRAINT qtfield_qtrelation_id_check;
ALTER TABLE field
  ADD CONSTRAINT field_relation_id_check CHECK (relation_id >= 0);

CREATE INDEX fki_field_fieldtype_id_fkey
  ON field USING btree (fieldtype_id);  
DROP INDEX fki_qtfield_fieldtype_id_fkey;

ALTER TABLE qtfield_groups RENAME TO field_groups;
ALTER TABLE field_groups RENAME qtfield_id  TO field_id;

ALTER TABLE field_groups ADD CONSTRAINT field_groups_field_id_fkey FOREIGN KEY (field_id)
      REFERENCES field (field_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE; 
      
ALTER TABLE field_groups DROP CONSTRAINT qtfield_groups_pkey;
ALTER TABLE field_groups ADD CONSTRAINT field_groups_pkey PRIMARY KEY(field_id, groupname);

ALTER TABLE e_qtrelationtype RENAME TO e_relationtype;
ALTER TABLE e_relationtype RENAME qtrelationtype_id  TO relationtype_id;
ALTER TABLE e_relationtype RENAME qtrelationtype_name  TO relationtype_name;
ALTER TABLE e_relationtype RENAME qtrelationtype_order  TO relationtype_order;
ALTER TABLE e_relationtype DROP CONSTRAINT e_qtrelationtype_pkey;
ALTER TABLE e_relationtype ADD CONSTRAINT e_relationtype_pkey PRIMARY KEY(relationtype_id);

DROP VIEW seldb_qtrelationtype;
CREATE OR REPLACE VIEW seldb_relationtype AS 
 SELECT relationtype_id AS id, relationtype_name AS opzione
   FROM e_relationtype ;

ALTER TABLE qtrelation RENAME TO relation;
ALTER TABLE relation DROP CONSTRAINT qtrelation_catalog_fkey;
ALTER TABLE relation DROP CONSTRAINT qtrelation_layer_id_fkey;
ALTER TABLE relation ADD CONSTRAINT relation_catalog_fkey FOREIGN KEY (catalog_id)
      REFERENCES catalog (catalog_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE relation ADD CONSTRAINT relation_layer_id_fkey FOREIGN KEY (layer_id)
      REFERENCES layer (layer_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE relation RENAME qtrelation_id  TO relation_id;
ALTER TABLE relation DROP CONSTRAINT qtrelation_pkey;
ALTER TABLE relation ADD CONSTRAINT relation_pkey PRIMARY KEY(relation_id);

ALTER TABLE relation RENAME qtrelation_name  TO relation_name;
ALTER TABLE relation DROP CONSTRAINT qtrelation_name_lower_case;
ALTER TABLE relation ADD CONSTRAINT relation_name_lower_case CHECK (relation_name::text = lower(relation_name::text));

ALTER TABLE relation DROP CONSTRAINT qtrelation_table_name_lower_case;
ALTER TABLE relation ADD CONSTRAINT relation_table_name_lower_case CHECK (table_name::text = lower(table_name::text));
ALTER TABLE relation RENAME qtrelationtype_id  TO relationtype_id;

DROP INDEX fki_qtrelation_catalog_id_fkey;
CREATE INDEX fki_relation_catalog_id_fkey  ON relation  USING btree  (catalog_id);

DROP FUNCTION delete_qtrelation() CASCADE;
CREATE OR REPLACE FUNCTION delete_relation()
  RETURNS trigger AS
$BODY$
BEGIN
	delete from field where relation_id=old.relation_id;
	return old;
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;

CREATE TRIGGER delete_relation
  AFTER DELETE
  ON relation
  FOR EACH ROW
  EXECUTE PROCEDURE delete_relation();

DROP VIEW seldb_qtrelation;
CREATE OR REPLACE VIEW seldb_relation AS 
         SELECT 0 AS id, 'layer'::character varying AS opzione, 0 AS layer_id
UNION 
         SELECT relation_id AS id, relation_name AS opzione, layer_id
           FROM relation;

           

DROP VIEW vista_qtfield;
CREATE OR REPLACE VIEW vista_field AS 
 SELECT field.field_id AS field_id, field.layer_id, field.fieldtype_id, x.relation_id, field.field_name AS field_name, field.resultype_id, field.field_header, field.field_order AS field_order, COALESCE(field.column_width, 0) AS column_width, x.name AS relation_name, x.relationtype_id, x.relationtype_name, field.editable
   FROM field
   JOIN e_fieldtype USING (fieldtype_id)
   JOIN ( SELECT y.relationtype_id, y.relation_id, y.name, z.relationtype_name
      FROM (         SELECT 0 AS relation_id, 'Data Layer'::character varying AS name, 0 AS relationtype_id
           UNION 
                    SELECT relation.relation_id AS relation_id, COALESCE(relation.relation_name, 'Nessuna Relazione'::character varying) AS name, relation.relationtype_id AS relationtype_id
                      FROM relation relation) y
   JOIN (         SELECT 0 AS relationtype_id, ''::character varying AS relationtype_name
           UNION 
                    SELECT e_relationtype.relationtype_id, e_relationtype.relationtype_name
                      FROM e_relationtype) z USING (relationtype_id)) x USING (relation_id)
  ORDER BY field.field_id, x.relation_id, x.relationtype_id;
  

ALTER TABLE qtlink RENAME TO layer_link;

ALTER TABLE layer_link DROP CONSTRAINT qt_link_pkey;

ALTER TABLE layer_link
  DROP CONSTRAINT IF EXISTS qt_link_link_id_fkey;
ALTER TABLE layer_link
  DROP CONSTRAINT IF EXISTS qtlink_layer_id_fkey;
ALTER TABLE layer_link
  DROP CONSTRAINT  IF EXISTS qtlink_link_id_fkey;
ALTER TABLE layer_link
  ADD CONSTRAINT layer_link_pkey PRIMARY KEY(layer_id, link_id);
--ALTER TABLE layer_link
--  ADD CONSTRAINT layer_link_link_id_fkey FOREIGN KEY (link_id)
--      REFERENCES link (link_id) MATCH FULL
--      ON UPDATE CASCADE ON DELETE CASCADE;
--ALTER TABLE layer_link
--  ADD CONSTRAINT layerlink_layer_id_fkey FOREIGN KEY (layer_id)
--      REFERENCES layer (layer_id) MATCH FULL
--      ON UPDATE CASCADE ON DELETE CASCADE;

DROP INDEX if exists fki_qt_link_link_id_fkey;
CREATE INDEX fki_layer_link_link_id_fkey ON layer_link USING btree (link_id);
DROP TABLE if exists qt CASCADE;

ALTER TABLE relation ADD COLUMN relation_title character varying;


-- RIPRISTINO qt_* PER REPORTISTICA

CREATE TABLE qt
(
  qt_id integer NOT NULL,
  theme_id integer NOT NULL,
  layer_id integer NOT NULL,
  qt_name character varying NOT NULL,
  max_rows smallint DEFAULT 25,
  papersize_id integer,
  edit_url character varying,
  groupobject integer DEFAULT 0,
  selection_color character varying,
  qt_order smallint DEFAULT 0,
  qtresultype_id integer,
  qt_filter character varying,
  zoom_buffer integer,
  qt_title character varying,
  CONSTRAINT qt_pkey PRIMARY KEY (qt_id)
);

CREATE INDEX fki_qt_layer_id_fkey ON qt USING btree (layer_id);

CREATE TABLE qt_relation
(
  qtrelation_id integer NOT NULL,
  qt_id integer NOT NULL,
  catalog_id integer NOT NULL,
  qtrelation_name character varying NOT NULL,
  qtrelationtype_id integer NOT NULL DEFAULT 1,
  data_field_1 character varying NOT NULL,
  data_field_2 character varying,
  data_field_3 character varying,
  table_name character varying NOT NULL,
  table_field_1 character varying NOT NULL,
  table_field_2 character varying,
  table_field_3 character varying,
  language_id character varying(2),
  CONSTRAINT qtrelation_pkey PRIMARY KEY (qtrelation_id),
  CONSTRAINT qtrelation_catalog_fkey FOREIGN KEY (catalog_id)
      REFERENCES catalog (catalog_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT qtrelation_qt_id_fkey FOREIGN KEY (qt_id)
      REFERENCES qt (qt_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX fki_qtrelation_catalog_id_fkey ON qt_relation USING btree (catalog_id);
CREATE INDEX fki_qtrelation_qt_id_fkey ON qt_relation USING btree (qt_id);

CREATE TABLE qt_field
(
  qtfield_id integer NOT NULL,
  qt_id integer NOT NULL,
  qtrelation_id integer NOT NULL DEFAULT 0,
  qtfield_name character varying NOT NULL,
  field_header character varying NOT NULL,
  fieldtype_id smallint NOT NULL DEFAULT 1,
  searchtype_id smallint NOT NULL DEFAULT 1,
  resultype_id smallint NOT NULL DEFAULT 3,
  field_format character varying,
  column_width integer,
  orderby_id integer NOT NULL DEFAULT 0,
  field_filter integer NOT NULL DEFAULT 0,
  datatype_id smallint NOT NULL DEFAULT 1,
  qtfield_order smallint NOT NULL DEFAULT 0,
  default_op character varying,
  editable numeric(1,0) DEFAULT 0,
  formula character varying,
  lookup_table character varying,
  lookup_id character varying,
  lookup_name character varying,
  filter_field_name character varying,
  CONSTRAINT qtfield_pkey PRIMARY KEY (qtfield_id),
  CONSTRAINT qtfield_fieldtype_id_fkey FOREIGN KEY (fieldtype_id)
      REFERENCES e_fieldtype (fieldtype_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT qtfield_qt_id_fkey FOREIGN KEY (qt_id)
      REFERENCES qt (qt_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT qtfield_qt_id_key UNIQUE (qt_id, field_header),
  CONSTRAINT qtfield_qtrelation_id_check CHECK (qtrelation_id >= 0)
);

CREATE INDEX fki_qtfield_fieldtype_id_fkey ON qt_field USING btree (fieldtype_id);


CREATE OR REPLACE VIEW seldb_qt AS 
         SELECT (-1) AS id, 'Seleziona ====>'::character varying AS opzione, ''::character varying AS mapset_name
UNION ALL 
         SELECT qt.qt_id AS id, qt.qt_name AS opzione, mapset_layergroup.mapset_name
           FROM qt qt
      LEFT JOIN layer USING (layer_id)
   LEFT JOIN mapset_layergroup USING (layergroup_id);


CREATE OR REPLACE VIEW seldb_qt_relation AS 
         SELECT 0 AS id, 'layer'::character varying AS opzione, 0 AS qt_id
UNION ALL
         SELECT qtrelation_id AS id, qtrelation_name AS opzione, qt_id
           FROM qt_relation;


CREATE OR REPLACE VIEW seldb_qt_relationtype AS 
 SELECT relationtype_id AS id, relationtype_name AS opzione
   FROM e_relationtype;


CREATE OR REPLACE VIEW vista_qtfield AS 
 SELECT qtfield_id, qt_id, fieldtype_id, x.qtrelation_id, qtfield_name, field_header, qtfield_order, COALESCE(column_width, 0) AS column_width, x.name AS qtrelation_name, x.qtrelationtype_id, x.qtrelationtype_name
   FROM qt_field
   JOIN e_fieldtype USING (fieldtype_id)
   JOIN ( SELECT y.qtrelationtype_id, y.qtrelation_id, y.name, z.qtrelationtype_name
      FROM (         SELECT 0 AS qtrelation_id, 'Data Layer'::character varying AS name, 0 AS qtrelationtype_id
           UNION ALL
                    SELECT qtrelation_id, COALESCE(qtrelation_name, 'Nessuna Relazione'::character varying) AS name, qtrelationtype_id
                      FROM qt_relation) y
   JOIN (         SELECT 0 AS qtrelationtype_id, ''::character varying AS qtrelationtype_name
           UNION ALL
                    SELECT relationtype_id, relationtype_name
                      FROM e_relationtype) z USING (qtrelationtype_id)) x USING (qtrelation_id)
  ORDER BY qtfield_id, x.qtrelation_id, x.qtrelationtype_id;




CREATE TABLE qt_link
(
  qt_id integer NOT NULL,
  link_id integer NOT NULL,
  resultype_id smallint,
  CONSTRAINT qt_link_pkey PRIMARY KEY (qt_id, link_id),
  CONSTRAINT qt_link_link_id_fkey FOREIGN KEY (link_id)
      REFERENCES link (link_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT qt_link_qt_id_fkey FOREIGN KEY (qt_id)
      REFERENCES qt (qt_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT qt_link_qt_id_key UNIQUE (qt_id, link_id, resultype_id)
);
CREATE INDEX fki_qt_link_link_id_fkey ON qt_link USING btree (link_id);
CREATE INDEX fki_qt_link_qt_id_fkey ON qt_link USING btree (qt_id);



-- MAPPROXY
ALTER TABLE project_srs ADD COLUMN max_extent character varying;
ALTER TABLE project_srs ADD COLUMN resolutions character varying;


CREATE OR REPLACE VIEW seldb_mapset_srid AS 
         SELECT 3857 AS id, 3857 AS opzione, project.project_name, NULL::character varying AS max_extent, NULL::character varying AS resolutions
           FROM project
UNION ALL 
        ( SELECT project_srs.srid AS id, project_srs.srid AS opzione, project_srs.project_name, project_srs.max_extent, project_srs.resolutions
           FROM project_srs
          ORDER BY project_srs.srid);



-- mapset unico come tiles 

ALTER TABLE project ADD COLUMN legend_font_size integer DEFAULT 8;
ALTER TABLE mapset ADD COLUMN mapset_tiles integer DEFAULT 0;

CREATE OR REPLACE VIEW seldb_mapset_tiles AS 
         SELECT 0 AS id, 'NO TILES'::character varying AS opzione
UNION ALL 
         SELECT e_owstype.owstype_id AS id, e_owstype.owstype_name AS opzione
           FROM e_owstype
          WHERE e_owstype.owstype_id = ANY (ARRAY[2, 3]);

---

DELETE FROM e_owstype;
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (1, 'WMS', 1);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (2, 'WMTS', 2);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (3, 'WMS (tiles in cache di mapproxy)', 3);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (4, 'Yahoo', 3);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (5, 'OSM', 5);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (6, 'TMS', 6);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (7, 'Google', 4);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (8, 'Bing', 6);


update layergroup set owstype_id=2 where owstype_id=9;

---
ALTER TABLE layer RENAME searchable  TO searchable_id;

CREATE TABLE e_searchable
(
  searchable_id smallint NOT NULL,
  searchable_name character varying NOT NULL,
  searchable_order smallint,
  CONSTRAINT e_searchable_pkey PRIMARY KEY (searchable_id)
);

INSERT INTO e_searchable values (0,'Non ricercabile',0);
INSERT INTO e_searchable values (1,'Visualizzato in ricerca',1);
INSERT INTO e_searchable values (2,'Solo ricerca veloce',2);

CREATE OR REPLACE VIEW seldb_searchable AS 
SELECT searchable_id AS id, searchable_name AS opzione
FROM e_searchable;



--pulizia
DROP TABLE IF EXISTS  classgroup CASCADE;
ALTER TABLE project_srs DROP CONSTRAINT project_srs_pkey ;
ALTER TABLE project_srs ADD CONSTRAINT  project_srs_pkey PRIMARY KEY(project_name, srid);
ALTER TABLE project_srs DROP COLUMN custom_srid;
DROP TABLE symbol_ttf CASCADE;
DROP TABLE IF EXISTS tb_import CASCADE;
DROP TABLE IF EXISTS tb_import_table CASCADE;
DROP TABLE IF EXISTS tb_logs CASCADE;

-- RICREA E-lEVEL E FORM


-- STRUTTURA DELLE PAGINE
DELETE FROM e_level;
ALTER TABLE e_level DROP CONSTRAINT if exists e_level_parent_id_fkey;
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (53, 'qtfield', 'qtfield', 1, 18, 5, 1, 4, 18, 'qtfield', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (18, 'qt', 'qt', 12, 11, 4, 0, 3, 11, 'qt', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (1, 'root', NULL, 1, NULL, NULL, 0, 0, NULL, NULL, 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (17, 'field', 'field', 11, 11, 4, 1, 2, 11, 'field', 2);
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
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (22, 'mapset_layergroup', 'mapset_layergroup', 17, 8, 2, 1, 1, 8, 'mapset_layergroup', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (27, 'selgroup', 'selgroup', NULL, 2, 1, 0, 8, 2, 'selgroup', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (33, 'project_admin', 'project_admin', 15, 2, 1, 1, 0, 2, 'project_admin', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (45, 'group_users', 'user_groups', NULL, 4, 1, 1, 0, 4, 'user_group', 1);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (32, 'user_project', 'project', 8, 2, 1, 1, 0, 2, 'user_project', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (46, 'user_groups', 'group_users', NULL, 3, 1, 1, 0, 3, 'user_group', 1);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (47, 'layer_groups', 'layer_groups', NULL, 11, 4, 1, 0, 11, 'layer_groups', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (48, 'project_languages', 'project', NULL, 2, 1, 1, 1, 2, 'project_languages', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (49, 'authfilter', 'authfilter', 8, 1, 0, 1, 0, 1, 'authfilter', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (50, 'layer_authfilter', 'layer', 15, 11, 4, 1, 1, 11, 'layer_authfilter', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (51, 'group_authfilter', 'groups', 1, 3, 1, 1, 0, 3, 'group_authfilter', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (19, 'qtlink', 'layer', 12, 11, 4, 1, 0, 11, 'qtlink', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (28, 'selgroup_layer', 'selgroup_layer', NULL, 27, 2, 1, 1, 27, 'selgroup_layer', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (16, 'relation', 'relation', 10, 11, 4, 1, 1, 11, 'relation', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (52, 'field_groups', 'field', 1, 17, 5, 1, 0, 17, 'field_groups', 2);

INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (90, 'qt', 'qt', 0, 18, NULL, NULL, 5, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (91, 'qt', 'qt', 1, 18, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (92, 'qt', 'qt', 1, 18, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (93, 'qt', 'qt', 2, 18, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (220, 'qtfield', 'qtfield', 0, 53, NULL, NULL, 18, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (221, 'qtfield', 'qtfield', 1, 53, NULL, NULL, 18, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (222, 'qtfield', 'qtfield', 1, 53, NULL, NULL, 18, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (223, 'qtfield', 'qtfield', 2, 53, NULL, NULL, 18, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (213, 'selgroup_layer', 'selgroup_layer', 4, 28, NULL, 'selgroup_layer', 27, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (214, 'selgroup_layer', 'selgroup_layer', 5, 28, NULL, 'selgroup_layer', 27, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (16, 'user', 'user', 0, 4, NULL, 'user', 2, NULL, 'user', NULL);
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
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (75, 'relation', 'relation_addnew', 0, 16, NULL, NULL, 13, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (30, 'layergroup', 'layergroup', 0, 10, NULL, 'layergroup', 5, NULL, NULL, 'layergroup_order,layergroup_title');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (31, 'layergroup', 'layergroup', 1, 10, NULL, 'layergroup', 5, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (32, 'layergroup', 'layergroup', 1, 10, NULL, 'layergroup', 5, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (33, 'layergroup', 'layergroup', 2, 10, NULL, 'layergroup', 5, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (84, 'map_layer', 'mapset_layergroup', 4, 22, NULL, 'mapset_layergroup', 8, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (85, 'map_layer', 'mapset_layergroup', 5, 22, NULL, 'mapset_layergroup', 8, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (86, 'map_layer', 'mapset_layergroup', 0, 22, NULL, 'mapset_layergroup', 8, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (170, 'layer_groups', 'layer_groups', 4, 47, NULL, 'layer_groups', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (171, 'layer_groups', 'layer_groups', 5, 47, NULL, 'layer_groups', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (202, 'project_languages', 'project_languages', 0, 48, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (203, 'project_languages', 'project_languages', 1, 48, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (204, 'authfilter', 'authfilter', 0, 49, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (205, 'authfilter', 'authfilter', 1, 49, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (206, 'layer_authfilter', 'layer_authfilter', 4, 50, NULL, 'layer_authfilter', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (207, 'layer_authfilter', 'layer_authfilter', 5, 50, NULL, 'layer_authfilter', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (208, 'group_authfilter', 'group_authfilter', 0, 51, NULL, NULL, 3, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (209, 'group_authfilter', 'group_authfilter', 1, 51, NULL, NULL, 3, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (66, 'qtlink', 'qtlink', 2, 19, NULL, 'qtlink', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (67, 'qtlink', 'qtlink', 0, 19, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (68, 'qtlink', 'qtlink', 1, 19, NULL, 'qtlink', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (69, 'qtlink', 'qtlink', 110, 19, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (20, 'group', 'group', 0, 3, NULL, 'group', 2, NULL, 'group', NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (18, 'user', 'user', 50, 4, NULL, 'user', 2, NULL, 'user', NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (58, 'relation', 'relation', 0, 16, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (59, 'relation', 'relation', 1, 16, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (60, 'relation', 'relation', 1, 16, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (61, 'relation', 'relation', 2, 16, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (63, 'fields', 'field', 1, 17, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (64, 'fields', 'field', 1, 17, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (65, 'fields', 'field', 2, 17, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (62, 'fields', 'field', 0, 17, NULL, NULL, 11, NULL, NULL, 'relationtype_id,relation_name,field_header,field_name');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (210, 'field_groups', 'field_groups', 4, 52, NULL, 'field_groups', 17, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (211, 'field_groups', 'field_groups', 5, 52, NULL, 'field_groups', 17, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (212, 'field_groups', 'field_groups', 0, 52, NULL, 'field_groups', 17, NULL, NULL, NULL);

INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (523, 18, 0, 91, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (524, 18, 1, 92, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (525, 18, 2, 93, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (522, 5, 3, 90, 3, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (530, 18, 3, 220, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (531, 53, 0, 221, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (532, 53, 1, 222, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (533, 53, 2, 223, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (520, 27, 3, 213, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (521, 28, 1, 214, 1, 1);
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
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (176, 46, 1, 152, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (79, 22, -1, 86, 2, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (69, 16, 1, 75, 2, 0);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (100, 27, 2, 105, 2, 0);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (33, 11, 3, 38, 3, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (51, 11, 3, 58, 4, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (52, 11, 3, 62, 5, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (200, 11, 0, 170, 7, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (201, 47, 1, 171, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (202, 47, 3, 171, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (203, 47, 2, 171, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (504, 48, 0, 203, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (505, 48, 1, 203, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (506, 48, 2, 203, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (507, 2, 3, 202, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (508, 49, 0, 205, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (509, 49, 1, 205, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (510, 49, 2, 205, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (513, 50, 1, 207, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (515, 51, 0, 209, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (516, 51, 1, 209, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (517, 51, 2, 209, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (518, 17, 0, 210, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (519, 52, 1, 211, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (53, 11, 3, 66, 6, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (60, 19, 0, 67, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (61, 19, 1, 68, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (62, 19, 1, 69, 2, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (175, 4, 3, 151, 2, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (163, 27, 3, 151, 1, 0);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (511, 1, 3, 204, 4, 0);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (512, 11, 3, 206, 8, 0);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (514, 3, 3, 208, 3, 0);


--2015-6-11 fix bux

CREATE OR REPLACE FUNCTION delete_relation()
  RETURNS trigger AS
$BODY$
BEGIN
	delete from field where relation_id=old.relation_id;
	return old;
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;


--2015-6-12 delete autfilter dependency