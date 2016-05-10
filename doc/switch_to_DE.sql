----------------------AGGIORNATO AD AUTHOR 3.4.3----------------------
set search_path=gisclient_34,public;

-------------------- UPDATE DELLE TABELLE DI LOOKUP  E_* --------------------
 
update e_datatype set datatype_name = 'Text' where datatype_id = 1;
update e_datatype set datatype_name = 'Nummer' where datatype_id = 2;
update e_datatype set datatype_name = 'Datum' where datatype_id = 3;
update e_datatype set datatype_name = 'Immagine' where datatype_id = 10;
update e_datatype set datatype_name = 'File' where datatype_id = 15;

update e_fieldformat set fieldformat_name = 'Ganzzahlig' where fieldformat_id = 1;
update e_fieldformat set fieldformat_name = 'Dezimal (1 Zahl)' where fieldformat_id = 2;
update e_fieldformat set fieldformat_name = 'Dezimal (2 Zahl)' where fieldformat_id = 3;
update e_fieldformat set fieldformat_name = 'Zeichenfolge' where fieldformat_id = 4;

update e_fieldtype set fieldtype_name = 'Standard' where fieldtype_id = 1;
update e_fieldtype set fieldtype_name = 'Verbindung' where fieldtype_id = 2;
update e_fieldtype set fieldtype_name = 'E-mail' where fieldtype_id = 3;
update e_fieldtype set fieldtype_name = 'Bild' where fieldtype_id = 8;
update e_fieldtype set fieldtype_name = 'Datei' where fieldtype_id = 10;
update e_fieldtype set fieldtype_name = 'Summe' where fieldtype_id = 101;
update e_fieldtype set fieldtype_name = 'Mittelwert' where fieldtype_id = 102;
update e_fieldtype set fieldtype_name = 'Min' where fieldtype_id = 103;
update e_fieldtype set fieldtype_name = 'Max' where fieldtype_id = 104;
update e_fieldtype set fieldtype_name = 'Abrechnung' where fieldtype_id = 105;
update e_fieldtype set fieldtype_name = 'Standardabweichung' where fieldtype_id = 106;
update e_fieldtype set fieldtype_name = 'Varianz' where fieldtype_id = 107;

update e_filetype set filetype_name = 'SQL' where filetype_id = 1;
update e_filetype set filetype_name = 'CSV' where filetype_id = 2;
update e_filetype set filetype_name = 'Shape' where filetype_id = 3;

update e_layertype set layertype_name = 'Punkt' where layertype_id = 1;
update e_layertype set layertype_name = 'Linie' where layertype_id = 2;
update e_layertype set layertype_name = 'Polygon' where layertype_id = 3;
update e_layertype set layertype_name = 'Raster' where layertype_id = 4;
update e_layertype set layertype_name = 'Tile-Raster' where layertype_id = 10;
update e_layertype set layertype_name = 'Diagramm' where layertype_id = 11;

update e_legendtype set legendtype_name = 'Nein' where legendtype_id = 0;
update e_legendtype set legendtype_name = 'Ja' where legendtype_id = 1;

update e_orderby set orderby_name = 'Keine' where orderby_id = 0;
update e_orderby set orderby_name = 'Steigend' where orderby_id = 1;
update e_orderby set orderby_name = 'Abnehmend' where orderby_id = 2;

delete from e_outputformat where outputformat_id not in (1,3,7,9);
update e_outputformat set outputformat_name = 'PNG 24 Bit (Transp.)' where outputformat_id = 1;
update e_outputformat set outputformat_name = 'JPG/JPEG' where outputformat_id = 3;
update e_outputformat set outputformat_name = 'PNG 8 Bit (Transp.)' where outputformat_id = 7;
update e_outputformat set outputformat_name = 'AGG PNG' where outputformat_id = 9;

delete from e_owstype where owstype_id not in (1,2,6);

update e_relationtype set relationtype_name = '1 : 1' where relationtype_id = 1;
update e_relationtype set relationtype_name = '1 : N' where relationtype_id = 2;

