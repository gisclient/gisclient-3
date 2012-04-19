--
-- PostgreSQL database dump
--

-- Dumped from database version 8.4.7
-- Dumped by pg_dump version 9.0.3
-- Started on 2011-09-08 15:18:27

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

--
-- TOC entry 6 (class 2615 OID 12134104)
-- Name: gisclient_30; Type: SCHEMA; Schema: -; Owner: gisclient
--

CREATE SCHEMA gisclient_30;


ALTER SCHEMA gisclient_30 OWNER TO gisclient;

SET search_path = gisclient_30, pg_catalog;

--
-- TOC entry 1112 (class 1247 OID 12134107)
-- Dependencies: 6 2612
-- Name: qt_selgroup_type; Type: TYPE; Schema: gisclient_30; Owner: gisclient
--

CREATE TYPE qt_selgroup_type AS (
	qt_selgroup_id integer,
	qt_id integer,
	selgroup_id integer,
	presente integer,
	project_id integer
);


ALTER TYPE gisclient_30.qt_selgroup_type OWNER TO gisclient;

--
-- TOC entry 1130 (class 1247 OID 12134110)
-- Dependencies: 6 2613
-- Name: slgrp_qt; Type: TYPE; Schema: gisclient_30; Owner: gisclient
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


ALTER TYPE gisclient_30.slgrp_qt OWNER TO gisclient;

--
-- TOC entry 1139 (class 1247 OID 12134113)
-- Dependencies: 6 2614
-- Name: tree; Type: TYPE; Schema: gisclient_30; Owner: gisclient
--

CREATE TYPE tree AS (
	id integer,
	name character varying,
	lvl_id integer,
	lvl_name character varying
);


ALTER TYPE gisclient_30.tree OWNER TO gisclient;

--
-- TOC entry 794 (class 1255 OID 12134114)
-- Dependencies: 1373 6
-- Name: check_catalog(); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
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


ALTER FUNCTION gisclient_30.check_catalog() OWNER TO gisclient;

--
-- TOC entry 803 (class 1255 OID 12134115)
-- Dependencies: 6 1373
-- Name: check_class(); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
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


ALTER FUNCTION gisclient_30.check_class() OWNER TO gisclient;

--
-- TOC entry 804 (class 1255 OID 12134116)
-- Dependencies: 6 1373
-- Name: check_layergroup(); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
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


ALTER FUNCTION gisclient_30.check_layergroup() OWNER TO gisclient;

--
-- TOC entry 806 (class 1255 OID 12134117)
-- Dependencies: 6 1373
-- Name: check_mapset(); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
--

CREATE FUNCTION check_mapset() RETURNS trigger
    LANGUAGE plpgsql
    AS $_$
DECLARE
	ext varchar;
	presente integer;
BEGIN

	new.mapset_name:=regexp_replace(trim(new.mapset_name),'([\t ]+)','_','g');
	select coalesce(project_extent,'') into ext from gisclient_30.project where project_name=new.project_name;
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


ALTER FUNCTION gisclient_30.check_mapset() OWNER TO gisclient;

--
-- TOC entry 807 (class 1255 OID 12134118)
-- Dependencies: 6 1373
-- Name: check_project(); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
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
	sk:='gisclient_30';	
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


ALTER FUNCTION gisclient_30.check_project() OWNER TO gisclient;

--
-- TOC entry 808 (class 1255 OID 12134119)
-- Dependencies: 6 1373
-- Name: delete_qt(); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
--

CREATE FUNCTION delete_qt() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	delete from gisclient_30.qtfield where qt_id=old.qt_id;
	return old;
END
$$;


ALTER FUNCTION gisclient_30.delete_qt() OWNER TO gisclient;

--
-- TOC entry 809 (class 1255 OID 12134120)
-- Dependencies: 6 1373
-- Name: delete_qtrelation(); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
--

CREATE FUNCTION delete_qtrelation() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	delete from gisclient_30.qtfield where qtrelation_id=old.qtrelation_id;
	return old;
END
$$;


ALTER FUNCTION gisclient_30.delete_qtrelation() OWNER TO gisclient;

--
-- TOC entry 810 (class 1255 OID 12134121)
-- Dependencies: 6 1373
-- Name: enc_pwd(); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
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


ALTER FUNCTION gisclient_30.enc_pwd() OWNER TO gisclient;

--
-- TOC entry 811 (class 1255 OID 12134122)
-- Dependencies: 1139 1373 6
-- Name: gw_findtree(integer, character varying); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
--

CREATE FUNCTION gw_findtree(id integer, lvl character varying) RETURNS SETOF tree
    LANGUAGE plpgsql IMMUTABLE
    AS $$
DECLARE
	rec record;
	t gisclient_30.tree;
	i integer;
	d integer;
BEGIN
	select into d coalesce(depth,-1) from gisclient_30.e_level where name=lvl;
	if (d=-1) then
		raise exception 'Livello % non esistente',lvl;
	end if;
	for i in reverse d..1 loop
		return next t;
	end loop;
	
END
$$;


ALTER FUNCTION gisclient_30.gw_findtree(id integer, lvl character varying) OWNER TO gisclient;

--
-- TOC entry 812 (class 1255 OID 12134123)
-- Dependencies: 1373 6
-- Name: move_layergroup(); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
--

CREATE FUNCTION move_layergroup() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	if(new.theme_id<>old.theme_id) then
		update gisclient_30.qt set theme_id=new.theme_id where qt_id in (select distinct qt_id from gisclient_30.qt inner join gisclient_30.layer using(layer_id) inner join gisclient_30.layergroup using(layergroup_id) where layergroup_id=new.layergroup_id);
	end if;
	return new;
END
$$;


ALTER FUNCTION gisclient_30.move_layergroup() OWNER TO gisclient;

--
-- TOC entry 813 (class 1255 OID 12134124)
-- Dependencies: 6 1373
-- Name: new_pkey(character varying, character varying); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
--

CREATE FUNCTION new_pkey(tab character varying, id_fld character varying) RETURNS integer
    LANGUAGE plpgsql
    AS $$
declare
    newid integer;
	sk varchar;
	query varchar;
begin
	sk:='gisclient_30';
	query:='select '||sk||'.new_pkey('''||tab||''','''||id_fld||''',0)';
	execute query into newid;
	return newid;
end
$$;


ALTER FUNCTION gisclient_30.new_pkey(tab character varying, id_fld character varying) OWNER TO gisclient;

--
-- TOC entry 814 (class 1255 OID 12134125)
-- Dependencies: 1373 6
-- Name: new_pkey(character varying, character varying, integer); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
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
	sk:='gisclient_30';
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


ALTER FUNCTION gisclient_30.new_pkey(tab character varying, id_fld character varying, st integer) OWNER TO gisclient;

--
-- TOC entry 815 (class 1255 OID 12134126)
-- Dependencies: 6 1373
-- Name: new_pkey(character varying, character varying, character varying); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
--

CREATE FUNCTION new_pkey(sk character varying, tab character varying, id_fld character varying) RETURNS integer
    LANGUAGE plpgsql
    AS $$
declare
    newid integer;
begin
	select gisclient_30.new_pkey(sk ,tab,id_fld,0) into newid; 
	return newid;
end
$$;


ALTER FUNCTION gisclient_30.new_pkey(sk character varying, tab character varying, id_fld character varying) OWNER TO gisclient;

--
-- TOC entry 816 (class 1255 OID 12134127)
-- Dependencies: 6 1373
-- Name: new_pkey(character varying, character varying, character varying, integer); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
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


ALTER FUNCTION gisclient_30.new_pkey(sk character varying, tab character varying, id_fld character varying, st integer) OWNER TO gisclient;

--
-- TOC entry 817 (class 1255 OID 12134128)
-- Dependencies: 1373 6
-- Name: new_pkey_varchar(character varying, character varying, character varying); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
--

CREATE FUNCTION new_pkey_varchar(tb character varying, fld character varying, val character varying) RETURNS character varying
    LANGUAGE plpgsql IMMUTABLE
    AS $_$
DECLARE
	query text;
	presente integer;
	newval varchar;
BEGIN
query:='select count(*) from gisclient_30.'||tb||' where '||fld||'='''||val||''';';
execute query into presente;
if(presente>0) then
	query:='select map||(max(newindex)+1)::varchar from (select regexp_replace('||fld||',''([0-9]+)$'','''') as map,case when(regexp_replace('||fld||',''^([A-z_]+)'','''')='''') then 0 else regexp_replace('||fld||',''^([A-z_]+)'','''')::integer end as newindex from gisclient_30.'||tb||' where '''||val||''' ~* regexp_replace('||fld||',''([0-9]+)$'','''')) X group by map;';
	execute query into newval;
	return newval;
else
	return val;
end if;
END
$_$;


ALTER FUNCTION gisclient_30.new_pkey_varchar(tb character varying, fld character varying, val character varying) OWNER TO gisclient;

--
-- TOC entry 818 (class 1255 OID 12134129)
-- Dependencies: 6 1373
-- Name: rm_project_groups(); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
--

CREATE FUNCTION rm_project_groups() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE

BEGIN
	delete from gisclient_30.mapset_groups where mapset_name in (select distinct mapset_name from gisclient_30.mapset where project_name=old.project_name) and group_name=old.group_name;
	return old;
END
$$;


ALTER FUNCTION gisclient_30.rm_project_groups() OWNER TO gisclient;

--
-- TOC entry 819 (class 1255 OID 12134130)
-- Dependencies: 6 1373
-- Name: set_depth(); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
--

CREATE FUNCTION set_depth() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	if (TG_OP='INSERT') then
		update gisclient_30.e_level set depth=(select coalesce(depth+1,0) from gisclient_30.e_level where id=new.parent_id) where id=new.id;
	elseif(new.parent_id<>coalesce(old.parent_id,-1)) then
		update gisclient_30.e_level set depth=(select coalesce(depth+1,0) from gisclient_30.e_level where id=new.parent_id) where id=new.id;
	end if;
	return new;
END
$$;


ALTER FUNCTION gisclient_30.set_depth() OWNER TO gisclient;

--
-- TOC entry 805 (class 1255 OID 12134131)
-- Dependencies: 6 1373
-- Name: set_layer_name(); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
--

CREATE FUNCTION set_layer_name() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	select into new.layer_name layer_name from gisclient_30.layer where layer_id=new.layer_id;
	return new;
END
$$;


ALTER FUNCTION gisclient_30.set_layer_name() OWNER TO gisclient;

--
-- TOC entry 820 (class 1255 OID 12134132)
-- Dependencies: 1373 6
-- Name: set_leaf(); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
--

CREATE FUNCTION set_leaf() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	if(TG_OP='INSERT') then
		update gisclient_30.e_level X set leaf=(select case when (count(parent_id)>0) then 0 else 1 end from gisclient_30.e_level where parent_id=X.id);
	elsif (new.parent_id<> coalesce(old.parent_id,-1)) then
		update gisclient_30.e_level X set leaf=(select case when (count(parent_id)>0) then 0 else 1 end from gisclient_30.e_level where parent_id=X.id);
	end if;
	return new;
END
$$;


ALTER FUNCTION gisclient_30.set_leaf() OWNER TO gisclient;

--
-- TOC entry 821 (class 1255 OID 12134133)
-- Dependencies: 6 1373
-- Name: set_map_extent(); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
--

CREATE FUNCTION set_map_extent() RETURNS trigger
    LANGUAGE plpgsql
    AS $_$
DECLARE
	ext varchar;
BEGIN

	new.mapset_name:=regexp_replace(trim(new.mapset_name),'([\t ]+)','_','g');
	select coalesce(project_extent,'') into ext from gisclient_30.project where project_name=new.project_name;
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


ALTER FUNCTION gisclient_30.set_map_extent() OWNER TO gisclient;

--
-- TOC entry 822 (class 1255 OID 12134134)
-- Dependencies: 6 1373
-- Name: style_name(); Type: FUNCTION; Schema: gisclient_30; Owner: gisclient
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
		select into rec * from gisclient_30.symbol where symbol_name=new.symbol_name;
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
			SELECT INTO num count(*)+1 FROM gisclient_30.style WHERE class_id=new.class_id and style_name ~* 'Stile ([0-9]+)';
		end if;
		new.style_name:='Stile '||num::varchar;
	end if;
	return new;
END
$_$;


ALTER FUNCTION gisclient_30.style_name() OWNER TO gisclient;

--
-- TOC entry 2615 (class 1259 OID 12134136)
-- Dependencies: 6
-- Name: catalog; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE catalog (
    catalog_id integer NOT NULL,
    catalog_name character varying NOT NULL,
    project_name character varying NOT NULL,
    connection_type smallint NOT NULL,
    catalog_path character varying NOT NULL,
    catalog_url character varying,
    catalog_description text
);


ALTER TABLE gisclient_30.catalog OWNER TO gisclient;

--
-- TOC entry 2616 (class 1259 OID 12134142)
-- Dependencies: 6
-- Name: catalog_import; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE catalog_import (
    catalog_import_id integer NOT NULL,
    project_name character varying NOT NULL,
    catalog_import_name text,
    catalog_from integer NOT NULL,
    catalog_to integer NOT NULL,
    catalog_import_description text
);


ALTER TABLE gisclient_30.catalog_import OWNER TO gisclient;

--
-- TOC entry 2617 (class 1259 OID 12134148)
-- Dependencies: 3035 3036 3037 3038 3039 3040 6
-- Name: class; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE class (
    class_id integer NOT NULL,
    layer_id integer,
    class_name character varying NOT NULL,
    class_title character varying,
    class_link character varying,
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


ALTER TABLE gisclient_30.class OWNER TO gisclient;

--
-- TOC entry 2618 (class 1259 OID 12134160)
-- Dependencies: 3041 3042 3043 6
-- Name: classgroup; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_30.classgroup OWNER TO gisclient;

--
-- TOC entry 2619 (class 1259 OID 12134169)
-- Dependencies: 6
-- Name: e_charset_encodings; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_charset_encodings (
    charset_encodings_id integer NOT NULL,
    charset_encodings_name character varying NOT NULL,
    charset_encodings_order smallint
);


ALTER TABLE gisclient_30.e_charset_encodings OWNER TO gisclient;

--
-- TOC entry 2620 (class 1259 OID 12134175)
-- Dependencies: 6
-- Name: e_conntype; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_conntype (
    conntype_id smallint NOT NULL,
    conntype_name character varying NOT NULL,
    conntype_order smallint
);


ALTER TABLE gisclient_30.e_conntype OWNER TO gisclient;

--
-- TOC entry 2621 (class 1259 OID 12134181)
-- Dependencies: 6
-- Name: e_datatype; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_datatype (
    datatype_id smallint NOT NULL,
    datatype_name character varying NOT NULL,
    datatype_order smallint
);


ALTER TABLE gisclient_30.e_datatype OWNER TO gisclient;

--
-- TOC entry 2622 (class 1259 OID 12134187)
-- Dependencies: 6
-- Name: e_fieldformat; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_fieldformat (
    fieldformat_id integer NOT NULL,
    fieldformat_name character varying NOT NULL,
    fieldformat_format character varying NOT NULL,
    fieldformat_order smallint
);


ALTER TABLE gisclient_30.e_fieldformat OWNER TO gisclient;

--
-- TOC entry 2623 (class 1259 OID 12134193)
-- Dependencies: 6
-- Name: e_fieldtype; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_fieldtype (
    fieldtype_id smallint NOT NULL,
    fieldtype_name character varying NOT NULL,
    fieldtype_order smallint
);


ALTER TABLE gisclient_30.e_fieldtype OWNER TO gisclient;

--
-- TOC entry 2624 (class 1259 OID 12134199)
-- Dependencies: 6
-- Name: e_filetype; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_filetype (
    filetype_id smallint NOT NULL,
    filetype_name character varying NOT NULL,
    filetype_order smallint
);


ALTER TABLE gisclient_30.e_filetype OWNER TO gisclient;

--
-- TOC entry 2625 (class 1259 OID 12134205)
-- Dependencies: 6
-- Name: e_form; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_30.e_form OWNER TO gisclient;

--
-- TOC entry 2626 (class 1259 OID 12134211)
-- Dependencies: 6
-- Name: e_language; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_language (
    language_id character(2) NOT NULL,
    language_name character varying NOT NULL,
    language_order integer
);


ALTER TABLE gisclient_30.e_language OWNER TO gisclient;

--
-- TOC entry 2627 (class 1259 OID 12134217)
-- Dependencies: 6
-- Name: e_layertype; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_layertype (
    layertype_id smallint NOT NULL,
    layertype_name character varying NOT NULL,
    layertype_ms smallint,
    layertype_order smallint
);


ALTER TABLE gisclient_30.e_layertype OWNER TO gisclient;

--
-- TOC entry 2628 (class 1259 OID 12134223)
-- Dependencies: 6
-- Name: e_lblposition; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_lblposition (
    lblposition_id integer NOT NULL,
    lblposition_name character varying NOT NULL,
    lblposition_order smallint
);


ALTER TABLE gisclient_30.e_lblposition OWNER TO gisclient;

--
-- TOC entry 2629 (class 1259 OID 12134229)
-- Dependencies: 6
-- Name: e_legendtype; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_legendtype (
    legendtype_id smallint NOT NULL,
    legendtype_name character varying NOT NULL,
    legendtype_order smallint
);


ALTER TABLE gisclient_30.e_legendtype OWNER TO gisclient;

--
-- TOC entry 2630 (class 1259 OID 12134235)
-- Dependencies: 3044 3045 6
-- Name: e_level; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_30.e_level OWNER TO gisclient;

--
-- TOC entry 2631 (class 1259 OID 12134243)
-- Dependencies: 6
-- Name: e_orderby; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_orderby (
    orderby_id smallint NOT NULL,
    orderby_name character varying NOT NULL,
    orderby_order smallint
);


ALTER TABLE gisclient_30.e_orderby OWNER TO gisclient;

--
-- TOC entry 2632 (class 1259 OID 12134249)
-- Dependencies: 6
-- Name: e_outputformat; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_30.e_outputformat OWNER TO gisclient;

--
-- TOC entry 2633 (class 1259 OID 12134255)
-- Dependencies: 6
-- Name: e_owstype; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_owstype (
    owstype_id smallint NOT NULL,
    owstype_name character varying NOT NULL,
    owstype_order smallint
);


ALTER TABLE gisclient_30.e_owstype OWNER TO gisclient;

--
-- TOC entry 2634 (class 1259 OID 12134261)
-- Dependencies: 6
-- Name: e_papersize; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_papersize (
    papersize_id integer NOT NULL,
    papersize_name character varying NOT NULL,
    papersize_size character varying NOT NULL,
    papersize_orientation character varying,
    papaersize_order smallint
);


ALTER TABLE gisclient_30.e_papersize OWNER TO gisclient;

--
-- TOC entry 2635 (class 1259 OID 12134267)
-- Dependencies: 6
-- Name: e_qtrelationtype; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_qtrelationtype (
    qtrelationtype_id integer NOT NULL,
    qtrelationtype_name character varying NOT NULL,
    qtrelationtype_order smallint
);


ALTER TABLE gisclient_30.e_qtrelationtype OWNER TO gisclient;

--
-- TOC entry 2636 (class 1259 OID 12134273)
-- Dependencies: 6
-- Name: e_resultype; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_resultype (
    resultype_id smallint NOT NULL,
    resultype_name character varying NOT NULL,
    resultype_order smallint
);


ALTER TABLE gisclient_30.e_resultype OWNER TO gisclient;

--
-- TOC entry 2637 (class 1259 OID 12134279)
-- Dependencies: 6
-- Name: e_searchtype; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_searchtype (
    searchtype_id smallint NOT NULL,
    searchtype_name character varying NOT NULL,
    searchtype_order smallint
);


ALTER TABLE gisclient_30.e_searchtype OWNER TO gisclient;

--
-- TOC entry 2638 (class 1259 OID 12134285)
-- Dependencies: 6
-- Name: e_sizeunits; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_sizeunits (
    sizeunits_id smallint NOT NULL,
    sizeunits_name character varying NOT NULL,
    sizeunits_order smallint
);


ALTER TABLE gisclient_30.e_sizeunits OWNER TO gisclient;

--
-- TOC entry 2639 (class 1259 OID 12134291)
-- Dependencies: 6
-- Name: e_symbolcategory; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_symbolcategory (
    symbolcategory_id smallint NOT NULL,
    symbolcategory_name character varying NOT NULL,
    symbolcategory_order smallint
);


ALTER TABLE gisclient_30.e_symbolcategory OWNER TO gisclient;

--
-- TOC entry 2640 (class 1259 OID 12134297)
-- Dependencies: 6
-- Name: e_tiletype; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE e_tiletype (
    tiletype_id smallint NOT NULL,
    tiletype_name character varying NOT NULL,
    tiletype_order smallint
);


ALTER TABLE gisclient_30.e_tiletype OWNER TO gisclient;

--
-- TOC entry 2641 (class 1259 OID 12134303)
-- Dependencies: 3046 6
-- Name: form_level; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE form_level (
    id integer NOT NULL,
    level integer,
    mode integer,
    form integer,
    order_fld integer,
    visible smallint DEFAULT 1
);


ALTER TABLE gisclient_30.form_level OWNER TO gisclient;

--
-- TOC entry 2642 (class 1259 OID 12134307)
-- Dependencies: 2793 6
-- Name: elenco_form; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW elenco_form AS
    SELECT form_level.id AS "ID", form_level.mode, CASE WHEN (form_level.mode = 2) THEN 'New'::text WHEN (form_level.mode = 3) THEN 'Elenco'::text WHEN (form_level.mode = 0) THEN 'View'::text WHEN (form_level.mode = 1) THEN 'Edit'::text ELSE 'Non definito'::text END AS "Modo Visualizzazione Pagina", e_form.id AS "Form ID", e_form.name AS "Nome Form", e_form.tab_type AS "Tipo Tabella", x.name AS "Livello Destinazione", e_level.name AS "Livello Visualizzazione", CASE WHEN (COALESCE((e_level.depth)::integer, (-1)) = (-1)) THEN 0 ELSE (e_level.depth + 1) END AS "Profondita Albero", form_level.order_fld AS "Ordine Visualizzazione", CASE WHEN (form_level.visible = 1) THEN 'SI'::text ELSE 'NO'::text END AS "Visibile" FROM (((form_level JOIN e_level ON ((form_level.level = e_level.id))) JOIN e_form ON ((e_form.id = form_level.form))) JOIN e_level x ON ((x.id = e_form.level_destination))) ORDER BY CASE WHEN (COALESCE((e_level.depth)::integer, (-1)) = (-1)) THEN 0 ELSE (e_level.depth + 1) END, form_level.level, CASE WHEN (form_level.mode = 2) THEN 'Nuovo'::text WHEN ((form_level.mode = 0) OR (form_level.mode = 3)) THEN 'Elenco'::text WHEN (form_level.mode = 1) THEN 'View'::text ELSE 'Edit'::text END, form_level.order_fld;


ALTER TABLE gisclient_30.elenco_form OWNER TO gisclient;

--
-- TOC entry 2643 (class 1259 OID 12134312)
-- Dependencies: 6
-- Name: font; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE font (
    font_name character varying NOT NULL,
    file_name character varying NOT NULL
);


ALTER TABLE gisclient_30.font OWNER TO gisclient;

--
-- TOC entry 2644 (class 1259 OID 12134318)
-- Dependencies: 6
-- Name: groups; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE groups (
    groupname character varying NOT NULL,
    description character varying
);


ALTER TABLE gisclient_30.groups OWNER TO gisclient;

--
-- TOC entry 2645 (class 1259 OID 12134324)
-- Dependencies: 6
-- Name: i18n_field; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE i18n_field (
    i18nf_id integer NOT NULL,
    table_name character varying(255),
    field_name character varying(255)
);


ALTER TABLE gisclient_30.i18n_field OWNER TO gisclient;

--
-- TOC entry 2646 (class 1259 OID 12134330)
-- Dependencies: 2645 6
-- Name: i18n_field_i18nf_id_seq; Type: SEQUENCE; Schema: gisclient_30; Owner: gisclient
--

CREATE SEQUENCE i18n_field_i18nf_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE gisclient_30.i18n_field_i18nf_id_seq OWNER TO gisclient;

--
-- TOC entry 3415 (class 0 OID 0)
-- Dependencies: 2646
-- Name: i18n_field_i18nf_id_seq; Type: SEQUENCE OWNED BY; Schema: gisclient_30; Owner: gisclient
--

ALTER SEQUENCE i18n_field_i18nf_id_seq OWNED BY i18n_field.i18nf_id;


--
-- TOC entry 3416 (class 0 OID 0)
-- Dependencies: 2646
-- Name: i18n_field_i18nf_id_seq; Type: SEQUENCE SET; Schema: gisclient_30; Owner: gisclient
--

SELECT pg_catalog.setval('i18n_field_i18nf_id_seq', 1, false);


--
-- TOC entry 2647 (class 1259 OID 12134332)
-- Dependencies: 3048 3049 3050 3051 3052 3053 3054 3055 3056 3057 3058 3059 6
-- Name: layer; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
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
    CONSTRAINT layer_layertype_id_check CHECK ((layertype_id > 0))
);


