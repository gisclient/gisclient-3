CREATE OR REPLACE VIEW gisclient_3.seldb_theme_single
 AS
SELECT '0'::integer AS id,
	'0'::integer AS theme_single,
    'No'::character varying AS opzione
UNION ALL
SELECT e_owstype.owstype_id AS id,
	e_owstype.owstype_id AS theme_single,
    'Si - ' || e_owstype.owstype_name AS opzione
   FROM gisclient_3.e_owstype
  WHERE owstype_id < 3;
