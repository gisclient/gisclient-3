<?php
/*
GisClient

Copyright (C) 2008 - 2010  Roberto Starnini - Gis & Web S.r.l. -info@gisweb.it

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
define('WMS_LAYER_TYPE',1);
define('WMTS_LAYER_TYPE',2);
define('WMS_CACHE_LAYER_TYPE',3);
define('TMS_LAYER_TYPE',6);

class gcMapfile{
    var $db;
    var $projectName='';
    private $projectTitle;
    var $symbolText='';
    var $layerText='';
    var $mapTitle='';
    var $mapAbstract='';
    var $printMap = false;
    var $serviceOnlineresource='';
    var $layersWithAccessConstraints = array();
    var $srsParams = array();
    var $epsgList;
    var $mapInfo=array();
    var $srsCustom=array();
    private $projectMaxScale;
    private $projectSrid;
    private $xCenter;
    private $yCenter;
    private $msVersion;
    private $grids = array();
    private $target = 'public';
    private $iconSize = array(16,10);
    private $tinyOWSLayers = array();

    
    private $i18n;
    private $languageId;
    
    function __construct ($languageId = null){
        $this->db = GCApp::getDB();
        $this->languageId = $languageId;
        $this->msVersion = substr(ms_GetVersionInt(),0,1);
        if(isset($_SESSION['save_to_tmp_map']) && $_SESSION['save_to_tmp_map'] === true ) $this->target = 'tmp';
    }
    
    function __destruct (){
    
        unset($this->db);
        unset($this->filter);
        unset($this->mapError);
        
    }
    
    public function setIconSize($size) {
        $this->iconSize = $size;
    }
    public function setTarget($target) {
        $this->target = $target;
    }

    function writeMap($keytype,$keyvalue){
        
        $sqlParams = array();
        
        if($keytype=="mapset") {    //GENERO IL MAPFILE PER IL MAPSET
                $filter="mapset.mapset_name=:keyvalue";
                $joinMapset="INNER JOIN ".DB_SCHEMA.".mapset using (project_name) INNER JOIN ".DB_SCHEMA.".mapset_layergroup using (mapset_name,layergroup_id)";
                $fieldsMapset="mapset_layergroup.status as layergroup_status, mapset_name,mapset_title,mapset_extent,mapset_srid,mapset.maxscale as mapset_maxscale,mapset_def,";
                $sqlParams['keyvalue'] = $keyvalue;
                
                $sql = 'select project_name from '.DB_SCHEMA.'.mapset where mapset_name=:mapset';
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array('mapset'=>$keyvalue));
                $projectName = $stmt->fetchColumn(0);
                
        } elseif($keytype=="project") { //GENERO TUTTI I MAPFILE PER IL PROGETTO OPPURE UNICO MAPFILE PER PROGETTO
            $filter="project.project_name=:keyvalue";
            if(defined('PROJECT_MAPFILE') && PROJECT_MAPFILE) {
                $joinMapset="";
                $fieldsMapset = '1 as layergroup_status, project_name as mapset_name, project_title as mapset_title, project_srid as mapset_srid, null as mapset_extent,';
            } else {
                $joinMapset="INNER JOIN ".DB_SCHEMA.".mapset using (project_name) INNER JOIN ".DB_SCHEMA.".mapset_layergroup using (mapset_name,layergroup_id)";
                $fieldsMapset="mapset_layergroup.status as layergroup_status, mapset_name,mapset_title,mapset_extent,mapset_srid,mapset.maxscale as mapset_maxscale,mapset_def,";               
            }
            $sqlParams['keyvalue'] = $keyvalue;
            $projectName = $keyvalue;
         
        } elseif($keytype=="layergroup") { //GENERO IL MAPFILE PER IL LAYERGROUP NEL SISTEMA DI RIF DEL PROGETTO (PREVIEW)
                $filter="layergroup.layergroup_id=:keyvalue";
                $joinMapset="";
                $fieldsMapset="1 as layergroup_status, layergroup_name as mapset_name,layergroup_title as mapset_title,project.max_extent_scale as mapset_maxscale,layer.data_srid as mapset_srid,layer.data_extent as mapset_extent,";         
                $sqlParams['keyvalue'] = $keyvalue;
    
        
        } elseif($keytype="print"){ //GENERO UN MAPFILE PER LA STAMPA
                $_in = GCApp::prepareInStatement($keyvalue);
                $sqlParams = $_in['parameters'];
                $inQuery = $_in['inQuery'];

            $this->printMap = true;
            $filter = "project_name||'.'||theme_name||'.'||layergroup_name in (".$inQuery.")";
        }
        
        if(!empty($this->languageId)) { 
          // inizializzo l'oggetto i18n per le traduzioni
            $this->i18n = new GCi18n($projectName, $this->languageId);
        }
        

        $sql="select project_name,".$fieldsMapset."base_url,max_extent_scale,project_srid,xc,yc,outputformat_mimetype,
        theme_title,theme_name,theme_single,layergroup_name,layergroup_title,layergroup_id,layergroup_description,layergroup_maxscale,layergroup_minscale,
        isbaselayer,layergroup_single,tree_group,tiletype_id,owstype_id,layer_id,layer_name,layer_title,layer.hidden,layertype_id, project_title, set_extent
        from ".DB_SCHEMA.".layer 
        INNER JOIN ".DB_SCHEMA.".layergroup  using (layergroup_id) 
        INNER JOIN ".DB_SCHEMA.".theme using (theme_id)
        INNER JOIN ".DB_SCHEMA.".project using (project_name) ".$joinMapset."
        LEFT JOIN ".DB_SCHEMA.".e_outputformat using (outputformat_id)
        LEFT JOIN ".DB_SCHEMA.".catalog using (catalog_id, project_name)
        where ".$filter." order by layer_order DESC,layergroup_order;"; 
        //where ".$filter." order by theme_order desc, layergroup_order desc, layer_order desc;";   SERVE PER SCRIVERE I LAYER NEL MAPFILE UTILIZZANDO L'ORDINE RELATIVO TEMA-LAYERGROUP-LAYER. Sarebbe da sviluppare la funzione che permette all'utente di sceglierlo a livello di progetto

        print_debug($sql,null,'writemap');
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute($sqlParams);
        $res = $stmt->fetchAll();

        if($stmt->rowCount() == 0) {
            $this->mapError=200;//Mancano i layers
            echo 'NO LAYERS';
            return;
        }       

        $aLayer=$res[0];
        $this->projectName = $aLayer["project_name"];
        $this->projectSrid = $aLayer["project_srid"];
        $this->xCenter = $aLayer['xc'];
        $this->yCenter = $aLayer['yc'];

        //SCALA MASSIMA DEL PROGETTO
        $projectMaxScale = floatval($aLayer["max_extent_scale"])?floatval($aLayer["max_extent_scale"]):100000000;
        $projectExtent = $this->_calculateExtentFromCenter($projectMaxScale, $this->projectSrid);   
        $this->projectMaxScale = $projectMaxScale;

        $mapText=array();
        $mapSrid=array();
        $mapExtent=array();
        $symbolsList=array();
        $oFeature = new gcFeature($this->i18n);

        //mapproxy
        $this->mpxLayers=array();
        $this->mpxCaches=array();

        $this->_setMapProjections();
        $oFeature->srsParams = $this->srsParams;

        if($this->printMap) $mapName = time().'_print';
        
        $defaultLayers = array();
        foreach ($res as $aLayer){
        
            $mapName = $aLayer["mapset_name"];
            $layergroupName = NameReplace($aLayer["layergroup_name"]);
            $layerTreeGroup = $aLayer["tree_group"];
            $mapSrid[$mapName] = $aLayer["mapset_srid"];    
            $mapTitle[$mapName] = $aLayer["mapset_title"];
            $mapExtent[$mapName] = $aLayer["mapset_extent"];
            $mapMaxScale[$mapName] = floatval($aLayer["mapset_maxscale"])?min(floatval($aLayer["mapset_maxscale"]), $projectMaxScale):$projectMaxScale;

            $oFeature->initFeature($aLayer["layer_id"]);
                        
            $oFeatureData = $oFeature->getFeatureData();
            if ($aLayer['set_extent'] === 1 && empty($oFeatureData['data_extent'])) {
                // use mapset extent if layer extent is not set
                // the layer extent is important to make wms layers work in some desktop gis clients
                $oFeatureData['data_extent'] = $aLayer["mapset_extent"];
                $oFeature->setFeatureData($oFeatureData);
            }

            // Force layer to be private if the mapset is private
            if (!empty($aLayer["mapset_private"]) && $aLayer["mapset_private"]) {
                $oFeature->setPrivate(true);
            }
        
            $layerText = $oFeature->getLayerText($layergroupName,$layerTreeGroup,$aLayer["layergroup_maxscale"],$aLayer["layergroup_minscale"]);
            if($oFeature->isPrivate()) array_push($this->layersWithAccessConstraints, $oFeature->getLayerName());

            if(!empty($this->i18n)) {
                $aLayer = $this->i18n->translateRow($aLayer, 'layergroup', $aLayer['layergroup_id'], array('layergroup_title', 'layergroup_description'));
            }
            
            if($layerText){
                $mapText[$mapName][] = $layerText;
                if(!isset($symbolsList[$mapName]))
                    $symbolsList[$mapName] = $oFeature->aSymbols;
                else{
                    $symbolsList[$mapName] = array_merge($symbolsList[$mapName],$oFeature->aSymbols);
                }
                //SE IL LAYER E' DI TIPO TILERASTER AGGIUNGO IL CORRISPONDENTE LAYER TILEINDEX DI TIPO POLYGON
                if($aLayer["layertype_id"] == 10){
                    $mapText[$mapName][] = $oFeature->getTileIndexLayer();
                }       
            }
            
            if(defined('TINYOWS_PATH') && $oFeature->isEditable()) {
                array_push($this->tinyOWSLayers, $oFeature->getTinyOWSLayerParams());
            }

            if(defined('MAPPROXY_PATH')){
                if(!empty($this->i18n)) {
                    $languageId = $this->i18n->getLanguageId();
                    $mapName.= "_".$languageId;
                }
                //DEFINIZIONE DEI LAYER PER MAPPROXY (COSTRUISCO UN LAYER WMS ANCHE PER I WMTS/TMS PER I TEST)
                //TODO: AGGIUNGERE LA GESTIONE DEI LAYER WMS PRESI DA SERVIZI ESTERNI
                if(empty($this->mpxLayers[$mapName])) $this->mpxLayers[$mapName] = array();
                if(empty($this->mpxCaches[$mapName])) $this->mpxCaches[$mapName] = array();
                if(empty($defaultLayers[$mapName])) $defaultLayers[$mapName] = array();
              //CACHE PER I TEMI SINGLE
                //print_array($aLayer);
                if($aLayer['theme_single']) {
                    $cacheName = $aLayer['theme_name'].'_cache';
                    if(empty($this->mpxCaches[$mapName][$cacheName])) $this->mpxCaches[$mapName][$cacheName] = array(
                        'grids'=>array_keys($this->grids),
                        'cache'=>$this->_getCacheType($aLayer['theme_name']),
                        'layergroups'=>array(),
                        'theme_name'=>$aLayer['theme_name'],
                        'theme_title'=>$aLayer['theme_title']
                    );

                    array_push($this->mpxCaches[$mapName][$cacheName]['layergroups'], $aLayer['layergroup_name']);
                }

                //LAYER ACCESI DI DEFAULT PER LA CACHE DEL MAPSET INTERO 
                //$defaulMapsetLayers = array();
                if(!empty($aLayer["layer_name"])){

                    if($aLayer["owstype_id"] == WMS_LAYER_TYPE){
                        if(empty($this->mpxLayers[$mapName][$aLayer["theme_name"]])) $this->mpxLayers[$mapName][$aLayer["theme_name"]] = array("name"=>$aLayer["theme_name"],"title"=>$aLayer["theme_title"],"layers"=>array());
                        if(empty($this->mpxLayers[$mapName][$aLayer["theme_name"]]["layers"][$aLayer["layergroup_name"]])) $this->mpxLayers[$mapName][$aLayer["theme_name"]]["layers"][$aLayer["layergroup_name"]] = array("name"=>$aLayer["layergroup_name"],"title"=>$aLayer["layergroup_title"]);
                        if($aLayer["layergroup_single"] == 1){
                            $this->mpxLayers[$mapName][$aLayer["theme_name"]]["layers"][$aLayer["layergroup_name"]]["sources"] = array("mapserver_source:".$aLayer["layergroup_name"]);
                        }else{
                            if(empty($this->mpxLayers[$mapName][$aLayer["theme_name"]]["layers"][$aLayer["layergroup_name"]]["layers"])) $this->mpxLayers[$mapName][$aLayer["theme_name"]]["layers"][$aLayer["layergroup_name"]]["layers"] = array();
                            //if($aLayer["hidden"]!=1) {
                                array_push($this->mpxLayers[$mapName][$aLayer["theme_name"]]["layers"][$aLayer["layergroup_name"]]["layers"], array(
                                    "name"=>$aLayer["layergroup_name"].".".$aLayer["layer_name"],
                                    "title"=>empty($aLayer["layer_title"])?$aLayer["layer_name"]:$aLayer["layer_title"],
                                    "sources"=>array("mapserver_source:".$aLayer["layergroup_name"].".".$aLayer["layer_name"])
                                ));
                            //}
                        }
                        if(!in_array($aLayer["layergroup_name"],$defaultLayers[$mapName]) && ($aLayer["isbaselayer"]  == 0) && ($aLayer["layergroup_status"] == 1))
                            array_push($defaultLayers[$mapName],$aLayer["layergroup_name"]);
                    }
    
                    else if($aLayer["owstype_id"] == WMS_CACHE_LAYER_TYPE || $aLayer["owstype_id"] == WMTS_LAYER_TYPE || $aLayer["owstype_id"] == TMS_LAYER_TYPE){
                        if(empty($this->mpxLayers[$mapName][$aLayer["theme_name"]])) $this->mpxLayers[$mapName][$aLayer["theme_name"]] = array("name"=>$aLayer["theme_name"],"title"=>$aLayer["theme_title"],"layers"=>array());
                        if(empty($this->mpxLayers[$mapName][$aLayer["theme_name"]]["layers"][$aLayer["layergroup_name"]])) $this->mpxLayers[$mapName][$aLayer["theme_name"]]["layers"][$aLayer["layergroup_name"]] = array("name"=>$aLayer["layergroup_name"],"title"=>$aLayer["layergroup_title"]);
                        //echo $aLayer["layergroup_name"];
                        //$this->mpxLayers[$mapName][$aLayer["theme_name"]]["layers"][$aLayer["layergroup_name"]]["sources"] = array($aLayer["layergroup_name"]."_cache_output"); //PER LA RIPROIEZIONE MA SEMBRA TROPPO LENTO

                        $this->mpxLayers[$mapName][$aLayer["theme_name"]]["layers"][$aLayer["layergroup_name"]]["sources"] = array($aLayer["layergroup_name"]."_cache");
                        if(empty($this->mpxCaches[$mapName][$aLayer["layergroup_name"]."_cache"])){
                            $this->mpxCaches[$mapName][$aLayer["layergroup_name"]."_cache"] = array(
                                "sources"=>array(),
                                "format"=>($aLayer["isbaselayer"])?"image/jpeg":"image/png",
                                "minimize_meta_requests"=>true,
                                "request_format"=>$aLayer["outputformat_mimetype"],
                                "cache"=>$this->_getCacheType($aLayer["theme_name"].'.'.$aLayer["layergroup_name"]),
                                "grids"=>array_keys($this->grids)
                                //'grids'=>array("epsg3857")                //PER LA RIPROIEZIONE MA SEMBRA TROPPO LENTO

                            );
                        }
                        //SE NEL LAYERGROUP C'È UN LAYER DA USARE COME SOURCE NON NASCOSTO LO METTO
                     
                        if(strrpos($aLayer["layer_name"],"self-wms")===false && $aLayer["hidden"]!=1) {
                            $sourceLayers = $this->mpxCaches[$mapName][$aLayer["layergroup_name"]."_cache"]["sources"];
                            if(count($sourceLayers) == 0) 
                                $sourceLayers = array("mapserver_source:".$aLayer["layergroup_name"].".".$aLayer["layer_name"]);
                            else
                                $sourceLayers[0] = $sourceLayers[0].",".$aLayer["layergroup_name"].".".$aLayer["layer_name"];
                            $this->mpxCaches[$mapName][$aLayer["layergroup_name"]."_cache"]["sources"] = $sourceLayers;
                        }

                        if(!in_array($aLayer["layergroup_name"],$defaultLayers[$mapName]) && ($aLayer["isbaselayer"]  == 0) && ($aLayer["layergroup_status"] == 1))
                            array_push($defaultLayers[$mapName],$aLayer["layergroup_name"]);
                    }

                    //VEDO SE CI SONO DEI LIVELLI MAPSERVER DENTRO I LAYERGROUP DEI SERVIZI WEB
                    else{
                        if(empty($this->mpxLayers[$mapName][$aLayer["theme_name"]])) $this->mpxLayers[$mapName][$aLayer["theme_name"]] = array("name"=>$aLayer["theme_name"],"title"=>$aLayer["theme_title"],"layers"=>array());
                        if(empty($this->mpxLayers[$mapName][$aLayer["theme_name"]]["layers"][$aLayer["layergroup_name"]])) $this->mpxLayers[$mapName][$aLayer["theme_name"]]["layers"][$aLayer["layergroup_name"]] = array("name"=>$aLayer["layergroup_name"],"title"=>$aLayer["layergroup_title"]);
                        if(empty($this->mpxLayers[$mapName][$aLayer["theme_name"]]["layers"][$aLayer["layergroup_name"]]["layers"])) $this->mpxLayers[$mapName][$aLayer["theme_name"]]["layers"][$aLayer["layergroup_name"]]["layers"] = array();
                        
                        if($aLayer["hidden"]!=1) {
                            array_push($this->mpxLayers[$mapName][$aLayer["theme_name"]]["layers"][$aLayer["layergroup_name"]]["layers"], array(
                                "name"=>$aLayer["layergroup_name"].".".$aLayer["layer_name"],
                                "title"=>empty($aLayer["layer_title"])?$aLayer["layer_name"]:$aLayer["layer_title"],
                                "sources"=>array("mapserver_source:".$aLayer["layergroup_name"])
                            ));
                        }
                    }

                }

            }

        }
        foreach($mapText as $mapName=>$mapContent){

            $this->layerText = implode("\n",$mapContent);
            $this->mapsetSrid = $mapSrid[$mapName];
            $this->mapsetTitle = $mapTitle[$mapName];

            $this->mapsetMaxScale = $mapMaxScale[$mapName];
            $this->mapsetExtent = $projectExtent;

            //non ho fissato un restricted extent per il mapset, quindi prendo l'extent in funzione della scala massima
            if(empty($mapExtent[$mapName])){    
                //EXTENT DEL MAPSET LO RICALCOLO SE NON POSSO USARE QUELLO DEL PROGETTO
                if(($mapSrid[$mapName] != $this->projectSrid) || ($mapMaxScale[$mapName] != $projectMaxScale)){
                    $this->mapsetExtent = $this->_calculateExtentFromCenter($this->mapsetMaxScale, $this->mapsetSrid);  
                }
            }else{
                $v = preg_split('/[\s]+/', $mapExtent[$mapName]);
                for ($i=0;$i<count($v);$i++){
                    $v[$i] = round(floatval($v[$i]),8);
                }
                $this->mapsetExtent = $v;
            }

            if($symbolsList[$mapName]) $this->layerText .= $this->_getSymbolText($symbolsList[$mapName]);
            $this->_writeFile($mapName);
            
            //NON GENERO I FILE YAML TEMPORANEI PER MAPPROXY
            if(defined('MAPPROXY_PATH') && ($this->target == 'public')){
          
                //NORMALIZZO L'ARRAY DEI LIVELLI
                foreach ($this->mpxLayers[$mapName] as $th => $grp) {
                    ksort($this->mpxLayers[$mapName][$th]["layers"]);
                    $this->mpxLayers[$mapName][$th]["layers"] = array_values($this->mpxLayers[$mapName][$th]["layers"]); 
                }
                ksort($this->mpxLayers[$mapName]);
                              
                $layersToAdd = array();
                
                //popolo il source con i nomi dei layergroups e aggiungo le caches di output
                if($this->mpxCaches[$mapName]){
                    foreach($this->mpxCaches[$mapName] as $cacheName => &$cache) {
                        if(!empty($cache['layergroups'])) {
                            $cache['sources'] = array('mapserver_source:'.implode(',',array_unique($cache['layergroups'])));
                            unset($cache['layergroups']);
                            
                            $layersToAdd[$cache['theme_name'].'_tiles'] = array(
                                'name'=>$cache['theme_name'].'_tiles',
                                'title'=>$cache['theme_title'],
                                'sources'=>array($cacheName)
                            );
                            unset($cache['theme_name'], $cache['theme_title']);
                        };
                    }
                    unset($cache);
                }
                
                foreach($layersToAdd as $name => $layer) {
                    $this->mpxLayers[$mapName][$name] = $layer;
                }

                //AGGIUNGO IL LAYER PER LA NAVIGAZIONE VELOCE
                $this->mpxCaches[$mapName][$mapName."_cache"] = array(
                    'sources'=>array('mapserver_source:'.implode(",",$defaultLayers[$mapName])),
                    'minimize_meta_requests'=>true,
                    'cache'=>$this->_getCacheType($mapName),
                    'grids'=>array_keys($this->grids)
                    //'grids'=>array("epsg3857")//PER LA RIPROIEZIONE MA SEMBRA TROPPO LENTO
                );
                $this->mpxLayers[$mapName][$mapName."_tiles"] = array(
                    'name'=>$mapName."_tiles",
                    'title'=>$mapName."_tiles",
                    'sources'=>array($mapName."_cache")


                );

                //PER LA RIPROIEZIONE MA SEMBRA TROPPO LENTO
/*              foreach($this->mpxCaches[$mapName] as $cacheName => $cache) {
                    $this->mpxCaches[$mapName][$cacheName."_output"] = array(
                        'sources'=>array($cacheName),
                        'disable_storage'=>true,
                        'grids'=>array_keys($this->grids)
                    );
                }
*/
                //$this->_writeMapProxyConfig($mpxLayers,$this->mpxCaches);
                $this->_writeMapProxyConfig($mapName);
            }
        }

        return $mapName;
    }
    
    function _writeFile(&$mapFile){
        $projectName = $this->projectName;
        $fontList=(defined('FONT_LIST'))?FONT_LIST:'fonts'; 
        $projLib=(defined('PROJ_LIB'))?"CONFIG 'PROJ_LIB' '".PROJ_LIB."'":'';
        $configDebugfile = '';
        $debugLevel = '';
        if (defined('DEBUG') && DEBUG && defined('DEBUG_DIR') && DEBUG_DIR) {
            $configDebugfile = "CONFIG 'MS_ERRORFILE' '".DEBUG_DIR.basename($mapFile).".debug'";
            $debugLevel = "DEBUG 5";
        }
        $outputFormat = $this->_getOutputFormat($mapFile);

        //$outputFormat = file_get_contents (ROOT_PATH."config/mapfile.outputformats.inc");
        //$metadata_inc = file_get_contents (ROOT_PATH."config/mapfile.metadata.inc");
        $metadata_inc = '';
        //$legend_inc = file_get_contents (ROOT_PATH."config/mapfile.legend.inc");
        $legend_inc = $this->_getLegendSettings();
        //$legend_inc = '';
        
        $imgPath = "IMAGEPATH \"".IMAGE_PATH."\"";
        $imgUrl = "IMAGEURL \"".IMAGE_URL."\"";
        $imgResolution = "RESOLUTION ".MAP_DPI;
        $size = TILE_SIZE . " " . TILE_SIZE;

        $wms_mime_type = "\t\"wms_feature_info_mime_type\"  \"text/html\"";
        $ows_title = "\t\"ows_title\"\t\"". $mapFile ."\"";
        $project_name = "\t\"project_name\"\t\"". $projectName ."\"";
        $ows_wfs_encoding = $this->_getEncoding();
        $ows_abstract = ""; //TODO: ripristinare aggiungendo descrizione a progetto
        $wfs_namespace_prefix = "\t\"wfs_namespace_prefix\"\t\"feature\"";//valore di default in OL
        $ows_srs = "\t\"wms_srs\"\t\"". implode(" ",$this->epsgList) ."\"";
        $ows_accessConstraints = '';
        if(!empty($this->layersWithAccessConstraints)) {
            $ows_accessConstraints = "\t\"ows_accessconstraints\"\t\"Layers ".implode(', ', $this->layersWithAccessConstraints)." need authentication\"";
        }
        
        $owsUrl = null;
        if (defined('GISCLIENT_OWS_URL')) {
            
            $owsUrl = rtrim(GISCLIENT_OWS_URL, '?&');
            
            if (false === ($owsUrlQueryPart = parse_url($owsUrl, PHP_URL_QUERY))) {
                throw new Exception("Could not parse '". GISCLIENT_OWS_URL . "' as string");
            }
            if(!empty($owsUrlQueryPart)) {
                $sep = '&';
            } else {
                $sep = '?';
            }
            $owsUrl .= $sep . 'project='.$this->projectName.'&map='.$mapFile;
        }        

        $wms_onlineresource = '';
        $wfs_onlineresource = '';
        if(!empty($owsUrl)) {
            $wms_onlineresource = "\t".'"wms_onlineresource" "'.$owsUrl.'"';
            $wfs_onlineresource = "\t".'"wfs_onlineresource" "'.$owsUrl.'"';
        }
        
        $layerText = $this->layerText;
        $mapProjection = "\t\"init=epsg:".$this->mapsetSrid."\"";
        if(!empty($this->srsParams[$this->mapsetSrid])) $mapProjection .= "\n\t\"+towgs84=".$this->srsParams[$this->mapsetSrid]."\"";
        $mapsetExtent = "EXTENT ". implode(" ", $this->mapsetExtent);

        if(defined('MAPFILE_MAX_SIZE')) $maxSize = MAPFILE_MAX_SIZE;
        else $maxSize = '4096';
        $fontList = '../../fonts/'.$fontList.'.list';

        $fileContent=
"MAP
NAME \"$mapFile\"
SIZE $size  
MAXSIZE $maxSize
$imgResolution
FONTSET $fontList
$projLib
WEB
    METADATA
        # for mapserver 6.0
        \"wms_enable_request\" \"*\"
        \"ows_enable_request\" \"*\"
    $project_name
    $ows_title
    $ows_abstract
    $ows_wfs_encoding
    $wms_onlineresource
    $wfs_onlineresource
    $wms_mime_type
    $wfs_namespace_prefix
    $ows_srs
    $ows_accessConstraints
$metadata_inc
    END
    $imgPath
    $imgUrl 
END 
PROJECTION
$mapProjection
END
$mapsetExtent
$layerText
$legend_inc
$outputFormat
END #MAP";

        if (!is_dir(ROOT_PATH)) {
            $errorMsg = ROOT_PATH . " is not a directory";
            GCError::register($errorMsg);
            return;
        }
        
        if($this->printMap) {
            $mapfileDir = ROOT_PATH."map/tmp/";
            if(!is_dir($mapfileDir)) {
                $rv = mkdir($mapfileDir, 0777, true);
                if ($rv === false) {
                    $errorMsg = "Could not create directory $mapfileDir";
                    GCError::register($errorMsg);
                    return;
                }
            }
            $mapFilePath=$mapfileDir.$mapFile.".map";
        } else {
            $mapfileDir = ROOT_PATH.'map/';
            if($this->target == 'tmp') {
                $mapFile = 'tmp.'.$mapFile;
            }
            $projectDir = $mapfileDir.$projectName.'/';
            if(!is_dir($projectDir)) {
                $rv = mkdir($projectDir, 0777, true);
                if ($rv === false) {
                    $errorMsg = "Could not create directory $projectDir";
                    GCError::register($errorMsg);
                    return;
                }
            }
            if(!empty($this->i18n)) {
                $languageId = $this->i18n->getLanguageId();
                $mapFile.= "_".$languageId;
            }
            $mapFilePath = $projectDir.$mapFile.".map";
        }
        if (false === ($f = fopen ($mapFilePath,"w"))) {
            $errorMsg = "Could not open $mapFilePath for writing";
            GCError::register($errorMsg);
            return;
        }
        if (false === (fwrite($f, $fileContent))) {
            $errorMsg = "Could not write to $mapFilePath";
            GCError::register($errorMsg);
            return;
        }
        fclose($f); 

        if(!$this->printMap && empty($this->i18n) && !empty($this->tinyOWSLayers)) {
            foreach($this->tinyOWSLayers as $layer) {
                $towsOnlineResource = TINYOWS_ONLINE_RESOURCE.$projectName.'/'.$layer['feature'].'/?';
                $fileContent = '<tinyows online_resource="'.$towsOnlineResource.'" schema_dir="'.TINYOWS_SCHEMA_DIR.'" check_schema="0" check_valid_geom="1" meter_precision="7" expose_pk="1" log_level="7"><pg host="'.DB_HOST.'" user="'.DB_USER.'" password="'.DB_PWD.'" dbname="'.$layer['database'].'" port="'.DB_PORT.'"/><metadata name="TinyOWS Server" title="TinyOWS Server" /><contact name="Admin" site="http://gisclient.net" email="admin@gisclient.net" />';
                $fileContent .= '<layer retrievable="1" writable="1" ns_prefix="feature" ns_uri="http://www.tinyows.org/" schema="'.$layer['schema'].'" name="'.$layer['name'].'" title="'.$layer['title'].'" />';
                $fileContent .= '</tinyows>';
                
                $tinyOwsConfigFile = $projectDir.'/'.$layer['feature'].'.xml';
                if (false === file_put_contents($tinyOwsConfigFile, $fileContent)) {
                    $errorMsg = "Could not write to $tinyOwsConfigFile";
                    GCError::register($errorMsg);
                    return;
                }
            }
        }
    
        //test sintassi mapfile     
        ms_ResetErrorList();
        try {
            @ms_newMapobj($mapFilePath);
        } 
        catch (Exception $e) {
            $error = ms_GetErrorObj();      
            if($error->code != MS_NOERR){
                $this->mapError=150;
                while(is_object($error) && $error->code != MS_NOERR) {
                    $errorMsg = "MAPFILE ERROR $mapFile<br>".sprintf("Error in %s: %s<br>", $error->routine, $error->message);
                    GCError::register($errorMsg);
                    $error = $error->next();
                }
                return;
            }   
            return;
        }
    }
    
    function _getCacheType($fileName){
        $ret = array('type'=>MAPPROXY_CACHE_TYPE);
        if(MAPPROXY_CACHE_TYPE == 'mbtiles') $ret["filename"] = $fileName.'.mbtiles';
        return $ret;
    }

    function _getPrintFormat(){
    
        $formatText ="
OUTPUTFORMAT
    NAME \"aggpng24\"
    DRIVER \"AGG/PNG\"
    MIMETYPE \"image/png\"
    IMAGEMODE RGB
    EXTENSION \"png\"   
    FORMATOPTION \"INTERLACE=OFF\"
    TRANSPARENT OFF
END";
        return $formatText;

    }
    
    function _isDriverSupported($driverName) {
        $mapserverSupport = ms_GetVersion();
        
        list($driver, $format) = explode('/', $driverName);
        
        // check on support
        if (preg_match_all ("/SUPPORTS=([A-Z_]+)/", $mapserverSupport, $supports)) {
            if (!in_array($driver, $supports[1]))
                return false;
        }
        
        // check on output
        if (preg_match_all ("/OUTPUT=([A-Z]+)/", $mapserverSupport, $outputs)) {
            if (!in_array($format, $outputs[1]))
                return false;
        }
        
        return true;
    }
    
    function _getOutputFormat($mapName){
            $formatText = '';
            $sql="select distinct e_outputformat.* from ".DB_SCHEMA.".e_outputformat;";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(); 
           // print_debug($sql);
            $numResults = $stmt->rowCount();
            if($numResults > 0) {
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    // ignore outputformat  with unsupported driver
                    if (!$this->_isDriverSupported($row["outputformat_driver"]))
                        continue;
                    $formatText .= "OUTPUTFORMAT    
    NAME \"".$row["outputformat_name"]."\"
    DRIVER \"".$row["outputformat_driver"]."\"
    MIMETYPE \"".$row["outputformat_mimetype"]."\"
    IMAGEMODE ".$row["outputformat_imagemode"] ."
    EXTENSION \"".$row["outputformat_extension"]."\"
    FORMATOPTION \"INTERLACE=OFF\"";
                    if($row["outputformat_option"]) $formatText.= "\n".$row["outputformat_option"];
                    $formatText .= "\nEND\n";   
                }
            } else {
                $formatText = file_get_contents (ROOT_PATH."config/mapfile.outputformats.inc");
            }
            return $formatText;
        }
    
    function _getEncoding(){
        $ows_wfs_encoding ='';
        $sql = "select charset_encodings_name 
            from ".DB_SCHEMA.".e_charset_encodings INNER JOIN ".DB_SCHEMA.".project on e_charset_encodings.charset_encodings_id=project.charset_encodings_id 
            where project_name=:projectName";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(':projectName' => $this->projectName));
        $res=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!empty($res)) $ows_wfs_encoding = "\t\"wfs_encoding\"\t\"".$res['charset_encodings_name']."\"\n".
                                            "\t\t\"wms_encoding\"\t\"".$res['charset_encodings_name']."\"\n";
        return $ows_wfs_encoding;
    }
    
    
    function _getLegendSettings(){
        // default font
        $legendFont = 'verdana';
        
        // get project font if assigned
        $sql="SELECT imagelabel_font,icon_w,icon_h,legend_font_size FROM ".DB_SCHEMA.".project WHERE project_name = ?;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->projectName));

        $numResults = $stmt->rowCount();
        if($numResults > 0) {
            $row=$stmt->fetch(PDO::FETCH_ASSOC);
            if (trim($row['imagelabel_font']) != '')
                $legendFont = $row['imagelabel_font'];
            $iconW = $row['icon_w']?$row['icon_w']:16;
            $iconH = $row['icon_h']?$row['icon_h']:10;
            $fontSize = $row['legend_font_size']?$row['legend_font_size']:10;
        }
        
        // mapfile snippet
        $formatText = "LEGEND\n" .
                      "    STATUS ON\n" .
                      "    OUTLINECOLOR 0 0 0 \n" .
                      "    KEYSIZE ".$iconW." ".$iconH."\n" .
                      "    LABEL\n" .
                      "       TYPE TRUETYPE\n" .
                      "       FONT '{$legendFont}'\n" .
                      "       SIZE ".$fontSize."\n" .
                      "       COLOR 0 0 0\n" .
                      "    END\n" .
                      "END\n";
        
        return $formatText;
    }
    

    function _getSymbolText($aSymbols){
                $_in = GCApp::prepareInStatement($aSymbols);
                $sqlParams = $_in['parameters'];
                $inQuery = $_in['inQuery'];

                $sql="select * from ".DB_SCHEMA.".symbol 
                    where symbol_name in (".$inQuery.");";
                    
                $stmt = $this->db->prepare($sql);
                $stmt->execute($sqlParams);
                $res = $stmt->fetchAll();

        $smbText=array();   
        for($i=0;$i<count($res);$i++){
            $smbText[]="SYMBOL";
            $smbText[]="\tNAME \"".$res[$i]["symbol_name"]."\"";
            if($res[$i]["symbol_type"])$smbText[]="\tTYPE ".$res[$i]["symbol_type"];
            if($res[$i]["font_name"]) $smbText[]="\tFONT \"".$res[$i]["font_name"]."\"";
            //if($res[$i]["ascii_code"]) $smbText[]="\tCHARACTER \"&#".$res[$i]["ascii_code"].";\"";//IN MAPSERVER 5.0 SEMBRA DARE PROBLEMI
            if($res[$i]["ascii_code"]) {
                if($res[$i]["ascii_code"]==34)
                    $smbText[]="\tCHARACTER '".chr($res[$i]["ascii_code"])."'";
                else if($res[$i]["ascii_code"]==92)
                    $smbText[]="\tCHARACTER '".chr($res[$i]["ascii_code"]).chr($res[$i]["ascii_code"])."'";
                else
                    $smbText[]="\tCHARACTER \"".chr($res[$i]["ascii_code"])."\"";

            }
            if($res[$i]["filled"]) $smbText[]="\tFILLED TRUE";
            if($res[$i]["points"]) $smbText[]="\tPOINTS ".$res[$i]["points"]." END";
            if($res[$i]["image"]) $smbText[]="\tIMAGE \"".$res[$i]["image"]."\"";
            if($res[$i]["symbol_def"]) $smbText[]=$res[$i]["symbol_def"];
            $smbText[]="END";
        }
        $txt = "\n###### SYMBOLS #######\n";
        $txt.= implode("\n",$smbText);
        return $txt;
    }

    function _calculateExtentFromCenter($maxScale, $srid) {
        $sql = "SELECT ".
        "st_x(st_transform(st_geometryfromtext('POINT('||".$this->xCenter."||' '||".$this->yCenter."||')',".$this->projectSrid."),$srid)) as xc, ".
        "st_y(st_transform(st_geometryfromtext('POINT('||".$this->xCenter."||' '||".$this->yCenter."||')',".$this->projectSrid."),$srid)) as yc, ".
        "CASE WHEN proj4text like '%+units=m%' then 'm' ".
        "WHEN proj4text LIKE '%+units=ft%' OR proj4text LIKE '%+units=us-ft%' THEN 'ft' ".
        "WHEN proj4text LIKE '%+proj=longlat%' THEN 'dd' ELSE 'm' END AS um ".
        "FROM spatial_ref_sys WHERE srid=:srid;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(':srid' => $srid));
        $row=$stmt->fetch(PDO::FETCH_ASSOC);
        $x = $row["xc"];
        $y = $row["yc"];
        $factor = GCAuthor::$aInchesPerUnit[$row["um"]];
        $precision = $row["um"] == "dd"?6:2;
        $maxResolution = $maxScale/( MAP_DPI * $factor );
        $extent = $maxResolution * TILE_SIZE * 4; //4 tiles??

        return array(
            0 => round($x - $extent, $precision),
            1 => round($y - $extent, $precision),
            2 => round($x + $extent, $precision),
            3 => round($y + $extent, $precision)
        );
    }

    function _setMapProjections(){
        //COSTRUISCO UNA LISTA DI PARAMETRI PER OGNI SRID CONTENUTO NEL PROGETTO PER EVITARE DI CALCOLARLI PER OGNI LAYER 
        $sql="SELECT DISTINCT srid, projparam FROM ".DB_SCHEMA.".layer 
            INNER JOIN ".DB_SCHEMA.".catalog USING(catalog_id) 
            INNER JOIN ".DB_SCHEMA.".project_srs using(project_name)
            WHERE project_name = ?;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->projectName));

        //GENERO LA LISTA DEGLI EXTENT PER I SISTEMI DI RIFERIMENTO
        while($row =  $stmt->fetch(PDO::FETCH_ASSOC)){
            $this->srsParams[$row["srid"]] = $row["projparam"];
        }

        //ELENCO DEI SISTEMI DI RIFERIMENTO NEI QUALI SI ESPONE IL SERVIZIO:(GRIDS)
        //DEFAULT WEB MERCATOR   
        $epsgList = array("EPSG:3857");
        $gridList = array(          
            "epsg3857" => array(
                'base'=>'GLOBAL_WEBMERCATOR',
                'srs'=>'EPSG:3857',
                'num_levels'=>MAPPROXY_GRIDS_NUMLEVELS
            )
        );

        $sql = "SELECT srid,".
        "st_x(st_transform(st_geometryfromtext('POINT('||".$this->xCenter."||' '||".$this->yCenter."||')',".$this->projectSrid."),srid)) as xc, ".
        "st_y(st_transform(st_geometryfromtext('POINT('||".$this->xCenter."||' '||".$this->yCenter."||')',".$this->projectSrid."),srid)) as yc, ".
        "CASE WHEN proj4text like '%+units=m%' then 'm' ".
        "WHEN proj4text LIKE '%+units=ft%' OR proj4text LIKE '%+units=us-ft%' THEN 'ft' ".
        "WHEN proj4text LIKE '%+proj=longlat%' THEN 'dd' ELSE 'm' END AS um ".
        "FROM ".DB_SCHEMA.".project_srs inner join spatial_ref_sys using(srid) WHERE srid<>3857 AND project_name = ?;";
        $stmt = $this->db->prepare($sql);

        $stmt->execute(array($this->projectName));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $srs = "epsg".$row["srid"];
            $epsgList[] = "EPSG:".$row["srid"];
            $gridList[$srs] = array("srs"=>"EPSG:".$row["srid"]);
            $gridList[$srs]["res"] = array();
            $convFact = GCAuthor::$aInchesPerUnit[$row["um"]]*MAP_DPI;
            $precision = $row["um"] == "dd"?10:2;
            if (defined('DEFAULT_SCALE_LIST')) {
                $scaleList = preg_split('/[\s]+/', DEFAULT_SCALE_LIST);
            } else {
                $scaleList = GCAuthor::$defaultScaleList;
            }
            foreach($scaleList as $scaleValue)  $gridList[$srs]["res"][] = round((float)$scaleValue/$convFact, $precision);
                    
            $aExtent=array();
            $extent = round($gridList[$srs]["res"][0] * TILE_SIZE);
            //echo $extent;return;
            $aExtent[0] = round((float)($row["xc"] - $extent), $precision);
            $aExtent[1] = round((float)($row["yc"] - $extent), $precision);
            $aExtent[2] = round((float)($row["xc"] + $extent), $precision);
            $aExtent[3] = round((float)($row["yc"] + $extent), $precision);
            $gridList[$srs]["bbox"] = $aExtent;
            $gridList[$srs]["bbox_srs"] = "EPSG:".$row["srid"];
        };
            
