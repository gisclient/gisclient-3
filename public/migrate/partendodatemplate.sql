
--###############################################

--pg_dump -f gc32.sql -n gisclient_34 mydb
--cat gc32.sql | sed 's/gisclient_34/gisclient_34/' > gc34.sql
--psql -f gc34.sql mydb
--psql -f aggiornamento_merge.sql mydb
--###############################################
BEGIN;
SET search_path = gisclient_34, pg_catalog;


--VEDERE SE DEVONO ESSERE APPLICATE
INSERT INTO e_datatype VALUES (10,'Immagine',null);
INSERT INTO e_datatype VALUES (15,'File',null);


DROP TABLE e_tiletype CASCADE;
DELETE FROM e_sizeunits WHERE sizeunits_id not IN (1,5,7);


ALTER TABLE mapset ADD COLUMN mapset_scale_type numeric(1,0) DEFAULT 0;
ALTER TABLE mapset ADD COLUMN mapset_order numeric(1,0) DEFAULT 0;


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

UPDATE i18n_field SET table_name='field' where table_name='qtfield';
UPDATE i18n_field SET field_name='field_name' where field_name='qtfield_name';


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
	delete from gisclient_34.field where relation_id=old.relation_id;
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

ALTER TABLE layer_link DROP CONSTRAINT IF EXISTS qtlink_pkey;
ALTER TABLE layer_link DROP CONSTRAINT IF EXISTS qt_link_pkey;

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

-- modifica delle view
DROP VIEW vista_qtrelation;

CREATE OR REPLACE VIEW vista_relation AS 
 SELECT r.relation_id, r.catalog_id, r.relation_name, r.relationtype_id, r.data_field_1, r.data_field_2, r.data_field_3, r.table_name, r.table_field_1, r.table_field_2, r.table_field_3, r.language_id, r.layer_id, 
        CASE
            WHEN c.connection_type <> 6 THEN '(i) Controllo non possibile: connessione non PostGIS'::text
            WHEN "substring"(c.catalog_path::text, 0, "position"(c.catalog_path::text, '/'::text)) <> current_database()::text THEN '(i) Controllo non possibile: DB diverso'::text
            WHEN NOT (l.layer_name::text IN ( SELECT tables.table_name
               FROM information_schema.tables
              WHERE tables.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)))) THEN '(!) La tabella DB del layer non esiste'::text
            WHEN NOT (r.table_name::text IN ( SELECT tables.table_name
               FROM information_schema.tables
              WHERE tables.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)))) THEN '(!) tabella DB di JOIN non esiste'::text
            WHEN r.data_field_1 IS NULL OR r.table_field_1 IS NULL THEN '(!) Uno dei campi della JOIN 1 è vuoto'::text
            WHEN NOT (r.data_field_1::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) AND columns.table_name::text = l.layer_name::text)) THEN '(!) Il campo chiave layer non esiste'::text
            WHEN NOT (r.table_field_1::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) AND columns.table_name::text = r.table_name::text)) THEN '(!) Il campo chiave della relazione non esiste'::text
            WHEN r.data_field_2 IS NULL AND r.table_field_2 IS NULL THEN 'OK'::text
            WHEN r.data_field_2 IS NULL OR r.table_field_2 IS NULL THEN '(!) Uno dei campi della JOIN 2 è vuoto'::text
            WHEN NOT (r.data_field_2::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) AND columns.table_name::text = l.layer_name::text)) THEN '(!) Il campo chiave layer della JOIN 2 non esiste'::text
            WHEN NOT (r.table_field_2::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) AND columns.table_name::text = r.table_name::text)) THEN '(!) Il campo chiave relazione della JOIN 2 non esiste'::text
            WHEN r.data_field_3 IS NULL AND r.table_field_3 IS NULL THEN 'OK'::text
            WHEN r.data_field_3 IS NULL OR r.table_field_3 IS NULL THEN '(!) Uno dei campi della JOIN 3 è vuoto'::text
            WHEN NOT (r.data_field_3::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) AND columns.table_name::text = l.layer_name::text)) THEN '(!) Il campo chiave layer della JOIN 3 non esiste'::text
            WHEN NOT (r.table_field_3::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) AND columns.table_name::text = r.table_name::text)) THEN '(!) Il campo chiave relazione della JOIN 3 non esiste'::text
            ELSE 'OK'::text
        END AS relation_control
   FROM relation r
   JOIN catalog c USING (catalog_id)
   JOIN layer l USING (layer_id)
   JOIN e_relationtype rt USING (relationtype_id);


  
DROP VIEW vista_qtfield;

