SET search_path = gisclient_3, pg_catalog;


CREATE TABLE access_log
(
  al_id serial NOT NULL,
  al_ip character(15),
  al_date timestamp without time zone,
  al_referer character varying,
  al_page character varying,
  al_useragent character varying,
  CONSTRAINT access_log_pkey PRIMARY KEY (al_id)
);
DROP TABLE IF EXISTS authfilter CASCADE;
CREATE TABLE authfilter
(
  filter_id integer NOT NULL,
  filter_name character varying(100),
  filter_description text,
  filter_priority integer NOT NULL DEFAULT 0,
  CONSTRAINT filter_pkey PRIMARY KEY (filter_id)
);

DROP TABLE IF EXISTS layer_authfilter CASCADE;
CREATE TABLE layer_authfilter
(
  layer_id integer NOT NULL,
  filter_id integer NOT NULL,
  required smallint DEFAULT 0,
  CONSTRAINT layer_authfilter_pkey PRIMARY KEY (layer_id, filter_id),
  CONSTRAINT layer_authfilter_filter_id_fkey FOREIGN KEY (filter_id)
      REFERENCES authfilter (filter_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT layer_authfilter_layer_id_fkey FOREIGN KEY (layer_id)
      REFERENCES layer (layer_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE
);

DROP TABLE IF EXISTS group_authfilter CASCADE;
CREATE TABLE group_authfilter
(
  groupname character varying NOT NULL,
  filter_id integer NOT NULL,
  filter_expression character varying,
  CONSTRAINT group_authfilter_pkey PRIMARY KEY (groupname, filter_id),
  CONSTRAINT group_authfilter_filter_id_fkey FOREIGN KEY (filter_id)
      REFERENCES authfilter (filter_id) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT group_authfilter_gropuname_fkey FOREIGN KEY (groupname)
      REFERENCES groups (groupname) MATCH FULL
      ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE OR REPLACE VIEW seldb_group_authfilter AS 
 SELECT authfilter.filter_id AS id, authfilter.filter_name AS opzione, 
        CASE
            WHEN group_authfilter.groupname IS NULL THEN ''::character varying
            ELSE group_authfilter.groupname
        END AS groupname
   FROM authfilter
   LEFT JOIN group_authfilter USING (filter_id);


INSERT INTO e_datatype VALUES (10,'Immagine',null);
INSERT INTO e_datatype VALUES (15,'File',null);

DROP TABLE e_importtype CASCADE;
DROP TABLE e_tiletype CASCADE;
DELETE FROM e_sizeunits WHERE sizeunits_id not IN (1,5,7);

ALTER TABLE layer ADD COLUMN hide_vector_geom numeric(1,0) DEFAULT 0;
ALTER TABLE layergroup ADD COLUMN tile_origin text;
ALTER TABLE layergroup ADD COLUMN tile_resolutions text;
ALTER TABLE layergroup ADD COLUMN tile_matrix_set character varying;

ALTER TABLE mapset ADD COLUMN mapset_scale_type numeric(1,0) DEFAULT 0;
ALTER TABLE mapset ADD COLUMN mapset_order numeric(1,0) DEFAULT 0;

CREATE TABLE theme_version
(
  theme_id serial NOT NULL,
  theme_version integer NOT NULL,
  CONSTRAINT theme_version_idx PRIMARY KEY (theme_id),
  CONSTRAINT theme_version_fk FOREIGN KEY (theme_id)
      REFERENCES theme (theme_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE version
(
  version_id serial NOT NULL,
  version_name character varying NOT NULL,
  version_date date NOT NULL,
  version_key character varying NOT NULL,
  CONSTRAINT version_pkey PRIMARY KEY (version_id)
);


CREATE OR REPLACE VIEW vista_catalog AS 
 SELECT c.catalog_id, c.catalog_name, c.project_name, c.connection_type, c.catalog_path, c.catalog_url, c.catalog_description, c.files_path, 
        CASE
            WHEN c.connection_type <> 6 THEN '(i) Controllo non possibile: connessione non PostGIS'::text
            WHEN "substring"(c.catalog_path::text, 0, "position"(c.catalog_path::text, '/'::text)) <> current_database()::text THEN '(i) Controllo non possibile: DB diverso'::text
            WHEN NOT ("substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) IN ( SELECT schemata.schema_name
               FROM information_schema.schemata)) THEN '(!) Lo schema configurato non esiste'::text
            ELSE 'OK'::text
        END AS catalog_control
   FROM catalog c;

CREATE OR REPLACE VIEW vista_class AS 
 SELECT c.class_id, c.layer_id, c.class_name, c.class_title, c.class_text, c.expression, c.maxscale, c.minscale, c.class_template, c.class_order, c.legendtype_id, c.symbol_ttf_name, c.label_font, c.label_angle, c.label_color, c.label_outlinecolor, c.label_bgcolor, c.label_size, c.label_minsize, c.label_maxsize, c.label_position, c.label_antialias, c.label_free, c.label_priority, c.label_wrap, c.label_buffer, c.label_force, c.label_def, c.locked, c.class_image, c.keyimage, 
        CASE
            WHEN c.expression IS NULL AND c.class_order <= (( SELECT max(class.class_order) AS max
               FROM class
              WHERE class.layer_id = c.layer_id AND class.class_id <> c.class_id AND class.expression IS NOT NULL)) THEN '(!) Classe con espressione vuota, spostare in fondo'::text
            WHEN c.legendtype_id = 1 AND NOT (c.class_id IN ( SELECT style.class_id
               FROM style)) THEN '(!) Mostra in legenda ma nessuno stile presente'::text
            WHEN c.label_font IS NOT NULL AND c.label_color IS NOT NULL AND c.label_size IS NOT NULL AND c.label_position IS NOT NULL AND l.labelitem IS NULL THEN '(!) Etichetta configurata correttamente, ma nessun campo etichetta configurato sul layer'::text
            WHEN c.label_font IS NOT NULL AND c.label_color IS NOT NULL AND c.label_size IS NOT NULL AND c.label_position IS NOT NULL AND l.labelitem IS NOT NULL THEN 'OK. (i) Con etichetta'::text
            ELSE 'OK'::text
        END AS class_control
   FROM class c
   JOIN layer l USING (layer_id);

CREATE OR REPLACE VIEW vista_field AS 
 SELECT field.field_id, field.layer_id, field.fieldtype_id, x.relation_id, field.field_name, field.resultype_id, field.field_header, field.field_order, COALESCE(field.column_width, 0) AS column_width, x.name AS relation_name, x.relationtype_id, x.relationtype_name, field.editable
   FROM field
   JOIN e_fieldtype USING (fieldtype_id)
   JOIN ( SELECT y.relationtype_id, y.relation_id, y.name, z.relationtype_name
      FROM (         SELECT 0 AS relation_id, 'Data Layer'::character varying AS name, 0 AS relationtype_id
           UNION 
                    SELECT relation.relation_id, COALESCE(relation.relation_name, 'Nessuna Relazione'::character varying) AS name, relation.relationtype_id
                      FROM relation relation) y
   JOIN (         SELECT 0 AS relationtype_id, ''::character varying AS relationtype_name
           UNION 
                    SELECT e_relationtype.relationtype_id, e_relationtype.relationtype_name
                      FROM e_relationtype) z USING (relationtype_id)) x USING (relation_id)
  ORDER BY field.field_id, x.relation_id, x.relationtype_id;

CREATE OR REPLACE VIEW vista_group_authfilter AS 
 SELECT af.filter_id, af.filter_name, gaf.filter_expression, gaf.groupname
   FROM authfilter af
   JOIN group_authfilter gaf USING (filter_id)
  ORDER BY af.filter_name;

CREATE OR REPLACE VIEW vista_layer AS 
 SELECT l.layer_id, l.layergroup_id, l.layer_name, l.layertype_id, l.catalog_id, l.data, l.data_geom, l.data_unique, l.data_srid, l.data_filter, l.classitem, l.labelitem, l.labelsizeitem, l.labelminscale, l.labelmaxscale, l.maxscale, l.minscale, l.symbolscale, l.opacity, l.maxfeatures, l.sizeunits_id, l.layer_def, l.metadata, l.template, l.header, l.footer, l.tolerance, l.layer_order, l.queryable, l.layer_title, l.zoom_buffer, l.group_object, l.selection_color, l.papersize_id, l.toleranceunits_id, l.selection_width, l.selection_info, l.hidden, l.private, l.postlabelcache, l.maxvectfeatures, l.data_type, l.last_update, l.data_extent, l.searchable_id AS searchable, l.hide_vector_geom, 
        CASE
            WHEN l.queryable = 1::numeric AND l.hidden = 0::numeric AND (l.layer_id IN ( SELECT field.layer_id
               FROM field
              WHERE field.resultype_id <> 4)) THEN 'SI. Config. OK'::text
            WHEN l.queryable = 1::numeric AND l.hidden = 1::numeric AND (l.layer_id IN ( SELECT field.layer_id
               FROM field
              WHERE field.resultype_id <> 4)) THEN 'SI. Ma è nascosto'::text
            WHEN l.queryable = 1::numeric AND (l.layer_id IN ( SELECT field.layer_id
               FROM field
              WHERE field.resultype_id = 4)) THEN 'NO. Nessun campo nei risultati'::text
            ELSE 'NO. WFS non abilitato'::text
        END AS is_queryable, 
        CASE
            WHEN l.queryable = 1::numeric AND (l.layer_id IN ( SELECT field.layer_id
               FROM field
              WHERE field.editable = 1::numeric)) THEN 'SI. Config. OK'::text
            WHEN l.queryable = 1::numeric AND (l.layer_id IN ( SELECT field.layer_id
               FROM field
              WHERE field.editable = 0::numeric)) THEN 'NO. Nessun campo è editabile'::text
            WHEN l.queryable = 0::numeric AND (l.layer_id IN ( SELECT field.layer_id
               FROM field
              WHERE field.editable = 1::numeric)) THEN 'NO. Esiste un campo editabile ma il WFS non è attivo'::text
            ELSE 'NO.'::text
        END AS is_editable, 
        CASE
            WHEN c.connection_type <> 6 THEN '(i) Controllo non possibile: connessione non PostGIS'::text
            WHEN "substring"(c.catalog_path::text, 0, "position"(c.catalog_path::text, '/'::text)) <> current_database()::text THEN '(i) Controllo non possibile: DB diverso'::text
            WHEN NOT (l.data::text IN ( SELECT tables.table_name
               FROM information_schema.tables
              WHERE tables.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)))) THEN '(!) La tabella non esiste nel DB'::text
            WHEN NOT (l.data_geom::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) AND columns.table_name::text = l.data::text AND columns.data_type::text = 'USER-DEFINED'::text)) THEN '(!) Il campo geometrico del layer non esiste'::text
            WHEN NOT (l.data_unique::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) AND columns.table_name::text = l.data::text)) THEN '(!) Il campo chiave del layer non esiste'::text
            WHEN NOT (l.data_srid IN ( SELECT geometry_columns.srid
               FROM public.geometry_columns
              WHERE geometry_columns.f_table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) AND geometry_columns.f_table_name::text = l.data::text)) THEN '(!) Lo SRID configurato non è quello corretto'::text
            WHEN NOT (upper(l.data_type::text) IN ( SELECT geometry_columns.type
               FROM public.geometry_columns
              WHERE geometry_columns.f_table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) AND geometry_columns.f_table_name::text = l.data::text)) THEN '(!) Geometrytype non corretto'::text
            WHEN NOT (l.labelitem::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) AND columns.table_name::text = l.data::text)) THEN '(!) Il campo etichetta del layer non esiste'::text
            WHEN NOT (l.labelitem::text IN ( SELECT field.field_name AS field_name
               FROM field
              WHERE field.layer_id = l.layer_id)) THEN '(!) Campo etichetta non presente nei campi del layer'::text
            WHEN NOT (l.labelsizeitem::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) AND columns.table_name::text = l.data::text)) THEN '(!) Il campo altezza etichetta del layer non esiste'::text
            WHEN NOT (l.labelsizeitem::text IN ( SELECT field.field_name AS field_name
               FROM field
              WHERE field.layer_id = l.layer_id)) THEN '(!) Campo altezza etichetta non presente nei campi del layer'::text
            WHEN ((((t.project_name::text || '.'::text) || lg.layergroup_name::text) || '.'::text) || l.layer_name::text IN ( SELECT (((t2.project_name::text || '.'::text) || lg2.layergroup_name::text) || '.'::text) || l2.layer_name::text
               FROM layer l2
          JOIN layergroup lg2 USING (layergroup_id)
     JOIN theme t2 USING (theme_id)
    GROUP BY (((t2.project_name::text || '.'::text) || lg2.layergroup_name::text) || '.'::text) || l2.layer_name::text
   HAVING count((((t2.project_name::text || '.'::text) || lg2.layergroup_name::text) || '.'::text) || l2.layer_name::text) > 1)) THEN '(!) Combinazione nome layergroup + nome layer non univoca. Cambiare nome al layer o al layergroup'::text
            WHEN NOT (l.layer_id IN ( SELECT class.layer_id
               FROM class)) THEN 'OK (i) Non ci sono classi configurate in questo layer'::text
            ELSE 'OK'::text
        END AS layer_control
   FROM layer l
   JOIN catalog c USING (catalog_id)
   JOIN e_layertype USING (layertype_id)
   JOIN layergroup lg USING (layergroup_id)
   JOIN theme t USING (theme_id);


