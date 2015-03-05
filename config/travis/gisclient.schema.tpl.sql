-- apply
-- sed -i 's/DB_SCHEMA/my_gisclient_schema/g' gisclient.sql
-- to adapt the schema name in this file


--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: DB_SCHEMA; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA DB_SCHEMA;


SET search_path = DB_SCHEMA, pg_catalog;


--
-- Name: tree; Type: TYPE; Schema: DB_SCHEMA; Owner: -
--

CREATE TYPE tree AS (
	id integer,
	name character varying,
	lvl_id integer,
	lvl_name character varying
);


--
-- Name: check_catalog(); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION check_catalog() RETURNS trigger
    LANGUAGE plpgsql
    AS $_$
BEGIN
	if(coalesce(new.catalog_path,'')<>'' and new.connection_type=1) then
		if (not new.catalog_path ~ '^(.+)/$') then
			new.catalog_path:=new.catalog_path||'/';
		end if;
	end if;
	return new;
END
$_$;


--
-- Name: check_class(); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION check_class() RETURNS trigger
    LANGUAGE plpgsql IMMUTABLE
    AS $_$
DECLARE
	ok boolean;
BEGIN
	if trim(coalesce(new.label_angle,''))<>'' then
		if not((new.label_angle ~ '^([0-9]+)(\.[0-9])?$') or (new.label_angle ~ '^([\[]{1})([A-z0-9]+)([\]]{1})$') or (upper(new.label_angle) = 'AUTO')) then 	--CONTROLLO IL VALORE DEL LABEL_ANGLE
			raise exception 'label_angle @ Il valore deve essere un numero, AUTO oppure un campo di binding (es. [nome_campo])';
		end if;
	end if;
	if trim(coalesce(new.label_size,''))<>'' then
		if not((new.label_size ~ '^([0-9]+)$') or (new.label_size ~ '^([\[]{1})([A-z0-9]+)([\]]{1})$')) then 	--CONTROLLO IL VALORE DEL LABEL_SIZE
			raise exception 'label_size @ Il valore deve essere un numero intero oppure un campo di binding (es. [nome_campo])';
		end if;
	end if;
	return new;
END
$_$;


--
-- Name: check_layer(); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION check_layer() RETURNS trigger
    LANGUAGE plpgsql
    AS $_$
DECLARE
	pr_name varchar;
	presente integer;
	newindex integer;
	ok boolean;
BEGIN
	--CONTROLLO CHE IL NOME SIA COMPOSTO DA CARATTERI ALFA-NUMERICI
	if(new.layer_name ~* '([ ]+)')	then 	
		raise exception 'layer_name @ Il nome del layer deve essere alfanumerico senza spazi bianchi';
	end if;

	--CONTROLLO IL VALORE DELLA TRASPARENCY
	if(coalesce(new.transparency,'')<>'') then 	
		new.transparency:=upper(new.transparency);
		select into ok (new.transparency='ALPHA' or new.transparency ='100' or new.transparency ~* '^([0-9]{1,2})$');
		if not ok then
			raise exception 'transparency @ Il valore deve essere un intero compreso tra 1-100 oppure ALPHA.';
		end if;
	end if;

	return new;
END
$_$;


--
-- Name: check_layergroup(); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION check_layergroup() RETURNS trigger
    LANGUAGE plpgsql
    AS $_$
DECLARE
	pr_name varchar;
	presente integer;
	newindex integer;
	ok boolean;
BEGIN
	--CONTROLLO IL VALORE DELLA TRASPARENCY
	if(coalesce(new.transparency,'')<>'') then 	
		new.transparency:=upper(new.transparency);
		select into ok (new.transparency='ALPHA' or new.transparency ='100' or new.transparency ~* '^([0-9]{1,2})$');
		if not ok then
			raise exception 'transparency @ Il valore deve essere un intero compreso tra 1-100 oppure ALPHA.';
		end if;
	end if;

	return new;
END
$_$;


--
-- Name: check_mapset(); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION check_mapset() RETURNS trigger
    LANGUAGE plpgsql
    AS $_$
DECLARE
	ext varchar;
	presente integer;
BEGIN

	new.mapset_name:=regexp_replace(trim(new.mapset_name),'([\t ]+)','_','g');
	select coalesce(project_extent,'') into ext from DB_SCHEMA.project where project_name=new.project_name;
	if (coalesce(new.mapset_extent,'')='') then
		new.mapset_extent:=ext;
	else
		new.mapset_extent:=regexp_replace(trim(new.mapset_extent),'([\t ]+)',' ','g');
		if (new.mapset_extent !~* '^([0-9. -]+)$') then
			raise exception 'extent @ I valori di Extent devono essere 4 valori numerici separati da uno spazio essere un percorso valido';
		end if;
	end if;
	if (coalesce(new.refmap_extent,'')='') then
		new.refmap_extent=new.mapset_extent;
	else
		new.refmap_extent:=regexp_replace(trim(new.refmap_extent),'([\t ]+)',' ','g');
		if (new.refmap_extent !~* '^([0-9. -]+)$') then
			raise exception 'extent @ I valori di Extent devono essere 4 valori numerici separati da uno spazio essere un percorso valido';
		end if;
	end if;
	if (coalesce(new.test_extent,'')='') then
		new.test_extent=new.mapset_extent;
	else
		new.test_extent:=regexp_replace(trim(new.test_extent),'([\t ]+)',' ','g');
		if (new.test_extent !~* '^([0-9. -]+)$') then
			raise exception 'extent @ I valori di Extent devono essere 4 valori numerici separati da uno spazio essere un percorso valido';
		end if;
	end if;

	return new;
END
$_$;


--
-- Name: check_project(); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION check_project() RETURNS trigger
    LANGUAGE plpgsql
    AS $_$
DECLARE 
	ok boolean;
	presente integer;
	sk varchar;
	query text;
	newid integer;
BEGIN
	ok:=false;
	sk:='DB_SCHEMA';	
	-- AGGIUNGO AL BASE URL LO SLASH FINALE (UTILE PER I LINK)
	if(coalesce(new.base_url,'')<>'') then
		if (not new.base_url ~ '^(.+)/$') then
			new.base_url:=new.base_url||'/';
		end if;
	end if;
	if(coalesce(new.base_path,'')<>'') then
		if (not new.base_path ~ '^(.+)/$') then
			new.base_path:=new.base_path||'/';
		end if;
	end if;

	-- CONTROLLO DELL'EXTENT
	if (trim(coalesce(new.project_extent,''))!='') then
		new.project_extent:=regexp_replace(trim(new.project_extent),'([\t ]+)',' ','g');
		if (new.project_extent !~* '^([0-9. -]+)$') then
			raise exception 'extent @ I valori di Extent devono essere 4 valori numerici separati da uno spazio essere un percorso valido';
		end if;
	end if;
	return new;
END
$_$;


--
-- Name: delete_qt(); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION delete_qt() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	delete from DB_SCHEMA.qtfield where qt_id=old.qt_id;
	return old;
END
$$;


--
-- Name: delete_qtrelation(); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION delete_qtrelation() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	delete from DB_SCHEMA.qtfield where qtrelation_id=old.qtrelation_id;
	return old;
END
$$;


--
-- Name: enc_pwd(); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION enc_pwd() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	if (coalesce(new.pwd,'')<>'') then
		new.enc_pwd:=md5(new.pwd);
		new.pwd = null;
	end if;
	return new;
END
$$;


--
-- Name: gw_findtree(integer, character varying); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION gw_findtree(id integer, lvl character varying) RETURNS SETOF tree
    LANGUAGE plpgsql IMMUTABLE
    AS $$
DECLARE
	rec record;
	t DB_SCHEMA.tree;
	i integer;
	d integer;
BEGIN
	select into d coalesce(depth,-1) from DB_SCHEMA.e_level where name=lvl;
	if (d=-1) then
		raise exception 'Livello % non esistente',lvl;
	end if;
	for i in reverse d..1 loop
		return next t;
	end loop;
	
END
$$;


--
-- Name: move_layergroup(); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION move_layergroup() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	if(new.theme_id<>old.theme_id) then
		update DB_SCHEMA.qt set theme_id=new.theme_id where qt_id in (select distinct qt_id from DB_SCHEMA.qt inner join DB_SCHEMA.layer using(layer_id) inner join DB_SCHEMA.layergroup using(layergroup_id) where layergroup_id=new.layergroup_id);
	end if;
	return new;
END
$$;


--
-- Name: new_pkey(character varying, character varying); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION new_pkey(tab character varying, id_fld character varying) RETURNS integer
    LANGUAGE plpgsql
    AS $$
declare
    newid integer;
	sk varchar;
	query varchar;
begin
	sk:='DB_SCHEMA';
	query:='select '||sk||'.new_pkey('''||tab||''','''||id_fld||''',0)';
	execute query into newid;
	return newid;
end
$$;


--
-- Name: new_pkey(character varying, character varying, integer); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION new_pkey(tab character varying, id_fld character varying, st integer) RETURNS integer
    LANGUAGE plpgsql
    AS $$
declare
    str varchar;
start_value integer;
    newid record;
    sk varchar;
begin
	sk:='DB_SCHEMA';
	if (st=0) then
		start_value:=0;
	else
		start_value:=st-1;
	end if;
	str:='select '||id_fld||' as id from '||sk||'.'||tab||' where '||id_fld||' ='||start_value||'+1';
	execute str into newid;
	if (coalesce(newid.id,0)=0) then
		return (start_value+1);
	end if;
	str:='SELECT coalesce(min('||id_fld||'),0)+1 as id FROM '||sk||'.'||tab||' f1
    WHERE NOT EXISTS (SELECT 1 FROM '||sk||'.'||tab||' f2 WHERE f2.'||id_fld||' = (f1.'||id_fld||'+1)) and '||id_fld||' > '||start_value||';';
	--raise notice '%',str;
	execute str into newid;
	return newid.id;

 
end
$$;


--
-- Name: new_pkey(character varying, character varying, character varying); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION new_pkey(sk character varying, tab character varying, id_fld character varying) RETURNS integer
    LANGUAGE plpgsql
    AS $$
declare
    newid integer;
begin
	select DB_SCHEMA.new_pkey(sk ,tab,id_fld,0) into newid; 
	return newid;
end
$$;


--
-- Name: new_pkey(character varying, character varying, character varying, integer); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION new_pkey(sk character varying, tab character varying, id_fld character varying, st integer) RETURNS integer
    LANGUAGE plpgsql
    AS $$
declare
    str varchar;
start_value integer;
    newid record;
begin
	if (st=0) then
		start_value:=0;
	else
		start_value:=st-1;
	end if;
	str:='select '||id_fld||' as id from '||sk||'.'||tab||' where '||id_fld||' ='||start_value||'+1';
	execute str into newid;
	if (coalesce(newid.id,0)=0) then
		return (start_value+1);
	end if;
	str:='SELECT coalesce(min('||id_fld||'),0)+1 as id FROM '||sk||'.'||tab||' f1
    WHERE NOT EXISTS (SELECT 1 FROM '||sk||'.'||tab||' f2 WHERE f2.'||id_fld||' = (f1.'||id_fld||'+1)) and '||id_fld||' > '||start_value||';';
	execute str into newid;
	return newid.id;

 
end
$$;


--
-- Name: new_pkey_varchar(character varying, character varying, character varying); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION new_pkey_varchar(tb character varying, fld character varying, val character varying) RETURNS character varying
    LANGUAGE plpgsql IMMUTABLE
    AS $_$
DECLARE
	query text;
	presente integer;
	newval varchar;
BEGIN
query:='select count(*) from DB_SCHEMA.'||tb||' where '||fld||'='''||val||''';';
execute query into presente;
if(presente>0) then
	query:='select map||(max(newindex)+1)::varchar from (select regexp_replace('||fld||',''([0-9]+)$'','''') as map,case when(regexp_replace('||fld||',''^([A-z_]+)'','''')='''') then 0 else regexp_replace('||fld||',''^([A-z_]+)'','''')::integer end as newindex from DB_SCHEMA.'||tb||' where '''||val||''' ~* regexp_replace('||fld||',''([0-9]+)$'','''')) X group by map;';
	execute query into newval;
	return newval;
else
	return val;
end if;
END
$_$;


--
-- Name: rm_project_groups(); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION rm_project_groups() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE

BEGIN
	delete from DB_SCHEMA.mapset_groups where mapset_name in (select distinct mapset_name from DB_SCHEMA.mapset where project_name=old.project_name) and group_name=old.group_name;
	return old;
END
$$;


--
-- Name: set_depth(); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION set_depth() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	if (TG_OP='INSERT') then
		update DB_SCHEMA.e_level set depth=(select coalesce(depth+1,0) from DB_SCHEMA.e_level where id=new.parent_id) where id=new.id;
	elseif(new.parent_id<>coalesce(old.parent_id,-1)) then
		update DB_SCHEMA.e_level set depth=(select coalesce(depth+1,0) from DB_SCHEMA.e_level where id=new.parent_id) where id=new.id;
	end if;
	return new;
