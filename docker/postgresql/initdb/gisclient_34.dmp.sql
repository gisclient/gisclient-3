--
-- PostgreSQL database dump
--

-- Dumped from database version 17.4
-- Dumped by pg_dump version 17.4

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: gisclient_34; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA gisclient_34;


--
-- Name: qt_selgroup_type; Type: TYPE; Schema: gisclient_34; Owner: -
--

CREATE TYPE gisclient_34.qt_selgroup_type AS (
	qt_selgroup_id integer,
	qt_id integer,
	selgroup_id integer,
	presente integer,
	project_id integer
);


--
-- Name: slgrp_qt; Type: TYPE; Schema: gisclient_34; Owner: -
--

CREATE TYPE gisclient_34.slgrp_qt AS (
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


--
-- Name: tree; Type: TYPE; Schema: gisclient_34; Owner: -
--

CREATE TYPE gisclient_34.tree AS (
	id integer,
	name character varying,
	lvl_id integer,
	lvl_name character varying
);


--
-- Name: check_catalog(); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.check_catalog() RETURNS trigger
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
-- Name: check_class(); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.check_class() RETURNS trigger
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
-- Name: check_layergroup(); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.check_layergroup() RETURNS trigger
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
-- Name: check_mapset(); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.check_mapset() RETURNS trigger
    LANGUAGE plpgsql
    AS $_$
DECLARE
	ext varchar;
	presente integer;
BEGIN

	new.mapset_name:=regexp_replace(trim(new.mapset_name),'([\t ]+)','_','g');
	select coalesce(project_extent,'') into ext from gisclient_34.project where project_name=new.project_name;
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
-- Name: check_project(); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.check_project() RETURNS trigger
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
	sk:='gisclient_34';	
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
-- Name: delete_qt(); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.delete_qt() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	delete from gisclient_34.qtfield where qt_id=old.qt_id;
	return old;
END
$$;


--
-- Name: delete_relation(); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.delete_relation() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	delete from gisclient_34.field where relation_id=old.relation_id;
	return old;
END
$$;


--
-- Name: enc_pwd(); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.enc_pwd() RETURNS trigger
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
-- Name: gw_findtree(integer, character varying); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.gw_findtree(id integer, lvl character varying) RETURNS SETOF gisclient_34.tree
    LANGUAGE plpgsql IMMUTABLE
    AS $$
DECLARE
	rec record;
	t gisclient_34.tree;
	i integer;
	d integer;
BEGIN
	select into d coalesce(depth,-1) from gisclient_34.e_level where name=lvl;
	if (d=-1) then
		raise exception 'Livello % non esistente',lvl;
	end if;
	for i in reverse d..1 loop
		return next t;
	end loop;
	
END
$$;


--
-- Name: move_layergroup(); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.move_layergroup() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	if(new.theme_id<>old.theme_id) then
		update gisclient_34.qt set theme_id=new.theme_id where qt_id in (select distinct qt_id from gisclient_34.qt inner join gisclient_34.layer using(layer_id) inner join gisclient_34.layergroup using(layergroup_id) where layergroup_id=new.layergroup_id);
	end if;
	return new;
END
$$;


--
-- Name: new_pkey(character varying, character varying); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.new_pkey(tab character varying, id_fld character varying) RETURNS integer
    LANGUAGE plpgsql
    AS $$
declare
    newid integer;
	sk varchar;
	query varchar;
begin
	sk:='gisclient_34';
	query:='select '||sk||'.new_pkey('''||tab||''','''||id_fld||''',0)';
	execute query into newid;
	return newid;
end
$$;


--
-- Name: new_pkey(character varying, character varying, integer); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.new_pkey(tab character varying, id_fld character varying, st integer) RETURNS integer
    LANGUAGE plpgsql
    AS $$
declare
    str varchar;
start_value integer;
    newid record;
    sk varchar;
begin
	sk:='gisclient_34';
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
-- Name: new_pkey(character varying, character varying, character varying); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.new_pkey(sk character varying, tab character varying, id_fld character varying) RETURNS integer
    LANGUAGE plpgsql
    AS $$
declare
    newid integer;
begin
	select gisclient_34.new_pkey(sk ,tab,id_fld,0) into newid; 
	return newid;
end
$$;


--
-- Name: new_pkey(character varying, character varying, character varying, integer); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.new_pkey(sk character varying, tab character varying, id_fld character varying, st integer) RETURNS integer
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
-- Name: new_pkey_varchar(character varying, character varying, character varying); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.new_pkey_varchar(tb character varying, fld character varying, val character varying) RETURNS character varying
    LANGUAGE plpgsql IMMUTABLE
    AS $_$
DECLARE
	query text;
	presente integer;
	newval varchar;
BEGIN
query:='select count(*) from gisclient_34.'||tb||' where '||fld||'='''||val||''';';
execute query into presente;
if(presente>0) then
	query:='select map||(max(newindex)+1)::varchar from (select regexp_replace('||fld||',''([0-9]+)$'','''') as map,case when(regexp_replace('||fld||',''^([A-z_]+)'','''')='''') then 0 else regexp_replace('||fld||',''^([A-z_]+)'','''')::integer end as newindex from gisclient_34.'||tb||' where '''||val||''' ~* regexp_replace('||fld||',''([0-9]+)$'','''')) X group by map;';
	execute query into newval;
	return newval;
else
	return val;
end if;
END
$_$;


--
-- Name: rm_project_groups(); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.rm_project_groups() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE

BEGIN
	delete from gisclient_34.mapset_groups where mapset_name in (select distinct mapset_name from gisclient_34.mapset where project_name=old.project_name) and group_name=old.group_name;
	return old;
END
$$;


--
-- Name: set_depth(); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.set_depth() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	if (TG_OP='INSERT') then
		update gisclient_34.e_level set depth=(select coalesce(depth+1,0) from gisclient_34.e_level where id=new.parent_id) where id=new.id;
	elseif(new.parent_id<>coalesce(old.parent_id,-1)) then
		update gisclient_34.e_level set depth=(select coalesce(depth+1,0) from gisclient_34.e_level where id=new.parent_id) where id=new.id;
	end if;
	return new;
END
$$;


--
-- Name: set_layer_name(); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.set_layer_name() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
SELECT INTO NEW.layer_name layer_name FROM gisclient_34.layer WHERE layer_id=NEW.layer_id;
RETURN NEW;
END
$$;


--
-- Name: set_leaf(); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.set_leaf() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	if(TG_OP='INSERT') then
		update gisclient_34.e_level X set leaf=(select case when (count(parent_id)>0) then 0 else 1 end from gisclient_34.e_level where parent_id=X.id);
	elsif (new.parent_id<> coalesce(old.parent_id,-1)) then
		update gisclient_34.e_level X set leaf=(select case when (count(parent_id)>0) then 0 else 1 end from gisclient_34.e_level where parent_id=X.id);
	end if;
	return new;
END
$$;


--
-- Name: set_map_extent(); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.set_map_extent() RETURNS trigger
    LANGUAGE plpgsql
    AS $_$
DECLARE
	ext varchar;
BEGIN

	new.mapset_name:=regexp_replace(trim(new.mapset_name),'([\t ]+)','_','g');
	select coalesce(project_extent,'') into ext from gisclient_34.project where project_name=new.project_name;
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
-- Name: style_name(); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.style_name() RETURNS trigger
    LANGUAGE plpgsql
    AS $_$
DECLARE
	num integer;
	rec record;
	check_hatch smallint;
BEGIN
	if ((coalesce(new.symbol_name,'')<>'') and (coalesce(new.size,0)=0)) then
		select into rec * from gisclient_34.symbol where symbol_name=new.symbol_name;
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
			SELECT INTO num count(*)+1 FROM gisclient_34.style WHERE class_id=new.class_id and style_name ~* 'Stile ([0-9]+)';
		end if;
		new.style_name:='Stile '||num::varchar;
	end if;
	return new;
END
$_$;


--
-- Name: theme_version_tr(); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.theme_version_tr() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  /* New function body */
  RAISE NOTICE 'Trigger "%" called on "%" for table %.%', TG_NAME, TG_OP, TG_TABLE_SCHEMA, TG_TABLE_NAME;
  IF TG_OP='INSERT' THEN
     INSERT INTO gisclient_34.theme_version(theme_id, theme_version) VALUES (NEW.theme_id, nextval('gisclient_34.theme_version_id_seq'));
  ELSIF TG_OP='UPDATE' THEN
     UPDATE gisclient_34.theme_version SET theme_version=nextval('gisclient_34.theme_version_id_seq') WHERE theme_id=NEW.theme_id;
  END IF;
  RETURN NEW;
END;
$$;


--
-- Name: upsertlocalization(character varying, character varying, character varying, character varying); Type: FUNCTION; Schema: gisclient_34; Owner: -
--

CREATE FUNCTION gisclient_34.upsertlocalization(v_field_name character varying, v_language_id character varying, v_string_to_translate character varying, v_translation character varying) RETURNS boolean
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
        DECLARE
            v_affected_rows integer;
        BEGIN
            IF (v_field_name = 'theme_title') THEN
                INSERT INTO localization (project_name, i18nf_id,pkey_id, language_id, value)
                WITH export_i18n AS (
                    SELECT
                        'theme'::character varying AS table_name,
                        'theme_title'::character varying As field_name,
                        project_name,
                        theme_id AS pkey_id,
                        v_language_id AS language_id,
                        v_translation AS value
                    FROM theme
                    WHERE theme_title = v_string_to_translate
                )
                SELECT
                    project_name, i18nf_id, pkey_id, language_id, value
                FROM export_i18n
                INNER JOIN i18n_field USING(table_name, field_name)
                ON CONFLICT (project_name, i18nf_id,pkey_id, language_id) DO UPDATE SET
                    value = excluded.value
                ;
            ELSIF (v_field_name = 'copyright_string') THEN
                INSERT INTO localization (project_name, i18nf_id,pkey_id, language_id, value)
                WITH export_i18n AS (
                    SELECT
                        'theme'::character varying AS table_name,
                        'copyright_string'::character varying As field_name,
                        project_name,
                        theme_id AS pkey_id,
                        v_language_id AS language_id,
                        v_translation AS value
                    FROM theme
                    WHERE copyright_string = v_string_to_translate
                )
                SELECT
                    project_name, i18nf_id, pkey_id, language_id, value
                FROM export_i18n
                INNER JOIN i18n_field USING(table_name, field_name)
                ON CONFLICT (project_name, i18nf_id,pkey_id, language_id) DO UPDATE SET
                    value = excluded.value
                ;
            ELSIF (v_field_name = 'layergroup_title') THEN
                INSERT INTO localization (project_name, i18nf_id,pkey_id, language_id, value)
                WITH export_i18n AS (
                    SELECT
                        'layergroup'::character varying AS table_name,
                        'layergroup_title'::character varying As field_name,
                        project_name,
                        layergroup_id AS pkey_id,
                        v_language_id AS language_id,
                        v_translation AS value
                    FROM layergroup
                    INNER JOIN theme USING (theme_id)
                    WHERE layergroup_title = v_string_to_translate
                )
                SELECT
                    project_name, i18nf_id, pkey_id, language_id, value
                FROM export_i18n
                INNER JOIN i18n_field USING(table_name, field_name)
                ON CONFLICT (project_name, i18nf_id,pkey_id, language_id) DO UPDATE SET
                    value = excluded.value
                ;
            ELSIF (v_field_name = 'sld') THEN
                INSERT INTO localization (project_name, i18nf_id,pkey_id, language_id, value)
                WITH export_i18n AS (
                    SELECT
                        'layergroup'::character varying AS table_name,
                        'sld'::character varying As field_name,
                        project_name,
                        layergroup_id AS pkey_id,
                        v_language_id AS language_id,
                        v_translation AS value
                    FROM layergroup
                    INNER JOIN theme USING (theme_id)
                    WHERE sld = v_string_to_translate
                )
                SELECT
                    project_name, i18nf_id, pkey_id, language_id, value
                FROM export_i18n
                INNER JOIN i18n_field USING(table_name, field_name)
                ON CONFLICT (project_name, i18nf_id,pkey_id, language_id) DO UPDATE SET
                    value = excluded.value
                ;
            ELSIF (v_field_name = 'layer_title') THEN
                INSERT INTO localization (project_name, i18nf_id,pkey_id, language_id, value)
                WITH export_i18n AS (
                    SELECT
                        'layer'::character varying AS table_name,
                        'layer_title'::character varying As field_name,
                        project_name,
                        layer_id AS pkey_id,
                        v_language_id AS language_id,
                        v_translation AS value
                    FROM layer
                    INNER JOIN layergroup USING (layergroup_id)
                    INNER JOIN theme USING (theme_id)
                    WHERE layer_title = v_string_to_translate
                )
                SELECT
                    project_name, i18nf_id, pkey_id, language_id, value
                FROM export_i18n
                INNER JOIN i18n_field USING(table_name, field_name)
                ON CONFLICT (project_name, i18nf_id,pkey_id, language_id) DO UPDATE SET
                    value = excluded.value
                ;
            ELSIF (v_field_name = 'labelitem') THEN
                INSERT INTO localization (project_name, i18nf_id,pkey_id, language_id, value)
                WITH export_i18n AS (
                    SELECT
                        'layer'::character varying AS table_name,
                        'labelitem'::character varying As field_name,
                        project_name,
                        layer_id AS pkey_id,
                        v_language_id AS language_id,
                        v_translation AS value
                    FROM layer
                    INNER JOIN layergroup USING (layergroup_id)
                    INNER JOIN theme USING (theme_id)
                    WHERE labelitem = v_string_to_translate
                )
                SELECT
                    project_name, i18nf_id, pkey_id, language_id, value
                FROM export_i18n
                INNER JOIN i18n_field USING(table_name, field_name)
                ON CONFLICT (project_name, i18nf_id,pkey_id, language_id) DO UPDATE SET
                    value = excluded.value
                ;
            ELSIF (v_field_name = 'template') THEN
                INSERT INTO localization (project_name, i18nf_id,pkey_id, language_id, value)
                WITH export_i18n AS (
                    SELECT
                        'layer'::character varying AS table_name,
                        'template'::character varying As field_name,
                        project_name,
                        layer_id AS pkey_id,
                        v_language_id AS language_id,
                        v_translation AS value
                    FROM layer
                    INNER JOIN layergroup USING (layergroup_id)
                    INNER JOIN theme USING (theme_id)
                    WHERE template = v_string_to_translate
                )
                SELECT
                    project_name, i18nf_id, pkey_id, language_id, value
                FROM export_i18n
                INNER JOIN i18n_field USING(table_name, field_name)
                ON CONFLICT (project_name, i18nf_id,pkey_id, language_id) DO UPDATE SET
                    value = excluded.value
                ;
            ELSIF (v_field_name = 'class_title') THEN
                INSERT INTO localization (project_name, i18nf_id,pkey_id, language_id, value)
                WITH export_i18n AS (
                    SELECT
                        'class'::character varying AS table_name,
                        'class_title'::character varying As field_name,
                        project_name,
                        class_id AS pkey_id,
                        v_language_id AS language_id,
                        v_translation AS value
                    FROM class
                    INNER JOIN layer USING (layer_id)
                    INNER JOIN layergroup USING (layergroup_id)
                    INNER JOIN theme USING (theme_id)
                    WHERE class_title = v_string_to_translate
                )
                SELECT
                    project_name, i18nf_id, pkey_id, language_id, value
                FROM export_i18n
                INNER JOIN i18n_field USING(table_name, field_name)
                ON CONFLICT (project_name, i18nf_id,pkey_id, language_id) DO UPDATE SET
                    value = excluded.value
                ;
            ELSIF (v_field_name = 'field_header') THEN
                INSERT INTO localization (project_name, i18nf_id,pkey_id, language_id, value)
                WITH export_i18n AS (
                    SELECT
                        'field'::character varying AS table_name,
                        'field_header'::character varying As field_name,
                        project_name,
                        field_id AS pkey_id,
                        v_language_id AS language_id,
                        v_translation AS value
                    FROM field
                    INNER JOIN layer USING (layer_id)
                    INNER JOIN layergroup USING (layergroup_id)
                    INNER JOIN theme USING (theme_id)
                    WHERE field_header = v_string_to_translate
                )
                SELECT
                    project_name, i18nf_id, pkey_id, language_id, value
                FROM export_i18n
                INNER JOIN i18n_field USING(table_name, field_name)
                ON CONFLICT (project_name, i18nf_id,pkey_id, language_id) DO UPDATE SET
                    value = excluded.value
                ;
            ELSIF (v_field_name = 'field_name') THEN
                INSERT INTO localization (project_name, i18nf_id,pkey_id, language_id, value)
                WITH export_i18n AS (
                    SELECT
                        'field'::character varying AS table_name,
                        'field_name'::character varying As field_name,
                        project_name,
                        field_id AS pkey_id,
                        v_language_id AS language_id,
                        v_translation AS value
                    FROM field
                    INNER JOIN layer USING (layer_id)
                    INNER JOIN layergroup USING (layergroup_id)
                    INNER JOIN theme USING (theme_id)
                    WHERE field_name = v_string_to_translate
                )
                SELECT
                    project_name, i18nf_id, pkey_id, language_id, value
                FROM export_i18n
                INNER JOIN i18n_field USING(table_name, field_name)
                ON CONFLICT (project_name, i18nf_id,pkey_id, language_id) DO UPDATE SET
                    value = excluded.value
                ;
          ELSE
            RAISE EXCEPTION 'Fieldname "%" is not supported', v_field_name ; 
          END IF;
          
          GET DIAGNOSTICS v_affected_rows = ROW_COUNT;
          IF (v_affected_rows > 0) THEN
            RETURN TRUE;
          ELSE
            RAISE WARNING 'Translation could not be applied for field "%" and value="%"', v_field_name, v_string_to_translate;
            RETURN FALSE;
          END IF;
        END;
        $$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: authfilter; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.authfilter (
    filter_id integer NOT NULL,
    filter_name character varying(100),
    filter_description text,
    filter_priority integer DEFAULT 0 NOT NULL
);


--
-- Name: catalog; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.catalog (
    catalog_id integer NOT NULL,
    catalog_name character varying NOT NULL,
    project_name character varying NOT NULL,
    connection_type smallint NOT NULL,
    catalog_path character varying NOT NULL,
    catalog_url character varying,
    catalog_description text,
    files_path character varying,
    set_extent smallint DEFAULT 1
);


--
-- Name: catalog_import; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.catalog_import (
    catalog_import_id integer NOT NULL,
    project_name character varying NOT NULL,
    catalog_import_name text,
    catalog_from integer NOT NULL,
    catalog_to integer NOT NULL,
    catalog_import_description text
);


--
-- Name: class; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.class (
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
-- Name: document; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.document (
    doc_id integer NOT NULL,
    doc_parent_id integer,
    doc_name character varying NOT NULL,
    doc_type character varying NOT NULL,
    doc_public boolean DEFAULT false
);


--
-- Name: document_doc_id_seq; Type: SEQUENCE; Schema: gisclient_34; Owner: -
--

CREATE SEQUENCE gisclient_34.document_doc_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: document_doc_id_seq; Type: SEQUENCE OWNED BY; Schema: gisclient_34; Owner: -
--

ALTER SEQUENCE gisclient_34.document_doc_id_seq OWNED BY gisclient_34.document.doc_id;


--
-- Name: e_charset_encodings; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_charset_encodings (
    charset_encodings_id integer NOT NULL,
    charset_encodings_name character varying NOT NULL,
    charset_encodings_order smallint
);


--
-- Name: e_conntype; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_conntype (
    conntype_id smallint NOT NULL,
    conntype_name character varying NOT NULL,
    conntype_order smallint
);


--
-- Name: e_datatype; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_datatype (
    datatype_id smallint NOT NULL,
    datatype_name character varying NOT NULL,
    datatype_order smallint
);


--
-- Name: e_fieldformat; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_fieldformat (
    fieldformat_id integer NOT NULL,
    fieldformat_name character varying NOT NULL,
    fieldformat_format character varying NOT NULL,
    fieldformat_order smallint
);


--
-- Name: e_fieldtype; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_fieldtype (
    fieldtype_id smallint NOT NULL,
    fieldtype_name character varying NOT NULL,
    fieldtype_order smallint
);


--
-- Name: e_filetype; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_filetype (
    filetype_id smallint NOT NULL,
    filetype_name character varying NOT NULL,
    filetype_order smallint
);


--
-- Name: e_form; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_form (
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
-- Name: e_formula; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_formula (
    formula_id integer NOT NULL,
    formula_name character varying NOT NULL,
    formula_format character varying NOT NULL,
    formula_order smallint
);


--
-- Name: e_language; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_language (
    language_id character(2) NOT NULL,
    language_name character varying NOT NULL,
    language_order integer
);


--
-- Name: e_layertype; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_layertype (
    layertype_id smallint NOT NULL,
    layertype_name character varying NOT NULL,
    layertype_ms smallint,
    layertype_order smallint
);


--
-- Name: e_lblposition; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_lblposition (
    lblposition_id integer NOT NULL,
    lblposition_name character varying NOT NULL,
    lblposition_order smallint
);


--
-- Name: e_legendtype; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_legendtype (
    legendtype_id smallint NOT NULL,
    legendtype_name character varying NOT NULL,
    legendtype_order smallint
);


--
-- Name: e_level; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_level (
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
-- Name: e_orderby; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_orderby (
    orderby_id smallint NOT NULL,
    orderby_name character varying NOT NULL,
    orderby_order smallint
);


--
-- Name: e_outputformat; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_outputformat (
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
-- Name: e_owstype; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_owstype (
    owstype_id smallint NOT NULL,
    owstype_name character varying NOT NULL,
    owstype_order smallint
);


--
-- Name: e_papersize; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_papersize (
    papersize_id integer NOT NULL,
    papersize_name character varying NOT NULL,
    papersize_size character varying NOT NULL,
    papersize_orientation character varying,
    papaersize_order smallint
);


--
-- Name: e_pattern; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_pattern (
    pattern_id integer NOT NULL,
    pattern_name character varying NOT NULL,
    pattern_def character varying NOT NULL,
    pattern_order smallint
);


--
-- Name: e_pattern_pattern_id_seq; Type: SEQUENCE; Schema: gisclient_34; Owner: -
--

CREATE SEQUENCE gisclient_34.e_pattern_pattern_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: e_pattern_pattern_id_seq; Type: SEQUENCE OWNED BY; Schema: gisclient_34; Owner: -
--

ALTER SEQUENCE gisclient_34.e_pattern_pattern_id_seq OWNED BY gisclient_34.e_pattern.pattern_id;


--
-- Name: e_relationtype; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_relationtype (
    relationtype_id integer NOT NULL,
    relationtype_name character varying NOT NULL,
    relationtype_order smallint
);


--
-- Name: e_resultype; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_resultype (
    resultype_id smallint NOT NULL,
    resultype_name character varying NOT NULL,
    resultype_order smallint
);


--
-- Name: e_searchable; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_searchable (
    searchable_id smallint NOT NULL,
    searchable_name character varying NOT NULL,
    searchable_order smallint
);


--
-- Name: e_searchtype; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_searchtype (
    searchtype_id smallint NOT NULL,
    searchtype_name character varying NOT NULL,
    searchtype_order smallint
);


--
-- Name: e_sizeunits; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_sizeunits (
    sizeunits_id smallint NOT NULL,
    sizeunits_name character varying NOT NULL,
    sizeunits_order smallint
);


--
-- Name: e_symbolcategory; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_symbolcategory (
    symbolcategory_id smallint NOT NULL,
    symbolcategory_name character varying NOT NULL,
    symbolcategory_order smallint
);


--
-- Name: e_tiletype; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_tiletype (
    tiletype_id smallint NOT NULL,
    tiletype_name character varying NOT NULL,
    tiletype_order smallint
);


--
-- Name: e_wmsversion; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.e_wmsversion (
    wmsversion_id smallint NOT NULL,
    wmsversion_name character varying NOT NULL,
    wmsversion_order smallint
);


--
-- Name: form_level; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.form_level (
    id integer NOT NULL,
    level integer,
    mode integer,
    form integer,
    order_fld integer,
    visible smallint DEFAULT 1
);


--
-- Name: elenco_form; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.elenco_form AS
 SELECT form_level.id AS "ID",
    form_level.mode,
        CASE
            WHEN (form_level.mode = 2) THEN 'New'::text
            WHEN (form_level.mode = 3) THEN 'Elenco'::text
            WHEN (form_level.mode = 0) THEN 'View'::text
            WHEN (form_level.mode = 1) THEN 'Edit'::text
            ELSE 'Non definito'::text
        END AS "Modo Visualizzazione Pagina",
    e_form.id AS "Form ID",
    e_form.name AS "Nome Form",
    e_form.tab_type AS "Tipo Tabella",
    x.name AS "Livello Destinazione",
    e_level.name AS "Livello Visualizzazione",
        CASE
            WHEN (COALESCE((e_level.depth)::integer, '-1'::integer) = '-1'::integer) THEN 0
            ELSE (e_level.depth + 1)
        END AS "Profondita Albero",
    form_level.order_fld AS "Ordine Visualizzazione",
        CASE
            WHEN (form_level.visible = 1) THEN 'SI'::text
            ELSE 'NO'::text
        END AS "Visibile"
   FROM (((gisclient_34.form_level
     JOIN gisclient_34.e_level ON ((form_level.level = e_level.id)))
     JOIN gisclient_34.e_form ON ((e_form.id = form_level.form)))
     JOIN gisclient_34.e_level x ON ((x.id = e_form.level_destination)))
  ORDER BY
        CASE
            WHEN (COALESCE((e_level.depth)::integer, '-1'::integer) = '-1'::integer) THEN 0
            ELSE (e_level.depth + 1)
        END, form_level.level,
        CASE
            WHEN (form_level.mode = 2) THEN 'Nuovo'::text
            WHEN ((form_level.mode = 0) OR (form_level.mode = 3)) THEN 'Elenco'::text
            WHEN (form_level.mode = 1) THEN 'View'::text
            ELSE 'Edit'::text
        END, form_level.order_fld;


--
-- Name: export_i18n; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.export_i18n (
    exporti18n_id integer NOT NULL,
    table_name character varying,
    field_name character varying,
    project_name character varying,
    pkey_id character varying,
    language_id character varying,
    value text,
    original_value text
);


--
-- Name: export_i18n_exporti18n_id_seq; Type: SEQUENCE; Schema: gisclient_34; Owner: -
--

CREATE SEQUENCE gisclient_34.export_i18n_exporti18n_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: export_i18n_exporti18n_id_seq; Type: SEQUENCE OWNED BY; Schema: gisclient_34; Owner: -
--

ALTER SEQUENCE gisclient_34.export_i18n_exporti18n_id_seq OWNED BY gisclient_34.export_i18n.exporti18n_id;


--
-- Name: field; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.field (
    field_id integer NOT NULL,
    relation_id integer DEFAULT 0 NOT NULL,
    field_name character varying NOT NULL,
    field_header character varying NOT NULL,
    fieldtype_id smallint DEFAULT 1 NOT NULL,
    searchtype_id smallint DEFAULT 1 NOT NULL,
    resultype_id smallint DEFAULT 3 NOT NULL,
    field_format character varying,
    column_width integer,
    orderby_id integer DEFAULT 0 NOT NULL,
    field_filter integer DEFAULT 0 NOT NULL,
    datatype_id smallint DEFAULT 1 NOT NULL,
    field_order smallint DEFAULT 0 NOT NULL,
    default_op character varying,
    layer_id integer,
    editable numeric(1,0) DEFAULT 0,
    formula character varying,
    lookup_table character varying,
    lookup_id character varying,
    lookup_name character varying,
    filter_field_name character varying,
    mandatory numeric(1,0) DEFAULT 0,
    CONSTRAINT field_relation_id_check CHECK ((relation_id >= 0))
);


--
-- Name: field_groups; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.field_groups (
    field_id integer NOT NULL,
    groupname character varying NOT NULL,
    editable numeric(1,0) DEFAULT 0
);


--
-- Name: font; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.font (
    font_name character varying NOT NULL,
    file_name character varying NOT NULL
);


--
-- Name: group_authfilter; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.group_authfilter (
    groupname character varying NOT NULL,
    filter_id integer NOT NULL,
    filter_expression character varying
);


--
-- Name: groups; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.groups (
    groupname character varying NOT NULL,
    description character varying
);


--
-- Name: i18n_field; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.i18n_field (
    i18nf_id integer NOT NULL,
    table_name character varying(255),
    field_name character varying(255)
);


--
-- Name: i18n_field_i18nf_id_seq; Type: SEQUENCE; Schema: gisclient_34; Owner: -
--

CREATE SEQUENCE gisclient_34.i18n_field_i18nf_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: i18n_field_i18nf_id_seq; Type: SEQUENCE OWNED BY; Schema: gisclient_34; Owner: -
--

ALTER SEQUENCE gisclient_34.i18n_field_i18nf_id_seq OWNED BY gisclient_34.i18n_field.i18nf_id;


--
-- Name: layer; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.layer (
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
    sizeunits_id numeric(1,0) DEFAULT 1 NOT NULL,
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
    searchable_id numeric(1,0) DEFAULT 0,
    hide_vector_geom numeric(1,0) DEFAULT 0,
    CONSTRAINT layer_layertype_id_check CHECK ((layertype_id > 0))
);


--
-- Name: layer_authfilter; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.layer_authfilter (
    layer_id integer NOT NULL,
    filter_id integer NOT NULL,
    required smallint DEFAULT 0
);


--
-- Name: layer_groups_seq; Type: SEQUENCE; Schema: gisclient_34; Owner: -
--

CREATE SEQUENCE gisclient_34.layer_groups_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: layer_groups; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.layer_groups (
    layer_id integer NOT NULL,
    groupname character varying NOT NULL,
    wms integer DEFAULT 0,
    wfs integer DEFAULT 0,
    wfst integer DEFAULT 0,
    layer_name character varying,
    layer_groups_id integer DEFAULT nextval('gisclient_34.layer_groups_seq'::regclass) NOT NULL
);


--
-- Name: layer_link; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.layer_link (
    layer_id integer NOT NULL,
    link_id integer NOT NULL,
    resultype_id numeric(1,0)
);


--
-- Name: layergroup; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.layergroup (
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
    wmsversion_id integer,
    CONSTRAINT layergroup_name_lower_case CHECK (((layergroup_name)::text = lower((layergroup_name)::text)))
);


--
-- Name: link; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.link (
    link_id integer NOT NULL,
    project_name character varying NOT NULL,
    link_name character varying NOT NULL,
    link_def character varying NOT NULL,
    link_order smallint DEFAULT 0,
    winw smallint,
    winh smallint
);


--
-- Name: localization; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.localization (
    localization_id integer NOT NULL,
    project_name character varying NOT NULL,
    i18nf_id integer,
    pkey_id character varying NOT NULL,
    language_id character(2),
    value text
);


--
-- Name: localization_localization_id_seq; Type: SEQUENCE; Schema: gisclient_34; Owner: -
--

CREATE SEQUENCE gisclient_34.localization_localization_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: localization_localization_id_seq; Type: SEQUENCE OWNED BY; Schema: gisclient_34; Owner: -
--

ALTER SEQUENCE gisclient_34.localization_localization_id_seq OWNED BY gisclient_34.localization.localization_id;


--
-- Name: logs; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.logs (
    log_id integer NOT NULL,
    log_user character varying,
    log_time timestamp without time zone DEFAULT now() NOT NULL,
    log_action character varying,
    log_info character varying
);


--
-- Name: logs_log_id_seq; Type: SEQUENCE; Schema: gisclient_34; Owner: -
--

CREATE SEQUENCE gisclient_34.logs_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: logs_log_id_seq; Type: SEQUENCE OWNED BY; Schema: gisclient_34; Owner: -
--

ALTER SEQUENCE gisclient_34.logs_log_id_seq OWNED BY gisclient_34.logs.log_id;


--
-- Name: mapset; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.mapset (
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
    mapset_srid integer DEFAULT '-1'::integer,
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
    displayprojection integer,
    private integer DEFAULT 0,
    mapset_scale_type smallint DEFAULT 0 NOT NULL,
    mapset_order smallint DEFAULT 0 NOT NULL,
    mapset_tiles integer DEFAULT 0,
    open_counter integer DEFAULT 0 NOT NULL,
    geolocator jsonb
);


--
-- Name: mapset_groups; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.mapset_groups (
    mapset_name character varying NOT NULL,
    groupname character varying NOT NULL,
    edit smallint DEFAULT 0 NOT NULL
);


--
-- Name: mapset_layergroup; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.mapset_layergroup (
    mapset_name character varying NOT NULL,
    layergroup_id integer NOT NULL,
    status smallint DEFAULT 0,
    refmap smallint DEFAULT 0,
    hide smallint DEFAULT 0
);


--
-- Name: project; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.project (
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
    charset_encodings_id integer,
    legend_font_size integer DEFAULT 8
);


--
-- Name: project_admin; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.project_admin (
    project_name character varying NOT NULL,
    username character varying NOT NULL
);


--
-- Name: project_languages; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.project_languages (
    project_name character varying NOT NULL,
    language_id character(2) NOT NULL
);


--
-- Name: project_srs; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.project_srs (
    project_name character varying NOT NULL,
    srid integer NOT NULL,
    projparam character varying,
    max_extent character varying,
    resolutions character varying
);


--
-- Name: qt; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.qt (
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
    qt_title character varying
);


--
-- Name: qt_field; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.qt_field (
    qtfield_id integer NOT NULL,
    qt_id integer NOT NULL,
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
    editable numeric(1,0) DEFAULT 0,
    formula character varying,
    lookup_table character varying,
    lookup_id character varying,
    lookup_name character varying,
    filter_field_name character varying,
    CONSTRAINT qtfield_qtrelation_id_check CHECK ((qtrelation_id >= 0))
);


--
-- Name: qt_link; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.qt_link (
    qt_id integer NOT NULL,
    link_id integer NOT NULL,
    resultype_id smallint
);


--
-- Name: qt_relation; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.qt_relation (
    qtrelation_id integer NOT NULL,
    qt_id integer NOT NULL,
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
    language_id character varying(2)
);


--
-- Name: relation; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.relation (
    relation_id integer NOT NULL,
    catalog_id integer NOT NULL,
    relation_name character varying NOT NULL,
    relationtype_id integer DEFAULT 1 NOT NULL,
    data_field_1 character varying NOT NULL,
    data_field_2 character varying,
    data_field_3 character varying,
    table_name character varying NOT NULL,
    table_field_1 character varying NOT NULL,
    table_field_2 character varying,
    table_field_3 character varying,
    language_id character varying(2),
    layer_id integer,
    relation_title character varying,
    CONSTRAINT relation_name_lower_case CHECK (((relation_name)::text = lower((relation_name)::text))),
    CONSTRAINT relation_table_name_lower_case CHECK (((table_name)::text = lower((table_name)::text)))
);


--
-- Name: saved_filter; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.saved_filter (
    saved_filter_id integer NOT NULL,
    username character varying NOT NULL,
    saved_filter_name character varying NOT NULL,
    mapset_name character varying NOT NULL,
    layer_id integer NOT NULL,
    saved_filter_scope character varying NOT NULL,
    saved_filter_data jsonb NOT NULL
);


--
-- Name: saved_filter_saved_filter_id_seq; Type: SEQUENCE; Schema: gisclient_34; Owner: -
--

CREATE SEQUENCE gisclient_34.saved_filter_saved_filter_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: saved_filter_saved_filter_id_seq; Type: SEQUENCE OWNED BY; Schema: gisclient_34; Owner: -
--

ALTER SEQUENCE gisclient_34.saved_filter_saved_filter_id_seq OWNED BY gisclient_34.saved_filter.saved_filter_id;


--
-- Name: seldb_catalog; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_catalog AS
 SELECT '-1'::integer AS id,
    'Seleziona ====>'::character varying AS opzione,
    '0'::character varying AS project_name
UNION ALL
 SELECT foo.id,
    foo.opzione,
    foo.project_name
   FROM ( SELECT catalog.catalog_id AS id,
            catalog.catalog_name AS opzione,
            catalog.project_name
           FROM gisclient_34.catalog
          ORDER BY catalog.catalog_name) foo;


--
-- Name: seldb_catalog_wms; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_catalog_wms AS
 SELECT catalog_id AS id,
    catalog_name AS opzione,
    project_name
   FROM gisclient_34.catalog
  WHERE (connection_type = 7);


--
-- Name: seldb_charset_encodings; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_charset_encodings AS
 SELECT id,
    opzione,
    option_order
   FROM ( SELECT '-1'::integer AS id,
            'Seleziona ====>'::character varying AS opzione,
            (0)::smallint AS option_order
        UNION
         SELECT e_charset_encodings.charset_encodings_id AS id,
            e_charset_encodings.charset_encodings_name AS opzione,
            e_charset_encodings.charset_encodings_order AS option_order
           FROM gisclient_34.e_charset_encodings) foo
  ORDER BY id;


--
-- Name: seldb_conntype; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_conntype AS
 SELECT NULL::integer AS id,
    'Seleziona ====>'::character varying AS opzione
UNION ALL
 SELECT foo.id,
    foo.opzione
   FROM ( SELECT e_conntype.conntype_id AS id,
            e_conntype.conntype_name AS opzione
           FROM gisclient_34.e_conntype
          ORDER BY e_conntype.conntype_order) foo;


--
-- Name: seldb_datatype; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_datatype AS
 SELECT datatype_id AS id,
    datatype_name AS opzione
   FROM gisclient_34.e_datatype;


--
-- Name: seldb_fieldtype; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_fieldtype AS
 SELECT fieldtype_id AS id,
    fieldtype_name AS opzione
   FROM gisclient_34.e_fieldtype;


--
-- Name: seldb_filetype; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_filetype AS
 SELECT '-1'::integer AS id,
    'Seleziona ====>'::character varying AS opzione
UNION
 SELECT e_filetype.filetype_id AS id,
    e_filetype.filetype_name AS opzione
   FROM gisclient_34.e_filetype;


--
-- Name: seldb_font; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_font AS
 SELECT id,
    opzione
   FROM ( SELECT ''::character varying AS id,
            'Seleziona ====>'::character varying AS opzione
        UNION
         SELECT font.font_name AS id,
            font.font_name AS opzione
           FROM gisclient_34.font) foo
  ORDER BY id;


--
-- Name: seldb_group_authfilter; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_group_authfilter AS
 SELECT authfilter.filter_id AS id,
    authfilter.filter_name AS opzione,
        CASE
            WHEN (group_authfilter.groupname IS NULL) THEN ''::character varying
            ELSE group_authfilter.groupname
        END AS groupname
   FROM (gisclient_34.authfilter
     LEFT JOIN gisclient_34.group_authfilter USING (filter_id));


--
-- Name: seldb_language; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_language AS
 SELECT id,
    opzione
   FROM ( SELECT ''::text AS id,
            'Seleziona ====>'::character varying AS opzione
        UNION
         SELECT e_language.language_id AS id,
            e_language.language_name AS opzione
           FROM gisclient_34.e_language) foo
  ORDER BY id;


--
-- Name: seldb_layer_layergroup; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_layer_layergroup AS
 SELECT '-1'::integer AS id,
    'Seleziona ====>'::character varying AS opzione,
    NULL::integer AS layergroup_id
UNION
( SELECT DISTINCT layer.layer_id AS id,
    layer.layer_name AS opzione,
    layer.layergroup_id
   FROM gisclient_34.layer
  WHERE (layer.queryable = (1)::numeric)
  ORDER BY layer.layer_name, layer.layer_id, layer.layergroup_id);


--
-- Name: seldb_layertype; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_layertype AS
 SELECT id,
    opzione
   FROM ( SELECT '-1'::integer AS id,
            'Seleziona ====>'::character varying AS opzione
        UNION
         SELECT e_layertype.layertype_id AS id,
            e_layertype.layertype_name AS opzione
           FROM gisclient_34.e_layertype) foo
  ORDER BY id;


--
-- Name: seldb_lblposition; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_lblposition AS
 SELECT ''::character varying AS id,
    'Seleziona ====>'::character varying AS opzione
UNION ALL
 SELECT e_lblposition.lblposition_name AS id,
    e_lblposition.lblposition_name AS opzione
   FROM gisclient_34.e_lblposition
  WHERE ((e_lblposition.lblposition_name)::text = 'AUTO'::text)
UNION ALL
 SELECT foo.id,
    foo.opzione
   FROM ( SELECT e_lblposition.lblposition_name AS id,
            e_lblposition.lblposition_name AS opzione
           FROM gisclient_34.e_lblposition
          WHERE ((e_lblposition.lblposition_name)::text <> 'AUTO'::text)
          ORDER BY e_lblposition.lblposition_order) foo;


--
-- Name: seldb_legendtype; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_legendtype AS
 SELECT legendtype_id AS id,
    legendtype_name AS opzione
   FROM gisclient_34.e_legendtype
  ORDER BY legendtype_order;


--
-- Name: seldb_link; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_link AS
 SELECT '-1'::integer AS id,
    'Seleziona ====>'::character varying AS opzione,
    ''::character varying AS project_name
UNION
 SELECT link.link_id AS id,
    link.link_name AS opzione,
    link.project_name
   FROM gisclient_34.link;


--
-- Name: seldb_mapset_srid; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_mapset_srid AS
 SELECT 32632 AS id,
    32632 AS opzione,
    project.project_name,
    NULL::character varying AS max_extent,
    NULL::character varying AS resolutions
   FROM gisclient_34.project
UNION ALL
( SELECT project_srs.srid AS id,
    project_srs.srid AS opzione,
    project_srs.project_name,
    project_srs.max_extent,
    project_srs.resolutions
   FROM gisclient_34.project_srs
  ORDER BY project_srs.srid);


--
-- Name: seldb_mapset_tiles; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_mapset_tiles AS
 SELECT 0 AS id,
    'NO TILES'::character varying AS opzione
UNION ALL
 SELECT e_owstype.owstype_id AS id,
    e_owstype.owstype_name AS opzione
   FROM gisclient_34.e_owstype
  WHERE (e_owstype.owstype_id = ANY (ARRAY[2, 3]));


--
-- Name: seldb_orderby; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_orderby AS
 SELECT orderby_id AS id,
    orderby_name AS opzione
   FROM gisclient_34.e_orderby;


--
-- Name: seldb_outputformat; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_outputformat AS
 SELECT id,
    opzione
   FROM ( SELECT e_outputformat.outputformat_id AS id,
            e_outputformat.outputformat_name AS opzione
           FROM gisclient_34.e_outputformat
          WHERE (e_outputformat.outputformat_id = 7)
        UNION ALL
         SELECT e_outputformat.outputformat_id AS id,
            e_outputformat.outputformat_name AS opzione
           FROM gisclient_34.e_outputformat
          WHERE (e_outputformat.outputformat_id = 1)
        UNION ALL
         SELECT e_outputformat.outputformat_id AS id,
            e_outputformat.outputformat_name AS opzione
           FROM gisclient_34.e_outputformat
          WHERE (e_outputformat.outputformat_id = 9)
        UNION ALL
         SELECT e_outputformat.outputformat_id AS id,
            e_outputformat.outputformat_name AS opzione
           FROM gisclient_34.e_outputformat
          WHERE (e_outputformat.outputformat_id = 3)
        UNION ALL
         SELECT e_outputformat.outputformat_id AS id,
            e_outputformat.outputformat_name AS opzione
           FROM gisclient_34.e_outputformat
          WHERE (e_outputformat.outputformat_id <> ALL (ARRAY[1, 3, 7, 9]))) foo;


--
-- Name: seldb_owstype; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_owstype AS
 SELECT owstype_id AS id,
    owstype_name AS opzione
   FROM gisclient_34.e_owstype;


--
-- Name: seldb_papersize; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_papersize AS
 SELECT '-1'::integer AS id,
    'Seleziona ====>'::character varying AS opzione
UNION
 SELECT e_papersize.papersize_id AS id,
    e_papersize.papersize_name AS opzione
   FROM gisclient_34.e_papersize;


--
-- Name: seldb_pattern; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_pattern AS
 SELECT pattern_id AS id,
    pattern_name AS opzione
   FROM gisclient_34.e_pattern;


--
-- Name: seldb_project; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_project AS
 SELECT ''::character varying AS id,
    'Seleziona ====>'::character varying AS opzione
UNION
( SELECT DISTINCT project.project_name AS id,
    project.project_name AS opzione
   FROM gisclient_34.project
  ORDER BY project.project_name);


--
-- Name: seldb_qt; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_qt AS
 SELECT '-1'::integer AS id,
    'Seleziona ====>'::character varying AS opzione,
    ''::character varying AS mapset_name
UNION ALL
 SELECT qt.qt_id AS id,
    qt.qt_name AS opzione,
    mapset_layergroup.mapset_name
   FROM ((gisclient_34.qt qt
     LEFT JOIN gisclient_34.layer USING (layer_id))
     LEFT JOIN gisclient_34.mapset_layergroup USING (layergroup_id));


--
-- Name: seldb_qt_relation; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_qt_relation AS
 SELECT 0 AS id,
    'layer'::character varying AS opzione,
    0 AS qt_id
UNION ALL
 SELECT qt_relation.qtrelation_id AS id,
    qt_relation.qtrelation_name AS opzione,
    qt_relation.qt_id
   FROM gisclient_34.qt_relation;


--
-- Name: seldb_qt_relationtype; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_qt_relationtype AS
 SELECT relationtype_id AS id,
    relationtype_name AS opzione
   FROM gisclient_34.e_relationtype;


--
-- Name: seldb_relation; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_relation AS
 SELECT 0 AS id,
    'layer'::character varying AS opzione,
    0 AS layer_id
UNION
 SELECT relation.relation_id AS id,
    relation.relation_name AS opzione,
    relation.layer_id
   FROM gisclient_34.relation;


--
-- Name: seldb_relationtype; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_relationtype AS
 SELECT relationtype_id AS id,
    relationtype_name AS opzione
   FROM gisclient_34.e_relationtype;


--
-- Name: seldb_resultype; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_resultype AS
 SELECT resultype_id AS id,
    resultype_name AS opzione
   FROM gisclient_34.e_resultype
  ORDER BY resultype_order;


--
-- Name: seldb_searchable; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_searchable AS
 SELECT searchable_id AS id,
    searchable_name AS opzione
   FROM gisclient_34.e_searchable;


--
-- Name: seldb_searchtype; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_searchtype AS
 SELECT searchtype_id AS id,
    searchtype_name AS opzione
   FROM gisclient_34.e_searchtype;


--
-- Name: seldb_sizeunits; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_sizeunits AS
 SELECT id,
    opzione
   FROM ( SELECT e_sizeunits.sizeunits_id AS id,
            e_sizeunits.sizeunits_name AS opzione
           FROM gisclient_34.e_sizeunits) foo
  ORDER BY id;


--
-- Name: theme; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.theme (
    theme_id integer NOT NULL,
    project_name character varying,
    theme_name character varying NOT NULL,
    theme_title character varying,
    theme_order integer,
    locked smallint DEFAULT 0,
    theme_single numeric(1,0) DEFAULT 0,
    radio numeric(1,0) DEFAULT 0,
    copyright_string character varying,
    symbol_name character varying,
    theme_description character varying,
    CONSTRAINT theme_name_lower_case CHECK (((theme_name)::text = lower((theme_name)::text)))
);


--
-- Name: seldb_theme; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_theme AS
 SELECT '-1'::integer AS id,
    'Seleziona ====>'::character varying AS opzione,
    ''::character varying AS project_name
UNION
 SELECT theme.theme_id AS id,
    theme.theme_name AS opzione,
    theme.project_name
   FROM gisclient_34.theme;


--
-- Name: seldb_tiletype; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_tiletype AS
 SELECT tiletype_id AS id,
    tiletype_name AS opzione
   FROM gisclient_34.e_tiletype;


--
-- Name: seldb_wmsversion; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.seldb_wmsversion AS
 SELECT NULL::smallint AS id,
    'Seleziona ====>'::character varying AS opzione,
    '-1'::integer AS wmsversion_order
UNION
 SELECT e_wmsversion.wmsversion_id AS id,
    e_wmsversion.wmsversion_name AS opzione,
    e_wmsversion.wmsversion_order
   FROM gisclient_34.e_wmsversion
  ORDER BY 3;


--
-- Name: selgroup; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.selgroup (
    selgroup_id integer NOT NULL,
    project_name character varying NOT NULL,
    selgroup_name character varying NOT NULL,
    selgroup_title character varying,
    selgroup_order smallint DEFAULT 1
);


--
-- Name: selgroup_layer; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.selgroup_layer (
    selgroup_id integer NOT NULL,
    layer_id integer NOT NULL
);


--
-- Name: sessions; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.sessions (
    sess_id character varying(128) NOT NULL,
    sess_data bytea NOT NULL,
    sess_time integer NOT NULL,
    sess_lifetime integer NOT NULL
);


--
-- Name: style; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.style (
    style_id integer NOT NULL,
    class_id integer NOT NULL,
    style_name character varying NOT NULL,
    symbol_name character varying,
    color character varying,
    outlinecolor character varying,
    bgcolor character varying,
    angle character varying,
    size character varying,
    minsize character varying,
    maxsize character varying,
    width character varying,
    maxwidth character varying,
    minwidth character varying,
    locked smallint DEFAULT 0,
    style_def text,
    style_order integer,
    pattern_id integer,
    CONSTRAINT style_deprecate_bgcolor_check CHECK ((bgcolor IS NULL))
);


--
-- Name: symbol; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.symbol (
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
-- Name: theme_version; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.theme_version (
    theme_id integer NOT NULL,
    theme_version integer NOT NULL
);


--
-- Name: theme_version_id_seq; Type: SEQUENCE; Schema: gisclient_34; Owner: -
--

CREATE SEQUENCE gisclient_34.theme_version_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_group; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.user_group (
    username character varying NOT NULL,
    groupname character varying NOT NULL
);


--
-- Name: usercontext; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.usercontext (
    usercontext_id integer NOT NULL,
    username character varying NOT NULL,
    mapset_name character varying NOT NULL,
    title character varying NOT NULL,
    context text
);


--
-- Name: usercontext_usercontext_id_seq; Type: SEQUENCE; Schema: gisclient_34; Owner: -
--

CREATE SEQUENCE gisclient_34.usercontext_usercontext_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usercontext_usercontext_id_seq; Type: SEQUENCE OWNED BY; Schema: gisclient_34; Owner: -
--

ALTER SEQUENCE gisclient_34.usercontext_usercontext_id_seq OWNED BY gisclient_34.usercontext.usercontext_id;


--
-- Name: users; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.users (
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
-- Name: users_options; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.users_options (
    users_options_id integer NOT NULL,
    username character varying NOT NULL,
    option_key character varying NOT NULL,
    option_value character varying NOT NULL
);


--
-- Name: users_options_users_options_id_seq; Type: SEQUENCE; Schema: gisclient_34; Owner: -
--

CREATE SEQUENCE gisclient_34.users_options_users_options_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_options_users_options_id_seq; Type: SEQUENCE OWNED BY; Schema: gisclient_34; Owner: -
--

ALTER SEQUENCE gisclient_34.users_options_users_options_id_seq OWNED BY gisclient_34.users_options.users_options_id;


--
-- Name: version; Type: TABLE; Schema: gisclient_34; Owner: -
--

CREATE TABLE gisclient_34.version (
    version_id integer NOT NULL,
    version_name character varying NOT NULL,
    version_date date NOT NULL,
    version_key character varying NOT NULL
);


--
-- Name: version_version_id_seq; Type: SEQUENCE; Schema: gisclient_34; Owner: -
--

CREATE SEQUENCE gisclient_34.version_version_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: version_version_id_seq; Type: SEQUENCE OWNED BY; Schema: gisclient_34; Owner: -
--

ALTER SEQUENCE gisclient_34.version_version_id_seq OWNED BY gisclient_34.version.version_id;


--
-- Name: vista_catalog; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.vista_catalog AS
 SELECT catalog_id,
    catalog_name,
    project_name,
    connection_type,
    catalog_path,
    catalog_url,
    catalog_description,
    files_path,
        CASE
            WHEN (connection_type <> 6) THEN '(i) Controllo non possibile: connessione non PostGIS'::text
            WHEN ("substring"((catalog_path)::text, 0, "position"((catalog_path)::text, '/'::text)) <> (current_database())::text) THEN '(i) Controllo non possibile: DB diverso'::text
            WHEN (NOT ("substring"((catalog_path)::text, ("position"((catalog_path)::text, '/'::text) + 1), length((catalog_path)::text)) IN ( SELECT schemata.schema_name
               FROM information_schema.schemata))) THEN '(!) Lo schema configurato non esiste'::text
            ELSE 'OK'::text
        END AS catalog_control
   FROM gisclient_34.catalog c;


--
-- Name: vista_class; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.vista_class AS
 SELECT c.class_id,
    c.layer_id,
    c.class_name,
    c.class_title,
    c.class_text,
    c.expression,
    c.maxscale,
    c.minscale,
    c.class_template,
    c.class_order,
    c.legendtype_id,
    c.symbol_ttf_name,
    c.label_font,
    c.label_angle,
    c.label_color,
    c.label_outlinecolor,
    c.label_bgcolor,
    c.label_size,
    c.label_minsize,
    c.label_maxsize,
    c.label_position,
    c.label_antialias,
    c.label_free,
    c.label_priority,
    c.label_wrap,
    c.label_buffer,
    c.label_force,
    c.label_def,
    c.locked,
    c.class_image,
    c.keyimage,
        CASE
            WHEN ((c.expression IS NULL) AND (c.class_order <= ( SELECT max(class.class_order) AS max
               FROM gisclient_34.class
              WHERE ((class.layer_id = c.layer_id) AND (class.class_id <> c.class_id) AND (class.expression IS NOT NULL))))) THEN '(!) Classe con espressione vuota, spostare in fondo'::text
            WHEN ((c.legendtype_id = 1) AND (NOT (c.class_id IN ( SELECT style.class_id
               FROM gisclient_34.style)))) THEN '(!) Mostra in legenda ma nessuno stile presente'::text
            WHEN ((c.label_font IS NOT NULL) AND (c.label_color IS NOT NULL) AND (c.label_size IS NOT NULL) AND (c.label_position IS NOT NULL) AND (l.labelitem IS NULL)) THEN '(!) Etichetta configurata correttamente, ma nessun campo etichetta configurato sul layer'::text
            WHEN ((c.label_font IS NOT NULL) AND (c.label_color IS NOT NULL) AND (c.label_size IS NOT NULL) AND (c.label_position IS NOT NULL) AND (l.labelitem IS NOT NULL)) THEN 'OK. (i) Con etichetta'::text
            ELSE 'OK'::text
        END AS class_control
   FROM (gisclient_34.class c
     JOIN gisclient_34.layer l USING (layer_id));


--
-- Name: vista_document_paths; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.vista_document_paths AS
 WITH RECURSIVE paths(doc_path, doc_id) AS (
         SELECT ('/'::text || (document_1.doc_name)::text) AS doc_path,
            document_1.doc_id
           FROM gisclient_34.document document_1
          WHERE (document_1.doc_parent_id IS NULL)
        UNION ALL
         SELECT ((p.doc_path || '/'::text) || (c.doc_name)::text) AS doc_path,
            c.doc_id
           FROM (gisclient_34.document c
             JOIN paths p ON ((p.doc_id = c.doc_parent_id)))
        )
 SELECT document.doc_id,
    document.doc_parent_id,
    document.doc_name,
    document.doc_type,
    document.doc_public,
    paths.doc_path
   FROM (gisclient_34.document
     JOIN paths USING (doc_id));


--
-- Name: vista_field; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.vista_field AS
 SELECT field.field_id,
    field.layer_id,
    field.fieldtype_id,
    x.relation_id,
    field.field_name,
    field.resultype_id,
    field.field_header,
    field.field_order,
    COALESCE(field.column_width, 0) AS column_width,
    x.name AS relation_name,
    x.relationtype_id,
    x.relationtype_name,
    field.editable,
        CASE
            WHEN (field.relation_id = 0) THEN
            CASE
                WHEN (c.connection_type <> 6) THEN '(i) Controllo non possibile: connessione non PostGIS'::text
                WHEN ("substring"((c.catalog_path)::text, 0, "position"((c.catalog_path)::text, '/'::text)) <> (current_database())::text) THEN '(i) Controllo non possibile: DB diverso'::text
                WHEN (NOT ((field.field_name)::text IN ( SELECT columns.column_name
                   FROM information_schema.columns
                  WHERE (("substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text)) = (i.table_schema)::text) AND ((l.data)::text = (i.table_name)::text))))) THEN '(!) Il campo non esiste nella tabella'::text
                ELSE 'OK'::text
            END
            ELSE
            CASE
                WHEN (cr.connection_type <> 6) THEN '(i) Controllo non possibile: connessione non PostGIS'::text
                WHEN ("substring"((cr.catalog_path)::text, 0, "position"((cr.catalog_path)::text, '/'::text)) <> (current_database())::text) THEN '(i) Controllo non possibile: DB diverso'::text
                WHEN (NOT ((field.field_name)::text IN ( SELECT columns.column_name
                   FROM information_schema.columns
                  WHERE (("substring"((cr.catalog_path)::text, ("position"((cr.catalog_path)::text, '/'::text) + 1), length((cr.catalog_path)::text)) = (i.table_schema)::text) AND ((r.table_name)::text = (i.table_name)::text))))) THEN ('(!) Il campo non esiste nella tabella di relazione: '::text || (r.relation_name)::text)
                ELSE 'OK'::text
            END
        END AS field_control
   FROM (((((((gisclient_34.field
     JOIN gisclient_34.e_fieldtype USING (fieldtype_id))
     JOIN ( SELECT y.relationtype_id,
            y.relation_id,
            y.name,
            z.relationtype_name
           FROM (( SELECT 0 AS relation_id,
                    'Data Layer'::character varying AS name,
                    0 AS relationtype_id
                UNION
                 SELECT relation.relation_id,
                    COALESCE(relation.relation_name, 'Nessuna Relazione'::character varying) AS name,
                    relation.relationtype_id
                   FROM gisclient_34.relation) y
             JOIN ( SELECT 0 AS relationtype_id,
                    ''::character varying AS relationtype_name
                UNION
                 SELECT e_relationtype.relationtype_id,
                    e_relationtype.relationtype_name
                   FROM gisclient_34.e_relationtype) z USING (relationtype_id))) x USING (relation_id))
     JOIN gisclient_34.layer l USING (layer_id))
     JOIN gisclient_34.catalog c USING (catalog_id))
     LEFT JOIN gisclient_34.relation r USING (relation_id))
     LEFT JOIN gisclient_34.catalog cr ON ((cr.catalog_id = r.catalog_id)))
     LEFT JOIN information_schema.columns i ON ((((field.field_name)::text = (i.column_name)::text) AND ("substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text)) = (i.table_schema)::text) AND (((l.data)::text = (i.table_name)::text) OR ((r.table_name)::text = (i.table_name)::text)))))
  ORDER BY field.field_id, x.relation_id, x.relationtype_id;


--
-- Name: vista_group_authfilter; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.vista_group_authfilter AS
 SELECT af.filter_id,
    af.filter_name,
    gaf.filter_expression,
    gaf.groupname
   FROM (gisclient_34.authfilter af
     JOIN gisclient_34.group_authfilter gaf USING (filter_id))
  ORDER BY af.filter_name;


--
-- Name: vista_layer; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.vista_layer AS
 SELECT l.layer_id,
    l.layergroup_id,
    l.layer_name,
    l.layertype_id,
    l.catalog_id,
    l.data,
    l.data_geom,
    l.data_unique,
    l.data_srid,
    l.data_filter,
    l.classitem,
    l.labelitem,
    l.labelsizeitem,
    l.labelminscale,
    l.labelmaxscale,
    l.maxscale,
    l.minscale,
    l.symbolscale,
    l.opacity,
    l.maxfeatures,
    l.sizeunits_id,
    l.layer_def,
    l.metadata,
    l.template,
    l.header,
    l.footer,
    l.tolerance,
    l.layer_order,
    l.queryable,
    l.layer_title,
    l.zoom_buffer,
    l.group_object,
    l.selection_color,
    l.papersize_id,
    l.toleranceunits_id,
    l.selection_width,
    l.selection_info,
    l.hidden,
    l.private,
    l.postlabelcache,
    l.maxvectfeatures,
    l.data_type,
    l.last_update,
    l.data_extent,
    l.searchable_id,
    l.hide_vector_geom,
        CASE
            WHEN ((l.queryable = (1)::numeric) AND (l.hidden = (0)::numeric) AND (l.layer_id IN ( SELECT field.layer_id
               FROM gisclient_34.field
              WHERE (field.resultype_id <> 4)))) THEN 'SI. Config. OK'::text
            WHEN ((l.queryable = (1)::numeric) AND (l.hidden = (1)::numeric) AND (l.layer_id IN ( SELECT field.layer_id
               FROM gisclient_34.field
              WHERE (field.resultype_id <> 4)))) THEN 'SI. Ma èascosto'::text
            WHEN ((l.queryable = (1)::numeric) AND (l.layer_id IN ( SELECT field.layer_id
               FROM gisclient_34.field
              WHERE (field.resultype_id = 4)))) THEN 'NO. Nessun campo nei risultati'::text
            ELSE 'NO. WFS non abilitato'::text
        END AS is_queryable,
        CASE
            WHEN ((l.queryable = (1)::numeric) AND (l.layer_id IN ( SELECT field.layer_id
               FROM gisclient_34.field
              WHERE (field.editable = (1)::numeric)))) THEN 'SI. Config. OK'::text
            WHEN ((l.queryable = (1)::numeric) AND (l.layer_id IN ( SELECT field.layer_id
               FROM gisclient_34.field
              WHERE (field.editable = (0)::numeric)))) THEN 'NO. Nessun campo èditabile'::text
            WHEN ((l.queryable = (0)::numeric) AND (l.layer_id IN ( SELECT field.layer_id
               FROM gisclient_34.field
              WHERE (field.editable = (1)::numeric)))) THEN 'NO. Esiste un campo editabile ma il WFS non èttivo'::text
            ELSE 'NO.'::text
        END AS is_editable,
        CASE
            WHEN (c.connection_type <> 6) THEN '(i) Controllo non possibile: connessione non PostGIS'::text
            WHEN ("substring"((c.catalog_path)::text, 0, "position"((c.catalog_path)::text, '/'::text)) <> (current_database())::text) THEN '(i) Controllo non possibile: DB diverso'::text
            WHEN (NOT ((l.data)::text IN ( SELECT tables.table_name
               FROM information_schema.tables
              WHERE ((tables.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text)))))) THEN '(!) La tabella non esiste nel DB'::text
            WHEN (NOT ((l.data_geom)::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE (((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (l.data)::text) AND ((columns.data_type)::text = 'USER-DEFINED'::text))))) THEN '(!) Il campo geometrico del layer non esiste'::text
            WHEN (NOT ((l.data_unique)::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE (((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (l.data)::text))))) THEN '(!) Il campo chiave del layer non esiste'::text
            WHEN (NOT (l.data_srid IN ( SELECT geometry_columns.srid
               FROM public.geometry_columns
              WHERE (((geometry_columns.f_table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND (geometry_columns.f_table_name = (l.data)::name))))) THEN '(!) Lo SRID configurato non èuello corretto'::text
            WHEN (NOT (upper((l.data_type)::text) IN ( SELECT geometry_columns.type
               FROM public.geometry_columns
              WHERE (((geometry_columns.f_table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND (geometry_columns.f_table_name = (l.data)::name))))) THEN '(!) Geometrytype non corretto'::text
            WHEN ((l.labelitem IS NOT NULL) AND (NOT ((l.labelitem)::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE (((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (l.data)::text)))))) THEN '(!) Il campo etichetta del layer non esiste'::text
            WHEN ((l.labelitem IS NOT NULL) AND (NOT ((l.labelitem)::text IN ( SELECT field.field_name
               FROM gisclient_34.field
              WHERE (field.layer_id = l.layer_id))))) THEN '(!) Campo etichetta non presente nei campi del layer'::text
            WHEN ((l.labelsizeitem IS NOT NULL) AND (NOT ((l.labelsizeitem)::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE (((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (l.data)::text)))))) THEN '(!) Il campo altezza etichetta del layer non esiste'::text
            WHEN ((l.labelsizeitem IS NOT NULL) AND (NOT ((l.labelsizeitem)::text IN ( SELECT field.field_name
               FROM gisclient_34.field
              WHERE (field.layer_id = l.layer_id))))) THEN '(!) Campo altezza etichetta non presente nei campi del layer'::text
            WHEN ((((((t.project_name)::text || '.'::text) || (lg.layergroup_name)::text) || '.'::text) || (l.layer_name)::text) IN ( SELECT (((((t2.project_name)::text || '.'::text) || (lg2.layergroup_name)::text) || '.'::text) || (l2.layer_name)::text)
               FROM ((gisclient_34.layer l2
                 JOIN gisclient_34.layergroup lg2 USING (layergroup_id))
                 JOIN gisclient_34.theme t2 USING (theme_id))
              GROUP BY (((((t2.project_name)::text || '.'::text) || (lg2.layergroup_name)::text) || '.'::text) || (l2.layer_name)::text)
             HAVING (count((((((t2.project_name)::text || '.'::text) || (lg2.layergroup_name)::text) || '.'::text) || (l2.layer_name)::text)) > 1))) THEN '(!) Combinazione nome layergroup + nome layer non univoca. Cambiare nome al layer o al layergroup'::text
            WHEN (NOT (l.layer_id IN ( SELECT class.layer_id
               FROM gisclient_34.class))) THEN 'OK (i) Non ci sono classi configurate in questo layer'::text
            ELSE 'OK'::text
        END AS layer_control
   FROM ((((gisclient_34.layer l
     JOIN gisclient_34.catalog c USING (catalog_id))
     JOIN gisclient_34.e_layertype USING (layertype_id))
     JOIN gisclient_34.layergroup lg USING (layergroup_id))
     JOIN gisclient_34.theme t USING (theme_id));


--
-- Name: vista_layergroup; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.vista_layergroup AS
 SELECT lg.layergroup_id,
    lg.theme_id,
    lg.layergroup_name,
    lg.layergroup_title,
    lg.layergroup_maxscale,
    lg.layergroup_minscale,
    lg.layergroup_smbscale,
    lg.layergroup_order,
    lg.locked,
    lg.multi,
    lg.hidden,
    lg.isbaselayer,
    lg.tiletype_id,
    lg.sld,
    lg.style,
    lg.url,
    lg.owstype_id,
    lg.outputformat_id,
    lg.layers,
    lg.parameters,
    lg.gutter,
    lg.transition,
    lg.tree_group,
    lg.layergroup_description,
    lg.buffer,
    lg.tiles_extent,
    lg.tiles_extent_srid,
    lg.layergroup_single,
    lg.metadata_url,
    lg.opacity,
    lg.tile_origin,
    lg.tile_resolutions,
    lg.tile_matrix_set,
    lg.wmsversion_id,
        CASE
            WHEN ((lg.tiles_extent_srid IS NOT NULL) AND (NOT (lg.tiles_extent_srid IN ( SELECT project_srs.srid
               FROM gisclient_34.project_srs
              WHERE ((project_srs.project_name)::text = (t.project_name)::text))))) THEN '(!) SRID estensione tiles non presente nei sistemi di riferimento del progetto'::text
            WHEN ((lg.owstype_id = 6) AND (lg.url IS NULL)) THEN '(!) Nessuna URL configurata per la chiamata TMS'::text
            WHEN ((lg.owstype_id = 6) AND (lg.layers IS NULL)) THEN '(!) Nessun layer configurato per la chiamata TMS'::text
            WHEN ((lg.owstype_id = 9) AND (lg.url IS NULL)) THEN '(!) Nessuna URL configurata per la chiamata WMTS'::text
            WHEN ((lg.owstype_id = 9) AND (lg.layers IS NULL)) THEN '(!) Nessun layer configurato per la chiamata WMTS'::text
            WHEN ((lg.owstype_id = 9) AND (lg.tile_matrix_set IS NULL)) THEN '(!) Nessun Tile Matrix configurato per la chiamata WMTS'::text
            WHEN ((lg.owstype_id = 9) AND (lg.style IS NULL)) THEN '(!) Nessuno stile configurato per la chiamata WMTS'::text
            WHEN ((lg.owstype_id = 9) AND (lg.tile_origin IS NULL)) THEN '(!) Nessuna origine configurata per la chiamata WMTS'::text
            WHEN ((lg.opacity IS NULL) OR ((lg.opacity)::text = '0'::text)) THEN '(i) Attenzione: trasparenza totale'::text
            WHEN ((NOT (lg.layergroup_id IN ( SELECT layer.layergroup_id
               FROM gisclient_34.layer))) AND (lg.layers IS NULL)) THEN 'OK (i) Non ci sono layer configurati in questo layergroup'::text
            ELSE 'OK'::text
        END AS layergroup_control
   FROM (gisclient_34.layergroup lg
     JOIN gisclient_34.theme t USING (theme_id));


--
-- Name: vista_link; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.vista_link AS
 SELECT link_id,
    project_name,
    link_name,
    link_def,
    link_order,
    winw,
    winh,
        CASE
            WHEN ((link_def)::text !~~ 'http%://%@%@'::text) THEN '(!) Definizione del link non corretta. La sintassi deve essere: http://url@campo@'::text
            WHEN (NOT (link_id IN ( SELECT link.link_id
               FROM gisclient_34.layer_link link))) THEN 'OK. Non utilizzato'::text
            WHEN (NOT (replace("substring"((link_def)::text, '%#"@%@#"%'::text, '#'::text), '@'::text, ''::text) IN ( SELECT qtfield.field_name AS qtfield_name
               FROM gisclient_34.field qtfield
              WHERE (qtfield.layer_id IN ( SELECT link.layer_id
                       FROM gisclient_34.layer_link link
                      WHERE (link.link_id = l.link_id)))))) THEN '(!) Campo non presente nel layer'::text
            ELSE 'OK. In uso'::text
        END AS link_control
   FROM gisclient_34.link l;


--
-- Name: vista_mapset; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.vista_mapset AS
 SELECT mapset_name,
    project_name,
    mapset_title,
    mapset_description,
    template,
    mapset_extent,
    page_size,
    filter_data,
    dl_image_res,
    imagelabel,
    bg_color,
    refmap_extent,
    test_extent,
    mapset_srid,
    mapset_def,
    mapset_group,
    sizeunits_id,
    static_reference,
    metadata,
    mapset_note,
    mask,
    maxscale,
    minscale,
    mapset_scales,
    displayprojection,
    private,
    mapset_scale_type,
    mapset_order,
    mapset_tiles,
        CASE
            WHEN (NOT ((mapset_name)::text IN ( SELECT mapset_layergroup.mapset_name
               FROM gisclient_34.mapset_layergroup))) THEN '(!) Nessun layergroup presente'::text
            WHEN (75 <= ( SELECT count(mapset_layergroup.layergroup_id) AS count
               FROM gisclient_34.mapset_layergroup
              WHERE ((mapset_layergroup.mapset_name)::text = (m.mapset_name)::text)
              GROUP BY mapset_layergroup.mapset_name)) THEN '(!) Openlayers non consente di rappresentare più di 75 layergroup alla volta'::text
            WHEN (mapset_scales IS NULL) THEN '(!) Nessun elenco di scale configurato'::text
            WHEN (mapset_srid <> displayprojection) THEN '(i) Coordinate visualizzate diverse da quelle di mappa'::text
            WHEN (0 = ( SELECT max(mapset_layergroup.refmap) AS max
               FROM gisclient_34.mapset_layergroup
              WHERE ((mapset_layergroup.mapset_name)::text = (m.mapset_name)::text)
              GROUP BY mapset_layergroup.mapset_name)) THEN '(i) Nessuna reference map'::text
            ELSE 'OK'::text
        END AS mapset_control
   FROM gisclient_34.mapset m;


--
-- Name: vista_project_languages; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.vista_project_languages AS
 SELECT project_languages.project_name,
    project_languages.language_id,
    e_language.language_name,
    e_language.language_order
   FROM (gisclient_34.project_languages
     JOIN gisclient_34.e_language ON ((project_languages.language_id = e_language.language_id)))
  ORDER BY e_language.language_order;


--
-- Name: vista_relation; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.vista_relation AS
 SELECT r.relation_id,
    r.catalog_id,
    r.relation_name,
    r.relationtype_id,
    r.data_field_1,
    r.data_field_2,
    r.data_field_3,
    r.table_name,
    r.table_field_1,
    r.table_field_2,
    r.table_field_3,
    r.language_id,
    r.layer_id,
        CASE
            WHEN (c.connection_type <> 6) THEN '(i) Controllo non possibile: connessione non PostGIS'::text
            WHEN ("substring"((c.catalog_path)::text, 0, "position"((c.catalog_path)::text, '/'::text)) <> (current_database())::text) THEN '(i) Controllo non possibile: DB diverso'::text
            WHEN (NOT ((l.layer_name)::text IN ( SELECT tables.table_name
               FROM information_schema.tables
              WHERE ((tables.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text)))))) THEN '(!) La tabella DB del layer non esiste'::text
            WHEN (NOT ((r.table_name)::text IN ( SELECT tables.table_name
               FROM information_schema.tables
              WHERE ((tables.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text)))))) THEN '(!) tabella DB di JOIN non esiste'::text
            WHEN ((r.data_field_1 IS NULL) OR (r.table_field_1 IS NULL)) THEN '(!) Uno dei campi della JOIN 1 è vuoto'::text
            WHEN (NOT ((r.data_field_1)::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE (((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (l.layer_name)::text))))) THEN '(!) Il campo chiave layer non esiste'::text
            WHEN (NOT ((r.table_field_1)::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE (((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (r.table_name)::text))))) THEN '(!) Il campo chiave della relazione non esiste'::text
            WHEN ((r.data_field_2 IS NULL) AND (r.table_field_2 IS NULL)) THEN 'OK'::text
            WHEN ((r.data_field_2 IS NULL) OR (r.table_field_2 IS NULL)) THEN '(!) Uno dei campi della JOIN 2 è vuoto'::text
            WHEN (NOT ((r.data_field_2)::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE (((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (l.layer_name)::text))))) THEN '(!) Il campo chiave layer della JOIN 2 non esiste'::text
            WHEN (NOT ((r.table_field_2)::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE (((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (r.table_name)::text))))) THEN '(!) Il campo chiave relazione della JOIN 2 non esiste'::text
            WHEN ((r.data_field_3 IS NULL) AND (r.table_field_3 IS NULL)) THEN 'OK'::text
            WHEN ((r.data_field_3 IS NULL) OR (r.table_field_3 IS NULL)) THEN '(!) Uno dei campi della JOIN 3 è vuoto'::text
            WHEN (NOT ((r.data_field_3)::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE (((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (l.layer_name)::text))))) THEN '(!) Il campo chiave layer della JOIN 3 non esiste'::text
            WHEN (NOT ((r.table_field_3)::text IN ( SELECT columns.column_name
               FROM information_schema.columns
              WHERE (((columns.table_schema)::text = "substring"((c.catalog_path)::text, ("position"((c.catalog_path)::text, '/'::text) + 1), length((c.catalog_path)::text))) AND ((columns.table_name)::text = (r.table_name)::text))))) THEN '(!) Il campo chiave relazione della JOIN 3 non esiste'::text
            ELSE 'OK'::text
        END AS relation_control
   FROM (((gisclient_34.relation r
     JOIN gisclient_34.catalog c USING (catalog_id))
     JOIN gisclient_34.layer l USING (layer_id))
     JOIN gisclient_34.e_relationtype rt USING (relationtype_id));


--
-- Name: vista_style; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.vista_style AS
 SELECT s.style_id,
    s.class_id,
    s.style_name,
    s.symbol_name,
    s.color,
    s.outlinecolor,
    s.bgcolor,
    s.angle,
    s.size,
    s.minsize,
    s.maxsize,
    s.width,
    s.maxwidth,
    s.minwidth,
    s.locked,
    s.style_def,
    s.style_order,
    s.pattern_id,
        CASE
            WHEN (NOT ((s.symbol_name)::text IN ( SELECT symbol_1.symbol_name
               FROM gisclient_34.symbol symbol_1))) THEN '(!) Il simbolo non esiste'::text
            WHEN ((s.color IS NULL) AND (s.outlinecolor IS NULL) AND (s.bgcolor IS NULL)) THEN '(!) Stile senza colore'::text
            WHEN ((s.symbol_name IS NOT NULL) AND (s.size IS NULL)) THEN '(!) Stile senza dimensione'::text
            ELSE 'OK'::text
        END AS style_control
   FROM (gisclient_34.style s
     LEFT JOIN gisclient_34.symbol USING (symbol_name))
  ORDER BY s.style_order;


--
-- Name: vista_version; Type: VIEW; Schema: gisclient_34; Owner: -
--

CREATE VIEW gisclient_34.vista_version AS
 SELECT version_id,
    version_name,
    version_date
   FROM gisclient_34.version
  WHERE ((version_key)::text = 'author'::text)
  ORDER BY version_id DESC
 LIMIT 1;


--
-- Name: document doc_id; Type: DEFAULT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.document ALTER COLUMN doc_id SET DEFAULT nextval('gisclient_34.document_doc_id_seq'::regclass);


--
-- Name: e_pattern pattern_id; Type: DEFAULT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_pattern ALTER COLUMN pattern_id SET DEFAULT nextval('gisclient_34.e_pattern_pattern_id_seq'::regclass);


--
-- Name: export_i18n exporti18n_id; Type: DEFAULT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.export_i18n ALTER COLUMN exporti18n_id SET DEFAULT nextval('gisclient_34.export_i18n_exporti18n_id_seq'::regclass);


--
-- Name: i18n_field i18nf_id; Type: DEFAULT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.i18n_field ALTER COLUMN i18nf_id SET DEFAULT nextval('gisclient_34.i18n_field_i18nf_id_seq'::regclass);


--
-- Name: localization localization_id; Type: DEFAULT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.localization ALTER COLUMN localization_id SET DEFAULT nextval('gisclient_34.localization_localization_id_seq'::regclass);


--
-- Name: logs log_id; Type: DEFAULT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.logs ALTER COLUMN log_id SET DEFAULT nextval('gisclient_34.logs_log_id_seq'::regclass);


--
-- Name: saved_filter saved_filter_id; Type: DEFAULT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.saved_filter ALTER COLUMN saved_filter_id SET DEFAULT nextval('gisclient_34.saved_filter_saved_filter_id_seq'::regclass);


--
-- Name: usercontext usercontext_id; Type: DEFAULT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.usercontext ALTER COLUMN usercontext_id SET DEFAULT nextval('gisclient_34.usercontext_usercontext_id_seq'::regclass);


--
-- Name: users_options users_options_id; Type: DEFAULT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.users_options ALTER COLUMN users_options_id SET DEFAULT nextval('gisclient_34.users_options_users_options_id_seq'::regclass);


--
-- Name: version version_id; Type: DEFAULT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.version ALTER COLUMN version_id SET DEFAULT nextval('gisclient_34.version_version_id_seq'::regclass);


--
-- Data for Name: authfilter; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.authfilter (filter_id, filter_name, filter_description, filter_priority) FROM stdin;
\.


--
-- Data for Name: catalog; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.catalog (catalog_id, catalog_name, project_name, connection_type, catalog_path, catalog_url, catalog_description, files_path, set_extent) FROM stdin;
1	geo	default	6	gisclient/geo	\N	\N	\N	1
\.


--
-- Data for Name: catalog_import; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.catalog_import (catalog_import_id, project_name, catalog_import_name, catalog_from, catalog_to, catalog_import_description) FROM stdin;
\.


--
-- Data for Name: class; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.class (class_id, layer_id, class_name, class_title, class_text, expression, maxscale, minscale, class_template, class_order, legendtype_id, symbol_ttf_name, label_font, label_angle, label_color, label_outlinecolor, label_bgcolor, label_size, label_minsize, label_maxsize, label_position, label_antialias, label_free, label_priority, label_wrap, label_buffer, label_force, label_def, locked, class_image, keyimage) FROM stdin;
3	1	buildings	Buildings	\N	\N	\N	\N	\N	\N	1	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	0	\N	\N	0	1	\N	0	\N	\N
35	21	hydro_poly	Hydro poly	\N	\N	\N	\N	\N	\N	1	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	0	\N	\N	0	1	\N	0	\N	\N
34	24	river	River	\N	([code] = 8101)	501000	\N	\N	100	1	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	0	\N	\N	0	1	\N	0	\N	\N
42	24	stream	Stream	\N	([code] = 8102)	26000	\N	\N	200	1	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	0	\N	\N	0	1	\N	0	\N	\N
43	24	canal	Canal	\N	([code] = 8103)	101000	\N	\N	300	1	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	0	\N	\N	0	1	\N	0	\N	\N
51	22	tertiary	Tertiary	\N	("[code]" IN "5115,5135")	51000	\N	\N	400	1	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	0	\N	\N	0	1	\N	0	\N	\N
36	24	drain	Drain	\N	([code] = 8104)	51000	\N	\N	400	1	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	0	\N	\N	0	1	\N	0	\N	\N
52	22	unclassified	Unclassified	\N	("[code]" IN "5121")	26000	\N	\N	500	1	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	0	\N	\N	0	1	\N	0	\N	\N
37	23	railroads	Railroads	\N	\N	\N	\N	\N	\N	1	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	0	\N	\N	0	1	\N	0	\N	\N
53	22	residential_service	Residential / service	\N	("[code]" IN "5122,5141")	11000	\N	\N	600	1	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	0	\N	\N	0	1	\N	0	\N	\N
44	22	others	Others	\N	\N	6000	\N	\N	999	1	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	0	\N	\N	0	1	\N	0	\N	\N
50	22	secondary	Secondary	\N	("[code]" IN "5114,5134")	101000	\N	\N	300	1	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	0	\N	\N	0	1	\N	0	\N	\N
45	31	town	Town	\N	\N	\N	\N	\N	\N	1	\N	verdana	\N	0 0 0	255 255 255	\N	10	\N	\N	AUTO	1	0	\N	\N	0	1	\N	0	\N	\N
47	33	hamlet_locality	Hamlet locality	\N	\N	\N	\N	\N	\N	1	\N	verdana	\N	0 0 0	255 255 255	\N	6	\N	\N	AUTO	1	0	\N	\N	0	1	\N	0	\N	\N
46	32	village_suburb	Village and suburb	\N	\N	\N	\N	\N	\N	1	\N	verdana	\N	0 0 0	255 255 255	\N	7	\N	\N	AUTO	1	0	\N	\N	\N	1	\N	0	\N	\N
49	22	primary_trunk	Primary / trunk	\N	("[code]" IN "5113,5112,5133,5132")	251000	\N	\N	200	1	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	0	\N	\N	0	1	\N	0	\N	\N
48	22	motorway	Motorway	\N	("[code]" IN "5111,5131")	2001000	\N	\N	100	1	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	0	\N	\N	0	1	\N	0	\N	\N
39	20	regions	Regions	\N	([admin_lv] = 4)	\N	\N	\N	100	1	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	0	\N	\N	0	1	\N	0	\N	\N
41	20	municipalities	Municipalities	\N	([admin_lv] = 8)	1000000	\N	\N	300	1	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	0	\N	\N	0	1	\N	0	\N	\N
40	20	provinces	Provinces	\N	([admin_lv] = 6)	\N	\N	\N	200	1	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	0	\N	\N	0	1	\N	0	\N	\N
38	26	city	City	\N	\N	\N	\N	\N	\N	1	\N	verdana-bold	\N	0 0 0	255 255 255	\N	12	\N	\N	AUTO	1	0	\N	\N	99	1	\N	0	\N	\N
\.


--
-- Data for Name: document; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.document (doc_id, doc_parent_id, doc_name, doc_type, doc_public) FROM stdin;
1	\N	documenti	folder	f
\.


--
-- Data for Name: e_charset_encodings; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_charset_encodings (charset_encodings_id, charset_encodings_name, charset_encodings_order) FROM stdin;
1	ISO-8859-1	1
2	UTF-8	2
\.


--
-- Data for Name: e_conntype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_conntype (conntype_id, conntype_name, conntype_order) FROM stdin;
7	WMS	4
3	SDE	7
6	Postgis	2
8	Oracle Spatial	3
1	Local Folder	1
9	WFS	5
4	OGR	6
\.


--
-- Data for Name: e_datatype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_datatype (datatype_id, datatype_name, datatype_order) FROM stdin;
1	Stringa di testo	\N
2	Numero	\N
3	Data	\N
10	Immagine	\N
15	File	\N
\.


--
-- Data for Name: e_fieldformat; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_fieldformat (fieldformat_id, fieldformat_name, fieldformat_format, fieldformat_order) FROM stdin;
1	intero	%d	10
2	decimale (1 cifra)	%01.1f	20
3	decimale (2 cifre)	%01.2f	30
\.


--
-- Data for Name: e_fieldtype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_fieldtype (fieldtype_id, fieldtype_name, fieldtype_order) FROM stdin;
1	Standard	\N
3	E-mail	\N
104	Max	\N
103	Min	\N
10	File	\N
2	Link	\N
107	Variance	\N
106	Standard Deviation	\N
105	Count	\N
102	Average	\N
101	Sum	\N
8	Image	\N
\.


--
-- Data for Name: e_filetype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_filetype (filetype_id, filetype_name, filetype_order) FROM stdin;
1	SQL file	1
2	CSV file	2
3	Shape file	3
\.


--
-- Data for Name: e_form; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_form (id, name, config_file, tab_type, level_destination, form_destination, save_data, parent_level, js, table_name, order_by) FROM stdin;
213	selgroup_layer	selgroup_layer	4	28	\N	selgroup_layer	27	\N	\N	\N
214	selgroup_layer	selgroup_layer	5	28	\N	selgroup_layer	27	\N	\N	\N
16	user	user	0	4	\N	user	2	\N	user	\N
2	progetto	project	0	2	\N	\N	\N	\N	\N	project_name
3	progetto	project	1	2		\N	\N	\N	\N	\N
5	mapset	mapset	0	8	\N	\N	\N	\N	\N	title
6	progetto	project	2	2		project	\N	\N	\N	\N
7	progetto	project	1	2	\N	project	\N	\N	\N	\N
8	temi	theme	0	5	\N	\N	\N	\N	\N	theme_order,theme_title
9	temi	theme	1	5	\N	\N	\N	\N	\N	\N
10	temi	theme	1	5	\N	\N	2	\N	\N	\N
11	temi	theme	2	5	\N	\N	2	\N	\N	\N
12	project_srs	project_srs	0	6	\N	\N	2	\N	\N	\N
13	project_srs	project_srs	1	6	\N	\N	2	\N	\N	\N
14	project_srs	project_srs	2	6	\N	\N	2	\N	\N	\N
23	group	group	50	3	\N	group	2	\N	group	\N
26	mapset	mapset	1	8		\N	2	\N	\N	\N
27	mapset	mapset	1	8	\N	mapset	2	\N	\N	\N
28	mapset	mapset	2	2	\N	mapset	2	\N	\N	\N
34	layer	layer	0	11	\N	\N	10	\N	\N	layer_order,layer_name
35	layer	layer	1	11	\N	layer	10	\N	\N	\N
36	layer	layer	1	11	\N	layer	10	\N	\N	\N
37	layer	layer	2	11	\N	layer	10	\N	\N	\N
38	classi	class	0	12	\N	\N	11	\N	\N	class_order
39	classi	class	1	12	\N	\N	11	\N	\N	\N
40	classi	class	1	12	\N	class	11	\N	\N	\N
41	classi	class	2	12	\N	class	11	\N	\N	\N
42	stili	style	0	14	\N	\N	12	\N	\N	style_order
43	stili	style	1	14	\N	\N	12	\N	\N	\N
44	stili	style	1	14	\N	style	12	\N	\N	\N
45	stili	style	2	14	\N	style	12	\N	\N	\N
50	catalog	catalog	0	7	\N	\N	2	\N	\N	catalog_name
51	catalog	catalog	1	7	\N	\N	2	\N	\N	\N
52	catalog	catalog	1	7	\N	catalog	2	\N	\N	\N
53	catalog	catalog	2	7	\N	catalog	2	\N	\N	\N
70	links	link	0	9		\N	2	\N	\N	link_order,link_name
72	links	link	1	9		\N	2	\N	\N	\N
73	links	link	1	9		\N	2	\N	\N	\N
74	links	link	2	9		\N	2	\N	\N	\N
105	selgroup	selgroup	0	27	\N	\N	2	\N	\N	\N
106	selgroup	selgroup	1	27	\N	\N	2	\N	\N	\N
107	selgroup	selgroup	1	27	\N	\N	2	\N	\N	\N
133	project_admin	admin_project	2	33	\N	\N	2	\N	\N	\N
134	project_admin	admin_project	5	33	\N	admin_project	6	\N	\N	\N
151	user_groups	user_groups	4	46	\N	user_groups	4	\N	\N	\N
152	user_groups	user_groups	5	46	\N	user_groups	4	\N	\N	\N
75	relation	relation_addnew	0	16	\N	\N	13	\N	\N	\N
30	layergroup	layergroup	0	10	\N	layergroup	5	\N	\N	layergroup_order,layergroup_title
31	layergroup	layergroup	1	10	\N	layergroup	5	\N	\N	\N
32	layergroup	layergroup	1	10	\N	layergroup	5	\N	\N	\N
33	layergroup	layergroup	2	10	\N	layergroup	5	\N	\N	\N
84	map_layer	mapset_layergroup	4	22	\N	mapset_layergroup	8	\N	\N	\N
85	map_layer	mapset_layergroup	5	22	\N	mapset_layergroup	8	\N	\N	\N
86	map_layer	mapset_layergroup	0	22	\N	mapset_layergroup	8	\N	\N	\N
170	layer_groups	layer_groups	4	47	\N	layer_groups	11	\N	\N	\N
171	layer_groups	layer_groups	5	47	\N	layer_groups	11	\N	\N	\N
202	project_languages	project_languages	0	48	\N	\N	2	\N	\N	\N
203	project_languages	project_languages	1	48	\N	\N	2	\N	\N	\N
204	authfilter	authfilter	0	49	\N	\N	2	\N	\N	\N
205	authfilter	authfilter	1	49	\N	\N	2	\N	\N	\N
206	layer_authfilter	layer_authfilter	4	50	\N	layer_authfilter	11	\N	\N	\N
207	layer_authfilter	layer_authfilter	5	50	\N	layer_authfilter	11	\N	\N	\N
208	group_authfilter	group_authfilter	0	51	\N	\N	3	\N	\N	\N
209	group_authfilter	group_authfilter	1	51	\N	\N	3	\N	\N	\N
20	group	group	0	3	\N	group	2	\N	group	\N
18	user	user	50	4	\N	user	2	\N	user	\N
58	relation	relation	0	16	\N	\N	11	\N	\N	\N
59	relation	relation	1	16	\N	\N	11	\N	\N	\N
60	relation	relation	1	16	\N	\N	11	\N	\N	\N
61	relation	relation	2	16	\N	\N	11	\N	\N	\N
63	fields	field	1	17	\N	\N	11	\N	\N	\N
64	fields	field	1	17	\N	\N	11	\N	\N	\N
65	fields	field	2	17	\N	\N	11	\N	\N	\N
62	fields	field	0	17	\N	\N	11	\N	\N	relationtype_id,relation_name,field_header,field_name
210	field_groups	field_groups	4	52	\N	field_groups	17	\N	\N	\N
211	field_groups	field_groups	5	52	\N	field_groups	17	\N	\N	\N
212	field_groups	field_groups	0	52	\N	field_groups	17	\N	\N	\N
66	layer_link	layer_link	2	19	\N	\N	11	\N	\N	\N
69	layer_link	layer_link	110	19	\N	\N	11	\N	\N	\N
68	layer_link	layer_link	1	19	\N	\N	11	\N	\N	\N
67	layer_link	layer_link	0	19	\N	\N	11	\N	\N	\N
215	mapset_groups	mapset_groups	4	53	\N	mapset_groups	8	\N	\N	\N
216	mapset_groups	mapset_groups	5	53	\N	mapset_groups	8	\N	\N	\N
\.


--
-- Data for Name: e_formula; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_formula (formula_id, formula_name, formula_format, formula_order) FROM stdin;
12	Date (YYYY-MM-DD)	to_char({{field_name}}, 'YYYY-MM-DD')	120
1	0 decimal places	to_char({{field_name}}, 'FM3263299990')	10
2	1 decimal places	to_char({{field_name}}, 'FM3263299990.0')	20
3	2 decimal places	to_char({{field_name}}, 'FM3263299990.00')	30
4	3 decimal places	to_char({{field_name}}, 'FM3263299990.000')	40
5	0 decimals with thousands separator	to_char({{field_name}}, 'FM9,999,999,990')	50
6	1 decimals with thousands separator	to_char({{field_name}}, 'FM9,999,999,990.0')	60
7	2 decimals with thousands separator	to_char({{field_name}}, 'FM9,999,999,990.00')	70
8	3 decimals with thousands separator	to_char({{field_name}}, 'FM9,999,999,990.000')	80
9	Date (GG/MM/YYYY)	to_char({{field_name}}, 'DD/MM/YYYY')	110
10	Date (GG.MM.YYYY)	to_char({{field_name}}, 'DD.MM.YYYY')	130
11	Currency (€)	to_char({{field_name}}, 'FM€ 3263299990.00')	210
\.


--
-- Data for Name: e_language; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_language (language_id, language_name, language_order) FROM stdin;
en	English	1
fr	Francais	2
de	Deutsch	3
es	Espanol	4
it	Italiano	5
ru	русский (Russian)	6
ua	український (Ukrainian)	7
zh	正體中文 (Chinese [traditional])	8
hu	Magyar (Hungarian)	9
he	יהודי (Jewish)	10
el	Ελληνικά (Greek)	11
\.


--
-- Data for Name: e_layertype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_layertype (layertype_id, layertype_name, layertype_ms, layertype_order) FROM stdin;
5	annotation	4	\N
1	point	0	\N
2	line	1	\N
3	polygon	2	\N
4	raster	3	\N
10	tileraster	100	\N
11	chart	8	\N
\.


--
-- Data for Name: e_lblposition; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_lblposition (lblposition_id, lblposition_name, lblposition_order) FROM stdin;
1	UL	\N
2	UC	\N
3	UR	\N
4	CL	\N
5	CC	\N
6	CR	\N
7	LL	\N
8	LC	\N
9	LR	\N
10	AUTO	\N
\.


--
-- Data for Name: e_legendtype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_legendtype (legendtype_id, legendtype_name, legendtype_order) FROM stdin;
1	auto	1
0	nessuna	2
\.


--
-- Data for Name: e_level; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_level (id, name, parent_name, "order", parent_id, depth, leaf, export, struct_parent_id, "table", admintype_id) FROM stdin;
1	root	\N	1	\N	\N	0	0	\N	\N	2
2	project	project	2	1	0	0	1	1	project	2
3	groups	groups	7	1	0	0	0	1	groups	1
4	users	users	6	1	0	0	0	1	users	1
5	theme	theme	3	2	1	0	5	2	theme	2
6	project_srs	project_srs	4	2	1	1	1	2	project_srs	2
7	catalog	catalog	13	2	1	1	2	2	catalog	2
8	mapset	mapset	15	2	1	0	6	2	mapset	2
9	link	link	15	2	1	1	4	2	link	2
10	layergroup	layergroup	4	5	2	0	1	5	layergroup	2
11	layer	layer	5	10	3	0	1	10	layer	2
12	class	class	6	11	4	0	1	11	class	2
14	style	style	7	12	5	1	1	12	style	2
22	mapset_layergroup	mapset_layergroup	17	8	2	1	1	8	mapset_layergroup	2
27	selgroup	selgroup	\N	2	1	0	8	2	selgroup	2
33	project_admin	project_admin	15	2	1	1	0	2	project_admin	2
45	group_users	user_groups	\N	4	2	1	0	4	user_group	1
46	user_groups	group_users	\N	3	2	1	0	3	user_group	1
32	user_project	project	8	2	1	1	0	2	user_project	2
47	layer_groups	layer_groups	\N	11	4	1	0	11	layer_groups	2
48	project_languages	project	\N	2	1	1	1	2	project_languages	2
49	authfilter	authfilter	8	1	0	1	0	1	authfilter	2
51	group_authfilter	groups	1	3	1	1	0	3	group_authfilter	2
28	selgroup_layer	selgroup_layer	\N	27	2	1	1	27	selgroup_layer	2
16	relation	relation	10	11	4	1	1	11	relation	2
17	field	field	11	11	4	1	2	11	field	2
52	field_groups	field	1	17	5	1	0	17	field_groups	2
50	layer_authfilter	layer	15	11	4	1	0	11	layer_authfilter	2
19	layer_link	layer	12	11	4	1	0	11	layer_link	2
53	mapset_groups	mapset	20	8	2	1	1	8	mapset_groups	2
\.


--
-- Data for Name: e_orderby; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_orderby (orderby_id, orderby_name, orderby_order) FROM stdin;
0	Nessuno	\N
1	Crescente	\N
2	Decresente	\N
\.


--
-- Data for Name: e_outputformat; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_outputformat (outputformat_id, outputformat_name, outputformat_driver, outputformat_mimetype, outputformat_imagemode, outputformat_extension, outputformat_option, outputformat_order) FROM stdin;
2	AGG PNG	AGG/PNG	image/png	PC256	png	\N	\N
4	PNG 8 bit	GD/PNG	image/png	PC256	png	\N	\N
5	PNG 24 bit	GD/PNG	image/png	RGB	png	\N	\N
6	PNG 32 bit Trasp	GD/PNG	image/png	RGBA	png	\N	\N
7	AGG Q	AGG/PNG	image/png; mode=8bit	RGB	png	    FORMATOPTION "QUANTIZE_FORCE=ON"\n    FORMATOPTION "QUANTIZE_DITHER=OFF"\n    FORMATOPTION "QUANTIZE_COLORS=256"	\N
1	AGG PNG 24 bit	AGG/PNG	image/png; mode=24bit	RGB	png	\N	\N
3	AGG JPG	AGG/JPG	jpeg	RGB	jpg	\N	\N
9	AGG PNG	AGG/PNG	image/png	RGB	png	    FORMATOPTION "QUANTIZE_FORCE=ON"\nFORMATOPTION "QUANTIZE_DITHER=OFF"\nFORMATOPTION "QUANTIZE_COLORS=256"	\N
10	GEOJSON	OGR/GEOJSON	application/json; subtype=geojson	JSON	json	FORMATOPTION "STORAGE=stream" FORMATOPTION "FORM=SIMPLE"	\N
\.


--
-- Data for Name: e_owstype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_owstype (owstype_id, owstype_name, owstype_order) FROM stdin;
1	WMS	1
2	WMTS	2
3	WMS (tiles in cache di mapproxy)	3
4	Yahoo	3
5	OSM	5
6	TMS	6
7	Google	4
8	Bing	6
10	WFS	4
\.


--
-- Data for Name: e_papersize; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_papersize (papersize_id, papersize_name, papersize_size, papersize_orientation, papaersize_order) FROM stdin;
1	A4 Verticale	A4	P	\N
2	A4 Orizzontale	A4	L	\N
3	A3 Verticale	A3	P	\N
4	A3 Orizzontale	A3	L	\N
5	A2 Verticale	A2	P	\N
6	A2 Orizzontale	A2	L	\N
7	A1 Verticale	A1	P	\N
8	A1 Orizzontale	A1	L	\N
9	A0 Verticale	A0	P	\N
10	A0 Orizzontale	A0	L	\N
\.


--
-- Data for Name: e_pattern; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_pattern (pattern_id, pattern_name, pattern_def, pattern_order) FROM stdin;
0	NO PATTERN	#PATTERN END	0
1	1-3	PATTERN 1 3 END	1
2	2-3	PATTERN 2 3 END	2
3	3-3	PATTERN 3 3 END	3
4	5-5	PATTERN 5 5 END	4
5	10-10	PATTERN 10 10 END	5
6	10-3	PATTERN 10 3 END	6
7	3-10	PATTERN 3 10 END	7
8	5-3-1-3	PATTERN 5 3 1 3 END	8
9	5-3-1-3-1-3	PATTERN 5 3 1 3 1 3 END	9
10	5-3-5-3-1-3	PATTERN 5 3 5 3 1 3 END	10
11	1-2-1-6	PATTERN 1 2 1 6 END	11
\.


--
-- Data for Name: e_relationtype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_relationtype (relationtype_id, relationtype_name, relationtype_order) FROM stdin;
1	Dettaglio (1 a 1)	\N
2	Secondaria (Info 1 a molti)	\N
\.


--
-- Data for Name: e_resultype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_resultype (resultype_id, resultype_name, resultype_order) FROM stdin;
1	Mostra sempre	1
4	Nascondi	2
5	Ignora	3
10	Nascondi in tabella	4
20	Nascondi in tooltip	5
30	Nascondi in scheda	6
\.


--
-- Data for Name: e_searchable; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_searchable (searchable_id, searchable_name, searchable_order) FROM stdin;
0	Non ricercabile	0
1	Visualizzato in ricerca	1
2	Solo ricerca veloce	2
\.


--
-- Data for Name: e_searchtype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_searchtype (searchtype_id, searchtype_name, searchtype_order) FROM stdin;
4	Numerico	\N
5	Data	\N
1	Testo	\N
2	Parte di testo	\N
3	Lista di valori	\N
0	Nessuno	\N
6	Lista di valori, non WFS	\N
\.


--
-- Data for Name: e_sizeunits; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_sizeunits (sizeunits_id, sizeunits_name, sizeunits_order) FROM stdin;
2	feet	\N
3	inches	\N
1	pixels	\N
4	kilometers	\N
5	meters	\N
6	miles	\N
7	dd	\N
\.


--
-- Data for Name: e_symbolcategory; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_symbolcategory (symbolcategory_id, symbolcategory_name, symbolcategory_order) FROM stdin;
1	MapServer	\N
3	Campiture	\N
4	Marker	\N
5	CatastoCML	\N
7	Numeri	\N
6	TechNET	\N
13	R3-TREES	\N
21	R3-CARTOGRAPHY	\N
91	R3-MAPSYMBOLS	\N
\.


--
-- Data for Name: e_tiletype; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_tiletype (tiletype_id, tiletype_name, tiletype_order) FROM stdin;
0	no Tiles	1
1	WMS Tiles	2
2	Tilecache Tiles	3
\.


--
-- Data for Name: e_wmsversion; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.e_wmsversion (wmsversion_id, wmsversion_name, wmsversion_order) FROM stdin;
1	1.0.0	100
2	1.1.0	110
3	1.1.1	111
4	1.3.0	130
\.


--
-- Data for Name: export_i18n; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.export_i18n (exporti18n_id, table_name, field_name, project_name, pkey_id, language_id, value, original_value) FROM stdin;
\.


--
-- Data for Name: field; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.field (field_id, relation_id, field_name, field_header, fieldtype_id, searchtype_id, resultype_id, field_format, column_width, orderby_id, field_filter, datatype_id, field_order, default_op, layer_id, editable, formula, lookup_table, lookup_id, lookup_name, filter_field_name, mandatory) FROM stdin;
59	0	edifc_id	Identificatore edificio	1	1	1	\N	\N	0	-1	1	4	\N	\N	0	\N	\N	\N	\N	\N	0
60	0	edifc_cass	Cassone d'appartenenza	1	1	1	\N	\N	0	-1	1	5	\N	\N	0	\N	\N	\N	\N	\N	0
61	0	edifc_ty	Tipologia edilizia	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
62	0	edifc_uso	Categoria uso	1	3	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
63	0	edifc_stat	Stato	1	3	1	\N	\N	0	-1	2	3	\N	\N	0	\N	\N	\N	\N	\N	0
64	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
65	0	sc_dis_ty	Tipo area	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
66	0	feature_id	Identificativo	1	4	1	\N	\N	0	-1	2	0	\N	\N	0	\N	\N	\N	\N	\N	0
67	0	a_tras_ty	Tipo	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
68	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
69	0	f_nter_ty	Tipo	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
70	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
71	0	alveo_reg	Regime	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
72	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
73	0	bosco_essz	Essenze	1	3	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
74	0	bosco_ty	Tipo	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
75	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
76	0	ps_inc_ty	Tipo	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
77	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
78	0	cl_agr_ty	Tipo	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
79	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
80	0	ar_vrd_pr	Posizione relativa	1	4	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
81	0	ar_vrd_ty	Tipo	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
82	0	feature_id	Identificativo	1	4	1	\N	\N	0	-1	2	0	\N	\N	0	\N	\N	\N	\N	\N	0
83	0	for_pc_ty	Tipo	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
84	0	feature_id	Identificativo	1	4	1	\N	\N	0	-1	2	0	\N	\N	0	\N	\N	\N	\N	\N	0
85	0	el_fer_pos	Posizione	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
86	0	el_fer_sta	Stato	1	3	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
87	0	el_fer_tav	Alta velocità	1	3	1	\N	\N	0	-1	2	3	\N	\N	0	\N	\N	\N	\N	\N	0
88	0	el_fer_tra	Tipo trazione	1	3	1	\N	\N	0	-1	2	4	\N	\N	0	\N	\N	\N	\N	\N	0
89	0	el_fer_ele	Elettrificazione	1	3	1	\N	\N	0	-1	2	5	\N	\N	0	\N	\N	\N	\N	\N	0
90	0	el_fer_sca	Scartamento	1	3	1	\N	\N	0	-1	2	6	\N	\N	0	\N	\N	\N	\N	\N	0
91	0	el_fer_sed	Sede	1	3	1	\N	\N	0	-1	2	7	\N	\N	0	\N	\N	\N	\N	\N	0
92	0	el_fer_liv	Livello	1	3	1	\N	\N	0	-1	2	8	\N	\N	0	\N	\N	\N	\N	\N	0
93	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
94	0	ac_vei_pr	Posizione relativa	1	4	1	\N	\N	0	-1	2	4	\N	\N	0	\N	\N	\N	\N	\N	0
95	0	ac_vei_zon	Zona	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
96	0	ac_vei_fon	Fondo	1	3	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
97	0	ac_vei_sed	Sede	1	3	1	\N	\N	0	-1	2	3	\N	\N	0	\N	\N	\N	\N	\N	0
98	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
99	0	ac_ped_pr	Posizione relativa	1	4	1	\N	\N	0	-1	2	5	\N	\N	0	\N	\N	\N	\N	\N	0
101	0	ac_ped_pos	Posizione	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
102	0	ac_ped_zon	Zona	1	3	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
103	0	ac_ped_fon	Fondo	1	3	1	\N	\N	0	-1	2	3	\N	\N	0	\N	\N	\N	\N	\N	0
104	0	ac_ped_sed	Sede	1	3	1	\N	\N	0	-1	2	4	\N	\N	0	\N	\N	\N	\N	\N	0
105	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
106	0	ac_cic_pr	Posizione relativa	1	4	1	\N	\N	0	-1	2	4	\N	\N	0	\N	\N	\N	\N	\N	0
107	0	ac_cic_pos	Posizione	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
108	0	ac_cic_fon	Fondo	1	3	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
109	0	ac_cic_sed	Sede	1	3	1	\N	\N	0	-1	2	3	\N	\N	0	\N	\N	\N	\N	\N	0
110	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
111	0	ar_vms_pr	Posizione relativa	1	4	1	\N	\N	0	-1	2	3	\N	\N	0	\N	\N	\N	\N	\N	0
112	0	ar_vms_ty	Tipo	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
113	0	ar_vms_sed	Sede	1	3	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
114	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
115	0	sd_fer_pr	Posizione relativa	1	4	1	\N	\N	0	-1	2	4	\N	\N	0	\N	\N	\N	\N	\N	0
116	0	sd_fer_ty	Tipo di trasporto su ferro	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
117	0	sd_fer_fon	Fondo	1	3	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
118	0	sd_fer_sed	Sede	1	3	1	\N	\N	0	-1	2	3	\N	\N	0	\N	\N	\N	\N	\N	0
119	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
120	0	ar_str_pr	Posizione relativa	1	4	1	\N	\N	0	-1	2	4	\N	\N	0	\N	\N	\N	\N	\N	0
121	0	ar_str_cf	Classifica funzionale	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
122	0	ar_str_sta	Stato	1	3	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
123	0	ar_str_sed	Sede	1	3	1	\N	\N	0	-1	2	3	\N	\N	0	\N	\N	\N	\N	\N	0
126	0	num_civic	Numero civico	1	2	1	\N	25	1	-1	1	3	\N	\N	0	\N	\N	\N	\N	\N	0
127	0	ab_cda_pr	Posizione relativa	1	4	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
128	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
129	0	ab_cda_sed	Sede	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
40	0	disegno	Rappresentabilità disegno	1	3	1	\N	\N	0	-1	2	3	\N	\N	0	\N	\N	\N	\N	\N	0
41	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
42	0	mn_edi_ty	Tipo	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
43	0	disegno	Rappresentabilità disegno	1	3	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
15	0	comune_nom	Nome Comune	1	3	1	\N	\N	1	-1	1	1	\N	\N	0	\N	\N	\N	\N	\N	0
16	0	comune_ist	Codice istat comune	1	3	1	\N	\N	1	-1	1	2	\N	\N	0	\N	\N	\N	\N	\N	0
17	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
18	0	sp_acq_nom	Nome specchio d'acqua	1	2	1	\N	\N	1	-1	1	2	\N	\N	0	\N	\N	\N	\N	\N	0
19	0	sp_acq_ty	Tipo specchio d'acqua	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
20	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
21	0	invaso_no	Nome invaso	1	2	1	\N	\N	0	-1	1	1	\N	\N	0	\N	\N	\N	\N	\N	0
22	0	invaso_ty	Tipo invaso	1	3	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
23	0	ab_cda_pr	Posizione relativa	1	4	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
24	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
25	0	ab_cda_sed	Sede	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
26	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
27	0	tralic_ty	Tipo	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
28	0	tralic_imp	Impianto	1	3	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
29	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
30	0	man_tr_pr	Posizione relativa	1	4	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
31	0	man_tr_ty	Tipo	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
32	0	disegno	Rappresentabilità disegno	1	3	1	\N	\N	0	-1	2	3	\N	\N	0	\N	\N	\N	\N	\N	0
33	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
34	0	attr_sp_pr	Posizione relativa	1	4	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
35	0	attr_sp_ty	Tipo	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
36	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
37	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
38	0	mn_mau_pr	Posizione relativa	1	4	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
39	0	mn_mau_ty	Tipo	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
44	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
45	0	par_ar_ty	Tipo particolare	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
46	0	disegno	Rappresentabilità disegno	1	3	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
47	0	id_edif	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
48	0	un_vol_av	Altezza volume	1	4	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
49	0	id_edif	Identificativo edificio	1	1	1	\N	0	0	-1	1	3	\N	\N	0	\N	\N	\N	\N	\N	0
50	0	un_vol_por	Tipo porzione	1	3	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
51	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
52	0	edi_min_id	Identificativo edificio	1	1	1	\N	\N	0	-1	1	4	\N	\N	0	\N	\N	\N	\N	\N	0
53	0	edi_min_ty	Tipologia edilizia	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
54	0	edi_min_pr	Struttura precaria	1	3	1	\N	\N	0	-1	2	2	\N	\N	0	\N	\N	\N	\N	\N	0
55	0	edi_min_st	Stato	1	3	1	\N	\N	0	-1	2	3	\N	\N	0	\N	\N	\N	\N	\N	0
56	0	feature_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
57	0	aatt_ty	Tipo	1	3	1	\N	\N	0	-1	2	1	\N	\N	0	\N	\N	\N	\N	\N	0
58	0	edifc_id	Identificativo	1	1	1	\N	\N	0	-1	1	0	\N	\N	0	\N	\N	\N	\N	\N	0
1	0	gid	gid	1	1	4	\N	\N	0	0	2	0	\N	1	0	\N	\N	\N	\N	\N	0
2	0	code	code	1	1	4	\N	\N	0	0	2	0	\N	1	0	\N	\N	\N	\N	\N	0
3	0	fclass	fclass	1	1	4	\N	\N	0	0	1	0	\N	1	0	\N	\N	\N	\N	\N	0
4	0	name	name	1	1	4	\N	\N	0	0	1	0	\N	1	0	\N	\N	\N	\N	\N	0
5	0	type	type	1	1	4	\N	\N	0	0	1	0	\N	1	0	\N	\N	\N	\N	\N	0
1364	0	di_name_1	Nome località	1	6	1	\N	\N	0	0	1	2	\N	\N	0	\N	\N	\N	\N	\N	0
950	0	gid	gid	1	1	4	\N	\N	0	0	2	0	\N	20	0	\N	\N	\N	\N	\N	0
951	0	name	name	1	1	4	\N	\N	0	0	1	0	\N	20	0	\N	\N	\N	\N	\N	0
952	0	admin_lv	admin_lv	1	1	4	\N	\N	0	0	2	0	\N	20	0	\N	\N	\N	\N	\N	0
953	0	gid	gid	1	1	4	\N	\N	0	0	2	0	\N	21	0	\N	\N	\N	\N	\N	0
954	0	code	code	1	1	4	\N	\N	0	0	2	0	\N	21	0	\N	\N	\N	\N	\N	0
955	0	fclass	fclass	1	1	4	\N	\N	0	0	1	0	\N	21	0	\N	\N	\N	\N	\N	0
956	0	name	name	1	1	4	\N	\N	0	0	1	0	\N	21	0	\N	\N	\N	\N	\N	0
957	0	gid	gid	1	1	4	\N	\N	0	0	2	0	\N	23	0	\N	\N	\N	\N	\N	0
958	0	code	code	1	1	4	\N	\N	0	0	2	0	\N	23	0	\N	\N	\N	\N	\N	0
959	0	fclass	fclass	1	1	4	\N	\N	0	0	1	0	\N	23	0	\N	\N	\N	\N	\N	0
960	0	name	name	1	1	4	\N	\N	0	0	1	0	\N	23	0	\N	\N	\N	\N	\N	0
961	0	layer	layer	1	1	4	\N	\N	0	0	2	0	\N	23	0	\N	\N	\N	\N	\N	0
962	0	bridge	bridge	1	1	4	\N	\N	0	0	1	0	\N	23	0	\N	\N	\N	\N	\N	0
963	0	tunnel	tunnel	1	1	4	\N	\N	0	0	1	0	\N	23	0	\N	\N	\N	\N	\N	0
964	0	gid	gid	1	1	4	\N	\N	0	0	2	0	\N	24	0	\N	\N	\N	\N	\N	0
965	0	code	code	1	1	4	\N	\N	0	0	2	0	\N	24	0	\N	\N	\N	\N	\N	0
966	0	fclass	fclass	1	1	4	\N	\N	0	0	1	0	\N	24	0	\N	\N	\N	\N	\N	0
967	0	width	width	1	1	4	\N	\N	0	0	2	0	\N	24	0	\N	\N	\N	\N	\N	0
968	0	name	name	1	1	4	\N	\N	0	0	1	0	\N	24	0	\N	\N	\N	\N	\N	0
969	0	gid	gid	1	1	4	\N	\N	0	0	2	0	\N	26	0	\N	\N	\N	\N	\N	0
970	0	code	code	1	1	4	\N	\N	0	0	2	0	\N	26	0	\N	\N	\N	\N	\N	0
971	0	fclass	fclass	1	1	4	\N	\N	0	0	1	0	\N	26	0	\N	\N	\N	\N	\N	0
972	0	name	name	1	1	4	\N	\N	0	0	1	0	\N	26	0	\N	\N	\N	\N	\N	0
989	0	gid	gid	1	1	4	\N	\N	0	0	2	0	\N	31	0	\N	\N	\N	\N	\N	0
990	0	code	code	1	1	4	\N	\N	0	0	2	0	\N	31	0	\N	\N	\N	\N	\N	0
991	0	fclass	fclass	1	1	4	\N	\N	0	0	1	0	\N	31	0	\N	\N	\N	\N	\N	0
992	0	name	name	1	1	4	\N	\N	0	0	1	0	\N	31	0	\N	\N	\N	\N	\N	0
993	0	gid	gid	1	1	4	\N	\N	0	0	2	0	\N	32	0	\N	\N	\N	\N	\N	0
994	0	code	code	1	1	4	\N	\N	0	0	2	0	\N	32	0	\N	\N	\N	\N	\N	0
995	0	fclass	fclass	1	1	4	\N	\N	0	0	1	0	\N	32	0	\N	\N	\N	\N	\N	0
996	0	name	name	1	1	4	\N	\N	0	0	1	0	\N	32	0	\N	\N	\N	\N	\N	0
997	0	gid	gid	1	1	4	\N	\N	0	0	2	0	\N	33	0	\N	\N	\N	\N	\N	0
998	0	code	code	1	1	4	\N	\N	0	0	2	0	\N	33	0	\N	\N	\N	\N	\N	0
999	0	fclass	fclass	1	1	4	\N	\N	0	0	1	0	\N	33	0	\N	\N	\N	\N	\N	0
1000	0	name	name	1	1	4	\N	\N	0	0	1	0	\N	33	0	\N	\N	\N	\N	\N	0
\.


--
-- Data for Name: field_groups; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.field_groups (field_id, groupname, editable) FROM stdin;
\.


--
-- Data for Name: font; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.font (font_name, file_name) FROM stdin;
verdana	verdana.ttf
verdana-bold	verdanab.ttf
verdana-italic	verdanai.ttf
verdana-bold-italic	verdanaz.ttf
\.


--
-- Data for Name: form_level; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.form_level (id, level, mode, form, order_fld, visible) FROM stdin;
520	27	3	213	1	1
521	28	1	214	1	1
1	1	3	2	1	1
2	2	0	3	1	1
5	2	3	5	8	1
7	2	1	7	1	1
8	2	2	6	1	1
14	2	3	12	3	1
15	6	1	13	1	1
16	6	2	13	1	1
17	6	0	13	1	1
19	8	0	26	1	1
20	8	1	27	1	1
21	8	2	28	1	1
22	5	0	9	1	1
23	5	1	10	1	1
24	5	2	11	1	1
25	5	3	30	3	1
26	10	0	31	1	1
27	10	1	32	1	1
28	10	2	33	1	1
29	10	3	34	3	1
30	11	0	35	1	1
31	11	1	36	1	1
32	11	2	37	1	1
34	12	0	39	1	1
35	12	1	40	1	1
36	12	2	41	2	1
37	12	3	42	3	1
38	14	0	43	1	1
39	14	1	44	1	1
40	14	2	45	1	1
46	7	0	51	1	1
47	7	1	52	1	1
48	7	2	53	1	1
54	16	0	59	1	1
55	16	1	60	1	1
56	16	2	61	1	1
57	17	0	63	1	1
58	17	1	64	1	1
59	17	2	65	1	1
63	2	3	70	7	1
64	9	0	72	1	1
65	9	1	73	1	1
66	9	2	74	1	1
77	8	3	84	6	1
78	22	1	85	1	1
98	2	3	105	6	1
99	27	1	106	1	1
101	27	0	107	1	1
127	33	1	134	15	1
131	2	3	133	15	1
132	27	2	106	1	1
164	1	3	16	3	1
165	4	0	18	1	1
166	4	1	18	1	1
167	4	2	18	1	1
168	1	3	20	2	1
169	3	0	23	1	1
170	3	1	23	1	1
171	3	2	23	1	1
176	46	1	152	1	1
79	22	-1	86	2	1
69	16	1	75	2	0
100	27	2	105	2	0
33	11	3	38	3	1
51	11	3	58	4	1
52	11	3	62	5	1
200	11	0	170	7	1
201	47	1	171	1	1
202	47	3	171	1	1
203	47	2	171	1	1
504	48	0	203	1	1
505	48	1	203	1	1
506	48	2	203	1	1
507	2	3	202	1	1
508	49	0	205	1	1
509	49	1	205	1	1
510	49	2	205	1	1
513	50	1	207	1	1
515	51	0	209	1	1
516	51	1	209	1	1
517	51	2	209	1	1
518	17	0	210	1	1
519	52	1	211	1	1
53	11	3	66	6	1
60	19	0	67	1	1
61	19	1	68	1	1
62	19	1	69	2	1
175	4	3	151	2	1
163	27	3	151	1	0
511	1	3	204	4	0
512	11	3	206	8	0
514	3	3	208	3	0
4	2	3	8	4	1
45	2	3	50	5	1
522	8	0	215	10	0
523	53	1	216	1	1
\.


--
-- Data for Name: group_authfilter; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.group_authfilter (groupname, filter_id, filter_expression) FROM stdin;
\.


--
-- Data for Name: groups; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.groups (groupname, description) FROM stdin;
\.


--
-- Data for Name: i18n_field; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.i18n_field (i18nf_id, table_name, field_name) FROM stdin;
1	class	class_title
2	class	expression
3	class	label_def
4	class	class_text
5	layer	layer_title
6	layer	data_filter
7	layer	layer_def
8	layer	metadata
9	layer	labelitem
10	layer	classitem
11	layergroup	layergroup_title
12	layergroup	sld
15	style	style_def
16	theme	theme_title
17	theme	copyright_string
18	mapset	mapset_title
19	mapset	mapset_description
14	field	field_header
13	field	field_name
20	layer	template
21	layer	header
22	layer	footer
\.


--
-- Data for Name: layer; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.layer (layer_id, layergroup_id, layer_name, layertype_id, catalog_id, data, data_geom, data_unique, data_srid, data_filter, classitem, labelitem, labelsizeitem, labelminscale, labelmaxscale, maxscale, minscale, symbolscale, opacity, maxfeatures, sizeunits_id, layer_def, metadata, template, header, footer, tolerance, layer_order, queryable, layer_title, zoom_buffer, group_object, selection_color, papersize_id, toleranceunits_id, selection_width, selection_info, hidden, private, postlabelcache, maxvectfeatures, data_type, last_update, data_extent, searchable_id, hide_vector_geom) FROM stdin;
20	34	boundaries	3	1	adm_bound_osm	the_geom	gid	32632	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	\N	\N	\N	\N	\N	\N	0	0	boundaries	\N	\N	\N	\N	1	\N	1	0	0	0	\N	multipolygon	\N	\N	0	0
1	1	buildings	3	1	edifici_osm	the_geom	gid	32632	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	\N	\N	\N	\N	\N	\N	0	0	buildings	\N	\N	\N	\N	1	\N	1	0	0	0	\N	multipolygon	\N	\N	0	0
23	90	railroads	2	1	railroad_osm	the_geom	gid	32632	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	\N	\N	\N	\N	\N	\N	0	0	railroads	\N	\N	\N	\N	1	\N	1	0	0	0	\N	multilinestring	\N	\N	0	0
22	89	roads	2	1	strade_osm	the_geom	gid	32632	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	\N	\N	\N	\N	\N	\N	0	0	roads	\N	\N	\N	\N	1	\N	1	0	0	0	\N	multilinestring	\N	\N	0	0
32	93	village_suburb	1	1	place_osm	the_geom	gid	32632	code = 1003 OR code = 1010	\N	name	\N	\N	\N	40000	\N	\N	\N	\N	1	\N	\N	\N	\N	\N	\N	300	0	Village and suburb	\N	\N	\N	\N	1	\N	1	0	0	0	\N	point	\N	\N	0	0
26	93	city	1	1	place_osm	the_geom	gid	32632	code = 1001	\N	name	\N	\N	\N	500000	\N	\N	\N	\N	1	\N	\N	\N	\N	\N	\N	100	0	City	\N	\N	\N	\N	1	\N	1	0	0	0	\N	point	\N	\N	0	0
31	93	town	1	1	place_osm	the_geom	gid	32632	code = 1002	\N	name	\N	\N	\N	100000	\N	\N	\N	\N	1	\N	\N	\N	\N	\N	\N	200	0	Town	\N	\N	\N	\N	1	\N	1	0	0	0	\N	point	\N	\N	0	0
33	93	hamlet_locality	1	1	place_osm	the_geom	gid	32632	code = 1004 OR code = 1050	\N	name	\N	\N	\N	10000	\N	\N	\N	\N	1	\N	\N	\N	\N	\N	\N	400	0	Hamlet and locality	\N	\N	\N	\N	1	\N	1	0	0	0	\N	point	\N	\N	0	0
24	66	line	2	1	idro_line_osm	the_geom	gid	32632	\N	\N	\N	\N	\N	\N	501000	\N	\N	\N	\N	1	\N	\N	\N	\N	\N	\N	200	0	line	\N	\N	\N	\N	1	\N	1	0	0	0	\N	multilinestring	\N	\N	0	0
21	66	poly	3	1	idro_poli_osm	the_geom	gid	32632	\N	\N	\N	\N	\N	\N	\N	\N	\N	75	\N	1	\N	\N	\N	\N	\N	\N	100	0	poly	\N	\N	\N	\N	1	\N	1	0	0	0	\N	multipolygon	\N	\N	0	0
\.


--
-- Data for Name: layer_authfilter; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.layer_authfilter (layer_id, filter_id, required) FROM stdin;
\.


--
-- Data for Name: layer_groups; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.layer_groups (layer_id, groupname, wms, wfs, wfst, layer_name, layer_groups_id) FROM stdin;
\.


--
-- Data for Name: layer_link; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.layer_link (layer_id, link_id, resultype_id) FROM stdin;
\.


--
-- Data for Name: layergroup; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.layergroup (layergroup_id, theme_id, layergroup_name, layergroup_title, layergroup_maxscale, layergroup_minscale, layergroup_smbscale, layergroup_order, locked, multi, hidden, isbaselayer, tiletype_id, sld, style, url, owstype_id, outputformat_id, layers, parameters, gutter, transition, tree_group, layergroup_description, buffer, tiles_extent, tiles_extent_srid, layergroup_single, metadata_url, opacity, tile_origin, tile_resolutions, tile_matrix_set, wmsversion_id) FROM stdin;
34	19	boundaries	Boundaries	\N	\N	\N	200	0	0	0	0	1	\N	\N	\N	1	7	\N	\N	0	0	\N	\N	\N	\N	\N	0	\N	100	\N	\N	\N	\N
93	19	places	Places	501000	\N	\N	100	0	0	0	0	0	\N	\N	\N	1	7	\N	\N	0	0	\N	\N	\N	\N	\N	0	\N	100	\N	\N	\N	\N
89	22	roads	Roads	\N	\N	\N	\N	0	0	0	0	1	\N	\N	\N	1	7	\N	\N	0	0	\N	\N	\N	\N	\N	0	\N	100	\N	\N	\N	\N
1	1	buildings	Buildings	300000	\N	\N	\N	0	0	0	0	1	\N	\N	\N	1	7	\N	\N	0	0	\N	\N	\N	\N	\N	0	\N	100	\N	\N	\N	\N
90	27	railroads	Railroads	101000	\N	\N	\N	0	0	0	0	1	\N	\N	\N	1	7	\N	\N	0	0	\N	\N	\N	\N	\N	0	\N	100	\N	\N	\N	\N
66	21	hydro	Hydro	\N	\N	\N	\N	0	0	0	0	1	\N	\N	\N	1	7	\N	\N	0	0	\N	\N	\N	\N	\N	0	\N	100	\N	\N	\N	\N
\.


--
-- Data for Name: link; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.link (link_id, project_name, link_name, link_def, link_order, winw, winh) FROM stdin;
\.


--
-- Data for Name: localization; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.localization (localization_id, project_name, i18nf_id, pkey_id, language_id, value) FROM stdin;
\.


--
-- Data for Name: logs; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.logs (log_id, log_user, log_time, log_action, log_info) FROM stdin;
\.


--
-- Data for Name: mapset; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.mapset (mapset_name, project_name, mapset_title, mapset_description, template, mapset_extent, page_size, filter_data, dl_image_res, imagelabel, bg_color, refmap_extent, test_extent, mapset_srid, mapset_def, mapset_group, sizeunits_id, static_reference, metadata, mapset_note, mask, maxscale, minscale, mapset_scales, displayprojection, private, mapset_scale_type, mapset_order, mapset_tiles, open_counter, geolocator) FROM stdin;
default	default	Default mapset	\N	\N	-245758 4561502 1247938 5483690	\N	\N	\N	1	255 255 255	-245758 4561502 1247938 5483690	\N	32632	\N	\N	5	0	\N	\N	\N	50000	\N	1500000,1000000,500000,250000,100000,50000,25000,10000,5000,2000,1000,500,250,100,50	32632	0	0	0	0	310	\N
\.


--
-- Data for Name: mapset_groups; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.mapset_groups (mapset_name, groupname, edit) FROM stdin;
\.


--
-- Data for Name: mapset_layergroup; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.mapset_layergroup (mapset_name, layergroup_id, status, refmap, hide) FROM stdin;
default	34	1	1	0
default	93	1	0	0
default	1	1	0	0
default	66	1	1	0
default	90	1	0	0
default	89	1	0	0
\.


--
-- Data for Name: project; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.project (project_name, project_title, project_description, base_path, base_url, project_extent, sel_user_color, sel_transparency, imagelabel_font, imagelabel_text, imagelabel_offset_x, imagelabel_offset_y, imagelabel_position, icon_w, icon_h, history, project_srid, imagelabel_size, imagelabel_color, login_page, project_note, include_outputformats, include_legend, include_metadata, xc, yc, max_extent_scale, default_language_id, charset_encodings_id, legend_font_size) FROM stdin;
default	Default project	\N	\N	\N	\N	\N	50	\N	\N	\N	\N	\N	\N	\N	4	32632	\N	\N	\N	\N	\N	\N	\N	501090	5022596	50000	it	2	8
\.


--
-- Data for Name: project_admin; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.project_admin (project_name, username) FROM stdin;
\.


--
-- Data for Name: project_languages; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.project_languages (project_name, language_id) FROM stdin;
\.


--
-- Data for Name: project_srs; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.project_srs (project_name, srid, projparam, max_extent, resolutions) FROM stdin;
default	32632	\N	\N	\N
default	3857	\N	\N	\N
\.


--
-- Data for Name: qt; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.qt (qt_id, theme_id, layer_id, qt_name, max_rows, papersize_id, edit_url, groupobject, selection_color, qt_order, qtresultype_id, qt_filter, zoom_buffer, qt_title) FROM stdin;
\.


--
-- Data for Name: qt_field; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.qt_field (qtfield_id, qt_id, qtrelation_id, qtfield_name, field_header, fieldtype_id, searchtype_id, resultype_id, field_format, column_width, orderby_id, field_filter, datatype_id, qtfield_order, default_op, editable, formula, lookup_table, lookup_id, lookup_name, filter_field_name) FROM stdin;
\.


--
-- Data for Name: qt_link; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.qt_link (qt_id, link_id, resultype_id) FROM stdin;
\.


--
-- Data for Name: qt_relation; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.qt_relation (qtrelation_id, qt_id, catalog_id, qtrelation_name, qtrelationtype_id, data_field_1, data_field_2, data_field_3, table_name, table_field_1, table_field_2, table_field_3, language_id) FROM stdin;
\.


--
-- Data for Name: relation; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.relation (relation_id, catalog_id, relation_name, relationtype_id, data_field_1, data_field_2, data_field_3, table_name, table_field_1, table_field_2, table_field_3, language_id, layer_id, relation_title) FROM stdin;
\.


--
-- Data for Name: saved_filter; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.saved_filter (saved_filter_id, username, saved_filter_name, mapset_name, layer_id, saved_filter_scope, saved_filter_data) FROM stdin;
\.


--
-- Data for Name: selgroup; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.selgroup (selgroup_id, project_name, selgroup_name, selgroup_title, selgroup_order) FROM stdin;
\.


--
-- Data for Name: selgroup_layer; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.selgroup_layer (selgroup_id, layer_id) FROM stdin;
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.sessions (sess_id, sess_data, sess_time, sess_lifetime) FROM stdin;
\.


--
-- Data for Name: style; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.style (style_id, class_id, style_name, symbol_name, color, outlinecolor, bgcolor, angle, size, minsize, maxsize, width, maxwidth, minwidth, locked, style_def, style_order, pattern_id) FROM stdin;
60	48	style	\N	255 150 150	\N	\N	\N	\N	\N	\N	4	5	3	0	\N	100	0
52	36	style	\N	48 168 156	\N	\N	\N	\N	\N	\N	0.5	\N	\N	0	\N	\N	0
63	49	style	\N	255 191 63	\N	\N	\N	\N	\N	\N	3.5	\N	\N	0	\N	100	0
70	49	background	\N	0 0 0	\N	\N	\N	\N	\N	\N	4	\N	\N	0	\N	200	0
58	43	style	\N	48 168 156	\N	\N	\N	\N	\N	\N	0.5	\N	\N	0	\N	\N	0
59	44	style	\N	200 200 200	\N	\N	\N	\N	\N	\N	1	\N	\N	0	\N	\N	0
64	50	style	\N	255 255 63	\N	\N	\N	\N	\N	\N	3	\N	\N	0	\N	100	0
61	51	style	\N	255 255 255	\N	\N	\N	\N	\N	\N	3	\N	\N	0	\N	100	0
62	52	style	\N	255 255 255	\N	\N	\N	\N	\N	\N	2.5	\N	\N	0	\N	100	0
65	53	style	\N	255 255 255	\N	\N	\N	\N	\N	\N	2	\N	\N	0	\N	100	0
66	53	background	\N	0 0 0	\N	\N	\N	\N	\N	\N	2.5	\N	\N	0	\N	200	0
67	52	background	\N	0 0 0	\N	\N	\N	\N	\N	\N	3	\N	\N	0	\N	200	0
68	51	background	\N	0 0 0	\N	\N	\N	\N	\N	\N	3.5	\N	\N	0	\N	200	0
71	50	background	\N	0 0 0	\N	\N	\N	\N	\N	\N	3.5	\N	\N	0	\N	200	0
69	48	background	\N	155 50 50	\N	\N	\N	\N	\N	\N	4.5	5.5	3.5	0	\N	200	0
57	39	style	\N	\N	0 0 0	\N	\N	\N	\N	\N	2	1	3	0	\N	\N	0
56	40	style	\N	\N	75 75 75	\N	\N	\N	\N	\N	2	1	3	0	OPACITY 75	\N	0
55	41	style	\N	\N	225 225 225	\N	\N	\N	\N	\N	1	2	1	0	OPACITY 80	\N	0
51	35	style	\N	74 146 214	44 116 184	\N	\N	\N	\N	\N	\N	\N	\N	0	\N	\N	0
50	34	style	\N	74 146 214	\N	\N	\N	\N	\N	\N	1.5	\N	\N	0	\N	\N	0
54	42	style	\N	74 146 214	\N	\N	\N	\N	\N	\N	0.5	\N	\N	0	\N	\N	0
3	3	style	\N	200 200 200	150 150 150	\N	\N	\N	\N	\N	\N	\N	\N	0	\N	\N	0
53	37	style	\N	150 150 150	\N	\N	\N	\N	\N	\N	2	\N	\N	0	\N	\N	0
2	38	center2	CIRCLE	0 0 0	\N	\N	\N	16	\N	\N	\N	\N	\N	0	\N	20	0
1	38	center1	CIRCLE	0 0 0	255 255 255	\N	\N	11	\N	\N	2	\N	\N	0	\N	10	0
\.


--
-- Data for Name: symbol; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.symbol (symbol_name, symbolcategory_id, icontype, symbol_image, symbol_def, symbol_type, font_name, ascii_code, filled, points, image) FROM stdin;
VIGNETO	3	0	\N	Type VECTOR\n  Filled TRUE\n  Points\n\t\t.8 .6\n\t\t.4 .6\n\t\t.6 0\n\t\t.4 0\n\t\t.2 .6\n\t\t.4 .8\n\t\t.6 .8\n\t\t.8 .6\n  END\n\t\t	\N	\N	\N	0	\N	\N
TENT	1	0	\N	TYPE VECTOR\nFILLED TRUE\nPOINTS\n0 1\n.5 0\n1 1\n.75 1\n.5 .5\n.25 1\n0 1\nEND	\N	\N	\N	0	\N	\N
STAR	1	0	\N	TYPE VECTOR\nFILLED TRUE\nPOINTS\n0 .375\n.35 .375\n.5 0\n.65 .375\n1 .375\n.75 .625\n.875 1\n.5 .75\n.125 1\n.25 .625\nEND	\N	\N	\N	0	\N	\N
TRIANGLE	1	0	\N	TYPE VECTOR\nFILLED TRUE\nPOINTS\n0 1\n.5 0\n1 1\n0 1\nEND	\N	\N	\N	0	\N	\N
SQUARE	1	0	\N	TYPE VECTOR\nFILLED TRUE\nPOINTS\n0 1\n0 0\n1 0\n1 1\n0 1\nEND	\N	\N	\N	0	\N	\N
PLUS	1	0	\N	TYPE VECTOR\nPOINTS\n.5 0\n.5 1\n-99 -99\n0 .5\n1 .5\nEND	\N	\N	\N	0	\N	\N
CROSS	1	0	\N	TYPE VECTOR\nPOINTS\n0 0\n1 1\n-99 -99\n0 1\n1 0\nEND	\N	\N	\N	0	\N	\N
VIVAIO	3	0	\N	TYPE Vector\n  POINTS\n\t\t.3 1\n\t\t.7 1\n\t\t.9 .1\n\t\t.1 .1\n\t\t.3 1\n\t\t-99 -99\n\t\t.2 .2\n\t\t.2 .1\n\t\t-99 -99\n\t\t.5 .2\n\t\t.5 .1\n\t\t-99 -99\n\t\t.7 .2\n\t\t.7 .1\n  END\n\t	\N	\N	\N	0	\N	\N
CIRCLE	1	0	\N	TYPE ELLIPSE\nFILLED TRUE\nPOINTS\n1 1\nEND	\N	\N	\N	0	\N	\N
WATER	3	0	\N	Type VECTOR\n  Filled FALSE  \n   Points\n\t\t0 .6\n\t\t.1 .4\n\t\t.2 .4\n\t\t.3 .6\n\t\t.4 .6\n\t\t.5 .4\n\t\t.6 .4\n\t\t.7 .6\n\t\t.8 .6\n\t\t.9 .4\n\t\t1 .4\n\t\t1.1 .6\n  END	\N	\N	\N	0	\N	\N
CIRCLE_EMPTY	3	0	\N	TYPE Vector\n  POINTS\n    0 .5\n\t\t.1 .7\n\t\t.3 .9\n\t\t.5 1\n\t\t.7 .9\n\t\t.9 .7\n\t\t1 .5\n\t\t.9 .3\n\t\t.7 .1\n\t\t.5 0\n\t\t.3 .1\n\t\t.1 .3\n\t\t0 .5\n  END\n\t	\N	\N	\N	0	\N	\N
CIRCLE_HALF	3	0	\N	TYPE Vector\n  POINTS\n    0 .5\n\t\t.1 .7\n\t\t.3 .9\n\t\t.5 1\n\t\t.7 .9\n\t\t.9 .7\n\t\t1 .5\n\t\t0 .5\n  END\n\n\t	\N	\N	\N	0	\N	\N
BOSCO	3	0	\N	TYPE Vector\n  POINTS\n    .5 1\n    .5 0\n\t\t-99 -99\n\t\t.5 0\n\t\t.3 .1 \n\t\t-99 -99\n\t\t.5 .0\n\t\t.7 .1\n\t\t-99 -99\n\t\t.5 .3\n\t\t.2 .4\n\t\t-99 -99\n\t\t.5 .3\n\t\t.8 .4\n\t\t-99 -99\n\t\t.5 .6\n\t\t.1 .8\n\t\t-99 -99\n\t\t.5 .6\n\t\t.9 .8\n  END\n\t	\N	\N	\N	0	\N	\N
CIMITERO	3	0	\N	TYPE VECTOR\nPOINTS\n.5 0\n.5 1\n-99 -99\n.2 .3\n.8 .3\nEND\n	\N	\N	\N	0	\N	\N
FRUTTETO	3	0	\N	Type VECTOR\n  Filled TRUE\n  Points\n\t\t.2 1\n\t\t.2 .8\n\t\t.4 .8 \n\t\t.4 .4\n\t\t0 0\n\t\t.2 0\n\t\t.4 .2\n\t\t.4 0\n\t\t.6 0\n\t\t.6 .2\n\t\t.8 0\n\t\t1 0\n\t\t.6 .4\n\t\t.6 .8\n\t\t.8 .8\n\t\t.8 1\n\t\t.2 1\n  END\n\t\t	\N	\N	\N	0	\N	\N
INCOLTO	3	0	\N	Type VECTOR\n  Filled TRUE\n  Points\n\t0 1\n\t.2 .6\n\t.35 .85\n\t.5 .6\n\t.65 .85\n\t.8 .6 \n\t1 1\n\t.9 1\n\t.8 .8\n\t.7 1\n\t.6 1\n\t.5 .8\n\t.4 1\n\t.3 1\n\t.2 .8\n\t.1 1\n\t0 1\n  END\n\t\t	\N	\N	\N	0	\N	\N
PASCOLO	3	0	\N	  Type VECTOR\n  Filled TRUE\n  Points\n    0 .4\n\t\t.2 1\n\t\t.4 1\n\t\t.2 .4\n\t\t0 .4\n\t\t-99 -99\n\t\t.4 0\n\t\t.6 0 \n\t\t.6 1\n\t\t.4 1\n\t\t.4 0 \n\t   -99 -99\n\t\t .8 .4\n\t\t 1 .4\n\t\t .8 1\n\t\t .6 1\n\t\t .8 .4\t\n  END\n\t\t	\N	\N	\N	0	\N	\N
RANDOM	3	0	\N	  Type VECTOR\n  Filled TRUE\n  Points\n    .1 .1\n\t\t.3 .3\n  -99 -99\n\t\t.5 .2\n\t\t.7 0\n  -99 -99\n\t\t.9 .2\n  -99 -99\n\t\t.7 .3\n  -99 -99\n\t\t.1 .5\t\t\n  -99 -99\n\t\t.6 .5\n\t\t.4 .7\n  -99 -99\n\t\t.3 .8\n  -99 -99\n\t\t.8 .7\n  -99 -99\n\t\t.1 .9\n  -99 -99\n\t\t.6 .8\n\t\t.6 1\n  END\n\t\t	\N	\N	\N	0	\N	\N
RISAIA	3	0	\N	Type VECTOR\n  Filled TRUE\n  Points\n\t\t0 1\n\t\t0 .4\n\t\t.2 .4\n\t\t.2 1\n\t\t0 1\n\t\t-99 -99\n\t\t.4 1\n\t\t.4 0\n\t\t.6 0\n\t\t.6 1\n\t\t.4 1\n\t\t-99 -99 \n\t\t.8 1\n\t\t.8 .4\n\t\t1 .4\n\t\t1 1\n\t\t.8 1 \n  END\n\t\t	\N	\N	\N	0	\N	\N
RUPESTRE	3	0	\N	  Type VECTOR\n  Filled TRUE\n  Points\n    .2 .8\n    .35 .6\n    .65 .6\n    .8 .8\n   -99 -99\n    0 .6\n    .15 .45\n    .35 .45\n    .5 .6\n    .65 .45\n    .85 .45\n    1 .6\t\t\n  END\n\t\t	\N	\N	\N	0	\N	\N
CIRCLE_FILL	3	0	\N	TYPE ELLIPSE\nFILLED TRUE\nPOINTS\n1 1\nEND\n\t	\N	\N	\N	0	\N	\N
SQUARE_EMPTY	3	0	\N	Type VECTOR\n  Points\n\t.1 .1\n\t.1 .9\n\t.9 .9\n\t.9 .1\n\t.1 .1\n  END	\N	\N	\N	0	\N	\N
TRIANGLE_EMPTY	3	0	\N	Type VECTOR\n  Points\n\t.1 .1\n\t.9 .1\n\t.9 .1\n\t.5 .9\n\t.1 .1\n  END	\N	\N	\N	0	\N	\N
PLUS_FILL	3	0	\N	TYPE VECTOR\nPOINTS\n    .1 .3\n    .5 .3\n    -99 -99\n    .3 .1\n    .3 .5\n    -99 -99\n    .5 .7\n    .9 .7\n    -99 -99\n    .7 .5\n    .7 .9\nEND	\N	\N	\N	0	\N	\N
SNOW	3	0	\N	Type VECTOR\n  Points\n\t0 .5\n\t1 .5\n\t-99 -99\n\t.2 0\n\t.8 1\n\t-99 -99\n\t.8 0\n\t.2 1\n  END\n\t\t	\N	\N	\N	0	\N	\N
HEXAGON_EMPTY	3	0	\N	Type VECTOR\n  Points\n\t.3 .1\n\t.8 .1\n\t1 .5\n\t.8 .9\n\t.3 .9\n\t.1 .5\n\t.3 .1\n  END	\N	\N	\N	0	\N	\N
HEXAGON_BEE	3	0	\N	Type VECTOR\n  Points\n\t.1 0\n\t.2 .2\n\t.1 .4\n\t0 .4\n\t-99 -99\n\t.2 .2\n\t.4 .2\n\t-99 -99\n\t.5 0\n\t.4 .2\n\t.5 .4\n\t.6 .4\n  END\n	\N	\N	\N	0	\N	\N
ICE	3	0	\N	Type VECTOR\n  Points\n\t0 .5\n    .5 1\n\t-99 -99\n\t0 0\n    1 .5\n\t-99 -99\n\t.5 0\n    0 1\n    -99 -99\n    .5 0\n    .5 1\n    -99 -99\n    0 0\n    0 .5\n  END\n	\N	\N	\N	0	\N	\N
HALF_SQUARE	3	0	\N	Type VECTOR\n  Points\n\t.2 1.8\n\t1.8 1.8\n\t1.8 .2\n  END	\N	\N	\N	0	\N	\N
DASH_DASH	3	0	\N	Type VECTOR\n  Points\n\t0 .9 \n\t.3 .9\n\t-99 -99\n\t.7 .9\n\t1 .9\n\t-99 -99\n\t.2 .4 \n\t.8 .4\n  END\n	\N	\N	\N	0	\N	\N
DASH_DASH_VERTICAL	3	0	\N	Type VECTOR\n  Points\n\t.9 0 \n\t.9 .3 \n\t-99 -99\n\t.9 .7 \n\t.9 1 \n\t-99 -99\n\t.4 .2 \n\t.4 .8 \n  END\n	\N	\N	\N	0	\N	\N
DASH_LINE	3	0	\N	Type VECTOR\n  Points\n\t0 .9 \n\t1 .9\n\t-99 -99\n\t.2 .4 \n\t.8 .4\n  END\n	\N	\N	\N	0	\N	\N
STREAMERS	3	0	\N	Type VECTOR\n  Points\n\t.1 .1\n    .4 .1\n\t-99 -99\n\t.9 .1\n    .6 .4\n\t-99 -99\n\t.1 .6 \n    .1 .9 \n    -99 -99\n\t.4 .6\n    .7 .9\n  END\n	\N	\N	\N	0	\N	\N
POINT_LINE_VERTICAL	3	0	\N	Type VECTOR\n  Points\n\t.9 0  \n\t.9 1 \n\t-99 -99\n\t .4 .4\n\t .4 .6\n  END\n	\N	\N	\N	0	\N	\N
DOUBLE_LINE_VERTICAL	3	0	\N	Type VECTOR\n  Points\n    .0 0  \n\t.0 1 \n\t-99 -99\n\t.3 0  \n\t.3 1 \n\t-99 -99\n\t1 0  \n\t1 1 \n  END\n	\N	\N	\N	0	\N	\N
SQUARE_FILL	3	0	\N	Type VECTOR\nFILLED TRUE\n  Points\n\t.1 .1\n\t.1 .9\n\t.9 .9\n\t.9 .1\n\t.1 .1\n  END\n\t\t	\N	\N	\N	0	\N	\N
RIPARIE-CANNETO	3	0	\N	TYPE VECTOR\nPOINTS\n.3 0\n.3 1\n.7 1\nEND\n 	\N	\N	\N	0	\N	\N
VERTEX	3	0	\N	TYPE VECTOR\nFILLED TRUE\nPOINTS\n\t1 8\n\t3 8\n\t3 9\n\t1 9\n\t1 8\n-99 -99\n\t7 8\n\t9 8\n\t9 9\n\t7 9\n\t7 8\n-99 -99\n\t4 1\n\t6 1\n\t6 2\n\t4 2\n\t4 1\nEND	\N	\N	\N	0	\N	\N
T	3	0	\N	TYPE VECTOR\nPOINTS\n.5 .5\n.5 1\t\n-99 -99\n0 .5\n1 .5\nEND	\N	\N	\N	0	\N	\N
DOUBLE_T	3	0	\N	TYPE VECTOR\nPOINTS\n.3 .5\n.3 1\t\n-99 -99\n.7 .5\n.7 1\t\n-99 -99\n0 .5\n1 .5\nEND	\N	\N	\N	0	\N	\N
D	3	0	\N	TYPE VECTOR\n FILLED TRUE\nPOINTS\n.5 0\n.5 1\n.3 .9\n.1 .7\n0 .5\n.1 .3\n.3 .1\n.5 0\nEND	\N	\N	\N	0	\N	\N
MONUMENTO	3	0	\N	TYPE VECTOR\nPOINTS\n.5 1\n.2 .3\n.2 .2\n.4 0\n.6 0\n.6 .2\n.6 .3\n.5 1\nEND	\N	\N	\N	0	\N	\N
VERTICAL	4	0	\N	TYPE VECTOR\nPOINTS\n.5 0\n.5 1\nEND	\N	\N	\N	0	\N	\N
HORIZONTAL	4	0	\N	TYPE VECTOR\nPOINTS\n0 .5\n1 .5\nEND	\N	\N	\N	0	\N	\N
SQUARE_HALF	1	0	\N	TYPE VECTOR\nFILLED TRUE\nPOINTS\n0 0\n0 1\n1 0\n0 0\nEND	\N	\N	\N	0	\N	\N
IDRANTE	1	0	\N	TYPE VECTOR\nFILLED TRUE\nPOINTS\n0 1\n1 1\n-99 -99\n.2 1\n.2 .4\n.8 .4\n.8 1\n.2 1\n-99 -99\n.2 .8\n0 .8\n0 .6\n.2 .6\n-99 -99\n.8 .8\n1 .8\n1 .6\n.8 .6\n-99 -99\n0 .4\n1 .4\n.9 .2\n.7 0\n.3 0\n.1 .2\n0 .4\nEND	\N	\N	\N	0	\N	\N
CONN.T	6	0	\N	TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#065;"	\N	\N	\N	0	\N	\N
SARACINESCA	6	0	\N	TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#066;"	\N	\N	\N	0	\N	\N
SALDATURA	6	0	\N	TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#067;"	\N	\N	\N	0	\N	\N
RIDUTTORE	6	0	\N	TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#068;"	\N	\N	\N	0	\N	\N
ALLACCIAMENTO	6	0	\N	TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#069;"	\N	\N	\N	0	\N	\N
ARCO	6	0	\N	TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#070;"	\N	\N	\N	0	\N	\N
VALVOLA	6	0	\N	TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#072;"	\N	\N	\N	0	\N	\N
TAPPO	6	0	\N	TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#073;"	\N	\N	\N	0	\N	\N
POZZETTO ISP	6	0	\N	TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#074;"	\N	\N	\N	0	\N	\N
IDRANT	6	0	\N	TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#075;"	\N	\N	\N	0	\N	\N
GIUNTO	6	0	\N	TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#076;"	\N	\N	\N	0	\N	\N
CONTATORE	6	0	\N	TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#077;"	\N	\N	\N	0	\N	\N
GENERICO	6	0	\N	TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#078;"	\N	\N	\N	0	\N	\N
VUOTO	6	0	\N	TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#079;"	\N	\N	\N	0	\N	\N
CENTRALINA	6	0	\N	TYPE TRUETYPE\nFONT "r3-technet"\nFILLED TRUE\nANTIALIAS FALSE\nCHARACTER "&#080;"	\N	\N	\N	0	\N	\N
\.


--
-- Data for Name: theme; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.theme (theme_id, project_name, theme_name, theme_title, theme_order, locked, theme_single, radio, copyright_string, symbol_name, theme_description) FROM stdin;
19	default	boundaries_places	Boundaries and places	10	0	0	0	© <a target="_blank" href ="http://opendatacommons.org/licenses/odbl/summary/">OpenStreetMap</a> contributors	\N	\N
21	default	hydro	Hydro	50	0	0	0	© <a target="_blank" href ="http://opendatacommons.org/licenses/odbl/summary/">OpenStreetMap</a> contributors	\N	\N
27	default	railroads	Railroads	40	0	0	0	© <a target="_blank" href ="http://opendatacommons.org/licenses/odbl/summary/">OpenStreetMap</a> contributors	\N	\N
22	default	roads	Roads	30	0	0	0	© <a target="_blank" href ="http://opendatacommons.org/licenses/odbl/summary/">OpenStreetMap</a> contributors	\N	\N
1	default	buildings	Buildings	20	0	0	0	© <a target="_blank" href ="http://opendatacommons.org/licenses/odbl/summary/">OpenStreetMap</a> contributors	\N	\N
\.


--
-- Data for Name: theme_version; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.theme_version (theme_id, theme_version) FROM stdin;
\.


--
-- Data for Name: user_group; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.user_group (username, groupname) FROM stdin;
\.


--
-- Data for Name: usercontext; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.usercontext (usercontext_id, username, mapset_name, title, context) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.users (username, pwd, enc_pwd, data_creazione, data_scadenza, data_modifica, attivato, ultimo_accesso, cognome, nome, macaddress, ip, host, controllo, userdata, email) FROM stdin;
admin	\N	21232f297a57a5a743894a0e4a801fc3	\N	\N	2024-09-16	1	\N	\N	\N	\N	\N	\N	\N	\N	\N
\.


--
-- Data for Name: users_options; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.users_options (users_options_id, username, option_key, option_value) FROM stdin;
\.


--
-- Data for Name: version; Type: TABLE DATA; Schema: gisclient_34; Owner: -
--

COPY gisclient_34.version (version_id, version_name, version_date, version_key) FROM stdin;
1	3.4.0	2015-06-15	author
2	3.4.1	2015-10-09	author
3	3.4.2	2016-01-25	author
4	3.4.3	2016-03-08	author
5	3.4.4	2016-05-07	author
6	3.4.5	2016-05-30	author
7	3.4.6	2016-10-26	author
8	3.4.7	2017-01-16	author
9	3.4.8	2017-02-03	author
10	3.5.0	2017-11-27	author
11	3.5.1	2018-01-25	author
12	3.5.2	2018-05-22	author
13	3.5.3	2018-08-14	author
14	3.5.4	2019-08-01	author
15	3.5.5	2019-03-19	author
16	3.5.6	2019-05-14	author
17	3.6.0	2019-01-24	author
18	3.6.1	2019-05-31	author
19	3.6.2	2019-10-11	author
20	3.6.3	2024-03-14	author
\.


--
-- Name: document_doc_id_seq; Type: SEQUENCE SET; Schema: gisclient_34; Owner: -
--

SELECT pg_catalog.setval('gisclient_34.document_doc_id_seq', 1, true);


--
-- Name: e_pattern_pattern_id_seq; Type: SEQUENCE SET; Schema: gisclient_34; Owner: -
--

SELECT pg_catalog.setval('gisclient_34.e_pattern_pattern_id_seq', 11, true);


--
-- Name: export_i18n_exporti18n_id_seq; Type: SEQUENCE SET; Schema: gisclient_34; Owner: -
--

SELECT pg_catalog.setval('gisclient_34.export_i18n_exporti18n_id_seq', 1, true);


--
-- Name: i18n_field_i18nf_id_seq; Type: SEQUENCE SET; Schema: gisclient_34; Owner: -
--

SELECT pg_catalog.setval('gisclient_34.i18n_field_i18nf_id_seq', 22, true);


--
-- Name: layer_groups_seq; Type: SEQUENCE SET; Schema: gisclient_34; Owner: -
--

SELECT pg_catalog.setval('gisclient_34.layer_groups_seq', 145, true);


--
-- Name: localization_localization_id_seq; Type: SEQUENCE SET; Schema: gisclient_34; Owner: -
--

SELECT pg_catalog.setval('gisclient_34.localization_localization_id_seq', 1290, true);


--
-- Name: logs_log_id_seq; Type: SEQUENCE SET; Schema: gisclient_34; Owner: -
--

SELECT pg_catalog.setval('gisclient_34.logs_log_id_seq', 1, false);


--
-- Name: saved_filter_saved_filter_id_seq; Type: SEQUENCE SET; Schema: gisclient_34; Owner: -
--

SELECT pg_catalog.setval('gisclient_34.saved_filter_saved_filter_id_seq', 1, true);


--
-- Name: theme_version_id_seq; Type: SEQUENCE SET; Schema: gisclient_34; Owner: -
--

SELECT pg_catalog.setval('gisclient_34.theme_version_id_seq', 288, true);


--
-- Name: usercontext_usercontext_id_seq; Type: SEQUENCE SET; Schema: gisclient_34; Owner: -
--

SELECT pg_catalog.setval('gisclient_34.usercontext_usercontext_id_seq', 1, true);


--
-- Name: users_options_users_options_id_seq; Type: SEQUENCE SET; Schema: gisclient_34; Owner: -
--

SELECT pg_catalog.setval('gisclient_34.users_options_users_options_id_seq', 1, true);


--
-- Name: version_version_id_seq; Type: SEQUENCE SET; Schema: gisclient_34; Owner: -
--

SELECT pg_catalog.setval('gisclient_34.version_version_id_seq', 20, true);


--
-- Name: i18n_field 18n_field_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.i18n_field
    ADD CONSTRAINT "18n_field_pkey" PRIMARY KEY (i18nf_id);


--
-- Name: catalog catalog_catalog_name_key; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.catalog
    ADD CONSTRAINT catalog_catalog_name_key UNIQUE (catalog_name, project_name);


--
-- Name: catalog_import catalog_import_catalog_import_name_key; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.catalog_import
    ADD CONSTRAINT catalog_import_catalog_import_name_key UNIQUE (catalog_import_name, project_name);


--
-- Name: catalog_import catalog_import_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.catalog_import
    ADD CONSTRAINT catalog_import_pkey PRIMARY KEY (catalog_import_id);


--
-- Name: catalog catalog_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.catalog
    ADD CONSTRAINT catalog_pkey PRIMARY KEY (catalog_id);


--
-- Name: class class_layer_id_key; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.class
    ADD CONSTRAINT class_layer_id_key UNIQUE (layer_id, class_name);


--
-- Name: class class_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.class
    ADD CONSTRAINT class_pkey PRIMARY KEY (class_id);


--
-- Name: document document_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.document
    ADD CONSTRAINT document_pkey PRIMARY KEY (doc_id);


--
-- Name: e_charset_encodings e_charset_encodings_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_charset_encodings
    ADD CONSTRAINT e_charset_encodings_pkey PRIMARY KEY (charset_encodings_id);


--
-- Name: e_conntype e_conntype_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_conntype
    ADD CONSTRAINT e_conntype_pkey PRIMARY KEY (conntype_id);


--
-- Name: e_datatype e_datatype_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_datatype
    ADD CONSTRAINT e_datatype_pkey PRIMARY KEY (datatype_id);


--
-- Name: e_fieldformat e_fieldformat_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_fieldformat
    ADD CONSTRAINT e_fieldformat_pkey PRIMARY KEY (fieldformat_id);


--
-- Name: e_fieldtype e_fieldtype_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_fieldtype
    ADD CONSTRAINT e_fieldtype_pkey PRIMARY KEY (fieldtype_id);


--
-- Name: e_filetype e_filetype_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_filetype
    ADD CONSTRAINT e_filetype_pkey PRIMARY KEY (filetype_id);


--
-- Name: e_form e_form_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_form
    ADD CONSTRAINT e_form_pkey PRIMARY KEY (id);


--
-- Name: e_formula e_formula_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_formula
    ADD CONSTRAINT e_formula_pkey PRIMARY KEY (formula_id);


--
-- Name: e_language e_language_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_language
    ADD CONSTRAINT e_language_pkey PRIMARY KEY (language_id);


--
-- Name: e_layertype e_layertype_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_layertype
    ADD CONSTRAINT e_layertype_pkey PRIMARY KEY (layertype_id);


--
-- Name: e_lblposition e_lblposition_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_lblposition
    ADD CONSTRAINT e_lblposition_pkey PRIMARY KEY (lblposition_id);


--
-- Name: e_legendtype e_legendtype_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_legendtype
    ADD CONSTRAINT e_legendtype_pkey PRIMARY KEY (legendtype_id);


--
-- Name: e_level e_level_name_key; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_level
    ADD CONSTRAINT e_level_name_key UNIQUE (name);


--
-- Name: e_level e_livelli_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_level
    ADD CONSTRAINT e_livelli_pkey PRIMARY KEY (id);


--
-- Name: e_orderby e_orderby_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_orderby
    ADD CONSTRAINT e_orderby_pkey PRIMARY KEY (orderby_id);


--
-- Name: e_outputformat e_outputformat_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_outputformat
    ADD CONSTRAINT e_outputformat_pkey PRIMARY KEY (outputformat_id);


--
-- Name: e_owstype e_owstype_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_owstype
    ADD CONSTRAINT e_owstype_pkey PRIMARY KEY (owstype_id);


--
-- Name: e_papersize e_papersize_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_papersize
    ADD CONSTRAINT e_papersize_pkey PRIMARY KEY (papersize_id);


--
-- Name: e_pattern e_pattern_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_pattern
    ADD CONSTRAINT e_pattern_pkey PRIMARY KEY (pattern_id);


--
-- Name: e_relationtype e_relationtype_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_relationtype
    ADD CONSTRAINT e_relationtype_pkey PRIMARY KEY (relationtype_id);


--
-- Name: e_resultype e_resultype_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_resultype
    ADD CONSTRAINT e_resultype_pkey PRIMARY KEY (resultype_id);


--
-- Name: e_searchable e_searchable_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_searchable
    ADD CONSTRAINT e_searchable_pkey PRIMARY KEY (searchable_id);


--
-- Name: e_searchtype e_searchtype_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_searchtype
    ADD CONSTRAINT e_searchtype_pkey PRIMARY KEY (searchtype_id);


--
-- Name: e_sizeunits e_sizeunits_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_sizeunits
    ADD CONSTRAINT e_sizeunits_pkey PRIMARY KEY (sizeunits_id);


--
-- Name: e_symbolcategory e_symbolcategory_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_symbolcategory
    ADD CONSTRAINT e_symbolcategory_pkey PRIMARY KEY (symbolcategory_id);


--
-- Name: e_tiletype e_tiletype_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_tiletype
    ADD CONSTRAINT e_tiletype_pkey PRIMARY KEY (tiletype_id);


--
-- Name: e_wmsversion e_wmsversion_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_wmsversion
    ADD CONSTRAINT e_wmsversion_pkey PRIMARY KEY (wmsversion_id);


--
-- Name: export_i18n export_i18n_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.export_i18n
    ADD CONSTRAINT export_i18n_pkey PRIMARY KEY (exporti18n_id);


--
-- Name: field field_field_name_layer_id_key; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.field
    ADD CONSTRAINT field_field_name_layer_id_key UNIQUE (field_name, relation_id, layer_id);


--
-- Name: field_groups field_groups_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.field_groups
    ADD CONSTRAINT field_groups_pkey PRIMARY KEY (field_id, groupname);


--
-- Name: field field_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.field
    ADD CONSTRAINT field_pkey PRIMARY KEY (field_id);


--
-- Name: authfilter filter_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.authfilter
    ADD CONSTRAINT filter_pkey PRIMARY KEY (filter_id);


--
-- Name: font font_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.font
    ADD CONSTRAINT font_pkey PRIMARY KEY (font_name);


--
-- Name: group_authfilter group_authfilter_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.group_authfilter
    ADD CONSTRAINT group_authfilter_pkey PRIMARY KEY (groupname, filter_id);


--
-- Name: groups groups_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.groups
    ADD CONSTRAINT groups_pkey PRIMARY KEY (groupname);


--
-- Name: i18n_field i18n_field_table_name_field_name_key; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.i18n_field
    ADD CONSTRAINT i18n_field_table_name_field_name_key UNIQUE (table_name, field_name);


--
-- Name: layer_authfilter layer_authfilter_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.layer_authfilter
    ADD CONSTRAINT layer_authfilter_pkey PRIMARY KEY (layer_id, filter_id);


--
-- Name: layer_groups layer_groups_layer_id_groupname_key; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.layer_groups
    ADD CONSTRAINT layer_groups_layer_id_groupname_key UNIQUE (layer_id, groupname);


--
-- Name: layer_groups layer_groups_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.layer_groups
    ADD CONSTRAINT layer_groups_pkey PRIMARY KEY (layer_groups_id);


--
-- Name: layer layer_layergroup_id_key; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.layer
    ADD CONSTRAINT layer_layergroup_id_key UNIQUE (layergroup_id, layer_name);


--
-- Name: layer_link layer_link_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.layer_link
    ADD CONSTRAINT layer_link_pkey PRIMARY KEY (layer_id, link_id);


--
-- Name: layer layer_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.layer
    ADD CONSTRAINT layer_pkey PRIMARY KEY (layer_id);


--
-- Name: layergroup layergroup_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.layergroup
    ADD CONSTRAINT layergroup_pkey PRIMARY KEY (layergroup_id);


--
-- Name: layergroup layergroup_theme_key; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.layergroup
    ADD CONSTRAINT layergroup_theme_key UNIQUE (theme_id, layergroup_name);


--
-- Name: link link_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.link
    ADD CONSTRAINT link_pkey PRIMARY KEY (link_id);


--
-- Name: form_level livelli_form_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.form_level
    ADD CONSTRAINT livelli_form_pkey PRIMARY KEY (id);


--
-- Name: localization localization_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.localization
    ADD CONSTRAINT localization_pkey PRIMARY KEY (localization_id);


--
-- Name: localization localization_project_name_i18nf_id_pkey_id_language_id_key; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.localization
    ADD CONSTRAINT localization_project_name_i18nf_id_pkey_id_language_id_key UNIQUE (project_name, i18nf_id, pkey_id, language_id);


--
-- Name: localization localization_project_name_i18nf_id_pkey_id_language_id_key1; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.localization
    ADD CONSTRAINT localization_project_name_i18nf_id_pkey_id_language_id_key1 UNIQUE (project_name, i18nf_id, pkey_id, language_id);


--
-- Name: localization localization_project_name_i18nf_id_pkey_id_language_id_key2; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.localization
    ADD CONSTRAINT localization_project_name_i18nf_id_pkey_id_language_id_key2 UNIQUE (project_name, i18nf_id, pkey_id, language_id);


--
-- Name: logs logs_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.logs
    ADD CONSTRAINT logs_pkey PRIMARY KEY (log_id);


--
-- Name: mapset_groups mapset_groups_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.mapset_groups
    ADD CONSTRAINT mapset_groups_pkey PRIMARY KEY (mapset_name, groupname);


--
-- Name: mapset_layergroup mapset_layergroup_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.mapset_layergroup
    ADD CONSTRAINT mapset_layergroup_pkey PRIMARY KEY (mapset_name, layergroup_id);


--
-- Name: mapset mapset_mapset_name_key; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.mapset
    ADD CONSTRAINT mapset_mapset_name_key UNIQUE (mapset_name);


--
-- Name: mapset mapset_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.mapset
    ADD CONSTRAINT mapset_pkey PRIMARY KEY (mapset_name);


--
-- Name: project_admin project_admin_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.project_admin
    ADD CONSTRAINT project_admin_pkey PRIMARY KEY (project_name, username);


--
-- Name: project_languages project_languages_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.project_languages
    ADD CONSTRAINT project_languages_pkey PRIMARY KEY (project_name, language_id);


--
-- Name: project project_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.project
    ADD CONSTRAINT project_pkey PRIMARY KEY (project_name);


--
-- Name: theme project_theme_id_key; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.theme
    ADD CONSTRAINT project_theme_id_key UNIQUE (project_name, theme_name);


--
-- Name: qt_link qt_link_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.qt_link
    ADD CONSTRAINT qt_link_pkey PRIMARY KEY (qt_id, link_id);


--
-- Name: qt_link qt_link_qt_id_key; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.qt_link
    ADD CONSTRAINT qt_link_qt_id_key UNIQUE (qt_id, link_id, resultype_id);


--
-- Name: qt qt_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.qt
    ADD CONSTRAINT qt_pkey PRIMARY KEY (qt_id);


--
-- Name: qt_field qtfield_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.qt_field
    ADD CONSTRAINT qtfield_pkey PRIMARY KEY (qtfield_id);


--
-- Name: qt_field qtfield_qt_id_key; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.qt_field
    ADD CONSTRAINT qtfield_qt_id_key UNIQUE (qt_id, field_header);


--
-- Name: field qtfield_unique_key; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.field
    ADD CONSTRAINT qtfield_unique_key UNIQUE (layer_id, relation_id, field_header);


--
-- Name: qt_relation qtrelation_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.qt_relation
    ADD CONSTRAINT qtrelation_pkey PRIMARY KEY (qtrelation_id);


--
-- Name: relation relation_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.relation
    ADD CONSTRAINT relation_pkey PRIMARY KEY (relation_id);


--
-- Name: saved_filter saved_filter_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.saved_filter
    ADD CONSTRAINT saved_filter_pkey PRIMARY KEY (saved_filter_id);


--
-- Name: selgroup_layer selgroup_layer_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.selgroup_layer
    ADD CONSTRAINT selgroup_layer_pkey PRIMARY KEY (layer_id, selgroup_id);


--
-- Name: selgroup selgroup_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.selgroup
    ADD CONSTRAINT selgroup_pkey PRIMARY KEY (selgroup_id);


--
-- Name: selgroup selgroup_selgroup_name_key; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.selgroup
    ADD CONSTRAINT selgroup_selgroup_name_key UNIQUE (selgroup_name, project_name);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (sess_id);


--
-- Name: style style_class_id_key; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.style
    ADD CONSTRAINT style_class_id_key UNIQUE (class_id, style_name);


--
-- Name: style style_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.style
    ADD CONSTRAINT style_pkey PRIMARY KEY (style_id);


--
-- Name: symbol symbol_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.symbol
    ADD CONSTRAINT symbol_pkey PRIMARY KEY (symbol_name);


--
-- Name: theme theme_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.theme
    ADD CONSTRAINT theme_pkey PRIMARY KEY (theme_id);


--
-- Name: theme_version theme_version_idx; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.theme_version
    ADD CONSTRAINT theme_version_idx PRIMARY KEY (theme_id);


--
-- Name: user_group user_group_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.user_group
    ADD CONSTRAINT user_group_pkey PRIMARY KEY (username, groupname);


--
-- Name: users user_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.users
    ADD CONSTRAINT user_pkey PRIMARY KEY (username);


--
-- Name: usercontext usercontext_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.usercontext
    ADD CONSTRAINT usercontext_pkey PRIMARY KEY (usercontext_id);


--
-- Name: users_options users_options_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.users_options
    ADD CONSTRAINT users_options_pkey PRIMARY KEY (users_options_id);


--
-- Name: version version_pkey; Type: CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.version
    ADD CONSTRAINT version_pkey PRIMARY KEY (version_id);


--
-- Name: fki_; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_ ON gisclient_34.relation USING btree (layer_id);


--
-- Name: fki_catalog_conntype_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_catalog_conntype_fkey ON gisclient_34.catalog USING btree (connection_type);


--
-- Name: fki_catalog_import_from_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_catalog_import_from_fkey ON gisclient_34.catalog_import USING btree (catalog_from);


--
-- Name: fki_catalog_import_project_name_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_catalog_import_project_name_fkey ON gisclient_34.catalog_import USING btree (project_name);


--
-- Name: fki_catalog_import_to_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_catalog_import_to_fkey ON gisclient_34.catalog_import USING btree (catalog_to);


--
-- Name: fki_catalog_project_name_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_catalog_project_name_fkey ON gisclient_34.catalog USING btree (project_name);


--
-- Name: fki_class_layer_id_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_class_layer_id_fkey ON gisclient_34.class USING btree (layer_id);


--
-- Name: fki_field_fieldtype_id_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_field_fieldtype_id_fkey ON gisclient_34.field USING btree (fieldtype_id);


--
-- Name: fki_layer_id; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_layer_id ON gisclient_34.layer_groups USING btree (layer_id);


--
-- Name: fki_layer_layergroup_id; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_layer_layergroup_id ON gisclient_34.layer USING btree (layergroup_id);


--
-- Name: fki_layer_link_link_id_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_layer_link_link_id_fkey ON gisclient_34.layer_link USING btree (link_id);


--
-- Name: fki_layergroup_theme_id; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_layergroup_theme_id ON gisclient_34.layergroup USING btree (theme_id);


--
-- Name: fki_link_project_name_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_link_project_name_fkey ON gisclient_34.link USING btree (project_name);


--
-- Name: fki_pattern_id_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_pattern_id_fkey ON gisclient_34.style USING btree (pattern_id);


--
-- Name: fki_qt_layer_id_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_qt_layer_id_fkey ON gisclient_34.qt USING btree (layer_id);


--
-- Name: fki_qt_link_link_id_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_qt_link_link_id_fkey ON gisclient_34.qt_link USING btree (link_id);


--
-- Name: fki_qt_link_qt_id_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_qt_link_qt_id_fkey ON gisclient_34.qt_link USING btree (qt_id);


--
-- Name: fki_qtfield_fieldtype_id_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_qtfield_fieldtype_id_fkey ON gisclient_34.qt_field USING btree (fieldtype_id);


--
-- Name: fki_qtfields_layer; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_qtfields_layer ON gisclient_34.field USING btree (layer_id);


--
-- Name: fki_qtrelation_catalog_id_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_qtrelation_catalog_id_fkey ON gisclient_34.qt_relation USING btree (catalog_id);


--
-- Name: fki_qtrelation_qt_id_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_qtrelation_qt_id_fkey ON gisclient_34.qt_relation USING btree (qt_id);


--
-- Name: fki_relation_catalog_id_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_relation_catalog_id_fkey ON gisclient_34.relation USING btree (catalog_id);


--
-- Name: fki_style_class_id_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_style_class_id_fkey ON gisclient_34.style USING btree (class_id);


--
-- Name: fki_symbol_icontype_id_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_symbol_icontype_id_fkey ON gisclient_34.symbol USING btree (icontype);


--
-- Name: fki_symbol_symbolcategory_id_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_symbol_symbolcategory_id_fkey ON gisclient_34.symbol USING btree (symbolcategory_id);


--
-- Name: fki_symbol_ttf_fkey; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX fki_symbol_ttf_fkey ON gisclient_34.class USING btree (symbol_ttf_name, label_font);


--
-- Name: qtfield_id_index; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX qtfield_id_index ON gisclient_34.field USING btree (field_id);


--
-- Name: qtfield_name_unique; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE UNIQUE INDEX qtfield_name_unique ON gisclient_34.field USING btree (layer_id, field_name);


--
-- Name: qtrelation_id_index; Type: INDEX; Schema: gisclient_34; Owner: -
--

CREATE INDEX qtrelation_id_index ON gisclient_34.relation USING btree (relation_id);


--
-- Name: catalog chk_catalog; Type: TRIGGER; Schema: gisclient_34; Owner: -
--

CREATE TRIGGER chk_catalog BEFORE INSERT OR UPDATE ON gisclient_34.catalog FOR EACH ROW EXECUTE FUNCTION gisclient_34.check_catalog();


--
-- Name: class chk_class; Type: TRIGGER; Schema: gisclient_34; Owner: -
--

CREATE TRIGGER chk_class BEFORE INSERT OR UPDATE ON gisclient_34.class FOR EACH ROW EXECUTE FUNCTION gisclient_34.check_class();


--
-- Name: relation delete_relation; Type: TRIGGER; Schema: gisclient_34; Owner: -
--

CREATE TRIGGER delete_relation AFTER DELETE ON gisclient_34.relation FOR EACH ROW EXECUTE FUNCTION gisclient_34.delete_relation();


--
-- Name: layergroup move_layergroup; Type: TRIGGER; Schema: gisclient_34; Owner: -
--

CREATE TRIGGER move_layergroup AFTER UPDATE ON gisclient_34.layergroup FOR EACH ROW EXECUTE FUNCTION gisclient_34.move_layergroup();


--
-- Name: users set_encpwd; Type: TRIGGER; Schema: gisclient_34; Owner: -
--

CREATE TRIGGER set_encpwd BEFORE INSERT OR UPDATE ON gisclient_34.users FOR EACH ROW EXECUTE FUNCTION gisclient_34.enc_pwd();


--
-- Name: catalog catalog_conntype_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.catalog
    ADD CONSTRAINT catalog_conntype_fkey FOREIGN KEY (connection_type) REFERENCES gisclient_34.e_conntype(conntype_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: catalog_import catalog_import_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.catalog_import
    ADD CONSTRAINT catalog_import_project_name_fkey FOREIGN KEY (project_name) REFERENCES gisclient_34.project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: catalog catalog_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.catalog
    ADD CONSTRAINT catalog_project_name_fkey FOREIGN KEY (project_name) REFERENCES gisclient_34.project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: class class_layer_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.class
    ADD CONSTRAINT class_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES gisclient_34.layer(layer_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: document document_doc_parent_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.document
    ADD CONSTRAINT document_doc_parent_id_fkey FOREIGN KEY (doc_parent_id) REFERENCES gisclient_34.document(doc_id);


--
-- Name: e_form e_form_level_destination_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.e_form
    ADD CONSTRAINT e_form_level_destination_fkey FOREIGN KEY (level_destination) REFERENCES gisclient_34.e_level(id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: field field_fieldtype_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.field
    ADD CONSTRAINT field_fieldtype_id_fkey FOREIGN KEY (fieldtype_id) REFERENCES gisclient_34.e_fieldtype(fieldtype_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: field_groups field_groups_field_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.field_groups
    ADD CONSTRAINT field_groups_field_id_fkey FOREIGN KEY (field_id) REFERENCES gisclient_34.field(field_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: field field_layer_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.field
    ADD CONSTRAINT field_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES gisclient_34.layer(layer_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: form_level form_level_form_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.form_level
    ADD CONSTRAINT form_level_form_fkey FOREIGN KEY (form) REFERENCES gisclient_34.e_form(id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: form_level form_level_level_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.form_level
    ADD CONSTRAINT form_level_level_fkey FOREIGN KEY (level) REFERENCES gisclient_34.e_level(id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: group_authfilter group_authfilter_filter_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.group_authfilter
    ADD CONSTRAINT group_authfilter_filter_id_fkey FOREIGN KEY (filter_id) REFERENCES gisclient_34.authfilter(filter_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: group_authfilter group_authfilter_gropuname_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.group_authfilter
    ADD CONSTRAINT group_authfilter_gropuname_fkey FOREIGN KEY (groupname) REFERENCES gisclient_34.groups(groupname) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: localization i18nfield_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.localization
    ADD CONSTRAINT i18nfield_fkey FOREIGN KEY (i18nf_id) REFERENCES gisclient_34.i18n_field(i18nf_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: localization language_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.localization
    ADD CONSTRAINT language_id_fkey FOREIGN KEY (language_id) REFERENCES gisclient_34.e_language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: project_languages language_id_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.project_languages
    ADD CONSTRAINT language_id_project_name_fkey FOREIGN KEY (project_name) REFERENCES gisclient_34.project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: layer_authfilter layer_authfilter_filter_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.layer_authfilter
    ADD CONSTRAINT layer_authfilter_filter_id_fkey FOREIGN KEY (filter_id) REFERENCES gisclient_34.authfilter(filter_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: layer_authfilter layer_authfilter_layer_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.layer_authfilter
    ADD CONSTRAINT layer_authfilter_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES gisclient_34.layer(layer_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: layer layer_catalog_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.layer
    ADD CONSTRAINT layer_catalog_id_fkey FOREIGN KEY (catalog_id) REFERENCES gisclient_34.catalog(catalog_id) ON UPDATE CASCADE;


--
-- Name: layer_groups layer_groups_layer_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.layer_groups
    ADD CONSTRAINT layer_groups_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES gisclient_34.layer(layer_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: layer layer_layergroup_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.layer
    ADD CONSTRAINT layer_layergroup_id_fkey FOREIGN KEY (layergroup_id) REFERENCES gisclient_34.layergroup(layergroup_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: layergroup layergroup_theme_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.layergroup
    ADD CONSTRAINT layergroup_theme_id_fkey FOREIGN KEY (theme_id) REFERENCES gisclient_34.theme(theme_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: layergroup layergroup_wmsversion_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.layergroup
    ADD CONSTRAINT layergroup_wmsversion_id_fkey FOREIGN KEY (wmsversion_id) REFERENCES gisclient_34.e_wmsversion(wmsversion_id);


--
-- Name: link link_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.link
    ADD CONSTRAINT link_project_name_fkey FOREIGN KEY (project_name) REFERENCES gisclient_34.project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: localization localization_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.localization
    ADD CONSTRAINT localization_project_name_fkey FOREIGN KEY (project_name) REFERENCES gisclient_34.project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: mapset_groups mapset_groups_groupname_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.mapset_groups
    ADD CONSTRAINT mapset_groups_groupname_fkey FOREIGN KEY (groupname) REFERENCES gisclient_34.groups(groupname) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: mapset_groups mapset_groups_mapset_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.mapset_groups
    ADD CONSTRAINT mapset_groups_mapset_name_fkey FOREIGN KEY (mapset_name) REFERENCES gisclient_34.mapset(mapset_name) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: mapset_layergroup mapset_layergroup_layergroup_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.mapset_layergroup
    ADD CONSTRAINT mapset_layergroup_layergroup_id_fkey FOREIGN KEY (layergroup_id) REFERENCES gisclient_34.layergroup(layergroup_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: mapset_layergroup mapset_layergroup_mapset_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.mapset_layergroup
    ADD CONSTRAINT mapset_layergroup_mapset_name_fkey FOREIGN KEY (mapset_name) REFERENCES gisclient_34.mapset(mapset_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: mapset mapset_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.mapset
    ADD CONSTRAINT mapset_project_name_fkey FOREIGN KEY (project_name) REFERENCES gisclient_34.project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: style pattern_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.style
    ADD CONSTRAINT pattern_id_fkey FOREIGN KEY (pattern_id) REFERENCES gisclient_34.e_pattern(pattern_id) ON UPDATE CASCADE;


--
-- Name: project_srs project_srs_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.project_srs
    ADD CONSTRAINT project_srs_project_name_fkey FOREIGN KEY (project_name) REFERENCES gisclient_34.project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: qt_link qt_link_link_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.qt_link
    ADD CONSTRAINT qt_link_link_id_fkey FOREIGN KEY (link_id) REFERENCES gisclient_34.link(link_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: qt_link qt_link_qt_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.qt_link
    ADD CONSTRAINT qt_link_qt_id_fkey FOREIGN KEY (qt_id) REFERENCES gisclient_34.qt(qt_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: qt_field qtfield_fieldtype_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.qt_field
    ADD CONSTRAINT qtfield_fieldtype_id_fkey FOREIGN KEY (fieldtype_id) REFERENCES gisclient_34.e_fieldtype(fieldtype_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: qt_field qtfield_qt_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.qt_field
    ADD CONSTRAINT qtfield_qt_id_fkey FOREIGN KEY (qt_id) REFERENCES gisclient_34.qt(qt_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: qt_relation qtrelation_catalog_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.qt_relation
    ADD CONSTRAINT qtrelation_catalog_fkey FOREIGN KEY (catalog_id) REFERENCES gisclient_34.catalog(catalog_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: qt_relation qtrelation_qt_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.qt_relation
    ADD CONSTRAINT qtrelation_qt_id_fkey FOREIGN KEY (qt_id) REFERENCES gisclient_34.qt(qt_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: relation relation_catalog_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.relation
    ADD CONSTRAINT relation_catalog_fkey FOREIGN KEY (catalog_id) REFERENCES gisclient_34.catalog(catalog_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: relation relation_layer_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.relation
    ADD CONSTRAINT relation_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES gisclient_34.layer(layer_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: saved_filter saved_filter_layer_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.saved_filter
    ADD CONSTRAINT saved_filter_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES gisclient_34.layer(layer_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: saved_filter saved_filter_mapset_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.saved_filter
    ADD CONSTRAINT saved_filter_mapset_name_fkey FOREIGN KEY (mapset_name) REFERENCES gisclient_34.mapset(mapset_name) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: saved_filter saved_filter_username_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.saved_filter
    ADD CONSTRAINT saved_filter_username_fkey FOREIGN KEY (username) REFERENCES gisclient_34.users(username) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: selgroup_layer selgroup_layer_layer_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.selgroup_layer
    ADD CONSTRAINT selgroup_layer_layer_id_fkey FOREIGN KEY (layer_id) REFERENCES gisclient_34.layer(layer_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: selgroup_layer selgroup_layer_selgroup_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.selgroup_layer
    ADD CONSTRAINT selgroup_layer_selgroup_fkey FOREIGN KEY (selgroup_id) REFERENCES gisclient_34.selgroup(selgroup_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: selgroup selgroup_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.selgroup
    ADD CONSTRAINT selgroup_project_name_fkey FOREIGN KEY (project_name) REFERENCES gisclient_34.project(project_name) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: style style_class_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.style
    ADD CONSTRAINT style_class_id_fkey FOREIGN KEY (class_id) REFERENCES gisclient_34.class(class_id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: symbol symbol_symbolcategory_id_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.symbol
    ADD CONSTRAINT symbol_symbolcategory_id_fkey FOREIGN KEY (symbolcategory_id) REFERENCES gisclient_34.e_symbolcategory(symbolcategory_id) MATCH FULL ON UPDATE CASCADE;


--
-- Name: theme theme_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.theme
    ADD CONSTRAINT theme_project_name_fkey FOREIGN KEY (project_name) REFERENCES gisclient_34.project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: theme theme_symbol_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.theme
    ADD CONSTRAINT theme_symbol_name_fkey FOREIGN KEY (symbol_name) REFERENCES gisclient_34.symbol(symbol_name) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: theme_version theme_version_fk; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.theme_version
    ADD CONSTRAINT theme_version_fk FOREIGN KEY (theme_id) REFERENCES gisclient_34.theme(theme_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: user_group user_group_groupname_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.user_group
    ADD CONSTRAINT user_group_groupname_fkey FOREIGN KEY (groupname) REFERENCES gisclient_34.groups(groupname) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: user_group user_group_username_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.user_group
    ADD CONSTRAINT user_group_username_fkey FOREIGN KEY (username) REFERENCES gisclient_34.users(username) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: usercontext usercontext_mapset_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.usercontext
    ADD CONSTRAINT usercontext_mapset_name_fkey FOREIGN KEY (mapset_name) REFERENCES gisclient_34.mapset(mapset_name) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: project_admin username_project_name_fkey; Type: FK CONSTRAINT; Schema: gisclient_34; Owner: -
--

ALTER TABLE ONLY gisclient_34.project_admin
    ADD CONSTRAINT username_project_name_fkey FOREIGN KEY (project_name) REFERENCES gisclient_34.project(project_name) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--
