

-- Type: result_elenco_livelli

-- DROP TYPE result_elenco_livelli;

CREATE TYPE result_elenco_livelli AS
   (theme_title character varying,
    theme_name character varying,
    layergroup character varying,
    layer character varying);
ALTER TYPE result_elenco_livelli
  OWNER TO postgres;



-- Function: gw_elenco_livelli(character varying, double precision, double precision, integer, integer)

-- DROP FUNCTION gw_elenco_livelli(character varying, double precision, double precision, integer, integer);

CREATE OR REPLACE FUNCTION gw_elenco_livelli(mapset character varying, xc double precision, yc double precision, w integer, h integer)
  RETURNS SETOF result_elenco_livelli AS
$BODY$
DECLARE
	rec record;
	query varchar;
	res integer;
	xmin float;
	ymin float;
	xmax float;
	ymax float;
	result result_elenco_livelli;
BEGIN
	xmin:=xc-w/2;
	xmax:=xc+w/2;
	ymin:=yc-h/2;
	ymax:=yc+h/2;
	for rec in  execute 'select theme_title,theme_name,layergroup_name,layer_name,split_part(catalog_path,''/'',2)||''.''||data as tabella,data_geom,data_filter from gisclient_3.layer inner join gisclient_3.layergroup using (layergroup_id) inner join gisclient_3.mapset_layergroup using(layergroup_id) inner join gisclient_3.theme using (theme_id) inner join gisclient_3.mapset using(mapset_name,project_name) inner join gisclient_3.catalog using(project_name,catalog_id) 
	where mapset_name='''|| mapset ||''' and theme_name <> ''base_cartografica'' and data_geom is not null and not layer_name ilike ''%_TBL''' loop
	   begin
		query:='select count(*) from ' || rec.tabella || ' WHERE ' || rec.data_geom || ' && ST_SetSRID(ST_MakeBox2D(ST_Point('||xmin||','||ymin||'),ST_Point('||xmax||','||ymax||')),3003)';
		if rec.data_filter <> '' then 
			query:=query||' AND ' || replace(rec.data_filter,'gc_geom',rec.data_geom); 
		end if;
	        raise notice '%',query;
		execute query into res;
		
		if res > 0 then
			result.theme_name:=rec.theme_name;
			result.theme_title:=rec.theme_title;
			result.layergroup:=rec.layergroup_name;
			result.layer:=rec.layer_name;
			return next result;
		end if;
		raise notice '% % %',res,rec.tabella,rec.layer_name;
		exception when undefined_table then
		
		   raise notice 'Tabella % non trovata',rec.tabella;
	   end;
	end loop;

END
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100
  ROWS 1000;
ALTER FUNCTION gw_elenco_livelli(character varying, double precision, double precision, integer, integer)
  OWNER TO postgres;