END
$$;


--
-- Name: set_layer_name(); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION set_layer_name() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	SELECT INTO NEW.layer_name layer_name FROM DB_SCHEMA.layer WHERE layer_id=NEW.layer_id;
	RETURN NEW;
END
$$;


--
-- Name: set_leaf(); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION set_leaf() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	if(TG_OP='INSERT') then
		update DB_SCHEMA.e_level X set leaf=(select case when (count(parent_id)>0) then 0 else 1 end from DB_SCHEMA.e_level where parent_id=X.id);
	elsif (new.parent_id<> coalesce(old.parent_id,-1)) then
		update DB_SCHEMA.e_level X set leaf=(select case when (count(parent_id)>0) then 0 else 1 end from DB_SCHEMA.e_level where parent_id=X.id);
	end if;
	return new;
END
$$;


--
-- Name: set_map_extent(); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION set_map_extent() RETURNS trigger
    LANGUAGE plpgsql
    AS $_$
DECLARE
	ext varchar;
BEGIN

	new.mapset_name:=regexp_replace(trim(new.mapset_name),'([\t ]+)','_','g');
	select coalesce(project_extent,'') into ext from DB_SCHEMA.project where project_name=new.project_name;
	if (coalesce(new.mapset_extent,'')='') then
		new.mapset_extent:=ext;
	else
		new.mapset_extent:=regexp_replace(trim(new.mapset_extent),'([\t ]+)',' ','g');
		if (new.mapset_extent !~* '^([0-9. -]+)$') then
			raise exception 'extent @ I valori di Extent devono essere 4 valori numerici separati da uno spazio essere un percorso valido';
		end if;
	end if;
	if (coalesce(new.refmap_extent,'')='') then
		new.refmap_extent=new.mapset_extent;
	else
		new.refmap_extent:=regexp_replace(trim(new.refmap_extent),'([\t ]+)',' ','g');
		if (new.refmap_extent !~* '^([0-9. -]+)$') then
			raise exception 'extent @ I valori di Extent devono essere 4 valori numerici separati da uno spazio essere un percorso valido';
		end if;
	end if;
	if (coalesce(new.test_extent,'')='') then
		new.test_extent=new.mapset_extent;
	else
		new.test_extent:=regexp_replace(trim(new.test_extent),'([\t ]+)',' ','g');
		if (new.test_extent !~* '^([0-9. -]+)$') then
			raise exception 'extent @ I valori di Extent devono essere 4 valori numerici separati da uno spazio essere un percorso valido';
		end if;
	end if;

	return new;
END
$_$;


--
-- Name: style_name(); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION style_name() RETURNS trigger
    LANGUAGE plpgsql
    AS $_$
DECLARE
	num integer;
	rec record;
	check_hatch smallint;
BEGIN
	if ((coalesce(new.symbol_name,'')<>'') and (coalesce(new.size,0)=0)) then
		select into rec * from DB_SCHEMA.symbol where symbol_name=new.symbol_name;
		if rec.style_def='TYPE HATCH' then
			raise exception 'size @ Per questo tipo di Simbolo ?ecessario definire il campo size';
		end if;
	end if;
	if trim(coalesce(new.angle,''))<>'' then
		if not((new.angle ~ '^([0-9]+)(\.[0-9])?$') or (new.angle ~ '^([\[]{1})([A-z0-9]+)([\]]{1})$') or (upper(new.angle) = 'AUTO')) then 	--CONTROLLO IL VALORE DEL LABEL_ANGLE
			raise exception 'angle @ Il valore deve essere un numero, AUTO oppure un campo di binding (es. [nome_campo])';
		end if;
	end if;
	
	if coalesce(new.style_name,'')='' then
		if new.style_order > 0 then 
			num:=new.style_order;
		else
			SELECT INTO num count(*)+1 FROM DB_SCHEMA.style WHERE class_id=new.class_id and style_name ~* 'Stile ([0-9]+)';
		end if;
		new.style_name:='Stile '||num::varchar;
	end if;
	return new;
END
$_$;


--
-- Name: theme_version_tr(); Type: FUNCTION; Schema: DB_SCHEMA; Owner: -
--

CREATE FUNCTION theme_version_tr() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  /* New function body */
  RAISE NOTICE 'Trigger "%" called on "%" for table %.%', TG_NAME, TG_OP, TG_TABLE_SCHEMA, TG_TABLE_NAME;
  IF TG_OP='INSERT' THEN
     INSERT INTO DB_SCHEMA.theme_version(theme_id, theme_version) VALUES (NEW.theme_id, nextval('DB_SCHEMA.theme_version_id_seq'));
  ELSIF TG_OP='UPDATE' THEN
     UPDATE DB_SCHEMA.theme_version SET theme_version=nextval('DB_SCHEMA.theme_version_id_seq') WHERE theme_id=NEW.theme_id;
  END IF;
  RETURN NEW;
END;
$$;


SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: access_log; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE access_log (
    al_id integer NOT NULL,
    al_ip character(15),
    al_date timestamp without time zone,
    al_referer character varying,
    al_page character varying,
    al_useragent character varying
);


--
-- Name: access_log_al_id_seq; Type: SEQUENCE; Schema: DB_SCHEMA; Owner: -
--

CREATE SEQUENCE access_log_al_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: access_log_al_id_seq; Type: SEQUENCE OWNED BY; Schema: DB_SCHEMA; Owner: -
--

ALTER SEQUENCE access_log_al_id_seq OWNED BY access_log.al_id;


--
-- Name: authfilter; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE authfilter (
    filter_id integer NOT NULL,
    filter_name character varying(100),
    filter_description text,
    filter_priority integer DEFAULT 0 NOT NULL
);


--
-- Name: catalog; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE catalog (
    catalog_id integer NOT NULL,
    catalog_name character varying NOT NULL,
    project_name character varying NOT NULL,
    connection_type smallint NOT NULL,
    catalog_path character varying NOT NULL,
    catalog_url character varying,
    catalog_description text,
    files_path character varying
);


--
-- Name: catalog_import; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE catalog_import (
    catalog_import_id integer NOT NULL,
    project_name character varying NOT NULL,
    catalog_import_name text,
    catalog_from integer NOT NULL,
    catalog_to integer NOT NULL,
    catalog_import_description text
);


--
-- Name: class; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE class (
    class_id integer NOT NULL,
    layer_id integer,
    class_name character varying NOT NULL,
    class_title character varying,
    class_text character varying,
    expression character varying,
    maxscale character varying,
    minscale character varying,
    class_template character varying,
    class_order integer,
    legendtype_id smallint DEFAULT 1,
    symbol_ttf_name character varying,
    label_font character varying,
    label_angle character varying,
    label_color character varying,
    label_outlinecolor character varying,
    label_bgcolor character varying,
    label_size character varying,
    label_minsize smallint,
    label_maxsize smallint,
    label_position character varying,
    label_antialias smallint DEFAULT 0,
    label_free smallint DEFAULT 0,
    label_priority smallint,
    label_wrap character(1),
    label_buffer integer DEFAULT 0,
    label_force smallint DEFAULT 0,
    label_def text,
    locked integer DEFAULT 0,
    class_image bytea,
    keyimage character varying
);


--
-- Name: classgroup; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE classgroup (
    classgroup_id integer NOT NULL,
    layer_id integer,
    classgroup_name character varying,
    classgroup_title character varying,
    classitem character varying,
    labelitem character varying,
    labelsizeitem character varying,
    labelminscale character varying,
    labelmaxscale character varying,
    template character varying,
    header character varying,
    footer character varying,
    tolerance integer,
    opacity character varying,
    maxfeatures integer,
    layer_order integer DEFAULT 0,
    maxscale character varying,
    minscale character varying,
    symbolscale character varying,
    sizeunits_id numeric(1,0) DEFAULT 1,
    default_group numeric(1,0) DEFAULT 0
);


--
-- Name: e_charset_encodings; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_charset_encodings (
    charset_encodings_id integer NOT NULL,
    charset_encodings_name character varying NOT NULL,
    charset_encodings_order smallint
);


--
-- Name: e_conntype; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_conntype (
    conntype_id smallint NOT NULL,
    conntype_name character varying NOT NULL,
    conntype_order smallint
);


--
-- Name: TABLE e_conntype; Type: COMMENT; Schema: DB_SCHEMA; Owner: -
--

COMMENT ON TABLE e_conntype IS 'Il tipo File Shape deve essere sempre uguale a 1!';


--
-- Name: e_datatype; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_datatype (
    datatype_id smallint NOT NULL,
    datatype_name character varying NOT NULL,
    datatype_order smallint
);


--
-- Name: e_fieldformat; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_fieldformat (
    fieldformat_id integer NOT NULL,
    fieldformat_name character varying NOT NULL,
    fieldformat_format character varying NOT NULL,
    fieldformat_order smallint
);


--
-- Name: e_fieldtype; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_fieldtype (
    fieldtype_id smallint NOT NULL,
    fieldtype_name character varying NOT NULL,
    fieldtype_order smallint
);


--
-- Name: e_filetype; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_filetype (
    filetype_id smallint NOT NULL,
    filetype_name character varying NOT NULL,
    filetype_order smallint
);


--
-- Name: e_form; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_form (
    id integer NOT NULL,
    name character varying,
    config_file character varying,
    tab_type integer,
    level_destination integer,
    form_destination character varying,
    save_data character varying,
    parent_level integer,
    js text,
    table_name character varying,
    order_by character varying
);


--
-- Name: e_language; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_language (
    language_id character(2) NOT NULL,
    language_name character varying NOT NULL,
    language_order integer
);


--
-- Name: e_layertype; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_layertype (
    layertype_id smallint NOT NULL,
    layertype_name character varying NOT NULL,
    layertype_ms smallint,
    layertype_order smallint
);


--
-- Name: e_lblposition; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_lblposition (
    lblposition_id integer NOT NULL,
    lblposition_name character varying NOT NULL,
    lblposition_order smallint
);


--
-- Name: e_legendtype; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_legendtype (
    legendtype_id smallint NOT NULL,
    legendtype_name character varying NOT NULL,
    legendtype_order smallint
);


--
-- Name: e_level; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_level (
    id integer NOT NULL,
    name character varying,
    parent_name character varying,
    "order" smallint,
    parent_id smallint,
    depth smallint,
    leaf smallint,
    export integer DEFAULT 1,
    struct_parent_id integer,
    "table" character varying,
    admintype_id integer DEFAULT 2
);


--
-- Name: e_orderby; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_orderby (
    orderby_id smallint NOT NULL,
    orderby_name character varying NOT NULL,
    orderby_order smallint
);


--
-- Name: e_outputformat; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_outputformat (
    outputformat_id smallint NOT NULL,
    outputformat_name character varying NOT NULL,
    outputformat_driver character varying NOT NULL,
    outputformat_mimetype character varying NOT NULL,
    outputformat_imagemode character varying NOT NULL,
    outputformat_extension character varying NOT NULL,
    outputformat_option character varying,
    outputformat_order smallint
);


--
-- Name: e_owstype; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_owstype (
    owstype_id smallint NOT NULL,
    owstype_name character varying NOT NULL,
    owstype_order smallint
);


--
-- Name: e_papersize; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_papersize (
    papersize_id integer NOT NULL,
    papersize_name character varying NOT NULL,
    papersize_size character varying NOT NULL,
    papersize_orientation character varying,
    papaersize_order smallint
);


--
-- Name: e_pattern; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_pattern (
    pattern_id integer NOT NULL,
    pattern_name character varying NOT NULL,
    pattern_def character varying NOT NULL,
    pattern_order smallint
);


--
-- Name: e_pattern_pattern_id_seq; Type: SEQUENCE; Schema: DB_SCHEMA; Owner: -
--

CREATE SEQUENCE e_pattern_pattern_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: e_pattern_pattern_id_seq; Type: SEQUENCE OWNED BY; Schema: DB_SCHEMA; Owner: -
--

ALTER SEQUENCE e_pattern_pattern_id_seq OWNED BY e_pattern.pattern_id;


--
-- Name: e_qtrelationtype; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_qtrelationtype (
    qtrelationtype_id integer NOT NULL,
    qtrelationtype_name character varying NOT NULL,
    qtrelationtype_order smallint
);


--
-- Name: e_resultype; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_resultype (
    resultype_id smallint NOT NULL,
    resultype_name character varying NOT NULL,
    resultype_order smallint
);


--
-- Name: e_searchtype; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_searchtype (
    searchtype_id smallint NOT NULL,
    searchtype_name character varying NOT NULL,
    searchtype_order smallint
);


--
-- Name: e_sizeunits; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_sizeunits (
    sizeunits_id smallint NOT NULL,
    sizeunits_name character varying NOT NULL,
    sizeunits_order smallint
);