delete from e_resultype where resultype_id not in (1,4,10);
update e_resultype set resultype_name = 'Ja' where resultype_id = 1;
update e_resultype set resultype_name = 'Nein' where resultype_id = 4;
update e_resultype set resultype_name = 'Nur in Tabelle anzeigen' where resultype_id = 10;

delete from e_searchable where searchable_id not in (0,1);
update e_searchable set searchable_name = 'Nein' where searchable_id = 0;
update e_searchable set searchable_name = 'Ja' where searchable_id = 1;

update e_searchtype set searchtype_name = 'Keine' where searchtype_id = 0;
update e_searchtype set searchtype_name = 'Text' where searchtype_id = 1;
update e_searchtype set searchtype_name = 'Textabschnitt' where searchtype_id = 2;
update e_searchtype set searchtype_name = 'Aufklappliste' where searchtype_id = 3;
update e_searchtype set searchtype_name = 'Numerisch' where searchtype_id = 4;
update e_searchtype set searchtype_name = 'Datum' where searchtype_id = 5;
update e_searchtype set searchtype_name = 'Aufklappliste, kein WFS' where searchtype_id = 6;

delete from e_sizeunits where sizeunits_id not in (1,5);
update e_sizeunits set sizeunits_name = 'Pixel' where sizeunits_id = 0;
update e_sizeunits set sizeunits_name = 'Meters' where sizeunits_id = 1;

update e_symbolcategory set symbolcategory_name = 'Grundierung' where symbolcategory_name = 'Campiture';
update e_symbolcategory set symbolcategory_name = 'Kataster' where symbolcategory_name = 'Catasto CML';

update e_tiletype set tiletype_name = 'Nein' where tiletype_id = 0;
update e_tiletype set tiletype_name = 'Ja' where tiletype_id = 1;

-------------------- UPDATE DELLE VIEW SELDB_* --------------------

CREATE OR REPLACE VIEW seldb_catalog AS 
 SELECT (-1) AS id,
    'Auswählen ====>'::character varying AS opzione,
    '0'::character varying AS project_name
UNION ALL
 SELECT foo.id,
    foo.opzione,
    foo.project_name
   FROM ( SELECT catalog.catalog_id AS id,
            catalog.catalog_name AS opzione,
            catalog.project_name
           FROM catalog
          ORDER BY catalog.catalog_name) foo;

ALTER TABLE seldb_catalog
  OWNER TO gisclient;

CREATE OR REPLACE VIEW seldb_charset_encodings AS 
 SELECT foo.id,
    foo.opzione,
    foo.option_order
   FROM ( SELECT (-1) AS id,
            'Auswählen ====>'::character varying AS opzione,
            0::smallint AS option_order
        UNION
         SELECT e_charset_encodings.charset_encodings_id AS id,
            e_charset_encodings.charset_encodings_name AS opzione,
            e_charset_encodings.charset_encodings_order AS option_order
           FROM e_charset_encodings) foo
  ORDER BY foo.id;

ALTER TABLE seldb_charset_encodings
  OWNER TO gisclient;

CREATE OR REPLACE VIEW seldb_conntype AS 
 SELECT (-1) AS id,
    'Auswählen ====>'::character varying AS opzione
UNION
( SELECT e_conntype.conntype_id AS id,
    e_conntype.conntype_name AS opzione
   FROM e_conntype
  ORDER BY e_conntype.conntype_order);

ALTER TABLE seldb_conntype
  OWNER TO gisclient;

CREATE OR REPLACE VIEW seldb_field_filter AS 
 SELECT (-1) AS id,
    'Keiner'::character varying AS opzione,
    0 AS qtfield_id,
    0 AS qt_id
UNION
( SELECT x.field_id AS id,
    x.field_header AS opzione,
    y.field_id AS qtfield_id,
    x.layer_id AS qt_id
   FROM field x
     JOIN field y USING (layer_id)
  WHERE x.field_id <> y.field_id
  ORDER BY x.field_id, x.field_order);

