--
-- PostgreSQL database dump
--

-- Dumped from database version 8.4.9
-- Dumped by pg_dump version 9.0.3
-- Started on 2011-12-20 13:38:44

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

--
-- TOC entry 19 (class 2615 OID 13242781)
-- Name: gisclient_31; Type: SCHEMA; Schema: -; Owner: gisclient
--

CREATE SCHEMA gisclient_31;


ALTER SCHEMA gisclient_31 OWNER TO gisclient;

SET search_path = gisclient_31, pg_catalog;

--
-- TOC entry 3647 (class 1247 OID 13242784)
-- Dependencies: 19 6682
-- Name: qt_selgroup_type; Type: TYPE; Schema: gisclient_31; Owner: gisclient
--

CREATE TYPE qt_selgroup_type AS (
	qt_selgroup_id integer,
	qt_id integer,
	selgroup_id integer,
	presente integer,
	project_id integer
);


ALTER TYPE gisclient_31.qt_selgroup_type OWNER TO gisclient;

--
-- TOC entry 3649 (class 1247 OID 13242787)
-- Dependencies: 19 6683
-- Name: slgrp_qt; Type: TYPE; Schema: gisclient_31; Owner: gisclient
--

CREATE TYPE slgrp_qt AS (
	qt_selgroup_id integer,
	presente integer,
	qt_id integer,
	selgroup_id integer,
	project_name character varying,
	qt_name character varying,
	selgroup_name character varying,
	qt_order smallint,
	theme_id integer,
	theme_title character varying
);


ALTER TYPE gisclient_31.slgrp_qt OWNER TO gisclient;

--
-- TOC entry 3651 (class 1247 OID 13242790)
-- Dependencies: 19 6684
-- Name: tree; Type: TYPE; Schema: gisclient_31; Owner: gisclient
--

CREATE TYPE tree AS (
	id integer,
	name character varying,
	lvl_id integer,
	lvl_name character varying
);


ALTER TYPE gisclient_31.tree OWNER TO gisclient;

--
-- TOC entry 1055 (class 1255 OID 13242791)
-- Dependencies: 4365 19
-- Name: check_catalog(); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
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


ALTER FUNCTION gisclient_31.check_catalog() OWNER TO gisclient;

--
-- TOC entry 1056 (class 1255 OID 13242792)
-- Dependencies: 4365 19
-- Name: check_class(); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
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


ALTER FUNCTION gisclient_31.check_class() OWNER TO gisclient;

--
-- TOC entry 1057 (class 1255 OID 13242793)
-- Dependencies: 4365 19
-- Name: check_layergroup(); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
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


ALTER FUNCTION gisclient_31.check_layergroup() OWNER TO gisclient;

--
-- TOC entry 1058 (class 1255 OID 13242794)
-- Dependencies: 4365 19
-- Name: check_mapset(); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
--

CREATE FUNCTION check_mapset() RETURNS trigger
    LANGUAGE plpgsql
    AS $_$
DECLARE
	ext varchar;
	presente integer;
BEGIN

	new.mapset_name:=regexp_replace(trim(new.mapset_name),'([\t ]+)','_','g');
	select coalesce(project_extent,'') into ext from gisclient_31.project where project_name=new.project_name;
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


ALTER FUNCTION gisclient_31.check_mapset() OWNER TO gisclient;

--
-- TOC entry 1059 (class 1255 OID 13242795)
-- Dependencies: 4365 19
-- Name: check_project(); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
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
	sk:='gisclient_31';	
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


ALTER FUNCTION gisclient_31.check_project() OWNER TO gisclient;

--
-- TOC entry 1060 (class 1255 OID 13242796)
-- Dependencies: 4365 19
-- Name: delete_qt(); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
--

CREATE FUNCTION delete_qt() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	delete from gisclient_31.qtfield where qt_id=old.qt_id;
	return old;
END
$$;


ALTER FUNCTION gisclient_31.delete_qt() OWNER TO gisclient;

--
-- TOC entry 1061 (class 1255 OID 13242797)
-- Dependencies: 4365 19
-- Name: delete_qtrelation(); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
--

CREATE FUNCTION delete_qtrelation() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	delete from gisclient_31.qtfield where qtrelation_id=old.qtrelation_id;
	return old;
END
$$;


ALTER FUNCTION gisclient_31.delete_qtrelation() OWNER TO gisclient;

--
-- TOC entry 1062 (class 1255 OID 13242798)
-- Dependencies: 4365 19
-- Name: enc_pwd(); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
--

CREATE FUNCTION enc_pwd() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	if (coalesce(new.pwd,'')<>'') then
		new.enc_pwd:=md5(new.pwd);
	end if;
	return new;
END
$$;


ALTER FUNCTION gisclient_31.enc_pwd() OWNER TO gisclient;

--
-- TOC entry 1063 (class 1255 OID 13242799)
-- Dependencies: 3651 4365 19
-- Name: gw_findtree(integer, character varying); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
--

CREATE FUNCTION gw_findtree(id integer, lvl character varying) RETURNS SETOF tree
    LANGUAGE plpgsql IMMUTABLE
    AS $$
DECLARE
	rec record;
	t gisclient_31.tree;
	i integer;
	d integer;
BEGIN
	select into d coalesce(depth,-1) from gisclient_31.e_level where name=lvl;
	if (d=-1) then
		raise exception 'Livello % non esistente',lvl;
	end if;
	for i in reverse d..1 loop
		return next t;
	end loop;
	
END
$$;


ALTER FUNCTION gisclient_31.gw_findtree(id integer, lvl character varying) OWNER TO gisclient;

--
-- TOC entry 1064 (class 1255 OID 13242800)
-- Dependencies: 4365 19
-- Name: move_layergroup(); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
--

CREATE FUNCTION move_layergroup() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	if(new.theme_id<>old.theme_id) then
		update gisclient_31.qt set theme_id=new.theme_id where qt_id in (select distinct qt_id from gisclient_31.qt inner join gisclient_31.layer using(layer_id) inner join gisclient_31.layergroup using(layergroup_id) where layergroup_id=new.layergroup_id);
	end if;
	return new;
END
$$;


ALTER FUNCTION gisclient_31.move_layergroup() OWNER TO gisclient;

--
-- TOC entry 1065 (class 1255 OID 13242801)
-- Dependencies: 4365 19
-- Name: new_pkey(character varying, character varying); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
--

CREATE FUNCTION new_pkey(tab character varying, id_fld character varying) RETURNS integer
    LANGUAGE plpgsql
    AS $$
declare
    newid integer;
	sk varchar;
	query varchar;
begin
	sk:='gisclient_31';
	query:='select '||sk||'.new_pkey('''||tab||''','''||id_fld||''',0)';
	execute query into newid;
	return newid;
end
$$;


ALTER FUNCTION gisclient_31.new_pkey(tab character varying, id_fld character varying) OWNER TO gisclient;

--
-- TOC entry 1053 (class 1255 OID 13242802)
-- Dependencies: 4365 19
-- Name: new_pkey(character varying, character varying, integer); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
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
	sk:='gisclient_31';
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


ALTER FUNCTION gisclient_31.new_pkey(tab character varying, id_fld character varying, st integer) OWNER TO gisclient;

--
-- TOC entry 1033 (class 1255 OID 13242803)
-- Dependencies: 4365 19
-- Name: new_pkey(character varying, character varying, character varying); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
--

CREATE FUNCTION new_pkey(sk character varying, tab character varying, id_fld character varying) RETURNS integer
    LANGUAGE plpgsql
    AS $$
declare
    newid integer;
begin
	select gisclient_31.new_pkey(sk ,tab,id_fld,0) into newid; 
	return newid;
end
$$;


ALTER FUNCTION gisclient_31.new_pkey(sk character varying, tab character varying, id_fld character varying) OWNER TO gisclient;

--
-- TOC entry 1034 (class 1255 OID 13242804)
-- Dependencies: 4365 19
-- Name: new_pkey(character varying, character varying, character varying, integer); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
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


ALTER FUNCTION gisclient_31.new_pkey(sk character varying, tab character varying, id_fld character varying, st integer) OWNER TO gisclient;

--
-- TOC entry 1066 (class 1255 OID 13242805)
-- Dependencies: 4365 19
-- Name: new_pkey_varchar(character varying, character varying, character varying); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
--

CREATE FUNCTION new_pkey_varchar(tb character varying, fld character varying, val character varying) RETURNS character varying
    LANGUAGE plpgsql IMMUTABLE
    AS $_$
DECLARE
	query text;
	presente integer;
	newval varchar;
BEGIN
query:='select count(*) from gisclient_31.'||tb||' where '||fld||'='''||val||''';';
execute query into presente;
if(presente>0) then
	query:='select map||(max(newindex)+1)::varchar from (select regexp_replace('||fld||',''([0-9]+)$'','''') as map,case when(regexp_replace('||fld||',''^([A-z_]+)'','''')='''') then 0 else regexp_replace('||fld||',''^([A-z_]+)'','''')::integer end as newindex from gisclient_31.'||tb||' where '''||val||''' ~* regexp_replace('||fld||',''([0-9]+)$'','''')) X group by map;';
	execute query into newval;
	return newval;
else
	return val;
end if;
END
$_$;


ALTER FUNCTION gisclient_31.new_pkey_varchar(tb character varying, fld character varying, val character varying) OWNER TO gisclient;

--
-- TOC entry 1067 (class 1255 OID 13242806)
-- Dependencies: 4365 19
-- Name: rm_project_groups(); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
--

CREATE FUNCTION rm_project_groups() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE

BEGIN
	delete from gisclient_31.mapset_groups where mapset_name in (select distinct mapset_name from gisclient_31.mapset where project_name=old.project_name) and group_name=old.group_name;
	return old;
END
$$;


ALTER FUNCTION gisclient_31.rm_project_groups() OWNER TO gisclient;

--
-- TOC entry 1068 (class 1255 OID 13242807)
-- Dependencies: 4365 19
-- Name: set_depth(); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
--

CREATE FUNCTION set_depth() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	if (TG_OP='INSERT') then
		update gisclient_31.e_level set depth=(select coalesce(depth+1,0) from gisclient_31.e_level where id=new.parent_id) where id=new.id;
	elseif(new.parent_id<>coalesce(old.parent_id,-1)) then
		update gisclient_31.e_level set depth=(select coalesce(depth+1,0) from gisclient_31.e_level where id=new.parent_id) where id=new.id;
	end if;
	return new;
END
$$;


ALTER FUNCTION gisclient_31.set_depth() OWNER TO gisclient;

--
-- TOC entry 1069 (class 1255 OID 13242808)
-- Dependencies: 4365 19
-- Name: set_layer_name(); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
--

CREATE FUNCTION set_layer_name() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	select into new.layer_name layer_name from gisclient_31.layer where layer_id=new.layer_id;
	return new;
END
$$;


ALTER FUNCTION gisclient_31.set_layer_name() OWNER TO gisclient;

--
-- TOC entry 1070 (class 1255 OID 13242809)
-- Dependencies: 4365 19
-- Name: set_leaf(); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
--

CREATE FUNCTION set_leaf() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	if(TG_OP='INSERT') then
		update gisclient_31.e_level X set leaf=(select case when (count(parent_id)>0) then 0 else 1 end from gisclient_31.e_level where parent_id=X.id);
	elsif (new.parent_id<> coalesce(old.parent_id,-1)) then
		update gisclient_31.e_level X set leaf=(select case when (count(parent_id)>0) then 0 else 1 end from gisclient_31.e_level where parent_id=X.id);
	end if;
	return new;
END
$$;


ALTER FUNCTION gisclient_31.set_leaf() OWNER TO gisclient;

--
-- TOC entry 1071 (class 1255 OID 13242810)
-- Dependencies: 4365 19
-- Name: set_map_extent(); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
--

CREATE FUNCTION set_map_extent() RETURNS trigger
    LANGUAGE plpgsql
    AS $_$
DECLARE
	ext varchar;
BEGIN

	new.mapset_name:=regexp_replace(trim(new.mapset_name),'([\t ]+)','_','g');
	select coalesce(project_extent,'') into ext from gisclient_31.project where project_name=new.project_name;
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


ALTER FUNCTION gisclient_31.set_map_extent() OWNER TO gisclient;

--
-- TOC entry 1072 (class 1255 OID 13242811)
-- Dependencies: 4365 19
-- Name: style_name(); Type: FUNCTION; Schema: gisclient_31; Owner: gisclient
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
		select into rec * from gisclient_31.symbol where symbol_name=new.symbol_name;
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
			SELECT INTO num count(*)+1 FROM gisclient_31.style WHERE class_id=new.class_id and style_name ~* 'Stile ([0-9]+)';
		end if;
		new.style_name:='Stile '||num::varchar;
	end if;
	return new;
END
$_$;


ALTER FUNCTION gisclient_31.style_name() OWNER TO gisclient;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- TOC entry 6685 (class 1259 OID 13242812)
-- Dependencies: 7462 19
-- Name: authfilter; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE authfilter (
    filter_id integer NOT NULL,
    filter_name character varying(100),
    filter_description text,
    filter_priority integer DEFAULT 0 NOT NULL
);


ALTER TABLE gisclient_31.authfilter OWNER TO gisclient;

--
-- TOC entry 6686 (class 1259 OID 13242819)
-- Dependencies: 19
-- Name: catalog; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_31.catalog OWNER TO gisclient;

--
-- TOC entry 6687 (class 1259 OID 13242825)
-- Dependencies: 19
-- Name: catalog_import; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE catalog_import (
    catalog_import_id integer NOT NULL,
    project_name character varying NOT NULL,
    catalog_import_name text,
    catalog_from integer NOT NULL,
    catalog_to integer NOT NULL,
    catalog_import_description text
);


ALTER TABLE gisclient_31.catalog_import OWNER TO gisclient;

--
-- TOC entry 6688 (class 1259 OID 13242831)
-- Dependencies: 7463 7464 7465 7466 7467 7468 19
-- Name: class; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_31.class OWNER TO gisclient;

--
-- TOC entry 6689 (class 1259 OID 13242843)
-- Dependencies: 7469 7470 7471 19
-- Name: classgroup; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_31.classgroup OWNER TO gisclient;

--
-- TOC entry 6690 (class 1259 OID 13242852)
-- Dependencies: 19
-- Name: e_charset_encodings; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_charset_encodings (
    charset_encodings_id integer NOT NULL,
    charset_encodings_name character varying NOT NULL,
    charset_encodings_order smallint
);


ALTER TABLE gisclient_31.e_charset_encodings OWNER TO gisclient;

--
-- TOC entry 6691 (class 1259 OID 13242858)
-- Dependencies: 19
-- Name: e_conntype; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_conntype (
    conntype_id smallint NOT NULL,
    conntype_name character varying NOT NULL,
    conntype_order smallint
);


ALTER TABLE gisclient_31.e_conntype OWNER TO gisclient;

--
-- TOC entry 6692 (class 1259 OID 13242864)
-- Dependencies: 19
-- Name: e_datatype; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_datatype (
    datatype_id smallint NOT NULL,
    datatype_name character varying NOT NULL,
    datatype_order smallint
);


ALTER TABLE gisclient_31.e_datatype OWNER TO gisclient;

--
-- TOC entry 6693 (class 1259 OID 13242870)
-- Dependencies: 19
-- Name: e_fieldformat; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_fieldformat (
    fieldformat_id integer NOT NULL,
    fieldformat_name character varying NOT NULL,
    fieldformat_format character varying NOT NULL,
    fieldformat_order smallint
);


ALTER TABLE gisclient_31.e_fieldformat OWNER TO gisclient;

--
-- TOC entry 6694 (class 1259 OID 13242876)
-- Dependencies: 19
-- Name: e_fieldtype; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_fieldtype (
    fieldtype_id smallint NOT NULL,
    fieldtype_name character varying NOT NULL,
    fieldtype_order smallint
);


ALTER TABLE gisclient_31.e_fieldtype OWNER TO gisclient;

--
-- TOC entry 6695 (class 1259 OID 13242882)
-- Dependencies: 19
-- Name: e_filetype; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_filetype (
    filetype_id smallint NOT NULL,
    filetype_name character varying NOT NULL,
    filetype_order smallint
);


ALTER TABLE gisclient_31.e_filetype OWNER TO gisclient;

--
-- TOC entry 6696 (class 1259 OID 13242888)
-- Dependencies: 19
-- Name: e_form; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_31.e_form OWNER TO gisclient;

