set search_path=gisclient_32,public;

-------------------- CAMBIA LE VIEW DI CONTROLLO DB --------------------

DROP VIEW IF EXISTS vista_layer;
CREATE OR REPLACE VIEW vista_layer AS 
 SELECT l.*, 
        CASE
          WHEN queryable = 1 and l.hidden = 0 and 
               layer_id IN (SELECT qtfield.layer_id 
                              FROM qtfield 
                              WHERE qtfield.resultype_id != 4)
          THEN 'JA. Konfig. OK'
          WHEN queryable = 1 and l.hidden = 1 and
               layer_id IN (SELECT qtfield.layer_id 
                              FROM qtfield 
                              WHERE qtfield.resultype_id != 4)
          THEN 'JA. Aber versteckt'
          WHEN queryable = 1 and 
               layer_id IN (SELECT qtfield.layer_id 
                              FROM qtfield 
                              WHERE qtfield.resultype_id = 4)
          THEN 'NEIN. Resultat enthä kein Feld'
          ELSE 'NEIN. WFS nicht aktiv'
        END AS is_queryable, 
        CASE
            WHEN queryable = 1 and layer_id IN ( SELECT qtfield.layer_id
               FROM qtfield
              WHERE qtfield.editable = 1)
            THEN 'JA. Konfig. OK' 
            WHEN queryable = 1 and layer_id IN ( SELECT qtfield.layer_id
               FROM qtfield
              WHERE qtfield.editable = 0)
            THEN 'NEIN. Kein Feld ist editierbar' 
            WHEN queryable = 0 and layer_id IN ( SELECT qtfield.layer_id
               FROM qtfield
              WHERE qtfield.editable = 1)
            THEN 'NEIN. Editierbare Felder vorhanden, aber WFS nicht aktiv' 
            ELSE 'NEIN.'
        END AS is_editable,
        CASE
            WHEN connection_type != 6 then '(i) Kontrolle unmöch: keine PostGIS Verbindung'
            WHEN substring(c.catalog_path,0,position('/' in c.catalog_path)) != current_database() then '(i) Kontrolle unmöch: unterschiedliches DB'
            WHEN layertype_id = 4 then '(i) Controllo non possibile: dato raster'
            WHEN catalog_id <= 0 then '(!) Nessun catalogo configurato'
            WHEN data not in (select table_name FROM information_schema.tables where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)))  THEN '(!) Die Tabelle existiert nicht im DB'
            when data_geom not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = data and data_type = 'USER-DEFINED') then '(!) Das geometrische Feld im Layer existiert nicht'
            when data_unique not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = data) then '(!)Das Index-Feld im Layer existiert nicht'
            when data_srid not in (select srid FROM public.geometry_columns where f_table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and f_table_name=data) then '(!) Der konfigurierte SRID ist nicht korrekt'
            when upper(data_type) not in (select type FROM public.geometry_columns where f_table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and f_table_name=data) then '(!) Geometrytype nicht korrekt'
            WHEN labelitem not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = data) then '(!)Das Label-Feld im Layer existiert nicht'
            WHEN labelitem not in (select qtfield_name FROM qtfield where layer_id = l.layer_id) then '(!) Kein Label-Feld unter den Feldern des Layers'
            WHEN labelsizeitem not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = data) then '(!) Das Feld Labelgrö im Layer existiert nicht'
            WHEN labelsizeitem not in (select qtfield_name FROM qtfield where layer_id = l.layer_id) then '(!)Kein Feld Labelgrö unter den Feldern des Layers'
            WHEN layer_name in (select distinct layer_name FROM layer where layergroup_id != lg.layergroup_id and catalog_id in (select catalog_id FROM catalog where project_name = c.project_name)) THEN '(!) Kombination layergroup Name + layer Name nicht eindeutig. Einen der beiden Namen äern'
            WHEN layer_id not in (select layer_id FROM class) then 'OK (i) Keine Klassen in diesem Layer konfiguriert'
            ELSE 'OK'
          END as layer_control
   FROM layer l
