DROP VIEW gisclient_3.seldb_fieldtype;
DROP VIEW IF EXISTS gisclient_3.seldb_qt_fieldtype;
DROP TABLE gisclient_3.e_fieldtype;
CREATE TABLE gisclient_3.e_fieldtype
(
  fieldtype_id smallint NOT NULL,
  fieldtype_name character varying NOT NULL,
  fieldtype_order smallint,
  sql_function character varying,
  CONSTRAINT e_fieldtype_pkey PRIMARY KEY (fieldtype_id)
)
WITH (
  OIDS=FALSE
);
CREATE OR REPLACE VIEW gisclient_3.seldb_fieldtype AS
 SELECT e_fieldtype.fieldtype_id AS id,
    e_fieldtype.fieldtype_name AS opzione
   FROM gisclient_3.e_fieldtype
  WHERE e_fieldtype.sql_function IS NULL;
CREATE OR REPLACE VIEW gisclient_3.seldb_qt_fieldtype AS
 SELECT e_fieldtype.fieldtype_id AS id,
    e_fieldtype.fieldtype_name AS opzione
   FROM gisclient_3.e_fieldtype;
