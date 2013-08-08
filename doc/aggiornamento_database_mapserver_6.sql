-- MAPSERVER 6 --
SET search_path = gisclient_xx, pg_catalog;

-- *********** SIMBOLOGIA LINEARE: SOSTITUZIONE DI STYLE CON PATTERN *********************
CREATE TABLE e_pattern
(
  pattern_id serial NOT NULL,
  pattern_name character varying NOT NULL,
  pattern_def character varying NOT NULL,
  pattern_order smallint,
  CONSTRAINT e_pattern_pkey PRIMARY KEY (pattern_id )
);
ALTER TABLE style ADD COLUMN pattern_id integer;

ALTER TABLE style  ADD CONSTRAINT pattern_id_fkey FOREIGN KEY (pattern_id)
      REFERENCES e_pattern (pattern_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE NO ACTION;
	  
CREATE INDEX fki_pattern_id_fkey ON style USING btree (pattern_id );

CREATE OR REPLACE VIEW seldb_pattern AS 
         SELECT (-1) AS id, 'Seleziona ====>' AS opzione
UNION ALL 
         SELECT pattern_id AS id, pattern_name AS opzione
           FROM e_pattern;

--UPGRADE DELLA TABELLA DEI SIMBOLI		   
ALTER TABLE symbol ADD COLUMN symbol_type character varying;
ALTER TABLE symbol ADD COLUMN font_name character varying;
ALTER TABLE symbol ADD COLUMN ascii_code integer;
ALTER TABLE symbol ADD COLUMN filled numeric(1,0) DEFAULT 0;
ALTER TABLE symbol ADD COLUMN points character varying;
ALTER TABLE symbol ADD COLUMN image character varying;
		   
--INSERISCO I PATTERN EREDITATI DAGLI STYLE CHE VENGONO APPLICATI ALLE LINEE NELLA VECCHIA VERSIONE
insert into e_pattern(pattern_name,pattern_def)
select symbol_name,'PATTERN' ||replace(substring(symbol_def from 'STYLE(.+)END'),'\n',' ') || 'END' from symbol where symbol_def like '%STYLE%';

--AGGIORNO IL pattern_id DELLA TABELLA style CON I VALORI DELLE CHIAVI
update style set pattern_id=e_pattern.pattern_id,symbol_name=null from e_pattern where e_pattern.pattern_name=style.symbol_name;

--TOLGO DAI SIMBOLI QUELLI CHE SERVIVANO SOLO PER IL PATTERN
delete from symbol where symbol_def like '%STYLE%';
--ELIMINO LE KEYWORDS NON COMPATIBILI (	CONTROLLARE IL RISULTATO)
update symbol  set symbol_def=regexp_replace(symbol_def, '\nGAP(.+)', '')  where symbol_def like '%GAP%';
delete from symbol where symbol_def like '%CARTOLINE%';

		   
-- *********** SIMBOLOGIA PUNTUALE: CREAZIONE DI SIMBOLI TRUETYPE IN SOSTITUZIONE DEL CARATTERE IN CLASS_TEXT *********************
--PULIZIA
UPDATE class set symbol_ttf_name=null where symbol_ttf_name='';
UPDATE class set label_font=null where label_font='';
UPDATE class set label_position=null where label_position='';

--TOLGO FONT E SIMBOLI INUTILI (soprattutto esri e robe amga)
DELETE from symbol_ttf where font_name like 'esri%' and font_name||'_'||symbol_ttf_name not in (SELECT label_font||'_'||symbol_ttf_name from class where label_font like 'esri%');
DELETE from symbol_ttf where font_name = 'galatone_si';
DELETE from symbol_ttf where font_name = 'padania_acque';
DELETE from symbol_ttf where font_name = 'atena';
DELETE from symbol_ttf where font_name = 'catasto2';


--INSERISCO I NUOVI SIMBOLI NELLA TABELLA
insert into symbol (symbol_name,symbolcategory_id,icontype,symbol_type,font_name,ascii_code,symbol_def)
select  font_name||'_'||symbol_ttf_name,1,0,'TRUETYPE',font_name,ascii_code,'ANTIALIAS TRUE' from symbol_ttf where font_name like 'esri%' order by 1;

--AGGIUNGO GLI STILI ALLE CLASSI---
insert into style(style_id,class_id,style_name,symbol_name,color,angle,size,minsize,maxsize)
select class_id+10000,class_id,symbol_ttf_name,label_font||'_'||symbol_ttf_name,label_color,label_angle,label_size,label_minsize,label_maxsize from class where label_font like 'esri%' and symbol_ttf_name is not null and label_font is not null;

--INSERISCO I NUOVI SIMBOLI NELLA TABELLA
insert into symbol (symbol_name,symbolcategory_id,icontype,symbol_type,font_name,ascii_code,symbol_def)
select symbol_ttf_name,1,0,'TRUETYPE',font_name,ascii_code,'ANTIALIAS TRUE' from symbol_ttf where not font_name like 'esri%' order by 1;

--AGGIUNGO GLI STILI ALLE CLASSI---
insert into style(style_id,class_id,style_name,symbol_name,color,angle,size,minsize,maxsize)
select class_id+10000,class_id,symbol_ttf_name,symbol_ttf_name,label_color,label_angle,label_size,label_minsize,label_maxsize from class where not label_font like 'esri%' and symbol_ttf_name is not null and label_font is not null;


--TOLGO I SYMBOLI TTF DA CLASSI
update class set symbol_ttf_name=null,label_font=null where  symbol_ttf_name is not null and label_font is not null;

--PULIZIA
--DROP TABLE symbol_ttf;
--DROP SEQUENCE gisclient_25.e_pattern_pattern_id_seq;
--ALTER TABLE e_pattern ALTER COLUMN pattern_id TYPE smallint;		   

		   