LEFT JOIN catalog c using (catalog_id)
JOIN e_layertype using (layertype_id)
JOIN layergroup lg using (layergroup_id)
JOIN theme t using (theme_id);

ALTER TABLE vista_layer
OWNER TO gisclient;  

-- 2014-10-09: ricrea la vista_qtfield, utile a sapere se un campo èonfigurato correttamente

drop view vista_qtfield;
CREATE OR REPLACE VIEW vista_qtfield AS 
 SELECT qtfield.qtfield_id, qtfield.layer_id, qtfield.fieldtype_id, x.qtrelation_id, qtfield.qtfield_name, qtfield.resultype_id, qtfield.field_header, qtfield.qtfield_order, COALESCE(qtfield.column_width, 0) AS column_width, x.name AS qtrelation_name, x.qtrelationtype_id, x.qtrelationtype_name, qtfield.editable, 
        CASE
            WHEN qtfield.qtrelation_id = 0 THEN 
            CASE
                WHEN c.connection_type <> 6 THEN '(i) Kontrolle unmöch: keine PostGIS Verbindung'::text
                WHEN "substring"(c.catalog_path::text, 0, "position"(c.catalog_path::text, '/'::text)) <> current_database()::text THEN '(i) Kontrolle unmöch: unterschiedliches DB'::text
                WHEN NOT (qtfield.qtfield_name::text IN ( SELECT columns.column_name
                   FROM information_schema.columns
                  WHERE "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) = i.table_schema::text AND l.data::text = i.table_name::text)) THEN '(!) Das Feld ist in der Tabelle nicht vorhanden'::text
                ELSE 'OK'::text
            END
            ELSE 
            CASE
                WHEN cr.connection_type <> 6 THEN '(i) Kontrolle unmöch: keine PostGIS Verbindung'::text
                WHEN "substring"(cr.catalog_path::text, 0, "position"(cr.catalog_path::text, '/'::text)) <> current_database()::text THEN '(i) Kontrolle unmöch: unterschiedliches DB'::text
                WHEN NOT (qtfield.qtfield_name::text IN ( SELECT columns.column_name
                   FROM information_schema.columns
                  WHERE "substring"(cr.catalog_path::text, "position"(cr.catalog_path::text, '/'::text) + 1, length(cr.catalog_path::text)) = i.table_schema::text AND r.table_name::text = i.table_name::text)) THEN '(!) Feld nicht gefunden in der Relations-Tabelle: '::text || r.qtrelation_name::text
                ELSE 'OK'::text
            END
        END AS qtfield_control
   FROM qtfield
   JOIN e_fieldtype USING (fieldtype_id)
   JOIN ( SELECT y.qtrelationtype_id, y.qtrelation_id, y.name, z.qtrelationtype_name
      FROM (         SELECT 0 AS qtrelation_id, 'Data Layer'::character varying AS name, 0 AS qtrelationtype_id
           UNION 
                    SELECT qtrelation.qtrelation_id, COALESCE(qtrelation.qtrelation_name, 'Keine Relation'::character varying) AS name, qtrelation.qtrelationtype_id
                      FROM qtrelation) y
   JOIN (         SELECT 0 AS qtrelationtype_id, ''::character varying AS qtrelationtype_name
           UNION 
                    SELECT e_qtrelationtype.qtrelationtype_id, e_qtrelationtype.qtrelationtype_name
                      FROM e_qtrelationtype) z USING (qtrelationtype_id)) x USING (qtrelation_id)
   JOIN layer l USING (layer_id)
   JOIN catalog c USING (catalog_id)
   LEFT JOIN qtrelation r USING (qtrelation_id)
   LEFT JOIN catalog cr ON cr.catalog_id = r.catalog_id
   LEFT JOIN information_schema.columns i ON qtfield.qtfield_name::text = i.column_name::text AND "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) = i.table_schema::text AND (l.data::text = i.table_name::text OR r.table_name::text = i.table_name::text)
  ORDER BY qtfield.qtfield_id, x.qtrelation_id, x.qtrelationtype_id;

ALTER TABLE vista_qtfield
  OWNER TO gisclient;
  
-- CREA VIEW vista_qtrelation e vista_class;

drop view vista_qtrelation;
CREATE OR REPLACE VIEW vista_qtrelation AS 
 SELECT r.qtrelation_id, r.catalog_id, r.qtrelation_name, r.qtrelationtype_id, r.data_field_1, r.data_field_2, r.data_field_3, r.table_name, r.table_field_1, r.table_field_2, r.table_field_3, r.language_id, r.layer_id, 
        CASE
            WHEN c.connection_type <> 6 THEN '(i) Kontrolle unmöch: keine PostGIS Verbindung'::text
            WHEN "substring"(c.catalog_path::text, 0, "position"(c.catalog_path::text, '/'::text)) <> current_database()::text THEN '(i) Kontrolle unmöch: unterschiedliches DB'::text
            WHEN NOT (l.layer_name::text IN ( SELECT tables.table_name
               FROM information_schema.tables
              WHERE tables.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)))) THEN '(!) Die DB-Tabelle des Layers existiert nicht'::text
            WHEN NOT (r.table_name::text IN ( SELECT tables.table_name
               FROM information_schema.tables
              WHERE tables.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)))) THEN '(!) Die JOIN Tabelle des DB existiert nicht'::text
            WHEN r.data_field_1 IS NULL OR r.table_field_1 IS NULL THEN '(!) Ein Feld der JOIN 1 ist leer'::text
            WHEN NOT (r.data_field_1::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) AND columns.table_name::text = l.layer_name::text)) THEN '(!) Das Index-Feld layer existiert nicht'::text
            WHEN NOT (r.table_field_1::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) AND columns.table_name::text = r.table_name::text)) THEN '(!) Das Index-Feld der Relation existiert nicht'::text
            WHEN r.data_field_2 IS NULL AND r.table_field_2 IS NULL THEN 'OK'::text
            WHEN r.data_field_2 IS NULL OR r.table_field_2 IS NULL THEN '(!) Ein Feld der JOIN 2 ist leer'::text
            WHEN NOT (r.data_field_2::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) AND columns.table_name::text = l.layer_name::text)) THEN '(!) Das Index-Feld layer der JOIN 2 existiert nicht'::text
            WHEN NOT (r.table_field_2::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) AND columns.table_name::text = r.table_name::text)) THEN '(!)Das Index-Feld Relation der JOIN 2 existiert nicht'::text
            WHEN r.data_field_3 IS NULL AND r.table_field_3 IS NULL THEN 'OK'::text
            WHEN r.data_field_3 IS NULL OR r.table_field_3 IS NULL THEN '(!)Ein Feld der JOIN 3 ist leer'::text
            WHEN NOT (r.data_field_3::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) AND columns.table_name::text = l.layer_name::text)) THEN '(!)Das Index-Feld Relation der JOIN 3 existiert nicht'::text
            WHEN NOT (r.table_field_3::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema::text = "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) AND columns.table_name::text = r.table_name::text)) THEN '(!)Das Index-Feld Relation der JOIN 3 existiert nicht'::text
            ELSE 'OK'::text
        END AS qtrelation_control
   FROM qtrelation r
   JOIN catalog c USING (catalog_id)
   JOIN layer l USING (layer_id)
   JOIN e_qtrelationtype rt USING (qtrelationtype_id);

ALTER TABLE vista_qtrelation
  OWNER TO gisclient;

drop view vista_class;  
CREATE OR REPLACE VIEW vista_class AS 
 SELECT c.class_id, c.layer_id, c.class_name, c.class_title, c.class_text, c.expression, c.maxscale, c.minscale, c.class_template, c.class_order, c.legendtype_id, c.symbol_ttf_name, c.label_font, c.label_angle, c.label_color, c.label_outlinecolor, c.label_bgcolor, c.label_size, c.label_minsize, c.label_maxsize, c.label_position, c.label_antialias, c.label_free, c.label_priority, c.label_wrap, c.label_buffer, c.label_force, c.label_def, c.locked, c.class_image, c.keyimage, 
        CASE
            WHEN c.expression IS NULL AND c.class_order <= (( SELECT max(class.class_order) AS max
               FROM class
              WHERE class.layer_id = c.layer_id AND class.class_id <> c.class_id AND class.expression IS NOT NULL)) THEN '(!) Klasse mit leerer Aussageform; nach unten verschieben'::text
            WHEN c.legendtype_id = 1 AND NOT (c.class_id IN ( SELECT style.class_id
               FROM style)) THEN '(!) In Legende anzeigen, aber kein Stil definiert'::text
            WHEN c.label_font IS NOT NULL AND c.label_color IS NOT NULL AND c.label_size IS NOT NULL AND c.label_position IS NOT NULL AND l.labelitem IS NULL THEN '(!) Label korrekt konfiguriert, aber kein Label-Feld auf dem Layer konfiguriert'::text
            WHEN c.label_font IS NOT NULL AND c.label_color IS NOT NULL AND c.label_size IS NOT NULL AND c.label_position IS NOT NULL AND l.labelitem IS NOT NULL THEN 'OK. (i) Mit Label'::text
            ELSE 'OK'::text
        END AS class_control
   FROM class c
   JOIN layer l USING (layer_id);

ALTER TABLE vista_class
  OWNER TO gisclient;

drop view vista_style;  
CREATE OR REPLACE VIEW vista_style AS 
 SELECT s.style_id, s.class_id, s.style_name, s.symbol_name, s.color, s.outlinecolor, s.bgcolor, s.angle, s.size, s.minsize, s.maxsize, s.width, s.maxwidth, s.minwidth, s.locked, s.style_def, s.style_order, s.pattern_id, 
        CASE
            WHEN NOT (s.symbol_name::text IN ( SELECT symbol.symbol_name
               FROM symbol)) THEN '(!) Symbol existiert nicht'::text
            WHEN s.color IS NULL AND s.outlinecolor IS NULL AND s.bgcolor IS NULL THEN '(!) Stile ohne Farben'::text
            WHEN s.symbol_name IS NOT NULL AND s.size IS NULL THEN '(!) Stil ohne Grönangabe'::text
            ELSE 'OK'::text
        END AS style_control
   FROM style s
   LEFT JOIN symbol USING (symbol_name)
  ORDER BY s.style_order;

ALTER TABLE vista_style
  OWNER TO gisclient; 
  
  
DROP VIEW IF EXISTS vista_layergroup;  
CREATE OR REPLACE VIEW vista_layergroup AS 
select lg.*,
CASE 
  WHEN tiles_extent_srid is not null and tiles_extent_srid not in (select srid from project_srs where project_name=t.project_name) THEN '(!) SRID extension tiles im Koordinatensystems des Projekts nicht vorhanden'
  WHEN owstype_id=6 and url is null then '(!) Keine URL fü TMS-Aufruf konfiguriert'
  WHEN owstype_id=6 and layers is null then '(!)Kein Layer fü TMS-Aufruf konfiguriert'
  WHEN owstype_id=9 and url is null then '(!)Keine URL fü WMTS-Aufruf konfiguriert'
  WHEN owstype_id=9 and layers is null then '(!)Kein Layer fü WMTS-Aufruf konfiguriert'
  WHEN owstype_id=9 and tile_matrix_set is null then '(!) Nessun Tile Matrix configurato per la chiamata WMTS'
  WHEN owstype_id=9 and style is null then '(!)Keine Tile Matrix fü WMTS-Aufruf konfiguriert'
  WHEN owstype_id=9 and tile_origin is null then '(!)Kein Stil fü WMTS-Aufruf konfiguriert'
  WHEN lg.opacity is null or lg.opacity = '0' then '(i) Achtung: vollstäige Trasparenz'
  WHEN (layergroup_id not in (select layergroup_id FROM layer)) AND layers is null then 'OK (i) Keine Layer konfigurirt'
  ELSE 'OK'
END as layergroup_control
from layergroup lg
JOIN theme t USING (theme_id);

ALTER TABLE vista_layergroup
  OWNER TO gisclient;  

  drop view vista_mapset;  
CREATE OR REPLACE VIEW vista_mapset AS 
 SELECT m.mapset_name, m.project_name, m.mapset_title, m.mapset_description, m.template, m.mapset_extent, m.page_size, m.filter_data, m.dl_image_res, m.imagelabel, m.bg_color, m.refmap_extent, m.test_extent, m.mapset_srid, m.mapset_def, m.mapset_group, m.sizeunits_id, m.static_reference, m.metadata, m.mapset_note, m.mask, m.maxscale, m.minscale, m.mapset_scales, m.displayprojection, m.private, 
        CASE
            WHEN NOT (m.mapset_name::text IN ( SELECT mapset_layergroup.mapset_name
               FROM mapset_layergroup)) THEN '(!) Keine Layergruppe in diesem Mapset'::text
            WHEN 75 <= (( SELECT count(mapset_layergroup.layergroup_id) AS count
               FROM mapset_layergroup
              WHERE mapset_layergroup.mapset_name::text = m.mapset_name::text
              GROUP BY mapset_layergroup.mapset_name)) THEN '(!) Openlayers kann nicht mehr als 75 layergroup auf einmal darstellen'::text
            WHEN m.mapset_scales IS NULL THEN '(!) Kein Massstab-Verzeichnis konfiguriert'::text
            WHEN m.mapset_srid <> m.displayprojection THEN '(i) Angezeigte Koordinaten sind verschieden von denen der Karte'::text
            WHEN 0 = (( SELECT max(mapset_layergroup.refmap) AS max
               FROM mapset_layergroup
              WHERE mapset_layergroup.mapset_name::text = m.mapset_name::text
              GROUP BY mapset_layergroup.mapset_name)) THEN '(i) Keine reference map'::text
            ELSE 'OK'::text
        END AS mapset_control
   FROM mapset m;

ALTER TABLE vista_mapset
  OWNER TO gisclient;

  drop view vista_catalog;  
  CREATE OR REPLACE VIEW vista_catalog AS 
 SELECT c.catalog_id, c.catalog_name, c.project_name, c.connection_type, c.catalog_path, c.catalog_url, c.catalog_description, c.files_path, 
        CASE
            WHEN c.connection_type <> 6 THEN '(i) Kontrolle unmöch: keine PostGIS Verbindung'::text
            WHEN "substring"(c.catalog_path::text, 0, "position"(c.catalog_path::text, '/'::text)) <> current_database()::text THEN '(i) Kontrolle unmöch: unterschiedliches DB'::text
            WHEN NOT ("substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) IN ( SELECT schemata.schema_name
               FROM information_schema.schemata)) THEN '(!) Das Schema existiert nicht im DB'::text
            ELSE 'OK'::text
        END AS catalog_control
   FROM catalog c;

