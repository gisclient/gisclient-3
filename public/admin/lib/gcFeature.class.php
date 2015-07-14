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

/* 	Campo search_type per definizione della ricerca:
  1 - Testo secco;
  2 - Parte di testo senza suggerimenti
  3 - Testo con autocompletamento e lista suggerimenti (dati presi dal campo search_list);
  4 - Numerico
  5 - Data
  6 - SI/NO */

// QUESTA SERVE SOLO PER LA COSTRUZIONE DEI MAPFILES	



class gcFeature {

    var $msFeatureType = array();
    var $aggregateFunction = array(101 => 'sum', 102 => 'avg', 103 => 'min', 104 => 'max', 105 => 'count', 106 => 'variance', 107 => 'stddev');
    var $resultHeaders = array();
    var $owsUrl;
    var $labels = false;
    var $aSymbols;
    var $db;
    var $srsList;
    var $srsParams;
    var $dataTypes;
    var $msVersion;
    var $forcePrivate = false;
    private $i18n;
    
    /**
     * Container of feature information
     * 
     * @var array
     */
    private $aFeature;

    function __destruct() {
        unset($this->aFeature);
        unset($this->mapError);
    }

    function __construct($i18n = null) {
        $this->db = GCApp::getDB();
        $this->i18n = $i18n;
        $this->msVersion = substr(ms_GetVersionInt(), 0, 1);
    }

    public function initFeature($layerId) {
        $this->forcePrivate = false;

        $sqlField = "select field.*,
			relation.relation_name, relation_id, relationtype_id, data_field_1, data_field_2, data_field_3, table_field_1, table_field_2, table_field_3, table_name, 
			catalog_path, catalog_url from " . DB_SCHEMA . ".field 
			left join " . DB_SCHEMA . ".relation using (layer_id,relation_id) 
			left join " . DB_SCHEMA . ".catalog using (catalog_id) 
			where field.layer_id = ? 
			order by field_order;";
        print_debug($sqlField, null, 'template');

        $stmt = $this->db->prepare($sqlField);
        $stmt->execute(array($layerId));

        $qRelation = array();
        $qField = array();

        // Costruzione dell'oggetto Feature
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            if (!empty($this->i18n)) {
                $row = $this->i18n->translateRow($row, 'field', $row['field_id'], array('field_name', 'field_header'));
            }

            $fieldId = $row["field_id"];
            $qField[$fieldId]["field_name"] = trim($row["field_name"]);
            $qField[$fieldId]["formula"] = trim($row["formula"]);
            $qField[$fieldId]["field_title"] = trim($row["field_header"]);
            $qField[$fieldId]["field_type"] = $row["fieldtype_id"];
            $qField[$fieldId]["data_type"] = $row["datatype_id"];
            $qField[$fieldId]["order_by"] = $row["orderby_id"];
            $qField[$fieldId]["field_format"] = $row["field_format"];
            $qField[$fieldId]["editable"] = $row["editable"];
            $qField[$fieldId]["search_type"] = trim($row["searchtype_id"]);
            $qField[$fieldId]["result_type"] = trim($row["resultype_id"]);
            $qField[$fieldId]["field_filter"] = trim($row["field_filter"]);
            $qField[$fieldId]["search_function"] = (!empty($row["search_function"])) ? trim($row["search_function"]) : '';
            $qField[$fieldId]["relation"] = $row["relation_id"];
            $qField[$fieldId]["column_width"] = $row["column_width"];
            $f = array();
            if ($relationId = $row["relation_id"]) {
                if (($row["data_field_1"]) && ($row["table_field_1"]))
                    $f[] = array(trim($row["data_field_1"]), trim($row["table_field_1"]));
                if (($row["data_field_2"]) && ($row["table_field_2"]))
                    $f[] = array(trim($row["data_field_2"]), trim($row["table_field_2"]));
                if (($row["data_field_3"]) && ($row["table_field_3"]))
                    $f[] = array(trim($row["data_field_3"]), trim($row["table_field_3"]));
                $qRelation[$relationId]["join_field"] = $f;
                $qRelation[$relationId]["name"] = NameReplace($row["relation_name"]);
                $qRelation[$relationId]["table_name"] = trim($row["table_name"]);
                $qRelation[$relationId]["catalog_path"] = trim($row["catalog_path"]);
                $qRelation[$relationId]["catalog_url"] = trim($row["catalog_url"]);
                $qRelation[$relationId]["relation_type"] = $row["relationtype_id"];
            }
        }

        //Assegno alle relazioni i valori  di schema e connessione
        foreach ($qRelation as $key => $value) {
            $aConnInfo = connInfofromPath($value["catalog_path"]);
            $qRelation[$key]["connection_string"] = $aConnInfo[0];
            $qRelation[$key]["table_schema"] = $aConnInfo[1];
        }

