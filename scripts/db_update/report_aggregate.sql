DROP VIEW gisclient_3.seldb_fieldtype;
DROP VIEW IF EXISTS gisclient_3.seldb_qt_fieldtype;
ALTER TABLE gisclient_3.e_fieldtype ADD COLUMN sql_function character varying;
UPDATE gisclient_3.e_fieldtype SET sql_function='sum(__field__)' WHERE fieldtype_id=101;
UPDATE gisclient_3.e_fieldtype SET sql_function='avg(__field__)' WHERE fieldtype_id=102;
UPDATE gisclient_3.e_fieldtype SET sql_function='min(__field__)' WHERE fieldtype_id=103;
UPDATE gisclient_3.e_fieldtype SET sql_function='max(__field__)' WHERE fieldtype_id=104;
UPDATE gisclient_3.e_fieldtype SET sql_function='count(__field__)' WHERE fieldtype_id=105;
UPDATE gisclient_3.e_fieldtype SET sql_function='stddev(__field__)' WHERE fieldtype_id=106;
UPDATE gisclient_3.e_fieldtype SET sql_function='variance(__field__)' WHERE fieldtype_id=107;
CREATE OR REPLACE VIEW gisclient_3.seldb_fieldtype AS
 SELECT e_fieldtype.fieldtype_id AS id,
    e_fieldtype.fieldtype_name AS opzione
   FROM gisclient_3.e_fieldtype
  WHERE e_fieldtype.sql_function IS NULL;
CREATE OR REPLACE VIEW gisclient_3.seldb_qt_fieldtype AS
 SELECT e_fieldtype.fieldtype_id AS id,
    e_fieldtype.fieldtype_name AS opzione
   FROM gisclient_3.e_fieldtype;
   