ALTER TABLE vista_catalog
  OWNER TO gisclient;


-------------------- CAMBIA LE VIEW DELLE SELECT  --------------------
CREATE OR REPLACE VIEW gisclient_32.seldb_catalog AS 
         SELECT (-1) AS id, 'Auswäen ====>'::character varying AS opzione, '0'::character varying AS project_name
UNION ALL 
         SELECT foo.id, foo.opzione, foo.project_name
           FROM ( SELECT catalog.catalog_id AS id, catalog.catalog_name AS opzione, catalog.project_name
                   FROM gisclient_32.catalog
                  ORDER BY catalog.catalog_name) foo;

ALTER TABLE gisclient_32.seldb_catalog
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW gisclient_32.seldb_charset_encodings AS 
 SELECT foo.id, foo.opzione, foo.option_order
   FROM (         SELECT (-1) AS id, 'Auswäen ====>'::character varying AS opzione, 0::smallint AS option_order
        UNION 
                 SELECT e_charset_encodings.charset_encodings_id AS id, e_charset_encodings.charset_encodings_name AS opzione, e_charset_encodings.charset_encodings_order AS option_order
                   FROM gisclient_32.e_charset_encodings) foo
  ORDER BY foo.id;

ALTER TABLE gisclient_32.seldb_charset_encodings
  OWNER TO gisclient;
  
  CREATE OR REPLACE VIEW gisclient_32.seldb_conntype AS 
         SELECT NULL::integer AS id, 'Auswäen ====>'::character varying AS opzione
