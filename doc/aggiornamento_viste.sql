SET search_path = gisclient_32, pg_catalog;



CREATE OR REPLACE VIEW seldb_catalog AS 
         SELECT (-1) AS id, 'Seleziona ====>' AS opzione, '0' AS project_name
UNION ALL 
         SELECT catalog.catalog_id AS id, catalog.catalog_name AS opzione, catalog.project_name
           FROM catalog;

CREATE OR REPLACE VIEW seldb_conntype AS 
         SELECT (-1) AS id, 'Seleziona ====>' AS opzione
UNION ALL 
        ( SELECT e_conntype.conntype_id AS id, e_conntype.conntype_name AS opzione
           FROM e_conntype
          ORDER BY e_conntype.conntype_order);
		  
CREATE OR REPLACE VIEW seldb_field_filter AS 
         SELECT (-1) AS id, 'Nessuno' AS opzione, 0 AS qtfield_id, 0 AS qt_id
UNION ALL 
        ( SELECT x.qtfield_id AS id, x.field_header AS opzione, y.qtfield_id, x.layer_id AS qt_id
           FROM qtfield x
      JOIN qtfield y USING (layer_id)
     WHERE x.qtfield_id <> y.qtfield_id
     ORDER BY x.qtfield_id, x.qtfield_order);
	 
CREATE OR REPLACE VIEW seldb_filetype AS 
         SELECT (-1) AS id, 'Seleziona ====>' AS opzione
UNION ALL 
         SELECT e_filetype.filetype_id AS id, e_filetype.filetype_name AS opzione
           FROM e_filetype;

CREATE OR REPLACE VIEW seldb_font AS 
         SELECT '' AS id, 'Seleziona ====>' AS opzione
UNION ALL 
         SELECT font.font_name AS id, font.font_name AS opzione
           FROM font;

CREATE OR REPLACE VIEW gisclient_31.seldb_language AS 
         SELECT ''::text AS id, 'Seleziona ====>' AS opzione
UNION ALL 
         SELECT e_language.language_id AS id, e_language.language_name AS opzione
           FROM gisclient_31.e_language;
				   			   
CREATE OR REPLACE VIEW seldb_layer_layergroup AS 
         SELECT (-1) AS id, 'Seleziona ====>' AS opzione, NULL::unknown AS layergroup_id
UNION ALL 
        ( SELECT DISTINCT layer.layer_id AS id, layer.layer_name AS opzione, layer.layergroup_id
           FROM layer
          WHERE layer.queryable = 1::numeric
          ORDER BY layer.layer_name, layer.layer_id, layer.layergroup_id);
		  
CREATE OR REPLACE VIEW seldb_layertype AS 
         SELECT (-1) AS id, 'Seleziona ====>' AS opzione
UNION ALL 
        ( SELECT e_layertype.layertype_id AS id, e_layertype.layertype_name AS opzione
           FROM e_layertype
          ORDER BY e_layertype.layertype_name);
		  
CREATE OR REPLACE VIEW seldb_lblposition AS 
         SELECT '' AS id, 'Seleziona ====>' AS opzione
UNION ALL 
        ( SELECT e_lblposition.lblposition_name AS id, e_lblposition.lblposition_name AS opzione
           FROM e_lblposition
          ORDER BY e_lblposition.lblposition_order);

CREATE OR REPLACE VIEW seldb_link AS 
         SELECT (-1) AS id, 'Seleziona ====>' AS opzione, '' AS project_name
UNION ALL  
         SELECT link.link_id AS id, link.link_name AS opzione, link.project_name
           FROM link;
		   
CREATE OR REPLACE VIEW seldb_mapset_srid AS 
         SELECT project.project_srid AS id, project.project_srid AS opzione, project.project_name
           FROM project
UNION ALL 
         SELECT project_srs.srid AS id, project_srs.srid AS opzione, project_srs.project_name
           FROM project_srs
          WHERE NOT (project_srs.project_name::text || project_srs.srid IN ( SELECT project.project_name::text || project.project_srid
                   FROM project))
 ORDER BY 1;
  
CREATE OR REPLACE VIEW seldb_papersize AS 
         SELECT (-1) AS id, 'Seleziona ====>' AS opzione
UNION ALL 
         SELECT e_papersize.papersize_id AS id, e_papersize.papersize_name AS opzione
           FROM e_papersize;
		   
CREATE OR REPLACE VIEW seldb_project AS 
         SELECT '' AS id, 'Seleziona ====>' AS opzione
UNION ALL 
        ( SELECT DISTINCT project.project_name AS id, project.project_name AS opzione
           FROM project
          ORDER BY project.project_name);

CREATE OR REPLACE VIEW seldb_qtrelation AS 
         SELECT 0 AS id, 'layer' AS opzione, 0 AS layer_id
UNION ALL 
         SELECT qtrelation.qtrelation_id AS id, qtrelation.qtrelation_name AS opzione, qtrelation.layer_id
           FROM qtrelation;
		   
CREATE OR REPLACE VIEW seldb_theme AS 
         SELECT (-1) AS id, 'Seleziona ====>' AS opzione, '' AS project_name
UNION ALL 
         SELECT theme.theme_id AS id, theme.theme_name AS opzione, theme.project_name
           FROM theme;
		   
CREATE OR REPLACE VIEW vista_qtfield AS 
 SELECT qtfield.qtfield_id, qtfield.layer_id, qtfield.fieldtype_id, x.qtrelation_id, qtfield.qtfield_name, qtfield.resultype_id, qtfield.field_header, qtfield.qtfield_order, COALESCE(qtfield.column_width, 0) AS column_width, x.name AS qtrelation_name, x.qtrelationtype_id, x.qtrelationtype_name
   FROM qtfield
   JOIN e_fieldtype USING (fieldtype_id)
   JOIN ( SELECT y.qtrelationtype_id, y.qtrelation_id, y.name, z.qtrelationtype_name
      FROM (         SELECT 0 AS qtrelation_id, 'Data Layer' AS name, 0 AS qtrelationtype_id
           UNION ALL 
                    SELECT qtrelation.qtrelation_id, COALESCE(qtrelation.qtrelation_name, 'Nessuna Relazione'::character varying) AS name, qtrelation.qtrelationtype_id
                      FROM qtrelation) y
   JOIN (         SELECT 0 AS qtrelationtype_id, '' AS qtrelationtype_name
           UNION ALL 
                    SELECT e_qtrelationtype.qtrelationtype_id, e_qtrelationtype.qtrelationtype_name
                      FROM e_qtrelationtype) z USING (qtrelationtype_id)) x USING (qtrelation_id)
  ORDER BY qtfield.qtfield_id, x.qtrelation_id, x.qtrelationtype_id;
  
  