ALTER TABLE gisclient_30.layer OWNER TO gisclient;

--
-- TOC entry 2648 (class 1259 OID 12134350)
-- Dependencies: 3060 3061 3062 6
-- Name: layer_groups; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE layer_groups (
    layer_id integer NOT NULL,
    groupname character varying NOT NULL,
    wms integer DEFAULT 0,
    wfs integer DEFAULT 0,
    wfst integer DEFAULT 0,
    layer_name character varying
);


ALTER TABLE gisclient_30.layer_groups OWNER TO gisclient;

--
-- TOC entry 2649 (class 1259 OID 12134359)
-- Dependencies: 3063 3064 3065 3066 3067 3068 3069 3070 3071 3072 6
-- Name: layergroup; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE layergroup (
    layergroup_id integer NOT NULL,
    theme_id integer NOT NULL,
    layergroup_name character varying NOT NULL,
    layergroup_title character varying,
    layergroup_link character varying,
    layergroup_maxscale integer,
    layergroup_minscale integer,
    layergroup_smbscale integer,
    layergroup_order integer,
    locked smallint DEFAULT 0,
    multi smallint DEFAULT 0,
    hidden integer DEFAULT 0,
    isbaselayer smallint DEFAULT 0,
    tiletype_id numeric(1,0) DEFAULT 1,
    attribution character varying,
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


ALTER TABLE gisclient_30.layergroup OWNER TO gisclient;

--
-- TOC entry 2650 (class 1259 OID 12134375)
-- Dependencies: 3073 3074 3075 6
-- Name: link; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_30.link OWNER TO gisclient;

--
-- TOC entry 2651 (class 1259 OID 12134384)
-- Dependencies: 6
-- Name: localization; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE localization (
    localization_id integer NOT NULL,
    project_name character varying NOT NULL,
    i18nf_id integer,
    pkey_id character varying NOT NULL,
    language_id character(2),
    value text
);


ALTER TABLE gisclient_30.localization OWNER TO gisclient;

--
-- TOC entry 2652 (class 1259 OID 12134390)
-- Dependencies: 6 2651
-- Name: localization_localization_id_seq; Type: SEQUENCE; Schema: gisclient_30; Owner: gisclient
--

CREATE SEQUENCE localization_localization_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE gisclient_30.localization_localization_id_seq OWNER TO gisclient;

--
-- TOC entry 3417 (class 0 OID 0)
-- Dependencies: 2652
-- Name: localization_localization_id_seq; Type: SEQUENCE OWNED BY; Schema: gisclient_30; Owner: gisclient
--

ALTER SEQUENCE localization_localization_id_seq OWNED BY localization.localization_id;


--
-- TOC entry 3418 (class 0 OID 0)
-- Dependencies: 2652
-- Name: localization_localization_id_seq; Type: SEQUENCE SET; Schema: gisclient_30; Owner: gisclient
--

SELECT pg_catalog.setval('localization_localization_id_seq', 1286, true);


--
-- TOC entry 2653 (class 1259 OID 12134392)
-- Dependencies: 3077 3078 3079 3080 3081 3082 3083 3084 3085 3086 6
-- Name: mapset; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
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
    legend_icon_w integer DEFAULT 18,
    legend_icon_h integer DEFAULT 14,
    imagelabel smallint DEFAULT 0,
    bg_color character varying DEFAULT '255 255 255'::character varying,
    refmap_extent character varying,
    test_extent character varying,
    mapset_srid integer DEFAULT (-1),
    mapset_def character varying,
    mapset_group character varying,
    outputformat_id integer,
    interlace smallint DEFAULT 0,
    geocoord integer DEFAULT 0,
    private integer DEFAULT 0,
    sizeunits_id smallint DEFAULT 5,
    readline_color character varying,
    static_reference integer DEFAULT 0,
    metadata text,
    mapset_note text,
    mask character varying,
    maxscale integer,
    minscale integer,
    mapset_scales character varying
);


ALTER TABLE gisclient_30.mapset OWNER TO gisclient;

--
-- TOC entry 3419 (class 0 OID 0)
-- Dependencies: 2653
-- Name: COLUMN mapset.mapset_scales; Type: COMMENT; Schema: gisclient_30; Owner: gisclient
--

COMMENT ON COLUMN mapset.mapset_scales IS 'Possible scale list separated with comma';


--
-- TOC entry 2654 (class 1259 OID 12134408)
-- Dependencies: 3087 3088 6
-- Name: mapset_groups; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE mapset_groups (
    mapset_name character varying NOT NULL,
    group_name character varying NOT NULL,
    edit integer DEFAULT 0,
    redline integer DEFAULT 0
);


ALTER TABLE gisclient_30.mapset_groups OWNER TO gisclient;

--
-- TOC entry 2655 (class 1259 OID 12134416)
-- Dependencies: 3089 3090 3091 6
-- Name: mapset_layergroup; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE mapset_layergroup (
    mapset_name character varying NOT NULL,
    layergroup_id integer NOT NULL,
    status smallint DEFAULT 0,
    refmap smallint DEFAULT 0,
    hide smallint DEFAULT 0
);


ALTER TABLE gisclient_30.mapset_layergroup OWNER TO gisclient;

--
-- TOC entry 2656 (class 1259 OID 12134425)
-- Dependencies: 3092 6
-- Name: mapset_link; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE mapset_link (
    mapset_name character varying NOT NULL,
    link_id integer NOT NULL,
    CONSTRAINT mapset_link_qtlink_id_check CHECK ((link_id > 0))
);


ALTER TABLE gisclient_30.mapset_link OWNER TO gisclient;

--
-- TOC entry 2657 (class 1259 OID 12134432)
-- Dependencies: 6
-- Name: mapset_qt; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE mapset_qt (
    mapset_name character varying NOT NULL,
    qt_id integer NOT NULL
);


ALTER TABLE gisclient_30.mapset_qt OWNER TO gisclient;

--
-- TOC entry 2658 (class 1259 OID 12134438)
-- Dependencies: 3093 3094 3095 3096 3097 3098 3099 3100 3101 3102 6
-- Name: project; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
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
    project_srid integer,
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
    default_language_id character(2) DEFAULT 'it'::bpchar NOT NULL
);


ALTER TABLE gisclient_30.project OWNER TO gisclient;

--
-- TOC entry 2659 (class 1259 OID 12134454)
-- Dependencies: 6
-- Name: project_admin; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE project_admin (
    project_name character varying NOT NULL,
    username character varying NOT NULL
);


ALTER TABLE gisclient_30.project_admin OWNER TO gisclient;

--
-- TOC entry 2660 (class 1259 OID 12134460)
-- Dependencies: 6
-- Name: project_languages; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE project_languages (
    project_name character varying NOT NULL,
    language_id character(2) NOT NULL
);


ALTER TABLE gisclient_30.project_languages OWNER TO gisclient;

--
-- TOC entry 2661 (class 1259 OID 12134466)
-- Dependencies: 6
-- Name: project_srs; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE project_srs (
    project_name character varying NOT NULL,
    srid integer NOT NULL,
    projparam character varying,
    custom_srid integer
);


ALTER TABLE gisclient_30.project_srs OWNER TO gisclient;

--
-- TOC entry 2662 (class 1259 OID 12134472)
-- Dependencies: 3103 3104 3105 3106 6
-- Name: qt; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_30.qt OWNER TO gisclient;

--
-- TOC entry 2663 (class 1259 OID 12134482)
-- Dependencies: 6
-- Name: qt_link; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE qt_link (
    qt_id integer NOT NULL,
    link_id integer NOT NULL,
    resultype_id smallint
);


ALTER TABLE gisclient_30.qt_link OWNER TO gisclient;

--
-- TOC entry 2664 (class 1259 OID 12134485)
-- Dependencies: 6
-- Name: qt_selgroup; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE qt_selgroup (
    qt_id integer NOT NULL,
    selgroup_id integer NOT NULL
);


ALTER TABLE gisclient_30.qt_selgroup OWNER TO gisclient;

--
-- TOC entry 2665 (class 1259 OID 12134488)
-- Dependencies: 3107 3108 3109 3110 3111 3112 3113 3114 3115 6
-- Name: qtfield; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
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
    CONSTRAINT qtfield_qtrelation_id_check CHECK ((qtrelation_id >= 0))
);


ALTER TABLE gisclient_30.qtfield OWNER TO gisclient;

--
-- TOC entry 2666 (class 1259 OID 12134503)
-- Dependencies: 3116 3117 3118 6
-- Name: qtrelation; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_30.qtrelation OWNER TO gisclient;

--
-- TOC entry 2667 (class 1259 OID 12134512)
-- Dependencies: 2794 6
-- Name: seldb_catalog; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_catalog AS
    SELECT (-1) AS id, 'Seleziona ====>' AS opzione, '0' AS project_name UNION ALL SELECT foo.id, foo.opzione, foo.project_name FROM (SELECT catalog.catalog_id AS id, catalog.catalog_name AS opzione, catalog.project_name FROM catalog ORDER BY catalog.catalog_name) foo;


ALTER TABLE gisclient_30.seldb_catalog OWNER TO gisclient;

--
-- TOC entry 2668 (class 1259 OID 12134516)
-- Dependencies: 2795 6
-- Name: seldb_catalog_wms; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_catalog_wms AS
    SELECT catalog.catalog_id AS id, catalog.catalog_name AS opzione, catalog.project_name FROM catalog WHERE (catalog.connection_type = 7);


ALTER TABLE gisclient_30.seldb_catalog_wms OWNER TO gisclient;

--
-- TOC entry 2669 (class 1259 OID 12134520)
-- Dependencies: 2796 6
-- Name: seldb_charset_encodings; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_charset_encodings AS
    SELECT e_charset_encodings.charset_encodings_id AS id, e_charset_encodings.charset_encodings_name AS opzione, e_charset_encodings.charset_encodings_order AS option_order FROM e_charset_encodings ORDER BY e_charset_encodings.charset_encodings_order;


ALTER TABLE gisclient_30.seldb_charset_encodings OWNER TO gisclient;

--
-- TOC entry 2670 (class 1259 OID 12134524)
-- Dependencies: 2797 6
-- Name: seldb_conntype; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_conntype AS
    SELECT NULL::integer AS id, 'Seleziona ====>' AS opzione UNION ALL SELECT foo.id, foo.opzione FROM (SELECT e_conntype.conntype_id AS id, e_conntype.conntype_name AS opzione FROM e_conntype ORDER BY e_conntype.conntype_order) foo;


ALTER TABLE gisclient_30.seldb_conntype OWNER TO gisclient;

--
-- TOC entry 2671 (class 1259 OID 12134528)
-- Dependencies: 2798 6
-- Name: seldb_datatype; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_datatype AS
    SELECT e_datatype.datatype_id AS id, e_datatype.datatype_name AS opzione FROM e_datatype;


ALTER TABLE gisclient_30.seldb_datatype OWNER TO gisclient;

--
-- TOC entry 2672 (class 1259 OID 12134532)
-- Dependencies: 2799 6
-- Name: seldb_fieldtype; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_fieldtype AS
    SELECT e_fieldtype.fieldtype_id AS id, e_fieldtype.fieldtype_name AS opzione FROM e_fieldtype;


ALTER TABLE gisclient_30.seldb_fieldtype OWNER TO gisclient;

--
-- TOC entry 2673 (class 1259 OID 12134536)
-- Dependencies: 2800 6
-- Name: seldb_filetype; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_filetype AS
    SELECT (-1) AS id, 'Seleziona ====>' AS opzione UNION SELECT e_filetype.filetype_id AS id, e_filetype.filetype_name AS opzione FROM e_filetype;


ALTER TABLE gisclient_30.seldb_filetype OWNER TO gisclient;

--
-- TOC entry 2674 (class 1259 OID 12134540)
-- Dependencies: 2801 6
-- Name: seldb_font; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_font AS
    SELECT foo.id, foo.opzione FROM (SELECT '' AS id, 'Seleziona ====>' AS opzione UNION SELECT font.font_name AS id, font.font_name AS opzione FROM font) foo ORDER BY foo.id;


ALTER TABLE gisclient_30.seldb_font OWNER TO gisclient;

--
-- TOC entry 2675 (class 1259 OID 12134544)
-- Dependencies: 2802 6
-- Name: seldb_language; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_language AS
    SELECT foo.id, foo.opzione FROM (SELECT ''::text AS id, 'Seleziona ====>' AS opzione UNION SELECT e_language.language_id AS id, e_language.language_name AS opzione FROM e_language) foo ORDER BY foo.id;


ALTER TABLE gisclient_30.seldb_language OWNER TO gisclient;

--
-- TOC entry 2676 (class 1259 OID 12134548)
-- Dependencies: 2803 6
-- Name: seldb_layer_layergroup; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_layer_layergroup AS
    SELECT (-1) AS id, 'Seleziona ====>' AS opzione, NULL::unknown AS layergroup_id UNION (SELECT DISTINCT layer.layer_id AS id, layer.layer_name AS opzione, layer.layergroup_id FROM layer WHERE (layer.queryable = (1)::numeric) ORDER BY layer.layer_name, layer.layer_id, layer.layergroup_id);


ALTER TABLE gisclient_30.seldb_layer_layergroup OWNER TO gisclient;

--
-- TOC entry 2677 (class 1259 OID 12134552)
-- Dependencies: 2804 6
-- Name: seldb_layertype; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_layertype AS
    SELECT (-1) AS id, 'Seleziona ====>' AS opzione UNION (SELECT e_layertype.layertype_id AS id, e_layertype.layertype_name AS opzione FROM e_layertype ORDER BY e_layertype.layertype_name);


ALTER TABLE gisclient_30.seldb_layertype OWNER TO gisclient;

--
-- TOC entry 2678 (class 1259 OID 12134556)
-- Dependencies: 2805 6
-- Name: seldb_lblposition; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_lblposition AS
    SELECT '' AS id, 'Seleziona ====>' AS opzione UNION (SELECT e_lblposition.lblposition_name AS id, e_lblposition.lblposition_name AS opzione FROM e_lblposition ORDER BY e_lblposition.lblposition_order);


ALTER TABLE gisclient_30.seldb_lblposition OWNER TO gisclient;