UNION ALL 
         SELECT foo.id, foo.opzione
           FROM ( SELECT e_conntype.conntype_id AS id, e_conntype.conntype_name AS opzione
                   FROM gisclient_32.e_conntype
                  ORDER BY e_conntype.conntype_order) foo;

ALTER TABLE gisclient_32.seldb_conntype
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW gisclient_32.seldb_field_filter AS 
         SELECT (-1) AS id, 'Kein'::character varying AS opzione, 0 AS qtfield_id, 0 AS qt_id
UNION 
        ( SELECT x.qtfield_id AS id, x.field_header AS opzione, y.qtfield_id, x.layer_id AS qt_id
           FROM gisclient_32.qtfield x
      JOIN gisclient_32.qtfield y USING (layer_id)
     WHERE x.qtfield_id <> y.qtfield_id
     ORDER BY x.qtfield_id, x.qtfield_order);

ALTER TABLE gisclient_32.seldb_field_filter
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW gisclient_32.seldb_filetype AS 
         SELECT (-1) AS id, 'Auswäen ====>'::character varying AS opzione
UNION 
         SELECT e_filetype.filetype_id AS id, e_filetype.filetype_name AS opzione
           FROM gisclient_32.e_filetype;

ALTER TABLE gisclient_32.seldb_filetype
  OWNER TO gisclient;

  CREATE OR REPLACE VIEW gisclient_32.seldb_font AS 
 SELECT foo.id, foo.opzione
   FROM (         SELECT ''::character varying AS id, 'Auswäen ====>'::character varying AS opzione
        UNION 
                 SELECT font.font_name AS id, font.font_name AS opzione
                   FROM gisclient_32.font) foo
  ORDER BY foo.id;

