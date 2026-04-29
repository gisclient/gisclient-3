-- Fix seldb_relation: return NULL instead of 0 for the "Data Layer" option so the
-- admin form submits an empty value, which savedata.class.php excludes from INSERT
-- (leaving the DB default NULL) and converts to explicit NULL on UPDATE.
-- This prevents FK violations introduced by the 3.6.6 migration that removed the
-- DEFAULT 0 sentinel and added a proper FK on field.relation_id.
CREATE OR REPLACE VIEW seldb_relation AS
         SELECT NULL::integer AS id, 'layer'::character varying AS opzione, 0 AS layer_id
UNION
         SELECT relation_id AS id, relation_name AS opzione, layer_id
           FROM relation;

-- Fix vista_field: fields with relation_id IS NULL (layer fields after the 3.6.6
-- migration) were excluded by the USING (relation_id) join because NULL != 0.
-- Use COALESCE in the join condition so NULL maps to the "Data Layer" (0) row,
-- expose field.relation_id directly (not x.relation_id) so the view returns the
-- real DB value, and extend the field_control CASE to treat NULL like 0.
CREATE OR REPLACE VIEW vista_field AS
 SELECT field.field_id, field.layer_id, field.fieldtype_id, field.relation_id, field.field_name, field.resultype_id, field.field_header, field.field_order, COALESCE(field.column_width, 0) AS column_width, x.name AS relation_name, x.relationtype_id, x.relationtype_name, field.editable,
        CASE
            WHEN field.relation_id IS NULL OR field.relation_id = 0 THEN
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
                          FROM e_relationtype) z USING (relationtype_id)) x ON COALESCE(field.relation_id, 0) = x.relation_id
   JOIN layer l USING (layer_id)
   JOIN catalog c USING (catalog_id)
   LEFT JOIN relation r ON r.relation_id = field.relation_id
   LEFT JOIN catalog cr ON cr.catalog_id = r.catalog_id
   LEFT JOIN information_schema.columns i ON field.field_name::text = i.column_name::text AND "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) = i.table_schema::text AND (l.data::text = i.table_name::text OR r.table_name::text = i.table_name::text)
  ORDER BY field.field_id, x.relation_id, x.relationtype_id;

INSERT INTO gisclient_34.version (version_name, version_key, version_date)
VALUES ('3.6.7', 'author', '2026-04-29');