ALTER TABLE seldb_field_filter
  OWNER TO gisclient;

CREATE OR REPLACE VIEW seldb_filetype AS 
 SELECT (-1) AS id,
    'Auswählen ====>'::character varying AS opzione
UNION
 SELECT e_filetype.filetype_id AS id,
    e_filetype.filetype_name AS opzione
   FROM e_filetype;

ALTER TABLE seldb_filetype
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW seldb_font AS 
 SELECT foo.id,
    foo.opzione
   FROM ( SELECT ''::character varying AS id,
            'Auswählen ====>'::character varying AS opzione
        UNION
         SELECT font.font_name AS id,
            font.font_name AS opzione
           FROM font) foo
  ORDER BY foo.id;

ALTER TABLE seldb_font
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW seldb_language AS 
 SELECT foo.id,
    foo.opzione
   FROM ( SELECT ''::text AS id,
            'Auswählen ====>'::character varying AS opzione
        UNION
         SELECT e_language.language_id AS id,
            e_language.language_name AS opzione
           FROM e_language) foo
  ORDER BY foo.id;

ALTER TABLE seldb_language
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW seldb_layer_layergroup AS 
 SELECT (-1) AS id,
    'Auswählen ====>'::character varying AS opzione,
    NULL::integer AS layergroup_id
UNION
( SELECT DISTINCT layer.layer_id AS id,
    layer.layer_name AS opzione,
    layer.layergroup_id
   FROM layer
  WHERE layer.queryable = 1::numeric
  ORDER BY layer.layer_name, layer.layer_id, layer.layergroup_id);

ALTER TABLE seldb_layer_layergroup
  OWNER TO gisclient;

CREATE OR REPLACE VIEW seldb_layertype AS 
 SELECT foo.id,
    foo.opzione
   FROM ( SELECT (-1) AS id,
            'Auswählen ====>'::character varying AS opzione
        UNION
         SELECT e_layertype.layertype_id AS id,
            e_layertype.layertype_name AS opzione
           FROM e_layertype) foo
  ORDER BY foo.id;

ALTER TABLE seldb_layertype
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW seldb_lblposition AS 
 SELECT ''::character varying AS id,
    'Auswählen ====>'::character varying AS opzione
UNION ALL
 SELECT e_lblposition.lblposition_name AS id,
    e_lblposition.lblposition_name AS opzione
   FROM e_lblposition
  WHERE e_lblposition.lblposition_name::text = 'AUTO'::text
UNION ALL
 SELECT foo.id,
    foo.opzione
   FROM ( SELECT e_lblposition.lblposition_name AS id,
            e_lblposition.lblposition_name AS opzione
           FROM e_lblposition
          WHERE e_lblposition.lblposition_name::text <> 'AUTO'::text
          ORDER BY e_lblposition.lblposition_order) foo;

ALTER TABLE seldb_lblposition
  OWNER TO gisclient;

CREATE OR REPLACE VIEW seldb_link AS 
 SELECT (-1) AS id,
    'Auswählen ====>'::character varying AS opzione,
    ''::character varying AS project_name
UNION
 SELECT link.link_id AS id,
    link.link_name AS opzione,
    link.project_name
   FROM link;

ALTER TABLE seldb_link
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW seldb_mapset_tiles AS 
 SELECT 0 AS id,
    'KEINE TILES'::character varying AS opzione
UNION ALL
 SELECT e_owstype.owstype_id AS id,
    e_owstype.owstype_name AS opzione
   FROM e_owstype
  WHERE e_owstype.owstype_id = ANY (ARRAY[2, 3]);

ALTER TABLE seldb_mapset_tiles
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW seldb_papersize AS 
 SELECT (-1) AS id,
    'Auswählen ====>'::character varying AS opzione
UNION
 SELECT e_papersize.papersize_id AS id,
    e_papersize.papersize_name AS opzione
   FROM e_papersize;