ALTER TABLE gisclient_32.seldb_font
  OWNER TO gisclient;

  CREATE OR REPLACE VIEW gisclient_32.seldb_language AS 
 SELECT foo.id, foo.opzione
   FROM (         SELECT ''::text AS id, 'Auswäen ====>'::character varying AS opzione
        UNION 
                 SELECT e_language.language_id AS id, e_language.language_name AS opzione
                   FROM gisclient_32.e_language) foo
  ORDER BY foo.id;

ALTER TABLE gisclient_32.seldb_language
  OWNER TO gisclient;

  CREATE OR REPLACE VIEW gisclient_32.seldb_layer_layergroup AS 
         SELECT (-1) AS id, 'Auswäen ====>'::character varying AS opzione, NULL::integer AS layergroup_id
UNION 
        ( SELECT DISTINCT layer.layer_id AS id, layer.layer_name AS opzione, layer.layergroup_id
           FROM gisclient_32.layer
          WHERE layer.queryable = 1::numeric
          ORDER BY layer.layer_name, layer.layer_id, layer.layergroup_id);

ALTER TABLE gisclient_32.seldb_layer_layergroup
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW gisclient_32.seldb_layertype AS 
 SELECT foo.id, foo.opzione
   FROM (         SELECT (-1) AS id, 'Auswäen ====>'::character varying AS opzione
        UNION 
                 SELECT e_layertype.layertype_id AS id, e_layertype.layertype_name AS opzione
                   FROM gisclient_32.e_layertype) foo
  ORDER BY foo.id;

