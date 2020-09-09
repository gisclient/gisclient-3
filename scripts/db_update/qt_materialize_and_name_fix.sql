ALTER TABLE gisclient_3.qt RENAME COLUMN zoom_buffer TO materialize;
ALTER TABLE gisclient_3.qt_relation RENAME COLUMN qtrelation_name TO qt_relation_name;
ALTER TABLE gisclient_3.qt_field RENAME COLUMN qtfield_name TO qt_field_name;
DROP VIEW gisclient_3.seldb_qt_relation;
CREATE OR REPLACE VIEW gisclient_3.seldb_qt_relation AS
 SELECT 0 AS id,
    'layer'::character varying AS opzione,
    0 AS qt_id
UNION ALL
 SELECT qt_relation.qt_relation_id AS id,
    qt_relation.qt_relation_name AS opzione,
    qt_relation.qt_id
   FROM gisclient_3.qt_relation;
DROP VIEW gisclient_3.vista_qtfield;
CREATE OR REPLACE VIEW gisclient_3.vista_qtfield AS
SELECT qt_field.qt_field_id,
   qt_field.qt_id,
   qt_field.fieldtype_id,
   x.qt_relation_id,
   qt_field.qt_field_name,
   qt_field.field_header,
   qt_field.qtfield_order,
   COALESCE(qt_field.column_width, 0) AS column_width,
   x.name AS qt_relation_name,
   x.qtrelationtype_id,
   x.qtrelationtype_name
  FROM gisclient_3.qt_field
    JOIN gisclient_3.e_fieldtype USING (fieldtype_id)
    JOIN ( SELECT y.qtrelationtype_id,
           y.qt_relation_id,
           y.name,
           z.qtrelationtype_name
          FROM ( SELECT 0 AS qt_relation_id,
                   'Data Layer'::character varying AS name,
                   0 AS qtrelationtype_id
               UNION ALL
                SELECT qt_relation.qt_relation_id,
                   COALESCE(qt_relation.qt_relation_name, 'Nessuna Relazione'::character varying) AS name,
                   qt_relation.qtrelationtype_id
                  FROM gisclient_3.qt_relation) y
            JOIN ( SELECT 0 AS qtrelationtype_id,
                   ''::character varying AS qtrelationtype_name
               UNION ALL
                SELECT e_relationtype.relationtype_id,
                   e_relationtype.relationtype_name
                  FROM gisclient_3.e_relationtype) z USING (qtrelationtype_id)) x USING (qt_relation_id)
 ORDER BY qt_field.qt_field_id, x.qt_relation_id, x.qtrelationtype_id;
