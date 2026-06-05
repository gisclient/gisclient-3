<?php

namespace GisClient\MapServer\Writer;

/**
 * Port of the legacy gcFeature (public/admin/lib/gcFeature.class.php)
 * used by the OptimizedMapfileWriter.
 *
 * The text-building code is copied verbatim from gcFeature so the produced
 * mapfile bytes are identical. The only difference is the data access:
 * instead of per-layer / per-class queries, rows come from a prefilled
 * MapfileDataCache.
 *
 * IMPORTANT: when the mapfile output of gcFeature changes, this class must
 * be updated accordingly (verified via `gisclient:refresh-mapfile --compare`)
 * until the legacy writer is removed.
 */
class FeatureTextBuilder
{
    public $owsUrl;
    public $labels = false;
    public $aSymbols;
    public $srsList;
    public $srsParams;
    public $msVersion;
    public $forcePrivate = false;

    /**
     * @var MapfileDataCache
     */
    private $cache;

    private $i18n;

    /**
     * Container of feature information
     *
     * @var array|null
     */
    private $aFeature;

    /**
     * @param \GCi18n|null $i18n
     */
    public function __construct(MapfileDataCache $cache, $i18n = null)
    {
        $this->cache = $cache;
        $this->i18n = $i18n;
        $this->msVersion = substr(ms_GetVersionInt(), 0, 1);
    }