CREATE OR REPLACE VIEW vista_field AS 
 SELECT field.field_id, field.layer_id, field.fieldtype_id, x.relation_id, field.field_name, field.resultype_id, field.field_header, field.field_order, COALESCE(field.column_width, 0) AS column_width, x.name AS relation_name, x.relationtype_id, x.relationtype_name, field.editable, 
        CASE
            WHEN field.relation_id = 0 THEN 
            CASE
                WHEN c.connection_type <> 6 THEN '(i) Controllo non possibile: connessione non PostGIS'::text
                WHEN "substring"(c.catalog_path::text, 0, "position"(c.catalog_path::text, '/'::text)) <> current_database()::text THEN '(i) Controllo non possibile: DB diverso'::text
                WHEN NOT (field.field_name::text IN ( SELECT columns.column_name
                   FROM information_schema.columns
                  WHERE "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) = i.table_schema::text AND l.data::text = i.table_name::text)) THEN '(!) Il campo non esiste nella tabella'::text
                ELSE 'OK'::text
            END
            ELSE 
            CASE
                WHEN cr.connection_type <> 6 THEN '(i) Controllo non possibile: connessione non PostGIS'::text
                WHEN "substring"(cr.catalog_path::text, 0, "position"(cr.catalog_path::text, '/'::text)) <> current_database()::text THEN '(i) Controllo non possibile: DB diverso'::text
                WHEN NOT (field.field_name::text IN ( SELECT columns.column_name
                   FROM information_schema.columns
                  WHERE "substring"(cr.catalog_path::text, "position"(cr.catalog_path::text, '/'::text) + 1, length(cr.catalog_path::text)) = i.table_schema::text AND r.table_name::text = i.table_name::text)) THEN '(!) Il campo non esiste nella tabella di relazione: '::text || r.relation_name::text
                ELSE 'OK'::text
            END
        END AS field_control
   FROM field
   JOIN e_fieldtype USING (fieldtype_id)
   JOIN ( SELECT y.relationtype_id, y.relation_id, y.name, z.relationtype_name
      FROM (         SELECT 0 AS relation_id, 'Data Layer'::character varying AS name, 0 AS relationtype_id
           UNION 
                    SELECT relation.relation_id, COALESCE(relation.relation_name, 'Nessuna Relazione'::character varying) AS name, relation.relationtype_id
                      FROM relation) y
   JOIN (         SELECT 0 AS relationtype_id, ''::character varying AS relationtype_name
           UNION 
                    SELECT e_relationtype.relationtype_id, e_relationtype.relationtype_name
                      FROM e_relationtype) z USING (relationtype_id)) x USING (relation_id)
   JOIN layer l USING (layer_id)
   JOIN catalog c USING (catalog_id)
   LEFT JOIN relation r USING (relation_id)
   LEFT JOIN catalog cr ON cr.catalog_id = r.catalog_id
   LEFT JOIN information_schema.columns i ON field.field_name::text = i.column_name::text AND "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) = i.table_schema::text AND (l.data::text = i.table_name::text OR r.table_name::text = i.table_name::text)
  ORDER BY field.field_id, x.relation_id, x.relationtype_id;


  
DROP VIEW IF EXISTS vista_link;
CREATE OR REPLACE VIEW vista_link AS 
 SELECT l.link_id, l.project_name, l.link_name, l.link_def, l.link_order, l.winw, l.winh, 
        CASE
            WHEN l.link_def::text !~~ 'http%://%@%@'::text THEN '(!) Definizione del link non corretta. La sintassi deve essere: http://url@campo@'::text
            WHEN NOT (l.link_id IN ( SELECT link.link_id
               FROM layer_link link)) THEN 'OK. Non utilizzato'::text
            WHEN NOT (replace("substring"(l.link_def::text, '%#"@%@#"%'::text, '#'::text), '@'::text, ''::text) IN ( SELECT qtfield.field_name AS qtfield_name
               FROM field qtfield
              WHERE (qtfield.layer_id IN ( SELECT link.layer_id
                       FROM layer_link link
                      WHERE link.link_id = l.link_id)))) THEN '(!) Campo non presente nel layer'::text
            ELSE 'OK. In uso'::text
        END AS link_control
   FROM link l;

  
DROP VIEW IF EXISTS vista_layer;
CREATE OR REPLACE VIEW vista_layer AS 
 SELECT l.*, 
        CASE
          WHEN queryable = 1 and l.hidden = 0 and 
               layer_id IN (SELECT field.layer_id 
                              FROM field 
                              WHERE field.resultype_id != 4)
          THEN 'SI. Config. OK'
          WHEN queryable = 1 and l.hidden = 1 and
               layer_id IN (SELECT field.layer_id 
                              FROM field 
                              WHERE field.resultype_id != 4)
          THEN 'SI. Ma è nascosto'
          WHEN queryable = 1 and 
               layer_id IN (SELECT field.layer_id 
                              FROM field 
                              WHERE field.resultype_id = 4)
          THEN 'NO. Nessun campo nei risultati'
          ELSE 'NO. WFS non abilitato'
        END AS is_queryable, 
        CASE
            WHEN queryable = 1 and layer_id IN ( SELECT field.layer_id
               FROM field
              WHERE field.editable = 1)
            THEN 'SI. Config. OK' 
            WHEN queryable = 1 and layer_id IN ( SELECT field.layer_id
               FROM field
              WHERE field.editable = 0)
            THEN 'NO. Nessun campo è editabile' 
            WHEN queryable = 0 and layer_id IN ( SELECT field.layer_id
               FROM field
              WHERE field.editable = 1)
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
            WHEN labelitem not in (select field_name FROM field where layer_id = l.layer_id) then '(!) Campo etichetta non presente nei campi del layer'
            WHEN labelsizeitem not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = data) then '(!) Il campo altezza etichetta del layer non esiste'
            WHEN labelsizeitem not in (select field_name FROM field where layer_id = l.layer_id) then '(!) Campo altezza etichetta non presente nei campi del layer'
            --WHEN layer_name in (select distinct layer_name FROM layer where layergroup_id != lg.layergroup_id and catalog_id in (select catalog_id FROM catalog where project_name = c.project_name)) THEN '(!) Combinazione nome layergroup + nome layer non univoca. Cambiare nome al layer o al layergroup'
            WHEN t.project_name||'.'||lg.layergroup_name||'.'||l.layer_name IN (select t2.project_name||'.'||lg2.layergroup_name||'.'||l2.layer_name 
              FROM layer l2
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

  
--da verificare. Ho problemi con pattern obbligatori su MS5
CREATE OR REPLACE VIEW seldb_pattern AS 
  --SELECT (-1) AS id, 'Seleziona ====>' AS opzione
  --UNION ALL 
  SELECT pattern_id AS id, pattern_name AS opzione
  FROM e_pattern;
  
-- RICREA E-lEVEL E FORM
DROP TABLE e_level CASCADE;
DROP TABLE e_form CASCADE;
DROP TABLE form_level CASCADE;

CREATE TABLE e_level
(
  id integer NOT NULL,
  name character varying,
  parent_name character varying,
  "order" smallint,
  parent_id smallint,
  depth smallint,
  leaf smallint,
  export integer DEFAULT 1,
  struct_parent_id integer,
  "table" character varying,
  admintype_id integer DEFAULT 2,
  CONSTRAINT e_livelli_pkey PRIMARY KEY (id),
  CONSTRAINT e_level_name_key UNIQUE (name)
);
CREATE TABLE e_form
(
  id integer NOT NULL,
  name character varying,
  config_file character varying,
  tab_type integer,
  level_destination integer,
  form_destination character varying,
  save_data character varying,
  parent_level integer,
  js text,
  table_name character varying,
  order_by character varying,
  CONSTRAINT e_form_pkey PRIMARY KEY (id),
  CONSTRAINT e_form_level_destination_fkey FOREIGN KEY (level_destination)
      REFERENCES e_level (id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE form_level
(
  id integer NOT NULL,
  level integer,
  mode integer,
  form integer,
  order_fld integer,
  visible smallint DEFAULT 1,
  CONSTRAINT livelli_form_pkey PRIMARY KEY (id),
  CONSTRAINT form_level_form_fkey FOREIGN KEY (form)
      REFERENCES e_form (id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT form_level_level_fkey FOREIGN KEY (level)
      REFERENCES e_level (id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE OR REPLACE VIEW elenco_form AS 
 SELECT form_level.id AS "ID", form_level.mode, 
        CASE
            WHEN form_level.mode = 2 THEN 'New'::text
            WHEN form_level.mode = 3 THEN 'Elenco'::text
            WHEN form_level.mode = 0 THEN 'View'::text
            WHEN form_level.mode = 1 THEN 'Edit'::text
            ELSE 'Non definito'::text
        END AS "Modo Visualizzazione Pagina", e_form.id AS "Form ID", e_form.name AS "Nome Form", e_form.tab_type AS "Tipo Tabella", x.name AS "Livello Destinazione", e_level.name AS "Livello Visualizzazione", 
        CASE
            WHEN COALESCE(e_level.depth::integer, (-1)) = (-1) THEN 0
            ELSE e_level.depth + 1
        END AS "Profondita Albero", form_level.order_fld AS "Ordine Visualizzazione", 
        CASE
            WHEN form_level.visible = 1 THEN 'SI'::text
            ELSE 'NO'::text
        END AS "Visibile"
   FROM form_level
   JOIN e_level ON form_level.level = e_level.id
   JOIN e_form ON e_form.id = form_level.form
   JOIN e_level x ON x.id = e_form.level_destination
  ORDER BY 
CASE
    WHEN COALESCE(e_level.depth::integer, (-1)) = (-1) THEN 0
    ELSE e_level.depth + 1
END, form_level.level, 
CASE
    WHEN form_level.mode = 2 THEN 'Nuovo'::text
    WHEN form_level.mode = 0 OR form_level.mode = 3 THEN 'Elenco'::text
    WHEN form_level.mode = 1 THEN 'View'::text
    ELSE 'Edit'::text
END, form_level.order_fld;





INSERT INTO e_level VALUES (1, 'root', NULL, 1, NULL, NULL, 0, 0, NULL, NULL, 2);
INSERT INTO e_level VALUES (2, 'project', 'project', 2, 1, 0, 0, 1, 1, 'project', 2);
INSERT INTO e_level VALUES (3, 'groups', 'groups', 7, 1, 0, 0, 0, 1, 'groups', 1);
INSERT INTO e_level VALUES (4, 'users', 'users', 6, 1, 0, 0, 0, 1, 'users', 1);
INSERT INTO e_level VALUES (5, 'theme', 'theme', 3, 2, 1, 0, 5, 2, 'theme', 2);
INSERT INTO e_level VALUES (6, 'project_srs', 'project_srs', 4, 2, 1, 1, 1, 2, 'project_srs', 2);
INSERT INTO e_level VALUES (7, 'catalog', 'catalog', 13, 2, 1, 1, 2, 2, 'catalog', 2);
INSERT INTO e_level VALUES (8, 'mapset', 'mapset', 15, 2, 1, 0, 6, 2, 'mapset', 2);
INSERT INTO e_level VALUES (9, 'link', 'link', 15, 2, 1, 1, 4, 2, 'link', 2);
INSERT INTO e_level VALUES (10, 'layergroup', 'layergroup', 4, 5, 2, 0, 1, 5, 'layergroup', 2);
INSERT INTO e_level VALUES (11, 'layer', 'layer', 5, 10, 3, 0, 1, 10, 'layer', 2);
INSERT INTO e_level VALUES (12, 'class', 'class', 6, 11, 4, 0, 1, 11, 'class', 2);
INSERT INTO e_level VALUES (14, 'style', 'style', 7, 12, 5, 1, 1, 12, 'style', 2);
INSERT INTO e_level VALUES (22, 'mapset_layergroup', 'mapset_layergroup', 17, 8, 2, 1, 1, 8, 'mapset_layergroup', 2);
INSERT INTO e_level VALUES (27, 'selgroup', 'selgroup', NULL, 2, 1, 0, 8, 2, 'selgroup', 2);
INSERT INTO e_level VALUES (33, 'project_admin', 'project_admin', 15, 2, 1, 1, 0, 2, 'project_admin', 2);
INSERT INTO e_level VALUES (45, 'group_users', 'user_groups', NULL, 4, 2, 1, 0, 4, 'user_group', 1);
INSERT INTO e_level VALUES (46, 'user_groups', 'group_users', NULL, 3, 2, 1, 0, 3, 'user_group', 1);
INSERT INTO e_level VALUES (32, 'user_project', 'project', 8, 2, 1, 1, 0, 2, 'user_project', 2);
INSERT INTO e_level VALUES (47, 'layer_groups', 'layer_groups', NULL, 11, 4, 1, 0, 11, 'layer_groups', 2);
INSERT INTO e_level VALUES (48, 'project_languages', 'project', NULL, 2, 1, 1, 1, 2, 'project_languages', 2);
INSERT INTO e_level VALUES (49, 'authfilter', 'authfilter', 8, 1, 0, 1, 0, 1, 'authfilter', 2);
INSERT INTO e_level VALUES (51, 'group_authfilter', 'groups', 1, 3, 1, 1, 0, 3, 'group_authfilter', 2);
INSERT INTO e_level VALUES (28, 'selgroup_layer', 'selgroup_layer', NULL, 27, 2, 1, 1, 27, 'selgroup_layer', 2);
INSERT INTO e_level VALUES (16, 'relation', 'relation', 10, 11, 4, 1, 1, 11, 'relation', 2);
INSERT INTO e_level VALUES (17, 'field', 'field', 11, 11, 4, 1, 2, 11, 'field', 2);
INSERT INTO e_level VALUES (52, 'field_groups', 'field', 1, 17, 5, 1, 0, 17, 'field_groups', 2);
INSERT INTO e_level VALUES (50, 'layer_authfilter', 'layer', 15, 11, 4, 1, 0, 11, 'layer_authfilter', 2);
INSERT INTO e_level VALUES (19, 'layer_link', 'layer', 12, 11, 4, 1, 0, 11, 'layer_link', 2);

INSERT INTO e_form VALUES (213, 'selgroup_layer', 'selgroup_layer', 4, 28, NULL, 'selgroup_layer', 27, NULL, NULL, NULL);
INSERT INTO e_form VALUES (214, 'selgroup_layer', 'selgroup_layer', 5, 28, NULL, 'selgroup_layer', 27, NULL, NULL, NULL);
INSERT INTO e_form VALUES (16, 'user', 'user', 0, 4, NULL, 'user', 2, NULL, 'user', NULL);
INSERT INTO e_form VALUES (2, 'progetto', 'project', 0, 2, NULL, NULL, NULL, NULL, NULL, 'project_name');
INSERT INTO e_form VALUES (3, 'progetto', 'project', 1, 2, '', NULL, NULL, NULL, NULL, NULL);
INSERT INTO e_form VALUES (5, 'mapset', 'mapset', 0, 8, NULL, NULL, NULL, NULL, NULL, 'title');
INSERT INTO e_form VALUES (6, 'progetto', 'project', 2, 2, '', 'project', NULL, NULL, NULL, NULL);
INSERT INTO e_form VALUES (7, 'progetto', 'project', 1, 2, NULL, 'project', NULL, NULL, NULL, NULL);
INSERT INTO e_form VALUES (8, 'temi', 'theme', 0, 5, NULL, NULL, NULL, NULL, NULL, 'theme_order,theme_title');
INSERT INTO e_form VALUES (9, 'temi', 'theme', 1, 5, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO e_form VALUES (10, 'temi', 'theme', 1, 5, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (11, 'temi', 'theme', 2, 5, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (12, 'project_srs', 'project_srs', 0, 6, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (13, 'project_srs', 'project_srs', 1, 6, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (14, 'project_srs', 'project_srs', 2, 6, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (23, 'group', 'group', 50, 3, NULL, 'group', 2, NULL, 'group', NULL);
INSERT INTO e_form VALUES (26, 'mapset', 'mapset', 1, 8, '', NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (27, 'mapset', 'mapset', 1, 8, NULL, 'mapset', 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (28, 'mapset', 'mapset', 2, 2, NULL, 'mapset', 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (34, 'layer', 'layer', 0, 11, NULL, NULL, 10, NULL, NULL, 'layer_order,layer_name');
INSERT INTO e_form VALUES (35, 'layer', 'layer', 1, 11, NULL, 'layer', 10, NULL, NULL, NULL);
INSERT INTO e_form VALUES (36, 'layer', 'layer', 1, 11, NULL, 'layer', 10, NULL, NULL, NULL);
INSERT INTO e_form VALUES (37, 'layer', 'layer', 2, 11, NULL, 'layer', 10, NULL, NULL, NULL);
INSERT INTO e_form VALUES (38, 'classi', 'class', 0, 12, NULL, NULL, 11, NULL, NULL, 'class_order');
INSERT INTO e_form VALUES (39, 'classi', 'class', 1, 12, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (40, 'classi', 'class', 1, 12, NULL, 'class', 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (41, 'classi', 'class', 2, 12, NULL, 'class', 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (42, 'stili', 'style', 0, 14, NULL, NULL, 12, NULL, NULL, 'style_order');
INSERT INTO e_form VALUES (43, 'stili', 'style', 1, 14, NULL, NULL, 12, NULL, NULL, NULL);
INSERT INTO e_form VALUES (44, 'stili', 'style', 1, 14, NULL, 'style', 12, NULL, NULL, NULL);
INSERT INTO e_form VALUES (45, 'stili', 'style', 2, 14, NULL, 'style', 12, NULL, NULL, NULL);
INSERT INTO e_form VALUES (50, 'catalog', 'catalog', 0, 7, NULL, NULL, 2, NULL, NULL, 'catalog_name');
INSERT INTO e_form VALUES (51, 'catalog', 'catalog', 1, 7, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (52, 'catalog', 'catalog', 1, 7, NULL, 'catalog', 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (53, 'catalog', 'catalog', 2, 7, NULL, 'catalog', 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (70, 'links', 'link', 0, 9, '', NULL, 2, NULL, NULL, 'link_order,link_name');
INSERT INTO e_form VALUES (72, 'links', 'link', 1, 9, '', NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (73, 'links', 'link', 1, 9, '', NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (74, 'links', 'link', 2, 9, '', NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (105, 'selgroup', 'selgroup', 0, 27, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (106, 'selgroup', 'selgroup', 1, 27, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (107, 'selgroup', 'selgroup', 1, 27, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (133, 'project_admin', 'admin_project', 2, 33, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (134, 'project_admin', 'admin_project', 5, 33, NULL, 'admin_project', 6, NULL, NULL, NULL);
INSERT INTO e_form VALUES (151, 'user_groups', 'user_groups', 4, 46, NULL, 'user_groups', 4, NULL, NULL, NULL);
INSERT INTO e_form VALUES (152, 'user_groups', 'user_groups', 5, 46, NULL, 'user_groups', 4, NULL, NULL, NULL);
INSERT INTO e_form VALUES (75, 'relation', 'relation_addnew', 0, 16, NULL, NULL, 13, NULL, NULL, NULL);
INSERT INTO e_form VALUES (30, 'layergroup', 'layergroup', 0, 10, NULL, 'layergroup', 5, NULL, NULL, 'layergroup_order,layergroup_title');
INSERT INTO e_form VALUES (31, 'layergroup', 'layergroup', 1, 10, NULL, 'layergroup', 5, NULL, NULL, NULL);
INSERT INTO e_form VALUES (32, 'layergroup', 'layergroup', 1, 10, NULL, 'layergroup', 5, NULL, NULL, NULL);
INSERT INTO e_form VALUES (33, 'layergroup', 'layergroup', 2, 10, NULL, 'layergroup', 5, NULL, NULL, NULL);
INSERT INTO e_form VALUES (84, 'map_layer', 'mapset_layergroup', 4, 22, NULL, 'mapset_layergroup', 8, NULL, NULL, NULL);
INSERT INTO e_form VALUES (85, 'map_layer', 'mapset_layergroup', 5, 22, NULL, 'mapset_layergroup', 8, NULL, NULL, NULL);
INSERT INTO e_form VALUES (86, 'map_layer', 'mapset_layergroup', 0, 22, NULL, 'mapset_layergroup', 8, NULL, NULL, NULL);
INSERT INTO e_form VALUES (170, 'layer_groups', 'layer_groups', 4, 47, NULL, 'layer_groups', 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (171, 'layer_groups', 'layer_groups', 5, 47, NULL, 'layer_groups', 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (202, 'project_languages', 'project_languages', 0, 48, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (203, 'project_languages', 'project_languages', 1, 48, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (204, 'authfilter', 'authfilter', 0, 49, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (205, 'authfilter', 'authfilter', 1, 49, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (206, 'layer_authfilter', 'layer_authfilter', 4, 50, NULL, 'layer_authfilter', 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (207, 'layer_authfilter', 'layer_authfilter', 5, 50, NULL, 'layer_authfilter', 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (208, 'group_authfilter', 'group_authfilter', 0, 51, NULL, NULL, 3, NULL, NULL, NULL);
INSERT INTO e_form VALUES (209, 'group_authfilter', 'group_authfilter', 1, 51, NULL, NULL, 3, NULL, NULL, NULL);
INSERT INTO e_form VALUES (20, 'group', 'group', 0, 3, NULL, 'group', 2, NULL, 'group', NULL);
INSERT INTO e_form VALUES (18, 'user', 'user', 50, 4, NULL, 'user', 2, NULL, 'user', NULL);
INSERT INTO e_form VALUES (58, 'relation', 'relation', 0, 16, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (59, 'relation', 'relation', 1, 16, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (60, 'relation', 'relation', 1, 16, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (61, 'relation', 'relation', 2, 16, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (63, 'fields', 'field', 1, 17, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (64, 'fields', 'field', 1, 17, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (65, 'fields', 'field', 2, 17, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (62, 'fields', 'field', 0, 17, NULL, NULL, 11, NULL, NULL, 'relationtype_id,relation_name,field_header,field_name');
INSERT INTO e_form VALUES (210, 'field_groups', 'field_groups', 4, 52, NULL, 'field_groups', 17, NULL, NULL, NULL);
INSERT INTO e_form VALUES (211, 'field_groups', 'field_groups', 5, 52, NULL, 'field_groups', 17, NULL, NULL, NULL);
INSERT INTO e_form VALUES (212, 'field_groups', 'field_groups', 0, 52, NULL, 'field_groups', 17, NULL, NULL, NULL);
INSERT INTO e_form VALUES (66, 'layer_link', 'layer_link', 2, 19, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (69, 'layer_link', 'layer_link', 110, 19, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (68, 'layer_link', 'layer_link', 1, 19, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (67, 'layer_link', 'layer_link', 0, 19, NULL, NULL, 11, NULL, NULL, NULL);

INSERT INTO form_level VALUES (520, 27, 3, 213, 1, 1);
INSERT INTO form_level VALUES (521, 28, 1, 214, 1, 1);
INSERT INTO form_level VALUES (1, 1, 3, 2, 1, 1);
INSERT INTO form_level VALUES (2, 2, 0, 3, 1, 1);
INSERT INTO form_level VALUES (5, 2, 3, 5, 8, 1);
INSERT INTO form_level VALUES (7, 2, 1, 7, 1, 1);
INSERT INTO form_level VALUES (8, 2, 2, 6, 1, 1);
INSERT INTO form_level VALUES (14, 2, 3, 12, 3, 1);
INSERT INTO form_level VALUES (15, 6, 1, 13, 1, 1);
INSERT INTO form_level VALUES (16, 6, 2, 13, 1, 1);
INSERT INTO form_level VALUES (17, 6, 0, 13, 1, 1);
INSERT INTO form_level VALUES (19, 8, 0, 26, 1, 1);
INSERT INTO form_level VALUES (20, 8, 1, 27, 1, 1);
INSERT INTO form_level VALUES (21, 8, 2, 28, 1, 1);
INSERT INTO form_level VALUES (22, 5, 0, 9, 1, 1);
INSERT INTO form_level VALUES (23, 5, 1, 10, 1, 1);
INSERT INTO form_level VALUES (24, 5, 2, 11, 1, 1);
INSERT INTO form_level VALUES (25, 5, 3, 30, 3, 1);
INSERT INTO form_level VALUES (26, 10, 0, 31, 1, 1);
INSERT INTO form_level VALUES (27, 10, 1, 32, 1, 1);
INSERT INTO form_level VALUES (28, 10, 2, 33, 1, 1);
INSERT INTO form_level VALUES (29, 10, 3, 34, 3, 1);
INSERT INTO form_level VALUES (30, 11, 0, 35, 1, 1);
INSERT INTO form_level VALUES (31, 11, 1, 36, 1, 1);
INSERT INTO form_level VALUES (32, 11, 2, 37, 1, 1);
INSERT INTO form_level VALUES (34, 12, 0, 39, 1, 1);
INSERT INTO form_level VALUES (35, 12, 1, 40, 1, 1);
INSERT INTO form_level VALUES (36, 12, 2, 41, 2, 1);
INSERT INTO form_level VALUES (37, 12, 3, 42, 3, 1);
INSERT INTO form_level VALUES (38, 14, 0, 43, 1, 1);
INSERT INTO form_level VALUES (39, 14, 1, 44, 1, 1);
INSERT INTO form_level VALUES (40, 14, 2, 45, 1, 1);
INSERT INTO form_level VALUES (46, 7, 0, 51, 1, 1);
INSERT INTO form_level VALUES (47, 7, 1, 52, 1, 1);
INSERT INTO form_level VALUES (48, 7, 2, 53, 1, 1);
INSERT INTO form_level VALUES (54, 16, 0, 59, 1, 1);
INSERT INTO form_level VALUES (55, 16, 1, 60, 1, 1);
INSERT INTO form_level VALUES (56, 16, 2, 61, 1, 1);
INSERT INTO form_level VALUES (57, 17, 0, 63, 1, 1);
INSERT INTO form_level VALUES (58, 17, 1, 64, 1, 1);
INSERT INTO form_level VALUES (59, 17, 2, 65, 1, 1);
INSERT INTO form_level VALUES (63, 2, 3, 70, 7, 1);
INSERT INTO form_level VALUES (64, 9, 0, 72, 1, 1);
INSERT INTO form_level VALUES (65, 9, 1, 73, 1, 1);
INSERT INTO form_level VALUES (66, 9, 2, 74, 1, 1);
INSERT INTO form_level VALUES (77, 8, 3, 84, 6, 1);
INSERT INTO form_level VALUES (78, 22, 1, 85, 1, 1);
INSERT INTO form_level VALUES (98, 2, 3, 105, 6, 1);
INSERT INTO form_level VALUES (99, 27, 1, 106, 1, 1);
INSERT INTO form_level VALUES (101, 27, 0, 107, 1, 1);
INSERT INTO form_level VALUES (127, 33, 1, 134, 15, 1);
INSERT INTO form_level VALUES (131, 2, 3, 133, 15, 1);
INSERT INTO form_level VALUES (132, 27, 2, 106, 1, 1);
INSERT INTO form_level VALUES (164, 1, 3, 16, 3, 1);
INSERT INTO form_level VALUES (165, 4, 0, 18, 1, 1);
INSERT INTO form_level VALUES (166, 4, 1, 18, 1, 1);
INSERT INTO form_level VALUES (167, 4, 2, 18, 1, 1);
INSERT INTO form_level VALUES (168, 1, 3, 20, 2, 1);
INSERT INTO form_level VALUES (169, 3, 0, 23, 1, 1);
INSERT INTO form_level VALUES (170, 3, 1, 23, 1, 1);
INSERT INTO form_level VALUES (171, 3, 2, 23, 1, 1);
INSERT INTO form_level VALUES (176, 46, 1, 152, 1, 1);
INSERT INTO form_level VALUES (79, 22, -1, 86, 2, 1);
INSERT INTO form_level VALUES (69, 16, 1, 75, 2, 0);
INSERT INTO form_level VALUES (100, 27, 2, 105, 2, 0);
INSERT INTO form_level VALUES (33, 11, 3, 38, 3, 1);
INSERT INTO form_level VALUES (51, 11, 3, 58, 4, 1);
INSERT INTO form_level VALUES (52, 11, 3, 62, 5, 1);
INSERT INTO form_level VALUES (200, 11, 0, 170, 7, 1);
INSERT INTO form_level VALUES (201, 47, 1, 171, 1, 1);
INSERT INTO form_level VALUES (202, 47, 3, 171, 1, 1);
INSERT INTO form_level VALUES (203, 47, 2, 171, 1, 1);
INSERT INTO form_level VALUES (504, 48, 0, 203, 1, 1);
INSERT INTO form_level VALUES (505, 48, 1, 203, 1, 1);
INSERT INTO form_level VALUES (506, 48, 2, 203, 1, 1);
INSERT INTO form_level VALUES (507, 2, 3, 202, 1, 1);
INSERT INTO form_level VALUES (508, 49, 0, 205, 1, 1);
INSERT INTO form_level VALUES (509, 49, 1, 205, 1, 1);
INSERT INTO form_level VALUES (510, 49, 2, 205, 1, 1);
INSERT INTO form_level VALUES (513, 50, 1, 207, 1, 1);
INSERT INTO form_level VALUES (515, 51, 0, 209, 1, 1);
INSERT INTO form_level VALUES (516, 51, 1, 209, 1, 1);
INSERT INTO form_level VALUES (517, 51, 2, 209, 1, 1);
INSERT INTO form_level VALUES (518, 17, 0, 210, 1, 1);
INSERT INTO form_level VALUES (519, 52, 1, 211, 1, 1);
INSERT INTO form_level VALUES (53, 11, 3, 66, 6, 1);
INSERT INTO form_level VALUES (60, 19, 0, 67, 1, 1);
INSERT INTO form_level VALUES (61, 19, 1, 68, 1, 1);
INSERT INTO form_level VALUES (62, 19, 1, 69, 2, 1);
INSERT INTO form_level VALUES (175, 4, 3, 151, 2, 1);
INSERT INTO form_level VALUES (163, 27, 3, 151, 1, 0);
INSERT INTO form_level VALUES (511, 1, 3, 204, 4, 0);
INSERT INTO form_level VALUES (512, 11, 3, 206, 8, 0);
INSERT INTO form_level VALUES (514, 3, 3, 208, 3, 0);
INSERT INTO form_level VALUES (4, 2, 3, 8, 4, 1);
INSERT INTO form_level VALUES (45, 2, 3, 50, 5, 1);

--2015-6-11 fix bux

CREATE OR REPLACE FUNCTION delete_relation()
  RETURNS trigger AS
$BODY$
BEGIN
	delete from gisclient_34.field where relation_id=old.relation_id;
	return old;
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;


--2015-6-12 delete autfilter dependency


-- inverte l'ordine dei layer e degli stili
update layer set layer_order = abs(layer_order-1000) ;
update style set style_order= abs(style_order-10) ;

--fix per import/export
DROP VIEW vista_mapset;

ALTER TABLE mapset ALTER COLUMN mapset_scale_type type smallint;
ALTER TABLE mapset ALTER COLUMN mapset_order type smallint;

CREATE OR REPLACE VIEW vista_mapset AS 
 SELECT m.mapset_name, m.project_name, m.mapset_title, m.template, m.mapset_extent, m.page_size, m.filter_data, m.dl_image_res, m.imagelabel, m.bg_color, m.refmap_extent, m.test_extent, m.mapset_srid, m.mapset_def, m.mapset_group, m.private, m.sizeunits_id, m.static_reference, m.metadata, m.mask, m.maxscale, m.minscale, m.mapset_scales, m.displayprojection, m.mapset_scale_type, m.mapset_order, 
        CASE
            WHEN NOT (m.mapset_name::text IN ( SELECT mapset_layergroup.mapset_name
               FROM mapset_layergroup)) THEN '(!) Nessun layergroup presente'::text
            WHEN 75 <= (( SELECT count(mapset_layergroup.layergroup_id) AS count
               FROM mapset_layergroup
              WHERE mapset_layergroup.mapset_name::text = m.mapset_name::text
              GROUP BY mapset_layergroup.mapset_name)) THEN ('(!) '::text || (( SELECT count(mapset_layergroup.layergroup_id) AS count
               FROM mapset_layergroup
              WHERE mapset_layergroup.mapset_name::text = m.mapset_name::text
              GROUP BY mapset_layergroup.mapset_name))) || ' layergroup presenti nel mapset. OpenLayers 2 non consente di rappresentare più di 74 layergroup alla volta'::text
            WHEN m.mapset_scales IS NULL THEN '(!) Nessun elenco di scale configurato'::text
            WHEN m.mapset_srid <> m.displayprojection THEN '(i) Coordinate visualizzate diverse da quelle di mappa'::text
            WHEN 0 = (( SELECT max(mapset_layergroup.refmap) AS max
               FROM mapset_layergroup
              WHERE mapset_layergroup.mapset_name::text = m.mapset_name::text
              GROUP BY mapset_layergroup.mapset_name)) THEN '(i) Nessuna reference map'::text
            ELSE 'OK'::text
        END AS mapset_control
   FROM mapset m;


DROP VIEW vista_mapset;
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

-- CREO LA TABELLA export_i18n SE NON ESISTE per non far crashare lo script nel successivo UPDATE
CREATE TABLE IF NOT EXISTS export_i18n
(
  exporti18n_id serial NOT NULL,
  table_name character varying,
  field_name character varying,
  project_name character varying,
  pkey_id character varying,
  language_id character varying,
  value text,
  original_value text,
  CONSTRAINT export_i18n_pkey PRIMARY KEY (exporti18n_id)
)
WITH (
  OIDS=FALSE
);

  
UPDATE export_i18n SET table_name='field' WHERE table_name='qtfield';
UPDATE export_i18n SET field_name='field_name' WHERE field_name='qtfield_name';

-- version
INSERT INTO version (version_name,version_key, version_date) values ('3.4.0', 'author', '2015-06-15');
COMMIT;

------------------------------------------- INIZIO SVILUPPI AUTHOR 3.4 -------------------------------------------

-- parametro per non scrivere l'estensione del layer nel mapfile se il catalogo è WMS
ALTER TABLE catalog
  ADD COLUMN set_extent smallint DEFAULT 1;

-- version
INSERT INTO version (version_name,version_key, version_date) values ('3.4.1', 'author', '2015-10-09');

-- 2015-01-25 Aggiunta traduzioni per template dei layer
INSERT INTO i18n_field (i18nf_id,table_name,field_name) values (22,'layer','template');
INSERT INTO i18n_field (i18nf_id,table_name,field_name) values (23,'layer','header');
INSERT INTO i18n_field (i18nf_id,table_name,field_name) values (24,'layer','footer');

-- version
INSERT INTO version (version_name,version_key, version_date) values ('3.4.2', 'author', '2016-01-25');

-- 2016-03-08: fix database necessario in seguito a commit: 2afd6e0
UPDATE class SET class_text=REPLACE(class_text,'''','');
UPDATE class SET class_text=REPLACE(class_text,'"','');

-- version
INSERT INTO version (version_name,version_key, version_date) values ('3.4.3', 'author', '2016-03-08');



--drop  authfilter tables
DROP TABLE authfilter CASCADE;
DROP TABLE group_authfilter CASCADE;
DROP TABLE layer_authfilter CASCADE;












