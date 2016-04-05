--tabelle mancanti
CREATE OR REPLACE FUNCTION gisclient_3.delete_relation()
  RETURNS trigger AS
$BODY$
BEGIN
  delete from gisclient_3.field where relation_id=old.relation_id;
  return old;
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION gisclient_3.delete_relation()
  OWNER TO postgres;

CREATE TABLE gisclient_3.relation
(
  relation_id integer NOT NULL,
  catalog_id integer NOT NULL,
  relation_name character varying NOT NULL,
  relationtype_id integer NOT NULL DEFAULT 1,
  data_field_1 character varying NOT NULL,
  data_field_2 character varying,
  data_field_3 character varying,
  table_name character varying NOT NULL,
  table_field_1 character varying NOT NULL,
  table_field_2 character varying,
  table_field_3 character varying,
  language_id character varying(2),
  layer_id integer,
  relation_title character varying,
  CONSTRAINT relation_pkey PRIMARY KEY (relation_id),
  CONSTRAINT relation_catalog_fkey FOREIGN KEY (catalog_id)
      REFERENCES gisclient_3.catalog (catalog_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT relation_layer_id_fkey FOREIGN KEY (layer_id)
      REFERENCES gisclient_3.layer (layer_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT relation_name_lower_case CHECK (relation_name::text = lower(relation_name::text)),
  CONSTRAINT relation_table_name_lower_case CHECK (table_name::text = lower(table_name::text))
)
WITH (
  OIDS=FALSE
);
ALTER TABLE gisclient_3.relation
  OWNER TO postgres;

-- Index: gisclient_3.fki_relation_catalog_id_fkey

-- DROP INDEX gisclient_3.fki_relation_catalog_id_fkey;

CREATE INDEX fki_relation_catalog_id_fkey
  ON gisclient_3.relation
  USING btree
  (catalog_id);


-- Trigger: delete_relation on gisclient_3.relation

-- DROP TRIGGER delete_relation ON gisclient_3.relation;

CREATE TRIGGER delete_relation
  AFTER DELETE
  ON gisclient_3.relation
  FOR EACH ROW
  EXECUTE PROCEDURE gisclient_3.delete_relation();



CREATE TABLE gisclient_3.field
(
  field_id integer NOT NULL,
  relation_id integer NOT NULL DEFAULT 0,
  field_name character varying NOT NULL,
  field_header character varying NOT NULL,
  fieldtype_id smallint NOT NULL DEFAULT 1,
  searchtype_id smallint NOT NULL DEFAULT 1,
  resultype_id smallint NOT NULL DEFAULT 3,
  field_format character varying,
  column_width integer,
  orderby_id integer NOT NULL DEFAULT 0,
  field_filter integer NOT NULL DEFAULT 0,
  datatype_id smallint NOT NULL DEFAULT 1,
  field_order smallint NOT NULL DEFAULT 0,
  default_op character varying,
  layer_id integer,
  editable numeric(1,0) DEFAULT 0,
  formula character varying,
  lookup_table character varying,
  lookup_id character varying,
  lookup_name character varying,
  filter_field_name character varying,
  CONSTRAINT field_pkey PRIMARY KEY (field_id),
  CONSTRAINT field_fieldtype_id_fkey FOREIGN KEY (fieldtype_id)
      REFERENCES gisclient_3.e_fieldtype (fieldtype_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT field_layer_id_fkey FOREIGN KEY (layer_id)
      REFERENCES gisclient_3.layer (layer_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT field_field_name_layer_id_key UNIQUE (field_name, relation_id, layer_id),
  CONSTRAINT field_relation_id_check CHECK (relation_id >= 0)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE gisclient_3.field
  OWNER TO postgres;

-- Index: gisclient_3.fki_field_fieldtype_id_fkey

-- DROP INDEX gisclient_3.fki_field_fieldtype_id_fkey;

CREATE INDEX fki_field_fieldtype_id_fkey
  ON gisclient_3.field
  USING btree
  (fieldtype_id);



CREATE TABLE gisclient_3.field_groups
(
  field_id integer NOT NULL,
  groupname character varying NOT NULL,
  editable numeric(1,0) DEFAULT 0,
  CONSTRAINT field_groups_pkey PRIMARY KEY (field_id, groupname),
  CONSTRAINT field_groups_field_id_fkey FOREIGN KEY (field_id)
      REFERENCES gisclient_3.field (field_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);
ALTER TABLE gisclient_3.field_groups
  OWNER TO postgres;


















-- RIPRISTINO qt_* PER REPORTISTICA
DROP TABLE qt CASCADE;
DROP TABLE qtlink CASCADE;
DROP TABLE qtfield CASCADE;
DROP TABLE qtrelation CASCADE;


ALTER TABLE e_qtrelationtype RENAME TO e_relationtype;
ALTER TABLE e_relationtype RENAME qtrelationtype_id  TO relationtype_id;
ALTER TABLE e_relationtype RENAME qtrelationtype_name  TO relationtype_name;
ALTER TABLE e_relationtype RENAME qtrelationtype_order  TO relationtype_order;
ALTER TABLE e_relationtype DROP CONSTRAINT e_qtrelationtype_pkey;
ALTER TABLE e_relationtype ADD CONSTRAINT e_relationtype_pkey PRIMARY KEY(relationtype_id);

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
  CONSTRAINT qt_relation_pkey PRIMARY KEY (qtrelation_id),
  CONSTRAINT qt_relation_catalog_fkey FOREIGN KEY (catalog_id)
      REFERENCES catalog (catalog_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT qt_relation_qt_id_fkey FOREIGN KEY (qt_id)
      REFERENCES qt (qt_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX fki_qt_relation_catalog_id_fkey ON qt_relation USING btree (catalog_id);
CREATE INDEX fki_qt_relation_qt_id_fkey ON qt_relation USING btree (qt_id);

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
  CONSTRAINT qt_field_pkey PRIMARY KEY (qtfield_id),
  CONSTRAINT qt_field_fieldtype_id_fkey FOREIGN KEY (fieldtype_id)
      REFERENCES e_fieldtype (fieldtype_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT qt_field_qt_id_fkey FOREIGN KEY (qt_id)
      REFERENCES qt (qt_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT qt_field_qt_id_key UNIQUE (qt_id, field_header),
  CONSTRAINT qt_field_qt_relation_id_check CHECK (qtrelation_id >= 0)
);

CREATE INDEX fki_qt_field_fieldtype_id_fkey ON qt_field USING btree (fieldtype_id);

DROP VIEW IF EXISTS gisclient_3.seldb_qt ;
CREATE OR REPLACE VIEW seldb_qt AS 
         SELECT (-1) AS id, 'Seleziona ====>'::character varying AS opzione, ''::character varying AS mapset_name
UNION ALL 
         SELECT qt.qt_id AS id, qt.qt_name AS opzione, mapset_layergroup.mapset_name
           FROM qt qt
      LEFT JOIN layer USING (layer_id)
   LEFT JOIN mapset_layergroup USING (layergroup_id);

DROP VIEW  IF EXISTS gisclient_3.seldb_qt_relation;
CREATE OR REPLACE VIEW seldb_qt_relation AS 
         SELECT 0 AS id, 'layer'::character varying AS opzione, 0 AS qt_id
UNION ALL
         SELECT qtrelation_id AS id, qtrelation_name AS opzione, qt_id
           FROM qt_relation;

DROP VIEW  IF EXISTS gisclient_3.seldb_qt_relationtype;
CREATE OR REPLACE VIEW seldb_qt_relationtype AS 
 SELECT relationtype_id AS id, relationtype_name AS opzione
   FROM e_relationtype;

DROP VIEW  IF EXISTS gisclient_3.vista_qtfield;
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