--
-- TOC entry 2679 (class 1259 OID 12134560)
-- Dependencies: 2806 6
-- Name: seldb_legendtype; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_legendtype AS
    SELECT e_legendtype.legendtype_id AS id, e_legendtype.legendtype_name AS opzione FROM e_legendtype ORDER BY e_legendtype.legendtype_order;


ALTER TABLE gisclient_30.seldb_legendtype OWNER TO gisclient;

--
-- TOC entry 2680 (class 1259 OID 12134564)
-- Dependencies: 2807 6
-- Name: seldb_link; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_link AS
    SELECT (-1) AS id, 'Seleziona ====>' AS opzione, '' AS project_name UNION SELECT link.link_id AS id, link.link_name AS opzione, link.project_name FROM link;


ALTER TABLE gisclient_30.seldb_link OWNER TO gisclient;

--
-- TOC entry 2681 (class 1259 OID 12134568)
-- Dependencies: 2808 6
-- Name: seldb_mapset_srid; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_mapset_srid AS
    SELECT project_srs.srid AS id, project_srs.srid AS opzione, project_srs.project_name FROM project_srs;


ALTER TABLE gisclient_30.seldb_mapset_srid OWNER TO gisclient;

--
-- TOC entry 2682 (class 1259 OID 12134572)
-- Dependencies: 2809 6
-- Name: seldb_orderby; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_orderby AS
    SELECT e_orderby.orderby_id AS id, e_orderby.orderby_name AS opzione FROM e_orderby;


ALTER TABLE gisclient_30.seldb_orderby OWNER TO gisclient;

--
-- TOC entry 2683 (class 1259 OID 12134576)
-- Dependencies: 2810 6
-- Name: seldb_outputformat; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_outputformat AS
    SELECT e_outputformat.outputformat_id AS id, e_outputformat.outputformat_name AS opzione FROM e_outputformat ORDER BY e_outputformat.outputformat_order;


ALTER TABLE gisclient_30.seldb_outputformat OWNER TO gisclient;

--
-- TOC entry 2684 (class 1259 OID 12134580)
-- Dependencies: 2811 6
-- Name: seldb_owstype; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_owstype AS
    SELECT e_owstype.owstype_id AS id, e_owstype.owstype_name AS opzione FROM e_owstype;


ALTER TABLE gisclient_30.seldb_owstype OWNER TO gisclient;

--
-- TOC entry 2685 (class 1259 OID 12134584)
-- Dependencies: 2812 6
-- Name: seldb_papersize; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_papersize AS
    SELECT (-1) AS id, 'Seleziona ====>' AS opzione UNION SELECT e_papersize.papersize_id AS id, e_papersize.papersize_name AS opzione FROM e_papersize;


ALTER TABLE gisclient_30.seldb_papersize OWNER TO gisclient;

--
-- TOC entry 2686 (class 1259 OID 12134588)
-- Dependencies: 2813 6
-- Name: seldb_project; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_project AS
    SELECT '' AS id, 'Seleziona ====>' AS opzione UNION (SELECT DISTINCT project.project_name AS id, project.project_name AS opzione FROM project ORDER BY project.project_name);


ALTER TABLE gisclient_30.seldb_project OWNER TO gisclient;

--
-- TOC entry 2687 (class 1259 OID 12134592)
-- Dependencies: 3119 3120 3121 3122 6
-- Name: theme; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE theme (
    theme_id integer NOT NULL,
    project_name character varying,
    theme_name character varying NOT NULL,
    theme_title character varying,
    theme_link character varying,
    theme_order integer,
    locked smallint DEFAULT 0,
    theme_single numeric(1,0) DEFAULT 0,
    radio numeric(1,0) DEFAULT 0,
    charset_encodings_id integer,
    copyright_string character varying,
    CONSTRAINT theme_name_lower_case CHECK (((theme_name)::text = lower((theme_name)::text)))
);


ALTER TABLE gisclient_30.theme OWNER TO gisclient;

--
-- TOC entry 2688 (class 1259 OID 12134602)
-- Dependencies: 2814 6
-- Name: seldb_qt_theme; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_qt_theme AS
    SELECT (-1) AS id, '___Seleziona ====>' AS opzione UNION SELECT qt.qt_id AS id, (qt.qt_name)::text AS opzione FROM (qt JOIN theme USING (theme_id)) WHERE (qt.theme_id = 55) ORDER BY 2;


ALTER TABLE gisclient_30.seldb_qt_theme OWNER TO gisclient;

--
-- TOC entry 2689 (class 1259 OID 12134607)
-- Dependencies: 2815 6
-- Name: seldb_qtrelation; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_qtrelation AS
    SELECT 0 AS id, 'layer' AS opzione, 0 AS layer_id UNION SELECT qtrelation.qtrelation_id AS id, qtrelation.qtrelation_name AS opzione, qtrelation.layer_id FROM qtrelation;


ALTER TABLE gisclient_30.seldb_qtrelation OWNER TO gisclient;

--
-- TOC entry 2690 (class 1259 OID 12134611)
-- Dependencies: 2816 6
-- Name: seldb_qtrelationtype; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_qtrelationtype AS
    SELECT e_qtrelationtype.qtrelationtype_id AS id, e_qtrelationtype.qtrelationtype_name AS opzione FROM e_qtrelationtype;


ALTER TABLE gisclient_30.seldb_qtrelationtype OWNER TO gisclient;

--
-- TOC entry 2691 (class 1259 OID 12134615)
-- Dependencies: 2817 6
-- Name: seldb_resultype; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_resultype AS
    SELECT e_resultype.resultype_id AS id, e_resultype.resultype_name AS opzione FROM e_resultype ORDER BY e_resultype.resultype_order;


ALTER TABLE gisclient_30.seldb_resultype OWNER TO gisclient;

--
-- TOC entry 2692 (class 1259 OID 12134619)
-- Dependencies: 2818 6
-- Name: seldb_searchtype; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_searchtype AS
    SELECT e_searchtype.searchtype_id AS id, e_searchtype.searchtype_name AS opzione FROM e_searchtype;


ALTER TABLE gisclient_30.seldb_searchtype OWNER TO gisclient;

--
-- TOC entry 2693 (class 1259 OID 12134623)
-- Dependencies: 2819 6
-- Name: seldb_sizeunits; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_sizeunits AS
    SELECT e_sizeunits.sizeunits_id AS id, e_sizeunits.sizeunits_name AS opzione FROM e_sizeunits;


ALTER TABLE gisclient_30.seldb_sizeunits OWNER TO gisclient;

--
-- TOC entry 2694 (class 1259 OID 12134627)
-- Dependencies: 2820 6
-- Name: seldb_theme; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_theme AS
    SELECT (-1) AS id, 'Seleziona ====>' AS opzione, '' AS project_name UNION SELECT theme.theme_id AS id, theme.theme_name AS opzione, theme.project_name FROM theme;


ALTER TABLE gisclient_30.seldb_theme OWNER TO gisclient;

--
-- TOC entry 2695 (class 1259 OID 12134631)
-- Dependencies: 2821 6
-- Name: seldb_tiletype; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW seldb_tiletype AS
    SELECT e_tiletype.tiletype_id AS id, e_tiletype.tiletype_name AS opzione FROM e_tiletype;


ALTER TABLE gisclient_30.seldb_tiletype OWNER TO gisclient;

--
-- TOC entry 2696 (class 1259 OID 12134635)
-- Dependencies: 3123 6
-- Name: selgroup; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE selgroup (
    selgroup_id integer NOT NULL,
    project_name character varying NOT NULL,
    selgroup_name character varying NOT NULL,
    selgroup_title character varying,
    selgroup_order smallint DEFAULT 1
);


ALTER TABLE gisclient_30.selgroup OWNER TO gisclient;

--
-- TOC entry 2697 (class 1259 OID 12134642)
-- Dependencies: 3124 6
-- Name: style; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_30.style OWNER TO gisclient;

--
-- TOC entry 2698 (class 1259 OID 12134649)
-- Dependencies: 3125 3126 6
-- Name: symbol; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE symbol (
    symbol_name character varying NOT NULL,
    symbolcategory_id integer DEFAULT 1 NOT NULL,
    icontype integer DEFAULT 0 NOT NULL,
    symbol_image bytea,
    symbol_def text
);


ALTER TABLE gisclient_30.symbol OWNER TO gisclient;

--
-- TOC entry 2699 (class 1259 OID 12134657)
-- Dependencies: 3127 6
-- Name: symbol_ttf; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE symbol_ttf (
    symbol_ttf_name character varying NOT NULL,
    font_name character varying NOT NULL,
    symbolcategory_id integer DEFAULT 0,
    ascii_code smallint NOT NULL,
    "position" character(2),
    symbol_ttf_image bytea
);


ALTER TABLE gisclient_30.symbol_ttf OWNER TO gisclient;




--
-- TOC entry 2701 (class 1259 OID 12134669)
-- Dependencies: 6
-- Name: user_group; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE TABLE user_group (
    username character varying NOT NULL,
    groupname character varying NOT NULL
);


ALTER TABLE gisclient_30.user_group OWNER TO gisclient;

--
-- TOC entry 2702 (class 1259 OID 12134675)
-- Dependencies: 3128 6
-- Name: users; Type: TABLE; Schema: gisclient_30; Owner: gisclient; Tablespace: 
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


ALTER TABLE gisclient_30.users OWNER TO gisclient;

--
-- TOC entry 2703 (class 1259 OID 12134682)
-- Dependencies: 2822 6
-- Name: vista_mapset_link; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW vista_mapset_link AS
    SELECT mapset_link.mapset_name, mapset_link.link_id, link.link_name AS link_title FROM (mapset_link LEFT JOIN link USING (link_id));


ALTER TABLE gisclient_30.vista_mapset_link OWNER TO gisclient;

--
-- TOC entry 2704 (class 1259 OID 12134686)
-- Dependencies: 2823 6
-- Name: vista_qtfield; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW vista_qtfield AS
    SELECT qtfield.qtfield_id, qtfield.layer_id, qtfield.fieldtype_id, x.qtrelation_id, qtfield.qtfield_name, qtfield.field_header, qtfield.qtfield_order, COALESCE(qtfield.column_width, 0) AS column_width, x.name AS qtrelation_name, x.qtrelationtype_id, x.qtrelationtype_name FROM ((qtfield JOIN e_fieldtype USING (fieldtype_id)) JOIN (SELECT y.qtrelationtype_id, y.qtrelation_id, y.name, z.qtrelationtype_name FROM ((SELECT 0 AS qtrelation_id, 'Data Layer' AS name, 0 AS qtrelationtype_id UNION SELECT qtrelation.qtrelation_id, COALESCE(qtrelation.qtrelation_name, 'Nessuna Relazione'::character varying) AS name, qtrelation.qtrelationtype_id FROM qtrelation) y JOIN (SELECT 0 AS qtrelationtype_id, '' AS qtrelationtype_name UNION SELECT e_qtrelationtype.qtrelationtype_id, e_qtrelationtype.qtrelationtype_name FROM e_qtrelationtype) z USING (qtrelationtype_id))) x USING (qtrelation_id)) ORDER BY qtfield.qtfield_id, x.qtrelation_id, x.qtrelationtype_id;


ALTER TABLE gisclient_30.vista_qtfield OWNER TO gisclient;

--
-- TOC entry 2705 (class 1259 OID 12134691)
-- Dependencies: 2824 6
-- Name: vista_style; Type: VIEW; Schema: gisclient_30; Owner: gisclient
--

CREATE VIEW vista_style AS
    SELECT style.style_id, style.class_id, style.style_name, style.angle, style.color, style.outlinecolor, style.bgcolor, style.size, style.minsize, style.maxsize, style.minwidth, style.width, style.maxwidth, style.style_def, style.locked, style.symbol_name, symbol.symbol_image, style.style_order FROM (style LEFT JOIN symbol USING (symbol_name)) ORDER BY style.style_order;


ALTER TABLE gisclient_30.vista_style OWNER TO gisclient;

--
-- TOC entry 3047 (class 2604 OID 12134696)
-- Dependencies: 2646 2645
-- Name: i18nf_id; Type: DEFAULT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE i18n_field ALTER COLUMN i18nf_id SET DEFAULT nextval('i18n_field_i18nf_id_seq'::regclass);


--
-- TOC entry 3076 (class 2604 OID 12134697)
-- Dependencies: 2652 2651
-- Name: localization_id; Type: DEFAULT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE localization ALTER COLUMN localization_id SET DEFAULT nextval('localization_localization_id_seq'::regclass);


--
-- TOC entry 3357 (class 0 OID 12134136)
-- Dependencies: 2615
-- Data for Name: catalog; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3358 (class 0 OID 12134142)
-- Dependencies: 2616
-- Data for Name: catalog_import; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3359 (class 0 OID 12134148)
-- Dependencies: 2617
-- Data for Name: class; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3360 (class 0 OID 12134160)
-- Dependencies: 2618
-- Data for Name: classgroup; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3361 (class 0 OID 12134169)
-- Dependencies: 2619
-- Data for Name: e_charset_encodings; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_charset_encodings VALUES (1, 'ISO-8859-1', 1);
INSERT INTO e_charset_encodings VALUES (2, 'UTF-8', 2);


--
-- TOC entry 3362 (class 0 OID 12134175)
-- Dependencies: 2620
-- Data for Name: e_conntype; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_conntype VALUES (7, 'WMS', 4);
INSERT INTO e_conntype VALUES (3, 'SDE', 7);
INSERT INTO e_conntype VALUES (6, 'Postgis', 2);
INSERT INTO e_conntype VALUES (8, 'Oracle Spatial', 3);
INSERT INTO e_conntype VALUES (1, 'Local Folder', 1);
INSERT INTO e_conntype VALUES (9, 'WFS', 5);
INSERT INTO e_conntype VALUES (4, 'OGR', 5);


--
-- TOC entry 3363 (class 0 OID 12134181)
-- Dependencies: 2621
-- Data for Name: e_datatype; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_datatype VALUES (1, 'Stringa di testo', NULL);
INSERT INTO e_datatype VALUES (2, 'Numero', NULL);
INSERT INTO e_datatype VALUES (3, 'Data', NULL);


--
-- TOC entry 3364 (class 0 OID 12134187)
-- Dependencies: 2622
-- Data for Name: e_fieldformat; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_fieldformat VALUES (1, 'intero', '%d', 10);
INSERT INTO e_fieldformat VALUES (2, 'decimale (1 cifra)', '%01.1f', 20);
INSERT INTO e_fieldformat VALUES (3, 'decimale (2 cifre)', '%01.2f', 30);


--
-- TOC entry 3365 (class 0 OID 12134193)
-- Dependencies: 2623
-- Data for Name: e_fieldtype; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_fieldtype VALUES (1, 'Standard', NULL);
INSERT INTO e_fieldtype VALUES (2, 'Collegamento', NULL);
INSERT INTO e_fieldtype VALUES (3, 'E-mail', NULL);
INSERT INTO e_fieldtype VALUES (10, 'Intestazione di gruppo', NULL);
INSERT INTO e_fieldtype VALUES (8, 'Immagine', NULL);
INSERT INTO e_fieldtype VALUES (107, 'Varianza', NULL);
INSERT INTO e_fieldtype VALUES (106, 'Deviazione  St', NULL);
INSERT INTO e_fieldtype VALUES (105, 'Conteggio', NULL);
INSERT INTO e_fieldtype VALUES (104, 'Max', NULL);
INSERT INTO e_fieldtype VALUES (103, 'Min', NULL);
INSERT INTO e_fieldtype VALUES (102, 'Media', NULL);
INSERT INTO e_fieldtype VALUES (101, 'Somma', NULL);


--
-- TOC entry 3366 (class 0 OID 12134199)
-- Dependencies: 2624
-- Data for Name: e_filetype; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_filetype VALUES (1, 'File SQL', 1);
INSERT INTO e_filetype VALUES (2, 'File CSV', 2);
INSERT INTO e_filetype VALUES (3, 'File Shape', 3);