ALTER TABLE seldb_papersize
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW seldb_project AS 
 SELECT ''::character varying AS id,
    'Auswählen ====>'::character varying AS opzione
UNION
( SELECT DISTINCT project.project_name AS id,
    project.project_name AS opzione
   FROM project
  ORDER BY project.project_name);

ALTER TABLE seldb_project
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW seldb_qt AS 
 SELECT (-1) AS id,
    'Auswählen ====>'::character varying AS opzione,
    ''::character varying AS mapset_name
UNION ALL
 SELECT qt.qt_id AS id,
    qt.qt_name AS opzione,
    mapset_layergroup.mapset_name
   FROM qt qt
     LEFT JOIN layer USING (layer_id)
     LEFT JOIN mapset_layergroup USING (layergroup_id);

ALTER TABLE seldb_qt
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW seldb_theme AS 
 SELECT (-1) AS id,
    'Auswählen ====>'::character varying AS opzione,
    ''::character varying AS project_name
UNION
 SELECT theme.theme_id AS id,
    theme.theme_name AS opzione,
    theme.project_name
   FROM theme;

ALTER TABLE seldb_theme
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW seldb_tilegrid AS 
 SELECT (-1) AS id,
    'Auswählen ====>'::character varying AS opzione
UNION ALL
 SELECT e_tilegrid.tilegrid_id AS id,
    e_tilegrid.tilegrid_title AS opzione
   FROM e_tilegrid;

ALTER TABLE seldb_tilegrid
  OWNER TO gisclient;
  
CREATE OR REPLACE VIEW seldb_wmsversion AS 
 SELECT NULL::smallint AS id,
    'Auswählen ====>'::character varying AS opzione,
    (-1) AS wmsversion_order
UNION
 SELECT e_wmsversion.wmsversion_id AS id,
    e_wmsversion.wmsversion_name AS opzione,
    e_wmsversion.wmsversion_order
   FROM e_wmsversion
  ORDER BY 3;

ALTER TABLE seldb_wmsversion
  OWNER TO gisclient;

-------------------- CAMBIA LE VIEW DI CONTROLLO DB --------------------

DROP VIEW IF EXISTS vista_catalog;
CREATE OR REPLACE VIEW vista_catalog as
select c.*,
CASE
  WHEN connection_type != 6 then '(i) Kontrolle unmöglich: keine PostGIS Verbindung'
  WHEN substring(c.catalog_path,0,position('/' in c.catalog_path)) != current_database() then '(i) Kontrolle unmöglich: unterschiedliches DB'
  WHEN substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) not in (select schema_name from information_schema.schemata) THEN '(!) Das Schema existiert nicht im DB'
  ELSE 'OK'
END as catalog_control
  from catalog c;
 
ALTER TABLE vista_catalog
  OWNER TO gisclient; 

create or replace view vista_class as 
select c.*,
CASE
  WHEN expression is null AND class_order <= (select max(class_order) from class where layer_id=c.layer_id and class_id != c.class_id and expression is not null) then '(!) Klasse mit leerer Aussageform; nach unten verschieben'
  WHEN legendtype_id = 1 and class_id not in (select class_id from style) then '(!) In Legende anzeigen, aber kein Stil definiert'
  WHEN label_font is not null and label_color is not null and label_size is not null and label_position is not null and labelitem is null then '(!) Label korrekt konfiguriert, aber kein Label-Feld auf dem Layer konfiguriert'
  WHEN label_font is not null and label_color is not null and label_size is not null and label_position is not null and labelitem is not null then 'OK. (i) Mit Label'
  ELSE 'OK'
END as class_control
FROM class c
JOIN layer l USING (layer_id);

ALTER TABLE vista_class
  OWNER TO gisclient;