--
-- TOC entry 6697 (class 1259 OID 13242894)
-- Dependencies: 19
-- Name: e_language; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_language (
    language_id character(2) NOT NULL,
    language_name character varying NOT NULL,
    language_order integer
);


ALTER TABLE gisclient_31.e_language OWNER TO gisclient;

--
-- TOC entry 6698 (class 1259 OID 13242900)
-- Dependencies: 19
-- Name: e_layertype; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_layertype (
    layertype_id smallint NOT NULL,
    layertype_name character varying NOT NULL,
    layertype_ms smallint,
    layertype_order smallint
);


ALTER TABLE gisclient_31.e_layertype OWNER TO gisclient;

--
-- TOC entry 6699 (class 1259 OID 13242906)
-- Dependencies: 19
-- Name: e_lblposition; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_lblposition (
    lblposition_id integer NOT NULL,
    lblposition_name character varying NOT NULL,
    lblposition_order smallint
);


ALTER TABLE gisclient_31.e_lblposition OWNER TO gisclient;

--
-- TOC entry 6700 (class 1259 OID 13242912)
-- Dependencies: 19
-- Name: e_legendtype; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_legendtype (
    legendtype_id smallint NOT NULL,
    legendtype_name character varying NOT NULL,
    legendtype_order smallint
);


ALTER TABLE gisclient_31.e_legendtype OWNER TO gisclient;

--
-- TOC entry 6701 (class 1259 OID 13242918)
-- Dependencies: 7472 7473 19
-- Name: e_level; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_31.e_level OWNER TO gisclient;

--
-- TOC entry 6702 (class 1259 OID 13242926)
-- Dependencies: 19
-- Name: e_orderby; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_orderby (
    orderby_id smallint NOT NULL,
    orderby_name character varying NOT NULL,
    orderby_order smallint
);


ALTER TABLE gisclient_31.e_orderby OWNER TO gisclient;

--
-- TOC entry 6703 (class 1259 OID 13242932)
-- Dependencies: 19
-- Name: e_outputformat; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_31.e_outputformat OWNER TO gisclient;

--
-- TOC entry 6704 (class 1259 OID 13242938)
-- Dependencies: 19
-- Name: e_owstype; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_owstype (
    owstype_id smallint NOT NULL,
    owstype_name character varying NOT NULL,
    owstype_order smallint
);


ALTER TABLE gisclient_31.e_owstype OWNER TO gisclient;

--
-- TOC entry 6705 (class 1259 OID 13242944)
-- Dependencies: 19
-- Name: e_papersize; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_papersize (
    papersize_id integer NOT NULL,
    papersize_name character varying NOT NULL,
    papersize_size character varying NOT NULL,
    papersize_orientation character varying,
    papaersize_order smallint
);


ALTER TABLE gisclient_31.e_papersize OWNER TO gisclient;

--
-- TOC entry 6706 (class 1259 OID 13242950)
-- Dependencies: 19
-- Name: e_qtrelationtype; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_qtrelationtype (
    qtrelationtype_id integer NOT NULL,
    qtrelationtype_name character varying NOT NULL,
    qtrelationtype_order smallint
);


ALTER TABLE gisclient_31.e_qtrelationtype OWNER TO gisclient;

--
-- TOC entry 6707 (class 1259 OID 13242956)
-- Dependencies: 19
-- Name: e_resultype; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_resultype (
    resultype_id smallint NOT NULL,
    resultype_name character varying NOT NULL,
    resultype_order smallint
);


ALTER TABLE gisclient_31.e_resultype OWNER TO gisclient;

--
-- TOC entry 6708 (class 1259 OID 13242962)
-- Dependencies: 19
-- Name: e_searchtype; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_searchtype (
    searchtype_id smallint NOT NULL,
    searchtype_name character varying NOT NULL,
    searchtype_order smallint
);


ALTER TABLE gisclient_31.e_searchtype OWNER TO gisclient;

--
-- TOC entry 6709 (class 1259 OID 13242968)
-- Dependencies: 19
-- Name: e_sizeunits; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_sizeunits (
    sizeunits_id smallint NOT NULL,
    sizeunits_name character varying NOT NULL,
    sizeunits_order smallint
);


ALTER TABLE gisclient_31.e_sizeunits OWNER TO gisclient;

--
-- TOC entry 6710 (class 1259 OID 13242974)
-- Dependencies: 19
-- Name: e_symbolcategory; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_symbolcategory (
    symbolcategory_id smallint NOT NULL,
    symbolcategory_name character varying NOT NULL,
    symbolcategory_order smallint
);


ALTER TABLE gisclient_31.e_symbolcategory OWNER TO gisclient;

--
-- TOC entry 6711 (class 1259 OID 13242980)
-- Dependencies: 19
-- Name: e_tiletype; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_tiletype (
    tiletype_id smallint NOT NULL,
    tiletype_name character varying NOT NULL,
    tiletype_order smallint
);


ALTER TABLE gisclient_31.e_tiletype OWNER TO gisclient;

--
-- TOC entry 6712 (class 1259 OID 13242986)
-- Dependencies: 7474 19
-- Name: form_level; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE form_level (
    id integer NOT NULL,
    level integer,
    mode integer,
    form integer,
    order_fld integer,
    visible smallint DEFAULT 1
);


ALTER TABLE gisclient_31.form_level OWNER TO gisclient;

--
-- TOC entry 6713 (class 1259 OID 13242990)
-- Dependencies: 7220 19
-- Name: elenco_form; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW elenco_form AS
    SELECT form_level.id AS "ID", form_level.mode, CASE WHEN (form_level.mode = 2) THEN 'New'::text WHEN (form_level.mode = 3) THEN 'Elenco'::text WHEN (form_level.mode = 0) THEN 'View'::text WHEN (form_level.mode = 1) THEN 'Edit'::text ELSE 'Non definito'::text END AS "Modo Visualizzazione Pagina", e_form.id AS "Form ID", e_form.name AS "Nome Form", e_form.tab_type AS "Tipo Tabella", x.name AS "Livello Destinazione", e_level.name AS "Livello Visualizzazione", CASE WHEN (COALESCE((e_level.depth)::integer, (-1)) = (-1)) THEN 0 ELSE (e_level.depth + 1) END AS "Profondita Albero", form_level.order_fld AS "Ordine Visualizzazione", CASE WHEN (form_level.visible = 1) THEN 'SI'::text ELSE 'NO'::text END AS "Visibile" FROM (((form_level JOIN e_level ON ((form_level.level = e_level.id))) JOIN e_form ON ((e_form.id = form_level.form))) JOIN e_level x ON ((x.id = e_form.level_destination))) ORDER BY CASE WHEN (COALESCE((e_level.depth)::integer, (-1)) = (-1)) THEN 0 ELSE (e_level.depth + 1) END, form_level.level, CASE WHEN (form_level.mode = 2) THEN 'Nuovo'::text WHEN ((form_level.mode = 0) OR (form_level.mode = 3)) THEN 'Elenco'::text WHEN (form_level.mode = 1) THEN 'View'::text ELSE 'Edit'::text END, form_level.order_fld;


ALTER TABLE gisclient_31.elenco_form OWNER TO gisclient;

--
-- TOC entry 6714 (class 1259 OID 13242995)
-- Dependencies: 19
-- Name: font; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE font (
    font_name character varying NOT NULL,
    file_name character varying NOT NULL
);


ALTER TABLE gisclient_31.font OWNER TO gisclient;

--
-- TOC entry 6715 (class 1259 OID 13243001)
-- Dependencies: 19
-- Name: group_authfilter; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE group_authfilter (
    groupname character varying NOT NULL,
    filter_id integer NOT NULL,
    filter_expression character varying
);


ALTER TABLE gisclient_31.group_authfilter OWNER TO gisclient;

--
-- TOC entry 6716 (class 1259 OID 13243007)
-- Dependencies: 19
-- Name: groups; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE groups (
    groupname character varying NOT NULL,
    description character varying
);


ALTER TABLE gisclient_31.groups OWNER TO gisclient;

--
-- TOC entry 6717 (class 1259 OID 13243013)
-- Dependencies: 19
-- Name: i18n_field; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE i18n_field (
    i18nf_id integer NOT NULL,
    table_name character varying(255),
    field_name character varying(255)
);


ALTER TABLE gisclient_31.i18n_field OWNER TO gisclient;

--
-- TOC entry 6718 (class 1259 OID 13243019)
-- Dependencies: 6717 19
-- Name: i18n_field_i18nf_id_seq; Type: SEQUENCE; Schema: gisclient_31; Owner: gisclient
--

CREATE SEQUENCE i18n_field_i18nf_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE gisclient_31.i18n_field_i18nf_id_seq OWNER TO gisclient;

--
-- TOC entry 7819 (class 0 OID 0)
-- Dependencies: 6718
-- Name: i18n_field_i18nf_id_seq; Type: SEQUENCE OWNED BY; Schema: gisclient_31; Owner: gisclient
--

ALTER SEQUENCE i18n_field_i18nf_id_seq OWNED BY i18n_field.i18nf_id;


--
-- TOC entry 7820 (class 0 OID 0)
-- Dependencies: 6718
-- Name: i18n_field_i18nf_id_seq; Type: SEQUENCE SET; Schema: gisclient_31; Owner: gisclient
--

SELECT pg_catalog.setval('i18n_field_i18nf_id_seq', 1, false);


--
-- TOC entry 6719 (class 1259 OID 13243021)
-- Dependencies: 7476 7477 7478 7479 7480 7481 7482 7483 7484 7485 7486 7487 19
-- Name: layer; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
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
    mapset_filter_ smallint DEFAULT 0,
    locked_ smallint DEFAULT 0,
    static_ integer DEFAULT 1,
    queryable numeric(1,0) DEFAULT 0,
    layer_title character varying,
    zoom_buffer numeric,
    group_object numeric(1,0),
    selection_color character varying,
    papersize_id numeric,
    toleranceunits_id numeric(1,0),
    visible numeric(1,0) DEFAULT 1,
    selection_width numeric(2,0),
    selection_info numeric(1,0) DEFAULT 1,
    hidden numeric(1,0) DEFAULT 0,
    private numeric(1,0) DEFAULT 0,
    postlabelcache numeric(1,0) DEFAULT 1,
    maxvectfeatures integer,
    data_type character varying,
    last_update character varying,
    data_extent character varying,
    CONSTRAINT layer_layertype_id_check CHECK ((layertype_id > 0))
);


ALTER TABLE gisclient_31.layer OWNER TO gisclient;

--
-- TOC entry 6720 (class 1259 OID 13243039)
-- Dependencies: 7488 19
-- Name: layer_authfilter; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE layer_authfilter (
    layer_id integer NOT NULL,
    filter_id integer NOT NULL,
    required smallint DEFAULT 0
);


ALTER TABLE gisclient_31.layer_authfilter OWNER TO gisclient;

--
-- TOC entry 6721 (class 1259 OID 13243043)
-- Dependencies: 7489 7490 7491 19
-- Name: layer_groups; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE layer_groups (
    layer_id integer NOT NULL,
    groupname character varying NOT NULL,
    wms integer DEFAULT 0,
    wfs integer DEFAULT 0,
    wfst integer DEFAULT 0,
    layer_name character varying
);


ALTER TABLE gisclient_31.layer_groups OWNER TO gisclient;

--
-- TOC entry 6722 (class 1259 OID 13243052)
-- Dependencies: 7492 7493 7494 7495 7496 7497 7498 7499 7500 7501 19
-- Name: layergroup; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
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
    layer_default integer,
    layergroup_description character varying,
    buffer numeric(1,0),
    tiles_extent character varying,
    tiles_extent_srid integer,
    layergroup_single numeric(1,0) DEFAULT 1,
    CONSTRAINT layergroup_name_lower_case CHECK (((layergroup_name)::text = lower((layergroup_name)::text)))
);


ALTER TABLE gisclient_31.layergroup OWNER TO gisclient;

--
-- TOC entry 6723 (class 1259 OID 13243068)
-- Dependencies: 7502 7503 7504 19
-- Name: link; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_31.link OWNER TO gisclient;

--
-- TOC entry 6724 (class 1259 OID 13243077)
-- Dependencies: 19
-- Name: localization; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE localization (
    localization_id integer NOT NULL,
    project_name character varying NOT NULL,
    i18nf_id integer,
    pkey_id character varying NOT NULL,
    language_id character(2),
    value text
);


ALTER TABLE gisclient_31.localization OWNER TO gisclient;

--
-- TOC entry 6725 (class 1259 OID 13243083)
-- Dependencies: 6724 19
-- Name: localization_localization_id_seq; Type: SEQUENCE; Schema: gisclient_31; Owner: gisclient
--

CREATE SEQUENCE localization_localization_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE gisclient_31.localization_localization_id_seq OWNER TO gisclient;

--
-- TOC entry 7821 (class 0 OID 0)
-- Dependencies: 6725
-- Name: localization_localization_id_seq; Type: SEQUENCE OWNED BY; Schema: gisclient_31; Owner: gisclient
--

ALTER SEQUENCE localization_localization_id_seq OWNED BY localization.localization_id;


--
-- TOC entry 7822 (class 0 OID 0)
-- Dependencies: 6725
-- Name: localization_localization_id_seq; Type: SEQUENCE SET; Schema: gisclient_31; Owner: gisclient
--

SELECT pg_catalog.setval('localization_localization_id_seq', 1286, true);


--
-- TOC entry 6726 (class 1259 OID 13243085)
-- Dependencies: 7506 7507 7508 7509 7510 7511 19
-- Name: mapset; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
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
    sizeunits_id smallint DEFAULT 5,
    static_reference integer DEFAULT 0,
    metadata text,
    mapset_note text,
    mask character varying,
    maxscale integer,
    minscale integer,
    mapset_scales character varying,
    private integer DEFAULT 0,
    displayprojection integer
);


ALTER TABLE gisclient_31.mapset OWNER TO gisclient;

--
-- TOC entry 7823 (class 0 OID 0)
-- Dependencies: 6726
-- Name: COLUMN mapset.mapset_scales; Type: COMMENT; Schema: gisclient_31; Owner: gisclient
--

COMMENT ON COLUMN mapset.mapset_scales IS 'Possible scale list separated with comma';


--
-- TOC entry 6727 (class 1259 OID 13243097)
-- Dependencies: 7512 7513 7514 19
-- Name: mapset_layergroup; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE mapset_layergroup (
    mapset_name character varying NOT NULL,
    layergroup_id integer NOT NULL,
    status smallint DEFAULT 0,
    refmap smallint DEFAULT 0,
    hide smallint DEFAULT 0
);


ALTER TABLE gisclient_31.mapset_layergroup OWNER TO gisclient;

--
-- TOC entry 6728 (class 1259 OID 13243106)
-- Dependencies: 7515 7516 7517 7518 7519 7520 7521 7522 7523 7524 19
-- Name: project; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_31.project OWNER TO gisclient;

--
-- TOC entry 6729 (class 1259 OID 13243122)
-- Dependencies: 19
-- Name: project_admin; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE project_admin (
    project_name character varying NOT NULL,
    username character varying NOT NULL
);


ALTER TABLE gisclient_31.project_admin OWNER TO gisclient;

--
-- TOC entry 6730 (class 1259 OID 13243128)
-- Dependencies: 19
-- Name: project_languages; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE project_languages (
    project_name character varying NOT NULL,
    language_id character(2) NOT NULL
);


ALTER TABLE gisclient_31.project_languages OWNER TO gisclient;

--
-- TOC entry 6731 (class 1259 OID 13243134)
-- Dependencies: 19
-- Name: project_srs; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE project_srs (
    project_name character varying NOT NULL,
    srid integer NOT NULL,
    projparam character varying,
    custom_srid integer
);


ALTER TABLE gisclient_31.project_srs OWNER TO gisclient;

--
-- TOC entry 6732 (class 1259 OID 13243140)
-- Dependencies: 7525 7526 7527 7528 7529 7530 7531 7532 7533 7534 19
-- Name: qtfield; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
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
    CONSTRAINT qtfield_qtrelation_id_check CHECK ((qtrelation_id >= 0))
);


ALTER TABLE gisclient_31.qtfield OWNER TO gisclient;