--
-- Name: e_symbolcategory; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_symbolcategory (
    symbolcategory_id smallint NOT NULL,
    symbolcategory_name character varying NOT NULL,
    symbolcategory_order smallint
);


--
-- Name: e_tiletype; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE e_tiletype (
    tiletype_id smallint NOT NULL,
    tiletype_name character varying NOT NULL,
    tiletype_order smallint
);


--
-- Name: form_level; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE form_level (
    id integer NOT NULL,
    level integer,
    mode integer,
    form integer,
    order_fld integer,
    visible smallint DEFAULT 1
);


--
-- Name: elenco_form; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW elenco_form AS
    SELECT form_level.id AS "ID", form_level.mode, CASE WHEN (form_level.mode = 2) THEN 'New'::text WHEN (form_level.mode = 3) THEN 'Elenco'::text WHEN (form_level.mode = 0) THEN 'View'::text WHEN (form_level.mode = 1) THEN 'Edit'::text ELSE 'Non definito'::text END AS "Modo Visualizzazione Pagina", e_form.id AS "Form ID", e_form.name AS "Nome Form", e_form.tab_type AS "Tipo Tabella", x.name AS "Livello Destinazione", e_level.name AS "Livello Visualizzazione", CASE WHEN (COALESCE((e_level.depth)::integer, (-1)) = (-1)) THEN 0 ELSE (e_level.depth + 1) END AS "Profondita Albero", form_level.order_fld AS "Ordine Visualizzazione", CASE WHEN (form_level.visible = 1) THEN 'SI'::text ELSE 'NO'::text END AS "Visibile" FROM (((form_level JOIN e_level ON ((form_level.level = e_level.id))) JOIN e_form ON ((e_form.id = form_level.form))) JOIN e_level x ON ((x.id = e_form.level_destination))) ORDER BY CASE WHEN (COALESCE((e_level.depth)::integer, (-1)) = (-1)) THEN 0 ELSE (e_level.depth + 1) END, form_level.level, CASE WHEN (form_level.mode = 2) THEN 'Nuovo'::text WHEN ((form_level.mode = 0) OR (form_level.mode = 3)) THEN 'Elenco'::text WHEN (form_level.mode = 1) THEN 'View'::text ELSE 'Edit'::text END, form_level.order_fld;


--
-- Name: font; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE font (
    font_name character varying NOT NULL,
    file_name character varying NOT NULL
);


--
-- Name: group_authfilter; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE group_authfilter (
    groupname character varying NOT NULL,
    filter_id integer NOT NULL,
    filter_expression character varying
);


--
-- Name: groups; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE groups (
    groupname character varying NOT NULL,
    description character varying
);


--
-- Name: i18n_field; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE i18n_field (
    i18nf_id integer NOT NULL,
    table_name character varying(255),
    field_name character varying(255)
);


--
-- Name: i18n_field_i18nf_id_seq; Type: SEQUENCE; Schema: DB_SCHEMA; Owner: -
--

CREATE SEQUENCE i18n_field_i18nf_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: i18n_field_i18nf_id_seq; Type: SEQUENCE OWNED BY; Schema: DB_SCHEMA; Owner: -
--

ALTER SEQUENCE i18n_field_i18nf_id_seq OWNED BY i18n_field.i18nf_id;


--
-- Name: layer; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE layer (
    layer_id integer NOT NULL,
    layergroup_id integer NOT NULL,
    layer_name character varying NOT NULL,
    layertype_id smallint NOT NULL,
    catalog_id integer NOT NULL,
    data character varying,
    data_geom character varying,
    data_unique character varying,
    data_srid integer,
    data_filter character varying,
    classitem character varying,
    labelitem character varying,
    labelsizeitem character varying,
    labelminscale character varying,
    labelmaxscale character varying,
    maxscale character varying,
    minscale character varying,
    symbolscale integer,
    opacity character varying,
    maxfeatures integer,
    sizeunits_id numeric(1,0) DEFAULT 1,
    layer_def text,
    metadata text,
    template character varying,
    header character varying,
    footer character varying,
    tolerance integer,
    layer_order integer DEFAULT 0,
    queryable numeric(1,0) DEFAULT 0,
    layer_title character varying,
    zoom_buffer numeric,
    group_object numeric(1,0),
    selection_color character varying,
    papersize_id numeric,
    toleranceunits_id numeric(1,0),
    selection_width numeric(2,0),
    selection_info numeric(1,0) DEFAULT 1,
    hidden numeric(1,0) DEFAULT 0,
    private numeric(1,0) DEFAULT 0,
    postlabelcache numeric(1,0) DEFAULT 0,
    maxvectfeatures integer,
    data_type character varying,
    last_update character varying,
    data_extent character varying,
    searchable numeric(1,0) DEFAULT 0,
    hide_vector_geom numeric(1,0) DEFAULT 0,
    CONSTRAINT layer_layertype_id_check CHECK ((layertype_id > 0))
);


--
-- Name: layer_authfilter; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE layer_authfilter (
    layer_id integer NOT NULL,
    filter_id integer NOT NULL,
    required smallint DEFAULT 0
);


--
-- Name: layer_groups_seq; Type: SEQUENCE; Schema: DB_SCHEMA; Owner: -
--

CREATE SEQUENCE layer_groups_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: layer_groups; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE layer_groups (
    layer_id integer NOT NULL,
    groupname character varying NOT NULL,
    wms integer DEFAULT 0,
    wfs integer DEFAULT 0,
    wfst integer DEFAULT 0,
    layer_name character varying,
    layer_groups_id integer DEFAULT nextval('layer_groups_seq'::regclass) NOT NULL
);


--
-- Name: layergroup; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE layergroup (
    layergroup_id integer NOT NULL,
    theme_id integer NOT NULL,
    layergroup_name character varying NOT NULL,
    layergroup_title character varying,
    layergroup_maxscale integer,
    layergroup_minscale integer,
    layergroup_smbscale integer,
    layergroup_order integer,
    locked smallint DEFAULT 0,
    multi smallint DEFAULT 0,
    hidden integer DEFAULT 0,
    isbaselayer smallint DEFAULT 0,
    tiletype_id numeric(1,0) DEFAULT 1,
    sld character varying,
    style character varying,
    url character varying,
    owstype_id smallint DEFAULT 1,
    outputformat_id smallint DEFAULT 1,
    layers character varying,
    parameters character varying,
    gutter smallint DEFAULT 0,
    transition numeric(1,0),
    tree_group character varying,
    layergroup_description character varying,
    buffer numeric(1,0),
    tiles_extent character varying,
    tiles_extent_srid integer,
    layergroup_single numeric(1,0) DEFAULT 1,
    metadata_url character varying,
    opacity character varying DEFAULT 100,
    tile_origin text,
    tile_resolutions text,
    tile_matrix_set character varying,
    CONSTRAINT layergroup_name_lower_case CHECK (((layergroup_name)::text = lower((layergroup_name)::text)))
);


--
-- Name: link; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE link (
    link_id integer NOT NULL,
    project_name character varying NOT NULL,
    link_name character varying NOT NULL,
    link_def character varying NOT NULL,
    link_order smallint DEFAULT 0,
    winw smallint DEFAULT 800,
    winh smallint DEFAULT 600
);


--
-- Name: localization; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE localization (
    localization_id integer NOT NULL,
    project_name character varying NOT NULL,
    i18nf_id integer,
    pkey_id character varying NOT NULL,
    language_id character(2),
    value text
);


--
-- Name: localization_localization_id_seq; Type: SEQUENCE; Schema: DB_SCHEMA; Owner: -
--

CREATE SEQUENCE localization_localization_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: localization_localization_id_seq; Type: SEQUENCE OWNED BY; Schema: DB_SCHEMA; Owner: -
--

ALTER SEQUENCE localization_localization_id_seq OWNED BY localization.localization_id;


--
-- Name: mapset; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE mapset (
    mapset_name character varying NOT NULL,
    project_name character varying NOT NULL,
    mapset_title character varying,
    mapset_description text,
    template character varying,
    mapset_extent character varying,
    page_size character varying,
    filter_data character varying,
    dl_image_res character varying,
    imagelabel smallint DEFAULT 0,
    bg_color character varying DEFAULT '255 255 255'::character varying,
    refmap_extent character varying,
    test_extent character varying,
    mapset_srid integer DEFAULT (-1),
    mapset_def character varying,
    mapset_group character varying,
    private integer DEFAULT 0,
    sizeunits_id smallint DEFAULT 5,
    static_reference integer DEFAULT 0,
    metadata text,
    mapset_note text,
    mask character varying,
    maxscale integer,
    minscale integer,
    mapset_scales character varying,
    displayprojection integer
);


--
-- Name: COLUMN mapset.mapset_scales; Type: COMMENT; Schema: DB_SCHEMA; Owner: -
--

COMMENT ON COLUMN mapset.mapset_scales IS 'Possible scale list separated with comma';


--
-- Name: mapset_layergroup; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE mapset_layergroup (
    mapset_name character varying NOT NULL,
    layergroup_id integer NOT NULL,
    status smallint DEFAULT 0,
    refmap smallint DEFAULT 0,
    hide smallint DEFAULT 0
);


--
-- Name: project; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE project (
    project_name character varying NOT NULL,
    project_title character varying,
    project_description text,
    base_path character varying,
    base_url character varying,
    project_extent character varying,
    sel_user_color character varying,
    sel_transparency integer DEFAULT 50,
    imagelabel_font character varying,
    imagelabel_text character varying,
    imagelabel_offset_x integer DEFAULT 5,
    imagelabel_offset_y integer DEFAULT 5,
    imagelabel_position character(2) DEFAULT 'LR'::bpchar,
    icon_w smallint DEFAULT 36,
    icon_h smallint DEFAULT 24,
    history smallint DEFAULT 4,
    project_srid integer NOT NULL,
    imagelabel_size integer DEFAULT 18,
    imagelabel_color character varying DEFAULT '0 0 0'::character varying,
    login_page character varying,
    project_note text,
    include_outputformats character varying,
    include_legend character varying,
    include_metadata character varying,
    xc numeric,
    yc numeric,
    max_extent_scale numeric,
    default_language_id character(2) DEFAULT 'it'::bpchar NOT NULL,
    charset_encodings_id integer
);


--
-- Name: project_admin; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE project_admin (
    project_name character varying NOT NULL,
    username character varying NOT NULL
);


--
-- Name: project_languages; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE project_languages (
    project_name character varying NOT NULL,
    language_id character(2) NOT NULL
);


--
-- Name: project_srs; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE project_srs (
    project_name character varying NOT NULL,
    srid integer NOT NULL,
    projparam character varying,
    custom_srid integer
);


--
-- Name: qt; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE qt (
    qt_id integer NOT NULL,
    theme_id integer NOT NULL,
    layer_id integer NOT NULL,
    qt_name character varying NOT NULL,
    max_rows smallint DEFAULT 25,
    papersize_id integer,
    edit_url character varying,
    groupobject integer DEFAULT 0,
    selection_color character varying,
    qt_order smallint DEFAULT 0,
    qtresultype_id integer,
    qt_filter character varying,
    zoom_buffer integer,
    catalog_id integer,
    data character varying,
    data_geom character varying,
    data_unique character varying,
    data_srid integer,
    data_filter character varying,
    template character varying,
    tolerance character varying,
    default_qt numeric(1,0) DEFAULT 0,
    qt_title character varying,
    layergroup_id integer
);


--
-- Name: qtfield; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE qtfield (
    qtfield_id integer NOT NULL,
    qtrelation_id integer DEFAULT 0 NOT NULL,
    qtfield_name character varying NOT NULL,
    field_header character varying NOT NULL,
    fieldtype_id smallint DEFAULT 1 NOT NULL,
    searchtype_id smallint DEFAULT 1 NOT NULL,
    resultype_id smallint DEFAULT 3 NOT NULL,
    field_format character varying,
    column_width integer,
    orderby_id integer DEFAULT 0 NOT NULL,
    field_filter integer DEFAULT 0 NOT NULL,
    datatype_id smallint DEFAULT 1 NOT NULL,
    qtfield_order smallint DEFAULT 0 NOT NULL,
    default_op character varying,
    layer_id integer,
    editable numeric(1,0) DEFAULT 0,
    formula character varying,
    lookup_table character varying,
    lookup_id character varying,
    lookup_name character varying,
    filter_field_name character varying,
    CONSTRAINT qtfield_qtrelation_id_check CHECK ((qtrelation_id >= 0))
);


--
-- Name: qtfield_groups; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE qtfield_groups (
    qtfield_id integer NOT NULL,
    groupname character varying NOT NULL,
    editable numeric(1,0) DEFAULT 0
);


--
-- Name: qtlink; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE qtlink (
    link_id integer NOT NULL,
    resultype_id smallint,
    layer_id integer NOT NULL
);