ALTER TABLE gisclient_32.seldb_layertype
  OWNER TO gisclient;
  

CREATE OR REPLACE VIEW gisclient_32.seldb_lblposition AS 
        (         SELECT ''::character varying AS id, 'Auswäen ====>'::character varying AS opzione
        UNION ALL 
                 SELECT e_lblposition.lblposition_name AS id, e_lblposition.lblposition_name AS opzione
                   FROM gisclient_32.e_lblposition
                  WHERE e_lblposition.lblposition_name::text = 'AUTO'::text)
UNION ALL 
         SELECT foo.id, foo.opzione
           FROM ( SELECT e_lblposition.lblposition_name AS id, e_lblposition.lblposition_name AS opzione
                   FROM gisclient_32.e_lblposition
                  WHERE e_lblposition.lblposition_name::text <> 'AUTO'::text
                  ORDER BY e_lblposition.lblposition_order) foo;

ALTER TABLE gisclient_32.seldb_lblposition
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW gisclient_32.seldb_link AS 
         SELECT (-1) AS id, 'Auswäen ====>'::character varying AS opzione, ''::character varying AS project_name
UNION 
         SELECT link.link_id AS id, link.link_name AS opzione, link.project_name
           FROM gisclient_32.link;

ALTER TABLE gisclient_32.seldb_link
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW gisclient_32.seldb_papersize AS 
         SELECT (-1) AS id, 'Auswäen ====>'::character varying AS opzione