--
-- TOC entry 6733 (class 1259 OID 13243156)
-- Dependencies: 7535 19
-- Name: qtfield_groups; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE qtfield_groups (
    qtfield_id integer NOT NULL,
    groupname character varying NOT NULL,
    editable numeric(1,0) DEFAULT 0
);


ALTER TABLE gisclient_31.qtfield_groups OWNER TO gisclient;

--
-- TOC entry 6734 (class 1259 OID 13243163)
-- Dependencies: 19
-- Name: qtlink; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE qtlink (
    layer_id integer NOT NULL,
    link_id integer NOT NULL,
    resultype_id numeric(1,0)
);


ALTER TABLE gisclient_31.qtlink OWNER TO gisclient;

--
-- TOC entry 6735 (class 1259 OID 13243166)
-- Dependencies: 7536 7537 7538 19
-- Name: qtrelation; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_31.qtrelation OWNER TO gisclient;

--
-- TOC entry 6736 (class 1259 OID 13243175)
-- Dependencies: 7221 19
-- Name: seldb_catalog; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_catalog AS
    SELECT (-1) AS id, 'Seleziona ====>' AS opzione, '0' AS project_name UNION ALL SELECT foo.id, foo.opzione, foo.project_name FROM (SELECT catalog.catalog_id AS id, catalog.catalog_name AS opzione, catalog.project_name FROM catalog ORDER BY catalog.catalog_name) foo;


ALTER TABLE gisclient_31.seldb_catalog OWNER TO gisclient;

--
-- TOC entry 6737 (class 1259 OID 13243179)
-- Dependencies: 7222 19
-- Name: seldb_catalog_wms; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_catalog_wms AS
    SELECT catalog.catalog_id AS id, catalog.catalog_name AS opzione, catalog.project_name FROM catalog WHERE (catalog.connection_type = 7);


ALTER TABLE gisclient_31.seldb_catalog_wms OWNER TO gisclient;

--
-- TOC entry 6738 (class 1259 OID 13243183)
-- Dependencies: 7223 19
-- Name: seldb_charset_encodings; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_charset_encodings AS
    SELECT e_charset_encodings.charset_encodings_id AS id, e_charset_encodings.charset_encodings_name AS opzione, e_charset_encodings.charset_encodings_order AS option_order FROM e_charset_encodings ORDER BY e_charset_encodings.charset_encodings_order;


ALTER TABLE gisclient_31.seldb_charset_encodings OWNER TO gisclient;

--
-- TOC entry 6739 (class 1259 OID 13243187)
-- Dependencies: 7224 19
-- Name: seldb_conntype; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_conntype AS
    SELECT NULL::integer AS id, 'Seleziona ====>' AS opzione UNION ALL SELECT foo.id, foo.opzione FROM (SELECT e_conntype.conntype_id AS id, e_conntype.conntype_name AS opzione FROM e_conntype ORDER BY e_conntype.conntype_order) foo;


ALTER TABLE gisclient_31.seldb_conntype OWNER TO gisclient;

--
-- TOC entry 6740 (class 1259 OID 13243191)
-- Dependencies: 7225 19
-- Name: seldb_datatype; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_datatype AS
    SELECT e_datatype.datatype_id AS id, e_datatype.datatype_name AS opzione FROM e_datatype;


ALTER TABLE gisclient_31.seldb_datatype OWNER TO gisclient;

--
-- TOC entry 6741 (class 1259 OID 13243195)
-- Dependencies: 7226 19
-- Name: seldb_fieldtype; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_fieldtype AS
    SELECT e_fieldtype.fieldtype_id AS id, e_fieldtype.fieldtype_name AS opzione FROM e_fieldtype;


ALTER TABLE gisclient_31.seldb_fieldtype OWNER TO gisclient;

--
-- TOC entry 6742 (class 1259 OID 13243199)
-- Dependencies: 7227 19
-- Name: seldb_filetype; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_filetype AS
    SELECT (-1) AS id, 'Seleziona ====>' AS opzione UNION SELECT e_filetype.filetype_id AS id, e_filetype.filetype_name AS opzione FROM e_filetype;


ALTER TABLE gisclient_31.seldb_filetype OWNER TO gisclient;

--
-- TOC entry 6743 (class 1259 OID 13243203)
-- Dependencies: 7228 19
-- Name: seldb_font; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_font AS
    SELECT foo.id, foo.opzione FROM (SELECT '' AS id, 'Seleziona ====>' AS opzione UNION SELECT font.font_name AS id, font.font_name AS opzione FROM font) foo ORDER BY foo.id;


ALTER TABLE gisclient_31.seldb_font OWNER TO gisclient;

--
-- TOC entry 6744 (class 1259 OID 13243207)
-- Dependencies: 7229 19
-- Name: seldb_group_authfilter; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_group_authfilter AS
    SELECT authfilter.filter_id AS id, authfilter.filter_name AS opzione, CASE WHEN (group_authfilter.groupname IS NULL) THEN ''::character varying ELSE group_authfilter.groupname END AS groupname FROM (authfilter LEFT JOIN group_authfilter USING (filter_id));


ALTER TABLE gisclient_31.seldb_group_authfilter OWNER TO gisclient;

--
-- TOC entry 6745 (class 1259 OID 13243211)
-- Dependencies: 7230 19
-- Name: seldb_language; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_language AS
    SELECT foo.id, foo.opzione FROM (SELECT ''::text AS id, 'Seleziona ====>' AS opzione UNION SELECT e_language.language_id AS id, e_language.language_name AS opzione FROM e_language) foo ORDER BY foo.id;


ALTER TABLE gisclient_31.seldb_language OWNER TO gisclient;

--
-- TOC entry 6746 (class 1259 OID 13243215)
-- Dependencies: 7231 19
-- Name: seldb_layer_layergroup; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_layer_layergroup AS
    SELECT (-1) AS id, 'Seleziona ====>' AS opzione, NULL::unknown AS layergroup_id UNION (SELECT DISTINCT layer.layer_id AS id, layer.layer_name AS opzione, layer.layergroup_id FROM layer WHERE (layer.queryable = (1)::numeric) ORDER BY layer.layer_name, layer.layer_id, layer.layergroup_id);


ALTER TABLE gisclient_31.seldb_layer_layergroup OWNER TO gisclient;

--
-- TOC entry 6747 (class 1259 OID 13243219)
-- Dependencies: 7232 19
-- Name: seldb_layertype; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_layertype AS
    SELECT (-1) AS id, 'Seleziona ====>' AS opzione UNION (SELECT e_layertype.layertype_id AS id, e_layertype.layertype_name AS opzione FROM e_layertype ORDER BY e_layertype.layertype_name);


ALTER TABLE gisclient_31.seldb_layertype OWNER TO gisclient;

--
-- TOC entry 6748 (class 1259 OID 13243223)
-- Dependencies: 7233 19
-- Name: seldb_lblposition; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_lblposition AS
    SELECT '' AS id, 'Seleziona ====>' AS opzione UNION (SELECT e_lblposition.lblposition_name AS id, e_lblposition.lblposition_name AS opzione FROM e_lblposition ORDER BY e_lblposition.lblposition_order);


ALTER TABLE gisclient_31.seldb_lblposition OWNER TO gisclient;

--
-- TOC entry 6749 (class 1259 OID 13243227)
-- Dependencies: 7234 19
-- Name: seldb_legendtype; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_legendtype AS
    SELECT e_legendtype.legendtype_id AS id, e_legendtype.legendtype_name AS opzione FROM e_legendtype ORDER BY e_legendtype.legendtype_order;


ALTER TABLE gisclient_31.seldb_legendtype OWNER TO gisclient;

--
-- TOC entry 6750 (class 1259 OID 13243231)
-- Dependencies: 7235 19
-- Name: seldb_link; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_link AS
    SELECT (-1) AS id, 'Seleziona ====>' AS opzione, '' AS project_name UNION SELECT link.link_id AS id, link.link_name AS opzione, link.project_name FROM link;


ALTER TABLE gisclient_31.seldb_link OWNER TO gisclient;

--
-- TOC entry 6751 (class 1259 OID 13243235)
-- Dependencies: 7236 19
-- Name: seldb_mapset_srid; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_mapset_srid AS
    SELECT project.project_srid AS id, project.project_srid AS opzione, project.project_name FROM project UNION SELECT project_srs.srid AS id, project_srs.srid AS opzione, project_srs.project_name FROM project_srs WHERE (NOT (((project_srs.project_name)::text || project_srs.srid) IN (SELECT ((project.project_name)::text || project.project_srid) FROM project))) ORDER BY 1;


ALTER TABLE gisclient_31.seldb_mapset_srid OWNER TO gisclient;

--
-- TOC entry 6752 (class 1259 OID 13243239)
-- Dependencies: 7237 19
-- Name: seldb_orderby; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_orderby AS
    SELECT e_orderby.orderby_id AS id, e_orderby.orderby_name AS opzione FROM e_orderby;


ALTER TABLE gisclient_31.seldb_orderby OWNER TO gisclient;

--
-- TOC entry 6753 (class 1259 OID 13243243)
-- Dependencies: 7238 19
-- Name: seldb_outputformat; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_outputformat AS
    SELECT e_outputformat.outputformat_id AS id, e_outputformat.outputformat_name AS opzione FROM e_outputformat ORDER BY e_outputformat.outputformat_order;


ALTER TABLE gisclient_31.seldb_outputformat OWNER TO gisclient;

--
-- TOC entry 6754 (class 1259 OID 13243247)
-- Dependencies: 7239 19
-- Name: seldb_owstype; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_owstype AS
    SELECT e_owstype.owstype_id AS id, e_owstype.owstype_name AS opzione FROM e_owstype;


ALTER TABLE gisclient_31.seldb_owstype OWNER TO gisclient;

--
-- TOC entry 6755 (class 1259 OID 13243251)
-- Dependencies: 7240 19
-- Name: seldb_papersize; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_papersize AS
    SELECT (-1) AS id, 'Seleziona ====>' AS opzione UNION SELECT e_papersize.papersize_id AS id, e_papersize.papersize_name AS opzione FROM e_papersize;


ALTER TABLE gisclient_31.seldb_papersize OWNER TO gisclient;

--
-- TOC entry 6756 (class 1259 OID 13243255)
-- Dependencies: 7241 19
-- Name: seldb_project; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_project AS
    SELECT '' AS id, 'Seleziona ====>' AS opzione UNION (SELECT DISTINCT project.project_name AS id, project.project_name AS opzione FROM project ORDER BY project.project_name);


ALTER TABLE gisclient_31.seldb_project OWNER TO gisclient;

--
-- TOC entry 6757 (class 1259 OID 13243259)
-- Dependencies: 7242 19
-- Name: seldb_qtrelation; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_qtrelation AS
    SELECT 0 AS id, 'layer' AS opzione, 0 AS layer_id UNION SELECT qtrelation.qtrelation_id AS id, qtrelation.qtrelation_name AS opzione, qtrelation.layer_id FROM qtrelation;


ALTER TABLE gisclient_31.seldb_qtrelation OWNER TO gisclient;

--
-- TOC entry 6758 (class 1259 OID 13243263)
-- Dependencies: 7243 19
-- Name: seldb_qtrelationtype; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_qtrelationtype AS
    SELECT e_qtrelationtype.qtrelationtype_id AS id, e_qtrelationtype.qtrelationtype_name AS opzione FROM e_qtrelationtype;


ALTER TABLE gisclient_31.seldb_qtrelationtype OWNER TO gisclient;

--
-- TOC entry 6759 (class 1259 OID 13243267)
-- Dependencies: 7244 19
-- Name: seldb_resultype; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_resultype AS
    SELECT e_resultype.resultype_id AS id, e_resultype.resultype_name AS opzione FROM e_resultype ORDER BY e_resultype.resultype_order;


ALTER TABLE gisclient_31.seldb_resultype OWNER TO gisclient;

--
-- TOC entry 6760 (class 1259 OID 13243271)
-- Dependencies: 7245 19
-- Name: seldb_searchtype; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_searchtype AS
    SELECT e_searchtype.searchtype_id AS id, e_searchtype.searchtype_name AS opzione FROM e_searchtype;


ALTER TABLE gisclient_31.seldb_searchtype OWNER TO gisclient;

--
-- TOC entry 6761 (class 1259 OID 13243275)
-- Dependencies: 7246 19
-- Name: seldb_sizeunits; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_sizeunits AS
    SELECT e_sizeunits.sizeunits_id AS id, e_sizeunits.sizeunits_name AS opzione FROM e_sizeunits;


ALTER TABLE gisclient_31.seldb_sizeunits OWNER TO gisclient;

--
-- TOC entry 6762 (class 1259 OID 13243279)
-- Dependencies: 7539 7540 7541 7542 19
-- Name: theme; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_31.theme OWNER TO gisclient;

--
-- TOC entry 6763 (class 1259 OID 13243289)
-- Dependencies: 7247 19
-- Name: seldb_theme; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_theme AS
    SELECT (-1) AS id, 'Seleziona ====>' AS opzione, '' AS project_name UNION SELECT theme.theme_id AS id, theme.theme_name AS opzione, theme.project_name FROM theme;


ALTER TABLE gisclient_31.seldb_theme OWNER TO gisclient;

--
-- TOC entry 6764 (class 1259 OID 13243293)
-- Dependencies: 7248 19
-- Name: seldb_tiletype; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW seldb_tiletype AS
    SELECT e_tiletype.tiletype_id AS id, e_tiletype.tiletype_name AS opzione FROM e_tiletype;


ALTER TABLE gisclient_31.seldb_tiletype OWNER TO gisclient;

--
-- TOC entry 6765 (class 1259 OID 13243297)
-- Dependencies: 7543 19
-- Name: selgroup; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE selgroup (
    selgroup_id integer NOT NULL,
    project_name character varying NOT NULL,
    selgroup_name character varying NOT NULL,
    selgroup_title character varying,
    selgroup_order smallint DEFAULT 1
);


ALTER TABLE gisclient_31.selgroup OWNER TO gisclient;

--
-- TOC entry 6777 (class 1259 OID 13243745)
-- Dependencies: 19
-- Name: selgroup_layer; Type: TABLE; Schema: gisclient_31; Owner: r3gis; Tablespace: 
--

CREATE TABLE selgroup_layer (
    selgroup_id integer NOT NULL,
    layer_id integer NOT NULL
);


ALTER TABLE gisclient_31.selgroup_layer OWNER TO r3gis;

--
-- TOC entry 6766 (class 1259 OID 13243307)
-- Dependencies: 7544 19
-- Name: style; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
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
    style_order integer
);


ALTER TABLE gisclient_31.style OWNER TO gisclient;

--
-- TOC entry 6767 (class 1259 OID 13243314)
-- Dependencies: 7545 7546 19
-- Name: symbol; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE symbol (
    symbol_name character varying NOT NULL,
    symbolcategory_id integer DEFAULT 1 NOT NULL,
    icontype integer DEFAULT 0 NOT NULL,
    symbol_image bytea,
    symbol_def text
);


ALTER TABLE gisclient_31.symbol OWNER TO gisclient;

--
-- TOC entry 6768 (class 1259 OID 13243322)
-- Dependencies: 7547 19
-- Name: symbol_ttf; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE symbol_ttf (
    symbol_ttf_name character varying NOT NULL,
    font_name character varying NOT NULL,
    symbolcategory_id integer DEFAULT 0,
    ascii_code smallint NOT NULL,
    "position" character(2),
    symbol_ttf_image bytea
);


ALTER TABLE gisclient_31.symbol_ttf OWNER TO gisclient;

--
-- TOC entry 6769 (class 1259 OID 13243329)
-- Dependencies: 19
-- Name: theme_version_id_seq; Type: SEQUENCE; Schema: gisclient_31; Owner: gisclient
--

CREATE SEQUENCE theme_version_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE gisclient_31.theme_version_id_seq OWNER TO gisclient;

--
-- TOC entry 7824 (class 0 OID 0)
-- Dependencies: 6769
-- Name: theme_version_id_seq; Type: SEQUENCE SET; Schema: gisclient_31; Owner: gisclient
--

SELECT pg_catalog.setval('theme_version_id_seq', 262, true);


--
-- TOC entry 6770 (class 1259 OID 13243331)
-- Dependencies: 19
-- Name: user_group; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE user_group (
    username character varying NOT NULL,
    groupname character varying NOT NULL
);


ALTER TABLE gisclient_31.user_group OWNER TO gisclient;