        //Feature *******************
        $sqlFeature = "select layer.*,connection_type,base_path,catalog_path,catalog_url 
			from " . DB_SCHEMA . ".layer inner join " . DB_SCHEMA . ".catalog using (catalog_id) 
			inner join " . DB_SCHEMA . ".project using(project_name) 
			where layer.layer_id = ?;";

        $stmt = $this->db->prepare($sqlFeature);
        $stmt->execute(array($layerId));

        $res = $stmt->fetchAll();
        if ($stmt->rowCount() == 0) {
            $this->aFeature = null;
            return;
        }
        $aFeature = $res[0];
        if (!empty($this->i18n)) {
            $aFeature = $this->i18n->translateRow($aFeature, 'layer', $aFeature['layer_id']);
        }

        //Assegno al layer i valori  di schema e connessione
        $aConnInfo = connInfofromPath($aFeature["catalog_path"]);
        $aFeature["connection_string"] = $aConnInfo[0];
        $aFeature["table_schema"] = $aConnInfo[1];
        //Se inizia con / o con ../ no concateno con il basepath
        if (substr(trim($aFeature["catalog_path"]), 0, 1) == '/' || substr(trim($aFeature["catalog_path"]), 0, 3) == '../') {
            $aFeature["filePath"] = trim($aFeature["catalog_path"]);
        } else {
            $aFeature["filePath"] = trim($aFeature["base_path"]) . trim($aFeature["catalog_path"]);
        }
        $aFeature["relation"] = (isset($qRelation)) ? $qRelation : null;
        $aFeature["fields"] = (isset($qField)) ? $qField : null;
        $aFeature["link"] = (isset($qLink)) ? array_values($qLink) : array();
        $aFeature["tileindex"] = false;