DROP VIEW IF EXISTS vista_field;
CREATE OR REPLACE VIEW vista_field AS 
 SELECT field.field_id, field.layer_id, field.fieldtype_id, x.relation_id, field.field_name, field.resultype_id, field.field_header, field.field_order, COALESCE(field.column_width, 0) AS column_width, x.name AS relation_name, x.relationtype_id, x.relationtype_name, field.editable, 
        CASE
            WHEN field.relation_id = 0 THEN 
            CASE
                WHEN c.connection_type <> 6 THEN '(i) Kontrolle unmöglich: keine PostGIS Verbindung'::text
                WHEN "substring"(c.catalog_path::text, 0, "position"(c.catalog_path::text, '/'::text)) <> current_database()::text THEN '(i) Kontrolle unmöglich: unterschiedliches DB'::text
                WHEN NOT (field.field_name::text IN ( SELECT columns.column_name
                   FROM information_schema.columns
                  WHERE "substring"(c.catalog_path::text, "position"(c.catalog_path::text, '/'::text) + 1, length(c.catalog_path::text)) = i.table_schema::text AND l.data::text = i.table_name::text)) THEN '(!) Das Feld ist in der Tabelle nicht vorhanden'::text
                ELSE 'OK'::text
            END
            ELSE 
            CASE
                WHEN cr.connection_type <> 6 THEN '(i) Kontrolle unmöglich: keine PostGIS Verbindung'::text
                WHEN "substring"(cr.catalog_path::text, 0, "position"(cr.catalog_path::text, '/'::text)) <> current_database()::text THEN '(i) Kontrolle unmöglich: unterschiedliches DB'::text
                WHEN NOT (field.field_name::text IN ( SELECT columns.column_name
                   FROM information_schema.columns
                  WHERE "substring"(cr.catalog_path::text, "position"(cr.catalog_path::text, '/'::text) + 1, length(cr.catalog_path::text)) = i.table_schema::text AND r.table_name::text = i.table_name::text)) THEN '(!) Feld nicht gefunden in der Relations-Tabelle: '::text || r.relation_name::text
                ELSE 'OK'::text
            END
        END AS field_control
   FROM field
   JOIN e_fieldtype USING (fieldtype_id)
   JOIN ( SELECT y.relationtype_id, y.relation_id, y.name, z.relationtype_name
      FROM (         SELECT 0 AS relation_id, 'Data Layer'::character varying AS name, 0 AS relationtype_id
           UNION 
                    SELECT relation.relation_id, COALESCE(relation.relation_name, 'Keine Relation'::character varying) AS name, relation.relationtype_id
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

ALTER TABLE vista_field
  OWNER TO gisclient;

DROP VIEW IF EXISTS vista_layer;
CREATE OR REPLACE VIEW vista_layer AS 
 SELECT l.*, 
        CASE
          WHEN queryable = 1 and l.hidden = 0 and 
               layer_id IN (SELECT field.layer_id 
                              FROM field 
                              WHERE field.resultype_id != 4)
          THEN 'JA. Konfig. OK'
          WHEN queryable = 1 and l.hidden = 1 and
               layer_id IN (SELECT field.layer_id 
                              FROM field 
                              WHERE field.resultype_id != 4)
          THEN 'JA. Aber versteckt'
          WHEN queryable = 1 and 
               layer_id IN (SELECT field.layer_id 
                              FROM field 
                              WHERE field.resultype_id = 4)
          THEN 'NEIN. Resultat enthä kein Feld'
          ELSE 'NEIN. WFS nicht aktiv'
        END AS is_queryable, 
        CASE
            WHEN queryable = 1 and layer_id IN ( SELECT field.layer_id
               FROM field
              WHERE field.editable = 1)
            THEN 'JA. Konfig. OK' 
            WHEN queryable = 1 and layer_id IN ( SELECT field.layer_id
               FROM field
              WHERE field.editable = 0)
            THEN 'NEIN. Kein Feld ist editierbar' 
            WHEN queryable = 0 and layer_id IN ( SELECT field.layer_id
               FROM field
              WHERE field.editable = 1)
            THEN 'NEIN. Editierbare Felder vorhanden, aber WFS nicht aktiv' 
            ELSE 'NEIN.'
        END AS is_editable,
        CASE
            WHEN connection_type != 6 then '(i) Kontrolle unmöglich: keine PostGIS Verbindung'
            WHEN substring(c.catalog_path,0,position('/' in c.catalog_path)) != current_database() then '(i) Kontrolle unmöch: unterschiedliches DB'
            WHEN data not in (select table_name FROM information_schema.tables where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)))  THEN '(!) Die Tabelle existiert nicht im DB'
            when data_geom not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = data and data_type = 'USER-DEFINED') then '(!) Das geometrische Feld im Layer existiert nicht'
            when data_unique not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = data) then '!)Das Index-Feld im Layer existiert nicht'
            when data_srid not in (select srid FROM public.geometry_columns where f_table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and f_table_name=data) then '(!) Der konfigurierte SRID ist nicht korrekt'
            when upper(data_type) not in (select type FROM public.geometry_columns where f_table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and f_table_name=data) then '(!) Geometrytype nicht korrekt'
            WHEN labelitem not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = data) then '(!)Das Label-Feld im Layer existiert nicht'
            WHEN labelitem not in (select field_name FROM field where layer_id = l.layer_id) then '(!) Kein Label-Feld unter den Feldern des Layers'
            WHEN labelsizeitem not in (select column_name FROM information_schema.columns where table_schema=substring(catalog_path,position('/' in catalog_path)+1,length(catalog_path)) and table_name = data) then '(!) Das Feld Labelgrö im Layer existiert nicht'
            WHEN labelsizeitem not in (select field_name FROM field where layer_id = l.layer_id) then '(!)Kein Feld Label-Größe unter den Feldern des Layers'
            --WHEN layer_name in (select distinct layer_name FROM layer where layergroup_id != lg.layergroup_id and catalog_id in (select catalog_id FROM catalog where project_name = c.project_name)) THEN '(!) Kombination layergroup Name + layer Name nicht eindeutig. Einen der beiden Namen ändern'
            WHEN t.project_name||'.'||lg.layergroup_name||'.'||l.layer_name IN (select t2.project_name||'.'||lg2.layergroup_name||'.'||l2.layer_name 
              FROM layer l2
              JOIN layergroup lg2 using (layergroup_id)
              JOIN theme t2 using (theme_id)
              group by t2.project_name||'.'||lg2.layergroup_name||'.'||l2.layer_name
              having count(t2.project_name||'.'||lg2.layergroup_name||'.'||l2.layer_name) > 1) 
              THEN '(!) Kombination layergroup Name + layer Name nicht eindeutig. Einen der beiden Namen ändern'
            WHEN layer_id not in (select layer_id FROM class) then 'OK (i) Keine Klassen in diesem Layer konfiguriert'
            ELSE 'OK'
          END as layer_control
   FROM layer l
