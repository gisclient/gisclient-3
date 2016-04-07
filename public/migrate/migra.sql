SET search_path = gisclient_34, pg_catalog;

--SIMBOLOGIA--





--TUTTI I QUERYTEMPLATES


INSERT INTO gisclient_3.qt(
"qt_id",
"theme_id",
"layer_id",
"qt_name",
"max_rows",
"papersize_id",
"edit_url",
"groupobject",
"selection_color",
"qt_order",
"zoom_buffer",
"qt_filter",
"qtresultype_id")
SELECT
"qt_id",
"theme_id",
"layer_id",
"qt_name",
"max_rows",
"papersize_id",
"edit_url",
"groupobject",
"selection_color",
"qt_order",
"zoom_buffer",
"qt_filter",
"qtresultype_id"
FROM gisclient_25.qt;
INSERT INTO gisclient_3.qt_relation(
"qtrelation_id",
"qt_id",
"catalog_id",
"qtrelation_name",
"qtrelationtype_id",
"data_field_1",
"data_field_2",
"data_field_3",
"table_name",
"table_field_1",
"table_field_2",
"table_field_3",
"language_id"




















--SISTEMI DI RIFERIMENTO--
INSERT INTO project_srs(project_name,srid)
SELECT DISTINCT project_name,data_srid FROM  gisclient_25.layer 
INNER JOIN gisclient_25.layergroup USING(layergroup_id) 
INNER JOIN gisclient_25.theme USING (theme_id) ORDER BY 2;


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

--SISTEMI DI RIFERIMENTO + DIFFUSI
INSERT INTO project_srs SELECT project_name, 900913, NULL, NULL, 1 FROM project;
INSERT INTO project_srs SELECT project_name, 3857, NULL, NULL, 1 FROM project;
INSERT INTO project_srs SELECT project_name, 25832, NULL, NULL, 1 FROM project;
INSERT INTO project_srs SELECT project_name, 4326, NULL, NULL, 2 FROM project;
INSERT INTO project_srs SELECT project_name, 3003, '-104.1,-49.1,-9.9,0.971,-2.917,0.714,-11.68', NULL, 3 FROM project;
INSERT INTO project_srs SELECT project_name, 3004, '-104.1,-49.1,-9.9,0.971,-2.917,0.714,-11.68', NULL, 3 FROM project;
INSERT INTO project_srs SELECT project_name, 23032, '-87,-98,-121', NULL, 5 FROM project;
INSERT INTO project_srs SELECT project_name, 32632, NULL, NULL, 4 FROM project;



UPDATE mapset set template='jquery/mobile.html';





--SISTEMI DI RIFERIMENTO + DIFFUSI
INSERT INTO project_srs SELECT project_name, 900913, NULL, NULL, 1 FROM project;
INSERT INTO project_srs SELECT project_name, 3857, NULL, NULL, 1 FROM project;
INSERT INTO project_srs SELECT project_name, 25832, NULL, NULL, 1 FROM project;
INSERT INTO project_srs SELECT project_name, 4326, NULL, NULL, 2 FROM project;
INSERT INTO project_srs SELECT project_name, 3003, '-104.1,-49.1,-9.9,0.971,-2.917,0.714,-11.68', NULL, 3 FROM project;
INSERT INTO project_srs SELECT project_name, 3004, '-104.1,-49.1,-9.9,0.971,-2.917,0.714,-11.68', NULL, 3 FROM project;
INSERT INTO project_srs SELECT project_name, 23032, '-87,-98,-121', NULL, 5 FROM project;
INSERT INTO project_srs SELECT project_name, 32632, NULL, NULL, 4 FROM project;

--nuovo template!!
UPDATE mapset set template='jquery/mobile.html';


