--
-- TOC entry 6771 (class 1259 OID 13243337)
-- Dependencies: 19
-- Name: usercontext; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE TABLE usercontext (
    usercontext_id integer NOT NULL,
    username character varying NOT NULL,
    mapset_name character varying NOT NULL,
    title character varying NOT NULL,
    context text
);


ALTER TABLE gisclient_31.usercontext OWNER TO gisclient;

--
-- TOC entry 6772 (class 1259 OID 13243343)
-- Dependencies: 6771 19
-- Name: usercontext_usercontext_id_seq; Type: SEQUENCE; Schema: gisclient_31; Owner: gisclient
--

CREATE SEQUENCE usercontext_usercontext_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE gisclient_31.usercontext_usercontext_id_seq OWNER TO gisclient;

--
-- TOC entry 7825 (class 0 OID 0)
-- Dependencies: 6772
-- Name: usercontext_usercontext_id_seq; Type: SEQUENCE OWNED BY; Schema: gisclient_31; Owner: gisclient
--

ALTER SEQUENCE usercontext_usercontext_id_seq OWNED BY usercontext.usercontext_id;


--
-- TOC entry 7826 (class 0 OID 0)
-- Dependencies: 6772
-- Name: usercontext_usercontext_id_seq; Type: SEQUENCE SET; Schema: gisclient_31; Owner: gisclient
--

SELECT pg_catalog.setval('usercontext_usercontext_id_seq', 12, true);


--
-- TOC entry 6773 (class 1259 OID 13243345)
-- Dependencies: 7549 19
-- Name: users; Type: TABLE; Schema: gisclient_31; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_31.users OWNER TO gisclient;

--
-- TOC entry 6774 (class 1259 OID 13243352)
-- Dependencies: 7249 19
-- Name: vista_group_authfilter; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW vista_group_authfilter AS
    SELECT af.filter_id, af.filter_name, gaf.filter_expression, gaf.groupname FROM (authfilter af JOIN group_authfilter gaf USING (filter_id)) ORDER BY af.filter_name;


ALTER TABLE gisclient_31.vista_group_authfilter OWNER TO gisclient;

--
-- TOC entry 6775 (class 1259 OID 13243356)
-- Dependencies: 7250 19
-- Name: vista_qtfield; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW vista_qtfield AS
    SELECT qtfield.qtfield_id, qtfield.layer_id, qtfield.resultype_id, qtfield.fieldtype_id, x.qtrelation_id, qtfield.qtfield_name, qtfield.field_header, qtfield.qtfield_order, COALESCE(qtfield.column_width, 0) AS column_width, x.name AS qtrelation_name, x.qtrelationtype_id, x.qtrelationtype_name FROM ((qtfield JOIN e_fieldtype USING (fieldtype_id)) JOIN (SELECT y.qtrelationtype_id, y.qtrelation_id, y.name, z.qtrelationtype_name FROM ((SELECT 0 AS qtrelation_id, 'Data Layer' AS name, 0 AS qtrelationtype_id UNION SELECT qtrelation.qtrelation_id, COALESCE(qtrelation.qtrelation_name, 'Nessuna Relazione'::character varying) AS name, qtrelation.qtrelationtype_id FROM qtrelation) y JOIN (SELECT 0 AS qtrelationtype_id, '' AS qtrelationtype_name UNION SELECT e_qtrelationtype.qtrelationtype_id, e_qtrelationtype.qtrelationtype_name FROM e_qtrelationtype) z USING (qtrelationtype_id))) x USING (qtrelation_id)) ORDER BY qtfield.qtfield_id, x.qtrelation_id, x.qtrelationtype_id;


ALTER TABLE gisclient_31.vista_qtfield OWNER TO gisclient;

--
-- TOC entry 6776 (class 1259 OID 13243361)
-- Dependencies: 7251 19
-- Name: vista_style; Type: VIEW; Schema: gisclient_31; Owner: gisclient
--

CREATE VIEW vista_style AS
    SELECT style.style_id, style.class_id, style.style_name, style.angle, style.color, style.outlinecolor, style.bgcolor, style.size, style.minsize, style.maxsize, style.minwidth, style.width, style.maxwidth, style.style_def, style.locked, style.symbol_name, symbol.symbol_image, style.style_order FROM (style LEFT JOIN symbol USING (symbol_name)) ORDER BY style.style_order;


ALTER TABLE gisclient_31.vista_style OWNER TO gisclient;

--
-- TOC entry 7475 (class 2604 OID 13243366)
-- Dependencies: 6718 6717
-- Name: i18nf_id; Type: DEFAULT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE i18n_field ALTER COLUMN i18nf_id SET DEFAULT nextval('i18n_field_i18nf_id_seq'::regclass);


--
-- TOC entry 7505 (class 2604 OID 13243367)
-- Dependencies: 6725 6724
-- Name: localization_id; Type: DEFAULT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE localization ALTER COLUMN localization_id SET DEFAULT nextval('localization_localization_id_seq'::regclass);


--
-- TOC entry 7548 (class 2604 OID 13243368)
-- Dependencies: 6772 6771
-- Name: usercontext_id; Type: DEFAULT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE usercontext ALTER COLUMN usercontext_id SET DEFAULT nextval('usercontext_usercontext_id_seq'::regclass);


--
-- TOC entry 7760 (class 0 OID 13242812)
-- Dependencies: 6685
-- Data for Name: authfilter; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7761 (class 0 OID 13242819)
-- Dependencies: 6686
-- Data for Name: catalog; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7762 (class 0 OID 13242825)
-- Dependencies: 6687
-- Data for Name: catalog_import; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7763 (class 0 OID 13242831)
-- Dependencies: 6688
-- Data for Name: class; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7764 (class 0 OID 13242843)
-- Dependencies: 6689
-- Data for Name: classgroup; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7765 (class 0 OID 13242852)
-- Dependencies: 6690
-- Data for Name: e_charset_encodings; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_charset_encodings (charset_encodings_id, charset_encodings_name, charset_encodings_order) VALUES (1, 'ISO-8859-1', 1);
INSERT INTO e_charset_encodings (charset_encodings_id, charset_encodings_name, charset_encodings_order) VALUES (2, 'UTF-8', 2);


--
-- TOC entry 7766 (class 0 OID 13242858)
-- Dependencies: 6691
-- Data for Name: e_conntype; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_conntype (conntype_id, conntype_name, conntype_order) VALUES (7, 'WMS', 4);
INSERT INTO e_conntype (conntype_id, conntype_name, conntype_order) VALUES (3, 'SDE', 7);
INSERT INTO e_conntype (conntype_id, conntype_name, conntype_order) VALUES (6, 'Postgis', 2);
INSERT INTO e_conntype (conntype_id, conntype_name, conntype_order) VALUES (8, 'Oracle Spatial', 3);
INSERT INTO e_conntype (conntype_id, conntype_name, conntype_order) VALUES (1, 'Local Folder', 1);
INSERT INTO e_conntype (conntype_id, conntype_name, conntype_order) VALUES (9, 'WFS', 5);
INSERT INTO e_conntype (conntype_id, conntype_name, conntype_order) VALUES (4, 'OGR', 5);


--
-- TOC entry 7767 (class 0 OID 13242864)
-- Dependencies: 6692
-- Data for Name: e_datatype; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_datatype (datatype_id, datatype_name, datatype_order) VALUES (1, 'Stringa di testo', NULL);
INSERT INTO e_datatype (datatype_id, datatype_name, datatype_order) VALUES (2, 'Numero', NULL);
INSERT INTO e_datatype (datatype_id, datatype_name, datatype_order) VALUES (3, 'Data', NULL);


--
-- TOC entry 7768 (class 0 OID 13242870)
-- Dependencies: 6693
-- Data for Name: e_fieldformat; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_fieldformat (fieldformat_id, fieldformat_name, fieldformat_format, fieldformat_order) VALUES (1, 'intero', '%d', 10);
INSERT INTO e_fieldformat (fieldformat_id, fieldformat_name, fieldformat_format, fieldformat_order) VALUES (2, 'decimale (1 cifra)', '%01.1f', 20);
INSERT INTO e_fieldformat (fieldformat_id, fieldformat_name, fieldformat_format, fieldformat_order) VALUES (3, 'decimale (2 cifre)', '%01.2f', 30);


--
-- TOC entry 7769 (class 0 OID 13242876)
-- Dependencies: 6694
-- Data for Name: e_fieldtype; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_fieldtype (fieldtype_id, fieldtype_name, fieldtype_order) VALUES (1, 'Standard', NULL);
INSERT INTO e_fieldtype (fieldtype_id, fieldtype_name, fieldtype_order) VALUES (2, 'Collegamento', NULL);
INSERT INTO e_fieldtype (fieldtype_id, fieldtype_name, fieldtype_order) VALUES (3, 'E-mail', NULL);
INSERT INTO e_fieldtype (fieldtype_id, fieldtype_name, fieldtype_order) VALUES (10, 'Intestazione di gruppo', NULL);
INSERT INTO e_fieldtype (fieldtype_id, fieldtype_name, fieldtype_order) VALUES (8, 'Immagine', NULL);
INSERT INTO e_fieldtype (fieldtype_id, fieldtype_name, fieldtype_order) VALUES (107, 'Varianza', NULL);
INSERT INTO e_fieldtype (fieldtype_id, fieldtype_name, fieldtype_order) VALUES (106, 'Deviazione  St', NULL);
INSERT INTO e_fieldtype (fieldtype_id, fieldtype_name, fieldtype_order) VALUES (105, 'Conteggio', NULL);
INSERT INTO e_fieldtype (fieldtype_id, fieldtype_name, fieldtype_order) VALUES (104, 'Max', NULL);
INSERT INTO e_fieldtype (fieldtype_id, fieldtype_name, fieldtype_order) VALUES (103, 'Min', NULL);
INSERT INTO e_fieldtype (fieldtype_id, fieldtype_name, fieldtype_order) VALUES (102, 'Media', NULL);
INSERT INTO e_fieldtype (fieldtype_id, fieldtype_name, fieldtype_order) VALUES (101, 'Somma', NULL);


--
-- TOC entry 7770 (class 0 OID 13242882)
-- Dependencies: 6695
-- Data for Name: e_filetype; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_filetype (filetype_id, filetype_name, filetype_order) VALUES (1, 'File SQL', 1);
INSERT INTO e_filetype (filetype_id, filetype_name, filetype_order) VALUES (2, 'File CSV', 2);
INSERT INTO e_filetype (filetype_id, filetype_name, filetype_order) VALUES (3, 'File Shape', 3);


