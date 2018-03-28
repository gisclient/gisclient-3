-- Function: gw_assegna_valore(integer)

-- DROP FUNCTION gw_assegna_valore(integer);

CREATE OR REPLACE FUNCTION gw_assegna_valore(campo integer)
  RETURNS text AS
$BODY$
BEGIN
	case campo
	    when 1 then return 'Si'; 
	    when 3 then return 'No';
	    else return 'Non determinabile';     
	end case;
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION gw_assegna_valore(integer)
  OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_assegna_valore(integer) TO postgres;
GRANT EXECUTE ON FUNCTION gw_assegna_valore(integer) TO public;
GRANT EXECUTE ON FUNCTION gw_assegna_valore(integer) TO mapserver;
--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_check_value(character varying, character varying)

-- DROP FUNCTION gw_check_value(character varying, character varying);

CREATE OR REPLACE FUNCTION gw_check_value(
    campo character varying,
    usr_stringa character varying)
  RETURNS text AS
$BODY$
BEGIN
	if campo then return campo;
		else return usr_stringa;
	end if;
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION gw_check_value(character varying, character varying)
  OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_check_value(character varying, character varying) TO public;
GRANT EXECUTE ON FUNCTION gw_check_value(character varying, character varying) TO postgres;
GRANT EXECUTE ON FUNCTION gw_check_value(character varying, character varying) TO mapserver;

--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_check_value(integer)

-- DROP FUNCTION gw_check_value(integer);

CREATE OR REPLACE FUNCTION gw_check_value(campo integer)
  RETURNS text AS
$BODY$
BEGIN
	if campo <> 0 then return campo;
		else return 'Cod. Amga non presente';
	end if;
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION gw_check_value(integer)
  OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_check_value(integer) TO postgres;
GRANT EXECUTE ON FUNCTION gw_check_value(integer) TO public;
GRANT EXECUTE ON FUNCTION gw_check_value(integer) TO mapserver;
--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_check_value(integer, character varying)

-- DROP FUNCTION gw_check_value(integer, character varying);

CREATE OR REPLACE FUNCTION gw_check_value(
    campo integer,
    usr_stringa character varying)
  RETURNS text AS
$BODY$
BEGIN
	if campo <> 0 then return campo;
		else return usr_stringa;
	end if;
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION gw_check_value(integer, character varying)
  OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_check_value(integer, character varying) TO public;
GRANT EXECUTE ON FUNCTION gw_check_value(integer, character varying) TO postgres;
GRANT EXECUTE ON FUNCTION gw_check_value(integer, character varying) TO mapserver;
-----------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_checkuser(character varying, character varying)

-- DROP FUNCTION gw_checkuser(character varying, character varying);

CREATE OR REPLACE FUNCTION gw_checkuser(
    username character varying,
    pwd character varying)
  RETURNS integer AS
$BODY$
DECLARE
	query text;
	presente integer;
	enc_pwd varchar;