UNION 
         SELECT e_papersize.papersize_id AS id, e_papersize.papersize_name AS opzione
           FROM gisclient_32.e_papersize;

ALTER TABLE gisclient_32.seldb_papersize
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW gisclient_32.seldb_pattern AS 
         SELECT (-1) AS id, 'Auswäen ====>'::character varying AS opzione
UNION ALL 
         SELECT e_pattern.pattern_id AS id, e_pattern.pattern_name AS opzione
           FROM gisclient_32.e_pattern;

ALTER TABLE gisclient_32.seldb_pattern
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW gisclient_32.seldb_project AS 
         SELECT ''::character varying AS id, 'Auswäen ====>'::character varying AS opzione
UNION 
        ( SELECT DISTINCT project.project_name AS id, project.project_name AS opzione
           FROM gisclient_32.project
          ORDER BY project.project_name);

ALTER TABLE gisclient_32.seldb_project
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW gisclient_32.seldb_theme AS 
         SELECT (-1) AS id, 'Auswäen ====>'::character varying AS opzione, ''::character varying AS project_name
UNION 
         SELECT theme.theme_id AS id, theme.theme_name AS opzione, theme.project_name
           FROM gisclient_32.theme;

ALTER TABLE gisclient_32.seldb_theme
  OWNER TO gisclient;

  
CREATE OR REPLACE VIEW gisclient_32.seldb_sizeunits AS 
 SELECT foo.id, foo.opzione
   FROM (         SELECT (-1)::smallint AS id, 'Auswäen ====>'::character varying AS opzione
        UNION 
                 SELECT e_sizeunits.sizeunits_id AS id, e_sizeunits.sizeunits_name AS opzione
                   FROM gisclient_32.e_sizeunits) foo
  ORDER BY foo.id;

ALTER TABLE gisclient_32.seldb_sizeunits
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW gisclient_32.seldb_theme AS 
         SELECT (-1) AS id, 'Auswäen ====>'::character varying AS opzione, ''::character varying AS project_name
UNION 
         SELECT theme.theme_id AS id, theme.theme_name AS opzione, theme.project_name
           FROM gisclient_32.theme;

ALTER TABLE gisclient_32.seldb_theme
  OWNER TO gisclient;