--
-- TOC entry 3367 (class 0 OID 12134205)
-- Dependencies: 2625
-- Data for Name: e_form; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_form VALUES (2, 'progetto', 'project', 0, 2, NULL, NULL, NULL, NULL, NULL, 'project_name');
INSERT INTO e_form VALUES (3, 'progetto', 'project', 1, 2, '', NULL, NULL, NULL, NULL, NULL);
INSERT INTO e_form VALUES (5, 'mapset', 'mapset', 0, 8, NULL, NULL, NULL, NULL, NULL, 'title');
INSERT INTO e_form VALUES (6, 'progetto', 'project', 2, 2, '', 'project', NULL, NULL, NULL, NULL);
INSERT INTO e_form VALUES (7, 'progetto', 'project', 1, 2, NULL, 'project', NULL, NULL, NULL, NULL);
INSERT INTO e_form VALUES (8, 'temi', 'theme', 0, 5, NULL, NULL, NULL, NULL, NULL, 'theme_order,theme_title');
INSERT INTO e_form VALUES (9, 'temi', 'theme', 1, 5, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO e_form VALUES (10, 'temi', 'theme', 1, 5, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (11, 'temi', 'theme', 2, 5, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (12, 'project_srs', 'project_srs', 0, 6, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (13, 'project_srs', 'project_srs', 1, 6, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (14, 'project_srs', 'project_srs', 2, 6, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (16, 'user', 'user', 4, 4, NULL, 'user', 2, NULL, 'user', NULL);
INSERT INTO e_form VALUES (18, 'user', 'user', 50, 4, NULL, 'user', 2, NULL, 'user', NULL);
INSERT INTO e_form VALUES (20, 'group', 'group', 4, 3, NULL, 'group', 2, NULL, 'group', NULL);
INSERT INTO e_form VALUES (23, 'group', 'group', 50, 3, NULL, 'group', 2, NULL, 'group', NULL);
INSERT INTO e_form VALUES (26, 'mapset', 'mapset', 1, 8, '', NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (27, 'mapset', 'mapset', 1, 8, NULL, 'mapset', 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (28, 'mapset', 'mapset', 2, 2, NULL, 'mapset', 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (34, 'layer', 'layer', 0, 11, NULL, NULL, 10, NULL, NULL, 'layer_order,layer_name');
INSERT INTO e_form VALUES (35, 'layer', 'layer', 1, 11, NULL, 'layer', 10, NULL, NULL, NULL);
INSERT INTO e_form VALUES (36, 'layer', 'layer', 1, 11, NULL, 'layer', 10, NULL, NULL, NULL);
INSERT INTO e_form VALUES (37, 'layer', 'layer', 2, 11, NULL, 'layer', 10, NULL, NULL, NULL);
INSERT INTO e_form VALUES (38, 'classi', 'class', 0, 12, NULL, NULL, 11, NULL, NULL, 'class_order');
INSERT INTO e_form VALUES (39, 'classi', 'class', 1, 12, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (40, 'classi', 'class', 1, 12, NULL, 'class', 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (41, 'classi', 'class', 2, 12, NULL, 'class', 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (42, 'stili', 'style', 0, 14, NULL, NULL, 12, NULL, NULL, 'style_order');
INSERT INTO e_form VALUES (43, 'stili', 'style', 1, 14, NULL, NULL, 12, NULL, NULL, NULL);
INSERT INTO e_form VALUES (44, 'stili', 'style', 1, 14, NULL, 'style', 12, NULL, NULL, NULL);
INSERT INTO e_form VALUES (45, 'stili', 'style', 2, 14, NULL, 'style', 12, NULL, NULL, NULL);
INSERT INTO e_form VALUES (50, 'catalog', 'catalog', 0, 7, NULL, NULL, 2, NULL, NULL, 'catalog_name');
INSERT INTO e_form VALUES (51, 'catalog', 'catalog', 1, 7, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (52, 'catalog', 'catalog', 1, 7, NULL, 'catalog', 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (53, 'catalog', 'catalog', 2, 7, NULL, 'catalog', 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (70, 'links', 'link', 0, 9, '', NULL, 2, NULL, NULL, 'link_order,link_name');
INSERT INTO e_form VALUES (72, 'links', 'link', 1, 9, '', NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (73, 'links', 'link', 1, 9, '', NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (74, 'links', 'link', 2, 9, '', NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (81, 'map_group', 'mapset_group', 4, 21, NULL, 'mapset_groups', 8, NULL, NULL, NULL);
INSERT INTO e_form VALUES (82, 'map_group', 'mapset_group', 5, 21, NULL, 'mapset_groups', 8, NULL, NULL, NULL);
INSERT INTO e_form VALUES (83, 'map_group', 'mapset_group', 0, 21, NULL, NULL, 8, NULL, NULL, NULL);
INSERT INTO e_form VALUES (87, 'map_qt', 'mapset_qt', 4, 23, NULL, 'mapset_qt', 8, NULL, NULL, NULL);
INSERT INTO e_form VALUES (88, 'map_qt', 'mapset_qt', 5, 23, NULL, 'mapset_qt', 8, NULL, NULL, NULL);
INSERT INTO e_form VALUES (89, 'map_qt', 'mapset_qt', 0, 23, NULL, NULL, 8, NULL, NULL, NULL);
INSERT INTO e_form VALUES (90, 'map_qtlink', 'mapset_link', 4, 24, NULL, 'map_link', 8, NULL, NULL, NULL);
INSERT INTO e_form VALUES (91, 'map_qtlink', 'mapset_link', 5, 24, NULL, 'map_link', 8, NULL, NULL, NULL);
INSERT INTO e_form VALUES (92, 'map_qtlink', 'mapset_link', 0, 24, NULL, NULL, 8, NULL, NULL, NULL);
INSERT INTO e_form VALUES (105, 'selgroup', 'selgroup', 0, 27, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (106, 'selgroup', 'selgroup', 1, 27, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (107, 'selgroup', 'selgroup', 1, 27, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (108, 'qt_selgroup', 'qt_selgroup', 4, 28, NULL, 'qt_selgroup', 27, NULL, NULL, NULL);
INSERT INTO e_form VALUES (109, 'qt_selgroup', 'qt_selgroup', 5, 28, NULL, 'qt_selgroup', 27, NULL, NULL, NULL);
INSERT INTO e_form VALUES (133, 'project_admin', 'admin_project', 2, 33, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (134, 'project_admin', 'admin_project', 5, 33, NULL, 'admin_project', 6, NULL, NULL, NULL);
INSERT INTO e_form VALUES (149, 'group_users', 'group_users', 4, 45, NULL, 'group_users', 3, NULL, NULL, NULL);
INSERT INTO e_form VALUES (150, 'group_users', 'group_users', 5, 45, NULL, 'group_users', 3, NULL, NULL, NULL);
INSERT INTO e_form VALUES (151, 'user_groups', 'user_groups', 4, 46, NULL, 'user_groups', 4, NULL, NULL, NULL);
INSERT INTO e_form VALUES (152, 'user_groups', 'user_groups', 5, 46, NULL, 'user_groups', 4, NULL, NULL, NULL);
INSERT INTO e_form VALUES (75, 'qt_relation', 'qt_relation_addnew', 0, 16, NULL, NULL, 13, NULL, NULL, NULL);
INSERT INTO e_form VALUES (30, 'layergroup', 'layergroup', 0, 10, NULL, 'layergroup', 5, NULL, NULL, 'layergroup_order,layergroup_title');
INSERT INTO e_form VALUES (31, 'layergroup', 'layergroup', 1, 10, NULL, 'layergroup', 5, NULL, NULL, NULL);
INSERT INTO e_form VALUES (32, 'layergroup', 'layergroup', 1, 10, NULL, 'layergroup', 5, NULL, NULL, NULL);
INSERT INTO e_form VALUES (33, 'layergroup', 'layergroup', 2, 10, NULL, 'layergroup', 5, NULL, NULL, NULL);
INSERT INTO e_form VALUES (84, 'map_layer', 'mapset_layergroup', 4, 22, NULL, 'mapset_layergroup', 8, NULL, NULL, NULL);
INSERT INTO e_form VALUES (85, 'map_layer', 'mapset_layergroup', 5, 22, NULL, 'mapset_layergroup', 8, NULL, NULL, NULL);
INSERT INTO e_form VALUES (86, 'map_layer', 'mapset_layergroup', 0, 22, NULL, 'mapset_layergroup', 8, NULL, NULL, NULL);
INSERT INTO e_form VALUES (62, 'qt_fields', 'qtfield', 0, 17, NULL, NULL, 11, NULL, NULL, 'qtrelationtype_id,qtrelation_name,field_header,qtfield_name');
INSERT INTO e_form VALUES (63, 'qt_fields', 'qtfield', 1, 17, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (64, 'qt_fields', 'qtfield', 1, 17, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (65, 'qt_fields', 'qtfield', 2, 17, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (66, 'qt_links', 'qt_links', 2, 19, NULL, 'qt_links', 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (67, 'qt_links', 'qt_links', 0, 19, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (68, 'qt_links', 'qt_links', 1, 19, NULL, 'qt_links', 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (69, 'qt_links', 'qt_links', 110, 19, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (201, 'classgroup', 'classgroup', 1, 100, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (200, 'classgroup', 'classgroup', 0, 100, NULL, NULL, 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (58, 'qt_relation', 'qtrelation', 0, 16, NULL, 'qtrelation', 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (59, 'qt_relation', 'qtrelation', 1, 16, NULL, 'qtrelation', 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (60, 'qt_relation', 'qtrelation', 1, 16, NULL, 'qtrelation', 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (61, 'qt_relation', 'qtrelation', 2, 16, NULL, 'qtrelation', 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (170, 'layer_groups', 'layer_groups', 4, 47, NULL, 'layer_groups', 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (171, 'layer_groups', 'layer_groups', 5, 47, NULL, 'layer_groups', 11, NULL, NULL, NULL);
INSERT INTO e_form VALUES (202, 'project_languages', 'project_languages', 0, 48, NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO e_form VALUES (203, 'project_languages', 'project_languages', 1, 48, NULL, NULL, 2, NULL, NULL, NULL);


--
-- TOC entry 3368 (class 0 OID 12134211)
-- Dependencies: 2626
-- Data for Name: e_language; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_language VALUES ('en', 'English', 1);
INSERT INTO e_language VALUES ('fr', 'Francais', 2);
INSERT INTO e_language VALUES ('de', 'Deutsch', 3);
INSERT INTO e_language VALUES ('es', 'Espanol', 4);
INSERT INTO e_language VALUES ('it', 'Italiano', 5);


--
-- TOC entry 3369 (class 0 OID 12134217)
-- Dependencies: 2627
-- Data for Name: e_layertype; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_layertype VALUES (5, 'annotation          ', 4, NULL);
INSERT INTO e_layertype VALUES (1, 'point               ', 0, NULL);
INSERT INTO e_layertype VALUES (2, 'line                ', 1, NULL);
INSERT INTO e_layertype VALUES (3, 'polygon             ', 2, NULL);
INSERT INTO e_layertype VALUES (4, 'raster              ', 3, NULL);
INSERT INTO e_layertype VALUES (8, 'tileindex', 7, NULL);
INSERT INTO e_layertype VALUES (10, 'tileraster', 100, NULL);
INSERT INTO e_layertype VALUES (11, 'chart', 8, NULL);


--
-- TOC entry 3370 (class 0 OID 12134223)
-- Dependencies: 2628
-- Data for Name: e_lblposition; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_lblposition VALUES (1, 'UL', NULL);
INSERT INTO e_lblposition VALUES (2, 'UC', NULL);
INSERT INTO e_lblposition VALUES (3, 'UR', NULL);
INSERT INTO e_lblposition VALUES (4, 'CL', NULL);
INSERT INTO e_lblposition VALUES (5, 'CC', NULL);
INSERT INTO e_lblposition VALUES (6, 'CR', NULL);
INSERT INTO e_lblposition VALUES (7, 'LL', NULL);
INSERT INTO e_lblposition VALUES (8, 'LC', NULL);
INSERT INTO e_lblposition VALUES (9, 'LR', NULL);
INSERT INTO e_lblposition VALUES (10, 'AUTO', NULL);


--
-- TOC entry 3371 (class 0 OID 12134229)
-- Dependencies: 2629
-- Data for Name: e_legendtype; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_legendtype VALUES (1, 'auto', 1);
INSERT INTO e_legendtype VALUES (0, 'nessuna', 2);


--
-- TOC entry 3372 (class 0 OID 12134235)
-- Dependencies: 2630
-- Data for Name: e_level; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_level VALUES (12, 'class', 'class', 6, 11, 4, 0, 1, 11, 'class', 2);
INSERT INTO e_level VALUES (19, 'qt_link', 'layer', 12, 11, 4, 1, 0, 11, 'qt_link', 2);
INSERT INTO e_level VALUES (17, 'qtfield', 'qtfield', 11, 11, 4, 1, 1, 11, 'qtfield', 2);
INSERT INTO e_level VALUES (16, 'qtrelation', 'qtrelation', 10, 11, 4, 1, 1, 11, 'qtrelation', 2);
INSERT INTO e_level VALUES (8, 'mapset', 'mapset', 15, 2, 1, 0, 1, 2, 'mapset', 2);
INSERT INTO e_level VALUES (100, 'classgroup', 'layer', NULL, 11, 4, 1, 0, 11, 'classgroup', 2);
INSERT INTO e_level VALUES (1, 'root', NULL, 1, NULL, NULL, 0, 0, NULL, NULL, 2);
INSERT INTO e_level VALUES (2, 'project', 'project', 2, 1, 0, 0, 1, 1, 'project', 2);
INSERT INTO e_level VALUES (3, 'groups', 'groups', 7, 1, 0, 0, 0, 1, 'groups', 1);
INSERT INTO e_level VALUES (4, 'users', 'users', 6, 1, 0, 0, 0, 1, 'users', 1);
INSERT INTO e_level VALUES (5, 'theme', 'theme', 3, 2, 1, 0, 5, 2, 'theme', 2);
INSERT INTO e_level VALUES (6, 'project_srs', 'project_srs', 4, 2, 1, 1, 1, 2, 'project_srs', 2);
INSERT INTO e_level VALUES (7, 'catalog', 'catalog', 13, 2, 1, 1, 2, 2, 'catalog', 2);
INSERT INTO e_level VALUES (9, 'link', 'link', 15, 2, 1, 1, 4, 2, 'link', 2);
INSERT INTO e_level VALUES (10, 'layergroup', 'layergroup', 4, 5, 2, 0, 1, 5, 'layergroup', 2);
INSERT INTO e_level VALUES (11, 'layer', 'layer', 5, 10, 3, 0, 1, 10, 'layer', 2);
INSERT INTO e_level VALUES (14, 'style', 'style', 7, 12, 5, 1, 1, 12, 'style', 2);
INSERT INTO e_level VALUES (21, 'mapset_groups', 'mapset_groups', 16, 8, 2, 1, 4, 8, 'mapset_usergroup', 2);
INSERT INTO e_level VALUES (22, 'mapset_layergroup', 'mapset_layergroup', 17, 8, 2, 1, 1, 8, 'mapset_layergroup', 2);
INSERT INTO e_level VALUES (23, 'mapset_qt', 'mapset_qt', 18, 8, 2, 1, 2, 8, 'mapset_qt', 2);
INSERT INTO e_level VALUES (24, 'mapset_link', 'mapset_link', 19, 8, 2, 1, 3, 8, 'mapset_link', 2);
INSERT INTO e_level VALUES (27, 'selgroup', 'selgroup', NULL, 2, 1, 0, 8, 2, 'selgroup', 2);
INSERT INTO e_level VALUES (28, 'qt_selgroup', 'selgroup', NULL, 27, 2, 1, 1, 27, 'qt_selgroup', 2);
INSERT INTO e_level VALUES (33, 'project_admin', 'project_admin', 15, 2, 1, 1, 0, 2, 'project_admin', 2);
INSERT INTO e_level VALUES (45, 'group_users', 'user_groups', NULL, 4, 2, 1, 0, 4, 'user_group', 1);
INSERT INTO e_level VALUES (46, 'user_groups', 'group_users', NULL, 3, 2, 1, 0, 3, 'user_group', 1);
INSERT INTO e_level VALUES (32, 'user_project', 'project', 8, 2, 1, 1, 0, 2, 'user_project', 2);
INSERT INTO e_level VALUES (47, 'layer_groups', 'layer_groups', NULL, 11, 4, 1, 1, 11, 'layer_groups', 2);
INSERT INTO e_level VALUES (48, 'project_languages', 'project_languages', NULL, 2, 1, 1, 1, 2, 'project_languages', 2);


--
-- TOC entry 3373 (class 0 OID 12134243)
-- Dependencies: 2631
-- Data for Name: e_orderby; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_orderby VALUES (0, 'Nessuno', NULL);
INSERT INTO e_orderby VALUES (1, 'Crescente', NULL);
INSERT INTO e_orderby VALUES (2, 'Decresente', NULL);


--
-- TOC entry 3374 (class 0 OID 12134249)
-- Dependencies: 2632
-- Data for Name: e_outputformat; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_outputformat VALUES (2, 'AGG PNG', 'AGG/PNG', 'image/png', 'PC256', 'png', NULL, NULL);
INSERT INTO e_outputformat VALUES (3, 'AGG JPG', 'AGG/JPG', 'image/jpg', 'RGB', 'jpg', NULL, NULL);
INSERT INTO e_outputformat VALUES (4, 'PNG 8 bit', 'GD/PNG', 'image/png', 'PC256', 'png', NULL, NULL);
INSERT INTO e_outputformat VALUES (5, 'PNG 24 bit', 'GD/PNG', 'image/png', 'RGB', 'png', NULL, NULL);
INSERT INTO e_outputformat VALUES (6, 'PNG 32 bit Trasp', 'GD/PNG', 'image/png', 'RGBA', 'png', NULL, NULL);
INSERT INTO e_outputformat VALUES (7, 'AGG Q', 'AGG/PNG', 'image/png; mode=8bit', 'RGB', 'png', '    FORMATOPTION "QUANTIZE_FORCE=ON"
    FORMATOPTION "QUANTIZE_DITHER=OFF"
    FORMATOPTION "QUANTIZE_COLORS=256"', NULL);
INSERT INTO e_outputformat VALUES (1, 'AGG PNG 24 bit', 'AGG/PNG', 'image/png; mode=24bit', 'RGB', 'png', NULL, NULL);


--
-- TOC entry 3375 (class 0 OID 12134255)
-- Dependencies: 2633
-- Data for Name: e_owstype; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_owstype VALUES (3, 'VirtualEarth', 3);
INSERT INTO e_owstype VALUES (4, 'Yahoo', 4);
INSERT INTO e_owstype VALUES (5, 'OSM', 5);
INSERT INTO e_owstype VALUES (1, 'OWS', 1);
INSERT INTO e_owstype VALUES (7, 'Google v.3', 7);
INSERT INTO e_owstype VALUES (8, 'Bing tiles', 8);
INSERT INTO e_owstype VALUES (2, 'Google v.2', 2);
INSERT INTO e_owstype VALUES (6, 'TMS', 6);


--
-- TOC entry 3376 (class 0 OID 12134261)
-- Dependencies: 2634
-- Data for Name: e_papersize; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_papersize VALUES (1, 'A4 Verticale', 'A4', 'P', NULL);
INSERT INTO e_papersize VALUES (2, 'A4 Orizzontale', 'A4', 'L', NULL);
INSERT INTO e_papersize VALUES (3, 'A3 Verticale', 'A3', 'P', NULL);
INSERT INTO e_papersize VALUES (4, 'A3 Orizzontale', 'A3', 'L', NULL);
INSERT INTO e_papersize VALUES (5, 'A2 Verticale', 'A2', 'P', NULL);
INSERT INTO e_papersize VALUES (6, 'A2 Orizzontale', 'A2', 'L', NULL);
INSERT INTO e_papersize VALUES (7, 'A1 Verticale', 'A1', 'P', NULL);
INSERT INTO e_papersize VALUES (8, 'A1 Orizzontale', 'A1', 'L', NULL);
INSERT INTO e_papersize VALUES (9, 'A0 Verticale', 'A0', 'P', NULL);
INSERT INTO e_papersize VALUES (10, 'A0 Orizzontale', 'A0', 'L', NULL);


--
-- TOC entry 3377 (class 0 OID 12134267)
-- Dependencies: 2635
-- Data for Name: e_qtrelationtype; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_qtrelationtype VALUES (1, 'Dettaglio (1 a 1)', NULL);
INSERT INTO e_qtrelationtype VALUES (2, 'Secondaria (Info 1 a molti)', NULL);


--
-- TOC entry 3378 (class 0 OID 12134273)
-- Dependencies: 2636
-- Data for Name: e_resultype; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_resultype VALUES (1, 'Si', 2);
INSERT INTO e_resultype VALUES (4, 'No', 4);


--
-- TOC entry 3379 (class 0 OID 12134279)
-- Dependencies: 2637
-- Data for Name: e_searchtype; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_searchtype VALUES (4, 'Numerico', NULL);
INSERT INTO e_searchtype VALUES (5, 'Data', NULL);
INSERT INTO e_searchtype VALUES (1, 'Testo', NULL);
INSERT INTO e_searchtype VALUES (2, 'Parte di testo', NULL);
INSERT INTO e_searchtype VALUES (3, 'Lista di valori', NULL);
INSERT INTO e_searchtype VALUES (0, 'Nessuno', NULL);


--
-- TOC entry 3380 (class 0 OID 12134285)
-- Dependencies: 2638
-- Data for Name: e_sizeunits; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_sizeunits VALUES (2, 'feet', NULL);
INSERT INTO e_sizeunits VALUES (3, 'inches', NULL);
INSERT INTO e_sizeunits VALUES (1, 'pixels', NULL);
INSERT INTO e_sizeunits VALUES (4, 'kilometers', NULL);
INSERT INTO e_sizeunits VALUES (5, 'meters', NULL);
INSERT INTO e_sizeunits VALUES (6, 'miles', NULL);
INSERT INTO e_sizeunits VALUES (7, 'dd', NULL);


--
-- TOC entry 3381 (class 0 OID 12134291)
-- Dependencies: 2639
-- Data for Name: e_symbolcategory; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_symbolcategory VALUES (1, 'Tutti', NULL);


--
-- TOC entry 3382 (class 0 OID 12134297)
-- Dependencies: 2640
-- Data for Name: e_tiletype; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO e_tiletype VALUES (0, 'no Tiles', 1);
INSERT INTO e_tiletype VALUES (1, 'WMS Tiles', 2);
INSERT INTO e_tiletype VALUES (2, 'Tilecache Tiles', 3);


--
-- TOC entry 3384 (class 0 OID 12134312)
-- Dependencies: 2643
-- Data for Name: font; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO font VALUES ('arial', 'arial.ttf');
INSERT INTO font VALUES ('arial-bold', 'arialbd.ttf');
INSERT INTO font VALUES ('arial-italic', 'ariali.ttf');
INSERT INTO font VALUES ('arial-bold-italic', 'arialbi.ttf');
INSERT INTO font VALUES ('arial_black', 'ariblk.ttf');
INSERT INTO font VALUES ('arial_narrow', 'arialn.ttf');
INSERT INTO font VALUES ('arial_narrow-bold', 'arialnb.ttf');
INSERT INTO font VALUES ('arial_narrow-italic', 'arialni.ttf');
INSERT INTO font VALUES ('arial_narrow-bold-italic', 'arialnbi.ttf');
INSERT INTO font VALUES ('comic_sans', 'comic.ttf');
INSERT INTO font VALUES ('comic_sans-bold', 'comicbd.ttf');
INSERT INTO font VALUES ('courier', 'cour.ttf');
INSERT INTO font VALUES ('courier-bold', 'courbd.ttf');
INSERT INTO font VALUES ('courier-italic', 'couri.ttf');
INSERT INTO font VALUES ('courier-bold-italic', 'courbi.ttf');
INSERT INTO font VALUES ('georgia', 'georgia.ttf');
INSERT INTO font VALUES ('georgia-bold', 'georgiab.ttf');
INSERT INTO font VALUES ('georgia-italic', 'georgiai.ttf');
INSERT INTO font VALUES ('georgia-bold-italic', 'georgiaz.ttf');
INSERT INTO font VALUES ('impact', 'impact.ttf');
INSERT INTO font VALUES ('tahoma', 'tahoma.ttf');
INSERT INTO font VALUES ('tahoma-bold', 'tahomabd.ttf');
INSERT INTO font VALUES ('times', 'times.ttf');
INSERT INTO font VALUES ('times-bold', 'timesbd.ttf');
INSERT INTO font VALUES ('times-italic', 'timesi.ttf');
INSERT INTO font VALUES ('times-bold-italic', 'timesbi.ttf');
INSERT INTO font VALUES ('trebuchet_ms', 'trebuc.ttf');
INSERT INTO font VALUES ('trebuchet_ms-bold', 'trebucbd.ttf');
INSERT INTO font VALUES ('trebuchet_ms-italic', 'trebucit.ttf');
INSERT INTO font VALUES ('trebuchet_ms-bold-italic', 'trebucbi.ttf');
INSERT INTO font VALUES ('verdana', 'verdana.ttf');
INSERT INTO font VALUES ('verdana-bold', 'verdanab.ttf');
INSERT INTO font VALUES ('verdana-italic', 'verdanai.ttf');
INSERT INTO font VALUES ('verdana-bold-italic', 'verdanaz.ttf');
INSERT INTO font VALUES ('romanc', 'romanc__.ttf');
INSERT INTO font VALUES ('romantic', 'romantic.ttf');
INSERT INTO font VALUES ('ctrn_liguria', 'HELN.TTF');
INSERT INTO font VALUES ('catasto', 'catasto.ttf');
INSERT INTO font VALUES ('prgsori', 'prgsori.ttf');
INSERT INTO font VALUES ('gw', 'gw.ttf');
INSERT INTO font VALUES ('iride', 'iride.ttf');
INSERT INTO font VALUES ('atena', 'atena.ttf');
INSERT INTO font VALUES ('spezia_cciaa', 'spezia_cciaa.ttf');
INSERT INTO font VALUES ('esri_5', 'esri_5.ttf');
INSERT INTO font VALUES ('esri_25', 'esri_25.ttf');
INSERT INTO font VALUES ('esri_160', 'esri_160.ttf');
INSERT INTO font VALUES ('esri_12', 'esri_12.ttf');
INSERT INTO font VALUES ('esri_9', 'esri_9.ttf');
INSERT INTO font VALUES ('esri_3', 'esri_3.ttf');
INSERT INTO font VALUES ('esri_159', 'esri_159.ttf');
INSERT INTO font VALUES ('esri_22', 'esri_22.ttf');
INSERT INTO font VALUES ('esri_39', 'esri_39.ttf');
INSERT INTO font VALUES ('esri_13', 'esri_13.ttf');
INSERT INTO font VALUES ('esri_730', 'esri_730.ttf');
INSERT INTO font VALUES ('esri_376', 'esri_376.ttf');
INSERT INTO font VALUES ('esri_153', 'esri_153.ttf');
INSERT INTO font VALUES ('esri_144', 'esri_144.ttf');
INSERT INTO font VALUES ('esri_131', 'esri_131.ttf');
INSERT INTO font VALUES ('esri_24', 'esri_24.ttf');
INSERT INTO font VALUES ('esri_216', 'esri_216.ttf');
INSERT INTO font VALUES ('esri_801', 'esri_801.ttf');
INSERT INTO font VALUES ('esri_375', 'esri_375.ttf');
INSERT INTO font VALUES ('esri_23', 'esri_23.ttf');
INSERT INTO font VALUES ('esri_803', 'esri_803.ttf');
INSERT INTO font VALUES ('esri_405', 'esri_405.ttf');
INSERT INTO font VALUES ('esri_652', 'esri_652.ttf');
INSERT INTO font VALUES ('esri_8', 'esri_8.ttf');
INSERT INTO font VALUES ('esri_44', 'esri_44.ttf');
INSERT INTO font VALUES ('esri_804', 'esri_804.ttf');
INSERT INTO font VALUES ('esri_17', 'esri_17.ttf');
INSERT INTO font VALUES ('esri_221', 'esri_221.ttf');
INSERT INTO font VALUES ('esri_500', 'esri_500.ttf');
INSERT INTO font VALUES ('esri_14', 'esri_14.ttf');
INSERT INTO font VALUES ('esri_15', 'esri_15.ttf');
INSERT INTO font VALUES ('esri_400', 'esri_400.ttf');
INSERT INTO font VALUES ('esri_29', 'esri_29.ttf');
INSERT INTO font VALUES ('esri_225', 'esri_225.ttf');
INSERT INTO font VALUES ('esri_48', 'esri_48.ttf');
INSERT INTO font VALUES ('esri_150', 'esri_150.ttf');
INSERT INTO font VALUES ('esri_377', 'esri_377.ttf');
INSERT INTO font VALUES ('esri_1', 'esri_1.ttf');
INSERT INTO font VALUES ('esri_19', 'esri_19.ttf');
INSERT INTO font VALUES ('esri_149', 'esri_149.ttf');
INSERT INTO font VALUES ('esri_151', 'esri_151.ttf');
INSERT INTO font VALUES ('esri_651', 'esri_651.ttf');
INSERT INTO font VALUES ('esri_220', 'esri_220.ttf');
INSERT INTO font VALUES ('esri_2', 'esri_2.ttf');
INSERT INTO font VALUES ('esri_40', 'esri_40.ttf');
INSERT INTO font VALUES ('esri_132', 'esri_132.ttf');
INSERT INTO font VALUES ('esri_7', 'esri_7.ttf');
INSERT INTO font VALUES ('esri_161', 'esri_161.ttf');
INSERT INTO font VALUES ('esri_222', 'esri_222.ttf');
INSERT INTO font VALUES ('esri_21', 'esri_21.ttf');
INSERT INTO font VALUES ('esri_47', 'esri_47.ttf');
INSERT INTO font VALUES ('esri_224', 'esri_224.ttf');
INSERT INTO font VALUES ('esri_133', 'esri_133.ttf');
INSERT INTO font VALUES ('esri_152', 'esri_152.ttf');
INSERT INTO font VALUES ('esri_121', 'esri_121.ttf');
INSERT INTO font VALUES ('esri_20', 'esri_20.ttf');
INSERT INTO font VALUES ('esri_26', 'esri_26.ttf');
INSERT INTO font VALUES ('esri_130', 'esri_130.ttf');
INSERT INTO font VALUES ('esri_49', 'esri_49.ttf');
INSERT INTO font VALUES ('esri_16', 'esri_16.ttf');
INSERT INTO font VALUES ('esri_4', 'esri_4.ttf');
INSERT INTO font VALUES ('esri_34', 'esri_34.ttf');
INSERT INTO font VALUES ('esri_33', 'esri_33.ttf');
INSERT INTO font VALUES ('esri_802', 'esri_802.ttf');
INSERT INTO font VALUES ('esri_800', 'esri_800.ttf');
INSERT INTO font VALUES ('esri_406', 'esri_406.ttf');
INSERT INTO font VALUES ('esri_223', 'esri_223.ttf');
INSERT INTO font VALUES ('esri_30s', 'esri_30s.ttf');
INSERT INTO font VALUES ('padania_acque', 'padania_acque.ttf');
INSERT INTO font VALUES ('galatone_si', 'galatone_si.ttf');
INSERT INTO font VALUES ('cogeme', 'cogeme.ttf');
INSERT INTO font VALUES ('wingdng3', 'wingdng3.ttf');
INSERT INTO font VALUES ('catasto2', 'catasto2.ttf');
INSERT INTO font VALUES ('dejavu-sans', 'dejavu-sans.ttf');
INSERT INTO font VALUES ('dejavu-sans-bold', 'dejavu-sans-bold.ttf');
INSERT INTO font VALUES ('dejavu-sans-bold-italic', 'dejavu-sans-bold-italic.ttf');
INSERT INTO font VALUES ('dejavu-serif', 'dejavu-serif.ttf');
INSERT INTO font VALUES ('dejavu-serif-bold', 'dejavu-serif-bold');
INSERT INTO font VALUES ('dejavu-serif-bold-italic', 'dejavu-serif-bold-italic.ttf');
INSERT INTO font VALUES ('dejavu-serif-italic', 'dejavu-serif-italic.ttf');
INSERT INTO font VALUES ('esri_11', 'esri_11.ttf');
INSERT INTO font VALUES ('dejavu-sans-italic', 'dejavu-sans-italic.ttf');


--
-- TOC entry 3383 (class 0 OID 12134303)
-- Dependencies: 2641
-- Data for Name: form_level; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO form_level VALUES (1, 1, 3, 2, 1, 1);
INSERT INTO form_level VALUES (2, 2, 0, 3, 1, 1);
INSERT INTO form_level VALUES (4, 2, 3, 8, 5, 1);
INSERT INTO form_level VALUES (5, 2, 3, 5, 8, 1);
INSERT INTO form_level VALUES (7, 2, 1, 7, 1, 1);
INSERT INTO form_level VALUES (8, 2, 2, 6, 1, 1);
INSERT INTO form_level VALUES (14, 2, 3, 12, 3, 1);
INSERT INTO form_level VALUES (15, 6, 1, 13, 1, 1);
INSERT INTO form_level VALUES (16, 6, 2, 13, 1, 1);
INSERT INTO form_level VALUES (17, 6, 0, 13, 1, 1);
INSERT INTO form_level VALUES (19, 8, 0, 26, 1, 1);
INSERT INTO form_level VALUES (20, 8, 1, 27, 1, 1);
INSERT INTO form_level VALUES (21, 8, 2, 28, 1, 1);
INSERT INTO form_level VALUES (22, 5, 0, 9, 1, 1);
INSERT INTO form_level VALUES (23, 5, 1, 10, 1, 1);
INSERT INTO form_level VALUES (24, 5, 2, 11, 1, 1);
INSERT INTO form_level VALUES (25, 5, 3, 30, 3, 1);
INSERT INTO form_level VALUES (26, 10, 0, 31, 1, 1);
INSERT INTO form_level VALUES (27, 10, 1, 32, 1, 1);
INSERT INTO form_level VALUES (28, 10, 2, 33, 1, 1);
INSERT INTO form_level VALUES (29, 10, 3, 34, 3, 1);
INSERT INTO form_level VALUES (30, 11, 0, 35, 1, 1);
INSERT INTO form_level VALUES (31, 11, 1, 36, 1, 1);
INSERT INTO form_level VALUES (32, 11, 2, 37, 1, 1);
INSERT INTO form_level VALUES (34, 12, 0, 39, 1, 1);
INSERT INTO form_level VALUES (35, 12, 1, 40, 1, 1);
INSERT INTO form_level VALUES (36, 12, 2, 41, 2, 1);
INSERT INTO form_level VALUES (37, 12, 3, 42, 3, 1);
INSERT INTO form_level VALUES (38, 14, 0, 43, 1, 1);
INSERT INTO form_level VALUES (39, 14, 1, 44, 1, 1);
INSERT INTO form_level VALUES (40, 14, 2, 45, 1, 1);
INSERT INTO form_level VALUES (45, 2, 3, 50, 4, 1);
INSERT INTO form_level VALUES (46, 7, 0, 51, 1, 1);
INSERT INTO form_level VALUES (47, 7, 1, 52, 1, 1);
INSERT INTO form_level VALUES (48, 7, 2, 53, 1, 1);
INSERT INTO form_level VALUES (54, 16, 0, 59, 1, 1);
INSERT INTO form_level VALUES (55, 16, 1, 60, 1, 1);
INSERT INTO form_level VALUES (56, 16, 2, 61, 1, 1);
INSERT INTO form_level VALUES (57, 17, 0, 63, 1, 1);
INSERT INTO form_level VALUES (58, 17, 1, 64, 1, 1);
INSERT INTO form_level VALUES (59, 17, 2, 65, 1, 1);
INSERT INTO form_level VALUES (63, 2, 3, 70, 7, 1);
INSERT INTO form_level VALUES (64, 9, 0, 72, 1, 1);
INSERT INTO form_level VALUES (65, 9, 1, 73, 1, 1);
INSERT INTO form_level VALUES (66, 9, 2, 74, 1, 1);
INSERT INTO form_level VALUES (74, 8, 3, 81, 2, 1);
INSERT INTO form_level VALUES (75, 21, 1, 82, 1, 1);
INSERT INTO form_level VALUES (77, 8, 3, 84, 6, 1);
INSERT INTO form_level VALUES (78, 22, 1, 85, 1, 1);
INSERT INTO form_level VALUES (80, 8, 3, 87, 4, 1);
INSERT INTO form_level VALUES (81, 23, 1, 88, 1, 1);
INSERT INTO form_level VALUES (83, 8, 3, 90, 5, 1);
INSERT INTO form_level VALUES (84, 24, 1, 91, 1, 1);
INSERT INTO form_level VALUES (98, 2, 3, 105, 6, 1);
INSERT INTO form_level VALUES (99, 27, 1, 106, 1, 1);
INSERT INTO form_level VALUES (101, 27, 0, 107, 1, 1);
INSERT INTO form_level VALUES (102, 28, 1, 109, 1, 1);
INSERT INTO form_level VALUES (116, 27, 3, 108, 6, 1);
INSERT INTO form_level VALUES (127, 33, 1, 134, 15, 1);
INSERT INTO form_level VALUES (131, 2, 3, 133, 15, 1);
INSERT INTO form_level VALUES (132, 27, 2, 106, 1, 1);
INSERT INTO form_level VALUES (164, 1, 3, 16, 3, 1);
INSERT INTO form_level VALUES (165, 4, 0, 18, 1, 1);
INSERT INTO form_level VALUES (166, 4, 1, 18, 1, 1);
INSERT INTO form_level VALUES (167, 4, 2, 18, 1, 1);
INSERT INTO form_level VALUES (168, 1, 3, 20, 2, 1);
INSERT INTO form_level VALUES (169, 3, 0, 23, 1, 1);
INSERT INTO form_level VALUES (170, 3, 1, 23, 1, 1);
INSERT INTO form_level VALUES (171, 3, 2, 23, 1, 1);
INSERT INTO form_level VALUES (172, 3, 3, 149, 2, 1);
INSERT INTO form_level VALUES (173, 45, 1, 150, 1, 1);
INSERT INTO form_level VALUES (175, 4, 3, 151, 2, 1);
INSERT INTO form_level VALUES (176, 46, 1, 152, 1, 1);
INSERT INTO form_level VALUES (79, 22, -1, 86, 2, 1);
INSERT INTO form_level VALUES (69, 16, 1, 75, 2, 0);
INSERT INTO form_level VALUES (76, 21, 1, 83, 2, 0);
INSERT INTO form_level VALUES (82, 23, 1, 89, 2, 0);
INSERT INTO form_level VALUES (85, 24, 1, 92, 2, 0);
INSERT INTO form_level VALUES (100, 27, 2, 105, 2, 0);
INSERT INTO form_level VALUES (163, 27, 3, 151, 1, 1);
INSERT INTO form_level VALUES (33, 11, 3, 38, 3, 1);
INSERT INTO form_level VALUES (500, 11, 3, 200, 2, 0);
INSERT INTO form_level VALUES (501, 100, 0, 201, 1, 0);
INSERT INTO form_level VALUES (502, 100, 1, 201, 1, 0);
INSERT INTO form_level VALUES (503, 100, 2, 201, 1, 0);
INSERT INTO form_level VALUES (51, 11, 3, 58, 4, 1);
INSERT INTO form_level VALUES (52, 11, 3, 62, 5, 1);
INSERT INTO form_level VALUES (60, 19, 0, 67, 1, 1);
INSERT INTO form_level VALUES (61, 19, 1, 68, 1, 1);
INSERT INTO form_level VALUES (62, 19, 1, 69, 2, 1);
INSERT INTO form_level VALUES (53, 11, 3, 66, 6, 1);
INSERT INTO form_level VALUES (200, 11, 0, 170, 7, 1);
INSERT INTO form_level VALUES (201, 47, 1, 171, 1, 1);
INSERT INTO form_level VALUES (202, 47, 3, 171, 1, 1);
INSERT INTO form_level VALUES (203, 47, 2, 171, 1, 1);
INSERT INTO form_level VALUES (504, 48, 0, 203, 1, 1);
INSERT INTO form_level VALUES (505, 48, 1, 203, 1, 1);
INSERT INTO form_level VALUES (506, 48, 2, 203, 1, 1);
INSERT INTO form_level VALUES (507, 2, 3, 202, 2, 1);


--
-- TOC entry 3385 (class 0 OID 12134318)
-- Dependencies: 2644
-- Data for Name: groups; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3386 (class 0 OID 12134324)
-- Dependencies: 2645
-- Data for Name: i18n_field; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO i18n_field VALUES (1, 'class', 'class_title');
INSERT INTO i18n_field VALUES (2, 'class', 'expression');
INSERT INTO i18n_field VALUES (3, 'class', 'label_def');
INSERT INTO i18n_field VALUES (4, 'class', 'class_text');
INSERT INTO i18n_field VALUES (5, 'layer', 'layer_title');
INSERT INTO i18n_field VALUES (6, 'layer', 'data_filter');
INSERT INTO i18n_field VALUES (7, 'layer', 'layer_def');
INSERT INTO i18n_field VALUES (8, 'layer', 'metadata');
INSERT INTO i18n_field VALUES (9, 'layer', 'labelitem');
INSERT INTO i18n_field VALUES (10, 'layer', 'classitem');
INSERT INTO i18n_field VALUES (11, 'layergroup', 'layergroup_title');
INSERT INTO i18n_field VALUES (12, 'layergroup', 'sld');
INSERT INTO i18n_field VALUES (13, 'qtfield', 'qtfield_name');
INSERT INTO i18n_field VALUES (14, 'qtfield', 'field_header');
INSERT INTO i18n_field VALUES (15, 'style', 'style_def');
INSERT INTO i18n_field VALUES (16, 'theme', 'theme_title');
INSERT INTO i18n_field VALUES (17, 'theme', 'copyright_string');
INSERT INTO i18n_field VALUES (18, 'mapset', 'mapset_title');
INSERT INTO i18n_field VALUES (19, 'mapset', 'mapset_description');


--
-- TOC entry 3387 (class 0 OID 12134332)
-- Dependencies: 2647
-- Data for Name: layer; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3388 (class 0 OID 12134350)
-- Dependencies: 2648
-- Data for Name: layer_groups; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3389 (class 0 OID 12134359)
-- Dependencies: 2649
-- Data for Name: layergroup; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3390 (class 0 OID 12134375)
-- Dependencies: 2650
-- Data for Name: link; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3391 (class 0 OID 12134384)
-- Dependencies: 2651
-- Data for Name: localization; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3392 (class 0 OID 12134392)
-- Dependencies: 2653
-- Data for Name: mapset; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3393 (class 0 OID 12134408)
-- Dependencies: 2654
-- Data for Name: mapset_groups; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3394 (class 0 OID 12134416)
-- Dependencies: 2655
-- Data for Name: mapset_layergroup; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3395 (class 0 OID 12134425)
-- Dependencies: 2656
-- Data for Name: mapset_link; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3396 (class 0 OID 12134432)
-- Dependencies: 2657
-- Data for Name: mapset_qt; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3397 (class 0 OID 12134438)
-- Dependencies: 2658
-- Data for Name: project; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3398 (class 0 OID 12134454)
-- Dependencies: 2659
-- Data for Name: project_admin; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3399 (class 0 OID 12134460)
-- Dependencies: 2660
-- Data for Name: project_languages; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3400 (class 0 OID 12134466)
-- Dependencies: 2661
-- Data for Name: project_srs; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3401 (class 0 OID 12134472)
-- Dependencies: 2662
-- Data for Name: qt; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3402 (class 0 OID 12134482)
-- Dependencies: 2663
-- Data for Name: qt_link; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3403 (class 0 OID 12134485)
-- Dependencies: 2664
-- Data for Name: qt_selgroup; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3404 (class 0 OID 12134488)
-- Dependencies: 2665
-- Data for Name: qtfield; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3405 (class 0 OID 12134503)
-- Dependencies: 2666
-- Data for Name: qtrelation; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3407 (class 0 OID 12134635)
-- Dependencies: 2696
-- Data for Name: selgroup; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3408 (class 0 OID 12134642)
-- Dependencies: 2697
-- Data for Name: style; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3409 (class 0 OID 12134649)
-- Dependencies: 2698
-- Data for Name: symbol; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--

INSERT INTO symbol VALUES ('NOISE_INSP', 1, 0, NULL, 'TYPE TRUETYPE
FONT "MSYMB"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#038;"');
INSERT INTO symbol VALUES ('DASH1', 1, 0, NULL, 'TYPE ELLIPSE
POINTS
1 1
END
STYLE
5 5 5 5
END');
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
INSERT INTO symbol VALUES ('VERTICAL', 1, 0, NULL, 'TYPE VECTOR
POINTS
0.5 0
0.5 1
END');
INSERT INTO symbol VALUES ('HORIZONTAL', 1, 0, NULL, 'TYPE VECTOR
POINTS
0 0.5
1 0.5
END');
INSERT INTO symbol VALUES ('CIRCLE', 1, 0, NULL, 'TYPE ELLIPSE
FILLED TRUE
POINTS
1 1
END');
INSERT INTO symbol VALUES ('AIR', 1, 0, NULL, 'TYPE TRUETYPE
FONT "MSYMB"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#033;"');
INSERT INTO symbol VALUES ('WATER', 1, 0, NULL, 'TYPE TRUETYPE
FONT "MSYMB"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#034;"');
INSERT INTO symbol VALUES ('FAUNA', 1, 0, NULL, 'TYPE TRUETYPE
FONT "MSYMB"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#035;"');
INSERT INTO symbol VALUES ('FLORA', 1, 0, NULL, 'TYPE TRUETYPE
FONT "MSYMB"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#036;"');
INSERT INTO symbol VALUES ('NOISE', 1, 0, NULL, 'TYPE TRUETYPE
FONT "MSYMB"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#037;"');
INSERT INTO symbol VALUES ('DIAGONAL1', 1, 0, NULL, 'TYPE VECTOR
POINTS
0 1
1 0
END
');
INSERT INTO symbol VALUES ('DIAGONAL2', 1, 0, NULL, 'TYPE VECTOR
POINTS
0 0
1 1
END
');
INSERT INTO symbol VALUES ('DASH2', 1, 0, '\\211PNG\\015\\012\\032\\012\\000\\000\\000\\015IHDR\\000\\000\\000\\030\\000\\000\\000\\020\\001\\003\\000\\000\\0006\\352-\\326\\000\\000\\000\\006PLTE\\377\\377\\377\\000\\000\\000U\\302\\323~\\000\\000\\0001IDAT\\010\\231c`\\000\\001; ~\\334\\300\\300\\330p\\200\\201\\231!\\201\\201\\211A\\201\\201\\215\\301\\200\\201\\205A\\000\\216A|\\2208H\\036\\244\\016\\244\\036\\250\\017\\000\\334(\\007wW\\276c\\227\\000\\000\\000\\000IEND\\256B`\\202', '  Type ELLIPSE
  Points
   1 1
  END
  STYLE
   2 5 2 5
  END');
INSERT INTO symbol VALUES ('COMUNE', 1, 0, '\\211PNG\\015\\012\\032\\012\\000\\000\\000\\015IHDR\\000\\000\\000\\030\\000\\000\\000\\020\\001\\003\\000\\000\\0006\\352-\\326\\000\\000\\000\\006PLTE\\377\\377\\377\\000\\000\\000U\\302\\323~\\000\\000\\0001IDAT\\010\\231c`\\000\\001; ~\\334\\300\\300\\330p\\200\\201\\231!\\201\\201\\211A\\201\\201\\215\\301\\200\\201\\205A\\000\\216A|\\2208H\\036\\244\\016\\244\\036\\250\\017\\000\\334(\\007wW\\276c\\227\\000\\000\\000\\000IEND\\256B`\\202', '  Type ELLIPSE
  Points
   1 1
  END
  STYLE
   2 4 2 4
  END');
INSERT INTO symbol VALUES ('STATO', 1, 0, '\\211PNG\\015\\012\\032\\012\\000\\000\\000\\015IHDR\\000\\000\\000\\030\\000\\000\\000\\020\\001\\003\\000\\000\\0006\\352-\\326\\000\\000\\000\\006PLTE\\377\\377\\377\\000\\000\\000U\\302\\323~\\000\\000\\000\\023IDAT\\010\\231c`\\000\\001\\026\\374\\230\\375\\377\\017\\\\r\\0009\\273\\0027P\\254H\\335\\000\\000\\000\\000IEND\\256B`\\202', 'Type VECTOR
  Points
    .5 0
    .5 1
    -99 -99
    0 .5
    1 .5
  END
  STYLE
   1 2 1 2
  END
GAP 2');
INSERT INTO symbol VALUES ('PROVINCIA', 1, 0, '\\211PNG\\015\\012\\032\\012\\000\\000\\000\\015IHDR\\000\\000\\000\\030\\000\\000\\000\\020\\001\\003\\000\\000\\0006\\352-\\326\\000\\000\\000\\006PLTE\\377\\377\\377\\000\\000\\000U\\302\\323~\\000\\000\\0001IDAT\\010\\231c`\\000\\001; ~\\334\\300\\300\\330p\\200\\201\\231!\\201\\201\\211A\\201\\201\\215\\301\\200\\201\\205A\\000\\216A|\\2208H\\036\\244\\016\\244\\036\\250\\017\\000\\334(\\007wW\\276c\\227\\000\\000\\000\\000IEND\\256B`\\202', 'Type ELLIPSE
  Points
   1 1
  END
  STYLE
   4 4 1 4 4
  END');
INSERT INTO symbol VALUES ('FERROVIA', 1, 0, '\\211PNG\\015\\012\\032\\012\\000\\000\\000\\015IHDR\\000\\000\\000\\030\\000\\000\\000\\020\\001\\003\\000\\000\\0006\\352-\\326\\000\\000\\000\\006PLTE\\377\\377\\377\\000\\000\\000U\\302\\323~\\000\\000\\000\\023IDAT\\010\\231c`\\000\\001\\026\\374\\230\\375\\377\\017\\\\r\\0009\\273\\0027P\\254H\\335\\000\\000\\000\\000IEND\\256B`\\202', '  Type VECTOR
  Points
    .5 0
    .5 1
    -99 -99
    0 .5
    1 .5
  END
  STYLE
   1 10 1 10
  END');
INSERT INTO symbol VALUES ('PASCOLO', 1, 0, NULL, '  Type VECTOR
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
		GAP 2');
INSERT INTO symbol VALUES ('INCOLTO', 1, 0, NULL, 'Type VECTOR
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
		GAP 2');
INSERT INTO symbol VALUES ('VIGNETO', 1, 0, NULL, 'Type VECTOR
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
		GAP 2');
INSERT INTO symbol VALUES ('FRUTTETO', 1, 0, NULL, 'Type VECTOR
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
		GAP 2');
INSERT INTO symbol VALUES ('RISAIA', 1, 0, NULL, 'Type VECTOR
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
		GAP 2');
INSERT INTO symbol VALUES ('CIRCLE_EMPTY', 1, 0, NULL, 'TYPE Vector
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
INSERT INTO symbol VALUES ('VIVAIO', 1, 0, NULL, 'TYPE Vector
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
  STYLE
    1 5 1 5
  END
	GAP 2');
INSERT INTO symbol VALUES ('BOSCO', 1, 0, NULL, 'TYPE Vector
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
  STYLE
    1 5 1 5
  END
	GAP 2');
INSERT INTO symbol VALUES ('RUPESTRE', 1, 0, NULL, '  Type VECTOR
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
		GAP 2');
INSERT INTO symbol VALUES ('RANDOM', 1, 0, NULL, '  Type VECTOR
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
INSERT INTO symbol VALUES ('1-3', 1, 0, NULL, 'Type ELLIPSE
	Points
	  1 1
	END
	STYLE
		1 3
	END');
INSERT INTO symbol VALUES ('2-3', 1, 0, NULL, 'Type ELLIPSE
	Points
	  1 1
	END
	STYLE
		2 3
	END');
INSERT INTO symbol VALUES ('3-3', 1, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    3 3
  END ');
INSERT INTO symbol VALUES ('5-3-1-3-1-3', 1, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    5 3 1 3 1 3
  END ');
INSERT INTO symbol VALUES ('5-3-1-3', 1, 0, NULL, 'Type ELLIPSE
  Points
    1 1
  END
  STYLE
    5 3 1 3
  END ');
INSERT INTO symbol VALUES ('3-10', 1, 0, NULL, '  Type ELLIPSE
	Points
	  1 1
	END
	STYLE
		3 10
	END');
INSERT INTO symbol VALUES ('ESRI_072', 1, 0, NULL, '  TYPE TRUETYPE
  FONT "esri_11"
  FILLED TRUE
  ANTIALIAS FALSE
  CHARACTER "&#072;"');
INSERT INTO symbol VALUES ('ESRI_064', 1, 0, NULL, 'TYPE TRUETYPE
FONT "esri_11"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#064;"');
INSERT INTO symbol VALUES ('ESRI_083', 1, 0, NULL, '  TYPE TRUETYPE
  FONT "esri_11"
  FILLED TRUE
  ANTIALIAS FALSE
  CHARACTER "&#083;"');
INSERT INTO symbol VALUES ('45', 1, 0, NULL, 'TYPE VECTOR
POINTS
0 1
1 0
END');
INSERT INTO symbol VALUES ('135', 1, 0, NULL, 'TYPE VECTOR
LINECAP square
LINEJOIN bevel
POINTS
0 0
1 1
END');
INSERT INTO symbol VALUES ('TPO - 1', 1, 0, NULL, 'TYPE TRUETYPE
FONT "tpo"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#065;"');
INSERT INTO symbol VALUES ('TPO - 2', 1, 0, NULL, 'TYPE TRUETYPE
FONT "tpo"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#066;"');
INSERT INTO symbol VALUES ('TPO - 3', 1, 0, NULL, 'TYPE TRUETYPE
FONT "tpo"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#067;"');
INSERT INTO symbol VALUES ('marker-blue', 1, 1, NULL, 'TYPE PIXMAP
IMAGE "../../../../symbols/marker-blue.png"
ANTIALIAS TRUE');
INSERT INTO symbol VALUES ('marker-red', 1, 1, NULL, 'TYPE PIXMAP
IMAGE "../../../../symbols/marker-red.png"
ANTIALIAS TRUE');
INSERT INTO symbol VALUES ('marker-gold', 1, 0, NULL, 'TYPE PIXMAP
IMAGE "../../../../symbols/marker-gold.png"
ANTIALIAS TRUE');
INSERT INTO symbol VALUES ('marker-green', 1, 1, NULL, 'TYPE PIXMAP
IMAGE "../../../../symbols/marker-green.png"
ANTIALIAS TRUE');
INSERT INTO symbol VALUES ('TPO - 4', 1, 0, NULL, 'TYPE TRUETYPE
FONT "tpo"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#068;"');
INSERT INTO symbol VALUES ('TPO - 5', 1, 0, NULL, 'TYPE TRUETYPE
FONT "tpo"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#069;"');
INSERT INTO symbol VALUES ('TPO - 6', 1, 0, NULL, 'TYPE TRUETYPE
FONT "tpo"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#070;"');
INSERT INTO symbol VALUES ('TPO - 7', 1, 0, NULL, 'TYPE TRUETYPE
FONT "tpo"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#071;"');
INSERT INTO symbol VALUES ('TPO - 8', 1, 0, NULL, 'TYPE TRUETYPE
FONT "tpo"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#072;"');
INSERT INTO symbol VALUES ('TPO - 9', 1, 0, NULL, 'TYPE TRUETYPE
FONT "tpo"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#073;"');
INSERT INTO symbol VALUES ('TPO - 10', 1, 0, NULL, 'TYPE TRUETYPE
FONT "tpo"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#074;"');
INSERT INTO symbol VALUES ('TPO - ALTRO', 1, 0, NULL, 'TYPE TRUETYPE
FONT "tpo"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#075;"');
INSERT INTO symbol VALUES ('TPO - IGNOTO', 1, 0, NULL, 'TYPE TRUETYPE
FONT "tpo"
FILLED TRUE
ANTIALIAS FALSE
CHARACTER "&#076;"');
INSERT INTO symbol VALUES ('CIMITERO', 1, 0, NULL, 'TYPE VECTOR
POINTS
.5 0
.5 1
-99 -99
.2 .3
.8 .3
END
GAP 2');
INSERT INTO symbol VALUES ('DIAGONAL 3', 1, 0, NULL, 'TYPE VECTOR
POINTS
0 0
1 1
-99 -99
0 1
-99 -99
1 0
END');
INSERT INTO symbol VALUES ('SKIGIS-SAFETY-35', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sat_id_35.png"');
INSERT INTO symbol VALUES ('SKIGIS-SAFETY-10', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sat_id_10.png"');
INSERT INTO symbol VALUES ('SKIGIS-SAFETY-11', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sat_id_11.png"');
INSERT INTO symbol VALUES ('SKIGIS-SAFETY-12', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sat_id_12.png"');
INSERT INTO symbol VALUES ('SKIGIS-SAFETY-13', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sat_id_13.png"');
INSERT INTO symbol VALUES ('SKIGIS-SAFETY-14', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sat_id_14.png"');
INSERT INTO symbol VALUES ('SKIGIS-SAFETY-15', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sat_id_15.png"');
INSERT INTO symbol VALUES ('SKIGIS-SAFETY-16', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sat_id_16.png"');
INSERT INTO symbol VALUES ('SKIGIS-SAFETY-17', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sat_id_17.png"');
INSERT INTO symbol VALUES ('SKIGIS-SAFETY-01', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sat_id_01.png"');
INSERT INTO symbol VALUES ('SKIGIS-SAFETY-02', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sat_id_02.png"');
INSERT INTO symbol VALUES ('SKIGIS-SAFETY-03', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sat_id_03.png"');
INSERT INTO symbol VALUES ('SKIGIS-SAFETY-04', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sat_id_04.png"');
INSERT INTO symbol VALUES ('SKIGIS-SAFETY-05', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sat_id_05.png"');
INSERT INTO symbol VALUES ('SKIGIS-SAFETY-06', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sat_id_06.png"');
INSERT INTO symbol VALUES ('SKIGIS-SAFETY-07', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sat_id_07.png"');
INSERT INTO symbol VALUES ('SKIGIS-SAFETY-08', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sat_id_08.png"');
INSERT INTO symbol VALUES ('SKIGIS-SAFETY-09', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sat_id_09.png"');
INSERT INTO symbol VALUES ('SKIGIS-SAFETY-PROJ', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sat_id_project.png"');
INSERT INTO symbol VALUES ('SKIGIS-SNOWGUN-01', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sgt_id_01.png"');
INSERT INTO symbol VALUES ('SKIGIS-SNOWGUN-02', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sgt_id_02.png"');
INSERT INTO symbol VALUES ('SKIGIS-SNOWGUN-PROJ', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sgt_id_project.png"');
INSERT INTO symbol VALUES ('SKIGIS-SNOWGUN-00', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/sgt_id_00.png"');
INSERT INTO symbol VALUES ('SKIGIS-PIT-01', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/ptt_id_01.png"');
INSERT INTO symbol VALUES ('SKIGIS-PIT-02', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/ptt_id_02.png"');
INSERT INTO symbol VALUES ('SKIGIS-PIT-03', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/ptt_id_03.png"');
INSERT INTO symbol VALUES ('SKIGIS-PIT-04', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/ptt_id_04.png"');
INSERT INTO symbol VALUES ('SKIGIS-PIT-05', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/ptt_id_05.png"');
INSERT INTO symbol VALUES ('SKIGIS-PIT-06', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/ptt_id_06.png"');
INSERT INTO symbol VALUES ('SKIGIS-PIT-07', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/ptt_id_07.png"');
INSERT INTO symbol VALUES ('SKIGIS-PIT-08', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/ptt_id_08.png"');
INSERT INTO symbol VALUES ('SKIGIS-PIT-00', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/ptt_id_00.png"');
INSERT INTO symbol VALUES ('SKIGIS-PIT-PROJ', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/ptt_id_project.png"');
INSERT INTO symbol VALUES ('SKIGIS-PIT-09', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/ptt_id_09.png"');
INSERT INTO symbol VALUES ('SKIGIS-PIT-10', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/ptt_id_10.png"');
INSERT INTO symbol VALUES ('SKIGIS-PILLAR-00', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/plt_id_00.png"');
INSERT INTO symbol VALUES ('SKIGIS-PILLAR-04', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/plt_id_04.png"');
INSERT INTO symbol VALUES ('SKIGIS-PILLAR-PROJ', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/plt_id_project.png"');
INSERT INTO symbol VALUES ('SKIGIS-PILLAR-01', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/plt_id_01.png"');
INSERT INTO symbol VALUES ('SKIGIS-PILLAR-02', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/plt_id_02.png"');
INSERT INTO symbol VALUES ('SKIGIS-PILLAR-03', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/plt_id_03.png"');
INSERT INTO symbol VALUES ('SKIGIS-PILLAR-07', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/plt_id_07.png"');
INSERT INTO symbol VALUES ('SKIGIS-PILLAR-08', 1, 0, NULL, 'TYPE PIXMAP
  IMAGE "/data/gis_data/default/symbols/skigis/plt_id_08.png"');
INSERT INTO symbol VALUES ('ARROW', 1, 0, NULL, 'TYPE Vector
	FILLED True
	POINTS
	  0 0
		.5 .5
		0 1
		0 0
	END
	ANTIALIAS true
	GAP -10');
INSERT INTO symbol VALUES ('ARROWBACK', 1, 0, NULL, '	TYPE Vector
	FILLED True
	POINTS
	  1 1
		.5 .5
		1 0
		1 1
	END
	ANTIALIAS true
	GAP -10');


--
-- TOC entry 3410 (class 0 OID 12134657)
-- Dependencies: 2699
-- Data for Name: symbol_ttf; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3406 (class 0 OID 12134592)
-- Dependencies: 2687
-- Data for Name: theme; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3411 (class 0 OID 12134669)
-- Dependencies: 2701
-- Data for Name: user_group; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3412 (class 0 OID 12134675)
-- Dependencies: 2702
-- Data for Name: users; Type: TABLE DATA; Schema: gisclient_30; Owner: gisclient
--



--
-- TOC entry 3203 (class 2606 OID 12134699)
-- Dependencies: 2645 2645
-- Name: 18n_field_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY i18n_field
    ADD CONSTRAINT "18n_field_pkey" PRIMARY KEY (i18nf_id);


--
-- TOC entry 3130 (class 2606 OID 12134701)
-- Dependencies: 2615 2615 2615
-- Name: catalog_catalog_name_key; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY catalog
    ADD CONSTRAINT catalog_catalog_name_key UNIQUE (catalog_name, project_name);


--
-- TOC entry 3136 (class 2606 OID 12134703)
-- Dependencies: 2616 2616 2616
-- Name: catalog_import_catalog_import_name_key; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY catalog_import
    ADD CONSTRAINT catalog_import_catalog_import_name_key UNIQUE (catalog_import_name, project_name);


--
-- TOC entry 3138 (class 2606 OID 12134705)
-- Dependencies: 2616 2616
-- Name: catalog_import_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY catalog_import
    ADD CONSTRAINT catalog_import_pkey PRIMARY KEY (catalog_import_id);


--
-- TOC entry 3132 (class 2606 OID 12134707)
-- Dependencies: 2615 2615
-- Name: catalog_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY catalog
    ADD CONSTRAINT catalog_pkey PRIMARY KEY (catalog_id);


--
-- TOC entry 3143 (class 2606 OID 12134709)
-- Dependencies: 2617 2617 2617
-- Name: class_layer_id_key; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY class
    ADD CONSTRAINT class_layer_id_key UNIQUE (layer_id, class_name);


--
-- TOC entry 3145 (class 2606 OID 12134711)
-- Dependencies: 2617 2617
-- Name: class_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY class
    ADD CONSTRAINT class_pkey PRIMARY KEY (class_id);


--
-- TOC entry 3149 (class 2606 OID 12134713)
-- Dependencies: 2618 2618
-- Name: classgroup_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY classgroup
    ADD CONSTRAINT classgroup_pkey PRIMARY KEY (classgroup_id);


--
-- TOC entry 3151 (class 2606 OID 12134715)
-- Dependencies: 2619 2619
-- Name: e_charset_encodings_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_charset_encodings
    ADD CONSTRAINT e_charset_encodings_pkey PRIMARY KEY (charset_encodings_id);


--
-- TOC entry 3153 (class 2606 OID 12134717)
-- Dependencies: 2620 2620
-- Name: e_conntype_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_conntype
    ADD CONSTRAINT e_conntype_pkey PRIMARY KEY (conntype_id);


--
-- TOC entry 3155 (class 2606 OID 12134719)
-- Dependencies: 2621 2621
-- Name: e_datatype_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_datatype
    ADD CONSTRAINT e_datatype_pkey PRIMARY KEY (datatype_id);


--
-- TOC entry 3157 (class 2606 OID 12134721)
-- Dependencies: 2622 2622
-- Name: e_fieldformat_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_fieldformat
    ADD CONSTRAINT e_fieldformat_pkey PRIMARY KEY (fieldformat_id);


--
-- TOC entry 3159 (class 2606 OID 12134723)
-- Dependencies: 2623 2623
-- Name: e_fieldtype_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_fieldtype
    ADD CONSTRAINT e_fieldtype_pkey PRIMARY KEY (fieldtype_id);


--
-- TOC entry 3161 (class 2606 OID 12134725)
-- Dependencies: 2624 2624
-- Name: e_filetype_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_filetype
    ADD CONSTRAINT e_filetype_pkey PRIMARY KEY (filetype_id);


--
-- TOC entry 3163 (class 2606 OID 12134727)
-- Dependencies: 2625 2625
-- Name: e_form_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_form
    ADD CONSTRAINT e_form_pkey PRIMARY KEY (id);


--
-- TOC entry 3165 (class 2606 OID 12134729)
-- Dependencies: 2626 2626
-- Name: e_language_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_language
    ADD CONSTRAINT e_language_pkey PRIMARY KEY (language_id);


--
-- TOC entry 3167 (class 2606 OID 12134731)
-- Dependencies: 2627 2627
-- Name: e_layertype_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_layertype
    ADD CONSTRAINT e_layertype_pkey PRIMARY KEY (layertype_id);


--
-- TOC entry 3169 (class 2606 OID 12134733)
-- Dependencies: 2628 2628
-- Name: e_lblposition_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_lblposition
    ADD CONSTRAINT e_lblposition_pkey PRIMARY KEY (lblposition_id);


--
-- TOC entry 3171 (class 2606 OID 12134735)
-- Dependencies: 2629 2629
-- Name: e_legendtype_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_legendtype
    ADD CONSTRAINT e_legendtype_pkey PRIMARY KEY (legendtype_id);


--
-- TOC entry 3173 (class 2606 OID 12134737)
-- Dependencies: 2630 2630
-- Name: e_level_name_key; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_level
    ADD CONSTRAINT e_level_name_key UNIQUE (name);


--
-- TOC entry 3175 (class 2606 OID 12134739)
-- Dependencies: 2630 2630
-- Name: e_livelli_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_level
    ADD CONSTRAINT e_livelli_pkey PRIMARY KEY (id);


--
-- TOC entry 3177 (class 2606 OID 12134741)
-- Dependencies: 2631 2631
-- Name: e_orderby_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_orderby
    ADD CONSTRAINT e_orderby_pkey PRIMARY KEY (orderby_id);


--
-- TOC entry 3179 (class 2606 OID 12134743)
-- Dependencies: 2632 2632
-- Name: e_outputformat_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_outputformat
    ADD CONSTRAINT e_outputformat_pkey PRIMARY KEY (outputformat_id);


--
-- TOC entry 3181 (class 2606 OID 12134745)
-- Dependencies: 2633 2633
-- Name: e_owstype_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_owstype
    ADD CONSTRAINT e_owstype_pkey PRIMARY KEY (owstype_id);


--
-- TOC entry 3183 (class 2606 OID 12134747)
-- Dependencies: 2634 2634
-- Name: e_papersize_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_papersize
    ADD CONSTRAINT e_papersize_pkey PRIMARY KEY (papersize_id);


--
-- TOC entry 3185 (class 2606 OID 12134749)
-- Dependencies: 2635 2635
-- Name: e_qtrelationtype_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_qtrelationtype
    ADD CONSTRAINT e_qtrelationtype_pkey PRIMARY KEY (qtrelationtype_id);


--
-- TOC entry 3187 (class 2606 OID 12134751)
-- Dependencies: 2636 2636
-- Name: e_resultype_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_resultype
    ADD CONSTRAINT e_resultype_pkey PRIMARY KEY (resultype_id);


--
-- TOC entry 3189 (class 2606 OID 12134753)
-- Dependencies: 2637 2637
-- Name: e_searchtype_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_searchtype
    ADD CONSTRAINT e_searchtype_pkey PRIMARY KEY (searchtype_id);


--
-- TOC entry 3191 (class 2606 OID 12134755)
-- Dependencies: 2638 2638
-- Name: e_sizeunits_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_sizeunits
    ADD CONSTRAINT e_sizeunits_pkey PRIMARY KEY (sizeunits_id);


--
-- TOC entry 3193 (class 2606 OID 12134757)
-- Dependencies: 2639 2639
-- Name: e_symbolcategory_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_symbolcategory
    ADD CONSTRAINT e_symbolcategory_pkey PRIMARY KEY (symbolcategory_id);


--
-- TOC entry 3195 (class 2606 OID 12134759)
-- Dependencies: 2640 2640
-- Name: e_tiletype_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY e_tiletype
    ADD CONSTRAINT e_tiletype_pkey PRIMARY KEY (tiletype_id);


--
-- TOC entry 3199 (class 2606 OID 12134761)
-- Dependencies: 2643 2643
-- Name: font_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY font
    ADD CONSTRAINT font_pkey PRIMARY KEY (font_name);


--
-- TOC entry 3201 (class 2606 OID 12134763)
-- Dependencies: 2644 2644
-- Name: groups_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY groups
    ADD CONSTRAINT groups_pkey PRIMARY KEY (groupname);


--
-- TOC entry 3211 (class 2606 OID 12134765)
-- Dependencies: 2648 2648 2648
-- Name: layer_groups_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY layer_groups
    ADD CONSTRAINT layer_groups_pkey PRIMARY KEY (layer_id, groupname);


--
-- TOC entry 3206 (class 2606 OID 12134767)
-- Dependencies: 2647 2647 2647
-- Name: layer_layergroup_id_key; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY layer
    ADD CONSTRAINT layer_layergroup_id_key UNIQUE (layergroup_id, layer_name);


--
-- TOC entry 3208 (class 2606 OID 12134769)
-- Dependencies: 2647 2647
-- Name: layer_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY layer
    ADD CONSTRAINT layer_pkey PRIMARY KEY (layer_id);


--
-- TOC entry 3214 (class 2606 OID 12134771)
-- Dependencies: 2649 2649
-- Name: layergroup_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY layergroup
    ADD CONSTRAINT layergroup_pkey PRIMARY KEY (layergroup_id);


--
-- TOC entry 3216 (class 2606 OID 12134773)
-- Dependencies: 2649 2649 2649
-- Name: layergroup_theme_key; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY layergroup
    ADD CONSTRAINT layergroup_theme_key UNIQUE (theme_id, layergroup_name);


--
-- TOC entry 3219 (class 2606 OID 12134775)
-- Dependencies: 2650 2650
-- Name: link_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY link
    ADD CONSTRAINT link_pkey PRIMARY KEY (link_id);


--
-- TOC entry 3197 (class 2606 OID 12134777)
-- Dependencies: 2641 2641
-- Name: livelli_form_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY form_level
    ADD CONSTRAINT livelli_form_pkey PRIMARY KEY (id);


--
-- TOC entry 3221 (class 2606 OID 12134779)
-- Dependencies: 2651 2651
-- Name: localization_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY localization
    ADD CONSTRAINT localization_pkey PRIMARY KEY (localization_id);


--
-- TOC entry 3229 (class 2606 OID 12134781)
-- Dependencies: 2654 2654 2654
-- Name: mapset_groups_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY mapset_groups
    ADD CONSTRAINT mapset_groups_pkey PRIMARY KEY (mapset_name, group_name);


--
-- TOC entry 3233 (class 2606 OID 12134783)
-- Dependencies: 2655 2655 2655
-- Name: mapset_layergroup_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY mapset_layergroup
    ADD CONSTRAINT mapset_layergroup_pkey PRIMARY KEY (mapset_name, layergroup_id);


--
-- TOC entry 3237 (class 2606 OID 12134785)
-- Dependencies: 2656 2656 2656
-- Name: mapset_link_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY mapset_link
    ADD CONSTRAINT mapset_link_pkey PRIMARY KEY (mapset_name, link_id);


--
-- TOC entry 3224 (class 2606 OID 12134787)
-- Dependencies: 2653 2653
-- Name: mapset_mapset_name_key; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY mapset
    ADD CONSTRAINT mapset_mapset_name_key UNIQUE (mapset_name);


--
-- TOC entry 3226 (class 2606 OID 12134789)
-- Dependencies: 2653 2653
-- Name: mapset_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY mapset
    ADD CONSTRAINT mapset_pkey PRIMARY KEY (mapset_name);


--
-- TOC entry 3241 (class 2606 OID 12134791)
-- Dependencies: 2657 2657 2657
-- Name: mapset_qt_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY mapset_qt
    ADD CONSTRAINT mapset_qt_pkey PRIMARY KEY (mapset_name, qt_id);


--
-- TOC entry 3245 (class 2606 OID 12134793)
-- Dependencies: 2659 2659 2659
-- Name: project_admin_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY project_admin
    ADD CONSTRAINT project_admin_pkey PRIMARY KEY (project_name, username);


--
-- TOC entry 3247 (class 2606 OID 12134795)
-- Dependencies: 2660 2660 2660
-- Name: project_languages_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY project_languages
    ADD CONSTRAINT project_languages_pkey PRIMARY KEY (project_name, language_id);


--
-- TOC entry 3243 (class 2606 OID 12134797)
-- Dependencies: 2658 2658
-- Name: project_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY project
    ADD CONSTRAINT project_pkey PRIMARY KEY (project_name);


--
-- TOC entry 3249 (class 2606 OID 12134799)
-- Dependencies: 2661 2661 2661
-- Name: project_srs_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY project_srs
    ADD CONSTRAINT project_srs_pkey PRIMARY KEY (project_name, srid);


--
-- TOC entry 3278 (class 2606 OID 12134801)
-- Dependencies: 2687 2687 2687
-- Name: project_theme_id_key; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY theme
    ADD CONSTRAINT project_theme_id_key UNIQUE (project_name, theme_name);


--
-- TOC entry 3259 (class 2606 OID 12134803)
-- Dependencies: 2663 2663 2663
-- Name: qt_link_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY qt_link
    ADD CONSTRAINT qt_link_pkey PRIMARY KEY (qt_id, link_id);


--
-- TOC entry 3261 (class 2606 OID 12134805)
-- Dependencies: 2663 2663 2663 2663
-- Name: qt_link_qt_id_key; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY qt_link
    ADD CONSTRAINT qt_link_qt_id_key UNIQUE (qt_id, link_id, resultype_id);


--
-- TOC entry 3253 (class 2606 OID 12134807)
-- Dependencies: 2662 2662
-- Name: qt_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY qt
    ADD CONSTRAINT qt_pkey PRIMARY KEY (qt_id);


--
-- TOC entry 3265 (class 2606 OID 12134809)
-- Dependencies: 2664 2664 2664
-- Name: qt_selgroup_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY qt_selgroup
    ADD CONSTRAINT qt_selgroup_pkey PRIMARY KEY (qt_id, selgroup_id);


--
-- TOC entry 3255 (class 2606 OID 12134811)
-- Dependencies: 2662 2662 2662
-- Name: qt_theme_key; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY qt
    ADD CONSTRAINT qt_theme_key UNIQUE (theme_id, qt_name);


--
-- TOC entry 3269 (class 2606 OID 12134813)
-- Dependencies: 2665 2665
-- Name: qtfield_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY qtfield
    ADD CONSTRAINT qtfield_pkey PRIMARY KEY (qtfield_id);


--
-- TOC entry 3271 (class 2606 OID 12134815)
-- Dependencies: 2665 2665 2665
-- Name: qtfield_unique_key; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY qtfield
    ADD CONSTRAINT qtfield_unique_key UNIQUE (layer_id, field_header);


--
-- TOC entry 3275 (class 2606 OID 12134817)
-- Dependencies: 2666 2666
-- Name: qtrelation_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY qtrelation
    ADD CONSTRAINT qtrelation_pkey PRIMARY KEY (qtrelation_id);


--
-- TOC entry 3283 (class 2606 OID 12134819)
-- Dependencies: 2696 2696
-- Name: selgroup_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY selgroup
    ADD CONSTRAINT selgroup_pkey PRIMARY KEY (selgroup_id);


--
-- TOC entry 3285 (class 2606 OID 12134821)
-- Dependencies: 2696 2696 2696
-- Name: selgroup_selgroup_name_key; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY selgroup
    ADD CONSTRAINT selgroup_selgroup_name_key UNIQUE (selgroup_name, project_name);


--
-- TOC entry 3288 (class 2606 OID 12134823)
-- Dependencies: 2697 2697 2697
-- Name: style_class_id_key; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY style
    ADD CONSTRAINT style_class_id_key UNIQUE (class_id, style_name);


--
-- TOC entry 3290 (class 2606 OID 12134825)
-- Dependencies: 2697 2697
-- Name: style_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY style
    ADD CONSTRAINT style_pkey PRIMARY KEY (style_id);


--
-- TOC entry 3294 (class 2606 OID 12134827)
-- Dependencies: 2698 2698
-- Name: symbol_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY symbol
    ADD CONSTRAINT symbol_pkey PRIMARY KEY (symbol_name);


--
-- TOC entry 3298 (class 2606 OID 12134829)
-- Dependencies: 2699 2699 2699
-- Name: symbol_ttf_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY symbol_ttf
    ADD CONSTRAINT symbol_ttf_pkey PRIMARY KEY (symbol_ttf_name, font_name);


--
-- TOC entry 3280 (class 2606 OID 12134831)
-- Dependencies: 2687 2687
-- Name: theme_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY theme
    ADD CONSTRAINT theme_pkey PRIMARY KEY (theme_id);


--
-- TOC entry 3300 (class 2606 OID 12134835)
-- Dependencies: 2701 2701 2701
-- Name: user_group_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY user_group
    ADD CONSTRAINT user_group_pkey PRIMARY KEY (username, groupname);


--
-- TOC entry 3302 (class 2606 OID 12134837)
-- Dependencies: 2702 2702
-- Name: user_pkey; Type: CONSTRAINT; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

ALTER TABLE ONLY users
    ADD CONSTRAINT user_pkey PRIMARY KEY (username);


--
-- TOC entry 3272 (class 1259 OID 12134838)
-- Dependencies: 2666
-- Name: fki_; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_ ON qtrelation USING btree (layer_id);


--
-- TOC entry 3133 (class 1259 OID 12134839)
-- Dependencies: 2615
-- Name: fki_catalog_conntype_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_catalog_conntype_fkey ON catalog USING btree (connection_type);


--
-- TOC entry 3139 (class 1259 OID 12134840)
-- Dependencies: 2616
-- Name: fki_catalog_import_from_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_catalog_import_from_fkey ON catalog_import USING btree (catalog_from);


--
-- TOC entry 3140 (class 1259 OID 12134841)
-- Dependencies: 2616
-- Name: fki_catalog_import_project_name_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_catalog_import_project_name_fkey ON catalog_import USING btree (project_name);


--
-- TOC entry 3141 (class 1259 OID 12134842)
-- Dependencies: 2616
-- Name: fki_catalog_import_to_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_catalog_import_to_fkey ON catalog_import USING btree (catalog_to);


--
-- TOC entry 3134 (class 1259 OID 12134843)
-- Dependencies: 2615
-- Name: fki_catalog_project_name_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_catalog_project_name_fkey ON catalog USING btree (project_name);


--
-- TOC entry 3146 (class 1259 OID 12134844)
-- Dependencies: 2617
-- Name: fki_class_layer_id_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_class_layer_id_fkey ON class USING btree (layer_id);


--
-- TOC entry 3209 (class 1259 OID 12134845)
-- Dependencies: 2648
-- Name: fki_layer_id; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_layer_id ON layer_groups USING btree (layer_id);


--
-- TOC entry 3204 (class 1259 OID 12134846)
-- Dependencies: 2647
-- Name: fki_layer_layergroup_id; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_layer_layergroup_id ON layer USING btree (layergroup_id);


--
-- TOC entry 3212 (class 1259 OID 12134847)
-- Dependencies: 2649
-- Name: fki_layergroup_theme_id; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_layergroup_theme_id ON layergroup USING btree (theme_id);


--
-- TOC entry 3217 (class 1259 OID 12134848)
-- Dependencies: 2650
-- Name: fki_link_project_name_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_link_project_name_fkey ON link USING btree (project_name);


--
-- TOC entry 3230 (class 1259 OID 12134849)
-- Dependencies: 2655
-- Name: fki_mapset_layergroup_layergroup_id_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_mapset_layergroup_layergroup_id_fkey ON mapset_layergroup USING btree (layergroup_id);


--
-- TOC entry 3231 (class 1259 OID 12134850)
-- Dependencies: 2655
-- Name: fki_mapset_layergroup_mapset_name_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_mapset_layergroup_mapset_name_fkey ON mapset_layergroup USING btree (mapset_name);


--
-- TOC entry 3234 (class 1259 OID 12134851)
-- Dependencies: 2656
-- Name: fki_mapset_link_link_id_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_mapset_link_link_id_fkey ON mapset_link USING btree (link_id);


--
-- TOC entry 3235 (class 1259 OID 12134852)
-- Dependencies: 2656
-- Name: fki_mapset_link_mapset_name_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_mapset_link_mapset_name_fkey ON mapset_link USING btree (mapset_name);


--
-- TOC entry 3227 (class 1259 OID 12134853)
-- Dependencies: 2654
-- Name: fki_mapset_name; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_mapset_name ON mapset_groups USING btree (mapset_name);


--
-- TOC entry 3222 (class 1259 OID 12134854)
-- Dependencies: 2653
-- Name: fki_mapset_project_name_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_mapset_project_name_fkey ON mapset USING btree (project_name);


--
-- TOC entry 3238 (class 1259 OID 12134855)
-- Dependencies: 2657
-- Name: fki_mapset_qt_mapset_name_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_mapset_qt_mapset_name_fkey ON mapset_qt USING btree (mapset_name);


--
-- TOC entry 3239 (class 1259 OID 12134856)
-- Dependencies: 2657
-- Name: fki_mapset_qt_qt_id_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_mapset_qt_qt_id_fkey ON mapset_qt USING btree (qt_id);


--
-- TOC entry 3276 (class 1259 OID 12134857)
-- Dependencies: 2687
-- Name: fki_project_theme_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_project_theme_fkey ON theme USING btree (project_name);


--
-- TOC entry 3250 (class 1259 OID 12134858)
-- Dependencies: 2662
-- Name: fki_qt_layer_id_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_qt_layer_id_fkey ON qt USING btree (layer_id);


--
-- TOC entry 3256 (class 1259 OID 12134859)
-- Dependencies: 2663
-- Name: fki_qt_link_link_id_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_qt_link_link_id_fkey ON qt_link USING btree (link_id);


--
-- TOC entry 3257 (class 1259 OID 12134860)
-- Dependencies: 2663
-- Name: fki_qt_link_qt_id_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_qt_link_qt_id_fkey ON qt_link USING btree (qt_id);


--
-- TOC entry 3262 (class 1259 OID 12134861)
-- Dependencies: 2664
-- Name: fki_qt_selgroup_qt_id_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_qt_selgroup_qt_id_fkey ON qt_selgroup USING btree (qt_id);


--
-- TOC entry 3263 (class 1259 OID 12134862)
-- Dependencies: 2664
-- Name: fki_qt_selgroup_selgroup_id_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_qt_selgroup_selgroup_id_fkey ON qt_selgroup USING btree (selgroup_id);


--
-- TOC entry 3251 (class 1259 OID 12134863)
-- Dependencies: 2662
-- Name: fki_qt_theme_id_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_qt_theme_id_fkey ON qt USING btree (theme_id);


--
-- TOC entry 3266 (class 1259 OID 12134864)
-- Dependencies: 2665
-- Name: fki_qtfield_fieldtype_id_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_qtfield_fieldtype_id_fkey ON qtfield USING btree (fieldtype_id);


--
-- TOC entry 3267 (class 1259 OID 12134865)
-- Dependencies: 2665
-- Name: fki_qtfields_layer; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_qtfields_layer ON qtfield USING btree (layer_id);


--
-- TOC entry 3273 (class 1259 OID 12134866)
-- Dependencies: 2666
-- Name: fki_qtrelation_catalog_id_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_qtrelation_catalog_id_fkey ON qtrelation USING btree (catalog_id);


--
-- TOC entry 3281 (class 1259 OID 12134867)
-- Dependencies: 2696
-- Name: fki_selgroup_project_name_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_selgroup_project_name_fkey ON selgroup USING btree (project_name);


--
-- TOC entry 3286 (class 1259 OID 12134868)
-- Dependencies: 2697
-- Name: fki_style_class_id_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_style_class_id_fkey ON style USING btree (class_id);


--
-- TOC entry 3291 (class 1259 OID 12134869)
-- Dependencies: 2698
-- Name: fki_symbol_icontype_id_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_symbol_icontype_id_fkey ON symbol USING btree (icontype);


--
-- TOC entry 3292 (class 1259 OID 12134870)
-- Dependencies: 2698
-- Name: fki_symbol_symbolcategory_id_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_symbol_symbolcategory_id_fkey ON symbol USING btree (symbolcategory_id);


--
-- TOC entry 3147 (class 1259 OID 12134871)
-- Dependencies: 2617 2617
-- Name: fki_symbol_ttf_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_symbol_ttf_fkey ON class USING btree (symbol_ttf_name, label_font);


--
-- TOC entry 3295 (class 1259 OID 12134872)
-- Dependencies: 2699
-- Name: fki_symbol_ttf_font_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_symbol_ttf_font_fkey ON symbol_ttf USING btree (font_name);


--
-- TOC entry 3296 (class 1259 OID 12134873)
-- Dependencies: 2699
-- Name: fki_symbol_ttf_symbolcategory_id_fkey; Type: INDEX; Schema: gisclient_30; Owner: gisclient; Tablespace: 
--

CREATE INDEX fki_symbol_ttf_symbolcategory_id_fkey ON symbol_ttf USING btree (symbolcategory_id);


--
-- TOC entry 3347 (class 2620 OID 12134874)
-- Dependencies: 794 2615
-- Name: chk_catalog; Type: TRIGGER; Schema: gisclient_30; Owner: gisclient
--

CREATE TRIGGER chk_catalog
    BEFORE INSERT OR UPDATE ON catalog
    FOR EACH ROW
    EXECUTE PROCEDURE check_catalog();


--
-- TOC entry 3348 (class 2620 OID 12134875)
-- Dependencies: 2617 803
-- Name: chk_class; Type: TRIGGER; Schema: gisclient_30; Owner: gisclient
--

CREATE TRIGGER chk_class
    BEFORE INSERT OR UPDATE ON class
    FOR EACH ROW
    EXECUTE PROCEDURE check_class();


--
-- TOC entry 3353 (class 2620 OID 12134876)
-- Dependencies: 808 2662
-- Name: delete_qtfields_qt; Type: TRIGGER; Schema: gisclient_30; Owner: gisclient
--

CREATE TRIGGER delete_qtfields_qt
    AFTER DELETE ON qt
    FOR EACH ROW
    EXECUTE PROCEDURE delete_qt();


--
-- TOC entry 3354 (class 2620 OID 12134877)
-- Dependencies: 809 2666
-- Name: delete_qtrelation; Type: TRIGGER; Schema: gisclient_30; Owner: gisclient
--

CREATE TRIGGER delete_qtrelation
    AFTER DELETE ON qtrelation
    FOR EACH ROW
    EXECUTE PROCEDURE delete_qtrelation();


--
-- TOC entry 3349 (class 2620 OID 12134878)
-- Dependencies: 819 2630
-- Name: depth; Type: TRIGGER; Schema: gisclient_30; Owner: gisclient
--

CREATE TRIGGER depth
    AFTER INSERT OR UPDATE ON e_level
    FOR EACH ROW
    EXECUTE PROCEDURE set_depth();


--
-- TOC entry 3351 (class 2620 OID 12134879)
-- Dependencies: 2648 805
-- Name: layername; Type: TRIGGER; Schema: gisclient_30; Owner: gisclient
--

CREATE TRIGGER layername
    BEFORE INSERT OR UPDATE ON layer_groups
    FOR EACH ROW
    EXECUTE PROCEDURE set_layer_name();


--
-- TOC entry 3350 (class 2620 OID 12134880)
-- Dependencies: 820 2630
-- Name: leaf; Type: TRIGGER; Schema: gisclient_30; Owner: gisclient
--

CREATE TRIGGER leaf
    AFTER INSERT OR UPDATE ON e_level
    FOR EACH ROW
    EXECUTE PROCEDURE set_leaf();


--
-- TOC entry 3352 (class 2620 OID 12134881)
-- Dependencies: 2649 812
-- Name: move_layergroup; Type: TRIGGER; Schema: gisclient_30; Owner: gisclient
--

CREATE TRIGGER move_layergroup
    AFTER UPDATE ON layergroup
    FOR EACH ROW
    EXECUTE PROCEDURE move_layergroup();


--
-- TOC entry 3356 (class 2620 OID 12134882)
-- Dependencies: 810 2702
-- Name: set_encpwd; Type: TRIGGER; Schema: gisclient_30; Owner: gisclient
--

CREATE TRIGGER set_encpwd
    BEFORE INSERT OR UPDATE ON users
    FOR EACH ROW
    EXECUTE PROCEDURE enc_pwd();



--
-- TOC entry 3303 (class 2606 OID 12134884)
-- Dependencies: 2615 2620 3152
-- Name: catalog_conntype_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY catalog
    ADD CONSTRAINT catalog_conntype_fkey FOREIGN KEY (connection_type) REFERENCES e_conntype(conntype_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3305 (class 2606 OID 12134889)
-- Dependencies: 3242 2658 2616
-- Name: catalog_import_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY catalog_import
    ADD CONSTRAINT catalog_import_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3304 (class 2606 OID 12134894)
-- Dependencies: 2658 2615 3242
-- Name: catalog_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY catalog
    ADD CONSTRAINT catalog_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3306 (class 2606 OID 12134899)
-- Dependencies: 3207 2647 2617
-- Name: class_layer_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY class
    ADD CONSTRAINT class_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES layer(layer_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3307 (class 2606 OID 12134904)
-- Dependencies: 2630 2625 3174
-- Name: e_form_level_destination_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY e_form
    ADD CONSTRAINT e_form_level_destination_fkey FOREIGN KEY (level_destination) REFERENCES e_level(id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3308 (class 2606 OID 12134909)
-- Dependencies: 2630 3174 2630
-- Name: e_level_parent_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY e_level
    ADD CONSTRAINT e_level_parent_id_fkey FOREIGN KEY (parent_id) REFERENCES e_level(id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3309 (class 2606 OID 12134914)
-- Dependencies: 2625 3162 2641
-- Name: form_level_form_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY form_level
    ADD CONSTRAINT form_level_form_fkey FOREIGN KEY (form) REFERENCES e_form(id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3310 (class 2606 OID 12134919)
-- Dependencies: 2641 3174 2630
-- Name: form_level_level_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY form_level
    ADD CONSTRAINT form_level_level_fkey FOREIGN KEY (level) REFERENCES e_level(id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3315 (class 2606 OID 12134924)
-- Dependencies: 3202 2651 2645
-- Name: i18nfield_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY localization
    ADD CONSTRAINT i18nfield_fkey FOREIGN KEY (i18nf_id) REFERENCES i18n_field(i18nf_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3316 (class 2606 OID 12134929)
-- Dependencies: 2651 3164 2626
-- Name: language_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY localization
    ADD CONSTRAINT language_id_fkey FOREIGN KEY (language_id) REFERENCES e_language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3327 (class 2606 OID 12134934)
-- Dependencies: 3242 2658 2660
-- Name: language_id_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY project_languages
    ADD CONSTRAINT language_id_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3312 (class 2606 OID 12134939)
-- Dependencies: 2648 3207 2647
-- Name: layer_groups_layer_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY layer_groups
    ADD CONSTRAINT layer_groups_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES layer(layer_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3311 (class 2606 OID 12134944)
-- Dependencies: 3213 2649 2647
-- Name: layer_layergroup_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY layer
    ADD CONSTRAINT layer_layergroup_id_fkey FOREIGN KEY (layergroup_id) REFERENCES layergroup(layergroup_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3313 (class 2606 OID 12134949)
-- Dependencies: 2649 3279 2687
-- Name: layergroup_theme_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY layergroup
    ADD CONSTRAINT layergroup_theme_id_fkey FOREIGN KEY (theme_id) REFERENCES theme(theme_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3314 (class 2606 OID 12134954)
-- Dependencies: 3242 2658 2650
-- Name: link_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY link
    ADD CONSTRAINT link_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3317 (class 2606 OID 12134959)
-- Dependencies: 3242 2658 2651
-- Name: localization_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY localization
    ADD CONSTRAINT localization_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3319 (class 2606 OID 12134964)
-- Dependencies: 3223 2653 2654
-- Name: mapset_groups_mapset_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY mapset_groups
    ADD CONSTRAINT mapset_groups_mapset_name_fkey FOREIGN KEY (mapset_name) REFERENCES mapset(mapset_name) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3320 (class 2606 OID 12134969)
-- Dependencies: 3213 2649 2655
-- Name: mapset_layergroup_layergroup_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY mapset_layergroup
    ADD CONSTRAINT mapset_layergroup_layergroup_id_fkey FOREIGN KEY (layergroup_id) REFERENCES layergroup(layergroup_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3321 (class 2606 OID 12134974)
-- Dependencies: 3223 2653 2655
-- Name: mapset_layergroup_mapset_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY mapset_layergroup
    ADD CONSTRAINT mapset_layergroup_mapset_name_fkey FOREIGN KEY (mapset_name) REFERENCES mapset(mapset_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3322 (class 2606 OID 12134979)
-- Dependencies: 2650 2656 3218
-- Name: mapset_link_link_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY mapset_link
    ADD CONSTRAINT mapset_link_link_id_fkey FOREIGN KEY (link_id) REFERENCES link(link_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3323 (class 2606 OID 12134984)
-- Dependencies: 2656 2653 3223
-- Name: mapset_link_mapset_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY mapset_link
    ADD CONSTRAINT mapset_link_mapset_name_fkey FOREIGN KEY (mapset_name) REFERENCES mapset(mapset_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3318 (class 2606 OID 12134989)
-- Dependencies: 2653 2658 3242
-- Name: mapset_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY mapset
    ADD CONSTRAINT mapset_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3324 (class 2606 OID 12134994)
-- Dependencies: 2657 2653 3223
-- Name: mapset_qt_mapset_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY mapset_qt
    ADD CONSTRAINT mapset_qt_mapset_name_fkey FOREIGN KEY (mapset_name) REFERENCES mapset(mapset_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3325 (class 2606 OID 12134999)
-- Dependencies: 2657 2662 3252
-- Name: mapset_qt_qt_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY mapset_qt
    ADD CONSTRAINT mapset_qt_qt_id_fkey FOREIGN KEY (qt_id) REFERENCES qt(qt_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3328 (class 2606 OID 12135004)
-- Dependencies: 2658 2661 3242
-- Name: project_srs_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY project_srs
    ADD CONSTRAINT project_srs_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3329 (class 2606 OID 12135009)
-- Dependencies: 2647 3207 2662
-- Name: qt_layer_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY qt
    ADD CONSTRAINT qt_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES layer(layer_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3331 (class 2606 OID 12135014)
-- Dependencies: 3218 2650 2663
-- Name: qt_link_link_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY qt_link
    ADD CONSTRAINT qt_link_link_id_fkey FOREIGN KEY (link_id) REFERENCES link(link_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3332 (class 2606 OID 12135019)
-- Dependencies: 2662 3252 2663
-- Name: qt_link_qt_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY qt_link
    ADD CONSTRAINT qt_link_qt_id_fkey FOREIGN KEY (qt_id) REFERENCES qt(qt_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3333 (class 2606 OID 12135024)
-- Dependencies: 2662 3252 2664
-- Name: qt_selgroup_qt_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY qt_selgroup
    ADD CONSTRAINT qt_selgroup_qt_id_fkey FOREIGN KEY (qt_id) REFERENCES qt(qt_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3330 (class 2606 OID 12135029)
-- Dependencies: 3279 2687 2662
-- Name: qt_theme_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY qt
    ADD CONSTRAINT qt_theme_id_fkey FOREIGN KEY (theme_id) REFERENCES theme(theme_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3334 (class 2606 OID 12135034)
-- Dependencies: 2665 3158 2623
-- Name: qtfield_fieldtype_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY qtfield
    ADD CONSTRAINT qtfield_fieldtype_id_fkey FOREIGN KEY (fieldtype_id) REFERENCES e_fieldtype(fieldtype_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3335 (class 2606 OID 12135039)
-- Dependencies: 2665 3207 2647
-- Name: qtfield_layer_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY qtfield
    ADD CONSTRAINT qtfield_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES layer(layer_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3336 (class 2606 OID 12135044)
-- Dependencies: 2666 3131 2615
-- Name: qtrelation_catalog_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY qtrelation
    ADD CONSTRAINT qtrelation_catalog_fkey FOREIGN KEY (catalog_id) REFERENCES catalog(catalog_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3337 (class 2606 OID 12135049)
-- Dependencies: 2647 2666 3207
-- Name: qtrelation_layer_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY qtrelation
    ADD CONSTRAINT qtrelation_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES layer(layer_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3340 (class 2606 OID 12135054)
-- Dependencies: 2696 2658 3242
-- Name: selgroup_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY selgroup
    ADD CONSTRAINT selgroup_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3341 (class 2606 OID 12135059)
-- Dependencies: 2617 2697 3144
-- Name: style_class_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY style
    ADD CONSTRAINT style_class_id_fkey FOREIGN KEY (class_id) REFERENCES class(class_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3342 (class 2606 OID 12135064)
-- Dependencies: 2698 3192 2639
-- Name: symbol_symbolcategory_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY symbol
    ADD CONSTRAINT symbol_symbolcategory_id_fkey FOREIGN KEY (symbolcategory_id) REFERENCES e_symbolcategory(symbolcategory_id) MATCH FULL ON UPDATE CASCADE;


--
-- TOC entry 3343 (class 2606 OID 12135069)
-- Dependencies: 3198 2643 2699
-- Name: symbol_ttf_font_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY symbol_ttf
    ADD CONSTRAINT symbol_ttf_font_fkey FOREIGN KEY (font_name) REFERENCES font(font_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3344 (class 2606 OID 12135074)
-- Dependencies: 3192 2699 2639
-- Name: symbol_ttf_symbolcategory_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY symbol_ttf
    ADD CONSTRAINT symbol_ttf_symbolcategory_id_fkey FOREIGN KEY (symbolcategory_id) REFERENCES e_symbolcategory(symbolcategory_id) MATCH FULL ON UPDATE CASCADE;


--
-- TOC entry 3338 (class 2606 OID 12135079)
-- Dependencies: 2687 3150 2619
-- Name: theme_charset_encodings_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY theme
    ADD CONSTRAINT theme_charset_encodings_id_fkey FOREIGN KEY (charset_encodings_id) REFERENCES e_charset_encodings(charset_encodings_id);


--
-- TOC entry 3339 (class 2606 OID 12135084)
-- Dependencies: 3242 2658 2687
-- Name: theme_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY theme
    ADD CONSTRAINT theme_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3345 (class 2606 OID 12135094)
-- Dependencies: 3200 2644 2701
-- Name: user_group_groupname_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY user_group
    ADD CONSTRAINT user_group_groupname_fkey FOREIGN KEY (groupname) REFERENCES groups(groupname) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3346 (class 2606 OID 12135099)
-- Dependencies: 2701 3301 2702
-- Name: user_group_username_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY user_group
    ADD CONSTRAINT user_group_username_fkey FOREIGN KEY (username) REFERENCES users(username) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3326 (class 2606 OID 12135104)
-- Dependencies: 2659 3242 2658
-- Name: username_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_30; Owner: gisclient
--

ALTER TABLE ONLY project_admin
    ADD CONSTRAINT username_project_name_fkey FOREIGN KEY (project_name) REFERENCES project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


-- Completed on 2011-09-08 15:18:28

--
-- PostgreSQL database dump complete
--