CREATE OR REPLACE VIEW vista_layergroup AS 
 SELECT lg.layergroup_id, lg.theme_id, lg.layergroup_name, lg.layergroup_title, lg.layergroup_maxscale, lg.layergroup_minscale, lg.layergroup_smbscale, lg.layergroup_order, lg.locked, lg.multi, lg.hidden, lg.isbaselayer, lg.tiletype_id, lg.sld, lg.style, lg.url, lg.owstype_id, lg.outputformat_id, lg.layers, lg.parameters, lg.gutter, lg.transition, lg.tree_group, lg.layergroup_description, lg.buffer, lg.tiles_extent, lg.tiles_extent_srid, lg.layergroup_single, lg.metadata_url, lg.opacity, lg.tile_origin, lg.tile_resolutions, lg.tile_matrix_set, 
        CASE
            WHEN lg.tiles_extent_srid IS NOT NULL AND NOT (lg.tiles_extent_srid IN ( SELECT project_srs.srid
               FROM project_srs
              WHERE project_srs.project_name::text = t.project_name::text)) THEN '(!) SRID estensione tiles non presente nei sistemi di riferimento del progetto'::text
            WHEN lg.owstype_id = 6 AND lg.url IS NULL THEN '(!) Nessuna URL configurata per la chiamata TMS'::text
            WHEN lg.owstype_id = 6 AND lg.layers IS NULL THEN '(!) Nessun layer configurato per la chiamata TMS'::text
            WHEN lg.owstype_id = 9 AND lg.url IS NULL THEN '(!) Nessuna URL configurata per la chiamata WMTS'::text
            WHEN lg.owstype_id = 9 AND lg.layers IS NULL THEN '(!) Nessun layer configurato per la chiamata WMTS'::text
            WHEN lg.owstype_id = 9 AND lg.tile_matrix_set IS NULL THEN '(!) Nessun Tile Matrix configurato per la chiamata WMTS'::text
            WHEN lg.owstype_id = 9 AND lg.style IS NULL THEN '(!) Nessuno stile configurato per la chiamata WMTS'::text
            WHEN lg.owstype_id = 9 AND lg.tile_origin IS NULL THEN '(!) Nessuna origine configurata per la chiamata WMTS'::text
            WHEN lg.opacity IS NULL OR lg.opacity::text = '0'::text THEN '(i) Attenzione: trasparenza totale'::text
            WHEN NOT (lg.layergroup_id IN ( SELECT layer.layergroup_id
               FROM layer)) AND lg.layers IS NULL THEN 'OK (i) Non ci sono layer configurati in questo layergroup'::text
            ELSE 'OK'::text
        END AS layergroup_control
   FROM layergroup lg
   JOIN theme t USING (theme_id);

