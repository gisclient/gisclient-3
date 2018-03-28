BEGIN;

SET search_path = gisclient_3, pg_catalog;

--tabelle eliminate e_tile_type, authfilter, group_authfilter, layer_authfilter
--vedere modifiche a project_srs
--qtrelation sbagliata


--select column_name from information_schema.columns where table_schema='gisclient_21' and table_name='qtrelation' order by ordinal_position;

--select 'INSERT INTO gisclient_3.'||table_name||' SELECT * FROM gisclient_21.'||table_name||';' as testo 
--from information_schema.tables where table_schema='gisclient_21' and table_name like 'e_%' order by table_name;

INSERT INTO users SELECT * FROM gisclient_21.users;
INSERT INTO groups SELECT * FROM gisclient_21.groups;
INSERT INTO user_group SELECT * FROM gisclient_21.user_group;
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
"project_note",
"xc",
"yc",
"max_extent_scale",
"default_language_id")
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
"project_note",
0,
0,
500000,
5
FROM gisclient_21.project;
--CALCOLA CENTRO DA ESTENSIONE
UPDATE project SET xc = split_part(project_extent,' ',1)::float + (split_part(project_extent,' ',3)::float - split_part(project_extent,' ',1)::float)/2,yc = split_part(project_extent,' ',2)::float + (split_part(project_extent,' ',4)::float - split_part(project_extent,' ',2)::float)/2;
INSERT INTO project_admin (project_name,username) SELECT lower(project_name),username FROM gisclient_21.project_admin;
INSERT INTO project_srs(project_name,srid,projparam) SELECT lower(project_name),srid,param FROM gisclient_21.project_srs;
INSERT INTO link SELECT link_id,lower(project_name),link_name,link_def,link_order,winw,winh FROM gisclient_21.link;

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
FROM gisclient_21.catalog;
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
FROM gisclient_21.theme;
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
"owstype_id",
"outputformat_id",
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
1,
1,
"hidden"
FROM gisclient_21.layergroup;
INSERT INTO layer(
"layer_id",
"layergroup_id",
"layer_name",
"layer_title",
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
replace("layer_name", '_', ' '),
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
FROM gisclient_21.layer;
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
FROM gisclient_21.class;
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
FROM gisclient_21.style;
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
FROM gisclient_21.mapset;
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
FROM gisclient_21.mapset_layergroup;


-- CAMPI DI RICERCA --

create table qtfield as select f.*, q.layer_id from gisclient_21.qtfield f, gisclient_21.qt q where  f.qt_id=q.qt_id;
create table qtrelation as select r.*, q.layer_id from gisclient_21.qtrelation r, gisclient_21.qt q where  r.qt_id=q.qt_id;

-- Aggiornamento qtfield con campo FORMULA
ALTER TABLE qtfield ADD COLUMN formula character varying;
UPDATE qtfield SET formula=qtfield_name WHERE qtfield_name LIKE '%(%' OR qtfield_name LIKE '%::%' OR qtfield_name LIKE '%||%';
UPDATE qtfield SET qtfield_name = 'formula_'||qtfield_id WHERE qtfield_name LIKE '%(%' OR qtfield_name LIKE '%::%' OR qtfield_name LIKE '%||%';
--UNICITA' DI FIELDNAME SU LAYER
delete from qtfield where qtfield_id in (select qtfield_id from (SELECT qtfield_id, ROW_NUMBER() OVER (partition BY qtfield_name, layer_id ORDER BY qtfield_id) AS rnum FROM qtfield) t where t.rnum > 1);
ALTER TABLE qtfield ADD CONSTRAINT qtfield_qtfield_name_layer_id_key UNIQUE(qtfield_name, layer_id);

create temp sequence temp_seq;

INSERT INTO qtfield (qtfield_id,qtfield_name,field_header,searchtype_id,resultype_id,qtrelation_id,fieldtype_id,orderby_id,qtfield_order,field_filter,datatype_id,layer_id)
SELECT (SELECT max(qtfield_id) FROM qtfield) + nextval('temp_seq')  as qtfield_id,c.column_name as qtfield_name,c.column_name as field_header,0,4,0,1,0,999,0,1,l.layer_id
FROM layer l, information_schema.columns c
WHERE c.table_name=l.data
AND (substring(l.data_filter from c.column_name || E'[ =<>!\\])]') is not null
    OR l.classitem=c.column_name
    OR l.labelitem=c.column_name
    OR l.labelsizeitem=c.column_name
    OR l.labelminscale=c.column_name
    OR l.labelmaxscale=c.column_name)
and  c.column_name || l.layer_id not in (SELECT qtfield_name || layer_id FROM qtfield)
GROUP BY l.layer_id,c.column_name
ORDER BY l.layer_id;

INSERT INTO qtfield (qtfield_id,qtfield_name,field_header,searchtype_id,resultype_id,qtrelation_id,fieldtype_id,orderby_id,qtfield_order,field_filter,datatype_id,layer_id)
SELECT (SELECT max(qtfield_id) FROM qtfield) + nextval('temp_seq')  as qtfield_id,c.column_name as qtfield_name,c.column_name as field_header,0,4,0,1,0,999,0,1,l.layer_id
FROM layer l, information_schema.columns c, class s
WHERE c.table_name=l.data
AND s.layer_id=l.layer_id
AND (substring(s.class_text from c.column_name || E'[ =<>!\\])]') is not null
    OR substring(s.expression from c.column_name || E'[ =<>!\\])]') is not null
    OR substring(s.label_angle from c.column_name || E'[ =<>!\\])]') is not null
    OR substring(s.label_size from c.column_name || E'[ =<>!\\])]') is not null)
AND  c.column_name || l.layer_id not in (SELECT qtfield_name || layer_id FROM qtfield)
GROUP BY l.layer_id,c.column_name
ORDER BY l.layer_id;

INSERT INTO qtfield (qtfield_id,qtfield_name,field_header,searchtype_id,resultype_id,qtrelation_id,fieldtype_id,orderby_id,qtfield_order,field_filter,datatype_id,layer_id)
SELECT (SELECT max(qtfield_id) FROM qtfield) + nextval('temp_seq') as qtfield_id,c.column_name as qtfield_name,c.column_name as field_header,0,4,0,1,0,999,0,1,l.layer_id
FROM layer l, information_schema.columns c, class s, style t
WHERE c.table_name=l.data
AND s.layer_id=l.layer_id
AND s.class_id=t.class_id
AND substring(t.angle from c.column_name || E'[ =<>!\\])]') is not null
AND  c.column_name || l.layer_id not in (SELECT qtfield_name || layer_id FROM qtfield)
GROUP BY l.layer_id,c.column_name
ORDER BY l.layer_id;

drop sequence temp_seq;

-- Togli __data__ di troppo per sicurezza (evitare i doppi)
update qtfield set formula=regexp_replace(formula, E'(__data__\\.)+', '') where formula like '%__data__%';

-- Aggiungi __data__ alle formule
update qtfield
set formula = _subq.new_formula
from(
select q.qtfield_id as _qtfield_id, regexp_replace(q.formula, column_name, E'__data__\.' || column_name) as new_formula
from qtfield q, information_schema.columns c
where formula is not null
AND qtrelation_id=0
AND (substring(q.formula from '^' || c.column_name || E'[ |:()]') is not null
  OR substring(q.formula from E'[ |().,]' || c.column_name || E'[ |:()]') is not null
  OR substring(q.formula from E'[ |().,]' || c.column_name || '$') is not null
  OR substring(q.formula from '^' || c.column_name || '$') is not null)
AND formula not like E'%__data__.' || c.column_name || '%'
GROUP BY q.formula,c.column_name,q.qtfield_id
ORDER BY q.qtfield_id
) AS _subq
WHERE qtfield_id=_subq._qtfield_id;

-- Ripeti che è meglio...
update qtfield
set formula = _subq.new_formula
from(
select q.qtfield_id as _qtfield_id, regexp_replace(q.formula, column_name, E'__data__\.' || column_name) as new_formula
from qtfield q, information_schema.columns c
where formula is not null
AND qtrelation_id=0
AND (substring(q.formula from '^' || c.column_name || E'[ |:()]') is not null
  OR substring(q.formula from E'[ |().]' || c.column_name || E'[ |:()]') is not null
  OR substring(q.formula from E'[ |().]' || c.column_name || '$') is not null
  OR substring(q.formula from E'^' || c.column_name || '$') is not null)
AND formula not like E'%__data__.' || c.column_name || '%'
GROUP BY q.formula,c.column_name,q.qtfield_id
ORDER BY q.qtfield_id
) AS _subq
WHERE qtfield_id=_subq._qtfield_id;

-- Aggiungi la tabella secondaria alle formule
update qtfield
set formula = _subq.new_formula
from(
select q.qtfield_id as _qtfield_id, regexp_replace(q.formula, column_name, r.qtrelation_name || '.' || column_name) as new_formula
from qtfield q, information_schema.columns c, qtrelation r
where formula is not null
AND q.qtrelation_id!=0
AND q.qtrelation_id=r.qtrelation_id
AND (substring(q.formula from '^' || c.column_name || E'[ |:()]') is not null
  OR substring(q.formula from E'[ |(),]' || c.column_name || E'[ |:()]') is not null
  OR substring(q.formula from E'[ |(),]' || c.column_name || '$') is not null
  OR substring(q.formula from '^' || c.column_name || '$') is not null)
AND formula not like E'%\\%%'
GROUP BY q.formula,c.column_name,q.qtfield_id, r.qtrelation_name
ORDER BY q.qtfield_id
) AS _subq
WHERE qtfield_id=_subq._qtfield_id;

-- Ripeti che è meglio...
update qtfield
set formula = _subq.new_formula
from(
select q.qtfield_id as _qtfield_id, regexp_replace(q.formula, column_name, r.qtrelation_name || '.' || column_name) as new_formula
from qtfield q, information_schema.columns c, qtrelation r
where formula is not null
AND q.qtrelation_id!=0
AND q.qtrelation_id=r.qtrelation_id
AND (substring(q.formula from '^' || c.column_name || E'[ |:()]') is not null
  OR substring(q.formula from E'[ |(),]' || c.column_name || E'[ |:()]') is not null
  OR substring(q.formula from E'[ |(),]' || c.column_name || '$') is not null
  OR substring(q.formula from '^' || c.column_name || '$') is not null)
AND formula not like E'%\\%%'
GROUP BY q.formula,c.column_name,q.qtfield_id, r.qtrelation_name
ORDER BY q.qtfield_id
) AS _subq
WHERE qtfield_id=_subq._qtfield_id;


-- Cancella campi non esistenti
DELETE FROM qtfield q WHERE qtfield_id NOT IN
(
SELECT q.qtfield_id
FROM qtfield q, layer l, information_schema.columns c
WHERE q.layer_id=l.layer_id
AND c.table_name=l.data
AND c.column_name = q.qtfield_name
)
AND formula IS NULL
AND qtrelation_id=0;

INSERT INTO field
(
  "field_id",
  "relation_id",
  "field_name",
  "field_header",
  "fieldtype_id",
  "searchtype_id",
  "resultype_id",
  "field_format",
  "column_width",
  "orderby_id",
  "field_filter",
  "datatype_id",
  "field_order",
  "default_op",
  "layer_id",
  "formula")
SELECT
  qtfield_id as field_id,
  qtrelation_id as relation_id,
  qtfield_name as field_name,
  field_header,
  fieldtype_id,
  searchtype_id,
  resultype_id,
  field_format,
  column_width,
  orderby_id,
  field_filter,
  datatype_id,
  qtfield_order as field_order,
  default_op,
  layer_id,
  formula
FROM qtfield;

INSERT INTO relation 
(
  "relation_id",
  "catalog_id",
  "relation_name",
  "relationtype_id",
  "data_field_1",
  "data_field_2",
  "data_field_3",
  "table_name",
  "table_field_1",
  "table_field_2",
  "table_field_3",
  "language_id",
  "layer_id"
)
SELECT
  qtrelation_id as relation_id,
  catalog_id,
  lower(qtrelation_name) as relation_name,
  qtrelationtype_id as relationtype_id,
  data_field_1,
  data_field_2,
  data_field_3,
  lower(table_name) as table_name,
  table_field_1,
  table_field_2,
  table_field_3,
  language_id,
  layer_id
FROM qtrelation;


drop table qtfield;
drop table qtrelation;


--SISTEMI DI RIFERIMENTO--
INSERT INTO project_srs(project_name,srid)
SELECT DISTINCT project_name,data_srid FROM  gisclient_21.layer 
INNER JOIN gisclient_21.layergroup USING(layergroup_id) 
INNER JOIN gisclient_21.theme USING (theme_id)
WHERE data_srid NOT IN (SELECT srid FROM project_srs)
ORDER BY 2;


--SISTEMIAMO IL DATO--
UPDATE layer SET layer_name = replace(layer_name,' ','_');
UPDATE layer SET sizeunits_id=1 where sizeunits_id = -1;
UPDATE layer set catalog_id=0 where catalog_id=-1;

--SIMBOLOGIA--


-- ESEGUIRE L'INSERT DEI PATTERN E DEI SIMBOLI MS6

ALTER TABLE style DROP CONSTRAINT pattern_id_fkey;

TRUNCATE e_symbolcategory CASCADE ;
TRUNCATE symbol CASCADE ;
TRUNCATE font CASCADE ;
--TRUNCATE e_pattern CASCADE;

-- POPOLA LA CATEGORIA DI SIMBOLI
INSERT INTO e_symbolcategory VALUES (1, 'MapServer', NULL);
INSERT INTO e_symbolcategory VALUES (2, 'Line', NULL);
INSERT INTO e_symbolcategory VALUES (3, 'Campiture', NULL);
INSERT INTO e_symbolcategory VALUES (4, 'Marker', NULL);
INSERT INTO e_symbolcategory VALUES (5, 'CatastoCML', NULL);
INSERT INTO e_symbolcategory VALUES (7, 'Numeri e lettere', NULL);
--- SW
INSERT INTO e_symbolcategory VALUES (6, 'TechNET', NULL);
INSERT INTO e_symbolcategory VALUES (8, 'COSAP', NULL);
INSERT INTO e_symbolcategory VALUES (9, 'SkiGIS', NULL);
INSERT INTO e_symbolcategory VALUES (10, 'SIGNS', NULL);
--INSERT INTO e_symbolcategory VALUES (11, 'VEHICLES', NULL);
INSERT INTO e_symbolcategory VALUES (12, 'R3-Ambiente', NULL);
-- CLIENTI
INSERT INTO e_symbolcategory VALUES (50, 'Sentieri CMSO', NULL);
INSERT INTO e_symbolcategory VALUES (60, 'P.E.I. CMVerbano', NULL);
INSERT INTO e_symbolcategory VALUES (70, 'PRG TSI Mori', NULL);
-- IMPORTED SYMBOLS
INSERT INTO e_symbolcategory VALUES (100, 'IMPORTED', NULL);
INSERT INTO e_symbolcategory
(
  "symbolcategory_id",
  "symbolcategory_name",
  "symbolcategory_order"
)
SELECT 
  symbolcategory_id+100 as symbolcategory_id,
  symbolcategory_name,
  symbolcategory_order
FROM gisclient_21.e_symbolcategory;

-- POPOLA I PATTERN E AGGIORNA GLI STILI
--INSERT INTO e_pattern VALUES(0,'NO PATTERN','#PATTERN END',0);
--INSERT INTO e_pattern VALUES(1,'1-3','PATTERN 1 3 END',1);
--INSERT INTO e_pattern VALUES(2,'2-3','PATTERN 2 3 END',2);
--INSERT INTO e_pattern VALUES(3,'3-3','PATTERN 3 3 END',3);
--INSERT INTO e_pattern VALUES(4,'5-5','PATTERN 5 5 END',4);
--INSERT INTO e_pattern VALUES(5,'10-10','PATTERN 10 10 END',5);
--INSERT INTO e_pattern VALUES(6,'10-3','PATTERN 10 3 END',6);
--INSERT INTO e_pattern VALUES(7,'3-10','PATTERN 3 10 END',7);
--INSERT INTO e_pattern VALUES(8,'5-3-1-3','PATTERN 5 3 1 3 END',8);
--INSERT INTO e_pattern VALUES(9,'5-3-1-3-1-3','PATTERN 5 3 1 3 1 3 END',9);
--INSERT INTO e_pattern VALUES(10,'5-3-5-3-1-3','PATTERN 5 3 5 3 1 3 END',10);
--INSERT INTO e_pattern VALUES(11,'1-2-1-6','PATTERN 1 2 1 6 END',11);


-- POPOLA I FONT
INSERT INTO font SELECT * FROM gisclient_21.font;

--INSERT INTO font VALUES ('dejavu-sans', 'dejavu-sans.ttf');
--INSERT INTO font VALUES ('dejavu-sans-bold', 'dejavu-sans-bold.ttf');
--INSERT INTO font VALUES ('dejavu-sans-bold-italic', 'dejavu-sans-bold-italic.ttf');
--INSERT INTO font VALUES ('dejavu-serif', 'dejavu-serif.ttf');
--INSERT INTO font VALUES ('dejavu-serif-bold', 'dejavu-serif-bold');
--INSERT INTO font VALUES ('dejavu-serif-bold-italic', 'dejavu-serif-bold-italic.ttf');
--INSERT INTO font VALUES ('dejavu-serif-italic', 'dejavu-serif-italic.ttf');
--INSERT INTO font VALUES ('dejavu-sans-italic', 'dejavu-sans-italic.ttf');
--INSERT INTO font VALUES ('tpa', 'tpa.ttf');
--INSERT INTO font VALUES ('tpe', 'tpe.ttf');
--INSERT INTO font VALUES ('tpg', 'tpg.ttf');
--INSERT INTO font VALUES ('tpo', 'tpo.ttf');
--INSERT INTO font VALUES ('tps', 'tps.ttf');
--INSERT INTO font VALUES ('tptc', 'tptc.ttf');
--INSERT INTO font VALUES ('tpts', 'tpts.ttf');
--INSERT INTO font VALUES ('tptlr', 'tptlr.ttf');
--INSERT INTO font VALUES ('cosap', 'cosap.ttf');

INSERT INTO symbol VALUES ('AIR', 3, 0, NULL, 'TYPE TRUETYPE
FONT "MSYMB"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#033;"');
INSERT INTO symbol VALUES ('VIGNETO', 3, 0, NULL, 'Type VECTOR
  Filled TRUE
  Points
		.8 .6
		.4 .6
		.6 0
		.4 0
		.2 .6
		.4 .8
		.6 .8
		.8 .6
  END
		');
INSERT INTO symbol VALUES ('TENT', 1, 0, NULL, 'TYPE VECTOR
FILLED TRUE
POINTS
0 1
.5 0
1 1
.75 1
.5 .5
.25 1
0 1
END');
INSERT INTO symbol VALUES ('STAR', 1, 0, NULL, 'TYPE VECTOR
FILLED TRUE
POINTS
0 .375
.35 .375
.5 0
.65 .375
1 .375
.75 .625
.875 1
.5 .75
.125 1
.25 .625
END');
INSERT INTO symbol VALUES ('TRIANGLE', 1, 0, NULL, 'TYPE VECTOR
FILLED TRUE
POINTS
0 1
.5 0
1 1
0 1
END');
INSERT INTO symbol VALUES ('SQUARE', 1, 0, NULL, 'TYPE VECTOR
FILLED TRUE
POINTS
0 1
0 0
1 0
1 1
0 1
END');
INSERT INTO symbol VALUES ('PLUS', 1, 0, NULL, 'TYPE VECTOR
POINTS
.5 0
.5 1
-99 -99
0 .5
1 .5
END');
INSERT INTO symbol VALUES ('CROSS', 1, 0, NULL, 'TYPE VECTOR
POINTS
0 0
1 1
-99 -99
0 1
1 0
END');
INSERT INTO symbol VALUES ('VIVAIO', 3, 0, NULL, 'TYPE Vector
  POINTS
		.3 1
		.7 1
		.9 .1
		.1 .1
		.3 1
		-99 -99
		.2 .2
		.2 .1
		-99 -99
		.5 .2
		.5 .1
		-99 -99
		.7 .2
		.7 .1
  END
	');
INSERT INTO symbol VALUES ('CIRCLE', 1, 0, NULL, 'TYPE ELLIPSE
FILLED TRUE
POINTS
1 1
END');
INSERT INTO symbol VALUES ('WATER', 3, 0, NULL, 'Type VECTOR
  Filled FALSE  
   Points
		0 .6
		.1 .4
		.2 .4
		.3 .6
		.4 .6
		.5 .4
		.6 .4
		.7 .6
		.8 .6
		.9 .4
		1 .4
		1.1 .6
  END');
INSERT INTO symbol VALUES ('CIRCLE_EMPTY', 3, 0, NULL, 'TYPE Vector
  POINTS
    0 .5
		.1 .7
		.3 .9
		.5 1
		.7 .9
		.9 .7
		1 .5
		.9 .3
		.7 .1
		.5 0
		.3 .1
		.1 .3
		0 .5
  END
	');
    INSERT INTO symbol VALUES ('CIRCLE_HALF', 3, 0, NULL, 'TYPE Vector
  POINTS
    0 .5
		.1 .7
		.3 .9
		.5 1
		.7 .9
		.9 .7
		1 .5
		0 .5
  END

	');
INSERT INTO symbol VALUES ('HATCH', 3, 0, NULL, 'TYPE HATCH');
INSERT INTO symbol VALUES ('10-3', 2, 0, NULL, ' Type ELLIPSE
	Points
	  1 1
	END
	STYLE
		10 3
	END');
INSERT INTO symbol VALUES ('1-3', 2, 0, NULL, 'Type ELLIPSE
	Points
	  1 1
	END
	STYLE
		1 3
	END');
INSERT INTO symbol VALUES ('2-3', 2, 0, NULL, 'Type ELLIPSE
	Points
	  1 1
	END
	STYLE
		2 3
	END');
INSERT INTO symbol VALUES ('3-10', 2, 0, NULL, '  Type ELLIPSE
	Points
	  1 1
	END
	STYLE
		3 10
	END');
INSERT INTO symbol VALUES ('3-3', 2, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    3 3
  END ');
  INSERT INTO symbol VALUES ('5-5', 2, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    5 5
  END ');
  INSERT INTO symbol VALUES ('10-10', 2, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    10 10
  END ');
INSERT INTO symbol VALUES ('5-3-1-3', 2, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    5 3 1 3
  END ');
INSERT INTO symbol VALUES ('5-3-1-3-1-3', 2, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    5 3 1 3 1 3
  END ');
INSERT INTO symbol VALUES ('5-3-5-3-1-3', 2, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    5 3 5 3 1 3
  END ');
INSERT INTO symbol VALUES ('BOSCO', 3, 0, NULL, 'TYPE Vector
  POINTS
    .5 1
    .5 0
		-99 -99
		.5 0
		.3 .1 
		-99 -99
		.5 .0
		.7 .1
		-99 -99
		.5 .3
		.2 .4
		-99 -99
		.5 .3
		.8 .4
		-99 -99
		.5 .6
		.1 .8
		-99 -99
		.5 .6
		.9 .8
  END
	');
INSERT INTO symbol VALUES ('CIMITERO', 3, 0, NULL, 'TYPE VECTOR
POINTS
.5 0
.5 1
-99 -99
.2 .3
.8 .3
END
');
INSERT INTO symbol VALUES ('FAUNA', 3, 0, NULL, 'TYPE TRUETYPE
FONT "MSYMB"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#035;"');
INSERT INTO symbol VALUES ('FLORA', 3, 0, NULL, 'TYPE TRUETYPE
FONT "MSYMB"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#036;"');
INSERT INTO symbol VALUES ('FRUTTETO', 3, 0, NULL, 'Type VECTOR
  Filled TRUE
  Points
		.2 1
		.2 .8
		.4 .8 
		.4 .4
		0 0
		.2 0
		.4 .2
		.4 0
		.6 0
		.6 .2
		.8 0
		1 0
		.6 .4
		.6 .8
		.8 .8
		.8 1
		.2 1
  END
		');
INSERT INTO symbol VALUES ('INCOLTO', 3, 0, NULL, 'Type VECTOR
  Filled TRUE
  Points
	0 1
	.2 .6
	.35 .85
	.5 .6
	.65 .85
	.8 .6 
	1 1
	.9 1
	.8 .8
	.7 1
	.6 1
	.5 .8
	.4 1
	.3 1
	.2 .8
	.1 1
	0 1
  END
		');
INSERT INTO symbol VALUES ('NOISE', 3, 0, NULL, 'TYPE TRUETYPE
FONT "MSYMB"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#037;"');
INSERT INTO symbol VALUES ('NOISE_INSP', 3, 0, NULL, 'TYPE TRUETYPE
FONT "MSYMB"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#038;"');
INSERT INTO symbol VALUES ('PASCOLO', 3, 0, NULL, '  Type VECTOR
  Filled TRUE
  Points
    0 .4
		.2 1
		.4 1
		.2 .4
		0 .4
		-99 -99
		.4 0
		.6 0 
		.6 1
		.4 1
		.4 0 
	   -99 -99
		 .8 .4
		 1 .4
		 .8 1
		 .6 1
		 .8 .4	
  END
		');
INSERT INTO symbol VALUES ('RANDOM', 3, 0, NULL, '  Type VECTOR
  Filled TRUE
  Points
    .1 .1
		.3 .3
  -99 -99
		.5 .2
		.7 0
  -99 -99
		.9 .2
  -99 -99
		.7 .3
  -99 -99
		.1 .5		
  -99 -99
		.6 .5
		.4 .7
  -99 -99
		.3 .8
  -99 -99
		.8 .7
  -99 -99
		.1 .9
  -99 -99
		.6 .8
		.6 1
  END
		');
INSERT INTO symbol VALUES ('RISAIA', 3, 0, NULL, 'Type VECTOR
  Filled TRUE
  Points
		0 1
		0 .4
		.2 .4
		.2 1
		0 1
		-99 -99
		.4 1
		.4 0
		.6 0
		.6 1
		.4 1
		-99 -99 
		.8 1
		.8 .4
		1 .4
		1 1
		.8 1 
  END
		');
INSERT INTO symbol VALUES ('RUPESTRE', 3, 0, NULL, '  Type VECTOR
  Filled TRUE
  Points
    .2 .8
    .35 .6
    .65 .6
    .8 .8
   -99 -99
    0 .6
    .15 .45
    .35 .45
    .5 .6
    .65 .45
    .85 .45
    1 .6		
  END
		');
INSERT INTO symbol VALUES ('ARROW', 2, 0, NULL, 'TYPE Vector
	FILLED True
	POINTS
	  0 0
		.5 .5
		0 1
		0 0
	END');
INSERT INTO symbol VALUES ('ARROWBACK', 2, 0, NULL, '	TYPE Vector
	FILLED True
	POINTS
	  1 1
		.5 .5
		1 0
		1 1
	END');
INSERT INTO symbol VALUES ('CIRCLE_FILL', 3, 0, NULL, 'TYPE ELLIPSE
FILLED TRUE
POINTS
1 1
END
	');
INSERT INTO symbol VALUES ('WARNING', 1, 4, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#033;"');
INSERT INTO symbol VALUES ('SQUARE_EMPTY', 3, 0, NULL, 'Type VECTOR
  Points
	.1 .1
	.1 .9
	.9 .9
	.9 .1
	.1 .1
  END');
INSERT INTO symbol VALUES ('1-2-1-6', 2, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    1 2 1 6
  END ');
INSERT INTO symbol VALUES ('TRIANGLE_EMPTY', 3, 0, NULL, 'Type VECTOR
  Points
	.1 .1
	.9 .1
	.9 .1
	.5 .9
	.1 .1
  END');
INSERT INTO symbol VALUES ('PLUS_FILL', 3, 0, NULL, 'TYPE VECTOR
POINTS
    .1 .3
    .5 .3
    -99 -99
    .3 .1
    .3 .5
    -99 -99
    .5 .7
    .9 .7
    -99 -99
    .7 .5
    .7 .9
END');
INSERT INTO symbol VALUES ('SNOW', 3, 0, NULL,  'Type VECTOR
  Points
	0 .5
	1 .5
	-99 -99
	.2 0
	.8 1
	-99 -99
	.8 0
	.2 1
  END
		');
INSERT INTO symbol VALUES ('HEXAGON_EMPTY', 3, 0, NULL, 'Type VECTOR
  Points
	.3 .1
	.8 .1
	1 .5
	.8 .9
	.3 .9
	.1 .5
	.3 .1
  END');
INSERT INTO symbol VALUES ('HEXAGON_BEE', 3, 0, NULL, 'Type VECTOR
  Points
	.1 0
	.2 .2
	.1 .4
	0 .4
	-99 -99
	.2 .2
	.4 .2
	-99 -99
	.5 0
	.4 .2
	.5 .4
	.6 .4
  END
');
INSERT INTO symbol VALUES ('ICE', 3, 0, NULL, 'Type VECTOR
  Points
	0 .5
    .5 1
	-99 -99
	0 0
    1 .5
	-99 -99
	.5 0
    0 1
    -99 -99
    .5 0
    .5 1
    -99 -99
    0 0
    0 .5
  END
');
INSERT INTO symbol VALUES ('HALF_SQUARE', 3, 0, NULL, 'Type VECTOR
  Points
	.2 1.8
	1.8 1.8
	1.8 .2
  END');
INSERT INTO symbol VALUES ('DASH_DASH', 3, 0, NULL, 'Type VECTOR
  Points
	0 .9 
	.3 .9
	-99 -99
	.7 .9
	1 .9
	-99 -99
	.2 .4 
	.8 .4
  END
');
INSERT INTO symbol VALUES ('DASH_DASH_VERTICAL', 3, 0, NULL, 'Type VECTOR
  Points
	.9 0 
	.9 .3 
	-99 -99
	.9 .7 
	.9 1 
	-99 -99
	.4 .2 
	.4 .8 
  END
');
INSERT INTO symbol VALUES ('DASH_LINE', 3, 0, NULL, 'Type VECTOR
  Points
	0 .9 
	1 .9
	-99 -99
	.2 .4 
	.8 .4
  END
');
INSERT INTO symbol VALUES ('STREAMERS', 3, 0, NULL, 'Type VECTOR
  Points
	.1 .1
    .4 .1
	-99 -99
	.9 .1
    .6 .4
	-99 -99
	.1 .6 
    .1 .9 
    -99 -99
	.4 .6
    .7 .9
  END
');
INSERT INTO symbol VALUES ('POINT_LINE_VERTICAL', 3, 0, NULL, 'Type VECTOR
  Points
	.9 0  
	.9 1 
	-99 -99
	 .4 .4
	 .4 .6
  END
');
INSERT INTO symbol VALUES ('DOUBLE_LINE_VERTICAL', 3, 0, NULL, 'Type VECTOR
  Points
    .0 0  
	.0 1 
	-99 -99
	.3 0  
	.3 1 
	-99 -99
	1 0  
	1 1 
  END
');
INSERT INTO symbol VALUES ('ARROW2', 2, 0, NULL, 'TYPE Vector
	POINTS
	  0 0
		.5 .5
		0 1
	END');
INSERT INTO symbol VALUES ('ARROWBACK2', 2, 0, NULL, '	TYPE Vector
	POINTS
	  1 1
		.5 .5
		1 0
	END');
 INSERT INTO symbol VALUES ('ARROW3', 2, 0, NULL, 'TYPE VECTOR
 POINTS
0 .5
1 .5
-99 -99
.8 .7
1 .5
.8 .3
END');
INSERT INTO symbol VALUES ('HOURGLASS', 2, 0, NULL, 'TYPE Vector
FILLED TRUE
	POINTS
	 0 0
	 0 1
	 1 0
	 1 1
	 0 0
	END
	ANTIALIAS true
	');
INSERT INTO symbol VALUES ('SQUARE_FILL', 3, 0, NULL, 'Type VECTOR
FILLED TRUE
  Points
	.1 .1
	.1 .9
	.9 .9
	.9 .1
	.1 .1
  END
		');
INSERT INTO symbol VALUES ('RUBINETTO', 3, 0, NULL, 'TYPE TRUETYPE
FONT "symbols"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#074;"');
INSERT INTO symbol VALUES ('RIPARIE-CANNETO', 3, 0, NULL, 'TYPE VECTOR
POINTS
.3 0
.3 1
.7 1
END
 ');
 INSERT INTO symbol VALUES ('VERTEX', 3, 0, NULL, 'TYPE VECTOR
FILLED TRUE
POINTS
	1 8
	3 8
	3 9
	1 9
	1 8
-99 -99
	7 8
	9 8
	9 9
	7 9
	7 8
-99 -99
	4 1
	6 1
	6 2
	4 2
	4 1
END');
 INSERT INTO symbol VALUES ('T', 3, 0, NULL, 'TYPE VECTOR
POINTS
.5 .5
.5 1	
-99 -99
0 .5
1 .5
END');
 INSERT INTO symbol VALUES ('DOUBLE_T', 3, 0, NULL, 'TYPE VECTOR
POINTS
.3 .5
.3 1	
-99 -99
.7 .5
.7 1	
-99 -99
0 .5
1 .5
END');
 INSERT INTO symbol VALUES ('D', 3, 0, NULL, 'TYPE VECTOR
 FILLED TRUE
POINTS
.5 0
.5 1
.3 .9
.1 .7
0 .5
.1 .3
.3 .1
.5 0
END');
 INSERT INTO symbol VALUES ('MONUMENTO', 3, 0, NULL, 'TYPE VECTOR
POINTS
.5 1
.2 .3
.2 .2
.4 0
.6 0
.6 .2
.6 .3
.5 1
END');
 INSERT INTO symbol VALUES ('VERTICAL', 4, 0, NULL, 'TYPE VECTOR
POINTS
.5 0
.5 1
END');
 INSERT INTO symbol VALUES ('HORIZONTAL', 4, 0, NULL, 'TYPE VECTOR
POINTS
0 .5
1 .5
END');
 
INSERT INTO symbol VALUES ('SQUARE_HALF', 1, 0, NULL, 'TYPE VECTOR
FILLED TRUE
POINTS
0 0
0 1
1 0
0 0
END');
INSERT INTO symbol VALUES ('IDRANTE', 1, 0, NULL, 'TYPE VECTOR
FILLED TRUE
POINTS
0 1
1 1
-99 -99
.2 1
.2 .4
.8 .4
.8 1
.2 1
-99 -99
.2 .8
0 .8
0 .6
.2 .6
-99 -99
.8 .8
1 .8
1 .6
.8 .6
-99 -99
0 .4
1 .4
.9 .2
.7 0
.3 0
.1 .2
0 .4
END');
INSERT INTO symbol VALUES ('PHOTO', 4, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/photo.png"');


-- Catasto
INSERT INTO symbol VALUES ('01 - P. ORIENTAMENTO', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#065;"');
INSERT INTO symbol VALUES ('02 - TERMINE PARTICELLARE', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#066;"');
INSERT INTO symbol VALUES ('03 - PARAMETRO', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#067;"');
INSERT INTO symbol VALUES ('04 - OSSO DI MORTO', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#068;"');
INSERT INTO symbol VALUES ('05 - FLUSSO GRANDE', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#069;"');
INSERT INTO symbol VALUES ('06 - FLUSSO MEDIO', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#070;"');
INSERT INTO symbol VALUES ('07 - FLUSSO PICCOLO', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#071;"');
INSERT INTO symbol VALUES ('08 - P. FID. TRIGONOMETRICO', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#072;"');
INSERT INTO symbol VALUES ('09 - GRAFFA GRANDE', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#073;"');
INSERT INTO symbol VALUES ('10 - ANCORA', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#074;"');
INSERT INTO symbol VALUES ('11 - TERMINE PROVINCIALE', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#075;"');
INSERT INTO symbol VALUES ('13 - CROCE SU ROCCIA', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#077;"');
INSERT INTO symbol VALUES ('14 - GRAFFA PICCOLA', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#078;"');
INSERT INTO symbol VALUES ('15 - BAFF. PICCOLA', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#079;"');
INSERT INTO symbol VALUES ('16 - BAFF. GRANDE', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#079;"');
INSERT INTO symbol VALUES ('20 - P. FID. SEMPLICE', 5, 0, NULL, 'TYPE TRUETYPE
FONT "r3cadastre"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#084;"');



--- Numeri
INSERT INTO symbol VALUES ('#0', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#048;"');
INSERT INTO symbol VALUES ('#1', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#049;"');
INSERT INTO symbol VALUES ('#2', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#050;"');
INSERT INTO symbol VALUES ('#3', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#051;"');
INSERT INTO symbol VALUES ('#4', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#052;"');
INSERT INTO symbol VALUES ('#5', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#053;"');
INSERT INTO symbol VALUES ('#6', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#054;"');
INSERT INTO symbol VALUES ('#7', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#055;"');
INSERT INTO symbol VALUES ('#8', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#056;"');
INSERT INTO symbol VALUES ('#9', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#057;"');
INSERT INTO symbol VALUES ('#A', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#065;"');
INSERT INTO symbol VALUES ('#B', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#066;"');
INSERT INTO symbol VALUES ('#C', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#067;"');
INSERT INTO symbol VALUES ('#D', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#068;"');
INSERT INTO symbol VALUES ('#E', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#069;"');
INSERT INTO symbol VALUES ('#F', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#070;"');
INSERT INTO symbol VALUES ('#G', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#071;"');
INSERT INTO symbol VALUES ('#H', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#072;"');
INSERT INTO symbol VALUES ('#I', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#073;"');
INSERT INTO symbol VALUES ('#J', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#074;"');
INSERT INTO symbol VALUES ('#K', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#075;"');
INSERT INTO symbol VALUES ('#L', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#076;"');
INSERT INTO symbol VALUES ('#M', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#077;"');
INSERT INTO symbol VALUES ('#N', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#078;"');
INSERT INTO symbol VALUES ('#O', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#079;"');
INSERT INTO symbol VALUES ('#P', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#080;"');
INSERT INTO symbol VALUES ('#Q', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#081;"');
INSERT INTO symbol VALUES ('#R', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#082;"');
INSERT INTO symbol VALUES ('#S', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#083;"');
INSERT INTO symbol VALUES ('#T', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#084;"');
INSERT INTO symbol VALUES ('#U', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#085;"');
INSERT INTO symbol VALUES ('#V', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#086;"');
INSERT INTO symbol VALUES ('#W', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#087;"');
INSERT INTO symbol VALUES ('#X', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#088;"');
INSERT INTO symbol VALUES ('#Y', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#089;"');
INSERT INTO symbol VALUES ('#Z', 7, 0, NULL, 'TYPE TRUETYPE
FONT "dejavu-sans-bold"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#090;"');

--- R3 MAP SYMBOLS
INSERT INTO symbol VALUES ('PIN1', 4, 0, NULL, 'TYPE TRUETYPE
FONT "r3-map-symbols"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#065;"');
INSERT INTO symbol VALUES ('PIN2', 4, 0, NULL, 'TYPE TRUETYPE
FONT "r3-map-symbols"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#066;"');
INSERT INTO symbol VALUES ('PIN3', 4, 0, NULL, 'TYPE TRUETYPE
FONT "r3-map-symbols"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#067;"');
INSERT INTO symbol VALUES ('PIN4', 4, 0, NULL, 'TYPE TRUETYPE
FONT "r3-map-symbols"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#068;"');
INSERT INTO symbol VALUES ('CAMERA', 4, 0, NULL, 'TYPE TRUETYPE
FONT "r3-map-symbols"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#069;"');
INSERT INTO symbol VALUES ('ORIENTED-PIN', 4, 0, NULL, 'TYPE TRUETYPE
FONT "r3-map-symbols"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#070;"');
INSERT INTO symbol VALUES ('LIKE', 4, 0, NULL, 'TYPE TRUETYPE
FONT "r3-map-symbols"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#071;"');


-- NEW TECHNET
INSERT INTO symbol VALUES ('CONN.T', 6, 0, NULL, 'TYPE TRUETYPE
FONT "r3-technet"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#065;"', NULL, NULL, NULL, 0, NULL, NULL);
INSERT INTO symbol VALUES ('SARACINESCA', 6, 0, NULL, 'TYPE TRUETYPE
FONT "r3-technet"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#066;"', NULL, NULL, NULL, 0, NULL, NULL);
INSERT INTO symbol VALUES ('SALDATURA', 6, 0, NULL, 'TYPE TRUETYPE
FONT "r3-technet"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#067;"', NULL, NULL, NULL, 0, NULL, NULL);
INSERT INTO symbol VALUES ('RIDUTTORE', 6, 0, NULL, 'TYPE TRUETYPE
FONT "r3-technet"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#068;"', NULL, NULL, NULL, 0, NULL, NULL);
INSERT INTO symbol VALUES ('ALLACCIAMENTO', 6, 0, NULL, 'TYPE TRUETYPE
FONT "r3-technet"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#069;"', NULL, NULL, NULL, 0, NULL, NULL);
INSERT INTO symbol VALUES ('ARCO', 6, 0, NULL, 'TYPE TRUETYPE
FONT "r3-technet"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#070;"', NULL, NULL, NULL, 0, NULL, NULL);
INSERT INTO symbol VALUES ('SPOSTAMENTO', 6, 0, NULL, 'TYPE TRUETYPE
FONT "r3-technet"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#071;"', NULL, NULL, NULL, 0, NULL, NULL);
INSERT INTO symbol VALUES ('VALVOLA', 6, 0, NULL, 'TYPE TRUETYPE
FONT "r3-technet"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#072;"', NULL, NULL, NULL, 0, NULL, NULL);
INSERT INTO symbol VALUES ('TAPPO', 6, 0, NULL, 'TYPE TRUETYPE
FONT "r3-technet"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#073;"', NULL, NULL, NULL, 0, NULL, NULL);
INSERT INTO symbol VALUES ('POZZETTO ISP', 6, 0, NULL, 'TYPE TRUETYPE
FONT "r3-technet"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#074;"', NULL, NULL, NULL, 0, NULL, NULL);
INSERT INTO symbol VALUES ('IDRANT', 6, 0, NULL, 'TYPE TRUETYPE
FONT "r3-technet"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#075;"', NULL, NULL, NULL, 0, NULL, NULL);
INSERT INTO symbol VALUES ('GIUNTO', 6, 0, NULL, 'TYPE TRUETYPE
FONT "r3-technet"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#076;"', NULL, NULL, NULL, 0, NULL, NULL);
INSERT INTO symbol VALUES ('CONTATORE', 6, 0, NULL, 'TYPE TRUETYPE
FONT "r3-technet"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#077;"', NULL, NULL, NULL, 0, NULL, NULL);
INSERT INTO symbol VALUES ('GENERICO', 6, 0, NULL, 'TYPE TRUETYPE
FONT "r3-technet"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#078;"', NULL, NULL, NULL, 0, NULL, NULL);
INSERT INTO symbol VALUES ('VUOTO', 6, 0, NULL, 'TYPE TRUETYPE
FONT "r3-technet"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#079;"', NULL, NULL, NULL, 0, NULL, NULL);
INSERT INTO symbol VALUES ('CENTRALINA', 6, 0, NULL, 'TYPE TRUETYPE
FONT "r3-technet"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#080;"', NULL, NULL, NULL, 0, NULL, NULL);


----- R3-Ambiebte
INSERT INTO symbol VALUES ('ARIA', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#065;"');
INSERT INTO symbol VALUES ('IDRICO', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#066;"');
INSERT INTO symbol VALUES ('IDRICO SUP.', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#067;"');
INSERT INTO symbol VALUES ('IDRICO SOTT.', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#068;"');
INSERT INTO symbol VALUES ('SUOLO E SOTT.', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#069;"');
INSERT INTO symbol VALUES ('ECOSIST. FLORA', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#070;"');
INSERT INTO symbol VALUES ('ECOSIST. FAUNA', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#071;"');
INSERT INTO symbol VALUES ('ECOSIST. UCCELLI', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#072;"');
INSERT INTO symbol VALUES ('RUMORE E VIBRAZIONI', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#073;"');
INSERT INTO symbol VALUES ('PAESAGGIO', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#074;"');
INSERT INTO symbol VALUES ('TERRE E ROCCE', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#075;"');
INSERT INTO symbol VALUES ('RADIAZIONI', 12, 0, NULL, 'TYPE TRUETYPE
FONT "ambiente"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#076;"');

--INSERISCO GLI ALTRI SIMBOLI
WITH new_symbols (symbol_name,symbolcategory_id,icontype,symbol_image,symbol_def) as (
  SELECT symbol_name,symbolcategory_id+100,icontype,symbol_image,symbol_def FROM gisclient_21.symbol WHERE NOT (symbol_def LIKE '%STYLE%' OR  symbol_def LIKE '%CARTOLINE%')
),
upsert as
( 
    update symbol m 
        set symbol_name = nv.symbol_name,
            symbolcategory_id = nv.symbolcategory_id,
            icontype = nv.icontype,
            symbol_image = nv.symbol_image,
            symbol_def = nv.symbol_def
    FROM new_symbols nv
    WHERE m.symbol_name = nv.symbol_name
    RETURNING m.*
)
INSERT INTO symbol (symbol_name,symbolcategory_id,icontype,symbol_image,symbol_def)
SELECT symbol_name,symbolcategory_id,icontype,symbol_image,symbol_def
FROM new_symbols
WHERE NOT EXISTS (SELECT 1 
                  FROM upsert up 
                  WHERE up.symbol_name = new_symbols.symbol_name);
--INSERT INTO symbol(symbol_name,symbolcategory_id,icontype,symbol_image,symbol_def) SELECT symbol_name,symbolcategory_id+100,icontype,symbol_image,symbol_def 
--FROM gisclient_21.symbol WHERE NOT (symbol_def LIKE '%STYLE%' OR  symbol_def LIKE '%CARTOLINE%');
--INSERISCO I NUOVI SIMBOLI NELLA TABELLA
--SPOSTO I SIMBOLI TTF DALLA CLASSI ALLA TABELLA SIMBOLI ED ELIMINO LA TABELLA SYMBOL_TTF 
insert into symbol (symbol_name,symbolcategory_id,icontype,symbol_type,font_name,ascii_code,symbol_def)
select  font_name||'_'||symbol_ttf_name,100,0,'TRUETYPE',font_name,ascii_code,'ANTIALIAS TRUE' from gisclient_21.symbol_ttf order by 1;
--ELIMINO LE KEYWORDS NON COMPATIBILI ( CONTROLLARE IL RISULTATO)
update symbol  set symbol_def=regexp_replace(symbol_def, '\nGAP(.+)', '')  where symbol_def like '%GAP%';
--TOLGO DAI SIMBOLI QUELLI CHE SERVIVANO SOLO PER IL PATTERN
delete from symbol where symbol_def like '%STYLE%';
delete from symbol where symbol_def like '%CARTOLINE%';

--AGGIUNGO GLI STILI ALLE CLASSI---
insert into style(style_id,class_id,style_name,symbol_name,color,angle,size,minsize,maxsize)
select class_id+(select max(style_id)from style),class_id,symbol_ttf_name,label_font||'_'||symbol_ttf_name,label_color,label_angle,label_size,label_minsize,label_maxsize from class where coalesce(symbol_ttf_name,'')<>'' and coalesce(label_font,'')<>'';


-- RIPORTA GLI STILI CON PATTERN NEL MODO CORRETTO
update style set pattern_id = e_pattern.pattern_id 
from e_pattern where symbol_name=pattern_name;
update style s set symbol_name=NULL 
FROM e_pattern e where s.pattern_id is not null and symbol_name=pattern_name;

ALTER TABLE style
  ADD CONSTRAINT pattern_id_fkey FOREIGN KEY (pattern_id)
      REFERENCES e_pattern (pattern_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE NO ACTION;

-- SISTEMA GLI STILI IN MODO DA RIALLINEARSI CON LA VESTIZIONE CORRETTA

update style set pattern_id = 0 where pattern_id is null;

update style 
  set symbol_name = NULL 
  where 
    SYMBOL_name = 'CIRCLE' and
    pattern_id = 0  and
    class_id in
      (Select class_id from class where layer_id IN
        (SELECT layer_id from layer where layertype_id = 2));

update style
set style_def = 'GAP '||(size::INT*2)::TEXT
where --style_def is null
--and 
symbol_name in (
select symbol_name from symbol where symbolcategory_id = 3);

update style
set style_def = 'GAP -'||(size::INT*4)::TEXT
where --style_def is null
--and 
symbol_name in (
select symbol_name from symbol where 
symbol_name like 'ARRO%' or symbol_name like '%_SPACE');

--pulisce le label dal BACKGROUNDCOLOR
update class
set label_def = label_def||' STYLE 
GEOMTRANSFORM ''labelpoly'' 
COLOR '||label_bgcolor||' 
END'
where label_bgcolor is not null;
UPDATE CLASS set label_bgcolor = NULL;

-- sistema i testi fissi

update class set
class_text = ''''||class_text||'''' where class_text is not null;

delete from symbol where symbolcategory_id = 2;
delete from e_symbolcategory where symbolcategory_id = 2;

update style set symbol_name = NULL where symbol_name='CIRCLE'
and pattern_id = 0 and class_id in
(select class_id from class where layer_id in
(select layer_id from layer where layertype_id = 2));


--AGGIORNAMENTO DEL VALORE DI EXPRESSION IN CLASS (AGGIUNTE LE PARENTESI)
update class set expression='('||expression||')' where (expression like '(''[%' or expression like '''[%'  or expression like '[%' ) and not expression like '(%)';
UPDATE class SET expression = REGEXP_REPLACE(expression, '\\''+', '''', 'g');
UPDATE class SET keyimage = 'NO' from layer where layertype_id=5 and class.layer_id=layer.layer_id;
UPDATE symbol SET symbol_def=replace(symbol_def,'../','../../') WHERE symbol_def LIKE '%PIXMAP%' AND NOT symbol_def LIKE '%../../%';

-- Correggi sintassi delle labels
UPDATE class 
SET class_text=regexp_replace(regexp_replace(regexp_replace(class_text, E'\\] \\[', E'\]\+\['), E'\\[', E'''\[', 'g'), E'\\]', E'\]''', 'g') 
WHERE class_text is not null;

-- Correzioni grafiche sugli style per resa simile a geoweb 2.1
-- Outlinecolor importato da class a style
update style set outlinecolor=c.label_outlinecolor, width=0.25, maxwidth=0.25, minwidth=0.25 
from (select class_id as _class_id, label_outlinecolor from class where label_outlinecolor is not null) as c
where c._class_id=class_id;

-- Crea sfondo per classi con label_bgcolor e simboli, usando il quadrato pieno
create temp sequence temp_seq;

insert into style (style_id,class_id,style_name,symbol_name,color,style_order)
SELECT (SELECT max(style_id) FROM style) + nextval('temp_seq')  as style_id, class_id, class_name || '_BG' as style_name, 'QUADRATO_PIENO', label_bgcolor, -1 FROM class where label_bgcolor is not null;

drop sequence temp_seq;

UPDATE layer SET data_type = 'point' WHERE layertype_id=1;
UPDATE layer SET data_type = 'linestring' WHERE layertype_id=2;
UPDATE layer SET data_type = 'multipolygon' WHERE layertype_id=3;
UPDATE layer SET data_type = 'point' WHERE layertype_id=4;
UPDATE layer SET data_type = 'point' WHERE layertype_id=5;

-- Mapserver 7 compatibility
UPDATE layer SET layertype_id=1 WHERE layertype_id=5;
UPDATE layer SET opacity='75' WHERE opacity='ALPHA';

INSERT INTO version (version_name,version_key, version_date) values ('7', 'mapserver', CURRENT_DATE);


UPDATE mapset set template='jquery/geoweb.html';

COMMIT;