JOIN catalog c using (catalog_id)
JOIN e_layertype using (layertype_id)
JOIN layergroup lg using (layergroup_id)
JOIN theme t using (theme_id);
ALTER TABLE vista_layer
  OWNER TO gisclient;

DROP VIEW IF EXISTS vista_layergroup;
CREATE OR REPLACE VIEW vista_layergroup AS 
select lg.*,
CASE 
  WHEN tiles_extent_srid is not null and tiles_extent_srid not in (select srid from project_srs where project_name=t.project_name) THEN '(!) SRID extension tiles im Koordinatensystems des Projekts nicht vorhanden'
  WHEN owstype_id=6 and url is null then '(!) Keine URL für TMS-Aufruf konfiguriert'
  WHEN owstype_id=6 and layers is null then '(!)Kein Layer für TMS-Aufruf konfiguriert'
  WHEN owstype_id=9 and url is null then '(!)Keine URL für WMTS-Aufruf konfiguriert'
  WHEN owstype_id=9 and layers is null then '(!)Kein Layer für WMTS-Aufruf konfiguriert'
  WHEN owstype_id=9 and tile_matrix_set is null then '(!)Keine Tile Matrix für WMTS-Aufruf konfiguriert'
  WHEN owstype_id=9 and style is null then '(!)Kein Stil für WMTS-Aufruf konfiguriert'
  WHEN owstype_id=9 and tile_origin is null then '(!) Keine Koordinatenursprung für WMTS-Aufruf konfiguriert'
  WHEN lg.opacity is null or lg.opacity = '0' then '(i) Achtung: vollstäige Trasparenz'
  WHEN (layergroup_id not in (select layergroup_id FROM layer)) AND layers is null then 'OK (i) Keine Layer konfiguriert'
  ELSE 'OK'