CREATE OR REPLACE VIEW vista_link AS 
 SELECT l.link_id, l.project_name, l.link_name, l.link_def, l.link_order, l.winw, l.winh, 
        CASE
            WHEN l.link_def::text !~~ 'http%://%@%@'::text THEN '(!) Definizione del link non corretta. La sintassi deve essere: http://url@campo@'::text
            WHEN NOT (l.link_id IN ( SELECT qtlink.link_id
               FROM layer_link qtlink)) THEN 'OK. Non utilizzato'::text
            WHEN NOT (replace("substring"(l.link_def::text, '%#"@%@#"%'::text, '#'::text), '@'::text, ''::text) IN ( SELECT field.field_name AS field_name
               FROM field
              WHERE (field.layer_id IN ( SELECT qtlink.layer_id
                       FROM layer_link qtlink
                      WHERE qtlink.link_id = l.link_id)))) THEN '(!) Campo non presente nel layer'::text
            ELSE 'OK. In uso'::text
        END AS link_control
   FROM link l;

CREATE OR REPLACE VIEW vista_mapset AS 
 SELECT m.mapset_name, m.project_name, m.mapset_title, m.mapset_description, m.template, m.mapset_extent, m.page_size, m.filter_data, m.dl_image_res, m.imagelabel, m.bg_color, m.refmap_extent, m.test_extent, m.mapset_srid, m.mapset_def, m.mapset_group, m.private, m.sizeunits_id, m.static_reference, m.metadata, m.mapset_note, m.mask, m.maxscale, m.minscale, m.mapset_scales, m.displayprojection, m.mapset_scale_type, m.mapset_order, 
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