CREATE OR REPLACE VIEW seldb_wmsversion AS 
 SELECT NULL AS id, 'Auswäen ====>' AS opzione, -1 as wmsversion_order
 UNION 
( SELECT wmsversion_id AS id, wmsversion_name AS opzione, wmsversion_order
   FROM e_wmsversion
  )
  ORDER BY wmsversion_order;

ALTER TABLE gisclient_32.seldb_wmsversion
  OWNER TO gisclient;

  
-------------------- CAMBIA LE VIEW DELLE SELECT  E_ --------------------
 
update gisclient_32.e_datatype set datatype_name = 'String' where datatype_id = 1;
update gisclient_32.e_datatype set datatype_name = 'Nummer' where datatype_id = 2;
update gisclient_32.e_datatype set datatype_name = 'Datum' WHERE datatype_id = 3;
update gisclient_32.e_datatype set datatype_name = 'Bild' WHERE datatype_id = 10;
update gisclient_32.e_datatype set datatype_name = 'Datei' WHERE datatype_id = 15;

update gisclient_32.e_fieldtype set fieldtype_name = 'Standard' where fieldtype_id = 1;
update gisclient_32.e_fieldtype set fieldtype_name = 'Verbindung' where fieldtype_id = 2;
update gisclient_32.e_fieldtype set fieldtype_name = 'Bild' where fieldtype_id = 8;
update gisclient_32.e_fieldtype set fieldtype_name = 'Datei' where fieldtype_id = 10;
update gisclient_32.e_fieldtype set fieldtype_name = 'Betrag' where fieldtype_id = 101;
update gisclient_32.e_fieldtype set fieldtype_name = 'Durchschnitt' where fieldtype_id = 102;
update gisclient_32.e_fieldtype set fieldtype_name = 'Abrechnung' where fieldtype_id = 105;
update gisclient_32.e_fieldtype set fieldtype_name = 'Standardabweichung' where fieldtype_id = 106;
update gisclient_32.e_fieldtype set fieldtype_name = 'Varianz' where fieldtype_id = 107;

update gisclient_32.e_legendtype set legendtype_name = 'Nein' where legendtype_id = 0;
update gisclient_32.e_legendtype set legendtype_name = 'Ja' where legendtype_id = 1;

update gisclient_32.e_qtrelationtype set qtrelationtype_name = '1 - 1' where qtrelationtype_id = 1;
update gisclient_32.e_qtrelationtype set qtrelationtype_name = '1 - N' where qtrelationtype_id = 2;

update gisclient_32.e_resultype set resultype_name = 'Immer anzeigen' where resultype_id = 1;
update gisclient_32.e_resultype set resultype_name = 'Nicht anzeigen' where resultype_id = 4;
update gisclient_32.e_resultype set resultype_name = 'Ignorieren' where resultype_id = 5;
update gisclient_32.e_resultype set resultype_name = 'In Tabelle verstecken' where resultype_id = 10;
update gisclient_32.e_resultype set resultype_name = 'In Tooltip verstecken' where resultype_id = 20;
update gisclient_32.e_resultype set resultype_name = 'In Datenblatt verstecken' where resultype_id = 30;

update gisclient_32.e_searchtype set searchtype_name = 'Keine' where searchtype_id = 0;
update gisclient_32.e_searchtype set searchtype_name = 'Text' where searchtype_id = 1;
update gisclient_32.e_searchtype set searchtype_name = 'Tail des Textes' where searchtype_id = 2;
update gisclient_32.e_searchtype set searchtype_name = 'aus Auslistung wäen' where searchtype_id = 3;
update gisclient_32.e_searchtype set searchtype_name = 'Numerisch' where searchtype_id = 4;
update gisclient_32.e_searchtype set searchtype_name = 'Datum' where searchtype_id = 5;
update gisclient_32.e_searchtype set searchtype_name = 'aus Auslistung wäen, nicht WFS' where searchtype_id = 6;