END as layergroup_control

from layergroup lg
JOIN theme t USING (theme_id);

ALTER TABLE vista_layergroup
  OWNER TO gisclient;  

DROP VIEW IF EXISTS vista_link;
CREATE OR REPLACE VIEW vista_link AS 
 SELECT l.link_id, l.project_name, l.link_name, l.link_def, l.link_order, l.winw, l.winh, 
        CASE
            WHEN l.link_def::text !~~ 'http%://%@%@'::text THEN '(!) Link Einstellung nicht gültig. Beispiel: http://url@feld@'::text
            WHEN NOT (l.link_id IN ( SELECT link.link_id
               FROM layer_link link)) THEN 'OK. Nicht benutzt'::text
            WHEN NOT (replace("substring"(l.link_def::text, '%#"@%@#"%'::text, '#'::text), '@'::text, ''::text) IN ( SELECT qtfield.field_name AS qtfield_name
               FROM field qtfield
              WHERE (qtfield.layer_id IN ( SELECT link.layer_id
                       FROM layer_link link
                      WHERE link.link_id = l.link_id)))) THEN '(!) Das Feld ist nicht vorhanden'::text
            ELSE 'OK. Benutzt'::text
        END AS link_control
   FROM link l;

ALTER TABLE vista_link
  OWNER TO gisclient;

DROP VIEW IF EXISTS vista_mapset;
CREATE OR REPLACE VIEW vista_mapset AS 
select m.*,
  CASE 
    when mapset_name not in (select mapset_name from mapset_layergroup) then '(!) Keine Layergruppe in diesem Mapset'
    when 75 <= (select count(layergroup_id) from mapset_layergroup where mapset_name=m.mapset_name group by mapset_name) then '(!) Openlayers kann nicht mehr als 75 layergroup auf einmal darstellen'
    WHEN mapset_scales is null THEN '(!) Kein Massstab-Verzeichnis konfiguriert'
    WHEN mapset_srid != displayprojection then '(i) Angezeigte Koordinaten sind verschieden von denen der Karte'
    WHEN 0 = (select max(refmap) from mapset_layergroup where mapset_name=m.mapset_name group by mapset_name) THEN '(i) Keine reference map'
    ELSE 'OK'
  END as mapset_control
from mapset m;

ALTER TABLE vista_mapset
  OWNER TO gisclient;  