--
-- Name: qtrelation; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE qtrelation (
    qtrelation_id integer NOT NULL,
    catalog_id integer NOT NULL,
    qtrelation_name character varying NOT NULL,
    qtrelationtype_id integer DEFAULT 1 NOT NULL,
    data_field_1 character varying NOT NULL,
    data_field_2 character varying,
    data_field_3 character varying,
    table_name character varying NOT NULL,
    table_field_1 character varying NOT NULL,
    table_field_2 character varying,
    table_field_3 character varying,
    language_id character varying(2),
    layer_id integer,
    CONSTRAINT qtrelation_name_lower_case CHECK (((qtrelation_name)::text = lower((qtrelation_name)::text))),
    CONSTRAINT qtrelation_table_name_lower_case CHECK (((table_name)::text = lower((table_name)::text)))
);


--
-- Name: seldb_catalog; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_catalog AS
    SELECT (-1) AS id, 'Seleziona ====>'::character varying AS opzione, '0'::character varying AS project_name UNION ALL SELECT foo.id, foo.opzione, foo.project_name FROM (SELECT catalog.catalog_id AS id, catalog.catalog_name AS opzione, catalog.project_name FROM catalog ORDER BY catalog.catalog_name) foo;


--
-- Name: seldb_catalog_wms; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_catalog_wms AS
    SELECT catalog.catalog_id AS id, catalog.catalog_name AS opzione, catalog.project_name FROM catalog WHERE (catalog.connection_type = 7);


--
-- Name: seldb_charset_encodings; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_charset_encodings AS
    SELECT foo.id, foo.opzione, foo.option_order FROM (SELECT (-1) AS id, 'Seleziona ====>'::character varying AS opzione, (0)::smallint AS option_order UNION SELECT e_charset_encodings.charset_encodings_id AS id, e_charset_encodings.charset_encodings_name AS opzione, e_charset_encodings.charset_encodings_order AS option_order FROM e_charset_encodings) foo ORDER BY foo.id;


--
-- Name: seldb_conntype; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_conntype AS
    SELECT NULL::integer AS id, 'Seleziona ====>'::character varying AS opzione UNION ALL SELECT foo.id, foo.opzione FROM (SELECT e_conntype.conntype_id AS id, e_conntype.conntype_name AS opzione FROM e_conntype ORDER BY e_conntype.conntype_order) foo;


--
-- Name: seldb_datatype; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_datatype AS
    SELECT e_datatype.datatype_id AS id, e_datatype.datatype_name AS opzione FROM e_datatype;


--
-- Name: seldb_field_filter; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_field_filter AS
    SELECT (-1) AS id, 'Nessuno'::character varying AS opzione, 0 AS qtfield_id, 0 AS qt_id UNION (SELECT x.qtfield_id AS id, x.field_header AS opzione, y.qtfield_id, x.layer_id AS qt_id FROM (qtfield x JOIN qtfield y USING (layer_id)) WHERE (x.qtfield_id <> y.qtfield_id) ORDER BY x.qtfield_id, x.qtfield_order);


--
-- Name: seldb_fieldtype; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_fieldtype AS
    SELECT e_fieldtype.fieldtype_id AS id, e_fieldtype.fieldtype_name AS opzione FROM e_fieldtype;


--
-- Name: seldb_filetype; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_filetype AS
    SELECT (-1) AS id, 'Seleziona ====>'::character varying AS opzione UNION SELECT e_filetype.filetype_id AS id, e_filetype.filetype_name AS opzione FROM e_filetype;


--
-- Name: seldb_font; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_font AS
    SELECT foo.id, foo.opzione FROM (SELECT ''::character varying AS id, 'Seleziona ====>'::character varying AS opzione UNION SELECT font.font_name AS id, font.font_name AS opzione FROM font) foo ORDER BY foo.id;


--
-- Name: seldb_group_authfilter; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_group_authfilter AS
    SELECT authfilter.filter_id AS id, authfilter.filter_name AS opzione, CASE WHEN (group_authfilter.groupname IS NULL) THEN ''::character varying ELSE group_authfilter.groupname END AS groupname FROM (authfilter LEFT JOIN group_authfilter USING (filter_id));


--
-- Name: seldb_language; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_language AS
    SELECT foo.id, foo.opzione FROM (SELECT ''::text AS id, 'Seleziona ====>'::character varying AS opzione UNION SELECT e_language.language_id AS id, e_language.language_name AS opzione FROM e_language) foo ORDER BY foo.id;


--
-- Name: seldb_layer_layergroup; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_layer_layergroup AS
    SELECT (-1) AS id, 'Seleziona ====>'::character varying AS opzione, NULL::integer AS layergroup_id UNION (SELECT DISTINCT layer.layer_id AS id, layer.layer_name AS opzione, layer.layergroup_id FROM layer WHERE (layer.queryable = (1)::numeric) ORDER BY layer.layer_name, layer.layer_id, layer.layergroup_id);


--
-- Name: seldb_layertype; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_layertype AS
    SELECT foo.id, foo.opzione FROM (SELECT (-1) AS id, 'Seleziona ====>'::character varying AS opzione UNION SELECT e_layertype.layertype_id AS id, e_layertype.layertype_name AS opzione FROM e_layertype) foo ORDER BY foo.id;


--
-- Name: seldb_lblposition; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_lblposition AS
    (SELECT ''::character varying AS id, 'Seleziona ====>'::character varying AS opzione UNION ALL SELECT e_lblposition.lblposition_name AS id, e_lblposition.lblposition_name AS opzione FROM e_lblposition WHERE ((e_lblposition.lblposition_name)::text = 'AUTO'::text)) UNION ALL SELECT foo.id, foo.opzione FROM (SELECT e_lblposition.lblposition_name AS id, e_lblposition.lblposition_name AS opzione FROM e_lblposition WHERE ((e_lblposition.lblposition_name)::text <> 'AUTO'::text) ORDER BY e_lblposition.lblposition_order) foo;


--
-- Name: seldb_legendtype; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_legendtype AS
    SELECT e_legendtype.legendtype_id AS id, e_legendtype.legendtype_name AS opzione FROM e_legendtype ORDER BY e_legendtype.legendtype_order;


--
-- Name: seldb_link; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_link AS
    SELECT (-1) AS id, 'Seleziona ====>'::character varying AS opzione, ''::character varying AS project_name UNION SELECT link.link_id AS id, link.link_name AS opzione, link.project_name FROM link;


--
-- Name: seldb_mapset_srid; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_mapset_srid AS
    SELECT project.project_srid AS id, project.project_srid AS opzione, project.project_name FROM project UNION SELECT project_srs.srid AS id, project_srs.srid AS opzione, project_srs.project_name FROM project_srs WHERE (NOT (((project_srs.project_name)::text || project_srs.srid) IN (SELECT ((project.project_name)::text || project.project_srid) FROM project))) ORDER BY 1;


--
-- Name: seldb_orderby; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_orderby AS
    SELECT e_orderby.orderby_id AS id, e_orderby.orderby_name AS opzione FROM e_orderby;


--
-- Name: seldb_outputformat; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_outputformat AS
    SELECT foo.id, foo.opzione FROM ((((SELECT e_outputformat.outputformat_id AS id, e_outputformat.outputformat_name AS opzione FROM e_outputformat WHERE (e_outputformat.outputformat_id = 7) UNION ALL SELECT e_outputformat.outputformat_id AS id, e_outputformat.outputformat_name AS opzione FROM e_outputformat WHERE (e_outputformat.outputformat_id = 1)) UNION ALL SELECT e_outputformat.outputformat_id AS id, e_outputformat.outputformat_name AS opzione FROM e_outputformat WHERE (e_outputformat.outputformat_id = 9)) UNION ALL SELECT e_outputformat.outputformat_id AS id, e_outputformat.outputformat_name AS opzione FROM e_outputformat WHERE (e_outputformat.outputformat_id = 3)) UNION ALL SELECT e_outputformat.outputformat_id AS id, e_outputformat.outputformat_name AS opzione FROM e_outputformat WHERE (e_outputformat.outputformat_id <> ALL (ARRAY[1, 3, 7, 9]))) foo;


--
-- Name: seldb_owstype; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_owstype AS
    SELECT e_owstype.owstype_id AS id, e_owstype.owstype_name AS opzione FROM e_owstype;


--
-- Name: seldb_papersize; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_papersize AS
    SELECT (-1) AS id, 'Seleziona ====>'::character varying AS opzione UNION SELECT e_papersize.papersize_id AS id, e_papersize.papersize_name AS opzione FROM e_papersize;


--
-- Name: seldb_pattern; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_pattern AS
    SELECT (-1) AS id, 'Seleziona ====>'::character varying AS opzione UNION ALL SELECT e_pattern.pattern_id AS id, e_pattern.pattern_name AS opzione FROM e_pattern;


--
-- Name: seldb_project; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_project AS
    SELECT ''::character varying AS id, 'Seleziona ====>'::character varying AS opzione UNION (SELECT DISTINCT project.project_name AS id, project.project_name AS opzione FROM project ORDER BY project.project_name);


--
-- Name: theme; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE theme (
    theme_id integer NOT NULL,
    project_name character varying,
    theme_name character varying NOT NULL,
    theme_title character varying,
    theme_order integer,
    locked smallint DEFAULT 0,
    theme_single numeric(1,0) DEFAULT 0,
    radio numeric(1,0) DEFAULT 0,
    copyright_string character varying,
    CONSTRAINT theme_name_lower_case CHECK (((theme_name)::text = lower((theme_name)::text)))
);


--
-- Name: seldb_qt_theme; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_qt_theme AS
    SELECT (-1) AS id, '___Seleziona ====>'::text AS opzione UNION SELECT qt.qt_id AS id, (qt.qt_name)::text AS opzione FROM (qt JOIN theme USING (theme_id)) WHERE (qt.theme_id = 55) ORDER BY 2;


--
-- Name: seldb_qtrelation; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_qtrelation AS
    SELECT 0 AS id, 'layer'::character varying AS opzione, 0 AS layer_id UNION SELECT qtrelation.qtrelation_id AS id, qtrelation.qtrelation_name AS opzione, qtrelation.layer_id FROM qtrelation;


--
-- Name: seldb_qtrelationtype; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_qtrelationtype AS
    SELECT e_qtrelationtype.qtrelationtype_id AS id, e_qtrelationtype.qtrelationtype_name AS opzione FROM e_qtrelationtype;


--
-- Name: seldb_resultype; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_resultype AS
    SELECT e_resultype.resultype_id AS id, e_resultype.resultype_name AS opzione FROM e_resultype ORDER BY e_resultype.resultype_order;


--
-- Name: seldb_searchtype; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_searchtype AS
    SELECT e_searchtype.searchtype_id AS id, e_searchtype.searchtype_name AS opzione FROM e_searchtype;


--
-- Name: seldb_sizeunits; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_sizeunits AS
    SELECT foo.id, foo.opzione FROM (SELECT ((-1))::smallint AS id, 'Seleziona ====>'::character varying AS opzione UNION SELECT e_sizeunits.sizeunits_id AS id, e_sizeunits.sizeunits_name AS opzione FROM e_sizeunits) foo ORDER BY foo.id;


--
-- Name: seldb_theme; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_theme AS
    SELECT (-1) AS id, 'Seleziona ====>'::character varying AS opzione, ''::character varying AS project_name UNION SELECT theme.theme_id AS id, theme.theme_name AS opzione, theme.project_name FROM theme;


--
-- Name: seldb_tiletype; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW seldb_tiletype AS
    SELECT e_tiletype.tiletype_id AS id, e_tiletype.tiletype_name AS opzione FROM e_tiletype;


--
-- Name: selgroup; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE selgroup (
    selgroup_id integer NOT NULL,
    project_name character varying NOT NULL,
    selgroup_name character varying NOT NULL,
    selgroup_title character varying,
    selgroup_order smallint DEFAULT 1
);


--
-- Name: selgroup_layer; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE selgroup_layer (
    selgroup_id integer NOT NULL,
    layer_id integer NOT NULL
);


--
-- Name: style; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE style (
    style_id integer NOT NULL,
    class_id integer NOT NULL,
    style_name character varying NOT NULL,
    symbol_name character varying,
    color character varying,
    outlinecolor character varying,
    bgcolor character varying,
    angle character varying,
    size character varying,
    minsize smallint,
    maxsize smallint,
    width real,
    maxwidth real,
    minwidth real,
    locked smallint DEFAULT 0,
    style_def text,
    style_order integer,
    pattern_id integer
);


--
-- Name: symbol; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE symbol (
    symbol_name character varying NOT NULL,
    symbolcategory_id integer DEFAULT 1 NOT NULL,
    icontype integer DEFAULT 0 NOT NULL,
    symbol_image bytea,
    symbol_def text,
    symbol_type character varying,
    font_name character varying,
    ascii_code integer,
    filled numeric(1,0) DEFAULT 0,
    points character varying,
    image character varying
);


