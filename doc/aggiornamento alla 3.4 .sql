SET search_path = gisclient_34, pg_catalog;

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

ALTER TABLE field DROP CONSTRAINT qtfield_qtfield_name_layer_id_key;
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
  