/*      while($row =  $stmt->fetch(PDO::FETCH_ASSOC)){
            $epsgList[] = "EPSG:".$row["srid"];
            if(isset($row["bbox"])){
                $gridList["epsg".$row["srid"]] = array("srs"=>"EPSG:".$row["srid"]);
                $gridList["epsg".$row["srid"]]["bbox"] = preg_split('/[\s]+/', $row["bbox"]);
                $gridList["epsg".$row["srid"]]["bbox_srs"] = "EPSG:4326";
                if(isset($row["resolutions"])){
                    $res = preg_split('/[\s]+/', $row["resolutions"]);
                    if(count($res)==1)
                        $gridList["epsg".$row["srid"]]["max_res"] = $res[0];
                    elseif(count($res)>1)
                        $gridList["epsg".$row["srid"]]["resolutions"] = $res;
                }
            }
        }*/

        $this->epsgList = $epsgList;
        $this->grids = $gridList;
    }

    function _writeMapProxyConfig($mapName){
        $config = array(
            'services'=>array(
                'tms'=>array(
                    'srs'=>$this->epsgList,
                    'use_grid_names'=>false,
                    'origin'=>'nw'
                ),
                'kml'=>array(
                    'use_grid_names'=>false
                ),
                'wmts'=>array(
                    'srs'=>$this->epsgList
                ),
                'wms'=>array(
                    'srs'=>$this->epsgList,
                    'md'=>array(
                        'title'=>$this->mapsetTitle,
                        'abstract'=>$this->mapsetTitle,
                        'online_resource'=>GISCLIENT_OWS_URL."?project=".$this->projectName."&amp;map=".$mapName,
                        'contact'=>array(
                            //ma serve sta roba?!?!
                            'person'=>'Roberto'
                        ),
                        'access_constraints'=>'None',
                        'fees'=>'None'
                    )
                )
            ),
            'sources'=>array(
                'mapserver_wms_source'=>array(
                    'type'=>'wms',
                    'supported_srs'=>$this->epsgList,
                    'req'=>array(
                        'url'=>MAPSERVER_URL,
                        'map'=>ROOT_PATH.'map/'.$this->projectName."/".$mapName.".map",
                        'format'=>'image/png',
                        'transparent'=> true,
                        'exceptions'=> 'inimage'
                    ),
                    'coverage'=>array(
                        'bbox'=>$this->mapsetExtent,
                        'srs'=>'EPSG:'.$this->mapsetSrid
                    ),
                    'image'=>array(
                        'transparent_color'=>'#ffffff',
                        'transparent_color_tolerance'=>0
                    )
                ),
                'mapserver_source'=>array(
                    'type'=>'mapserver',
                    'req'=>array(                        
                        'transparent'=>true,
                        'map'=>ROOT_PATH.'map/'.$this->projectName."/".$mapName.".map",
                        'exceptions'=> 'inimage'
                    ),
                    'coverage'=>array(
                        'bbox'=>$this->mapsetExtent,
                        'srs'=>'EPSG:'.$this->mapsetSrid
                    ),
                    'image'=>array(
                        'transparent_color'=>'#ffffff',
                        'transparent_color_tolerance'=>0
                    ),
                    'mapserver'=>array(
                        'binary'=>MAPSERVER_BINARY_PATH,
                        'working_dir'=>ROOT_PATH.'map/'.$this->projectName
                    )

                )
            ),
            'globals'=>array(
                'srs'=>array(
                    'proj_data_dir'=>PROJ_LIB
                ),
                'cache'=>array(
                    'type'=>MAPPROXY_CACHE_TYPE,
                    'base_dir'=>MAPPROXY_CACHE_PATH.$this->projectName.'/',
                    'lock_dir'=>MAPPROXY_CACHE_PATH.'locks/',
                    'tile_lock_dir'=>MAPPROXY_CACHE_PATH.'tile_locks/'
                )
            )
        );

        if(defined('MAPPROXY_DEMO') && MAPPROXY_DEMO) $config["services"]["demo"]=array('name'=>$mapName);
        if($this->grids) $config["grids"] = $this->grids;
        if($this->mpxCaches && count($this->mpxCaches[$mapName]) > 0) $config["caches"] = $this->mpxCaches[$mapName];
        if($this->mpxLayers) $config["layers"] = array_values($this->mpxLayers[$mapName]);

        if(count($this->grids)==0) unset($config["grids"]);

        
        //if(!is_dir(MAPPROXY_FILES)) mkdir(MAPPROXY_FILES);
        //if(!is_dir(ROOT_PATH.'mapproxy/'.$this->projectName)) mkdir(ROOT_PATH.'mapproxy/'.$this->projectName);

        //Verifica esistenza cartella dei tiles
        if(!is_dir(TILES_CACHE)) mkdir(TILES_CACHE);
        if(!is_dir(TILES_CACHE.$this->projectName)) mkdir(TILES_CACHE.$this->projectName);
        
        //$content = yaml_emit($config,YAML_UTF8_ENCODING);

        print_debug($config,null,'yaml');
        $content = Spyc::YAMLDump($config,1,0);

        //file_put_contents(MAPPROXY_FILES.$mapName.'.yaml', $content);
        //AGGIUNGO I LIVELLI WMS (che non hanno layer definiti nella tabella layer)


        $mapfileDir = ROOT_PATH.'map/';
        $projectDir = $mapfileDir.$this->projectName.'/';

        //CREO IL FILE DI CONFIGURAZIONE SE NON ESISTE
        $wsgiConfigFile = $mapfileDir.$this->projectName.".wsgi";
        if(!file_exists ($wsgiConfigFile)){
            $content = "activate_this = '".MAPPROXY_PATH."bin/activate_this.py'\n";
            $content.= "execfile(activate_this, dict(__file__=activate_this))\n";
            $content.= "from mapproxy.multiapp import make_wsgi_app\n";
            $content.= "application = make_wsgi_app('".$projectDir."', allow_listing=True)";
            file_put_contents($wsgiConfigFile, $content);
        }


        file_put_contents($projectDir.$mapName.'.yaml', $content);


    }
    

}