--
-- Name: symbol_ttf; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE symbol_ttf (
    symbol_ttf_name character varying NOT NULL,
    font_name character varying NOT NULL,
    symbolcategory_id integer DEFAULT 0,
    ascii_code smallint NOT NULL,
    "position" character(2),
    symbol_ttf_image bytea
);


--
-- Name: tb_import; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE tb_import (
    tb_import_id integer NOT NULL,
    catalog_id integer NOT NULL,
    conn_filter character varying,
    conn_model character varying,
    file_path character varying
);


--
-- Name: tb_import_table; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE tb_import_table (
    tb_import_table_id integer NOT NULL,
    tb_import_id integer NOT NULL,
    table_name character varying
);


--
-- Name: tb_logs; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE tb_logs (
    tb_logs_id integer NOT NULL,
    tb_import_id integer,
    data date,
    ora time without time zone,
    log_info character varying
);


--
-- Name: theme_version; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE theme_version (
    theme_id integer NOT NULL,
    theme_version integer NOT NULL
);


--
-- Name: theme_version_id_seq; Type: SEQUENCE; Schema: DB_SCHEMA; Owner: -
--

CREATE SEQUENCE theme_version_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_group; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE user_group (
    username character varying NOT NULL,
    groupname character varying NOT NULL
);


--
-- Name: usercontext; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE usercontext (
    usercontext_id integer NOT NULL,
    username character varying NOT NULL,
    mapset_name character varying NOT NULL,
    title character varying NOT NULL,
    context text
);


--
-- Name: usercontext_usercontext_id_seq; Type: SEQUENCE; Schema: DB_SCHEMA; Owner: -
--

CREATE SEQUENCE usercontext_usercontext_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usercontext_usercontext_id_seq; Type: SEQUENCE OWNED BY; Schema: DB_SCHEMA; Owner: -
--

ALTER SEQUENCE usercontext_usercontext_id_seq OWNED BY usercontext.usercontext_id;


--
-- Name: users; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE users (
    username character varying NOT NULL,
    pwd character varying,
    enc_pwd character varying,
    data_creazione date,
    data_scadenza date,
    data_modifica date,
    attivato smallint DEFAULT 1 NOT NULL,
    ultimo_accesso timestamp without time zone,
    cognome character varying,
    nome character varying,
    macaddress character varying,
    ip character varying,
    host character varying,
    controllo character varying,
    userdata character varying,
    email character varying
);


--
-- Name: users_options; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE users_options (
    users_options_id integer NOT NULL,
    username character varying NOT NULL,
    option_key character varying NOT NULL,
    option_value character varying NOT NULL
);


--
-- Name: users_options_users_options_id_seq; Type: SEQUENCE; Schema: DB_SCHEMA; Owner: -
--

CREATE SEQUENCE users_options_users_options_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_options_users_options_id_seq; Type: SEQUENCE OWNED BY; Schema: DB_SCHEMA; Owner: -
--

ALTER SEQUENCE users_options_users_options_id_seq OWNED BY users_options.users_options_id;


--
-- Name: version; Type: TABLE; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE TABLE version (
    version_id integer NOT NULL,
    version_name character varying NOT NULL,
    version_date date NOT NULL,
    version_key character varying NOT NULL
);


--
-- Name: version_version_id_seq; Type: SEQUENCE; Schema: DB_SCHEMA; Owner: -
--

CREATE SEQUENCE version_version_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: version_version_id_seq; Type: SEQUENCE OWNED BY; Schema: DB_SCHEMA; Owner: -
--

ALTER SEQUENCE version_version_id_seq OWNED BY version.version_id;