CREATE OR REPLACE VIEW vista_project_languages AS 
 SELECT project_languages.project_name, project_languages.language_id, e_language.language_name, e_language.language_order
   FROM project_languages
   JOIN e_language ON project_languages.language_id = e_language.language_id
  ORDER BY e_language.language_order;

CREATE OR REPLACE VIEW vista_relation AS 
 SELECT r.relation_id AS relation_id, r.catalog_id, r.relation_name AS relation_name, r.relationtype_id AS relationtype_id, r.data_field_1, r.data_field_2, r.data_field_3, r.table_name, r.table_field_1, r.table_field_2, r.table_field_3, r.language_id, r.layer_id, 
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

CREATE OR REPLACE VIEW vista_style AS 
 SELECT s.style_id, s.class_id, s.style_name, s.symbol_name, s.color, s.outlinecolor, s.bgcolor, s.angle, s.size, s.minsize, s.maxsize, s.width, s.maxwidth, s.minwidth, s.locked, s.style_def, s.style_order, s.pattern_id, 
        CASE
            WHEN NOT (s.symbol_name::text IN ( SELECT symbol.symbol_name
               FROM symbol)) THEN '(!) Il simbolo non esiste'::text
            WHEN s.color IS NULL AND s.outlinecolor IS NULL AND s.bgcolor IS NULL THEN '(!) Stile senza colore'::text
            WHEN s.symbol_name IS NOT NULL AND s.size IS NULL THEN '(!) Stile senza dimensione'::text
            ELSE 'OK'::text
        END AS style_control
   FROM style s
   LEFT JOIN symbol USING (symbol_name)
  ORDER BY s.style_order;

CREATE OR REPLACE VIEW vista_version AS 
 SELECT version.version_id, version.version_name, version.version_date
   FROM version
  WHERE version.version_key::text = 'author'::text
  ORDER BY version.version_id DESC
 LIMIT 1;


 --CAMBIO 900913
 update layer set data_srid=3857 where data_srid=900913;
 update mapset set mapset_srid=3857 where mapset_srid=900913;
delete from project_srs where srid=900913;