--
-- TOC entry 7771 (class 0 OID 13242888)
-- Dependencies: 6696
-- Data for Name: e_form; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (2, 'progetto', 'project', 0, 2, NULL, NULL, NULL, NULL, NULL, 'project_name');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (3, 'progetto', 'project', 1, 2, '', NULL, NULL, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (5, 'mapset', 'mapset', 0, 8, NULL, NULL, NULL, NULL, NULL, 'title');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (8, 'temi', 'theme', 0, 5, NULL, NULL, NULL, NULL, NULL, 'theme_order,theme_title');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (9, 'temi', 'theme', 1, 5, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (10, 'temi', 'theme', 1, 5, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (11, 'temi', 'theme', 2, 5, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (12, 'project_srs', 'project_srs', 0, 6, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (13, 'project_srs', 'project_srs', 1, 6, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (14, 'project_srs', 'project_srs', 2, 6, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (23, 'group', 'group', 50, 3, NULL, 'group', 2, NULL, 'group', NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (26, 'mapset', 'mapset', 1, 8, '', NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (27, 'mapset', 'mapset', 1, 8, NULL, 'mapset', 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (28, 'mapset', 'mapset', 2, 2, NULL, 'mapset', 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (34, 'layer', 'layer', 0, 11, NULL, NULL, 10, NULL, NULL, 'layer_order,layer_name');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (35, 'layer', 'layer', 1, 11, NULL, 'layer', 10, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (36, 'layer', 'layer', 1, 11, NULL, 'layer', 10, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (37, 'layer', 'layer', 2, 11, NULL, 'layer', 10, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (38, 'classi', 'class', 0, 12, NULL, NULL, 11, NULL, NULL, 'class_order');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (39, 'classi', 'class', 1, 12, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (40, 'classi', 'class', 1, 12, NULL, 'class', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (41, 'classi', 'class', 2, 12, NULL, 'class', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (42, 'stili', 'style', 0, 14, NULL, NULL, 12, NULL, NULL, 'style_order');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (43, 'stili', 'style', 1, 14, NULL, NULL, 12, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (44, 'stili', 'style', 1, 14, NULL, 'style', 12, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (45, 'stili', 'style', 2, 14, NULL, 'style', 12, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (50, 'catalog', 'catalog', 0, 7, NULL, NULL, 2, NULL, NULL, 'catalog_name');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (51, 'catalog', 'catalog', 1, 7, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (52, 'catalog', 'catalog', 1, 7, NULL, 'catalog', 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (53, 'catalog', 'catalog', 2, 7, NULL, 'catalog', 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (70, 'links', 'link', 0, 9, '', NULL, 2, NULL, NULL, 'link_order,link_name');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (72, 'links', 'link', 1, 9, '', NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (73, 'links', 'link', 1, 9, '', NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (74, 'links', 'link', 2, 9, '', NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (81, 'map_group', 'mapset_group', 4, 21, NULL, 'mapset_groups', 8, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (82, 'map_group', 'mapset_group', 5, 21, NULL, 'mapset_groups', 8, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (83, 'map_group', 'mapset_group', 0, 21, NULL, NULL, 8, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (105, 'selgroup', 'selgroup', 0, 27, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (106, 'selgroup', 'selgroup', 1, 27, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (107, 'selgroup', 'selgroup', 1, 27, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (108, 'qt_selgroup', 'qt_selgroup', 4, 28, NULL, 'qt_selgroup', 27, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (109, 'qt_selgroup', 'qt_selgroup', 5, 28, NULL, 'qt_selgroup', 27, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (149, 'group_users', 'group_users', 4, 45, NULL, 'group_users', 3, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (150, 'group_users', 'group_users', 5, 45, NULL, 'group_users', 3, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (151, 'user_groups', 'user_groups', 4, 46, NULL, 'user_groups', 4, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (152, 'user_groups', 'user_groups', 5, 46, NULL, 'user_groups', 4, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (75, 'qt_relation', 'qt_relation_addnew', 0, 16, NULL, NULL, 13, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (62, 'qt_fields', 'qtfield', 0, 17, NULL, NULL, 11, NULL, NULL, 'qtrelationtype_id,qtrelation_name,field_header,qtfield_name');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (63, 'qt_fields', 'qtfield', 1, 17, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (64, 'qt_fields', 'qtfield', 1, 17, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (65, 'qt_fields', 'qtfield', 2, 17, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (201, 'classgroup', 'classgroup', 1, 100, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (200, 'classgroup', 'classgroup', 0, 100, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (58, 'qt_relation', 'qtrelation', 0, 16, NULL, 'qtrelation', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (59, 'qt_relation', 'qtrelation', 1, 16, NULL, 'qtrelation', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (60, 'qt_relation', 'qtrelation', 1, 16, NULL, 'qtrelation', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (61, 'qt_relation', 'qtrelation', 2, 16, NULL, 'qtrelation', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (170, 'layer_groups', 'layer_groups', 4, 47, NULL, 'layer_groups', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (171, 'layer_groups', 'layer_groups', 5, 47, NULL, 'layer_groups', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (202, 'project_languages', 'project_languages', 0, 48, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (203, 'project_languages', 'project_languages', 1, 48, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (204, 'authfilter', 'authfilter', 0, 49, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (205, 'authfilter', 'authfilter', 1, 49, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (16, 'user', 'user', 0, 4, NULL, 'user', 2, NULL, 'user', NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (206, 'layer_authfilter', 'layer_authfilter', 4, 50, NULL, 'layer_authfilter', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (207, 'layer_authfilter', 'layer_authfilter', 5, 50, NULL, 'layer_authfilter', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (6, 'progetto', 'project', 2, 2, '', NULL, NULL, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (209, 'group_authfilter', 'group_authfilter', 1, 51, NULL, NULL, 3, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (208, 'group_authfilter', 'group_authfilter', 0, 51, NULL, NULL, 3, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (66, 'qtlink', 'qtlink', 2, 19, NULL, 'qtlink', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (67, 'qtlink', 'qtlink', 0, 19, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (68, 'qtlink', 'qtlink', 1, 19, NULL, 'qtlink', 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (30, 'layergroup', 'layergroup', 0, 10, NULL, NULL, 5, NULL, NULL, 'layergroup_order,layergroup_title');
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (31, 'layergroup', 'layergroup', 1, 10, NULL, NULL, 5, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (32, 'layergroup', 'layergroup', 1, 10, NULL, NULL, 5, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (33, 'layergroup', 'layergroup', 2, 10, NULL, NULL, 5, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (7, 'progetto', 'project', 1, 2, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (133, 'project_admin', 'admin_project', 2, 33, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (134, 'project_admin', 'admin_project', 5, 33, NULL, NULL, 6, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (210, 'qtfield_groups', 'qtfield_groups', 4, 52, NULL, 'qtfield_groups', 17, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (211, 'qtfield_groups', 'qtfield_groups', 5, 52, NULL, 'qtfield_groups', 17, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (212, 'qtfield_groups', 'qtfield_groups', 0, 52, NULL, 'qtfield_groups', 17, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (84, 'map_layer', 'mapset_layergroup', 4, 22, NULL, 'mapset_layergroup', 8, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (85, 'map_layer', 'mapset_layergroup', 5, 22, NULL, 'mapset_layergroup', 8, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (86, 'map_layer', 'mapset_layergroup', 0, 22, NULL, NULL, 8, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (69, 'qtlink', 'qtlink', 110, 19, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (20, 'group', 'group', 0, 3, NULL, 'group', 2, NULL, 'group', NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (18, 'user', 'user', 50, 4, NULL, 'user', 2, NULL, 'user', NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (213, 'selgroup_layer', 'selgroup_layer', 4, 28, NULL, 'selgroup_layer', 27, NULL, NULL, NULL);
INSERT INTO e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) VALUES (214, 'selgroup_layer', 'selgroup_layer', 5, 28, NULL, 'selgroup_layer', 27, NULL, NULL, NULL);


--
-- TOC entry 7772 (class 0 OID 13242894)
-- Dependencies: 6697
-- Data for Name: e_language; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_language (language_id, language_name, language_order) VALUES ('en', 'English', 1);
INSERT INTO e_language (language_id, language_name, language_order) VALUES ('fr', 'Francais', 2);
INSERT INTO e_language (language_id, language_name, language_order) VALUES ('de', 'Deutsch', 3);
INSERT INTO e_language (language_id, language_name, language_order) VALUES ('es', 'Espanol', 4);
INSERT INTO e_language (language_id, language_name, language_order) VALUES ('it', 'Italiano', 5);


--
-- TOC entry 7773 (class 0 OID 13242900)
-- Dependencies: 6698
-- Data for Name: e_layertype; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_layertype (layertype_id, layertype_name, layertype_ms, layertype_order) VALUES (5, 'annotation', 4, NULL);
INSERT INTO e_layertype (layertype_id, layertype_name, layertype_ms, layertype_order) VALUES (1, 'point', 0, NULL);
INSERT INTO e_layertype (layertype_id, layertype_name, layertype_ms, layertype_order) VALUES (2, 'line', 1, NULL);
INSERT INTO e_layertype (layertype_id, layertype_name, layertype_ms, layertype_order) VALUES (3, 'polygon', 2, NULL);
INSERT INTO e_layertype (layertype_id, layertype_name, layertype_ms, layertype_order) VALUES (4, 'raster', 3, NULL);
INSERT INTO e_layertype (layertype_id, layertype_name, layertype_ms, layertype_order) VALUES (8, 'tileindex', 7, NULL);
INSERT INTO e_layertype (layertype_id, layertype_name, layertype_ms, layertype_order) VALUES (10, 'tileraster', 100, NULL);
INSERT INTO e_layertype (layertype_id, layertype_name, layertype_ms, layertype_order) VALUES (11, 'chart', 8, NULL);


--
-- TOC entry 7774 (class 0 OID 13242906)
-- Dependencies: 6699
-- Data for Name: e_lblposition; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_lblposition (lblposition_id, lblposition_name, lblposition_order) VALUES (1, 'UL', NULL);
INSERT INTO e_lblposition (lblposition_id, lblposition_name, lblposition_order) VALUES (2, 'UC', NULL);
INSERT INTO e_lblposition (lblposition_id, lblposition_name, lblposition_order) VALUES (3, 'UR', NULL);
INSERT INTO e_lblposition (lblposition_id, lblposition_name, lblposition_order) VALUES (4, 'CL', NULL);
INSERT INTO e_lblposition (lblposition_id, lblposition_name, lblposition_order) VALUES (5, 'CC', NULL);
INSERT INTO e_lblposition (lblposition_id, lblposition_name, lblposition_order) VALUES (6, 'CR', NULL);
INSERT INTO e_lblposition (lblposition_id, lblposition_name, lblposition_order) VALUES (7, 'LL', NULL);
INSERT INTO e_lblposition (lblposition_id, lblposition_name, lblposition_order) VALUES (8, 'LC', NULL);
INSERT INTO e_lblposition (lblposition_id, lblposition_name, lblposition_order) VALUES (9, 'LR', NULL);
INSERT INTO e_lblposition (lblposition_id, lblposition_name, lblposition_order) VALUES (10, 'AUTO', NULL);


--
-- TOC entry 7775 (class 0 OID 13242912)
-- Dependencies: 6700
-- Data for Name: e_legendtype; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_legendtype (legendtype_id, legendtype_name, legendtype_order) VALUES (1, 'auto', 1);
INSERT INTO e_legendtype (legendtype_id, legendtype_name, legendtype_order) VALUES (0, 'nessuna', 2);


--
-- TOC entry 7776 (class 0 OID 13242918)
-- Dependencies: 6701
-- Data for Name: e_level; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (19, 'qtlink', 'layer', 12, 11, 4, 1, 0, 11, 'qtlink', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (12, 'class', 'class', 6, 11, 4, 0, 1, 11, 'class', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (17, 'qtfield', 'qtfield', 11, 11, 4, 0, 1, 11, 'qtfield', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (16, 'qtrelation', 'qtrelation', 10, 11, 4, 1, 1, 11, 'qtrelation', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (8, 'mapset', 'mapset', 15, 2, 1, 0, 1, 2, 'mapset', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (100, 'classgroup', 'layer', NULL, 11, 4, 1, 0, 11, 'classgroup', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (1, 'root', NULL, 1, NULL, NULL, 0, 0, NULL, NULL, 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (2, 'project', 'project', 2, 1, 0, 0, 1, 1, 'project', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (5, 'theme', 'theme', 3, 2, 1, 0, 5, 2, 'theme', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (6, 'project_srs', 'project_srs', 4, 2, 1, 1, 1, 2, 'project_srs', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (7, 'catalog', 'catalog', 13, 2, 1, 1, 2, 2, 'catalog', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (9, 'link', 'link', 15, 2, 1, 1, 4, 2, 'link', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (10, 'layergroup', 'layergroup', 4, 5, 2, 0, 1, 5, 'layergroup', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (11, 'layer', 'layer', 5, 10, 3, 0, 1, 10, 'layer', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (14, 'style', 'style', 7, 12, 5, 1, 1, 12, 'style', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (21, 'mapset_groups', 'mapset_groups', 16, 8, 2, 1, 4, 8, 'mapset_usergroup', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (22, 'mapset_layergroup', 'mapset_layergroup', 17, 8, 2, 1, 1, 8, 'mapset_layergroup', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (23, 'mapset_qt', 'mapset_qt', 18, 8, 2, 1, 2, 8, 'mapset_qt', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (3, 'groups', 'groups', 7, 1, 0, 0, 0, 1, 'groups', 1);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (51, 'group_authfilter', 'groups', 1, 3, 1, 1, 1, 3, 'group_authfilter', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (52, 'qtfield_groups', 'qtfield', 1, 17, 5, 1, 0, 17, 'qtfield_groups', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (24, 'mapset_link', 'mapset_link', 19, 8, 2, 1, 3, 8, 'mapset_link', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (27, 'selgroup', 'selgroup', NULL, 2, 1, 0, 8, 2, 'selgroup', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (33, 'project_admin', 'project_admin', 15, 2, 1, 1, 0, 2, 'project_admin', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (32, 'user_project', 'project', 8, 2, 1, 1, 0, 2, 'user_project', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (47, 'layer_groups', 'layer_groups', NULL, 11, 4, 1, 1, 11, 'layer_groups', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (48, 'project_languages', 'project_languages', NULL, 2, 1, 1, 1, 2, 'project_languages', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (4, 'users', 'users', 6, 1, 0, 0, 0, 1, 'users', 1);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (49, 'authfilter', 'authfilter', 8, 1, 0, 1, 0, 1, 'authfilter', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (50, 'layer_authfilter', 'layer', 15, 11, 4, 1, 1, 11, 'layer_authfilter', 2);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (46, 'user_groups', 'group_users', NULL, 3, 1, 1, 0, 3, 'user_group', 1);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (45, 'group_users', 'user_groups', NULL, 4, 1, 1, 0, 4, 'user_group', 1);
INSERT INTO e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) VALUES (28, 'selgroup_layer', 'selgroup', NULL, 27, 2, 1, 1, 27, 'selgroup_layer', 2);


--
-- TOC entry 7777 (class 0 OID 13242926)
-- Dependencies: 6702
-- Data for Name: e_orderby; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_orderby (orderby_id, orderby_name, orderby_order) VALUES (0, 'Nessuno', NULL);
INSERT INTO e_orderby (orderby_id, orderby_name, orderby_order) VALUES (1, 'Crescente', NULL);
INSERT INTO e_orderby (orderby_id, orderby_name, orderby_order) VALUES (2, 'Decresente', NULL);


--
-- TOC entry 7778 (class 0 OID 13242932)
-- Dependencies: 6703
-- Data for Name: e_outputformat; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (2, 'AGG PNG', 'AGG/PNG', 'image/png', 'PC256', 'png', NULL, NULL);
INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (3, 'AGG JPG', 'AGG/JPG', 'image/jpg', 'RGB', 'jpg', NULL, NULL);
INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (4, 'PNG 8 bit', 'GD/PNG', 'image/png', 'PC256', 'png', NULL, NULL);
INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (5, 'PNG 24 bit', 'GD/PNG', 'image/png', 'RGB', 'png', NULL, NULL);
INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (6, 'PNG 32 bit Trasp', 'GD/PNG', 'image/png', 'RGBA', 'png', NULL, NULL);
INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (7, 'AGG Q', 'AGG/PNG', 'image/png; mode=8bit', 'RGB', 'png', '    FORMATOPTION "QUANTIZE_FORCE=ON"
    FORMATOPTION "QUANTIZE_DITHER=OFF"
    FORMATOPTION "QUANTIZE_COLORS=256"', NULL);
INSERT INTO e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) VALUES (1, 'AGG PNG 24 bit', 'AGG/PNG', 'image/png; mode=24bit', 'RGB', 'png', NULL, NULL);


--
-- TOC entry 7779 (class 0 OID 13242938)
-- Dependencies: 6704
-- Data for Name: e_owstype; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (3, 'VirtualEarth', 3);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (4, 'Yahoo', 4);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (5, 'OSM', 5);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (1, 'OWS', 1);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (7, 'Google v.3', 7);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (8, 'Bing tiles', 8);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (2, 'Google v.2', 2);
INSERT INTO e_owstype (owstype_id, owstype_name, owstype_order) VALUES (6, 'TMS', 6);


--
-- TOC entry 7780 (class 0 OID 13242944)
-- Dependencies: 6705
-- Data for Name: e_papersize; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_papersize (papersize_id, papersize_name, papersize_size, papersize_orientation, papaersize_order) VALUES (1, 'A4 Verticale', 'A4', 'P', NULL);
INSERT INTO e_papersize (papersize_id, papersize_name, papersize_size, papersize_orientation, papaersize_order) VALUES (2, 'A4 Orizzontale', 'A4', 'L', NULL);
INSERT INTO e_papersize (papersize_id, papersize_name, papersize_size, papersize_orientation, papaersize_order) VALUES (3, 'A3 Verticale', 'A3', 'P', NULL);
INSERT INTO e_papersize (papersize_id, papersize_name, papersize_size, papersize_orientation, papaersize_order) VALUES (4, 'A3 Orizzontale', 'A3', 'L', NULL);
INSERT INTO e_papersize (papersize_id, papersize_name, papersize_size, papersize_orientation, papaersize_order) VALUES (5, 'A2 Verticale', 'A2', 'P', NULL);
INSERT INTO e_papersize (papersize_id, papersize_name, papersize_size, papersize_orientation, papaersize_order) VALUES (6, 'A2 Orizzontale', 'A2', 'L', NULL);
INSERT INTO e_papersize (papersize_id, papersize_name, papersize_size, papersize_orientation, papaersize_order) VALUES (7, 'A1 Verticale', 'A1', 'P', NULL);
INSERT INTO e_papersize (papersize_id, papersize_name, papersize_size, papersize_orientation, papaersize_order) VALUES (8, 'A1 Orizzontale', 'A1', 'L', NULL);
INSERT INTO e_papersize (papersize_id, papersize_name, papersize_size, papersize_orientation, papaersize_order) VALUES (9, 'A0 Verticale', 'A0', 'P', NULL);
INSERT INTO e_papersize (papersize_id, papersize_name, papersize_size, papersize_orientation, papaersize_order) VALUES (10, 'A0 Orizzontale', 'A0', 'L', NULL);


--
-- TOC entry 7781 (class 0 OID 13242950)
-- Dependencies: 6706
-- Data for Name: e_qtrelationtype; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_qtrelationtype (qtrelationtype_id, qtrelationtype_name, qtrelationtype_order) VALUES (1, 'Dettaglio (1 a 1)', NULL);
INSERT INTO e_qtrelationtype (qtrelationtype_id, qtrelationtype_name, qtrelationtype_order) VALUES (2, 'Secondaria (Info 1 a molti)', NULL);


--
-- TOC entry 7782 (class 0 OID 13242956)
-- Dependencies: 6707
-- Data for Name: e_resultype; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_resultype (resultype_id, resultype_name, resultype_order) VALUES (1, 'Si', 2);
INSERT INTO e_resultype (resultype_id, resultype_name, resultype_order) VALUES (4, 'No', 4);


--
-- TOC entry 7783 (class 0 OID 13242962)
-- Dependencies: 6708
-- Data for Name: e_searchtype; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_searchtype (searchtype_id, searchtype_name, searchtype_order) VALUES (4, 'Numerico', NULL);
INSERT INTO e_searchtype (searchtype_id, searchtype_name, searchtype_order) VALUES (5, 'Data', NULL);
INSERT INTO e_searchtype (searchtype_id, searchtype_name, searchtype_order) VALUES (1, 'Testo', NULL);
INSERT INTO e_searchtype (searchtype_id, searchtype_name, searchtype_order) VALUES (2, 'Parte di testo', NULL);
INSERT INTO e_searchtype (searchtype_id, searchtype_name, searchtype_order) VALUES (3, 'Lista di valori', NULL);
INSERT INTO e_searchtype (searchtype_id, searchtype_name, searchtype_order) VALUES (0, 'Nessuno', NULL);


--
-- TOC entry 7784 (class 0 OID 13242968)
-- Dependencies: 6709
-- Data for Name: e_sizeunits; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_sizeunits (sizeunits_id, sizeunits_name, sizeunits_order) VALUES (2, 'feet', NULL);
INSERT INTO e_sizeunits (sizeunits_id, sizeunits_name, sizeunits_order) VALUES (3, 'inches', NULL);
INSERT INTO e_sizeunits (sizeunits_id, sizeunits_name, sizeunits_order) VALUES (1, 'pixels', NULL);
INSERT INTO e_sizeunits (sizeunits_id, sizeunits_name, sizeunits_order) VALUES (4, 'kilometers', NULL);
INSERT INTO e_sizeunits (sizeunits_id, sizeunits_name, sizeunits_order) VALUES (5, 'meters', NULL);
INSERT INTO e_sizeunits (sizeunits_id, sizeunits_name, sizeunits_order) VALUES (6, 'miles', NULL);
INSERT INTO e_sizeunits (sizeunits_id, sizeunits_name, sizeunits_order) VALUES (7, 'dd', NULL);


--
-- TOC entry 7785 (class 0 OID 13242974)
-- Dependencies: 6710
-- Data for Name: e_symbolcategory; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_symbolcategory (symbolcategory_id, symbolcategory_name, symbolcategory_order) VALUES (1, 'Standard', NULL);
INSERT INTO e_symbolcategory (symbolcategory_id, symbolcategory_name, symbolcategory_order) VALUES (10, 'Campitura', NULL);
INSERT INTO e_symbolcategory (symbolcategory_id, symbolcategory_name, symbolcategory_order) VALUES (2, 'Tratteggio', NULL);
INSERT INTO e_symbolcategory (symbolcategory_id, symbolcategory_name, symbolcategory_order) VALUES (99, 'TEST', NULL);


--
-- TOC entry 7786 (class 0 OID 13242980)
-- Dependencies: 6711
-- Data for Name: e_tiletype; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO e_tiletype (tiletype_id, tiletype_name, tiletype_order) VALUES (0, 'no Tiles', 1);
INSERT INTO e_tiletype (tiletype_id, tiletype_name, tiletype_order) VALUES (1, 'WMS Tiles', 2);
INSERT INTO e_tiletype (tiletype_id, tiletype_name, tiletype_order) VALUES (2, 'Tilecache Tiles', 3);


--
-- TOC entry 7788 (class 0 OID 13242995)
-- Dependencies: 6714
-- Data for Name: font; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO font (font_name, file_name) VALUES ('dejavu-sans', 'dejavu-sans.ttf');
INSERT INTO font (font_name, file_name) VALUES ('dejavu-sans-bold', 'dejavu-sans-bold.ttf');
INSERT INTO font (font_name, file_name) VALUES ('dejavu-sans-bold-italic', 'dejavu-sans-bold-italic.ttf');
INSERT INTO font (font_name, file_name) VALUES ('dejavu-serif', 'dejavu-serif.ttf');
INSERT INTO font (font_name, file_name) VALUES ('dejavu-serif-bold', 'dejavu-serif-bold');
INSERT INTO font (font_name, file_name) VALUES ('dejavu-serif-bold-italic', 'dejavu-serif-bold-italic.ttf');
INSERT INTO font (font_name, file_name) VALUES ('dejavu-serif-italic', 'dejavu-serif-italic.ttf');
INSERT INTO font (font_name, file_name) VALUES ('dejavu-sans-italic', 'dejavu-sans-italic.ttf');


--
-- TOC entry 7787 (class 0 OID 13242986)
-- Dependencies: 6712
-- Data for Name: form_level; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (1, 1, 3, 2, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (2, 2, 0, 3, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (4, 2, 3, 8, 5, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (5, 2, 3, 5, 8, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (7, 2, 1, 7, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (8, 2, 2, 6, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (14, 2, 3, 12, 3, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (15, 6, 1, 13, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (16, 6, 2, 13, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (17, 6, 0, 13, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (19, 8, 0, 26, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (20, 8, 1, 27, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (21, 8, 2, 28, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (22, 5, 0, 9, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (23, 5, 1, 10, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (24, 5, 2, 11, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (25, 5, 3, 30, 3, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (26, 10, 0, 31, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (27, 10, 1, 32, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (28, 10, 2, 33, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (29, 10, 3, 34, 3, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (30, 11, 0, 35, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (31, 11, 1, 36, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (32, 11, 2, 37, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (34, 12, 0, 39, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (35, 12, 1, 40, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (36, 12, 2, 41, 2, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (37, 12, 3, 42, 3, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (38, 14, 0, 43, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (39, 14, 1, 44, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (40, 14, 2, 45, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (45, 2, 3, 50, 4, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (46, 7, 0, 51, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (47, 7, 1, 52, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (48, 7, 2, 53, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (54, 16, 0, 59, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (55, 16, 1, 60, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (56, 16, 2, 61, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (57, 17, 0, 63, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (58, 17, 1, 64, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (59, 17, 2, 65, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (63, 2, 3, 70, 7, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (64, 9, 0, 72, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (65, 9, 1, 73, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (66, 9, 2, 74, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (74, 8, 3, 81, 2, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (75, 21, 1, 82, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (77, 8, 3, 84, 6, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (78, 22, 1, 85, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (98, 2, 3, 105, 6, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (99, 27, 1, 106, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (101, 27, 0, 107, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (102, 28, 1, 109, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (116, 27, 3, 108, 6, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (127, 33, 1, 134, 15, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (131, 2, 3, 133, 15, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (132, 27, 2, 106, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (164, 1, 3, 16, 3, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (165, 4, 0, 18, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (166, 4, 1, 18, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (167, 4, 2, 18, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (168, 1, 3, 20, 2, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (169, 3, 0, 23, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (170, 3, 1, 23, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (171, 3, 2, 23, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (172, 3, 3, 149, 2, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (173, 45, 1, 150, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (175, 4, 3, 151, 2, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (176, 46, 1, 152, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (79, 22, -1, 86, 2, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (69, 16, 1, 75, 2, 0);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (76, 21, 1, 83, 2, 0);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (100, 27, 2, 105, 2, 0);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (163, 27, 3, 151, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (33, 11, 3, 38, 3, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (500, 11, 3, 200, 2, 0);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (501, 100, 0, 201, 1, 0);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (502, 100, 1, 201, 1, 0);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (503, 100, 2, 201, 1, 0);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (51, 11, 3, 58, 4, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (52, 11, 3, 62, 5, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (200, 11, 0, 170, 7, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (201, 47, 1, 171, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (202, 47, 3, 171, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (203, 47, 2, 171, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (504, 48, 0, 203, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (505, 48, 1, 203, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (506, 48, 2, 203, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (507, 2, 3, 202, 2, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (508, 49, 0, 205, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (509, 49, 1, 205, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (510, 49, 2, 205, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (513, 50, 1, 207, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (512, 11, 3, 206, 8, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (511, 1, 3, 204, 4, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (514, 3, 3, 208, 3, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (515, 51, 0, 209, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (516, 51, 1, 209, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (517, 51, 2, 209, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (518, 17, 0, 210, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (519, 52, 1, 211, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (53, 11, 3, 66, 6, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (60, 19, 0, 67, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (61, 19, 1, 68, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (62, 19, 1, 69, 2, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (520, 27, 3, 213, 1, 1);
INSERT INTO form_level (id, level, mode, form, order_fld, visible) VALUES (521, 28, 1, 214, 1, 1);


--
-- TOC entry 7789 (class 0 OID 13243001)
-- Dependencies: 6715
-- Data for Name: group_authfilter; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7790 (class 0 OID 13243007)
-- Dependencies: 6716
-- Data for Name: groups; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7791 (class 0 OID 13243013)
-- Dependencies: 6717
-- Data for Name: i18n_field; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (1, 'class', 'class_title');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (2, 'class', 'expression');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (3, 'class', 'label_def');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (4, 'class', 'class_text');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (5, 'layer', 'layer_title');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (6, 'layer', 'data_filter');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (7, 'layer', 'layer_def');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (8, 'layer', 'metadata');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (9, 'layer', 'labelitem');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (10, 'layer', 'classitem');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (11, 'layergroup', 'layergroup_title');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (12, 'layergroup', 'sld');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (13, 'qtfield', 'qtfield_name');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (14, 'qtfield', 'field_header');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (15, 'style', 'style_def');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (16, 'theme', 'theme_title');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (17, 'theme', 'copyright_string');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (18, 'mapset', 'mapset_title');
INSERT INTO i18n_field (i18nf_id, table_name, field_name) VALUES (19, 'mapset', 'mapset_description');


--
-- TOC entry 7792 (class 0 OID 13243021)
-- Dependencies: 6719
-- Data for Name: layer; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7793 (class 0 OID 13243039)
-- Dependencies: 6720
-- Data for Name: layer_authfilter; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7794 (class 0 OID 13243043)
-- Dependencies: 6721
-- Data for Name: layer_groups; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7795 (class 0 OID 13243052)
-- Dependencies: 6722
-- Data for Name: layergroup; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7796 (class 0 OID 13243068)
-- Dependencies: 6723
-- Data for Name: link; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7797 (class 0 OID 13243077)
-- Dependencies: 6724
-- Data for Name: localization; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7798 (class 0 OID 13243085)
-- Dependencies: 6726
-- Data for Name: mapset; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7799 (class 0 OID 13243097)
-- Dependencies: 6727
-- Data for Name: mapset_layergroup; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7800 (class 0 OID 13243106)
-- Dependencies: 6728
-- Data for Name: project; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7801 (class 0 OID 13243122)
-- Dependencies: 6729
-- Data for Name: project_admin; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7802 (class 0 OID 13243128)
-- Dependencies: 6730
-- Data for Name: project_languages; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7803 (class 0 OID 13243134)
-- Dependencies: 6731
-- Data for Name: project_srs; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7804 (class 0 OID 13243140)
-- Dependencies: 6732
-- Data for Name: qtfield; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7805 (class 0 OID 13243156)
-- Dependencies: 6733
-- Data for Name: qtfield_groups; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7806 (class 0 OID 13243163)
-- Dependencies: 6734
-- Data for Name: qtlink; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7807 (class 0 OID 13243166)
-- Dependencies: 6735
-- Data for Name: qtrelation; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7809 (class 0 OID 13243297)
-- Dependencies: 6765
-- Data for Name: selgroup; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7816 (class 0 OID 13243745)
-- Dependencies: 6777
-- Data for Name: selgroup_layer; Type: TABLE DATA; Schema: gisclient_31; Owner: r3gis
--



--
-- TOC entry 7810 (class 0 OID 13243307)
-- Dependencies: 6766
-- Data for Name: style; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7811 (class 0 OID 13243314)
-- Dependencies: 6767
-- Data for Name: symbol; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--

INSERT INTO symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def) VALUES ('TENT', 1, 0, NULL, 'TYPE VECTOR
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
INSERT INTO symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def) VALUES ('STAR', 1, 0, NULL, 'TYPE VECTOR
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
INSERT INTO symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def) VALUES ('TRIANGLE', 1, 0, NULL, 'TYPE VECTOR
FILLED TRUE
POINTS
0 1
.5 0
1 1
0 1
END');
INSERT INTO symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def) VALUES ('SQUARE', 1, 0, NULL, 'TYPE VECTOR
FILLED TRUE
POINTS
0 1
0 0
1 0
1 1
0 1
END');
INSERT INTO symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def) VALUES ('PLUS', 1, 0, NULL, 'TYPE VECTOR
POINTS
.5 0
.5 1
-99 -99
0 .5
1 .5
END');
INSERT INTO symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def) VALUES ('CROSS', 1, 0, NULL, 'TYPE VECTOR
POINTS
0 0
1 1
-99 -99
0 1
1 0
END');
INSERT INTO symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def) VALUES ('VERTICAL', 1, 0, NULL, 'TYPE VECTOR
POINTS
0.5 0
0.5 1
END');
INSERT INTO symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def) VALUES ('CIRCLE', 1, 0, NULL, 'TYPE ELLIPSE
FILLED TRUE
POINTS
1 1
END');
INSERT INTO symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def) VALUES ('CIRCLE_EMPTY', 1, 0, NULL, 'TYPE Vector
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
  STYLE
    1 5 1 5
  END
	GAP 2');
INSERT INTO symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def) VALUES ('2-3', 2, 0, NULL, 'Type ELLIPSE
	Points
	  1 1
	END
	STYLE
		2 3
	END');
INSERT INTO symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def) VALUES ('3-10', 2, 0, NULL, '  Type ELLIPSE
	Points
	  1 1
	END
	STYLE
		3 10
	END');
INSERT INTO symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def) VALUES ('3-3', 2, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    3 3
  END ');
INSERT INTO symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def) VALUES ('5-3-1-3', 2, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    5 3 1 3
  END ');
INSERT INTO symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def) VALUES ('5-3-1-3-1-3', 2, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    5 3 1 3 1 3
  END ');
INSERT INTO symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def) VALUES ('HORIZONTAL', 1, 0, NULL, 'TYPE VECTOR
POINTS
0 0.5
1 0.5
END');
INSERT INTO symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def) VALUES ('ARROW', 1, 0, NULL, 'TYPE Vector
	FILLED True
	POINTS
	  0 0
		.5 .5
		0 1
		0 0
	END
	ANTIALIAS true
	GAP -10');
INSERT INTO symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def) VALUES ('ARROWBACK', 1, 0, NULL, '	TYPE Vector
	FILLED True
	POINTS
	  1 1
		.5 .5
		1 0
		1 1
	END
	ANTIALIAS true
	GAP -10');
INSERT INTO symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def) VALUES ('1-3', 2, 0, NULL, 'Type ELLIPSE
	Points
	  1 1
	END
	STYLE
		1 3
	END');
INSERT INTO symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def) VALUES ('RANDOM', 10, 0, NULL, '  Type VECTOR
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
		GAP 2');


--
-- TOC entry 7812 (class 0 OID 13243322)
-- Dependencies: 6768
-- Data for Name: symbol_ttf; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7808 (class 0 OID 13243279)
-- Dependencies: 6762
-- Data for Name: theme; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7813 (class 0 OID 13243331)
-- Dependencies: 6770
-- Data for Name: user_group; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7814 (class 0 OID 13243337)
-- Dependencies: 6771
-- Data for Name: usercontext; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7815 (class 0 OID 13243345)
-- Dependencies: 6773
-- Data for Name: users; Type: TABLE DATA; Schema: gisclient_31; Owner: gisclient
--



--
-- TOC entry 7628 (class 2606 OID 13243370)
-- Dependencies: 6717 6717
-- Name: 18n_field_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY i18n_field
    ADD CONSTRAINT "18n_field_pkey" PRIMARY KEY (i18nf_id);


--
-- TOC entry 7553 (class 2606 OID 13243372)
-- Dependencies: 6686 6686 6686
-- Name: catalog_catalog_name_key; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY catalog
    ADD CONSTRAINT catalog_catalog_name_key UNIQUE (catalog_name, project_name);


--
-- TOC entry 7559 (class 2606 OID 13243374)
-- Dependencies: 6687 6687 6687
-- Name: catalog_import_catalog_import_name_key; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY catalog_import
    ADD CONSTRAINT catalog_import_catalog_import_name_key UNIQUE (catalog_import_name, project_name);


--
-- TOC entry 7561 (class 2606 OID 13243376)
-- Dependencies: 6687 6687
-- Name: catalog_import_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY catalog_import
    ADD CONSTRAINT catalog_import_pkey PRIMARY KEY (catalog_import_id);


--
-- TOC entry 7555 (class 2606 OID 13243378)
-- Dependencies: 6686 6686
-- Name: catalog_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY catalog
    ADD CONSTRAINT catalog_pkey PRIMARY KEY (catalog_id);


--
-- TOC entry 7566 (class 2606 OID 13243380)
-- Dependencies: 6688 6688 6688
-- Name: class_layer_id_key; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY class
    ADD CONSTRAINT class_layer_id_key UNIQUE (layer_id, class_name);


--
-- TOC entry 7568 (class 2606 OID 13243382)
-- Dependencies: 6688 6688
-- Name: class_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY class
    ADD CONSTRAINT class_pkey PRIMARY KEY (class_id);


--
-- TOC entry 7572 (class 2606 OID 13243384)
-- Dependencies: 6689 6689
-- Name: classgroup_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY classgroup
    ADD CONSTRAINT classgroup_pkey PRIMARY KEY (classgroup_id);


--
-- TOC entry 7574 (class 2606 OID 13243386)
-- Dependencies: 6690 6690
-- Name: e_charset_encodings_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_charset_encodings
    ADD CONSTRAINT e_charset_encodings_pkey PRIMARY KEY (charset_encodings_id);


--
-- TOC entry 7576 (class 2606 OID 13243388)
-- Dependencies: 6691 6691
-- Name: e_conntype_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_conntype
    ADD CONSTRAINT e_conntype_pkey PRIMARY KEY (conntype_id);


--
-- TOC entry 7578 (class 2606 OID 13243390)
-- Dependencies: 6692 6692
-- Name: e_datatype_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_datatype
    ADD CONSTRAINT e_datatype_pkey PRIMARY KEY (datatype_id);


--
-- TOC entry 7580 (class 2606 OID 13243392)
-- Dependencies: 6693 6693
-- Name: e_fieldformat_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_fieldformat
    ADD CONSTRAINT e_fieldformat_pkey PRIMARY KEY (fieldformat_id);


--
-- TOC entry 7582 (class 2606 OID 13243394)
-- Dependencies: 6694 6694
-- Name: e_fieldtype_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_fieldtype
    ADD CONSTRAINT e_fieldtype_pkey PRIMARY KEY (fieldtype_id);


--
-- TOC entry 7584 (class 2606 OID 13243396)
-- Dependencies: 6695 6695
-- Name: e_filetype_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_filetype
    ADD CONSTRAINT e_filetype_pkey PRIMARY KEY (filetype_id);


--
-- TOC entry 7586 (class 2606 OID 13243398)
-- Dependencies: 6696 6696
-- Name: e_form_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_form
    ADD CONSTRAINT e_form_pkey PRIMARY KEY (id);


--
-- TOC entry 7588 (class 2606 OID 13243400)
-- Dependencies: 6697 6697
-- Name: e_language_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_language
    ADD CONSTRAINT e_language_pkey PRIMARY KEY (language_id);


--
-- TOC entry 7590 (class 2606 OID 13243402)
-- Dependencies: 6698 6698
-- Name: e_layertype_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_layertype
    ADD CONSTRAINT e_layertype_pkey PRIMARY KEY (layertype_id);


--
-- TOC entry 7592 (class 2606 OID 13243404)
-- Dependencies: 6699 6699
-- Name: e_lblposition_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_lblposition
    ADD CONSTRAINT e_lblposition_pkey PRIMARY KEY (lblposition_id);


--
-- TOC entry 7594 (class 2606 OID 13243406)
-- Dependencies: 6700 6700
-- Name: e_legendtype_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_legendtype
    ADD CONSTRAINT e_legendtype_pkey PRIMARY KEY (legendtype_id);


--
-- TOC entry 7596 (class 2606 OID 13243408)
-- Dependencies: 6701 6701
-- Name: e_level_name_key; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_level
    ADD CONSTRAINT e_level_name_key UNIQUE (name);


--
-- TOC entry 7598 (class 2606 OID 13243410)
-- Dependencies: 6701 6701
-- Name: e_livelli_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_level
    ADD CONSTRAINT e_livelli_pkey PRIMARY KEY (id);


--
-- TOC entry 7600 (class 2606 OID 13243412)
-- Dependencies: 6702 6702
-- Name: e_orderby_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_orderby
    ADD CONSTRAINT e_orderby_pkey PRIMARY KEY (orderby_id);


--
-- TOC entry 7602 (class 2606 OID 13243414)
-- Dependencies: 6703 6703
-- Name: e_outputformat_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_outputformat
    ADD CONSTRAINT e_outputformat_pkey PRIMARY KEY (outputformat_id);


--
-- TOC entry 7604 (class 2606 OID 13243416)
-- Dependencies: 6704 6704
-- Name: e_owstype_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_owstype
    ADD CONSTRAINT e_owstype_pkey PRIMARY KEY (owstype_id);


--
-- TOC entry 7606 (class 2606 OID 13243418)
-- Dependencies: 6705 6705
-- Name: e_papersize_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_papersize
    ADD CONSTRAINT e_papersize_pkey PRIMARY KEY (papersize_id);


--
-- TOC entry 7608 (class 2606 OID 13243420)
-- Dependencies: 6706 6706
-- Name: e_qtrelationtype_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_qtrelationtype
    ADD CONSTRAINT e_qtrelationtype_pkey PRIMARY KEY (qtrelationtype_id);


--
-- TOC entry 7610 (class 2606 OID 13243422)
-- Dependencies: 6707 6707
-- Name: e_resultype_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_resultype
    ADD CONSTRAINT e_resultype_pkey PRIMARY KEY (resultype_id);


--
-- TOC entry 7612 (class 2606 OID 13243424)
-- Dependencies: 6708 6708
-- Name: e_searchtype_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_searchtype
    ADD CONSTRAINT e_searchtype_pkey PRIMARY KEY (searchtype_id);


--
-- TOC entry 7614 (class 2606 OID 13243426)
-- Dependencies: 6709 6709
-- Name: e_sizeunits_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_sizeunits
    ADD CONSTRAINT e_sizeunits_pkey PRIMARY KEY (sizeunits_id);


--
-- TOC entry 7616 (class 2606 OID 13243428)
-- Dependencies: 6710 6710
-- Name: e_symbolcategory_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_symbolcategory
    ADD CONSTRAINT e_symbolcategory_pkey PRIMARY KEY (symbolcategory_id);


--
-- TOC entry 7618 (class 2606 OID 13243430)
-- Dependencies: 6711 6711
-- Name: e_tiletype_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_tiletype
    ADD CONSTRAINT e_tiletype_pkey PRIMARY KEY (tiletype_id);


--
-- TOC entry 7551 (class 2606 OID 13243432)
-- Dependencies: 6685 6685
-- Name: filter_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY authfilter
    ADD CONSTRAINT filter_pkey PRIMARY KEY (filter_id);


--
-- TOC entry 7622 (class 2606 OID 13243434)
-- Dependencies: 6714 6714
-- Name: font_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY font
    ADD CONSTRAINT font_pkey PRIMARY KEY (font_name);


--
-- TOC entry 7624 (class 2606 OID 13243436)
-- Dependencies: 6715 6715 6715
-- Name: group_authfilter_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY group_authfilter
    ADD CONSTRAINT group_authfilter_pkey PRIMARY KEY (groupname, filter_id);


--
-- TOC entry 7626 (class 2606 OID 13243438)
-- Dependencies: 6716 6716
-- Name: groups_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY groups
    ADD CONSTRAINT groups_pkey PRIMARY KEY (groupname);


--
-- TOC entry 7635 (class 2606 OID 13243440)
-- Dependencies: 6720 6720 6720
-- Name: layer_authfilter_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY layer_authfilter
    ADD CONSTRAINT layer_authfilter_pkey PRIMARY KEY (layer_id, filter_id);


--
-- TOC entry 7638 (class 2606 OID 13243442)
-- Dependencies: 6721 6721 6721
-- Name: layer_groups_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY layer_groups
    ADD CONSTRAINT layer_groups_pkey PRIMARY KEY (layer_id, groupname);


--
-- TOC entry 7631 (class 2606 OID 13243444)
-- Dependencies: 6719 6719 6719
-- Name: layer_layergroup_id_key; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY layer
    ADD CONSTRAINT layer_layergroup_id_key UNIQUE (layergroup_id, layer_name);


--
-- TOC entry 7633 (class 2606 OID 13243446)
-- Dependencies: 6719 6719
-- Name: layer_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY layer
    ADD CONSTRAINT layer_pkey PRIMARY KEY (layer_id);


--
-- TOC entry 7641 (class 2606 OID 13243448)
-- Dependencies: 6722 6722
-- Name: layergroup_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY layergroup
    ADD CONSTRAINT layergroup_pkey PRIMARY KEY (layergroup_id);


--
-- TOC entry 7643 (class 2606 OID 13243450)
-- Dependencies: 6722 6722 6722
-- Name: layergroup_theme_key; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY layergroup
    ADD CONSTRAINT layergroup_theme_key UNIQUE (theme_id, layergroup_name);


--
-- TOC entry 7646 (class 2606 OID 13243452)
-- Dependencies: 6723 6723
-- Name: link_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY link
    ADD CONSTRAINT link_pkey PRIMARY KEY (link_id);


--
-- TOC entry 7620 (class 2606 OID 13243454)
-- Dependencies: 6712 6712
-- Name: livelli_form_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY form_level
    ADD CONSTRAINT livelli_form_pkey PRIMARY KEY (id);


--
-- TOC entry 7648 (class 2606 OID 13243456)
-- Dependencies: 6724 6724
-- Name: localization_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY localization
    ADD CONSTRAINT localization_pkey PRIMARY KEY (localization_id);


--
-- TOC entry 7657 (class 2606 OID 13243458)
-- Dependencies: 6727 6727 6727
-- Name: mapset_layergroup_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY mapset_layergroup
    ADD CONSTRAINT mapset_layergroup_pkey PRIMARY KEY (mapset_name, layergroup_id);


--
-- TOC entry 7651 (class 2606 OID 13243460)
-- Dependencies: 6726 6726
-- Name: mapset_mapset_name_key; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY mapset
    ADD CONSTRAINT mapset_mapset_name_key UNIQUE (mapset_name);


--
-- TOC entry 7653 (class 2606 OID 13243462)
-- Dependencies: 6726 6726
-- Name: mapset_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY mapset
    ADD CONSTRAINT mapset_pkey PRIMARY KEY (mapset_name);


--
-- TOC entry 7661 (class 2606 OID 13243464)
-- Dependencies: 6729 6729 6729
-- Name: project_admin_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY project_admin
    ADD CONSTRAINT project_admin_pkey PRIMARY KEY (project_name, username);


--
-- TOC entry 7663 (class 2606 OID 13243466)
-- Dependencies: 6730 6730 6730
-- Name: project_languages_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY project_languages
    ADD CONSTRAINT project_languages_pkey PRIMARY KEY (project_name, language_id);


--
-- TOC entry 7659 (class 2606 OID 13243468)
-- Dependencies: 6728 6728
-- Name: project_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY project
    ADD CONSTRAINT project_pkey PRIMARY KEY (project_name);


--
-- TOC entry 7665 (class 2606 OID 13243470)
-- Dependencies: 6731 6731 6731
-- Name: project_srs_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY project_srs
    ADD CONSTRAINT project_srs_pkey PRIMARY KEY (project_name, srid);


--
-- TOC entry 7682 (class 2606 OID 13243472)
-- Dependencies: 6762 6762 6762
-- Name: project_theme_id_key; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY theme
    ADD CONSTRAINT project_theme_id_key UNIQUE (project_name, theme_name);


--
-- TOC entry 7675 (class 2606 OID 13243474)
-- Dependencies: 6734 6734 6734
-- Name: qt_link_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY qtlink
    ADD CONSTRAINT qt_link_pkey PRIMARY KEY (layer_id, link_id);


--
-- TOC entry 7673 (class 2606 OID 13243476)
-- Dependencies: 6733 6733 6733
-- Name: qtfield_groups_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY qtfield_groups
    ADD CONSTRAINT qtfield_groups_pkey PRIMARY KEY (qtfield_id, groupname);


--
-- TOC entry 7669 (class 2606 OID 13243478)
-- Dependencies: 6732 6732
-- Name: qtfield_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY qtfield
    ADD CONSTRAINT qtfield_pkey PRIMARY KEY (qtfield_id);


--
-- TOC entry 7671 (class 2606 OID 13243480)
-- Dependencies: 6732 6732 6732
-- Name: qtfield_unique_key; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY qtfield
    ADD CONSTRAINT qtfield_unique_key UNIQUE (layer_id, field_header);


--
-- TOC entry 7679 (class 2606 OID 13243482)
-- Dependencies: 6735 6735
-- Name: qtrelation_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY qtrelation
    ADD CONSTRAINT qtrelation_pkey PRIMARY KEY (qtrelation_id);


--
-- TOC entry 7710 (class 2606 OID 13243749)
-- Dependencies: 6777 6777 6777
-- Name: selgroup_layer_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: r3gis; Tablespace: 
--

ALTER TABLE ONLY selgroup_layer
    ADD CONSTRAINT selgroup_layer_pkey PRIMARY KEY (layer_id, selgroup_id);


--
-- TOC entry 7687 (class 2606 OID 13243486)
-- Dependencies: 6765 6765
-- Name: selgroup_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY selgroup
    ADD CONSTRAINT selgroup_pkey PRIMARY KEY (selgroup_id);


--
-- TOC entry 7689 (class 2606 OID 13243488)
-- Dependencies: 6765 6765 6765
-- Name: selgroup_selgroup_name_key; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY selgroup
    ADD CONSTRAINT selgroup_selgroup_name_key UNIQUE (selgroup_name, project_name);


--
-- TOC entry 7692 (class 2606 OID 13243490)
-- Dependencies: 6766 6766 6766
-- Name: style_class_id_key; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY style
    ADD CONSTRAINT style_class_id_key UNIQUE (class_id, style_name);


--
-- TOC entry 7694 (class 2606 OID 13243492)
-- Dependencies: 6766 6766
-- Name: style_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY style
    ADD CONSTRAINT style_pkey PRIMARY KEY (style_id);


--
-- TOC entry 7698 (class 2606 OID 13243494)
-- Dependencies: 6767 6767
-- Name: symbol_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY symbol
    ADD CONSTRAINT symbol_pkey PRIMARY KEY (symbol_name);


--
-- TOC entry 7702 (class 2606 OID 13243496)
-- Dependencies: 6768 6768 6768
-- Name: symbol_ttf_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY symbol_ttf
    ADD CONSTRAINT symbol_ttf_pkey PRIMARY KEY (symbol_ttf_name, font_name);


--
-- TOC entry 7684 (class 2606 OID 13243498)
-- Dependencies: 6762 6762
-- Name: theme_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY theme
    ADD CONSTRAINT theme_pkey PRIMARY KEY (theme_id);


--
-- TOC entry 7704 (class 2606 OID 13243500)
-- Dependencies: 6770 6770 6770
-- Name: user_group_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY user_group
    ADD CONSTRAINT user_group_pkey PRIMARY KEY (username, groupname);


--
-- TOC entry 7708 (class 2606 OID 13243502)
-- Dependencies: 6773 6773
-- Name: user_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY users
    ADD CONSTRAINT user_pkey PRIMARY KEY (username);


--
-- TOC entry 7706 (class 2606 OID 13243504)
-- Dependencies: 6771 6771
-- Name: usercontext_pkey; Type: CONSTRAINT; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY usercontext
    ADD CONSTRAINT usercontext_pkey PRIMARY KEY (usercontext_id);


--
-- TOC entry 7676 (class 1259 OID 13243505)
-- Dependencies: 6735
-- Name: fki_; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_ ON qtrelation USING btree (layer_id);


--
-- TOC entry 7556 (class 1259 OID 13243506)
-- Dependencies: 6686
-- Name: fki_catalog_conntype_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_catalog_conntype_fkey ON catalog USING btree (connection_type);


--
-- TOC entry 7562 (class 1259 OID 13243507)
-- Dependencies: 6687
-- Name: fki_catalog_import_from_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_catalog_import_from_fkey ON catalog_import USING btree (catalog_from);


--
-- TOC entry 7563 (class 1259 OID 13243508)
-- Dependencies: 6687
-- Name: fki_catalog_import_project_name_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_catalog_import_project_name_fkey ON catalog_import USING btree (project_name);


--
-- TOC entry 7564 (class 1259 OID 13243509)
-- Dependencies: 6687
-- Name: fki_catalog_import_to_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_catalog_import_to_fkey ON catalog_import USING btree (catalog_to);


--
-- TOC entry 7557 (class 1259 OID 13243510)
-- Dependencies: 6686
-- Name: fki_catalog_project_name_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_catalog_project_name_fkey ON catalog USING btree (project_name);


--
-- TOC entry 7569 (class 1259 OID 13243511)
-- Dependencies: 6688
-- Name: fki_class_layer_id_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_class_layer_id_fkey ON class USING btree (layer_id);


--
-- TOC entry 7636 (class 1259 OID 13243512)
-- Dependencies: 6721
-- Name: fki_layer_id; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_layer_id ON layer_groups USING btree (layer_id);


--
-- TOC entry 7629 (class 1259 OID 13243513)
-- Dependencies: 6719
-- Name: fki_layer_layergroup_id; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_layer_layergroup_id ON layer USING btree (layergroup_id);


--
-- TOC entry 7639 (class 1259 OID 13243514)
-- Dependencies: 6722
-- Name: fki_layergroup_theme_id; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_layergroup_theme_id ON layergroup USING btree (theme_id);


--
-- TOC entry 7644 (class 1259 OID 13243515)
-- Dependencies: 6723
-- Name: fki_link_project_name_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_link_project_name_fkey ON link USING btree (project_name);


--
-- TOC entry 7654 (class 1259 OID 13243516)
-- Dependencies: 6727
-- Name: fki_mapset_layergroup_layergroup_id_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_mapset_layergroup_layergroup_id_fkey ON mapset_layergroup USING btree (layergroup_id);


--
-- TOC entry 7655 (class 1259 OID 13243517)
-- Dependencies: 6727
-- Name: fki_mapset_layergroup_mapset_name_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_mapset_layergroup_mapset_name_fkey ON mapset_layergroup USING btree (mapset_name);


--
-- TOC entry 7649 (class 1259 OID 13243518)
-- Dependencies: 6726
-- Name: fki_mapset_project_name_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_mapset_project_name_fkey ON mapset USING btree (project_name);


--
-- TOC entry 7680 (class 1259 OID 13243519)
-- Dependencies: 6762
-- Name: fki_project_theme_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_project_theme_fkey ON theme USING btree (project_name);


--
-- TOC entry 7666 (class 1259 OID 13243520)
-- Dependencies: 6732
-- Name: fki_qtfield_fieldtype_id_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_qtfield_fieldtype_id_fkey ON qtfield USING btree (fieldtype_id);


--
-- TOC entry 7667 (class 1259 OID 13243521)
-- Dependencies: 6732
-- Name: fki_qtfields_layer; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_qtfields_layer ON qtfield USING btree (layer_id);


--
-- TOC entry 7677 (class 1259 OID 13243522)
-- Dependencies: 6735
-- Name: fki_qtrelation_catalog_id_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_qtrelation_catalog_id_fkey ON qtrelation USING btree (catalog_id);


--
-- TOC entry 7685 (class 1259 OID 13243523)
-- Dependencies: 6765
-- Name: fki_selgroup_project_name_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_selgroup_project_name_fkey ON selgroup USING btree (project_name);


--
-- TOC entry 7690 (class 1259 OID 13243524)
-- Dependencies: 6766
-- Name: fki_style_class_id_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_style_class_id_fkey ON style USING btree (class_id);


--
-- TOC entry 7695 (class 1259 OID 13243525)
-- Dependencies: 6767
-- Name: fki_symbol_icontype_id_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_symbol_icontype_id_fkey ON symbol USING btree (icontype);


--
-- TOC entry 7696 (class 1259 OID 13243526)
-- Dependencies: 6767
-- Name: fki_symbol_symbolcategory_id_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_symbol_symbolcategory_id_fkey ON symbol USING btree (symbolcategory_id);


--
-- TOC entry 7570 (class 1259 OID 13243527)
-- Dependencies: 6688 6688
-- Name: fki_symbol_ttf_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_symbol_ttf_fkey ON class USING btree (symbol_ttf_name, label_font);


--
-- TOC entry 7699 (class 1259 OID 13243528)
-- Dependencies: 6768
-- Name: fki_symbol_ttf_font_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_symbol_ttf_font_fkey ON symbol_ttf USING btree (font_name);


--
-- TOC entry 7700 (class 1259 OID 13243529)
-- Dependencies: 6768
-- Name: fki_symbol_ttf_symbolcategory_id_fkey; Type: INDEX; Schema: gisclient_31; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_symbol_ttf_symbolcategory_id_fkey ON symbol_ttf USING btree (symbolcategory_id);


--
-- TOC entry 7752 (class 2620 OID 13243530)
-- Dependencies: 6686 1055
-- Name: chk_catalog; Type: TRIGGER; Schema: gisclient_31; Owner: gisclient
--

CREATE TRIGGER chk_catalog
    BEFORE INSERT OR UPDATE ON catalog
    FOR EACH ROW
    EXECUTE PROCEDURE check_catalog();


--
-- TOC entry 7753 (class 2620 OID 13243531)
-- Dependencies: 6688 1056
-- Name: chk_class; Type: TRIGGER; Schema: gisclient_31; Owner: gisclient
--

CREATE TRIGGER chk_class
    BEFORE INSERT OR UPDATE ON class
    FOR EACH ROW
    EXECUTE PROCEDURE check_class();


--
-- TOC entry 7758 (class 2620 OID 13243532)
-- Dependencies: 6735 1061
-- Name: delete_qtrelation; Type: TRIGGER; Schema: gisclient_31; Owner: gisclient
--

CREATE TRIGGER delete_qtrelation
    AFTER DELETE ON qtrelation
    FOR EACH ROW
    EXECUTE PROCEDURE delete_qtrelation();


--
-- TOC entry 7754 (class 2620 OID 13243533)
-- Dependencies: 6701 1068
-- Name: depth; Type: TRIGGER; Schema: gisclient_31; Owner: gisclient
--

CREATE TRIGGER depth
    AFTER INSERT OR UPDATE ON e_level
    FOR EACH ROW
    EXECUTE PROCEDURE set_depth();


--
-- TOC entry 7756 (class 2620 OID 13243534)
-- Dependencies: 6721 1069
-- Name: layername; Type: TRIGGER; Schema: gisclient_31; Owner: gisclient
--

CREATE TRIGGER layername
    BEFORE INSERT OR UPDATE ON layer_groups
    FOR EACH ROW
    EXECUTE PROCEDURE set_layer_name();


--
-- TOC entry 7755 (class 2620 OID 13243535)
-- Dependencies: 6701 1070
-- Name: leaf; Type: TRIGGER; Schema: gisclient_31; Owner: gisclient
--

CREATE TRIGGER leaf
    AFTER INSERT OR UPDATE ON e_level
    FOR EACH ROW
    EXECUTE PROCEDURE set_leaf();


--
-- TOC entry 7757 (class 2620 OID 13243536)
-- Dependencies: 6722 1064
-- Name: move_layergroup; Type: TRIGGER; Schema: gisclient_31; Owner: gisclient
--

CREATE TRIGGER move_layergroup
    AFTER UPDATE ON layergroup
    FOR EACH ROW
    EXECUTE PROCEDURE move_layergroup();


--
-- TOC entry 7759 (class 2620 OID 13243537)
-- Dependencies: 6773 1062
-- Name: set_encpwd; Type: TRIGGER; Schema: gisclient_31; Owner: gisclient
--

CREATE TRIGGER set_encpwd
    BEFORE INSERT OR UPDATE ON users
    FOR EACH ROW
    EXECUTE PROCEDURE enc_pwd();


--
-- TOC entry 7712 (class 2606 OID 13243538)
-- Dependencies: 7575 6691 6686
-- Name: catalog_conntype_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY catalog
    ADD CONSTRAINT catalog_conntype_fkey FOREIGN KEY (connection_type) REFERENCES e_conntype(conntype_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7713 (class 2606 OID 13243543)
-- Dependencies: 7658 6728 6687
-- Name: catalog_import_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY catalog_import
    ADD CONSTRAINT catalog_import_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7711 (class 2606 OID 13243548)
-- Dependencies: 7658 6728 6686
-- Name: catalog_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY catalog
    ADD CONSTRAINT catalog_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7714 (class 2606 OID 13243553)
-- Dependencies: 7632 6719 6688
-- Name: class_layer_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY class
    ADD CONSTRAINT class_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES layer(layer_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7715 (class 2606 OID 13243558)
-- Dependencies: 7597 6701 6696
-- Name: e_form_level_destination_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY e_form
    ADD CONSTRAINT e_form_level_destination_fkey FOREIGN KEY (level_destination) REFERENCES e_level(id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7716 (class 2606 OID 13243563)
-- Dependencies: 7597 6701 6701
-- Name: e_level_parent_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY e_level
    ADD CONSTRAINT e_level_parent_id_fkey FOREIGN KEY (parent_id) REFERENCES e_level(id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7718 (class 2606 OID 13243568)
-- Dependencies: 7585 6696 6712
-- Name: form_level_form_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY form_level
    ADD CONSTRAINT form_level_form_fkey FOREIGN KEY (form) REFERENCES e_form(id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7717 (class 2606 OID 13243573)
-- Dependencies: 7597 6701 6712
-- Name: form_level_level_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY form_level
    ADD CONSTRAINT form_level_level_fkey FOREIGN KEY (level) REFERENCES e_level(id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7720 (class 2606 OID 13243578)
-- Dependencies: 7550 6685 6715
-- Name: group_authfilter_filter_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY group_authfilter
    ADD CONSTRAINT group_authfilter_filter_id_fkey FOREIGN KEY (filter_id) REFERENCES authfilter(filter_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7719 (class 2606 OID 13243583)
-- Dependencies: 7625 6716 6715
-- Name: group_authfilter_gropuname_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY group_authfilter
    ADD CONSTRAINT group_authfilter_gropuname_fkey FOREIGN KEY (groupname) REFERENCES groups(groupname) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7729 (class 2606 OID 13243588)
-- Dependencies: 7627 6717 6724
-- Name: i18nfield_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY localization
    ADD CONSTRAINT i18nfield_fkey FOREIGN KEY (i18nf_id) REFERENCES i18n_field(i18nf_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7728 (class 2606 OID 13243593)
-- Dependencies: 7587 6697 6724
-- Name: language_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY localization
    ADD CONSTRAINT language_id_fkey FOREIGN KEY (language_id) REFERENCES e_language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7734 (class 2606 OID 13243598)
-- Dependencies: 7658 6728 6730
-- Name: language_id_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY project_languages
    ADD CONSTRAINT language_id_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7723 (class 2606 OID 13243603)
-- Dependencies: 7550 6685 6720
-- Name: layer_authfilter_filter_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY layer_authfilter
    ADD CONSTRAINT layer_authfilter_filter_id_fkey FOREIGN KEY (filter_id) REFERENCES authfilter(filter_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7722 (class 2606 OID 13243608)
-- Dependencies: 7632 6719 6720
-- Name: layer_authfilter_layer_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY layer_authfilter
    ADD CONSTRAINT layer_authfilter_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES layer(layer_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7724 (class 2606 OID 13243613)
-- Dependencies: 7632 6719 6721
-- Name: layer_groups_layer_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY layer_groups
    ADD CONSTRAINT layer_groups_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES layer(layer_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7721 (class 2606 OID 13243618)
-- Dependencies: 7640 6722 6719
-- Name: layer_layergroup_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY layer
    ADD CONSTRAINT layer_layergroup_id_fkey FOREIGN KEY (layergroup_id) REFERENCES layergroup(layergroup_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7725 (class 2606 OID 13243623)
-- Dependencies: 7683 6762 6722
-- Name: layergroup_theme_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY layergroup
    ADD CONSTRAINT layergroup_theme_id_fkey FOREIGN KEY (theme_id) REFERENCES theme(theme_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7726 (class 2606 OID 13243628)
-- Dependencies: 7658 6728 6723
-- Name: link_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY link
    ADD CONSTRAINT link_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7727 (class 2606 OID 13243633)
-- Dependencies: 7658 6728 6724
-- Name: localization_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY localization
    ADD CONSTRAINT localization_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7732 (class 2606 OID 13243638)
-- Dependencies: 7640 6722 6727
-- Name: mapset_layergroup_layergroup_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY mapset_layergroup
    ADD CONSTRAINT mapset_layergroup_layergroup_id_fkey FOREIGN KEY (layergroup_id) REFERENCES layergroup(layergroup_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7731 (class 2606 OID 13243643)
-- Dependencies: 7650 6726 6727
-- Name: mapset_layergroup_mapset_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY mapset_layergroup
    ADD CONSTRAINT mapset_layergroup_mapset_name_fkey FOREIGN KEY (mapset_name) REFERENCES mapset(mapset_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7730 (class 2606 OID 13243648)
-- Dependencies: 7658 6728 6726
-- Name: mapset_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY mapset
    ADD CONSTRAINT mapset_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7735 (class 2606 OID 13243653)
-- Dependencies: 7658 6728 6731
-- Name: project_srs_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY project_srs
    ADD CONSTRAINT project_srs_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7737 (class 2606 OID 13243658)
-- Dependencies: 7581 6694 6732
-- Name: qtfield_fieldtype_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY qtfield
    ADD CONSTRAINT qtfield_fieldtype_id_fkey FOREIGN KEY (fieldtype_id) REFERENCES e_fieldtype(fieldtype_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7738 (class 2606 OID 13243663)
-- Dependencies: 7668 6732 6733
-- Name: qtfield_groups_qtfield_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY qtfield_groups
    ADD CONSTRAINT qtfield_groups_qtfield_id_fkey FOREIGN KEY (qtfield_id) REFERENCES qtfield(qtfield_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7736 (class 2606 OID 13243668)
-- Dependencies: 7632 6719 6732
-- Name: qtfield_layer_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY qtfield
    ADD CONSTRAINT qtfield_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES layer(layer_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7740 (class 2606 OID 13243673)
-- Dependencies: 7554 6686 6735
-- Name: qtrelation_catalog_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY qtrelation
    ADD CONSTRAINT qtrelation_catalog_fkey FOREIGN KEY (catalog_id) REFERENCES catalog(catalog_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7739 (class 2606 OID 13243678)
-- Dependencies: 7632 6719 6735
-- Name: qtrelation_layer_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY qtrelation
    ADD CONSTRAINT qtrelation_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES layer(layer_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7751 (class 2606 OID 13243750)
-- Dependencies: 7632 6719 6777
-- Name: selgroup_layer_layer_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: r3gis
--

ALTER TABLE ONLY selgroup_layer
    ADD CONSTRAINT selgroup_layer_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES layer(layer_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7750 (class 2606 OID 13243755)
-- Dependencies: 7686 6765 6777
-- Name: selgroup_layer_selgroup_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: r3gis
--

ALTER TABLE ONLY selgroup_layer
    ADD CONSTRAINT selgroup_layer_selgroup_fkey FOREIGN KEY (selgroup_id) REFERENCES selgroup(selgroup_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7742 (class 2606 OID 13243693)
-- Dependencies: 7658 6728 6765
-- Name: selgroup_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY selgroup
    ADD CONSTRAINT selgroup_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7743 (class 2606 OID 13243698)
-- Dependencies: 7567 6688 6766
-- Name: style_class_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY style
    ADD CONSTRAINT style_class_id_fkey FOREIGN KEY (class_id) REFERENCES class(class_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7744 (class 2606 OID 13243703)
-- Dependencies: 7615 6710 6767
-- Name: symbol_symbolcategory_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY symbol
    ADD CONSTRAINT symbol_symbolcategory_id_fkey FOREIGN KEY (symbolcategory_id) REFERENCES e_symbolcategory(symbolcategory_id) MATCH FULL ON UPDATE CASCADE;


--
-- TOC entry 7746 (class 2606 OID 13243708)
-- Dependencies: 7621 6714 6768
-- Name: symbol_ttf_font_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY symbol_ttf
    ADD CONSTRAINT symbol_ttf_font_fkey FOREIGN KEY (font_name) REFERENCES font(font_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7745 (class 2606 OID 13243713)
-- Dependencies: 7615 6710 6768
-- Name: symbol_ttf_symbolcategory_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY symbol_ttf
    ADD CONSTRAINT symbol_ttf_symbolcategory_id_fkey FOREIGN KEY (symbolcategory_id) REFERENCES e_symbolcategory(symbolcategory_id) MATCH FULL ON UPDATE CASCADE;


--
-- TOC entry 7741 (class 2606 OID 13243718)
-- Dependencies: 7658 6728 6762
-- Name: theme_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY theme
    ADD CONSTRAINT theme_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7748 (class 2606 OID 13243723)
-- Dependencies: 7625 6716 6770
-- Name: user_group_groupname_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY user_group
    ADD CONSTRAINT user_group_groupname_fkey FOREIGN KEY (groupname) REFERENCES groups(groupname) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7747 (class 2606 OID 13243728)
-- Dependencies: 7707 6773 6770
-- Name: user_group_username_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY user_group
    ADD CONSTRAINT user_group_username_fkey FOREIGN KEY (username) REFERENCES users(username) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7749 (class 2606 OID 13243733)
-- Dependencies: 7650 6726 6771
-- Name: usercontext_mapset_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY usercontext
    ADD CONSTRAINT usercontext_mapset_name_fkey FOREIGN KEY (mapset_name) REFERENCES mapset(mapset_name) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 7733 (class 2606 OID 13243738)
-- Dependencies: 7658 6728 6729
-- Name: username_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_31; Owner: gisclient
--

ALTER TABLE ONLY project_admin
    ADD CONSTRAINT username_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


-- Completed on 2011-12-20 13:38:45

--
-- PostgreSQL database dump complete
--