    public function initFeature($layerId)
    {
        $this->forcePrivate = false;

        $qRelation = [];
        $qField = [];

        // Costruzione dell'oggetto Feature
        // (legacy: per-layer field query, see gcFeature::initFeature)
        foreach ($this->cache->getFieldRows($layerId) as $row) {
            if (!empty($this->i18n)) {
                $row = $this->i18n->translateRow($row, 'field', $row['field_id'], ['field_name', 'field_header']);
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
            $f = [];
            if ($relationId = $row["relation_id"]) {
                if (($row["data_field_1"]) && ($row["table_field_1"])) {
                    $f[] = [trim($row["data_field_1"]), trim($row["table_field_1"])];
                }
                if (($row["data_field_2"]) && ($row["table_field_2"])) {
                    $f[] = [trim($row["data_field_2"]), trim($row["table_field_2"])];
                }
                if (($row["data_field_3"]) && ($row["table_field_3"])) {
                    $f[] = [trim($row["data_field_3"]), trim($row["table_field_3"])];
                }
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
        // (legacy: per-layer feature query, see gcFeature::initFeature)
        $aFeature = $this->cache->getLayerRow($layerId);
        if ($aFeature === null) {
            $this->aFeature = null;
            return;
        }
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
        $aFeature["relation"] = $qRelation;
        $aFeature["fields"] = $qField;
        $aFeature["link"] = [];
        $aFeature["tileindex"] = false;

        $this->aFeature = $aFeature;
    }

    /**
     * Return data of the current feature
     *
     * @return array
     */
    public function getFeatureData()
    {
        return $this->aFeature;
    }

    /**
     * Set feature data
     */
    public function setFeatureData(array $aFeature)
    {
        $this->aFeature = $aFeature;
    }

    public function isEditable()
    {
        if ($this->aFeature['connection_type'] != 6) {
            return false;
        }
        if ($this->aFeature['queryable'] != 1) {
            return false;
        }
        foreach ($this->aFeature['fields'] as $k => $v) {
            if ($v['editable'] == 1) {
                return true;
            }
        }
        return false;
    }

    public function getTinyOWSLayerParams()
    {
        //TODO: così funziona solo per le definizioni DB_NAME/DB_SCHEMA
        [$dbName, $dbSchema] = explode('/', $this->aFeature['catalog_path']);
        return [
            'schema' => $dbSchema,
            'database' => $dbName,
            'name' => $this->aFeature['data'],
            'feature' => $this->aFeature['layergroup_name'] . '.' . $this->aFeature['layer_name'],
            'title' => $this->aFeature['layer_name'],
        ];
    }

    public function getLayerName()
    {
        return $this->aFeature['layer_name'];
    }

    public function isPrivate()
    {
        return $this->forcePrivate || ($this->aFeature['private'] > 0);
    }

    // Used to force private layer in mapset is private
    public function setPrivate($private)
    {
        $this->forcePrivate = $private;
    }

    public function getLayerText($layergroupName, $layergroup)
    {
        if (!$this->aFeature) {
            return false;
        }

        // translate layergroup
        if (!empty($this->i18n)) {
            $layergroup = $this->i18n->translateRow($layergroup, 'layergroup', $layergroup['layergroup_id']);
        }

        $maxScale = $layergroup['layergroup_maxscale'];
        $minScale = $layergroup['layergroup_minscale'];
        // NOTE: kept although the return value is unused — it populates
        // $this->aFeature['1n_count_fields'] which _getMetadata() reads below
        // (same behavior as legacy gcFeature::getLayerText).
        $this->_getLayerData();
        $this->aFeature['layergroup_name'] = $layergroupName;
        $this->aSymbols = []; //Elenco dei simboli usati nelle classi della feature
        $aMapservUnitDef = [
            1 => "pixels",
            2 => "feet",
            3 => "inches",
            4 => "kilometers",
            5 => "meters",
            6 => "miles",
            7 => "nauticalmiles",
        ];
        $aGCLayerType = [
            1 => "POINT",
            2 => "LINE",
            3 => "POLYGON",
            4 => "RASTER",
            10 => 'RASTER',
            11 => 'CHART',
        ]; //10 TILERASTER
        $layText = [];
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
            if (!empty($this->srsParams[$this->aFeature["data_srid"]])) {
                $layText[] = "\t\"+towgs84=" . $this->srsParams[$this->aFeature["data_srid"]] . "\"";
            }
            $layText[] = "END";
        }

        $this->_getLayerConnection($layText);
        if (!empty($this->aFeature["data_extent"])) {
            $layText[] = "EXTENT " . $this->aFeature["data_extent"];
        }
        if (!empty($this->aFeature["sizeunits_id"])) {
            $layText[] = "SIZEUNITS " . $aMapservUnitDef[$this->aFeature["sizeunits_id"]];
        }
        if (!empty($this->aFeature['maxscale'])) {
            $layText[] = 'MAXSCALEDENOM ' . $this->aFeature['maxscale'];
        } elseif (!empty($maxScale)) {
            $layText[] = 'MAXSCALEDENOM ' . $maxScale;
        }
        if (!empty($this->aFeature['minscale'])) {
            $layText[] = 'MINSCALEDENOM ' . $this->aFeature['minscale'];
        } elseif (!empty($minScale)) {
            $layText[] = 'MINSCALEDENOM ' . $minScale;
        }
        if (!empty($this->aFeature["maxfeatures"]) && $this->aFeature["maxfeatures"] > 0) {
            $layText[] = "MAXFEATURES " . $this->aFeature["maxfeatures"];
        }
        if (!empty($this->aFeature["tolerance"])) {
            $layText[] = "TOLERANCE " . $this->aFeature["tolerance"];
        }
        if (!empty($this->aFeature["toleranceunits"])) {
            $layText[] = "TOLERANCEUNITS " . $aMapservUnitDef[$this->aFeature["toleranceunits_id"]];
        }
        if (!empty($this->aFeature["template"])) {
            $layText[] = "TEMPLATE \"" . $this->aFeature["template"] . "\"";
        }
        if (!empty($this->aFeature["header"])) {
            $layText[] = "HEADER \"" . $this->aFeature["header"] . "\"";
        };
        if (!empty($this->aFeature["footer"])) {
            $layText[] = "FOOTER \"" . $this->aFeature["footer"] . "\"";
        };
        if (!empty($this->aFeature["opacity"])) {
            $layText[] = "COMPOSITE";
            $layText[] = "\tOPACITY " . $this->aFeature["opacity"];
            $layText[] = "END";
        }
        if (!empty($this->aFeature["symbolscale"])) {
            $layText[] = "SYMBOLSCALEDENOM " . $this->aFeature["symbolscale"];
        }

        //classi:
        // (legacy: per-layer class query, see gcFeature::getLayerText)
        $res = $this->cache->getClassRows($this->aFeature["layer_id"]);

        //Solo se presenti classi
        if (count($res) > 0) {
            if (!empty($this->aFeature["labelitem"])) {
                $layText[] = "LABELITEM \"" . $this->aFeature["labelitem"] . "\"";
            }
            if (!empty($this->aFeature["labelminscale"])) {
                $layText[] = "LABELMINSCALEDENOM " . $this->aFeature["labelminscale"];
            }
            if (!empty($this->aFeature["labelmaxscale"])) {
                $layText[] = "LABELMAXSCALEDENOM " . $this->aFeature["labelmaxscale"];
            }
            if (!empty($this->aFeature["classitem"])) {
                $layText[] = "CLASSITEM \"" . $this->aFeature["classitem"] . "\"";
            }
        }

        for ($i = 0; $i < count($res); $i++) {
            if (!empty($this->i18n)) {
                $res[$i] = $this->i18n->translateRow($res[$i], 'class', $res[$i]['class_id']);
            }

            $layText[] = "CLASS";
            $layText[] = $this->_getClassText($res[$i]);
            $layText[] = "END";
        }

        if ($this->labels && $this->aFeature['postlabelcache'] == 1) {
            $layText[] = "POSTLABELCACHE TRUE";
        }
        if (!empty($this->aFeature["layer_def"])) {
            $layText[] = $this->aFeature["layer_def"];
        }
        $layText[] = "END";

        return implode("\n\t", $layText);
    }

    private function _getLayerConnection(&$layText)
    {
        if ($this->aFeature["layertype_id"] == 10 && !$this->aFeature["tileindex"]) {//TILERASTER
            $layText[] = "TILEINDEX \"" . $this->aFeature["layer_name"] . ".TILEINDEX\"";
            $layText[] = "TILEITEM \"location\"";
        } else {
            $closedDefer = defined('LAYER_CLOSE_CONNECTION_DEFER') ? LAYER_CLOSE_CONNECTION_DEFER : false;
            switch ($this->aFeature["connection_type"]) {
                case MS_SHAPEFILE: //Local folder shape and raster
                    $filePath = $this->aFeature["filePath"];
                    if (substr($filePath, -1) != "/") {
                        $filePath .= "/";
                    }
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
                    if (!empty($this->aFeature["data_unique"])) {
                        $sData .= " USING UNIQUE gc_objid";
                    }
                    if (!empty($this->aFeature["data_srid"])) {
                        $sData .= " USING SRID=" . $this->aFeature["data_srid"];
                    }
                    $layText[] = "DATA \"$sData\"";
                    if (!empty($this->aFeature["data_filter"])) {
                        $layText[] = "PROCESSING \"NATIVE_FILTER=" . $this->aFeature["data_filter"] . "\"";
                    }
                    if ($closedDefer) {
                        $layText[] = "PROCESSING \"CLOSE_CONNECTION=DEFER\"";
                    }
                    if ($this->aFeature["queryable"] == 1) {
                        $layText[] = "DUMP TRUE";
                    }
                    break;

                case MS_ORACLESPATIAL:
                    $layText[] = "CONNECTIONTYPE ORACLESPATIAL";
                    $layText[] = "CONNECTION \"" . $this->aFeature["catalog_path"] . "\"";
                    $sData = $this->_getOracleLayerData();
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
                    if (!empty($this->aFeature["data_filter"])) {
                        $layText[] = "PROCESSING \"NATIVE_FILTER=" . $this->aFeature["data_filter"] . "\"";
                    }
                    $layText[] = "PROCESSING \"CLOSE_CONNECTION=DEFER\"";
                    if ($this->aFeature["queryable"] == 1) {
                        $layText[] = "DUMP TRUE";
                    }
                    break;

                case MS_SDE:
                    break;

                case MS_OGR:
                    $layText[] = "CONNECTIONTYPE OGR";
                    $layText[] = "CONNECTION \"" . $this->aFeature["catalog_path"] . "\"";
                    $layText[] = "DATA \"" . $this->aFeature["data"] . "\"";
                    if ($this->aFeature["queryable"] == 1) {
                        $layText[] = "DUMP TRUE";
                    }
                    break;
                case MS_GRATICULE:
                    break;
                case MS_MYGIS:
                    break;
                case MS_PLUGIN:
                    break;
            }
        }
    }

    public function getTileIndexLayer()
    {
        $layText = [];
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
    private function _getOracleLayerData()
    {
        $string = $this->aFeature['data_geom'] . ' FROM ';
        return $string . $this->aFeature['data'];
    }

    /**
     * Construct the DATA statement for the mapfile, http://mapserver.org/mapfile/layer.html
     * @return string
     */
    private function _getLayerData()
    {
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
        } elseif (preg_match("|select (.+) from (.+)|i", $datalayerTable)) { //Definizione alias della tabella o vista pricipale (nel caso l'utente abbia definito una vista)  (da valutare se ha senso)
            $datalayerTable = "($datalayerTable) AS " . DATALAYER_ALIAS_TABLE;
        } else {
            $datalayerTable = $datalayerSchema . "." . $datalayerTable . " AS " . DATALAYER_ALIAS_TABLE;
        }

        $joinString = $datalayerTable;
        $fieldString = "*";
        $groupBy = '';

        //Elenco dei campi definiti
        if ($aFeature["fields"]) {
            $fieldList = [];

            // collection of all fields which should be listed in the GROUP BY clause
            // the primary key is certainly part of it
            // with PostgreSQL 9.1 and later, the primary key would be enough.
            $groupByFieldList = [DATALAYER_ALIAS_TABLE . "." . $datalayerKey];

            foreach ($aFeature["fields"] as $idField => $aField) {
                if ($aField["relation"] == 0 || $aFeature["relation"][$aField["relation"]]["relation_type"] == 1) {
                    if ($aField["relation"] != 0) {//Il campo appartiene alla relazione e non alla tabella del layer
                        $idRelation = $aField["relation"];
                        $aliasTable = $aFeature["relation"][$idRelation]["name"];
                    } else {
                        $aliasTable = DATALAYER_ALIAS_TABLE;
                    }

                    //Campi calcolati non metto tabella.campo
                    if ($aField["formula"]) {
                        $fieldName = $aField["formula"] . " AS " . $aField["field_name"];
                        $groupByFieldList[] = $aField['field_name'];
                    } else {
                        $fieldName = $aliasTable . "." . $aField["field_name"];
                        $groupByFieldList[] = $aliasTable . '.' . $aField['field_name'];
                    }
                    $fieldList[] = $fieldName;
                }
            }

            //Elenco delle relazioni
            $joinString = $datalayerTable;
            if ($aRelation = $aFeature["relation"]) {
                foreach ($aRelation as $idrel => $rel) {
                    $relationAliasTable = NameReplace($rel["name"]);

                    //TODO RELAZIONI 1-MOLTI IN GC3
                    if ($rel["relation_type"] == 2) {
                        //aggiungo un campo che ha come nome il nome della relazione, come formato l'id della relazione  e valore il valore di un campo di join -> se la tabella secondaria non ha corrispondenze il valore è vuoto
                        $keyList = [];
                        foreach ($rel["join_field"] as $jF) {
                            $keyList[] = DATALAYER_ALIAS_TABLE . "." . $jF[0];
                        }
                        $fieldList[] = implode("||','||", $keyList) . " as $relationAliasTable";

                        $groupBy = ' GROUP BY  ' . implode(', ', $groupByFieldList) . ', ' . $datalayerGeom;
                        $fieldList[] = ' count(' . $relationAliasTable . '.' . $rel['join_field'][0][1] . ') as num_' . $idrel;

                        if (!isset($this->aFeature['1n_count_fields'])) {
                            $this->aFeature['1n_count_fields'] = [];
                        }
                        array_push($this->aFeature['1n_count_fields'], 'num_' . $idrel);
                    }

                    $joinList = [];
                    for ($i = 0; $i < count($rel["join_field"]); $i++) {
                        $joinList[] = DATALAYER_ALIAS_TABLE . "." . $rel["join_field"][$i][0] . "=" . $relationAliasTable . "." . $rel["join_field"][$i][1];
                    }

                    $joinFields = implode(" AND ", $joinList);
                    $joinString = "$joinString left join " . $rel["table_schema"] . "." . $rel["table_name"] . " AS " . $relationAliasTable . " ON (" . $joinFields . ")";
                }
            }

            $fieldString = implode(",", $fieldList);
        }

        $datalayerTable = "gc_geom FROM (SELECT " . DATALAYER_ALIAS_TABLE . "." . $datalayerKey . " as gc_objid," . DATALAYER_ALIAS_TABLE . "." . $datalayerGeom . " as gc_geom, $fieldString FROM $joinString $groupBy) AS foo";

        return $datalayerTable;
    }

    private function _getMetadata()
    {
        $agmlType = [
            1 => "Point",
            2 => "Line",
            3 => "Polygon",
            4 => "Point",
        ];
        $ageometryType = [
            "point" => "point",
            "multipoint" => "multipoint",
            "linestring" => "line",
            "multilinestring" => "multiline",
            "polygon" => "polygon",
            "multipolygon" => "multipolygon",
        ];
        $metaText = '';
        $aMeta["ows_title"] = empty($this->aFeature["layer_title"]) ? $this->aFeature["layer_name"] : $this->aFeature["layer_title"];
        $aMeta["wms_title"] = empty($this->aFeature["layer_title"]) ? $this->aFeature["layer_name"] : $this->aFeature["layer_title"];

        if ($this->srsList) {
            $aMeta["ows_extent"] = $this->srsList[$this->aFeature["data_srid"]]["extent"];
            $aMeta["ows_srs"] = $this->srsList[$this->aFeature["data_srid"]]["epsg_code"];
        }

        // WFS
        $aMeta["wfs_enable_request"] = "!*"; // disabled by default
        if ($this->aFeature["queryable"] == 1) {
            $aMeta["wfs_enable_request"] = "*"; // enable all WFS requests
            $aMeta["gml_geometries"] = $this->aFeature["data_geom"];
            $aMeta["ows_onlineresource"] = $this->owsUrl;

            if ($this->aFeature["fields"]) {
                if ($this->aFeature["connection_type"] == MS_POSTGIS) {
                    $aMeta["gml_featureid"] = "gc_objid";
                } else {
                    $aMeta["gml_featureid"] = $this->aFeature["data_unique"];
                }
                $includeItems = [];
                foreach ($this->aFeature['fields'] as $field) {
                    if ($field['result_type'] != 5) {
                        array_push($includeItems, $field['field_name']);
                    }
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
                if (array_key_exists($this->aFeature["data_type"], $ageometryType)) {
                    $aMeta["gml_" . $this->aFeature["data_geom"] . "_type"] = $ageometryType[$this->aFeature["data_type"]];
                } else {
                    $aMeta["gml_" . $this->aFeature["data_geom"] . "_type"] = $agmlType[$this->aFeature["layertype_id"]];
                }
            }

            foreach ($this->aFeature['fields'] as $fieldId => $field) {
                $gmlType = $this->_getMetadataFieldDataType($field['data_type']);
                if ($gmlType && $field['field_name'] != 'layer') {
                    $aMeta['gml_' . $field['field_name'] . '_type'] = $gmlType;
                }
            }
        }

        if (!empty($this->aFeature['hidden']) && $this->aFeature["hidden"] == 1) {
            $aMeta["gc_hide_layer"] = '1';
        }
        if ($this->forcePrivate ||
                (!empty($this->aFeature['private']) && $this->aFeature["private"] == 1)) {
            $aMeta["gc_private_layer"] = '1';
        }

        foreach ($aMeta as $key => $value) {
            $metaText .= "\t\"$key\"\t\"$value\"\n\t";
        }
        if (!empty($this->aFeature["metadata"])) {
            $metaText .= "\t" . str_replace("\n", "\n\t\t", $this->aFeature["metadata"]);
        }
        return $metaText;
    }

    private function _getClassText($aClass)
    {
        $clsText = [];
        $clsText[] = "\tNAME \"" . str_replace(" ", "_", $aClass["class_name"]) . "\"";
        if ($aClass['legendtype_id'] == 0) {
            $clsText[] = "METADATA \"gc_no_image\" \"1\" END";
        }
        if (!empty($aClass['keyimage'])) {
            $clsText[] = "KEYIMAGE \"" . $aClass["keyimage"] . "\"";
        }

        if (!empty($aClass["class_title"])) {
            $clsText[] = "TITLE \"" . str_replace("\"", "'", $aClass["class_title"]) . "\"";
        }
        if (!empty($aClass["classgroup_name"])) {
            $clsText[] = "GROUP \"" . $aClass["classgroup_name"] . "\"";
        }
        if (!empty($aClass["expression"])) {
            $clsText[] = "EXPRESSION " . $aClass["expression"];
        }
        if (ms_GetVersionInt() < 60000) {
            // MapServer 5
            if (!empty($aClass["class_text"])) {
                $clsText[] = "TEXT (" . $aClass["class_text"] . ")";
            } elseif (!empty($aClass["smbchar"])) {//simbolo true type
                $clsText[] = "TEXT (" . $aClass["smbchar"] . ")";
            }
        }

        if (!empty($aClass["maxscale"])) {
            $clsText[] = "MAXSCALEDENOM " . $aClass["maxscale"];
        }
        if (!empty($aClass["minscale"])) {
            $clsText[] = "MINSCALEDENOM " . $aClass["minscale"];
        }
        if (!empty($aClass["class_template"])) {
            $clsText[] = "TEMPLATE \"" . $aClass["class_template"] . "\"";
        }
        if (!empty($aClass["class_def"])) {
            $clsText[] = $aClass["class_def"];
        }

        //Se ho impostato il font aggiungo la label
        if ($aClass["label_font"]) {
            $this->labels = true;
            $clsText[] = "LABEL";
            $clsText[] = "\tTYPE TRUETYPE";
            $clsText[] = "\tPARTIALS TRUE";
            $clsText[] = "\tFONT \"" . $aClass["label_font"] . "\"";
            if (ms_GetVersionInt() >= 60000) {
                if (!empty($aClass["class_text"])) {
                    $clsText[] = "\tTEXT '" . $aClass["class_text"] . "'";
                } elseif (!empty($aClass["smbchar"])) {//simbolo true type
                    $clsText[] = "\tTEXT '" . $aClass["smbchar"] . "'";
                }
            }
            if ($aClass["label_angle"]) {
                $clsText[] = "\tANGLE " . $aClass["label_angle"];
            }
            if ($aClass["label_color"]) {
                $clsText[] = "\tCOLOR " . $aClass["label_color"];
            }
            if ($aClass["label_bgcolor"] && $this->msVersion == '5') {
                $clsText[] = "\tBACKGROUNDCOLOR " . $aClass["label_bgcolor"];
            }
            if ($aClass["label_outlinecolor"]) {
                $clsText[] = "\tOUTLINECOLOR " . $aClass["label_outlinecolor"];
            }
            if ($aClass["label_size"]) {
                $clsText[] = "\tSIZE " . $aClass["label_size"];
            }
            if ($aClass["label_minsize"]) {
                $clsText[] = "\tMINSIZE " . $aClass["label_minsize"];
            }
            if ($aClass["label_maxsize"]) {
                $clsText[] = "\tMAXSIZE " . $aClass["label_maxsize"];
            }
            if ($aClass["label_position"]) {
                $clsText[] = "\tPOSITION " . $aClass["label_position"];
            }
            if ($aClass["label_priority"]) {
                $clsText[] = "\tPRIORITY " . $aClass["label_priority"];
            }
            if ($aClass["label_buffer"]) {
                $clsText[] = "\tBUFFER " . $aClass["label_buffer"];
            }
            if ($aClass["label_force"]) {
                $clsText[] = "\tFORCE TRUE";
            }
            if ($aClass["label_wrap"] == '#') {
                $aClass["label_wrap"] = ' ';
            }
            if ($aClass["label_wrap"]) {
                $clsText[] = "\tWRAP \"" . $aClass["label_wrap"] . "\"";
            }
            if ($aClass["label_def"]) {
                $clsText[] = $aClass["label_def"];
            }
            $clsText[] = "END";
        }

        // (legacy: per-class style query, see gcFeature::_getClassText)
        $res = $this->cache->getStyleRows($aClass["class_id"]);
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

    private function _getStyleText($aStyle)
    {
        $styText = [];
        if (!empty($aStyle["color"])) {
            $styText[] = "COLOR " . $aStyle["color"];
        }
        if (!empty($aStyle["symbol_name"])) {
            $styText[] = "SYMBOL \"" . $aStyle["symbol_name"] . "\"";
        }
        if (!empty($aStyle["bgcolor"])) {
            $styText[] = "BACKGROUNDCOLOR " . $aStyle["bgcolor"];
        }
        if (!empty($aStyle["outlinecolor"])) {
            $styText[] = "OUTLINECOLOR " . $aStyle["outlinecolor"];
        }
        if (!empty($aStyle["size"])) {
            $styText[] = "SIZE " . $aStyle["size"];
        }
        if (!empty($aStyle["minsize"])) {
            $styText[] = "MINSIZE " . $aStyle["minsize"];
        }
        if (!empty($aStyle["maxsize"])) {
            $styText[] = "MAXSIZE " . $aStyle["maxsize"];
        }
        if (!empty($aStyle["angle"])) {
            $styText[] = "ANGLE " . $aStyle["angle"];
        }
        if (isset($aStyle["width"]) && $aStyle["width"]) {
            $styText[] = "WIDTH " . $aStyle["width"];
        } else {
            $styText[] = "WIDTH 1"; //pach mapserver 5.6 non disegna un width di default
        }
        if (!empty($aStyle["pattern_def"]) && $this->msVersion == '6') {
            $styText[] = $aStyle["pattern_def"];
        }
        if (!empty($aStyle["minwidth"])) {
            $styText[] = "MINWIDTH " . $aStyle["minwidth"];
        }
        if (!empty($aStyle["maxwidth"])) {
            $styText[] = "MAXWIDTH " . $aStyle["maxwidth"];
        }
        if ((!empty($aStyle["symbol_name"]))) {
            $this->aSymbols[$aStyle["symbol_name"]] = $aStyle["symbol_name"];
        }
        if (!empty($aStyle["style_def"])) {
            $styText[] = $aStyle["style_def"];
        }
        $styleText = "\t" . implode("\n\t\t\t", $styText);
        return $styleText;
    }

    /**
     * Convert datatype into gml type
     *
     * @see https://mapserver.org/ogc/wfs_server.html (gml_[item name]_type)
     * @param integer $typeId
     * @return string|false
     */
    private function _getMetadataFieldDataType($typeId)
    {
        switch ($typeId) {
            case 1:
            case 3:
                return 'Character';
            case 2:
                return 'Real';
        }

        return false;
    }
}