BEGIN
	if (coalesce(username,'')='' or coalesce(pwd,'')='') then
		return -1;
	end if;
	select count(*) into presente from pg_user where usename=username;
	if (presente=1) then
		return 1;
	else
		enc_pwd=md5(pwd);
		query:='CREATE ROLE '||username||' LOGIN ENCRYPTED PASSWORD '''||enc_pwd||''' NOSUPERUSER NOINHERIT NOCREATEDB NOCREATEROLE';
		execute query;
		select count(*) into presente from pg_user where usename=username;
		if presente=1 then
			return 2;
		else
			return -2;
		end if;
	end if;
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION gw_checkuser(character varying, character varying)
  OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_checkuser(character varying, character varying) TO postgres;
GRANT EXECUTE ON FUNCTION gw_checkuser(character varying, character varying) TO public;
GRANT EXECUTE ON FUNCTION gw_checkuser(character varying, character varying) TO mapserver;

--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_choose_value(double precision, double precision)

-- DROP FUNCTION gw_choose_value(double precision, double precision);

CREATE OR REPLACE FUNCTION gw_choose_value(
    campo_a double precision,
    campo_b double precision)
  RETURNS double precision AS
$BODY$
BEGIN
	if campo_a > 0 then return campo_a; 
		else return campo_b;
	end if;
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION gw_choose_value(double precision, double precision)
  OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_choose_value(double precision, double precision) TO public;
GRANT EXECUTE ON FUNCTION gw_choose_value(double precision, double precision) TO postgres;
GRANT EXECUTE ON FUNCTION gw_choose_value(double precision, double precision) TO mapserver;
--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_concat_fields(character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying)

-- DROP FUNCTION gw_concat_fields(character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying);

CREATE OR REPLACE FUNCTION gw_concat_fields(
    c1 character varying,
    c2 character varying,
    c3 character varying,
    c4 character varying,
    c5 character varying,
    c6 character varying,
    c7 character varying,
    c8 character varying)
  RETURNS text AS
$BODY$
DECLARE
	s1 text; s2 text; s3 text; s4 text; s5 text; s6 text; s7 text; s8 text;	s_tot text;
BEGIN
	s1:=c1; s2:=c2; s3:=c3; s4:=c4; s5:=c5; s6:=c6; s7:=c7; s8:=c8;
	--controllo che non esistano valori nulli (non inizializzati)
	if s1 is null then s1:='';
	end if;
	if s2 is null then s2:='';
        end if;
	if s3 is null then s3:='';
	end if;
	if s4 is null then s4:='';
        end if;
       	if s5 is null then s5:='';
	end if;
	if s6 is null then s6:='';
        end if;
        if s7 is null then s7:='';
	end if;
	if s8 is null then s8:='';
        end if;
        -- scrivo la stringa risultante
        s_tot:=(s1 || '-' || s2 || '-' || s3 || '-' || s4 || '-' || s5 || '-' || s6 || '-' || s7 || '-' || s8);
        if substring(s_tot from 1 for 1) = '-' then s_tot:=substring(s_tot from 2 for 200);
        end if;
        return s_tot;
	
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION gw_concat_fields(character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying)
  OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_concat_fields(character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying) TO postgres;
GRANT EXECUTE ON FUNCTION gw_concat_fields(character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying) TO public;
GRANT EXECUTE ON FUNCTION gw_concat_fields(character varying, character varying, character varying, character varying, character varying, character varying, character varying, character varying) TO mapserver;
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_concat_valori(character varying, character varying)

-- DROP FUNCTION gw_concat_valori(character varying, character varying);

CREATE OR REPLACE FUNCTION gw_concat_valori(
    c1 character varying,
    c2 character varying)
  RETURNS text AS
$BODY$
DECLARE
	s1 text; s2 text; s_tot text;
BEGIN
	s1:=c1;s2:=c2;
	--verifico se esiste qualche stringa nulla
	if s1 is null then s1:=''; end if;
	if s2 is null then s2:=''; end if;
	--costruisco la stringa risultatnte
        s_tot:=(s1 || '-' || s2);
        if substring(s_tot from 1 for 1) = '-' then s_tot:=substring(s_tot from 2 for 200);
        end if;
        return s_tot;
	
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION gw_concat_valori(character varying, character varying)
  OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_concat_valori(character varying, character varying) TO postgres;
GRANT EXECUTE ON FUNCTION gw_concat_valori(character varying, character varying) TO public;
GRANT EXECUTE ON FUNCTION gw_concat_valori(character varying, character varying) TO mapserver;
-----------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_concat_valori(integer, integer)

-- DROP FUNCTION gw_concat_valori(integer, integer);

CREATE OR REPLACE FUNCTION gw_concat_valori(
    c1 integer,
    c2 integer)
  RETURNS text AS
$BODY$
DECLARE
	s1 text;
	s2 text;
	s_tot text;
BEGIN
	s1:=(c1::text);
	if s1 is null then s1:='';
	end if;
	s2:=(c2::text);
	if s2 is null then s2:='';
        end if;
        s_tot:=(s1 || '-' || s2);
        if substring(s_tot from 1 for 1) = '-' then s_tot:=substring(s_tot from 2 for 200);
        end if;
        return s_tot;
	
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION gw_concat_valori(integer, integer)
  OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_concat_valori(integer, integer) TO postgres;
GRANT EXECUTE ON FUNCTION gw_concat_valori(integer, integer) TO public;
GRANT EXECUTE ON FUNCTION gw_concat_valori(integer, integer) TO mapserver;
-----------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_create_centroid_4_street(character varying, integer)

-- DROP FUNCTION gw_create_centroid_4_street(character varying, integer);

CREATE OR REPLACE FUNCTION gw_create_centroid_4_street(
    finestra character varying,
    st_coord integer)
  RETURNS geometry AS
$BODY$
DECLARE
   a text;
   lung integer;
   lungmin integer;
   lungmax integer;
   pos integer;
   posmin integer;
   posmax integer;
   pmin text;
   pmax text;
   xmin double precision;
   ymin double precision;
   xmax double precision;
   ymax double precision;

BEGIN
	if finestra is not null then
		a =regexp_replace(regexp_replace(finestra, ',[0-9.E-]*;',';'), ',[0-9.E-]*$', '');
		lung = length(a);
		pos = position(';' in a);
		pmin = substring(a from 0 for pos);
		pmax = substring(a from (pos + 1) for (lung - pos));
		lungmin = length(pmin);
		posmin = position (',' in pmin);
		xmin = (substring(pmin from 0 for posmin))::double precision;
		ymin = (substring(pmin from (posmin + 1) for (lungmin - posmin)))::double precision;
		lungmax = length(pmax);
		posmax = position (',' in pmax);
		xmax = (substring(pmax from 0 for posmax))::double precision;
		ymax = (substring(pmax from (posmax + 1) for (lungmax - posmax)))::double precision;
		return ST_setsrid(ST_centroid(ST_MakeBox2D(ST_Point(xmin , ymin), ST_Point(xmax , ymax))), st_coord);			
	else 	
		return ST_setsrid(ST_centroid(ST_MakeBox2D(ST_Point(0.0,0.0), ST_Point(0.0,0.0))), st_coord);
	end if;
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION gw_create_centroid_4_street(character varying, integer)
  OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_create_centroid_4_street(character varying, integer) TO public;
GRANT EXECUTE ON FUNCTION gw_create_centroid_4_street(character varying, integer) TO postgres;
GRANT EXECUTE ON FUNCTION gw_create_centroid_4_street(character varying, integer) TO mapserver;
-----------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_droptable(character varying, character varying)

-- DROP FUNCTION gw_droptable(character varying, character varying);

CREATE OR REPLACE FUNCTION gw_droptable(sk character varying, tb character varying)
  RETURNS text AS
$BODY$
DECLARE
schema_name varchar;
BEGIN
IF coalesce(tb,'')='' THEN
return 'TABELLA NON DEFINITA';
END IF;
IF coalesce(sk,'')='' THEN
schema_name:='public';
ELSE
schema_name:=sk;
END IF;
BEGIN
EXECUTE 'DROP TABLE ' || quote_ident(schema_name) || '.' || quote_ident(tb) || ' CASCADE';
EXCEPTION
WHEN undefined_table THEN
return 'LA TABELLA '||schema_name||'.'||tb||' NON ESISTE';
WHEN dependent_objects_still_exist THEN
return 'ALTRI OGGETTI DIPENDONO DALLA TABELLA '||schema_name||'.'||tb;
END;
return 'TABELLA '||schema_name||'.'||tb||' CANCELLATA';
END;
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100;
ALTER FUNCTION gw_droptable(character varying, character varying) OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_droptable(character varying, character varying) TO public;
GRANT EXECUTE ON FUNCTION gw_droptable(character varying, character varying) TO postgres;
GRANT EXECUTE ON FUNCTION gw_droptable(character varying, character varying) TO mapserver;
--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_dropview(character varying, character varying)

-- DROP FUNCTION gw_dropview(character varying, character varying);

CREATE OR REPLACE FUNCTION gw_dropview(sk character varying, vw character varying)
  RETURNS text AS
$BODY$
DECLARE
schema_name varchar;
BEGIN
IF coalesce(vw,'')='' THEN
return 'VISTA NON DEFINITA';
END IF;
IF coalesce(sk,'')='' THEN
schema_name:='public';
ELSE
schema_name:=sk;
END IF;
BEGIN
EXECUTE 'DROP VIEW ' || quote_ident(schema_name) || '.' || quote_ident(vw);
EXCEPTION
WHEN undefined_table THEN
return 'LA VISTA '||schema_name||'.'||vw||' NON ESISTE';
WHEN dependent_objects_still_exist THEN
return 'ALTRI OGGETTI DIPENDONO DALLA VISTA '||schema_name||'.'||vw;
END;
return 'VISTA '||schema_name||'.'||vw||' CANCELLATA';
END;
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100;
ALTER FUNCTION gw_dropview(character varying, character varying) OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_dropview(character varying, character varying) TO public;
GRANT EXECUTE ON FUNCTION gw_dropview(character varying, character varying) TO postgres;
GRANT EXECUTE ON FUNCTION gw_dropview(character varying, character varying) TO mapserver;
--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_grantexecute(character varying, character varying, integer)

-- DROP FUNCTION gw_grantexecute(character varying, character varying, integer);

CREATE OR REPLACE FUNCTION gw_grantexecute(usr character varying, sk character varying, rev integer)
  RETURNS void AS
$BODY$
DECLARE 
	rec record;
	rec1 record;
	fld text[];
	query varchar;
	fname varchar;
BEGIN
	for rec in select specific_name,routine_name from information_schema.routines where routine_schema=sk and not routine_name ilike 'gw_grant%' loop
		fld:='{}';
		for rec1 in select udt_name from information_schema.parameters where specific_name=rec.specific_name and specific_schema=sk order by ordinal_position loop
			fld:=fld||rec1.udt_name::text;
		end loop;
		fname:=sk||'.'||rec.routine_name||'('||array_to_string(fld,',')||')';
		if (rev > 0) then
			query:='GRANT EXECUTE ON FUNCTION '||fname||' TO '||usr||';';
		else
			query:='REVOKE EXECUTE ON FUNCTION '||fname||' TO '||usr||';';
		end if;
		
		begin
			execute query;
			exception when undefined_function then
				raise notice 'Funzione % non trovata',fname;
		end;
	end loop;
END
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100;
ALTER FUNCTION gw_grantexecute(character varying, character varying, integer) OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_grantexecute(character varying, character varying, integer) TO public;
GRANT EXECUTE ON FUNCTION gw_grantexecute(character varying, character varying, integer) TO postgres;
GRANT EXECUTE ON FUNCTION gw_grantexecute(character varying, character varying, integer) TO mapserver;
--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_grantselect(character varying, character varying, integer)

-- DROP FUNCTION gw_grantselect(character varying, character varying, integer);

CREATE OR REPLACE FUNCTION gw_grantselect(usr character varying, sk character varying, rev integer)
  RETURNS void AS
$BODY$
DECLARE 
	rec record;
	query varchar;
BEGIN
	if ( rev > 0) then
		query:='GRANT USAGE ON SCHEMA '||sk||' TO '||usr||';';
	else
		query:='REVOKE USAGE ON SCHEMA '||sk||' FROM '||usr||';';
	end if;
	execute query;
	for rec in select table_name from information_schema.tables where table_schema=sk and table_name not in (select distinct table_name from information_schema.table_privileges where table_schema=sk and grantee=usr and privilege_type='SELECT') order by table_name loop
		if(rev > 0) then
			query:='GRANT SELECT ON TABLE '||sk||'."'||rec.table_name||'" TO '||usr||';';
		else
			query:='REVOKE SELECT ON TABLE '||sk||'."'||rec.table_name||'" FROM '||usr||';';
		end if;
		begin
			execute query;
			exception when undefined_table then
				raise notice 'Tabella %.% non trovata',sk,rec.table_name;
		end;
	end loop;
END
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100;
ALTER FUNCTION gw_grantselect(character varying, character varying, integer) OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_grantselect(character varying, character varying, integer) TO postgres;
GRANT EXECUTE ON FUNCTION gw_grantselect(character varying, character varying, integer) TO public;
GRANT EXECUTE ON FUNCTION gw_grantselect(character varying, character varying, integer) TO mapserver;
--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_grantselect_all(character varying, character varying, integer)

-- DROP FUNCTION gw_grantselect_all(character varying, character varying, integer);

CREATE OR REPLACE FUNCTION gw_grantselect_all(usr character varying, sk character varying, rev integer)
  RETURNS void AS
$BODY$
DECLARE 
	rec record;
	query varchar;
BEGIN
	if ( rev > 0) then
		query:='GRANT USAGE ON SCHEMA '||sk||' TO '||usr||';';
	else
		query:='REVOKE USAGE ON SCHEMA '||sk||' FROM '||usr||';';
	end if;
	execute query;
	for rec in select table_name from information_schema.tables where table_schema=sk and table_name not in (select distinct table_name from information_schema.table_privileges where table_schema=sk and grantee=usr and privilege_type='SELECT') order by table_name loop
		if(rev > 0) then
			query:='GRANT SELECT ON TABLE '||sk||'."'||rec.table_name||'" TO '||usr||';';
		else
			query:='REVOKE SELECT ON TABLE '||sk||'."'||rec.table_name||'" FROM '||usr||';';
		end if;
		begin
			execute query;
			exception when undefined_table then
				raise notice 'Tabella %.% non trovata',sk,rec.table_name;
		end;
	end loop;
		for rec in select routine_name from information_schema.routines where routine_schema=sk and routine_name not in (select distinct routine_name from information_schema.routine_privileges where routine_schema=sk and grantee=usr and privilege_type='SELECT') order by routine_name loop
		if(rev > 0) then
			query:='GRANT SELECT ON FUNCTION '||sk||'.'||rec.routine_name||' TO '||usr||';';
		else
			query:='REVOKE SELECT ON FUNCTION '||sk||'.'||rec.routine_name||' FROM '||usr||';';
		end if;
		begin
			execute query;
			exception when undefined_table then
				raise notice 'Tabella %.% non trovata',sk,rec.routine_name;
		end;
	end loop;
END
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100;
ALTER FUNCTION gw_grantselect_all(character varying, character varying, integer) OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_grantselect_all(character varying, character varying, integer) TO postgres;
GRANT EXECUTE ON FUNCTION gw_grantselect_all(character varying, character varying, integer) TO public;
GRANT EXECUTE ON FUNCTION gw_grantselect_all(character varying, character varying, integer) TO mapserver;
--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_subst_value(character varying, integer, character varying)

-- DROP FUNCTION gw_subst_value(character varying, integer, character varying);

CREATE OR REPLACE FUNCTION gw_subst_value(campo character varying, inizio integer, usr_stringa character varying)
  RETURNS text AS
$BODY$
BEGIN
	if campo is not null then return substring(campo from inizio for (length(campo) + 1 - inizio));
		else return usr_stringa;
	end if;
END
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100;
ALTER FUNCTION gw_subst_value(character varying, integer, character varying) OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_subst_value(character varying, integer, character varying) TO public;
GRANT EXECUTE ON FUNCTION gw_subst_value(character varying, integer, character varying) TO postgres;
GRANT EXECUTE ON FUNCTION gw_subst_value(character varying, integer, character varying) TO mapserver;
--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_tipo_prot_cat(integer)

-- DROP FUNCTION gw_tipo_prot_cat(integer);

CREATE OR REPLACE FUNCTION gw_tipo_prot_cat(campo integer)
  RETURNS text AS
$BODY$
BEGIN
	case campo
	    when 1 then return 'ANODO'; 
	    when 2 then return 'SISTEMA';
	    when 3 then return '0';
	    else return NULL;     
	end case;
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION gw_tipo_prot_cat(integer)
  OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_tipo_prot_cat(integer) TO public;
GRANT EXECUTE ON FUNCTION gw_tipo_prot_cat(integer) TO postgres;
GRANT EXECUTE ON FUNCTION gw_tipo_prot_cat(integer) TO mapserver;
-----------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_transf_bool(integer)

-- DROP FUNCTION gw_transf_bool(integer);

CREATE OR REPLACE FUNCTION gw_transf_bool(campo integer)
  RETURNS text AS
$BODY$
BEGIN
	if campo <> 1 then return 'NO'; 
		else return 'SI';
	end if;
END
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100;
ALTER FUNCTION gw_transf_bool(integer) OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_transf_bool(integer) TO public;
GRANT EXECUTE ON FUNCTION gw_transf_bool(integer) TO postgres;
GRANT EXECUTE ON FUNCTION gw_transf_bool(integer) TO mapserver;
--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_transf_bool(character varying)

-- DROP FUNCTION gw_transf_bool(character varying);

CREATE OR REPLACE FUNCTION gw_transf_bool(campo character varying)
  RETURNS text AS
$BODY$
BEGIN
	if campo = 'S' or campo = 's' or campo = 'T' or campo = 't' then return 'SI'; 
		else return 'NO';
	end if;
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION gw_transf_bool(character varying)
  OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_transf_bool(character varying) TO postgres;
GRANT EXECUTE ON FUNCTION gw_transf_bool(character varying) TO public;
GRANT EXECUTE ON FUNCTION gw_transf_bool(character varying) TO mapserver;
-----------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_transf_orientation(double precision, double precision, double precision)

-- DROP FUNCTION gw_transf_orientation(double precision, double precision, double precision);

CREATE OR REPLACE FUNCTION gw_transf_orientation(
    angle double precision,
    delta_angle double precision,
    lung_quote double precision)
  RETURNS double precision AS
$BODY$
BEGIN
	if lung_quote <= 4.00 then return angle;
		else return (angle + delta_angle);
	end if;
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION gw_transf_orientation(double precision, double precision, double precision)
  OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_transf_orientation(double precision, double precision, double precision) TO postgres;
GRANT EXECUTE ON FUNCTION gw_transf_orientation(double precision, double precision, double precision) TO public;
GRANT EXECUTE ON FUNCTION gw_transf_orientation(double precision, double precision, double precision) TO mapserver;
-----------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_transf_string(character varying, character varying)

-- DROP FUNCTION gw_transf_string(character varying, character varying);

CREATE OR REPLACE FUNCTION gw_transf_string(campo character varying, sottostringa character varying)
  RETURNS text AS
$BODY$
BEGIN
	if (position (sottostringa in campo) - 1) < 0 then return campo; 
		else return substring (campo from 1 for (position (sottostringa in campo) - 1));
	end if;
END
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100;
ALTER FUNCTION gw_transf_string(character varying, character varying) OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_transf_string(character varying, character varying) TO public;
GRANT EXECUTE ON FUNCTION gw_transf_string(character varying, character varying) TO postgres;
GRANT EXECUTE ON FUNCTION gw_transf_string(character varying, character varying) TO mapserver;
--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_transform_geometry(character varying, character varying, character varying, integer)

-- DROP FUNCTION gw_transform_geometry(character varying, character varying, character varying, integer);

CREATE OR REPLACE FUNCTION gw_transform_geometry(
    schema_name character varying,
    table_name character varying,
    column_name character varying,
    new_srid integer)
  RETURNS text AS
$BODY$
DECLARE
	schema_name alias for $1;
	table_name alias for $2;
	column_name alias for $3;
	new_srid alias for $4;
	cname varchar;

BEGIN

	-- Update ref from geometry_columns table
	EXECUTE 'UPDATE geometry_columns SET SRID = ' || new_srid || 
		' where f_table_schema = ' ||
		quote_literal(schema_name) || ' and f_table_name = ' ||
		quote_literal(table_name)  || ' and f_geometry_column = ' ||
		quote_literal(column_name);
	
	-- Make up constraint name
	cname = 'enforce_srid_'  || column_name;

	-- Drop enforce_srid constraint
	EXECUTE 'ALTER TABLE ' || quote_ident(schema_name) ||
		'.' || quote_ident(table_name) ||
		' DROP constraint ' || quote_ident(cname);

	-- Update geometries coordinate
	EXECUTE 'UPDATE ' || quote_ident(schema_name) ||
		'.' || quote_ident(table_name) ||
		' SET ' || quote_ident(column_name) ||
		' = st_transform(' || quote_ident(column_name) ||
		', ' || new_srid || ')';
           
	-- Update geometries SRID
	EXECUTE 'UPDATE ' || quote_ident(schema_name) ||
		'.' || quote_ident(table_name) ||
		' SET ' || quote_ident(column_name) ||
		' = setSRID(' || quote_ident(column_name) ||
		', ' || new_srid || ')';

	-- Reset enforce_srid constraint
	EXECUTE 'ALTER TABLE ' || quote_ident(schema_name) ||
		'.' || quote_ident(table_name) ||
		' ADD constraint ' || quote_ident(cname) ||
		' CHECK (srid(' || quote_ident(column_name) ||
		') = ' || new_srid || ')';

	RETURN schema_name || '.' || table_name || '.' || column_name ||' SRID changed to ' || new_srid;
	
END;
$BODY$
  LANGUAGE plpgsql VOLATILE STRICT
  COST 100;
ALTER FUNCTION gw_transform_geometry(character varying, character varying, character varying, integer)
  OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_transform_geometry(character varying, character varying, character varying, integer) TO public;
GRANT EXECUTE ON FUNCTION gw_transform_geometry(character varying, character varying, character varying, integer) TO postgres;
GRANT EXECUTE ON FUNCTION gw_transform_geometry(character varying, character varying, character varying, integer) TO mapserver;
-----------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_transform_geometry(integer, character varying, character varying)

-- DROP FUNCTION gw_transform_geometry(integer, character varying, character varying);

CREATE OR REPLACE FUNCTION gw_transform_geometry(
    new_srid integer,
    schema_name character varying DEFAULT NULL::character varying,
    table_name character varying DEFAULT NULL::character varying)
  RETURNS void AS
$BODY$
DECLARE
	new_srid alias for $1;
	query text;
	rec record;
   curs1 refcursor;
BEGIN
	if coalesce(new_srid, 0) = 0 then
		--raise notice 'NO new_srid';
		return;
	end if;
   
   query := 'select f_table_schema as sc,f_table_name as tb,f_geometry_column as col from geometry_columns inner join information_schema.tables on (f_table_schema=table_schema and f_table_name=table_name) where table_type=''BASE TABLE''';

   if schema_name IS NOT NULL THEN
		query := query || 'and table_schema = ' || quote_literal(schema_name);
		if table_name IS NOT NULL THEN
			query := query || 'and table_name = ' || quote_literal(table_name);
		end if;
	end if;

	OPEN curs1 FOR EXECUTE query;
	LOOP
		FETCH curs1 INTO rec;
		EXIT WHEN NOT FOUND;
		--UpdateGeometrySRID(rec.sc,rec.tb,rec.col,new_srid);
		query:='select gw_transform_geometry('''||rec.sc||''','''||rec.tb||''','''||rec.col||''','||new_srid||');';
		raise notice '%',query;
      execute query;
		--end;
	END LOOP;
	CLOSE curs1;
   
	return;
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION gw_transform_geometry(integer, character varying, character varying)
  OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_transform_geometry(integer, character varying, character varying) TO public;
GRANT EXECUTE ON FUNCTION gw_transform_geometry(integer, character varying, character varying) TO postgres;
GRANT EXECUTE ON FUNCTION gw_transform_geometry(integer, character varying, character varying) TO mapserver;
-----------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_truncate(character varying)

-- DROP FUNCTION gw_truncate(character varying);

CREATE OR REPLACE FUNCTION gw_truncate(_schema character varying)
  RETURNS void AS
$BODY$
DECLARE 
	rec record;
	query varchar;
BEGIN
	for rec in select table_name from information_schema.tables where table_schema=_schema and table_type='BASE TABLE' loop
           query:='DELETE FROM "' || _schema || '"."' || rec.table_name || '" WHERE gs_id<>0;';
           begin
	   execute query;
	   exception when undefined_table then
	   raise notice 'Tabella %.% non trovata',sk,rec.table_name;
	   end;
	end loop;
END
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100;
ALTER FUNCTION gw_truncate(character varying) OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_truncate(character varying) TO postgres;
GRANT EXECUTE ON FUNCTION gw_truncate(character varying) TO public;
GRANT EXECUTE ON FUNCTION gw_truncate(character varying) TO mapserver;
--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_update_srid(integer, text)

-- DROP FUNCTION gw_update_srid(integer, text);

CREATE OR REPLACE FUNCTION gw_update_srid(srid integer, m_schema text)
  RETURNS void AS
$BODY$
DECLARE
	query text;
	query2 text;
	rec record;
	curs1 refcursor;
BEGIN
	if coalesce(srid,0) = 0 then
		--raise notice 'NO srid';
		return;
	end if;

	query := 'select f_table_schema as sc,f_table_name as tb,f_geometry_column as col from geometry_columns inner join information_schema.tables on (f_table_schema=table_schema and f_table_name=table_name) where table_type=''BASE TABLE'' and not f_geometry_column like ''%_cs''';
	if m_schema IS NOT NULL THEN
		query := query || 'and table_schema = ' || quote_literal(m_schema);
	end if;
		
	OPEN curs1 FOR EXECUTE query;
	LOOP
		FETCH curs1 INTO rec;
		EXIT WHEN NOT FOUND;
		--UpdateGeometrySRID(rec.sc,rec.tb,rec.col,srid);
		query:='select UpdateGeometrySRID('''||rec.sc||''','''||rec.tb||''','''||rec.col||''','||srid||');';
		raise notice '%',query;
		--begin
		       execute query;
		--exception when undefined_object then
		--query2:='ALTER TABLE ' || rec.sc ||'.'|| rec.tb || ' ADD CONSTRAINT enforce_srid_the_geom CHECK (srid('|| rec.col ||') = -1);';
		--raise notice '%',query2;
		--execute query2;
		--end;
	END LOOP;
	CLOSE curs1;

	return;
END
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100;
ALTER FUNCTION gw_update_srid(integer, text) OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_update_srid(integer, text) TO public;
GRANT EXECUTE ON FUNCTION gw_update_srid(integer, text) TO postgres;
GRANT EXECUTE ON FUNCTION gw_update_srid(integer, text) TO mapserver;
--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_update_srid(integer)

-- DROP FUNCTION gw_update_srid(integer);

CREATE OR REPLACE FUNCTION gw_update_srid(srid integer)
  RETURNS void AS
$BODY$
DECLARE
	query text;
	query2 text;
	rec record;
BEGIN
	if coalesce(srid,0)=0 then
		--raise notice 'NO srid';
		return;
	end if;
	for rec in select f_table_schema as sc,f_table_name as tb,f_geometry_column as col from geometry_columns inner join information_schema.tables on (f_table_schema=table_schema and f_table_name=table_name) where table_type='BASE TABLE' and not f_geometry_column like '%_cs' loop
		--UpdateGeometrySRID(rec.sc,rec.tb,rec.col,srid);
		
		query:='select UpdateGeometrySRID('''||rec.sc||''','''||rec.tb||''','''||rec.col||''','||srid||');';
		raise notice '%',query;

		--begin
			execute query;
		--exception when undefined_object then

			--query2:='ALTER TABLE ' || rec.sc ||'.'|| rec.tb || ' ADD CONSTRAINT enforce_srid_the_geom CHECK (srid('|| rec.col ||') = -1);';
--raise notice '%',query2;
			--execute query2;

			--execute query;
		--end;
	end loop;
	return;
END
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100;
ALTER FUNCTION gw_update_srid(integer) OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_update_srid(integer) TO public;
GRANT EXECUTE ON FUNCTION gw_update_srid(integer) TO postgres;
GRANT EXECUTE ON FUNCTION gw_update_srid(integer) TO mapserver;
--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-- Function: gw_valve_state(integer)

-- DROP FUNCTION gw_valve_state(integer);

CREATE OR REPLACE FUNCTION gw_valve_state(campo integer)
  RETURNS text AS
$BODY$
BEGIN
	if campo <> 1 then return 'CHIUSA'; 
		else return 'APERTA';
	end if;
END
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100;
ALTER FUNCTION gw_valve_state(integer) OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_valve_state(integer) TO public;
GRANT EXECUTE ON FUNCTION gw_valve_state(integer) TO postgres;
GRANT EXECUTE ON FUNCTION gw_valve_state(integer) TO mapserver;
----------------------------------------------------------------------------------------------------------------------------------------------------
--Function: gw_create_label(nomec1 character varying, nomec2 character varying, campo1 character varying, campo2 character varying, valore1 character varying, valore2 character varying)

-- DROP FUNCTION gw_create_label(nomec1 character varying, nomec2 character varying, campo1 character varying, campo2 character varying, valore1 character varying, valore2 character varying)

CREATE OR REPLACE FUNCTION gw_create_label(nomec1 character varying, nomec2 character varying, campo1 character varying, campo2 character varying, valore1 character varying, valore2 character varying)
  RETURNS text AS
$BODY$
BEGIN
  CASE
      WHEN campo1 = valore1 AND campo2 = valore2 THEN return '';
      WHEN campo1 = valore1 AND campo2 <> valore2 THEN return '''[' || nomec2|| ']''';
      WHEN campo2 = valore2 and campo1 <> valore1 THEN return '''[' || nomec1|| ']'''; 
      ELSE return '''[' || nomec1|| '] + ''  '' + [' || nomec2|| ']''';
  END CASE;
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION gw_create_label(nomec1 character varying, nomec2 character varying, campo1 character varying, campo2 character varying, valore1 character varying, valore2 character varying)
  OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_create_label(nomec1 character varying, nomec2 character varying, campo1 character varying, campo2 character varying, valore1 character varying, valore2 character varying) TO postgres;
GRANT EXECUTE ON FUNCTION gw_create_label(nomec1 character varying, nomec2 character varying, campo1 character varying, campo2 character varying, valore1 character varying, valore2 character varying) TO public;
GRANT EXECUTE ON FUNCTION gw_create_label(nomec1 character varying, nomec2 character varying, campo1 character varying, campo2 character varying, valore1 character varying, valore2 character varying) TO mapserver;
-----------------------------------------------------------------------------------------------------------------------------------------------------
--Function: gw_crea_etichetta(campo1 character varying, campo2 character varying, valore1 character varying, valore2 character varying)

-- DROP FUNCTION gw_crea_etichetta(campo1 character varying, campo2 character varying, valore1 character varying, valore2 character var
CREATE OR REPLACE FUNCTION gw_crea_etichetta(campo1 character varying, campo2 character varying, valore1 character varying, valore2 character varying)
  RETURNS text AS
$BODY$
BEGIN
  CASE
      WHEN campo1 = valore1 AND campo2 = valore2 THEN return NULL;
      WHEN campo1 = valore1 AND campo2 <> valore2 THEN return campo2;
      --WHEN campo1 = valore1 AND campo2 <> valore2 THEN return nomec2;
      WHEN campo2 = valore2 and campo1 <> valore1 THEN return campo1; 
      --WHEN campo2 = valore2 and campo1 <> valore1 THEN return nomec1; 
      ELSE return campo1 || '  ' || campo2;
      --ELSE return '[' || nomec1|| '] + ''  '' + [' || nomec2|| ']';
  END CASE;
END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION gw_crea_etichetta(campo1 character varying, campo2 character varying, valore1 character varying, valore2 character varying)
  OWNER TO postgres;
GRANT EXECUTE ON FUNCTION gw_crea_etichetta(campo1 character varying, campo2 character varying, valore1 character varying, valore2 character varying) TO postgres;
GRANT EXECUTE ON FUNCTION gw_crea_etichetta(campo1 character varying, campo2 character varying, valore1 character varying, valore2 character varying) TO public;
GRANT EXECUTE ON FUNCTION gw_crea_etichetta(campo1 character varying, campo2 character varying, valore1 character varying, valore2 character varying) TO mapserver;



