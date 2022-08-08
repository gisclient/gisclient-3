<?php
/*
GisClient map browser

Copyright (C) 2008 - 2009  Roberto Starnini - Gis & Web S.r.l. -info@gisweb.it

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 3
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/

/*    Campo search_type per definizione della ricerca:
    1 - Testo secco;
    2 - Parte di testo senza suggerimenti
    3 - Testo con autocompletamento e lista suggerimenti (dati presi dal campo search_list);
    4 - Numerico
    5 - Data
    6 - SI/NO*/



//SE SI MODIFICA RICORDARSI DI MODIFICARLA ANCHE NELLE FUNZIONI DI RICERCA SU DATABASE!!!!!!!!!!!
class PgQuery
{

    public $allQueryResults = array();
    public $allQueryExtent = array();
    public $mapToUpdate=0;
    public $aggregateFunction = array(101=>'sum',102=>'avg',103=>'min',104=>'max',105=>'count',106=>'variance',107=>'stddev');
    public $resultHeaders = array();
    public $isGraph = 0;
    public $request;
    public $templates;

    public function __destruct()
    {
        //$this->db->sql_close();
        //unset($this->db);
        //unset($this->mapsetId);
        //unset($this->mapError);
    }

    public function __construct($request)
    {
        //$db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
        $this->request = $request;
        $db = GCApp::getDB();
        //if (!$db->db_connect_id) die( "Impossibile connettersi al database ");
        $this->db=$db;
        $dbschema=DB_SCHEMA;

        //costruzione oggetto querytemplate
        $sqlField="select field.*, relation.relation_name, relation_id, relationtype_id, data_field_1, data_field_2, data_field_3, table_field_1, table_field_2, table_field_3, table_name, catalog_path, catalog_url
        from $dbschema.field
        left join $dbschema.relation using (relation_id)
        left join $dbschema.catalog using (catalog_id)
        where field.layer_id = :layer_id
        order by field_order;";

        $stmt = $db->prepare($sqlField);
        $stmt->execute(array('layer_id'=>$request['layer_id']));
        $qRelation = array();
        $qField = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $Id=$row["layer_id"];
            $fieldId=$row["field_id"];
            $qField[$Id][$fieldId]["field_name"]=trim($row["field_name"]);
            $qField[$Id][$fieldId]["field_alias"]=trim($row["field_header"]);
            $qField[$Id][$fieldId]["formula"]=trim($row["formula"]);
            $qField[$Id][$fieldId]["field_type"]=$row["fieldtype_id"];
            $qField[$Id][$fieldId]["data_type"]=$row["datatype_id"];
            $qField[$Id][$fieldId]["order_by"]=$row["orderby_id"];
            $qField[$Id][$fieldId]["field_format"]=$row["field_format"];
            $qField[$Id][$fieldId]["search_type"]=trim($row["searchtype_id"]);
            $qField[$Id][$fieldId]["result_type"]=trim($row["resultype_id"]);
            $qField[$Id][$fieldId]["field_filter"]=trim($row["field_filter"]);
            $qField[$Id][$fieldId]["search_function"]=(isset($row["search_function"]))?trim($row["search_function"]):'';
            $qField[$Id][$fieldId]["relation"]=$row["relation_id"];
            $qField[$Id][$fieldId]["column_width"]=$row["column_width"];
            $f=array();
            if (!empty($row['relation_id'])) {
                $relationId = $row['relation_id'];
                if (($row["data_field_1"])&&($row["table_field_1"])) {
                    $f[]=array(trim($row["data_field_1"]),trim($row["table_field_1"]));
                }
                if (($row["data_field_2"])&&($row["table_field_2"])) {
                    $f[]=array(trim($row["data_field_2"]),trim($row["table_field_2"]));
                }
                if (($row["data_field_3"])&&($row["table_field_3"])) {
                    $f[]=array(trim($row["data_field_3"]),trim($row["table_field_3"]));
                }
                $qRelation[$Id][$relationId]["join_field"]=$f;
                $qRelation[$Id][$relationId]["name"]=trim($row["relation_name"]);
                $qRelation[$Id][$relationId]["table_name"]=trim($row["table_name"]);
                $qRelation[$Id][$relationId]["path"]=trim($row["catalog_path"]);
                $qRelation[$Id][$relationId]["catalog_url"]=trim($row["catalog_url"]);
                if ($row["relationtype_id"]==100) {
                    $row["relationtype_id"]=2;
                    $this->isGraph=1;
                }
                $qRelation[$Id][$relationId]["relation_type"]=$row["relationtype_id"];
            }
        }
        /*         echo 'Fields<br><pre>';
        var_export($qField); */
        //Assegno alle relazioni i valori  di schema e connessione
        foreach ($qRelation as $qt => $aRel) {
            foreach ($aRel as $qtrel => $row) {
                $aConnInfo = connInfofromPath($row["path"]);
                $qRelation[$qt][$qtrel]["table_connection"] = $aConnInfo[0];
                $qRelation[$qt][$qtrel]["table_schema"] = $aConnInfo[1];
            }
        }
        /*         echo 'Relations<br><pre>';
        var_export($qRelation); */
        //Aggiungo eventuali hyperlink relativi ai query_template