--
-- Name: vista_catalog; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW vista_catalog AS
    SELECT c.catalog_id, c.catalog_name, c.project_name, c.connection_type, c.catalog_path, c.catalog_url, c.catalog_description, c.files_path, CASE WHEN (c.connection_type <> 6) THEN '(i) Controllo non possibile: connessione non PostGIS'::text WHEN ("substring"((c.catalog_path)::text, 0, "position"((c.catalog_path)::text, '/'::text)) <> (current_database())::text) THEN '(i) Controllo non possibile: DB diverso'::text WHEN (NOT ("substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text)) IN (SELECT tables.table_schema FROM information_schema.tables))) THEN '(!) Lo schema configurato non esiste'::text ELSE 'OK'::text END AS catalog_control FROM catalog c;


--
-- Name: vista_class; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW vista_class AS
    SELECT c.class_id, c.layer_id, c.class_name, c.class_title, c.class_text, c.expression, c.maxscale, c.minscale, c.class_template, c.class_order, c.legendtype_id, c.symbol_ttf_name, c.label_font, c.label_angle, c.label_color, c.label_outlinecolor, c.label_bgcolor, c.label_size, c.label_minsize, c.label_maxsize, c.label_position, c.label_antialias, c.label_free, c.label_priority, c.label_wrap, c.label_buffer, c.label_force, c.label_def, c.locked, c.class_image, c.keyimage, CASE WHEN ((c.expression IS NULL) AND (c.class_order <= (SELECT max(class.class_order) AS max FROM class WHERE (((class.layer_id = c.layer_id) AND (class.class_id <> c.class_id)) AND (class.expression IS NOT NULL))))) THEN '(!) Classe con espressione vuota, spostare in fondo'::text WHEN ((c.legendtype_id = 1) AND (NOT (c.class_id IN (SELECT style.class_id FROM style)))) THEN '(!) Mostra in legenda ma nessuno stile presente'::text WHEN (((((c.label_font IS NOT NULL) AND (c.label_color IS NOT NULL)) AND (c.label_size IS NOT NULL)) AND (c.label_position IS NOT NULL)) AND (l.labelitem IS NULL)) THEN '(!) Etichetta configurata correttamente, ma nessun campo etichetta configurato sul layer'::text WHEN (((((c.label_font IS NOT NULL) AND (c.label_color IS NOT NULL)) AND (c.label_size IS NOT NULL)) AND (c.label_position IS NOT NULL)) AND (l.labelitem IS NOT NULL)) THEN 'OK. (i) Con etichetta'::text ELSE 'OK'::text END AS class_control FROM (class c JOIN layer l USING (layer_id));


--
-- Name: vista_group_authfilter; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW vista_group_authfilter AS
    SELECT af.filter_id, af.filter_name, gaf.filter_expression, gaf.groupname FROM (authfilter af JOIN group_authfilter gaf USING (filter_id)) ORDER BY af.filter_name;


--
-- Name: vista_layer; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW vista_layer AS
    SELECT l.layer_id, l.layergroup_id, l.layer_name, l.layertype_id, l.catalog_id, l.data, l.data_geom, l.data_unique, l.data_srid, l.data_filter, l.classitem, l.labelitem, l.labelsizeitem, l.labelminscale, l.labelmaxscale, l.maxscale, l.minscale, l.symbolscale, l.opacity, l.maxfeatures, l.sizeunits_id, l.layer_def, l.metadata, l.template, l.header, l.footer, l.tolerance, l.layer_order, l.queryable, l.layer_title, l.zoom_buffer, l.group_object, l.selection_color, l.papersize_id, l.toleranceunits_id, l.selection_width, l.selection_info, l.hidden, l.private, l.postlabelcache, l.maxvectfeatures, l.data_type, l.last_update, l.data_extent, l.searchable, l.hide_vector_geom, CASE WHEN (((l.queryable = (1)::numeric) AND (l.hidden = (0)::numeric)) AND (l.layer_id IN (SELECT qtfield.layer_id FROM qtfield WHERE (qtfield.resultype_id <> 4)))) THEN 'SI. Config. OK'::text WHEN (((l.queryable = (1)::numeric) AND (l.hidden = (1)::numeric)) AND (l.layer_id IN (SELECT qtfield.layer_id FROM qtfield WHERE (qtfield.resultype_id <> 4)))) THEN 'SI. Ma è nascosto'::text WHEN ((l.queryable = (1)::numeric) AND (l.layer_id IN (SELECT qtfield.layer_id FROM qtfield WHERE (qtfield.resultype_id = 4)))) THEN 'NO. Nessun campo nei risultati'::text ELSE 'NO. WFS non abilitato'::text END AS is_queryable, CASE WHEN ((l.queryable = (1)::numeric) AND (l.layer_id IN (SELECT qtfield.layer_id FROM qtfield WHERE (qtfield.editable = (1)::numeric)))) THEN 'SI. Config. OK'::text WHEN ((l.queryable = (1)::numeric) AND (l.layer_id IN (SELECT qtfield.layer_id FROM qtfield WHERE (qtfield.editable = (0)::numeric)))) THEN 'NO. Nessun campo è editabile'::text WHEN ((l.queryable = (0)::numeric) AND (l.layer_id IN (SELECT qtfield.layer_id FROM qtfield WHERE (qtfield.editable = (1)::numeric)))) THEN 'NO. Esiste un campo editabile ma il WFS non è attivo'::text ELSE 'NO.'::text END AS is_editable, CASE WHEN (c.connection_type <> 6) THEN '(i) Controllo non possibile: connessione non PostGIS'::text WHEN ("substring"((c.catalog_path)::text, 0, "position"((c.catalog_path)::text, '/'::text)) <> (current_database())::text) THEN '(i) Controllo non possibile: DB diverso'::text WHEN (NOT ((l.data)::text IN (SELECT tables.table_name FROM information_schema.tables WHERE ((tables.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text)))))) THEN '(!) La tabella non esiste nel DB'::text WHEN (NOT ((l.data_geom)::text IN (SELECT columns.column_name FROM information_schema.columns WHERE ((((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (l.data)::text)) AND ((columns.data_type)::text = 'USER-DEFINED'::text))))) THEN '(!) Il campo geometrico del layer non esiste'::text WHEN (NOT ((l.data_unique)::text IN (SELECT columns.column_name FROM information_schema.columns WHERE (((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (l.data)::text))))) THEN '(!) Il campo chiave del layer non esiste'::text WHEN (NOT (l.data_srid IN (SELECT geometry_columns.srid FROM public.geometry_columns WHERE (((geometry_columns.f_table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((geometry_columns.f_table_name)::text = (l.data)::text))))) THEN '(!) Lo SRID configurato non è quello corretto'::text WHEN (NOT (upper((l.data_type)::text) IN (SELECT geometry_columns.type FROM public.geometry_columns WHERE (((geometry_columns.f_table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((geometry_columns.f_table_name)::text = (l.data)::text))))) THEN '(!) Geometrytype non corretto'::text WHEN (NOT ((l.labelitem)::text IN (SELECT columns.column_name FROM information_schema.columns WHERE (((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (l.data)::text))))) THEN '(!) Il campo etichetta del layer non esiste'::text WHEN (NOT ((l.labelitem)::text IN (SELECT qtfield.qtfield_name FROM qtfield WHERE (qtfield.layer_id = l.layer_id)))) THEN '(!) Campo etichetta non presente nei campi del layer'::text WHEN (NOT ((l.labelsizeitem)::text IN (SELECT columns.column_name FROM information_schema.columns WHERE (((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (l.data)::text))))) THEN '(!) Il campo altezza etichetta del layer non esiste'::text WHEN (NOT ((l.labelsizeitem)::text IN (SELECT qtfield.qtfield_name FROM qtfield WHERE (qtfield.layer_id = l.layer_id)))) THEN '(!) Campo altezza etichetta non presente nei campi del layer'::text WHEN ((l.layer_name)::text IN (SELECT DISTINCT layer.layer_name FROM layer WHERE ((layer.layergroup_id <> lg.layergroup_id) AND (layer.catalog_id IN (SELECT catalog.catalog_id FROM catalog WHERE ((catalog.project_name)::text = (c.project_name)::text)))))) THEN '(!) Combinazione nome layergroup + nome layer non univoca. Cambiare nome al layer o al layergroup'::text WHEN (NOT (l.layer_id IN (SELECT class.layer_id FROM class))) THEN 'OK (i) Non ci sono classi configurate in questo layer'::text ELSE 'OK'::text END AS layer_control FROM ((((layer l JOIN catalog c USING (catalog_id)) JOIN e_layertype USING (layertype_id)) JOIN layergroup lg USING (layergroup_id)) JOIN theme t USING (theme_id));


--
-- Name: vista_layergroup; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW vista_layergroup AS
    SELECT lg.layergroup_id, lg.theme_id, lg.layergroup_name, lg.layergroup_title, lg.layergroup_maxscale, lg.layergroup_minscale, lg.layergroup_smbscale, lg.layergroup_order, lg.locked, lg.multi, lg.hidden, lg.isbaselayer, lg.tiletype_id, lg.sld, lg.style, lg.url, lg.owstype_id, lg.outputformat_id, lg.layers, lg.parameters, lg.gutter, lg.transition, lg.tree_group, lg.layergroup_description, lg.buffer, lg.tiles_extent, lg.tiles_extent_srid, lg.layergroup_single, lg.metadata_url, lg.opacity, lg.tile_origin, lg.tile_resolutions, lg.tile_matrix_set, CASE WHEN ((lg.tiles_extent_srid IS NOT NULL) AND (NOT (lg.tiles_extent_srid IN (SELECT project_srs.srid FROM project_srs WHERE ((project_srs.project_name)::text = (t.project_name)::text))))) THEN '(!) SRID estensione tiles non presente nei sistemi di riferimento del progetto'::text WHEN ((lg.owstype_id = 6) AND (lg.url IS NULL)) THEN '(!) Nessuna URL configurata per la chiamata TMS'::text WHEN ((lg.owstype_id = 6) AND (lg.layers IS NULL)) THEN '(!) Nessun layer configurato per la chiamata TMS'::text WHEN ((lg.owstype_id = 9) AND (lg.url IS NULL)) THEN '(!) Nessuna URL configurata per la chiamata WMTS'::text WHEN ((lg.owstype_id = 9) AND (lg.layers IS NULL)) THEN '(!) Nessun layer configurato per la chiamata WMTS'::text WHEN ((lg.owstype_id = 9) AND (lg.tile_matrix_set IS NULL)) THEN '(!) Nessun Tile Matrix configurato per la chiamata WMTS'::text WHEN ((lg.owstype_id = 9) AND (lg.style IS NULL)) THEN '(!) Nessuno stile configurato per la chiamata WMTS'::text WHEN ((lg.owstype_id = 9) AND (lg.tile_origin IS NULL)) THEN '(!) Nessuna origine configurata per la chiamata WMTS'::text WHEN ((lg.opacity IS NULL) OR ((lg.opacity)::text = '0'::text)) THEN '(i) Attenzione: trasparenza totale'::text ELSE 'OK'::text END AS layergroup_control FROM (layergroup lg JOIN theme t USING (theme_id));


--
-- Name: vista_mapset; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW vista_mapset AS
    SELECT m.mapset_name, m.project_name, m.mapset_title, m.mapset_description, m.template, m.mapset_extent, m.page_size, m.filter_data, m.dl_image_res, m.imagelabel, m.bg_color, m.refmap_extent, m.test_extent, m.mapset_srid, m.mapset_def, m.mapset_group, m.private, m.sizeunits_id, m.static_reference, m.metadata, m.mapset_note, m.mask, m.maxscale, m.minscale, m.mapset_scales, m.displayprojection, CASE WHEN (NOT ((m.mapset_name)::text IN (SELECT mapset_layergroup.mapset_name FROM mapset_layergroup))) THEN '(!) Nessun layergroup presente'::text WHEN (75 <= (SELECT count(mapset_layergroup.layergroup_id) AS count FROM mapset_layergroup WHERE ((mapset_layergroup.mapset_name)::text = (m.mapset_name)::text) GROUP BY mapset_layergroup.mapset_name)) THEN '(!) Openlayers non consente di rappresentare più di 75 layergroup alla volta'::text WHEN (m.mapset_scales IS NULL) THEN '(!) Nessun elenco di scale configurato'::text WHEN (m.mapset_srid <> m.displayprojection) THEN '(i) Coordinate visualizzate diverse da quelle di mappa'::text WHEN (0 = (SELECT max(mapset_layergroup.refmap) AS max FROM mapset_layergroup WHERE ((mapset_layergroup.mapset_name)::text = (m.mapset_name)::text) GROUP BY mapset_layergroup.mapset_name)) THEN '(i) Nessuna reference map'::text ELSE 'OK'::text END AS mapset_control FROM mapset m;


--
-- Name: vista_project_languages; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW vista_project_languages AS
    SELECT project_languages.project_name, project_languages.language_id, e_language.language_name, e_language.language_order FROM (project_languages JOIN e_language ON ((project_languages.language_id = e_language.language_id))) ORDER BY e_language.language_order;


--
-- Name: vista_qtfield; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW vista_qtfield AS
    SELECT qtfield.qtfield_id, qtfield.layer_id, qtfield.fieldtype_id, x.qtrelation_id, qtfield.qtfield_name, qtfield.resultype_id, qtfield.field_header, qtfield.qtfield_order, COALESCE(qtfield.column_width, 0) AS column_width, x.name AS qtrelation_name, x.qtrelationtype_id, x.qtrelationtype_name, qtfield.editable, CASE WHEN (qtfield.qtrelation_id = 0) THEN CASE WHEN (c.connection_type <> 6) THEN '(i) Controllo non possibile: connessione non PostGIS'::text WHEN ("substring"((c.catalog_path)::text, 0, "position"((c.catalog_path)::text, '/'::text)) <> (current_database())::text) THEN '(i) Controllo non possibile: DB diverso'::text WHEN (NOT ((qtfield.qtfield_name)::text IN (SELECT columns.column_name FROM information_schema.columns WHERE (("substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text)) = (i.table_schema)::text) AND ((l.data)::text = (i.table_name)::text))))) THEN '(!) Il campo non esiste nella tabella'::text ELSE 'OK'::text END ELSE CASE WHEN (cr.connection_type <> 6) THEN '(i) Controllo non possibile: connessione non PostGIS'::text WHEN ("substring"((cr.catalog_path)::text, 0, "position"((cr.catalog_path)::text, '/'::text)) <> (current_database())::text) THEN '(i) Controllo non possibile: DB diverso'::text WHEN (NOT ((qtfield.qtfield_name)::text IN (SELECT columns.column_name FROM information_schema.columns WHERE (("substring"((cr.catalog_path)::text, ("position"((cr.catalog_path)::text, '/'::text) + 1), length((cr.catalog_path)::text)) = (i.table_schema)::text) AND ((r.table_name)::text = (i.table_name)::text))))) THEN ('(!) Il campo non esiste nella tabella di relazione: '::text || (r.qtrelation_name)::text) ELSE 'OK'::text END END AS qtfield_control FROM (((((((qtfield JOIN e_fieldtype USING (fieldtype_id)) JOIN (SELECT y.qtrelationtype_id, y.qtrelation_id, y.name, z.qtrelationtype_name FROM ((SELECT 0 AS qtrelation_id, 'Data Layer'::character varying AS name, 0 AS qtrelationtype_id UNION SELECT qtrelation.qtrelation_id, COALESCE(qtrelation.qtrelation_name, 'Nessuna Relazione'::character varying) AS name, qtrelation.qtrelationtype_id FROM qtrelation) y JOIN (SELECT 0 AS qtrelationtype_id, ''::character varying AS qtrelationtype_name UNION SELECT e_qtrelationtype.qtrelationtype_id, e_qtrelationtype.qtrelationtype_name FROM e_qtrelationtype) z USING (qtrelationtype_id))) x USING (qtrelation_id)) JOIN layer l USING (layer_id)) JOIN catalog c USING (catalog_id)) LEFT JOIN qtrelation r USING (qtrelation_id)) LEFT JOIN catalog cr ON ((cr.catalog_id = r.catalog_id))) LEFT JOIN information_schema.columns i ON (((((qtfield.qtfield_name)::text = (i.column_name)::text) AND ("substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text)) = (i.table_schema)::text)) AND (((l.data)::text = (i.table_name)::text) OR ((r.table_name)::text = (i.table_name)::text))))) ORDER BY qtfield.qtfield_id, x.qtrelation_id, x.qtrelationtype_id;


--
-- Name: vista_qtrelation; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW vista_qtrelation AS
    SELECT r.qtrelation_id, r.catalog_id, r.qtrelation_name, r.qtrelationtype_id, r.data_field_1, r.data_field_2, r.data_field_3, r.table_name, r.table_field_1, r.table_field_2, r.table_field_3, r.language_id, r.layer_id, CASE WHEN (c.connection_type <> 6) THEN '(i) Controllo non possibile: connessione non PostGIS'::text WHEN ("substring"((c.catalog_path)::text, 0, "position"((c.catalog_path)::text, '/'::text)) <> (current_database())::text) THEN '(i) Controllo non possibile: DB diverso'::text WHEN (NOT ((l.layer_name)::text IN (SELECT tables.table_name FROM information_schema.tables WHERE ((tables.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text)))))) THEN '(!) La tabella DB del layer non esiste'::text WHEN (NOT ((r.table_name)::text IN (SELECT tables.table_name FROM information_schema.tables WHERE ((tables.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text)))))) THEN '(!) tabella DB di JOIN non esiste'::text WHEN ((r.data_field_1 IS NULL) OR (r.table_field_1 IS NULL)) THEN '(!) Uno dei campi della JOIN 1 è vuoto'::text WHEN (NOT ((r.data_field_1)::text IN (SELECT columns.column_name FROM information_schema.columns WHERE (((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (l.layer_name)::text))))) THEN '(!) Il campo chiave layer non esiste'::text WHEN (NOT ((r.table_field_1)::text IN (SELECT columns.column_name FROM information_schema.columns WHERE (((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (r.table_name)::text))))) THEN '(!) Il campo chiave della relazione non esiste'::text WHEN ((r.data_field_2 IS NULL) AND (r.table_field_2 IS NULL)) THEN 'OK'::text WHEN ((r.data_field_2 IS NULL) OR (r.table_field_2 IS NULL)) THEN '(!) Uno dei campi della JOIN 2 è vuoto'::text WHEN (NOT ((r.data_field_2)::text IN (SELECT columns.column_name FROM information_schema.columns WHERE (((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (l.layer_name)::text))))) THEN '(!) Il campo chiave layer della JOIN 2 non esiste'::text WHEN (NOT ((r.table_field_2)::text IN (SELECT columns.column_name FROM information_schema.columns WHERE (((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (r.table_name)::text))))) THEN '(!) Il campo chiave relazione della JOIN 2 non esiste'::text WHEN ((r.data_field_3 IS NULL) AND (r.table_field_3 IS NULL)) THEN 'OK'::text WHEN ((r.data_field_3 IS NULL) OR (r.table_field_3 IS NULL)) THEN '(!) Uno dei campi della JOIN 3 è vuoto'::text WHEN (NOT ((r.data_field_3)::text IN (SELECT columns.column_name FROM information_schema.columns WHERE (((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (l.layer_name)::text))))) THEN '(!) Il campo chiave layer della JOIN 3 non esiste'::text WHEN (NOT ((r.table_field_3)::text IN (SELECT columns.column_name FROM information_schema.columns WHERE (((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (r.table_name)::text))))) THEN '(!) Il campo chiave relazione della JOIN 3 non esiste'::text ELSE 'OK'::text END AS qtrelation_control FROM (((qtrelation r JOIN catalog c USING (catalog_id)) JOIN layer l USING (layer_id)) JOIN e_qtrelationtype rt USING (qtrelationtype_id));


--
-- Name: vista_style; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW vista_style AS
    SELECT s.style_id, s.class_id, s.style_name, s.symbol_name, s.color, s.outlinecolor, s.bgcolor, s.angle, s.size, s.minsize, s.maxsize, s.width, s.maxwidth, s.minwidth, s.locked, s.style_def, s.style_order, s.pattern_id, CASE WHEN (NOT ((s.symbol_name)::text IN (SELECT symbol.symbol_name FROM symbol))) THEN '(!) Il simbolo non esiste'::text WHEN (s.style_order = (SELECT style.style_order FROM style WHERE ((style.style_id <> s.style_id) AND (style.class_id = s.class_id)))) THEN '(!) Due stili con lo stesso ordine'::text WHEN (((s.color IS NULL) AND (s.outlinecolor IS NULL)) AND (s.bgcolor IS NULL)) THEN '(!) Stile senza colore'::text WHEN ((s.symbol_name IS NOT NULL) AND (s.size IS NULL)) THEN '(!) Stile senza dimensione'::text ELSE 'OK'::text END AS style_control FROM (style s LEFT JOIN symbol USING (symbol_name)) ORDER BY s.style_order;


--
-- Name: vista_version; Type: VIEW; Schema: DB_SCHEMA; Owner: -
--

CREATE VIEW vista_version AS
    SELECT version.version_id, version.version_name, version.version_date FROM version WHERE ((version.version_key)::text = 'author'::text) ORDER BY version.version_id DESC LIMIT 1;


--
-- Name: al_id; Type: DEFAULT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY access_log ALTER COLUMN al_id SET DEFAULT nextval('access_log_al_id_seq'::regclass);


--
-- Name: pattern_id; Type: DEFAULT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY e_pattern ALTER COLUMN pattern_id SET DEFAULT nextval('e_pattern_pattern_id_seq'::regclass);


--
-- Name: i18nf_id; Type: DEFAULT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY i18n_field ALTER COLUMN i18nf_id SET DEFAULT nextval('i18n_field_i18nf_id_seq'::regclass);


--
-- Name: localization_id; Type: DEFAULT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY localization ALTER COLUMN localization_id SET DEFAULT nextval('localization_localization_id_seq'::regclass);


--
-- Name: usercontext_id; Type: DEFAULT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY usercontext ALTER COLUMN usercontext_id SET DEFAULT nextval('usercontext_usercontext_id_seq'::regclass);


--
-- Name: users_options_id; Type: DEFAULT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY users_options ALTER COLUMN users_options_id SET DEFAULT nextval('users_options_users_options_id_seq'::regclass);


--
-- Name: version_id; Type: DEFAULT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY version ALTER COLUMN version_id SET DEFAULT nextval('version_version_id_seq'::regclass);


--
-- Name: 18n_field_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY i18n_field
    ADD CONSTRAINT "18n_field_pkey" PRIMARY KEY (i18nf_id);


--
-- Name: access_log_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY access_log
    ADD CONSTRAINT access_log_pkey PRIMARY KEY (al_id);


--
-- Name: catalog_catalog_name_key; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY catalog
    ADD CONSTRAINT catalog_catalog_name_key UNIQUE (catalog_name, project_name);


--
-- Name: catalog_import_catalog_import_name_key; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY catalog_import
    ADD CONSTRAINT catalog_import_catalog_import_name_key UNIQUE (catalog_import_name, project_name);


--
-- Name: catalog_import_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY catalog_import
    ADD CONSTRAINT catalog_import_pkey PRIMARY KEY (catalog_import_id);


--
-- Name: catalog_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY catalog
    ADD CONSTRAINT catalog_pkey PRIMARY KEY (catalog_id);


--
-- Name: class_layer_id_key; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY class
    ADD CONSTRAINT class_layer_id_key UNIQUE (layer_id, class_name);


--
-- Name: class_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY class
    ADD CONSTRAINT class_pkey PRIMARY KEY (class_id);


--
-- Name: classgroup_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY classgroup
    ADD CONSTRAINT classgroup_pkey PRIMARY KEY (classgroup_id);


--
-- Name: e_charset_encodings_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_charset_encodings
    ADD CONSTRAINT e_charset_encodings_pkey PRIMARY KEY (charset_encodings_id);


--
-- Name: e_conntype_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_conntype
    ADD CONSTRAINT e_conntype_pkey PRIMARY KEY (conntype_id);


--
-- Name: e_datatype_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_datatype
    ADD CONSTRAINT e_datatype_pkey PRIMARY KEY (datatype_id);


--
-- Name: e_fieldformat_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_fieldformat
    ADD CONSTRAINT e_fieldformat_pkey PRIMARY KEY (fieldformat_id);


--
-- Name: e_fieldtype_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_fieldtype
    ADD CONSTRAINT e_fieldtype_pkey PRIMARY KEY (fieldtype_id);


--
-- Name: e_filetype_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_filetype
    ADD CONSTRAINT e_filetype_pkey PRIMARY KEY (filetype_id);


--
-- Name: e_form_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_form
    ADD CONSTRAINT e_form_pkey PRIMARY KEY (id);


--
-- Name: e_language_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_language
    ADD CONSTRAINT e_language_pkey PRIMARY KEY (language_id);


--
-- Name: e_layertype_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_layertype
    ADD CONSTRAINT e_layertype_pkey PRIMARY KEY (layertype_id);


--
-- Name: e_lblposition_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_lblposition
    ADD CONSTRAINT e_lblposition_pkey PRIMARY KEY (lblposition_id);


--
-- Name: e_legendtype_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_legendtype
    ADD CONSTRAINT e_legendtype_pkey PRIMARY KEY (legendtype_id);


--
-- Name: e_level_name_key; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_level
    ADD CONSTRAINT e_level_name_key UNIQUE (name);


--
-- Name: e_livelli_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_level
    ADD CONSTRAINT e_livelli_pkey PRIMARY KEY (id);


--
-- Name: e_orderby_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_orderby
    ADD CONSTRAINT e_orderby_pkey PRIMARY KEY (orderby_id);


--
-- Name: e_outputformat_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_outputformat
    ADD CONSTRAINT e_outputformat_pkey PRIMARY KEY (outputformat_id);


--
-- Name: e_owstype_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_owstype
    ADD CONSTRAINT e_owstype_pkey PRIMARY KEY (owstype_id);


--
-- Name: e_papersize_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_papersize
    ADD CONSTRAINT e_papersize_pkey PRIMARY KEY (papersize_id);


--
-- Name: e_pattern_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_pattern
    ADD CONSTRAINT e_pattern_pkey PRIMARY KEY (pattern_id);


--
-- Name: e_qtrelationtype_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_qtrelationtype
    ADD CONSTRAINT e_qtrelationtype_pkey PRIMARY KEY (qtrelationtype_id);


--
-- Name: e_resultype_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_resultype
    ADD CONSTRAINT e_resultype_pkey PRIMARY KEY (resultype_id);


--
-- Name: e_searchtype_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_searchtype
    ADD CONSTRAINT e_searchtype_pkey PRIMARY KEY (searchtype_id);


--
-- Name: e_sizeunits_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_sizeunits
    ADD CONSTRAINT e_sizeunits_pkey PRIMARY KEY (sizeunits_id);


--
-- Name: e_symbolcategory_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_symbolcategory
    ADD CONSTRAINT e_symbolcategory_pkey PRIMARY KEY (symbolcategory_id);


--
-- Name: e_tiletype_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY e_tiletype
    ADD CONSTRAINT e_tiletype_pkey PRIMARY KEY (tiletype_id);


--
-- Name: filter_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY authfilter
    ADD CONSTRAINT filter_pkey PRIMARY KEY (filter_id);


--
-- Name: font_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY font
    ADD CONSTRAINT font_pkey PRIMARY KEY (font_name);


--
-- Name: group_authfilter_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY group_authfilter
    ADD CONSTRAINT group_authfilter_pkey PRIMARY KEY (groupname, filter_id);


--
-- Name: groups_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY groups
    ADD CONSTRAINT groups_pkey PRIMARY KEY (groupname);


--
-- Name: layer_authfilter_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY layer_authfilter
    ADD CONSTRAINT layer_authfilter_pkey PRIMARY KEY (layer_id, filter_id);


--
-- Name: layer_groups_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY layer_groups
    ADD CONSTRAINT layer_groups_pkey PRIMARY KEY (layer_groups_id);


--
-- Name: layer_layergroup_id_key; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY layer
    ADD CONSTRAINT layer_layergroup_id_key UNIQUE (layergroup_id, layer_name);


--
-- Name: layer_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY layer
    ADD CONSTRAINT layer_pkey PRIMARY KEY (layer_id);


--
-- Name: layergroup_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY layergroup
    ADD CONSTRAINT layergroup_pkey PRIMARY KEY (layergroup_id);


--
-- Name: layergroup_theme_key; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY layergroup
    ADD CONSTRAINT layergroup_theme_key UNIQUE (theme_id, layergroup_name);


--
-- Name: link_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY link
    ADD CONSTRAINT link_pkey PRIMARY KEY (link_id);


--
-- Name: livelli_form_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY form_level
    ADD CONSTRAINT livelli_form_pkey PRIMARY KEY (id);


--
-- Name: localization_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY localization
    ADD CONSTRAINT localization_pkey PRIMARY KEY (localization_id);


--
-- Name: mapset_layergroup_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY mapset_layergroup
    ADD CONSTRAINT mapset_layergroup_pkey PRIMARY KEY (mapset_name, layergroup_id);


--
-- Name: mapset_mapset_name_key; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY mapset
    ADD CONSTRAINT mapset_mapset_name_key UNIQUE (mapset_name);


--
-- Name: mapset_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY mapset
    ADD CONSTRAINT mapset_pkey PRIMARY KEY (mapset_name);


--
-- Name: project_admin_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY project_admin
    ADD CONSTRAINT project_admin_pkey PRIMARY KEY (project_name, username);


--
-- Name: project_languages_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY project_languages
    ADD CONSTRAINT project_languages_pkey PRIMARY KEY (project_name, language_id);


--
-- Name: project_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY project
    ADD CONSTRAINT project_pkey PRIMARY KEY (project_name);


--
-- Name: project_srs_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY project_srs
    ADD CONSTRAINT project_srs_pkey PRIMARY KEY (project_name, srid);


--
-- Name: project_theme_id_key; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY theme
    ADD CONSTRAINT project_theme_id_key UNIQUE (project_name, theme_name);


--
-- Name: qt_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY qt
    ADD CONSTRAINT qt_pkey PRIMARY KEY (qt_id);


--
-- Name: qt_theme_key; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY qt
    ADD CONSTRAINT qt_theme_key UNIQUE (theme_id, qt_name);


--
-- Name: qtfield_groups_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY qtfield_groups
    ADD CONSTRAINT qtfield_groups_pkey PRIMARY KEY (qtfield_id, groupname);


--
-- Name: qtfield_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY qtfield
    ADD CONSTRAINT qtfield_pkey PRIMARY KEY (qtfield_id);


--
-- Name: qtfield_qtfield_name_layer_id_key; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY qtfield
    ADD CONSTRAINT qtfield_qtfield_name_layer_id_key UNIQUE (qtfield_name, layer_id);


--
-- Name: qtfield_unique_key; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY qtfield
    ADD CONSTRAINT qtfield_unique_key UNIQUE (layer_id, field_header);


--
-- Name: qtlink_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY qtlink
    ADD CONSTRAINT qtlink_pkey PRIMARY KEY (layer_id, link_id);


--
-- Name: qtrelation_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY qtrelation
    ADD CONSTRAINT qtrelation_pkey PRIMARY KEY (qtrelation_id);


--
-- Name: selgroup_layer_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY selgroup_layer
    ADD CONSTRAINT selgroup_layer_pkey PRIMARY KEY (layer_id, selgroup_id);


--
-- Name: selgroup_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY selgroup
    ADD CONSTRAINT selgroup_pkey PRIMARY KEY (selgroup_id);


--
-- Name: selgroup_selgroup_name_key; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY selgroup
    ADD CONSTRAINT selgroup_selgroup_name_key UNIQUE (selgroup_name, project_name);


--
-- Name: service_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY tb_logs
    ADD CONSTRAINT service_pkey PRIMARY KEY (tb_logs_id);


--
-- Name: style_class_id_key; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY style
    ADD CONSTRAINT style_class_id_key UNIQUE (class_id, style_name);


--
-- Name: style_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY style
    ADD CONSTRAINT style_pkey PRIMARY KEY (style_id);


--
-- Name: symbol_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY symbol
    ADD CONSTRAINT symbol_pkey PRIMARY KEY (symbol_name);


--
-- Name: symbol_ttf_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY symbol_ttf
    ADD CONSTRAINT symbol_ttf_pkey PRIMARY KEY (symbol_ttf_name, font_name);


--
-- Name: tb_import_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY tb_import
    ADD CONSTRAINT tb_import_pkey PRIMARY KEY (tb_import_id);


--
-- Name: tb_import_table_id_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY tb_import_table
    ADD CONSTRAINT tb_import_table_id_pkey PRIMARY KEY (tb_import_table_id);


--
-- Name: theme_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY theme
    ADD CONSTRAINT theme_pkey PRIMARY KEY (theme_id);


--
-- Name: theme_version_idx; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY theme_version
    ADD CONSTRAINT theme_version_idx PRIMARY KEY (theme_id);


--
-- Name: user_group_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY user_group
    ADD CONSTRAINT user_group_pkey PRIMARY KEY (username, groupname);


--
-- Name: user_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY users
    ADD CONSTRAINT user_pkey PRIMARY KEY (username);


--
-- Name: usercontext_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY usercontext
    ADD CONSTRAINT usercontext_pkey PRIMARY KEY (usercontext_id);


--
-- Name: users_options_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY users_options
    ADD CONSTRAINT users_options_pkey PRIMARY KEY (users_options_id);


--
-- Name: version_pkey; Type: CONSTRAINT; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

ALTER TABLE ONLY version
    ADD CONSTRAINT version_pkey PRIMARY KEY (version_id);


--
-- Name: fki_; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_ ON qtrelation USING btree (layer_id);


--
-- Name: fki_catalog_conntype_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_catalog_conntype_fkey ON catalog USING btree (connection_type);


--
-- Name: fki_catalog_import_from_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_catalog_import_from_fkey ON catalog_import USING btree (catalog_from);


--
-- Name: fki_catalog_import_project_name_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_catalog_import_project_name_fkey ON catalog_import USING btree (project_name);


--
-- Name: fki_catalog_import_to_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_catalog_import_to_fkey ON catalog_import USING btree (catalog_to);


--
-- Name: fki_catalog_project_name_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_catalog_project_name_fkey ON catalog USING btree (project_name);


--
-- Name: fki_class_layer_id_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_class_layer_id_fkey ON class USING btree (layer_id);


--
-- Name: fki_layer_id; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_layer_id ON layer_groups USING btree (layer_id);


--
-- Name: fki_layer_layergroup_id; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_layer_layergroup_id ON layer USING btree (layergroup_id);


--
-- Name: fki_layergroup_theme_id; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_layergroup_theme_id ON layergroup USING btree (theme_id);


--
-- Name: fki_link_project_name_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_link_project_name_fkey ON link USING btree (project_name);


--
-- Name: fki_mapset_layergroup_layergroup_id_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_mapset_layergroup_layergroup_id_fkey ON mapset_layergroup USING btree (layergroup_id);


--
-- Name: fki_mapset_layergroup_mapset_name_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_mapset_layergroup_mapset_name_fkey ON mapset_layergroup USING btree (mapset_name);


--
-- Name: fki_mapset_project_name_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_mapset_project_name_fkey ON mapset USING btree (project_name);


--
-- Name: fki_pattern_id_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_pattern_id_fkey ON style USING btree (pattern_id);


--
-- Name: fki_project_theme_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_project_theme_fkey ON theme USING btree (project_name);


--
-- Name: fki_qt_layer_id_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_qt_layer_id_fkey ON qt USING btree (layer_id);


--
-- Name: fki_qt_link_link_id_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_qt_link_link_id_fkey ON qtlink USING btree (link_id);


--
-- Name: fki_qt_theme_id_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_qt_theme_id_fkey ON qt USING btree (theme_id);


--
-- Name: fki_qtfield_fieldtype_id_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_qtfield_fieldtype_id_fkey ON qtfield USING btree (fieldtype_id);


--
-- Name: fki_qtfields_layer; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_qtfields_layer ON qtfield USING btree (layer_id);


--
-- Name: fki_qtrelation_catalog_id_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_qtrelation_catalog_id_fkey ON qtrelation USING btree (catalog_id);


--
-- Name: fki_selgroup_project_name_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_selgroup_project_name_fkey ON selgroup USING btree (project_name);


--
-- Name: fki_style_class_id_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_style_class_id_fkey ON style USING btree (class_id);


--
-- Name: fki_symbol_icontype_id_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_symbol_icontype_id_fkey ON symbol USING btree (icontype);


--
-- Name: fki_symbol_symbolcategory_id_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_symbol_symbolcategory_id_fkey ON symbol USING btree (symbolcategory_id);


--
-- Name: fki_symbol_ttf_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_symbol_ttf_fkey ON class USING btree (symbol_ttf_name, label_font);


--
-- Name: fki_symbol_ttf_font_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_symbol_ttf_font_fkey ON symbol_ttf USING btree (font_name);


--
-- Name: fki_symbol_ttf_symbolcategory_id_fkey; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE INDEX fki_symbol_ttf_symbolcategory_id_fkey ON symbol_ttf USING btree (symbolcategory_id);


--
-- Name: qtfield_name_unique; Type: INDEX; Schema: DB_SCHEMA; Owner: -; Tablespace: 
--

CREATE UNIQUE INDEX qtfield_name_unique ON qtfield USING btree (layer_id, qtfield_name);


--
-- Name: chk_catalog; Type: TRIGGER; Schema: DB_SCHEMA; Owner: -
--

CREATE TRIGGER chk_catalog BEFORE INSERT OR UPDATE ON catalog FOR EACH ROW EXECUTE PROCEDURE check_catalog();


--
-- Name: chk_class; Type: TRIGGER; Schema: DB_SCHEMA; Owner: -
--

CREATE TRIGGER chk_class BEFORE INSERT OR UPDATE ON class FOR EACH ROW EXECUTE PROCEDURE check_class();


--
-- Name: delete_qtfields_qt; Type: TRIGGER; Schema: DB_SCHEMA; Owner: -
--

CREATE TRIGGER delete_qtfields_qt AFTER DELETE ON qt FOR EACH ROW EXECUTE PROCEDURE delete_qt();


--
-- Name: delete_qtrelation; Type: TRIGGER; Schema: DB_SCHEMA; Owner: -
--

CREATE TRIGGER delete_qtrelation AFTER DELETE ON qtrelation FOR EACH ROW EXECUTE PROCEDURE delete_qtrelation();


--
-- Name: depth; Type: TRIGGER; Schema: DB_SCHEMA; Owner: -
--

CREATE TRIGGER depth AFTER INSERT OR UPDATE ON e_level FOR EACH ROW EXECUTE PROCEDURE set_depth();


--
-- Name: layername; Type: TRIGGER; Schema: DB_SCHEMA; Owner: -
--

CREATE TRIGGER layername BEFORE INSERT OR UPDATE ON layer_groups FOR EACH ROW EXECUTE PROCEDURE set_layer_name();


--
-- Name: leaf; Type: TRIGGER; Schema: DB_SCHEMA; Owner: -
--

CREATE TRIGGER leaf AFTER INSERT OR UPDATE ON e_level FOR EACH ROW EXECUTE PROCEDURE set_leaf();


--
-- Name: move_layergroup; Type: TRIGGER; Schema: DB_SCHEMA; Owner: -
--

CREATE TRIGGER move_layergroup AFTER UPDATE ON layergroup FOR EACH ROW EXECUTE PROCEDURE move_layergroup();


--
-- Name: set_encpwd; Type: TRIGGER; Schema: DB_SCHEMA; Owner: -
--

CREATE TRIGGER set_encpwd BEFORE INSERT OR UPDATE ON users FOR EACH ROW EXECUTE PROCEDURE enc_pwd();


--
-- Name: theme_tr; Type: TRIGGER; Schema: DB_SCHEMA; Owner: -
--

CREATE TRIGGER theme_tr AFTER INSERT OR UPDATE ON theme FOR EACH ROW EXECUTE PROCEDURE theme_version_tr();


--
-- Name: catalog_conntype_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY catalog
    ADD CONSTRAINT catalog_conntype_fkey FOREIGN KEY (connection_type) REFERENCES e_conntype(conntype_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: catalog_import_project_name_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY catalog_import
    ADD CONSTRAINT catalog_import_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: catalog_project_name_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY catalog
    ADD CONSTRAINT catalog_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: class_layer_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY class
    ADD CONSTRAINT class_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES layer(layer_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: e_form_level_destination_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY e_form
    ADD CONSTRAINT e_form_level_destination_fkey FOREIGN KEY (level_destination) REFERENCES e_level(id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: e_level_parent_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY e_level
    ADD CONSTRAINT e_level_parent_id_fkey FOREIGN KEY (parent_id) REFERENCES e_level(id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: form_level_form_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY form_level
    ADD CONSTRAINT form_level_form_fkey FOREIGN KEY (form) REFERENCES e_form(id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: form_level_level_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY form_level
    ADD CONSTRAINT form_level_level_fkey FOREIGN KEY (level) REFERENCES e_level(id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: group_authfilter_filter_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY group_authfilter
    ADD CONSTRAINT group_authfilter_filter_id_fkey FOREIGN KEY (filter_id) REFERENCES authfilter(filter_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: group_authfilter_gropuname_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY group_authfilter
    ADD CONSTRAINT group_authfilter_gropuname_fkey FOREIGN KEY (groupname) REFERENCES groups(groupname) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: i18nfield_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY localization
    ADD CONSTRAINT i18nfield_fkey FOREIGN KEY (i18nf_id) REFERENCES i18n_field(i18nf_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: language_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY localization
    ADD CONSTRAINT language_id_fkey FOREIGN KEY (language_id) REFERENCES e_language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: language_id_project_name_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY project_languages
    ADD CONSTRAINT language_id_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: layer_authfilter_filter_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY layer_authfilter
    ADD CONSTRAINT layer_authfilter_filter_id_fkey FOREIGN KEY (filter_id) REFERENCES authfilter(filter_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: layer_authfilter_layer_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY layer_authfilter
    ADD CONSTRAINT layer_authfilter_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES layer(layer_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: layer_groups_layer_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY layer_groups
    ADD CONSTRAINT layer_groups_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES layer(layer_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: layer_layergroup_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY layer
    ADD CONSTRAINT layer_layergroup_id_fkey FOREIGN KEY (layergroup_id) REFERENCES layergroup(layergroup_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: layergroup_theme_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY layergroup
    ADD CONSTRAINT layergroup_theme_id_fkey FOREIGN KEY (theme_id) REFERENCES theme(theme_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: link_project_name_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY link
    ADD CONSTRAINT link_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: localization_project_name_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY localization
    ADD CONSTRAINT localization_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: mapset_layergroup_layergroup_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY mapset_layergroup
    ADD CONSTRAINT mapset_layergroup_layergroup_id_fkey FOREIGN KEY (layergroup_id) REFERENCES layergroup(layergroup_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: mapset_layergroup_mapset_name_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY mapset_layergroup
    ADD CONSTRAINT mapset_layergroup_mapset_name_fkey FOREIGN KEY (mapset_name) REFERENCES mapset(mapset_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: mapset_project_name_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY mapset
    ADD CONSTRAINT mapset_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: pattern_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY style
    ADD CONSTRAINT pattern_id_fkey FOREIGN KEY (pattern_id) REFERENCES e_pattern(pattern_id) ON UPDATE CASCADE;


--
-- Name: project_srs_project_name_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY project_srs
    ADD CONSTRAINT project_srs_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: qt_layer_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY qt
    ADD CONSTRAINT qt_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES layer(layer_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: qt_link_link_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY qtlink
    ADD CONSTRAINT qt_link_link_id_fkey FOREIGN KEY (link_id) REFERENCES link(link_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: qt_theme_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY qt
    ADD CONSTRAINT qt_theme_id_fkey FOREIGN KEY (theme_id) REFERENCES theme(theme_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: qtfield_fieldtype_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY qtfield
    ADD CONSTRAINT qtfield_fieldtype_id_fkey FOREIGN KEY (fieldtype_id) REFERENCES e_fieldtype(fieldtype_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: qtfield_groups_qtfield_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY qtfield_groups
    ADD CONSTRAINT qtfield_groups_qtfield_id_fkey FOREIGN KEY (qtfield_id) REFERENCES qtfield(qtfield_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: qtfield_layer_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY qtfield
    ADD CONSTRAINT qtfield_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES layer(layer_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: qtlink_layer_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY qtlink
    ADD CONSTRAINT qtlink_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES layer(layer_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: qtlink_link_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY qtlink
    ADD CONSTRAINT qtlink_link_id_fkey FOREIGN KEY (link_id) REFERENCES link(link_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: qtrelation_catalog_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY qtrelation
    ADD CONSTRAINT qtrelation_catalog_fkey FOREIGN KEY (catalog_id) REFERENCES catalog(catalog_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: qtrelation_layer_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY qtrelation
    ADD CONSTRAINT qtrelation_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES layer(layer_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: selgroup_layer_layer_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY selgroup_layer
    ADD CONSTRAINT selgroup_layer_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES layer(layer_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: selgroup_layer_selgroup_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY selgroup_layer
    ADD CONSTRAINT selgroup_layer_selgroup_fkey FOREIGN KEY (selgroup_id) REFERENCES selgroup(selgroup_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: selgroup_project_name_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY selgroup
    ADD CONSTRAINT selgroup_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: style_class_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY style
    ADD CONSTRAINT style_class_id_fkey FOREIGN KEY (class_id) REFERENCES class(class_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: symbol_symbolcategory_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY symbol
    ADD CONSTRAINT symbol_symbolcategory_id_fkey FOREIGN KEY (symbolcategory_id) REFERENCES e_symbolcategory(symbolcategory_id) MATCH FULL ON UPDATE CASCADE;


--
-- Name: symbol_ttf_font_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY symbol_ttf
    ADD CONSTRAINT symbol_ttf_font_fkey FOREIGN KEY (font_name) REFERENCES font(font_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: symbol_ttf_symbolcategory_id_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY symbol_ttf
    ADD CONSTRAINT symbol_ttf_symbolcategory_id_fkey FOREIGN KEY (symbolcategory_id) REFERENCES e_symbolcategory(symbolcategory_id) MATCH FULL ON UPDATE CASCADE;


--
-- Name: theme_project_name_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY theme
    ADD CONSTRAINT theme_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: theme_version_fk; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY theme_version
    ADD CONSTRAINT theme_version_fk FOREIGN KEY (theme_id) REFERENCES theme(theme_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: user_group_groupname_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY user_group
    ADD CONSTRAINT user_group_groupname_fkey FOREIGN KEY (groupname) REFERENCES groups(groupname) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: user_group_username_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY user_group
    ADD CONSTRAINT user_group_username_fkey FOREIGN KEY (username) REFERENCES users(username) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: username_project_name_fkey; Type: FK CONSTRAINT; Schema: DB_SCHEMA; Owner: -
--

ALTER TABLE ONLY project_admin
    ADD CONSTRAINT username_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