        print_debug($aFeature, null, 'template');
        $this->aFeature = $aFeature;
    }
    
    /**
     * Return data of the current feature
     * 
     * @return array
     */
    public function getFeatureData() {
        return $this->aFeature;
    }
    
    /**
     * Set feature data
     * 
     * @param array $aFeature
     */
    public function setFeatureData(array $aFeature) {
        $this->aFeature = $aFeature;
    }

    public function isEditable() {
        if ($this->aFeature['connection_type'] != 6)
            return false;
        if ($this->aFeature['queryable'] != 1)
            return false;
        foreach ($this->aFeature['fields'] as $k => $v) {
            if ($v['editable'] == 1)
                return true;
        }
        return false;
    }

    public function getTinyOWSLayerParams() {
        //TODO: così funziona solo per le definizioni DB_NAME/DB_SCHEMA
        list($dbName, $dbSchema) = explode('/', $this->aFeature['catalog_path']);
        return array(
            'schema' => $dbSchema,
            'database' => $dbName,
            'name' => $this->aFeature['data'],
            'feature' => $this->aFeature['layergroup_name'] . '.' . $this->aFeature['layer_name'],
            'title' => $this->aFeature['layer_name']
        );
    }

    public function getLayerName() {
        return $this->aFeature['layer_name'];
    }

    public function isPrivate() {
        return $this->forcePrivate || ($this->aFeature['private'] > 0);
    }

    // Used to force private layer in mapset is private
    public function setPrivate($private) {
        $this->forcePrivate = $private;
    }

    public function getLayerText($layergroupName, $layergroup) {
        if (!$this->aFeature)
            return false;
        $maxScale = $layergroup['layergroup_maxscale'];
        $minScale = $layergroup['layergroup_minscale'];
        // FIXME: the following does not use the return value, can it be removed?
        $this->_getLayerData();
        $this->aFeature['layergroup_name'] = $layergroupName;
        $this->aSymbols = array(); //Elenco dei simboli usati nelle classi della feature
        $aMapservUnitDef = array(1 => "pixels", 2 => "feet", 3 => "inches", 4 => "kilometers", 5 => "meters", 6 => "miles", 7 => "nauticalmiles");
        $aGCLayerType = array(1 => "POINT", 2 => "LINE", 3 => "POLYGON", 4 => "RASTER", 5 => "ANNOTATION", 10 => 'RASTER', 11 => 'CHART'); //10 TILERASTER
        $layText = array();
        $layText[] = "LAYER";
        $layText[] = "GROUP \"$layergroupName\"";
        $layText[] = "NAME \"$layergroupName." . $this->aFeature["layer_name"] . "\"";
        $layText[] = "TYPE " . $aGCLayerType[$this->aFeature["layertype_id"]];
        $layText[] = "STATUS OFF";
        $layText[] = "METADATA";
        $layText[] = "\t\"wms_group_title\" \"" . $layergroup['layergroup_title'] . "\"";
        $layText[] = $this->_getMetadata();
        $layText[] = "END";
        if (!empty($this->aFeature["data_srid"])) {
            $layText[] = "PROJECTION";
            $layText[] = "\t\"init=epsg:" . $this->aFeature["data_srid"] . "\"";
            if (!empty($this->srsParams[$this->aFeature["data_srid"]]))
                $layText[] = "\t\"+towgs84=" . $this->srsParams[$this->aFeature["data_srid"]] . "\"";
            $layText[] = "END";
        }

        $this->_getLayerConnection($layText);
        if (!empty($this->aFeature["data_extent"]))
            $layText[] = "EXTENT " . $this->aFeature["data_extent"];
        if (!empty($this->aFeature["sizeunits_id"]))
            $layText[] = "SIZEUNITS " . $aMapservUnitDef[$this->aFeature["sizeunits_id"]];
        if (!empty($this->aFeature['maxscale']))
            $layText[] = 'MAXSCALEDENOM ' . $this->aFeature['maxscale'];
        else if (!empty($maxScale))
            $layText[] = 'MAXSCALEDENOM ' . $maxScale;
        if (!empty($this->aFeature['minscale']))
            $layText[] = 'MINSCALEDENOM ' . $this->aFeature['minscale'];
        else if (!empty($minScale))
            $layText[] = 'MINSCALEDENOM ' . $minScale;
        //if(!empty($maxScale))$layText[]="MAXSCALEDENOM $maxScale"; elseif(!empty($this->aFeature["maxscale"])) $layText[]="MAXSCALEDENOM ". $this->aFeature["maxscale"];
        //if(!empty($minScale))$layText[]="MINSCALEDENOM $minScale"; elseif(!empty($this->aFeature["minscale"])) $layText[]="MINSCALEDENOM ". $this->aFeature["minscale"];
        if (!empty($this->aFeature["maxfeatures"]) && $this->aFeature["maxfeatures"] > 0)
            $layText[] = "MAXFEATURES " . $this->aFeature["maxfeatures"];
        if (!empty($this->aFeature["tolerance"]))
            $layText[] = "TOLERANCE " . $this->aFeature["tolerance"];
        if (!empty($this->aFeature["toleranceunits"]))
            $layText[] = "TOLERANCEUNITS " . $aMapservUnitDef[$this->aFeature["toleranceunits_id"]];
        if (!empty($this->aFeature["template"]))
            $layText[] = "TEMPLATE \"" . $this->aFeature["template"] . "\"";
        if (!empty($this->aFeature["header"]))
            $layText[] = "HEADER \"" . $this->aFeature["header"] . "\"";;
        if (!empty($this->aFeature["footer"]))
            $layText[] = "FOOTER \"" . $this->aFeature["footer"] . "\"";;
        if (!empty($this->aFeature["opacity"]))
            $layText[] = "OPACITY " . $this->aFeature["opacity"];
        if (!empty($this->aFeature["symbolscale"]))
            $layText[] = "SYMBOLSCALEDENOM " . $this->aFeature["symbolscale"];

        //classi:

        $sql = "select class_id,class_name,class_title,class_text,class_image,legendtype_id,keyimage,expression,class.maxscale,class.minscale,label_font,label_angle,label_color,label_outlinecolor,label_bgcolor,label_size,label_minsize,label_maxsize,label_position,label_priority,label_buffer,label_force,label_wrap,label_def
        from " . DB_SCHEMA . ".class where layer_id=? order by class_order;";

        print_debug($sql, null, 'classi');

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->aFeature["layer_id"]));
        $res = $stmt->fetchAll();

        //Solo se presenti classi
        if (count($res) > 0) {
            if (!empty($this->aFeature["labelitem"]))
                $layText[] = "LABELITEM \"" . $this->aFeature["labelitem"] . "\"";
            if (!empty($this->aFeature["labelminscale"]))
                $layText[] = "LABELMINSCALEDENOM " . $this->aFeature["labelminscale"];
            if (!empty($this->aFeature["labelmaxscale"]))
                $layText[] = "LABELMAXSCALEDENOM " . $this->aFeature["labelmaxscale"];
            if (!empty($this->aFeature["classitem"]))
                $layText[] = "CLASSITEM \"" . $this->aFeature["classitem"] . "\"";
        }

        for ($i = 0; $i < count($res); $i++) {

            if (!empty($this->i18n)) {
                $res[$i] = $this->i18n->translateRow($res[$i], 'class', $res[$i]['class_id']);
            }

            $layText[] = "CLASS";
            $layText[] = $this->_getClassText($res[$i]);
            $layText[] = "END";
        }

        if ($this->labels && $this->aFeature['postlabelcache'] == 1)
            $layText[] = "POSTLABELCACHE TRUE";
        if (!empty($this->aFeature["layer_def"]))
            $layText[] = $this->aFeature["layer_def"];
        $layText[] = "END";

        return implode("\n\t", $layText);
    }

    private function _getLayerConnection(&$layText) {
        if ($this->aFeature["layertype_id"] == 10 && !$this->aFeature["tileindex"]) {//TILERASTER
            $layText[] = "TILEINDEX \"" . $this->aFeature["layer_name"] . ".TILEINDEX\"";
            $layText[] = "TILEITEM \"location\"";
        } else {
            switch ($this->aFeature["connection_type"]) {
                case MS_SHAPEFILE: //Local folder shape and raster
                    $filePath = $this->aFeature["filePath"];
                    if (substr($filePath, -1) != "/")
                        $filePath.="/";
                    $layText[] = "DATA \"" . $filePath . $this->aFeature["data"] . "\"";
                    break;

                case MS_WMS:
                    $layText[] = "CONNECTIONTYPE WMS";
                    $layText[] = "CONNECTION \"" . $this->aFeature["catalog_path"] . "\"";
                    break;

                case MS_WFS:
                    $layText[] = "CONNECTIONTYPE WFS";
                    $layText[] = "CONNECTION \"" . $this->aFeature["catalog_path"] . "\"";
                    break;

                case MS_POSTGIS:
                    $layText[] = "CONNECTIONTYPE POSTGIS";
                    $layText[] = "CONNECTION \"" . $this->aFeature["connection_string"] . "\"";
                    $sData = $this->_getLayerData();
                    if (!empty($this->aFeature["data_unique"]))
                        $sData .= " USING UNIQUE gc_objid";
                    if (!empty($this->aFeature["data_srid"]))
                        $sData .= " USING SRID=" . $this->aFeature["data_srid"];
                    $layText[] = "DATA \"$sData\"";
                    if (!empty($this->aFeature["data_filter"]))
                        $layText[] = "FILTER \"" . $this->aFeature["data_filter"] . "\"";
                    $layText[] = "PROCESSING \"CLOSE_CONNECTION=DEFER\"";
                    if ($this->aFeature["queryable"] == 1)
                        $layText[] = "DUMP TRUE";
                    break;

                case MS_ORACLESPATIAL:
                    $layText[] = "CONNECTIONTYPE ORACLESPATIAL";
                    $layText[] = "CONNECTION \"" . $this->aFeature["catalog_path"] . "\"";
                    $sData = $this->_getOracleLayerData();
                    $using = '';
                    if (!empty($this->aFeature['data_srid']) || !empty($this->aFeature["data_unique"])) {
                        $sData .= ' USING ';
                        if (!empty($this->aFeature["data_unique"])) {
                            $sData .= ' UNIQUE ' . $this->aFeature["data_unique"];
                        }
                        if (!empty($this->aFeature['data_srid'])) {
                            $sData .= ' SRID ' . $this->aFeature['data_srid'];
                        }
                    }
                    $layText[] = "DATA \"$sData\"";
                    if (!empty($this->aFeature["data_filter"]))
                        $layText[] = "FILTER \"" . $this->aFeature["data_filter"] . "\"";
                    $layText[] = "PROCESSING \"CLOSE_CONNECTION=DEFER\"";
                    if ($this->aFeature["queryable"] == 1)
                        $layText[] = "DUMP TRUE";
                    break;

                case MS_SDE:
                    break;

                case MS_OGR:
                    $layText[] = "CONNECTIONTYPE OGR";
                    $layText[] = "CONNECTION \"" . $this->aFeature["catalog_path"] . "\"";
                    $layText[] = "DATA \"" . $this->aFeature["data"] . "\"";
                    if ($this->aFeature["queryable"] == 1)
                        $layText[] = "DUMP TRUE";
                    break;
                case MS_GRATICULE:
                    break;
                case MS_MYGIS:
                    break;
                    break;
                case MS_PLUGIN:
                    break;
            }
        }
    }

    public function getTileIndexLayer() {
        $layText = array();
        $layText[] = "LAYER";
        $layText[] = "\tNAME \"" . $this->aFeature["layer_name"] . ".TILEINDEX\"";
        $layText[] = "TYPE POLYGON";
        $layText[] = "STATUS OFF";
        if (!empty($this->srsList)) {
            $layText[] = "PROJECTION";
            $layText[] = "\t\"" . $this->srsList[$this->aFeature["data_srid"]]["proj4text"] . "\"";
            $layText[] = "END";
            $layText[] = "EXTENT " . $this->srsList[$this->aFeature["data_srid"]]["extent"];
        }
        $this->aFeature["tileindex"] = true;
        $this->_getLayerConnection($layText);
        $layText[] = "END";
        return implode("\n\t", $layText);
    }

    //ritorna la querystring per la feature da usare nel tag DATA del mapfile
    private function _getOracleLayerData() {
        $string = $this->aFeature['data_geom'] . ' FROM ';
        return $string . $this->aFeature['data'];
        // questo sotto non sembra funzionare
        if (empty($this->aFeature["fields"]))
            return $string . $this->aFeature['data'];

        $fields = array($this->aFeature['data_geom']);
        foreach ($this->aFeature["fields"] as $fieldId => $field) {
            array_push($fields, $field['field_name']);
        }
        return $string . ' (SELECT ' . implode(', ', $fields) . ' FROM ' . $this->aFeature['data'] . ') as foo';
    }

    /**
     * Construct the DATA statement for the mapfile, http://mapserver.org/mapfile/layer.html
     * @return string
     */
    private function _getLayerData() {

        $aFeature = $this->aFeature;
        $datalayerTable = $aFeature["data"];
        $datalayerGeom = $aFeature["data_geom"];
        $datalayerKey = $aFeature["data_unique"];
        $datalayerSchema = $aFeature["table_schema"];

        if ($aFeature["tileindex"]) { //X TILERASTER
            $location = "'" . trim($aFeature["base_path"]) . "' || location as location"; //value for location
            $table = $aFeature["table_schema"] . "." . $aFeature["data"];
            $datalayerTable = "(SELECT $datalayerKey as gc_objid,$datalayerGeom as the_geom,$location FROM $table) AS " . DATALAYER_ALIAS_TABLE;
            return "the_geom from " . $datalayerTable;
        } elseif (preg_match("|select (.+) from (.+)|i", $datalayerTable))//Definizione alias della tabella o vista pricipale (nel caso l'utente abbia definito una vista)  (da valutare se ha senso)
            $datalayerTable = "($datalayerTable) AS " . DATALAYER_ALIAS_TABLE;
        else
            $datalayerTable = $datalayerSchema . "." . $datalayerTable . " AS " . DATALAYER_ALIAS_TABLE;

        $joinString = $datalayerTable;
        $fieldString = "*";
        $groupBy = '';

        //Elenco dei campi definiti
        if ($aFeature["fields"]) {
            $fieldList = array();

            // collection of all fields which should be listed in the GROUP BY clause
            // the primary key is certainly part of it
            // with PostgreSQL 9.1 and later, the primary key would be enough.
            $groupByFieldList = array(DATALAYER_ALIAS_TABLE . "." . $datalayerKey);

            foreach ($aFeature["fields"] as $idField => $aField) {

                if ($aField["relation"] == 0 || $aFeature["relation"][$aField["relation"]]["relation_type"] == 1) {

                    if ($aField["relation"] != 0) {//Il campo appartiene alla relazione e non alla tabella del layer 
                        $idRelation = $aField["relation"];
                        $aliasTable = $aFeature["relation"][$idRelation]["name"];
                    } else {
                        $aliasTable = DATALAYER_ALIAS_TABLE;
                    }
                    $groupByFieldList[] = $aliasTable . '.' . $aField['field_name'];

                    //Campi calcolati non metto tabella.campo
                    //if(strpos($aField["field_name"],'(')!==false)
                    //if(preg_match('|[(](.+)[)]|i',$aField["field_name"]) || strpos($aField["field_name"],"||"))
                    if ($aField["formula"]) {
                        $fieldName = $aField["formula"] . " AS " . $aField["field_name"]; // . " AS " . strtolower(NameReplace($aField["field_title"]));
                    } else {
                        $fieldName = $aliasTable . "." . $aField["field_name"];
                    }
                    // **** Marco Giraudi (Old Snapo) 02/09/2013 - Correzione per group by con formule ****
                    $groupByFieldList[] = $aField['field_name'];
                    
                    $fieldList[] = $fieldName;

                    /*
                      if($aField["relation"]>0)
                      $fieldString = $fieldName;// .  " AS " . $aliasTable . "_" . NameReplace($aField["field_title"]);
                      else
                      $fieldString = $fieldName;
                      $fieldList[] = $fieldString;
                     */
                }
            }

            //Elenco delle relazioni
            $joinString = $datalayerTable;
            if ($aRelation = $aFeature["relation"]) {
                foreach ($aRelation as $idrel => $rel) {
                    $relationAliasTable = NameReplace($rel["name"]);

                    //TODO RELAZIONI 1-MOLTI IN GC3
                    if ($rel["relation_type"] == 2) {
                        continue;
                        //aggiungo un campo che ha come nome il nome della relazione, come formato l'id della relazione  e valore il valore di un campo di join -> se la tabella secondaria non ha corrispondenze il valore è vuoto
                        //$keyList = array();
                        //foreach($rel["join_field"] as $jF) $keyList[] = DATALAYER_ALIAS_TABLE.".".$jF[0];
                        //$fieldList[] = implode("||','||",$keyList)." as $relationAliasTable";

                        //$groupBy = ' GROUP BY  ' . implode(', ', $groupByFieldList) . ', ' . $datalayerGeom;
                        //$fieldList[] = ' count(' . $relationAliasTable . '.' . $rel['join_field'][0][1] . ') as num_' . $idrel;

                        //if (!isset($this->aFeature['1n_count_fields']))
                        //    $this->aFeature['1n_count_fields'] = array();
                        //array_push($this->aFeature['1n_count_fields'], 'num_' . $idrel);
                    }

                    $joinList = array();
                    for ($i = 0; $i < count($rel["join_field"]); $i++) {
                        $joinList[] = DATALAYER_ALIAS_TABLE . "." . $rel["join_field"][$i][0] . "=" . $relationAliasTable . "." . $rel["join_field"][$i][1];
                        //$flagField = $relationAliasTable.".".$rel["join_field"][$i][1]." AS " .$relationAliasTable;   //tengo un campo della tabella in relazione per sapere in caso di secondarie se il dato � presente
                    }

                    $joinFields = implode(" AND ", $joinList);
                    $joinString = "$joinString left join " . $rel["table_schema"] . "." . $rel["table_name"] . " AS " . $relationAliasTable . " ON (" . $joinFields . ")";
                    //Se non sto visualizzando la secondaria e la relazione � 1 a molti genero il campo che dar� origine al link alla tabella
                }
            }

            $fieldString = implode(",", $fieldList);
        }

        $datalayerTable = "gc_geom FROM (SELECT " . DATALAYER_ALIAS_TABLE . "." . $datalayerKey . " as gc_objid," . DATALAYER_ALIAS_TABLE . "." . $datalayerGeom . " as gc_geom, $fieldString FROM $joinString $groupBy) AS foo";
        print_debug($datalayerTable, null, 'datalayer');

        return $datalayerTable;
    }

    private function _getMetadata() {
        $agmlType = array(1 => "Point", 2 => "Line", 3 => "Polygon", 4 => "Point");
        $ageometryType = array("point" => "point", "multipoint" => "multipoint", "linestring" => "line", "multilinestring" => "multiline", "polygon" => "polygon", "multipolygon" => "multipolygon");
        $metaText = '';
        $aMeta["ows_title"] = empty($this->aFeature["layer_title"]) ? $this->aFeature["layer_name"] : $this->aFeature["layer_title"];
        $aMeta["wms_title"] = empty($this->aFeature["layer_title"]) ? $this->aFeature["layer_name"] : $this->aFeature["layer_title"];

        if ($this->srsList) {
            $aMeta["ows_extent"] = $this->srsList[$this->aFeature["data_srid"]]["extent"];
            $aMeta["ows_srs"] = $this->srsList[$this->aFeature["data_srid"]]["epsg_code"];
        }
        if ($this->aFeature["queryable"] == 1) {

            $aMeta["gml_geometries"] = $this->aFeature["data_geom"];
            $aMeta["ows_onlineresource"] = $this->owsUrl;

            if ($this->aFeature["fields"]) {
                if ($this->aFeature["connection_type"] == MS_POSTGIS)
                    $aMeta["gml_featureid"] = "gc_objid";
                else
                    $aMeta["gml_featureid"] = $this->aFeature["data_unique"];
                $includeItems = array();
                foreach ($this->aFeature['fields'] as $field) {
                    if ($field['result_type'] != 5)
                        array_push($includeItems, $field['field_name']);
                }
                if (!empty($this->aFeature['1n_count_fields'])) {
                    foreach ($this->aFeature['1n_count_fields'] as $fieldName) {
                        array_push($includeItems, $fieldName);
                    }
                }
                if (!empty($includeItems)) {
                    $aMeta['ows_include_items'] = implode(',', $includeItems);
                    $aMeta['gml_include_items'] = implode(',', $includeItems);
                }
            } else {
                $aMeta["ows_include_items"] = "all";
                $aMeta["gml_include_items"] = "all";
                $aMeta["wms_include_items"] = "all";
                $aMeta["ows_exclude_items"] = $this->aFeature["data_geom"];
                $aMeta["gml_exclude_items"] = $this->aFeature["data_geom"];
                $aMeta["gml_featureid"] = $this->aFeature["data_unique"];
            }
            if (strpos($this->aFeature['metadata'], "gml_" . $this->aFeature["data_geom"] . "_type") === false) {
                if (array_key_exists($this->aFeature["data_type"], $ageometryType))
                    $aMeta["gml_" . $this->aFeature["data_geom"] . "_type"] = $ageometryType[$this->aFeature["data_type"]];
                else
                    $aMeta["gml_" . $this->aFeature["data_geom"] . "_type"] = $agmlType[$this->aFeature["layertype_id"]];
            }

            foreach ($this->aFeature['fields'] as $fieldId => $field) {
                $gmlType = $this->_getMetadataFieldDataType($field['data_type']);
                if ($gmlType && $field['field_name'] != 'layer')
                    $aMeta['gml_' . $field['field_name'] . '_type'] = $gmlType;
            }
        }

        if (!empty($this->aFeature['hidden']) && $this->aFeature["hidden"] == 1) {
            $aMeta["gc_hide_layer"] = '1';
        }
        if ($this->forcePrivate ||
                (!empty($this->aFeature['private']) && $this->aFeature["private"] == 1)) {
            $aMeta["gc_private_layer"] = '1';
        }

        $sql = "select af.filter_name, laf.required " .
                " from " . DB_SCHEMA . ".authfilter af inner join " . DB_SCHEMA . ".layer_authfilter laf using(filter_id) " .
                " where layer_id = ? ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->aFeature['layer_id']));
        $n = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $aMeta['gc_authfilter_' . $n] = $row['filter_name'];
            if (!empty($row['required']))
                $aMeta['gc_authfilter_' . $n . '_required'] = 1;
            $n++;
        }

        foreach ($aMeta as $key => $value) {
            $metaText .= "\t\"$key\"\t\"$value\"\n\t";
        }
        if (!empty($this->aFeature["metadata"]))
            $metaText .= "\t" . str_replace("\n", "\n\t\t", $this->aFeature["metadata"]);
        //$metaText;
        return $metaText;
    }

    private function _getClassText($aClass) {

        print_debug($aClass, null, 'classi');
        $clsText = array();
        $clsText[] = "\tNAME \"" . str_replace(" ", "_", $aClass["class_name"]) . "\"";
        if ($aClass['legendtype_id'] == 0) {
            $clsText[] = "METADATA \"gc_no_image\" \"1\" END";
        }
        if (!empty($aClass['keyimage'])) {
            $clsText[] = "KEYIMAGE \"" . $aClass["keyimage"] . "\"";
        }

        if (!empty($aClass["class_title"]))
            $clsText[] = "TITLE \"" . str_replace("\"", "'", $aClass["class_title"]) . "\"";
        if (!empty($aClass["classgroup_name"]))
            $clsText[] = "GROUP \"" . $aClass["classgroup_name"] . "\"";
        if (!empty($aClass["expression"]))
            $clsText[] = "EXPRESSION " . $aClass["expression"];

        if (!empty($aClass["class_text"])) {
            $clsText[] = "TEXT (" . $aClass["class_text"] . ")";
        } elseif (!empty($aClass["smbchar"])) {//simbolo true type
            $clsText[] = "TEXT (" . $aClass["smbchar"] . ")";
        }

        if (!empty($aClass["maxscale"]))
            $clsText[] = "MAXSCALEDENOM " . $aClass["maxscale"];
        if (!empty($aClass["minscale"]))
            $clsText[] = "MINSCALEDENOM " . $aClass["minscale"];
        if (!empty($aClass["class_template"]))
            $clsText[] = "TEMPLATE \"" . $aClass["class_template"] . "\"";
        if (!empty($aClass["class_def"]))
            $clsText[] = $aClass["class_def"];

        //Se ho impostato il font aggiungo la label
        if ($aClass["label_font"]) {
            $this->labels = true;
            $clsText[] = "LABEL";
            $clsText[] = "\tTYPE TRUETYPE";
            $clsText[] = "\tPARTIALS TRUE";
            $clsText[] = "\tFONT \"" . $aClass["label_font"] . "\"";
            if ($aClass["label_angle"])
                $clsText[] = "\tANGLE " . $aClass["label_angle"];
            if ($aClass["label_color"])
                $clsText[] = "\tCOLOR " . $aClass["label_color"];
            if ($aClass["label_bgcolor"] && $this->msVersion == '5')
                $clsText[] = "\tBACKGROUNDCOLOR " . $aClass["label_bgcolor"];
            if ($aClass["label_outlinecolor"])
                $clsText[] = "\tOUTLINECOLOR " . $aClass["label_outlinecolor"];
            if ($aClass["label_size"])
                $clsText[] = "\tSIZE " . $aClass["label_size"];
            if ($aClass["label_minsize"])
                $clsText[] = "\tMINSIZE " . $aClass["label_minsize"];
            if ($aClass["label_maxsize"])
                $clsText[] = "\tMAXSIZE " . $aClass["label_maxsize"];
            if ($aClass["label_position"])
                $clsText[] = "\tPOSITION " . $aClass["label_position"];
            if ($aClass["label_priority"])
                $clsText[] = "\tPRIORITY " . $aClass["label_priority"];
            if ($aClass["label_buffer"])
                $clsText[] = "\tBUFFER " . $aClass["label_buffer"];
            if ($aClass["label_force"])
                $clsText[] = "\tFORCE TRUE";
            if ($aClass["label_wrap"] == '#')
                $aClass["label_wrap"] = ' ';
            if ($aClass["label_wrap"])
                $clsText[] = "\tWRAP \"" . $aClass["label_wrap"] . "\"";
            if ($aClass["label_def"])
                $clsText[] = $aClass["label_def"];
            $clsText[] = "END";
        }

        $sql = "select style_id,angle,color,outlinecolor,bgcolor,size,minsize,maxsize,minwidth,width,style_def,symbol.symbol_name, pattern_def
                    from " . DB_SCHEMA . ".style left join " . DB_SCHEMA . ".symbol using (symbol_name) left join " . DB_SCHEMA . ".e_pattern using(pattern_id)
                    where class_id=? order by style_order DESC;";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($aClass["class_id"]));

        $res = $stmt->fetchAll();
        for ($i = 0; $i < count($res); $i++) {
            $aStyle = $res[$i];

            if (!empty($this->i18n)) {
                $aStyle = $this->i18n->translateRow($aStyle, 'style', $aStyle['style_id']);
            }

            $clsText[] = "STYLE";
            $clsText[] = $this->_getStyleText($aStyle);
            $clsText[] = "END";
        }
        return implode("\n\t\t", $clsText);
    }

    private function _getStyleText($aStyle) {

        $styText = array();
        if (!empty($aStyle["color"]))
            $styText[] = "COLOR " . $aStyle["color"];
        if (!empty($aStyle["symbol_name"]))
            $styText[] = "SYMBOL \"" . $aStyle["symbol_name"] . "\"";
        if (!empty($aStyle["bgcolor"]))
            $styText[] = "BACKGROUNDCOLOR " . $aStyle["bgcolor"];
        if (!empty($aStyle["outlinecolor"]))
            $styText[] = "OUTLINECOLOR " . $aStyle["outlinecolor"];
        if (!empty($aStyle["size"]))
            $styText[] = "SIZE " . $aStyle["size"];
        if (!empty($aStyle["minsize"]))
            $styText[] = "MINSIZE " . $aStyle["minsize"];
        if (!empty($aStyle["maxsize"]))
            $styText[] = "MAXSIZE " . $aStyle["maxsize"];
        if (!empty($aStyle["angle"]))
            $styText[] = "ANGLE " . $aStyle["angle"];
        if (isset($aStyle["width"]) && $aStyle["width"])
            $styText[] = "WIDTH " . $aStyle["width"];
        else
            $styText[] = "WIDTH 1"; //pach mapserver 5.6 non disegna un width di default
        if (!empty($aStyle["pattern_def"]) && $this->msVersion == '6')
            $styText[] = $aStyle["pattern_def"];
        if (!empty($aStyle["minwidth"]))
            $styText[] = "MINWIDTH " . $aStyle["minwidth"];
        if (!empty($aStyle["maxwidth"]))
            $styText[] = "MAXWIDTH " . $aStyle["maxwidth"];
        if ((!empty($aStyle["symbol_name"])))
            $this->aSymbols[$aStyle["symbol_name"]] = $aStyle["symbol_name"];
        if (!empty($aStyle["style_def"]))
            $styText[] = $aStyle["style_def"];
        $styleText = "\t" . implode("\n\t\t\t", $styText);
        return $styleText;
    }

    /**
     * Funzione utilizzato ancora in rpc.php
     * 
     * SERVE A MARCO??????
     * 
     * @param type $layerId
     * @return type
     */
    
    public function getFeatureField($layerId = null) {
        $result = Array();
        if ($layerId)
            $this->init($layerId);
        $data = $this->_getLayerData();
        $sqlField = "SELECT * FROM $data LIMIT 0;";
        $aFeature = $this->aFeature;
        foreach ($aFeature["fields"] as $fieldId => $field) {
            $relationName = ($field["relation"]) ? ($aFeature["relation"][$field["relation"]]["table_name"]) : ($aFeature["data"]);
            $relationSchema = ($field["relation"]) ? ($aFeature["relation"][$field["relation"]]["table_schema"]) : ($aFeature["table_schema"]);
            $relationConnStr = ($field["relation"]) ? ($aFeature["relation"][$field["relation"]]["connection_string"]) : ($aFeature["connection_string"]);
            $result[$fieldId] = Array(
                "id" => $fieldId,
                "name" => $field["field_name"],
                "title" => $field["field_title"],
                "table" => $relationName,
                "schema" => $relationSchema,
                "connection_string" => $relationConnStr,
                "data_type" => $field["data_type"]
            );
        }
        return $result;
    }

    private function _getMetadataFieldDataType($typeId) {
        if ($typeId == 1 || $typeId == 3)
            return 'Character';
        return false;
    }

}
