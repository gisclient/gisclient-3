SET search_path = gisclient_333, pg_catalog;

-- RIPRISTINO QT_* PER REPORTISTICA

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


--AGGIORNAMENTO DEI DATI
insert into qt select * from gisclient_22.qt;
insert into qt_field select * from gisclient_22.qtfield;
insert into qt_relation select * from gisclient_22.qtrelation;
insert into qt_link select * from gisclient_22.qt_link;