DROP VIEW IF EXISTS vista_relation;
CREATE OR REPLACE VIEW vista_relation AS 
 SELECT r.relation_id, r.catalog_id, r.relation_name, r.relationtype_id, r.data_field_1, r.data_field_2, r.data_field_3, r.table_name, r.table_field_1, r.table_field_2, r.table_field_3, r.language_id, r.layer_id, 
        CASE
            WHEN c.connection_type <> 6 THEN '(i) Kontrolle unmöglich: keine PostGIS Verbindung'
            WHEN "substring"(c.catalog_path, 0, "position"(c.catalog_path, '/')) <> current_database() THEN '(i) Kontrolle unmöch: unterschiedliches DB'
            WHEN NOT (l.layer_name IN ( SELECT tables.table_name
               FROM information_schema.tables
              WHERE tables.table_schema = "substring"(c.catalog_path, "position"(c.catalog_path, '/') + 1, length(c.catalog_path)))) THEN '(!) Die DB-Tabelle des Layers existiert nicht'
            WHEN NOT (r.table_name IN ( SELECT tables.table_name
               FROM information_schema.tables
              WHERE tables.table_schema = "substring"(c.catalog_path, "position"(c.catalog_path, '/') + 1, length(c.catalog_path)))) THEN '(!) Die JOIN Tabelle des DB existiert nicht'
            WHEN r.data_field_1 IS NULL OR r.table_field_1 IS NULL THEN '(!) Ein Feld der JOIN 1 ist leer'
            WHEN NOT (r.data_field_1 IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema = "substring"(c.catalog_path, "position"(c.catalog_path, '/') + 1, length(c.catalog_path)) AND columns.table_name = l.layer_name)) THEN '(!) Das Index-Feld layer existiert nicht'
            WHEN NOT (r.table_field_1 IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema = "substring"(c.catalog_path, "position"(c.catalog_path, '/') + 1, length(c.catalog_path)) AND columns.table_name = r.table_name)) THEN '(!) Das Index-Feld der Relation existiert nicht'
            WHEN r.data_field_2 IS NULL AND r.table_field_2 IS NULL THEN 'OK'
            WHEN r.data_field_2 IS NULL OR r.table_field_2 IS NULL THEN '(!) Ein Feld der JOIN 2 ist leer'
            WHEN NOT (r.data_field_2 IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema = "substring"(c.catalog_path, "position"(c.catalog_path, '/') + 1, length(c.catalog_path)) AND columns.table_name = l.layer_name)) THEN '(!) Das Index-Feld layer der JOIN 2 existiert nicht'
            WHEN NOT (r.table_field_2 IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema = "substring"(c.catalog_path, "position"(c.catalog_path, '/') + 1, length(c.catalog_path)) AND columns.table_name = r.table_name)) THEN '(!)Das Index-Feld Relation der JOIN 2 existiert nicht'
            WHEN r.data_field_3 IS NULL AND r.table_field_3 IS NULL THEN 'OK'
            WHEN r.data_field_3 IS NULL OR r.table_field_3 IS NULL THEN '(!) Ein Feld der JOIN 3 ist leer'
            WHEN NOT (r.data_field_3 IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema = "substring"(c.catalog_path, "position"(c.catalog_path, '/') + 1, length(c.catalog_path)) AND columns.table_name = l.layer_name)) THEN '(!)Das Index-Feld Relation der JOIN 3 existiert nicht'
            WHEN NOT (r.table_field_3 IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE columns.table_schema = "substring"(c.catalog_path, "position"(c.catalog_path, '/') + 1, length(c.catalog_path)) AND columns.table_name = r.table_name)) THEN '(!)Das Index-Feld Relation der JOIN 3 existiert nicht'
            ELSE 'OK'::text
        END AS relation_control
   FROM relation r
   JOIN catalog c USING (catalog_id)
   JOIN layer l USING (layer_id)
   JOIN e_relationtype rt USING (relationtype_id);

ALTER TABLE vista_relation
  OWNER TO gisclient;

DROP VIEW IF EXISTS vista_style;
CREATE OR REPLACE VIEW vista_style AS 
 SELECT s.style_id, s.class_id, s.style_name, s.symbol_name, s.color, 
    s.outlinecolor, s.bgcolor, s.angle, s.size, s.minsize, s.maxsize, s.width, 
    s.maxwidth, s.minwidth, s.locked, s.style_def, s.style_order, s.pattern_id, 
        CASE
            WHEN NOT (s.symbol_name IN ( SELECT symbol_name
               FROM symbol)) THEN '(!) Symbol existiert nicht'
            WHEN s.color IS NULL AND s.outlinecolor IS NULL AND s.bgcolor IS NULL THEN '(!) Stile ohne Farben'
            WHEN s.symbol_name IS NOT NULL AND s.size IS NULL THEN '(!) Stil ohne Grönangabe'
            ELSE 'OK'
        END AS style_control
   FROM style s
   LEFT JOIN symbol USING (symbol_name)
  ORDER BY s.style_order;

ALTER TABLE vista_style
  OWNER TO gisclient;