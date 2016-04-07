BEGIN;

SET search_path = gisclient_34, pg_catalog;

--tabelle eliminate e_tile_type, authfilter, group_authfilter, layer_authfilter
--vedere modifiche a project_srs
--qtrelation sbagliata


--select column_name from information_schema.columns where table_schema='gisclient_25' and table_name='qtrelation' order by ordinal_position;

--select 'INSERT INTO gisclient_3.'||table_name||' SELECT * FROM gisclient_25.'||table_name||';' as testo 
--from information_schema.tables where table_schema='gisclient_25' and table_name like 'e_%' order by table_name;

INSERT INTO e_conntype SELECT * FROM gisclient_25.e_conntype;
INSERT INTO e_datatype SELECT * FROM gisclient_25.e_datatype;
INSERT INTO e_fieldformat SELECT * FROM gisclient_25.e_fieldformat;
INSERT INTO e_fieldtype SELECT * FROM gisclient_25.e_fieldtype;
INSERT INTO e_filetype SELECT * FROM gisclient_25.e_filetype;
INSERT INTO e_language SELECT * FROM gisclient_25.e_language;
INSERT INTO e_layertype SELECT * FROM gisclient_25.e_layertype;
INSERT INTO e_lblposition SELECT * FROM gisclient_25.e_lblposition;
INSERT INTO e_legendtype SELECT * FROM gisclient_25.e_legendtype;
INSERT INTO e_orderby SELECT * FROM gisclient_25.e_orderby;
INSERT INTO e_papersize SELECT * FROM gisclient_25.e_papersize;
INSERT INTO e_relationtype SELECT * FROM gisclient_25.e_qtrelationtype;
INSERT INTO e_resultype SELECT * FROM gisclient_25.e_resultype;
INSERT INTO e_searchtype SELECT * FROM gisclient_25.e_searchtype;
INSERT INTO e_sizeunits SELECT * FROM gisclient_25.e_sizeunits;
INSERT INTO e_symbolcategory SELECT * FROM gisclient_25.e_symbolcategory;
INSERT INTO font SELECT * FROM gisclient_25.font;

--AGGIORNAMENTO SIMBOLI--
--INSERISCO I PATTERN EREDITATI DAGLI STYLE CHE VENGONO APPLICATI ALLE LINEE IN MAPSERVER 5
insert into e_pattern(pattern_name,pattern_def)
select symbol_name,'PATTERN' ||replace(substring(symbol_def from 'STYLE(.+)END'),'\n',' ') || 'END' from gisclient_25.symbol where symbol_def like '%STYLE%';

--INSERISCO GLI ALTRI SIMBOLI
INSERT INTO symbol(symbol_name,symbolcategory_id,icontype,symbol_image,symbol_def) SELECT symbol_name,symbolcategory_id,icontype,symbol_image,symbol_def 
FROM gisclient_25.symbol WHERE NOT (symbol_def LIKE '%STYLE%' OR  symbol_def LIKE '%CARTOLINE%');
UPDATE symbol SET symbol_def=regexp_replace(symbol_def, '\nGAP(.+)', '')  WHERE symbol_def LIKE '%GAP%';