        $qLink = array();
        /*
        if (false) { //FD: lasciamo un attimo via i link...
            $sqlLink="select link.id,link.link_id,link_def,link.link_name,winw,winh,link_order from $dbschema.link inner join $dbschema.mapset_link using (link_id) inner join $dbschema.link using (link_id) where mapset_name = '". $_REQUEST["mapset"]."' and resultype_id in (".$this->resultype.",3) and link.id $sqlQt order by link_order;";
            $db->sql_query($sqlLink);
            while ($row=$db->sql_fetchrow()) {
                $qtId=intval($row["id"]);
                $linkId=intval($row["link_id"]);
                $link=$row["link_def"];
                $linkTitle=$row["link_name_alt"]?$row["link_name_alt"]:$row["link_name"];
                $qLink[$qtId][$linkId]=array($link,$linkTitle,intval($row["winw"]),intval($row["winh"]));
            }
            print_debug($sqlLink, null, 'template');
        }
        */

        //query template *******************
        //$sqlTemplate="select layer.layer_id,layer_name,layer.layergroup_id,layergroup.hidden,mapset_filter,id,base_url,catalog_path,catalog_url,connection_type,data,data_geom,data_filter,data_unique,data_srid,template,tolerance,name,max_rows,selection_color,zoom_buffer,edit_url,groupobject,layertype_ms,static,papersize_id,filter,papersize_size,papersize_orientation from $dbschema.qt inner join $dbschema.layer using (layer_id) inner join $dbschema.e_layertype using (layertype_id) inner join $dbschema.catalog using (catalog_id) inner join $dbschema.layergroup using (layergroup_id) inner join $dbschema.project using (project_name) left join $dbschema.e_papersize using(papersize_id)  where qt.id $sqlQt order by order;";
        $sqlTemplate="select layer.layer_id, layer_name, layer.layergroup_id, layergroup.hidden, catalog_path, catalog_url, connection_type, data, data_geom, data_filter, data_unique, data_srid, layertype_ms
        from $dbschema.layer
        inner join $dbschema.e_layertype using (layertype_id)
        inner join $dbschema.catalog using (catalog_id)
        inner join $dbschema.layergroup using (layergroup_id)
        where layer_id = :layer_id order by layer_order;";
        print_debug($sqlTemplate, null, 'template');

        $stmt = $db->prepare($sqlTemplate);
        $stmt->execute(array('layer_id'=>$request['layer_id']));
        //Tutti i query template dei modelli di ricerca interessati
        $allTemplates = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $layerId=$row["layer_id"];
            $allTemplates[$layerId]=$row;
            $allTemplates[$layerId]["field"]= (isset($qField[$layerId]))?$qField[$layerId]:null;
            $allTemplates[$layerId]["relation"]= (isset($qRelation[$layerId]))?$qRelation[$layerId]:null;
            $allTemplates[$layerId]["link"]=(isset($qLink[$layerId]))?array_values($qLink[$layerId]):array();
        }

        /*         echo 'AllTemplates<br><pre>';
        var_export($allTemplates); */

        $this->templates = $allTemplates;

        //return $this->getInfoByTemplate($aTemplate);
    }

    public function query($layerId)
    {
        return $this->getInfoByTemplate($this->templates[$layerId]);
    }

    //Per ogni querytemplate ritorna un array di risultati
    public function getInfoByTemplate($aTemplate)
    {
        //$myMap = "MAPSET_".$_REQUEST["mapset"];
        //$templateId = $aTemplate["layer_id"];

        $dataDB = GCApp::getDataDB($aTemplate['catalog_path']);
        $datalayerSchema = GCApp::getDataDBSchema($aTemplate['catalog_path']);
        $aTemplate['table_schema'] = $datalayerSchema;
        $aTemplate['fields'] = $aTemplate['field']; //temporaneo

        $options = array('include_1n_relations'=>true, 'getGeomAs'=>'text');
        if (!empty($this->request['srid'])) {
            $options['srid'] = $this->request['srid'];
        }
        if (!empty($this->request['action']) && $this->request['action'] == 'viewdetails') {
            $options['group_1n'] = false;
            if (!empty($this->request['relationName'])) {
                $options['show_relation'] = $this->request['relationName'];
            }
        }

        $queryString = GCAuthor::buildFeatureQuery($aTemplate, $options);

        $params = array();
        $whereClause = null;

        if (!empty($this->request['query'])) {
            $whereClause = $this->request['query'];
            if (!empty($this->request['values'])) {
                $params = $this->request['values'];
            }
        } elseif (!empty($this->request['action']) && $this->request['action'] == 'viewdetails') {
            $whereClause = $aTemplate['data_unique'].' = :'.$aTemplate['data_unique'];
            $params[$aTemplate['data_unique']] = $this->request['featureId'];
        }

        if (!empty($whereClause)) {
            $queryString = 'select * from ('.$queryString.') as foo where '.$whereClause;
        }

        //die($queryString);
        $stmt = $dataDB->prepare($queryString);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $results;
    }
}