DELETE FROM e_outputformat;
INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (1, 'AGG PNG', 'AGG/PNG', 'image/png', 'RGBA', 'png', NULL, NULL);
INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (3, 'AGG JPG', 'AGG/JPG', 'image/jpeg', 'RGBA', 'jpg', NULL, NULL);
INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (4, 'PNG 8 bit', 'GD/PNG', 'image/png', 'PC256', 'png', NULL, NULL);
INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (5, 'PNG 24 bit', 'GD/PNG', 'image/png', 'RGBA', 'png', NULL, NULL);
INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (6, 'PNG 32 bit Trasp', 'GD/PNG', 'image/png', 'RGBA', 'png', NULL, NULL);
INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (7, 'AGG Q', 'AGG/PNG', 'image/png; mode=8bit', 'RGBA', 'png', '    FORMATOPTION "QUANTIZE_FORCE=ON"
    FORMATOPTION "QUANTIZE_DITHER=OFF"
    FORMATOPTION "QUANTIZE_COLORS=256"', NULL);
DELETE FROM e_owstype;
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (1, 'WMS', 1);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (2, 'WMTS', 2);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (3, 'WMS (tiles in cache)', 3);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (4, 'Mappe Yahoo', 9);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (5, 'OpenStreetMap', 5);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (6, 'TMS', 4);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (7, 'Mappe Google', 7);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (8, 'Mappe Bing', 8);


INSERT INTO users SELECT * FROM gisclient_25.users;
INSERT INTO groups SELECT * FROM gisclient_25.groups;
INSERT INTO user_group SELECT * FROM gisclient_25.user_group;
INSERT INTO project (
"project_name",
"project_title",
"project_description",
"base_path",
"base_url",
"project_extent",
"sel_user_color",
"sel_transparency",
"imagelabel_font",
"imagelabel_text",
"imagelabel_offset_x",
"imagelabel_offset_y",
"imagelabel_position",
"icon_w",
"icon_h",
"history",
"project_srid",
"imagelabel_size",
"imagelabel_color",
"login_page",
"project_note")
SELECT
lower("project_name"),
"project_title",
"project_description",
"base_path",
"base_url",
"project_extent",
"sel_user_color",
"sel_transparency",
"imagelabel_font",
"imagelabel_text",
"imagelabel_offset_x",
"imagelabel_offset_y",
"imagelabel_position",
"icon_w",
"icon_h",
"history",
"project_srid",
"imagelabel_size",
"imagelabel_color",
"login_page",
"project_note"
FROM gisclient_25.project;
--CALCOLA CENTRO DA ESTENSIONE
UPDATE project SET xc = split_part(project_extent,' ',1)::float + (split_part(project_extent,' ',3)::float - split_part(project_extent,' ',1)::float)/2,yc = split_part(project_extent,' ',2)::float + (split_part(project_extent,' ',4)::float - split_part(project_extent,' ',2)::float)/2;
INSERT INTO project_admin (project_name,username) SELECT lower(project_name),username FROM gisclient_25.project_admin;
INSERT INTO project_srs(project_name,srid,projparam) SELECT lower(project_name),srid,param FROM gisclient_25.project_srs;
INSERT INTO link SELECT link_id,lower(project_name),link_name,link_def,link_order,winw,winh FROM gisclient_25.link;

INSERT INTO catalog(
"catalog_id",
"catalog_name",
"project_name",
"connection_type",
"catalog_path",
"catalog_url",
"catalog_description")
SELECT
"catalog_id",
lower("catalog_name"),
lower("project_name"),
"connection_type",
"catalog_path",
"catalog_url",
"catalog_description"
FROM gisclient_25.catalog;
INSERT INTO theme(
"theme_id",
"project_name",
"theme_name",
"theme_title",
"theme_order",
"locked")
SELECT 
"theme_id",
lower("project_name"),
lower("theme_name"),
"theme_title",
"theme_order",
"locked"
FROM gisclient_25.theme;
INSERT INTO layergroup(
"layergroup_id",
"theme_id",
"layergroup_name",
"layergroup_title",
"layergroup_maxscale",
"layergroup_minscale",
"layergroup_smbscale",
"layergroup_order",
"locked",
"multi",
"hidden")
SELECT
"layergroup_id",
"theme_id",
lower("layergroup_name"),
"layergroup_title",
"layergroup_maxscale",
"layergroup_minscale",
"layergroup_smbscale",
"layergroup_order",
"locked",
"multi",
"hidden"
FROM gisclient_25.layergroup;
INSERT INTO layer(
"layer_id",
"layergroup_id",
"layer_name",
"layertype_id",
"catalog_id",
"data",
"data_geom",
"data_unique",
"data_srid",
"data_filter",
"classitem",
"labelitem",
"labelsizeitem",
"labelminscale",
"labelmaxscale",
"maxscale",
"minscale",
"symbolscale",
"opacity",
"maxfeatures",
"sizeunits_id",
"layer_def",
"metadata",
"template",
"header",
"footer",
"tolerance",
"layer_order")
SELECT 
"layer_id",
"layergroup_id",
lower("layer_name"),
"layertype_id",
"catalog_id",
"data",
"data_geom",
"data_unique",
"data_srid",
"data_filter",
"classitem",
"labelitem",
"labelsizeitem",
"labelminscale",
"labelmaxscale",
"maxscale",
"minscale",
"symbolscale",
"transparency",
"maxfeatures",
"sizeunits_id",
"layer_def",
"metadata",
"template",
"header",
"footer",
"tolerance",
"layer_order"
FROM gisclient_25.layer;
INSERT INTO class(
"class_id",
"layer_id",
"class_order",
"class_name",
"class_title",
"class_text",
"expression",
"maxscale",
"minscale",
"class_template",
"legendtype_id",
"symbol_ttf_name",
"label_font",
"label_angle",
"label_color",
"label_outlinecolor",
"label_bgcolor",
"label_size",
"label_minsize",
"label_maxsize",
"label_position",
"label_antialias",
"label_free",
"label_priority",
"label_wrap",
"label_buffer",
"label_force",
"label_def",
"class_image")
SELECT
"class_id",
"layer_id",
"class_order",
lower("class_name"),
"class_title",
"class_text",
"expression",
"maxscale",
"minscale",
"class_template",
"legendtype_id",
"symbol_ttf_name",
"label_font",
"label_angle",
"label_color",
"label_outlinecolor",
"label_bgcolor",
"label_size",
"label_minsize",
"label_maxsize",
"label_position",
"label_antialias",
"label_free",
"label_priority",
"label_wrap",
"label_buffer",
"label_force",
"label_def",
"class_image"
FROM gisclient_25.class;
INSERT INTO style(
"style_id",
"class_id",
"style_name",
"symbol_name",
"color",
"outlinecolor",
"bgcolor",
"angle",
"size",
"minsize",
"maxsize",
"width",
"maxwidth",
"minwidth",
"locked",
"style_def",
"style_order")
SELECT
"style_id",
"class_id",
lower("style_name"),
"symbol_name",
"color",
"outlinecolor",
"bgcolor",
"angle",
"size",
"minsize",
"maxsize",
"width",
"maxwidth",
"minwidth",
"locked",
"style_def",
"style_order"
FROM gisclient_25.style;
INSERT INTO mapset(
"mapset_name",
"project_name",
"mapset_title",
"template",
"mapset_extent",
"page_size",
"filter_data",
"dl_image_res",
"imagelabel",
"bg_color",
"refmap_extent",
"test_extent",
"mapset_srid",
"mapset_def",
"mapset_group",
"private",
"sizeunits_id",
"static_reference",
"metadata",
"mask",
"mapset_description",
"mapset_note")
SELECT
"mapset_name",
"project_name",
"mapset_title",
"template",
"mapset_extent",
"page_size",
"filter_data",
"dl_image_res",
"imagelabel",
"bg_color",
"refmap_extent",
"test_extent",
"mapset_srid",
"mapset_def",
"mapset_group",
"private",
"sizeunits_id",
"static_reference",
"metadata",
"mask",
"mapset_description",
"mapset_note"
FROM gisclient_25.mapset;
INSERT INTO mapset_layergroup(
"mapset_name",
"layergroup_id",
"status",
"refmap")
SELECT
"mapset_name",
"layergroup_id",
"status",
"refmap"
FROM gisclient_25.mapset_layergroup;


--MANCANO I CAMPI DI RICERCA---












--SISTEMI DI RIFERIMENTO--
INSERT INTO project_srs(project_name,srid)
SELECT DISTINCT project_name,data_srid FROM  gisclient_25.layer 
INNER JOIN gisclient_25.layergroup USING(layergroup_id) 
INNER JOIN gisclient_25.theme USING (theme_id)
WHERE data_srid NOT IN (SELECT srid FROM project_srs)
ORDER BY 2;


--SISTEMIAMO IL DATO--
UPDATE layer SET layer_name = replace(layer_name,' ','_');
UPDATE e_layertype set layertype_id = 10 WHERE layertype_id = 11;
UPDATE e_layertype set layertype_name = 'tileraster', layertype_ms = 100 WHERE layertype_id = 10;    
DELETE FROM e_layertype where layertype_name = 'tileindex';
DELETE FROM e_layertype where layertype_ms = 99;
UPDATE layer SET sizeunits_id=1 where sizeunits_id = -1;
UPDATE layer set catalog_id=0 where catalog_id=-1;

--SIMBOLOGIA--
--AGGIORNAMENTO DEL VALORE DI EXPRESSION IN CLASS (AGGIUNTE LE PARENTESI)
update class set expression='('||expression||')' where (expression like '(''[%' or expression like '''[%'  or expression like '[%' ) and not expression like '(%)';
UPDATE class SET keyimage = 'NO' from layer where layertype_id=5 and class.layer_id=layer.layer_id;
UPDATE symbol SET symbol_def=replace(symbol_def,'../','../../') WHERE symbol_def LIKE '%PIXMAP%' AND NOT symbol_def LIKE '%../../%';
--2014-6-20 aggiornamento del campo data_type
UPDATE layer SET data_type = 'point' WHERE layertype_id=1;
UPDATE layer SET data_type = 'linestring' WHERE layertype_id=2;
UPDATE layer SET data_type = 'multipolygon' WHERE layertype_id=3;
UPDATE layer SET data_type = 'point' WHERE layertype_id=4;
UPDATE layer SET data_type = 'point' WHERE layertype_id=5;



--AGGIORNO IL pattern_id DELLA TABELLA style CON I VALORI DELLE CHIAVI
update style set pattern_id=e_pattern.pattern_id,symbol_name=null from e_pattern where e_pattern.pattern_name=style.symbol_name;

--TOLGO DAI SIMBOLI QUELLI CHE SERVIVANO SOLO PER IL PATTERN
delete from symbol where symbol_def like '%STYLE%';
--ELIMINO LE KEYWORDS NON COMPATIBILI ( CONTROLLARE IL RISULTATO)
update symbol  set symbol_def=regexp_replace(symbol_def, '\nGAP(.+)', '')  where symbol_def like '%GAP%';
delete from symbol where symbol_def like '%CARTOLINE%';
--SPOSTO I SIMBOLI TTF DALLA CLASSI ALLA TABELLA SIMBOLI ED ELIMINO LA TABELLA SYMBOL_TTF 
--INSERISCO I NUOVI SIMBOLI NELLA TABELLA
insert into symbol (symbol_name,symbolcategory_id,icontype,symbol_type,font_name,ascii_code,symbol_def)
select  font_name||'_'||symbol_ttf_name,1,0,'TRUETYPE',font_name,ascii_code,'ANTIALIAS TRUE' from gisclient_25.symbol_ttf order by 1;
--AGGIUNGO GLI STILI ALLE CLASSI---
insert into style(style_id,class_id,style_name,symbol_name,color,angle,size,minsize,maxsize)
select class_id+(select max(style_id)from style),class_id,symbol_ttf_name,label_font||'_'||symbol_ttf_name,label_color,label_angle,label_size,label_minsize,label_maxsize from class where coalesce(symbol_ttf_name,'')<>'' and coalesce(label_font,'')<>'';

UPDATE mapset set template='jquery/default.html';

COMMIT;
