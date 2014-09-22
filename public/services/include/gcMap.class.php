<?php
/******************************************************************************
*
* Purpose: Inizializzazione dei parametri per la creazione della mappa 
     
* Author:  Roberto Starnini, Gis & Web Srl, roberto.starnini@gisweb.it
*
******************************************************************************
*
* Copyright (c) 2009-2010 Gis & Web Srl www.gisweb.it
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version. See the COPYING file.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with p.mapper; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*
******************************************************************************/

define('WMS_LAYER_TYPE',1);
define('WMTS_LAYER_TYPE',2);
define('WMS_CACHE_LAYER_TYPE',3);
define('GMAP_LAYER_TYPE',7);
define('VMAP_LAYER_TYPE',3);
define('YMAP_LAYER_TYPE',4);
define('OSM_LAYER_TYPE',5);
define('TMS_LAYER_TYPE',6);
define('BING_LAYER_TYPE',8);
define('GOOGLESRID',3857);
define('SERVICE_MAX_RESOLUTION',156543.03390625);
define('SERVICE_MIN_ZOOM_LEVEL',0);
define('SERVICE_MAX_ZOOM_LEVEL',21);

class gcMap{

	var $db;
	var $authorizedLayers = array();
	var $authorizedGroups = array();
	var $selgroupList = array();
	var $mapLayers = array();
	var $featureTypes = array();
    var $defaultLayers = array();
	var $projectName;
	var $mapsetName;
	var $mapConfig;
	var $mapsetSRID;
	var $mapsetGRID;
	var $serverResolutions = array();
	var $mapsetResolutions = array();
	var $tilesExtent;
	var $activeBaseLayer = '';
	var $isPublicLayerQueryable = true; //FLAG CHE SETTA I LAYER PUBBLICI ANCHE INTERROGABILI 
	var $fractionalZoom = 0;
	var $allOverlays = 0;
	var $coordSep = ' ';
	var $listProviders = array(); //Elenco dei provider settati per il mapset
	var $aUnitDef = array(1=>"m",2=>"ft",3=>"inches",4=>"km",5=>"m",6=>"mi",7=>"dd");//units tables (force pixel ->m)
	var $getLegend = false;

	var $mapProviders = array(
			VMAP_LAYER_TYPE => "http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.3",
			YMAP_LAYER_TYPE => "http://api.maps.yahoo.com/ajaxymap?v=3.0&appid=euzuro-openlayers",
			OSM_LAYER_TYPE => "http://openstreetmap.org/openlayers/OpenStreetMap.js",
			GMAP_LAYER_TYPE => "http://maps.google.com/maps/api/js?sensor=false");//Elenco dei provider di mappe OSM GMap VEMap YMap come mappati in tabelle e_owstype
	
	private $i18n;
	protected $oMap;
	protected $sldContents = array();
	
	function __construct ($mapsetName, $getLegend = false, $languageId = null){

		

		function _toFloat($val){
			$value = 0.0;
			$dec = strlen($val) - strpos($val, ".") - 1;
			$dec = strpos($val, ".");
			$s = str_replace(".", "", $val);
			//echo "--$s--";
			for ($i=0; $i < strlen($s); $i++) { 
				//if(substr($val,$i,1)!=".") 
					$value += (floatval(substr($s,$i,1))) * pow(10,$dec-1-$i);
					//echo (substr($s,$i,1)*pow(10,$dec-1-$i))."\n" ;
			}

			return $value;
	
	
		}


		$this->db = GCApp::getDB();
		
		//if (defined('GMAPKEY')) $this->mapProviders[GMAP_LAYER_TYPE] .= "&key='".GMAPKEY."'";
		//if (defined('GMAPSENSOR')) $this->mapProviders[GMAP_LAYER_TYPE] .= "&sensor=true"; else $this->mapProviders[GMAP_LAYER_TYPE] .= "&sensor=false";
	
		$sql = "SELECT mapset.*, ".
			" st_x(st_transform(st_geometryfromtext('POINT('||xc||' '||yc||')',project_srid),mapset_srid)) as xc, ".
			" st_y(st_transform(st_geometryfromtext('POINT('||xc||' '||yc||')',project_srid),mapset_srid)) as yc, ".
			" max_extent_scale, project_title, mapset_grid, tilegrid_extent,tilegrid_resolutions FROM ".DB_SCHEMA.".mapset ".
			" INNER JOIN ".DB_SCHEMA.".project USING (project_name) ".
			" LEFT JOIN (SELECT project_name,srid as mapset_srid,tilegrid_name as mapset_grid,tilegrid_extent,tilegrid_resolutions FROM ".DB_SCHEMA.".project_srs INNER JOIN ".DB_SCHEMA.".e_tilegrid USING (tilegrid_id)) AS tilegrid USING (project_name,mapset_srid) ".
			" WHERE mapset_name=?";
		
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($mapsetName));

		if($stmt->rowCount() == 0){
		    echo "Il mapset \"{$mapsetName}\" non esiste<br /><br />\n\n";
			echo "{$stmt->queryString}<br />\n";
			// echo "{$sql}<br />\n";
			die();
		}

		
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if(!empty($languageId)) {
			$this->i18n = new GCi18n($row['project_name'], $languageId);
			$row['mapset_title'] = $this->i18n->translate($row['mapset_title'], 'mapset', $row['mapset_name'], 'mapset_title');
			$row['project_title'] = $this->i18n->translate($row['project_title'], 'project', $row['project_name'], 'project_title');
		}
		
		$this->projectName = $row["project_name"];
		$this->mapsetName = $row["mapset_name"];
		$sizeUnitId = empty($row["sizeunits_id"]) ? 5 : intval($row["sizeunits_id"]);
		if($row["mapset_srid"]==4326) $sizeUnitId = 7; //Forzo dd se in 4326
		
		$mapConfig=array();
		$mapConfig["name"] = $row["mapset_name"];
		$mapConfig["title"] = (strtoupper(CHAR_SET) != 'UTF-8')?utf8_encode($row["mapset_title"]):$row["mapset_title"];
		$mapConfig["projectName"] = $row["project_name"];	
		if(!empty($row["project_title"])) $mapConfig["projectTitle"] = (strtoupper(CHAR_SET) != 'UTF-8')?utf8_encode($row["project_title"]):$row["project_title"];
		$mapConfig["mapsetTiles"] = (int)$row["mapset_tiles"];
		$mapConfig["dpi"] = MAP_DPI;
		$mapConfig['projectionDescription'] = $this->_getProjectionDescription('EPSG', $row['mapset_srid']);
		$mapConfig['projdefs'] = $this->_getProj4jsDefs();

		$mapOptions=array();
		$mapOptions["center"] = array(floatval($row["xc"]),floatval($row["yc"]));
		$mapOptions["units"] = $this->aUnitDef[$sizeUnitId];
		$mapOptions["projection"] = "EPSG:".$row["mapset_srid"];
		if(!empty($row["displayprojection"])) $mapOptions["displayProjection"] = "EPSG:".$row["displayprojection"];
		$this->mapsetSRID = $row["mapset_srid"];
		$this->fractionalZoom = 1;

		//GRID & TILES
		if(!empty($row["mapset_grid"])){
			$precision = $sizeUnitId == 7?10:6;
			$this->tilesExtent = explode($this->coordSep,$row["tilegrid_extent"]); 
            foreach($this->tilesExtent as &$res) $res = round((float)$res,$precision);
            unset($res);
			$this->serverResolutions = explode($this->coordSep,$row["tilegrid_resolutions"]); 
			$this->mapsetGRID = $row["mapset_grid"];

			//ATTENZIONE SU QUALCHE VERSIONE PHP NON ARROTONDAVA CORRETTAMENTE
			//for($i=0;$i<count($this->serverResolutions);$i++) $this->serverResolutions [$i] = round(floatval($this->serverResolutions [$i]),$precision);
			//for($i=0;$i<count($this->serverResolutions);$i++) $this->serverResolutions [$i] = floatval($this->serverResolutions [$i]);
            foreach($this->serverResolutions as &$res) $res = round((float)$res,$precision);
            unset($res);
		}

		$this->_getResolutions($row["minscale"],empty($row["maxscale"])?$row["max_extent_scale"]:$row["maxscale"],$sizeUnitId);

		$mapOptions["serverResolutions"] = $this->serverResolutions;
		$mapOptions["minZoomLevel"] = $this->minZoomLevel;
		$mapOptions["maxZoomLevel"] = $this->maxZoomLevel;
		$mapOptions["numZoomLevels"] = $this->numZoomLevels;

		$mapOptions["maxExtent"] = $this->_getExtent($row["xc"],$row["yc"],$this->serverResolutions[$this->minZoomLevel]);
		$mapOptions["tilesExtent"] = $this->tilesExtent;
		$mapOptions["matrixSet"] = $this->mapsetGRID."_".$this->mapsetSRID;
		//$mapOptions["wmtsBaseUrl"] = GISCLIENT_WMTS_URL;
		//Limita estensione:
		if(($row["mapset_extent"])){
			$ext = explode($this->coordSep,$row["mapset_extent"]);
			$mapOptions["restrictedExtent"] = array(floatval($ext[0]),floatval($ext[1]),floatval($ext[2]),floatval($ext[3]));
		}

		$mapConfig["mapOptions"] = $mapOptions;
		unset($mapOptions);
		
		
		// TODO AGGIUNGERE IL TESTO MAPPA, DIRECTORY PROGETTO .... ?????
		
		$this->getLegend = $getLegend;
		
		//$this->_getLayers();
		
		$this->_getSelgroup();
		$this->_getLayers();
		$this->_getFeatureTypes();
		
		if(count($this->listProviders)>0){
			$mapConfig["mapProviders"] = array();
			foreach($this->listProviders as $key){
				array_push($mapConfig["mapProviders"],$this->mapProviders[$key]);
			}
		}
		

		$mapConfig["layers"] = $this->mapLayers;
		$mapConfig["featureTypes"] = $this->featureTypes;
		if($this->activeBaseLayer) $mapConfig["baseLayerName"] = $this->activeBaseLayer;
		if($this->fractionalZoom == 1) $mapConfig["mapOptions"]["fractionalZoom"] = true;
		
		if($this->selgroupList)
			$mapConfig["selgroup"] = $this->selgroupList;

		//SE HO DEFINITO UN CONTESTO AGGIUNGO LE OPZIONI DI CONTESTO (PER ORA AGGIUNGO I LAYER DEL REDLINE) (TODO FRANCESCO) 
		//SOVRASCRIVO GLI ATTRIBUTI DI mapConfig E AGGIUNGO I LAYER DEL CONTEXT
		//LASCEREI IL DOPPIO PASSAGGIO JSONENCODE JSONDECODE PER IL CONTROLLO DEGLI ERRORI ..... DA VEDERE
		
		if(!empty($_REQUEST['context'])) {
			$userContext = $this->_getUserContext($_REQUEST['context']);
			if(!empty($userContext) && !empty($userContext['layers'])) $mapConfig["context_layers"] = $userContext['layers'];
		}
        
        // background diverso da bianco/trasparente
        if(!empty($row['bg_color']) && $row['bg_color'] != '255 255 255') {
            $mapConfig['bg_color'] = $row['bg_color'];
        }
        
        $user = new GCUser();
        if($user->isAuthenticated()) {
            $mapConfig['logged_username'] = $user->getUsername();
        }
        
        $sql = 'select mapset_name, mapset_title from '.DB_SCHEMA.'.mapset where project_name = :project';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array('project'=>$this->projectName));
        $mapConfig['mapsets'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $mapConfig['default_layers'] = $this->defaultLayers;

		//$this->maxRes = $maxRes;
		//$this->minRes = $minRes;
		$this->mapConfig = $mapConfig;
		
	}
	
	function __destruct (){
		unset($this->db);
	}


	function _getLayers(){

		$user = new GCUser();
		$user->setAuthorizedLayers(array('mapset_name'=>$this->mapsetName));

		$authorizedLayers = $user->getAuthorizedLayers(array('mapset_name'=>$this->mapsetName));
		$userLayers = $user->getMapLayers(array('mapset_name'=>$this->mapsetName));
		//print_array($userLayers);die();
        //$extents = $this->_getMaxExtents();
		//print_array($userLayers);

		$sqlParams = array();
		$sqlAuthorizedLayers = "";
		if (count($authorizedLayers)>0) $sqlAuthorizedLayers = " OR layer_id IN (".implode(',', $authorizedLayers).")";
		$sqlLayers = "SELECT theme_id,theme_name,theme_title,theme_single,theme.radio,theme.copyright_string,layergroup.*,mapset_layergroup.*,outputformat_mimetype,outputformat_extension, owstype_name FROM ".DB_SCHEMA.".layergroup INNER JOIN ".DB_SCHEMA.".mapset_layergroup using (layergroup_id) INNER JOIN ".DB_SCHEMA.".theme using(theme_id) LEFT JOIN ".DB_SCHEMA.".e_outputformat using (outputformat_id) LEFT JOIN ".DB_SCHEMA.".e_owstype using (owstype_id) 
			WHERE layergroup_id IN (
				SELECT layergroup_id FROM ".DB_SCHEMA.".layer WHERE layer.private = 0 ".$sqlAuthorizedLayers;
		$sqlLayers .= " UNION
				SELECT layergroup_id FROM ".DB_SCHEMA.".layergroup LEFT JOIN ".DB_SCHEMA.".layer USING (layergroup_id) WHERE layer_id IS NULL
			) AND mapset_name = :mapset_name
                        ORDER BY theme.theme_order,theme.theme_title, layergroup.layergroup_order,layergroup.layergroup_title;"; 
		//die($sqlAuthorizedLayers);
		$stmt = $this->db->prepare($sqlLayers);
		$stmt->bindValue(':mapset_name', $this->mapsetName);
		$stmt->execute();

		$ows_url = (defined('GISCLIENT_OWS_URL'))?GISCLIENT_OWS_URL:'../../services/ows.php';
		if(defined('MAPPROXY_URL')){
			$mapproxy_url = (defined('PROJECT_MAPFILE') && PROJECT_MAPFILE)?MAPPROXY_URL."/".$this->projectName:MAPPROXY_URL."/".$this->mapsetName;
		}
	
		$rowset = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$themeMinScale = false; $themeMaxScale = false;
		for($i=0; $i < count($rowset); $i++) {
			$row = $rowset[$i];
			if(!empty($this->i18n)) {
				$row = $this->i18n->translateRow($row, 'theme', $row['theme_id'], array('theme_title', 'copyright_string'));
				$row = $this->i18n->translateRow($row, 'layergroup', $row['layergroup_id'], array('layergroup_title', 'sld'));
			}
            
            if($row['status']) {
                array_push($this->defaultLayers, $row['layergroup_name']);
            }

			$themeName = $row['theme_name'];
			$mapsetName = $row['mapset_name'];
			$themeTitle = empty($row['theme_title'])?$theme_name:((strtoupper(CHAR_SET) != 'UTF-8')?utf8_encode($row["theme_title"]):$row["theme_title"]);
			$layergroupName = $row['layergroup_name'];
			$layergroupTitle = empty($row['layergroup_title'])?$layergroupName:((strtoupper(CHAR_SET) != 'UTF-8')?utf8_encode($row["layergroup_title"]):$row["layergroup_title"]);
			$layerType = intval($row["owstype_id"]);
			
			//SE METTO LA / NON METTE GRUPPO
			/*ELIMINO???????????????????????? DA VEDERE COME CUSTOMIZZARE IL TEMA DI APPARTENENZA
			if(empty($row['tree_group']))
				$layerTreeGroup = $themeTitle;
			elseif($row['tree_group']=="/")
				$layerTreeGroup = "";
			else
				$layerTreeGroup = (strtoupper(CHAR_SET) != 'UTF-8')?utf8_encode($row["tree_group"]):$row["tree_group"];
			*/

			$aLayer = array();
			$aLayer["name"] = $layergroupName;
			//$aLayer["title"] = $layergroupTitle;
			
			//$aLayer["typeId"] = $layerType;
			$aLayer["typeId"] = intval($row["owstype_id"]);
			$aLayer["type"] = $row["owstype_name"];
			$layerOptions = array();
			if($row["status"] == 0) $layerOptions["visibility"] = false;
			if($row["hidden"] == 1) $layerOptions["displayInLayerSwitcher"] = false;
            if(!empty($row['copyright_string'])) $layerOptions["attribution"] = (strtoupper(CHAR_SET) != 'UTF-8')?utf8_encode($row["copyright_string"]):$row["copyright_string"];
			if($row["isbaselayer"] == 1 && $row["status"] == 1) $this->activeBaseLayer = $layergroupName;
			if($row['opacity'] != null && $row['opacity'] != 100) $layerOptions['opacity'] = $row['opacity']/100;
			if(!empty($row['metadata_url'])) $layerOptions['metadataUrl'] = $row['metadata_url'];
            //if(!empty($extents[$row['layergroup_id']])) $layerOptions['maxExtent'] = $extents[$row['layergroup_id']];
 			$layerOptions["theme"] = $themeTitle;
 			$layerOptions["theme_id"] = $row['theme_name'];
 			$layerOptions["title"] = $layergroupTitle;
            if($row["refmap"]) $aLayer["overview"] = true;

			//ALLA ROVESCIA RISPETTO A MAPSERVER
			if($row["layergroup_maxscale"]>0) $layerOptions["minScale"] = floatval($row["layergroup_maxscale"]);
			if($row["layergroup_minscale"]>0) $layerOptions["maxScale"] = floatval($row["layergroup_minscale"]);

			if($layerType == WMS_LAYER_TYPE || $layerType == WMS_CACHE_LAYER_TYPE){
				//TEMI SINGOLA IMMAGINE: PRENDO LA CONFIGURAZIONE DEL PRIMO LIVELLO WMS
				$layerParameters=array();
				if($layerType == WMS_CACHE_LAYER_TYPE){
					if(!$mapproxy_url) continue;
					$aLayer["url"] = $mapproxy_url."/service";
				}
				else{
					$aLayer["url"] = empty($row["url"])?$ows_url:$row["url"];
					$layerParameters["map"] = $mapsetName;// AGGIUNGIAMO LA LINGUA ??? $row["theme_name"];
				}

				$layerParameters["exceptions"] = (defined('DEBUG') && DEBUG==1)?'xml':'blank';				
				$layerParameters["format"] = $row["outputformat_mimetype"];
				$layerParameters["transparent"] = true;
                if (!empty($row['sld'])) $layerParameters["sld"] = PUBLIC_URL."sld/".$row["sld"];
				if (!empty($_REQUEST["tmp"])) $layerParameters['tmp'] = 1;
                    
                // TODO: check for layergroup.layername
				$layerOptions["buffer"] = intval($row["buffer"]);
				if($row["isbaselayer"]==1) $layerOptions["isBaseLayer"] = true;
				if($row["transition"]==1) $layerOptions["transitionEffect"] = "resize";
				if($row["gutter"]>0) $layerOptions["gutter"] = intval($row["gutter"]);
				if($row["tiletype_id"]==0) $layerOptions["singleTile"] = true;
				//$aLayer["singleImage"] = intval($row["layergroup_single"]);

				$aLayer["parameters"] = $layerParameters;
				$aLayer["options"] = $layerOptions;	


				//SETTO IL PARAMETRO LAYERS	E LA STRUTTURA		
				//DA RIVEDRE!!!!!!!!!!!!!!!!!
				if($layerType == WMS_CACHE_LAYER_TYPE){
					$aLayer["parameters"]["layers"] = $layergroupName;
				}

				// Layer impostati sul layergroup
				elseif (!empty($row['layers'])) { 
                   $aLayer["parameters"]["layers"] = explode(",",$row['layers']);
 		        } 


				//Tema singola immagine: passo tutti i layergroupname come layer e prendo le impostazioni di base dal primo wms
				elseif($row["theme_single"]==1){ 
					$idx = $this->_getThemeLayerIndex($themeName);
					$newFlag = false;

					if($idx==-1){
						$aLayer["name"] = $themeName;
						$aLayer["nodes"] = array();
                        $aLayer['theme_single'] = true; 
						$aLayer["options"]["title"] = $themeTitle;	
						$aLayer["options"]["visibility"] = false;
						$aLayer["parameters"]["layers"] = array();				
						array_push($this->mapLayers, $aLayer);
						$idx = count($this->mapLayers) - 1;
						$newFlag = true;
					}

					//Override dei valori
					//if($row["status"] == 1) array_push($this->mapLayers[$idx]["parameters"]["layers"], $layergroupName);
					$this->mapLayers[$idx]["options"]["visibility"] = $this->mapLayers[$idx]["options"]["visibility"] || ($row["status"] == 1);
					//$node = array("layer"=>$layergroupName, "title" => $layergroupTitle, "visibility" => $row["status"] == 1);

					//Layergroup singola immagine: passo solo il layergroupname
					if($row["layergroup_single"] == 1){ 
						if($row["status"] == 1) array_push($this->mapLayers[$idx]["parameters"]["layers"], $layergroupName);
						$node = array("layer"=>$layergroupName, "title" => $layergroupTitle, "visibility" => $row["status"] == 1);
					}

					//Layergroup con singoli layer distinti				(DA FORZARE SE ASSOCIATO A UNA FEATURETYPE?????)		
					else { 	
						$nodes = array();
						$layers = array();
						foreach($userLayers[$themeName][$layergroupName] as $userLayer) {							

							if($row["status"] == 1) array_push($this->mapLayers[$idx]["parameters"]["layers"], $userLayer["name"]);
							$arr = array("layer"=>$userLayer["name"], "title"=>$userLayer["title"]);
							if($userLayer["minScale"]) $arr["minScale"] = floatval($userLayer["minScale"]);
							if($userLayer["maxScale"]) $arr["maxScale"] = floatval(+$userLayer["maxScale"]);
							$nodes[] = $arr;
							$layers[] = $userLayer["name"];
						}
						$node = array("layer"=>$layergroupName, "title" => $layergroupTitle, "visibility" => $row["status"] == 1 , "nodes" => $nodes);

					}

					//INIZIALIZZO IL VALORE PER VERIFICARE CHE SIANO SETTATI MAXSCALE E MINSCALE PER TUTTI I LAYER DEL TEMA ALTRIMENTI NON SETTO IL VALORE NEL TEMA LAYER
					if($newFlag && !empty($layerOptions["minScale"])) $this->mapLayers[$idx]["options"]["minScale"] = $layerOptions["minScale"];
					if($newFlag && !empty($layerOptions["maxScale"])) $this->mapLayers[$idx]["options"]["maxScale"] = $layerOptions["maxScale"]; 

					if(!empty($layerOptions["minScale"])) { 
						$node["minScale"] = $layerOptions["minScale"];
						if(!empty($this->mapLayers[$idx]["options"]["minScale"]))  $this->mapLayers[$idx]["options"]["minScale"] = max($this->mapLayers[$idx]["options"]["minScale"],$layerOptions["minScale"]);
					}
					else
						unset($this->mapLayers[$idx]["options"]["minScale"]);

					if(!empty($layerOptions["maxScale"])) {
						$node["maxScale"] = $layerOptions["maxScale"];	
						if(!empty($this->mapLayers[$idx]["options"]["maxScale"]))  $this->mapLayers[$idx]["options"]["maxScale"] = min($this->mapLayers[$idx]["options"]["maxScale"],$layerOptions["maxScale"]);
					}
					else
						unset($this->mapLayers[$idx]["options"]["maxScale"]);

					array_push($this->mapLayers[$idx]["nodes"], $node);	

					continue;
				}
				
				//Layergroup singola immagine: passo solo il layergroupname
				elseif($row["layergroup_single"] == 1){ 
					$aLayer["parameters"]["layers"] = array($layergroupName);
				}

				//Layergroup con singoli layer distinti				(DA FORZARE SE ASSOCIATO A UNA FEATURETYPE?????)		
				else { 	
					$aLayer["parameters"]["layers"] = array();
					$aLayer["nodes"] = array();	
					$hidden=true;		
					foreach($userLayers[$themeName][$layergroupName] as $userLayer) {
						array_push($aLayer["parameters"]["layers"], $userLayer["name"]);
						$arr = array("layer"=>$userLayer["name"], "title"=>$userLayer["title"]);
						if($userLayer["minScale"]) $arr["minScale"] = floatval($userLayer["minScale"]);
						if($userLayer["maxScale"]) $arr["maxScale"] = floatval(+$userLayer["maxScale"]);
						array_push($aLayer["nodes"], $arr);
						if($userLayer["hidden"]==0) $hidden=false;
					}
					if($hidden) $aLayer["options"]["displayInLayerSwitcher"]=false;

				}
				array_push($this->mapLayers, $aLayer);
			}
	
			elseif($layerType == GMAP_LAYER_TYPE || $layerType == BING_LAYER_TYPE || $layerType == VMAP_LAYER_TYPE || $layerType == YMAP_LAYER_TYPE){//Google VE Yahoo	
				$this->allOverlays = 0;
				$this->fractionalZoom = 0;
				if(!in_array($layerType,$this->listProviders) && $layerType!=BING_LAYER_TYPE) $this->listProviders[] = $layerType;
				$layerOptions["type"] = empty($row["layers"])?"null":$row["layers"];
				if($layerType == BING_LAYER_TYPE) {
					$layerOptions["name"] = $aLayer["name"];
					$layerOptions["key"] = BINGKEY;
				}
				$layerOptions["sphericalMercator"] = true;
				$layerOptions["minZoomLevel"] = $this->minZoomLevel;
				if($layerOptions["type"] == "terrain") $layerOptions["maxZoomLevel"] = 15;
				if($row["status"] == 1) $this->activeBaseLayer = $layergroupName;
				unset($layerOptions["minScale"]);
				unset($layerOptions["maxScale"]);
				$aLayer["options"]= $layerOptions;
				array_push($this->mapLayers, $aLayer);				
			}
	
			elseif($layerType==OSM_LAYER_TYPE){//OSM
				$this->allOverlays = 0;
				$this->fractionalZoom = 0;
				if(!in_array($layerType,$this->listProviders)) $this->listProviders[] = $layerType;
				$layerOptions["sphericalMercator"] = true;
				$layerOptions["zoomOffset"] = $this->minZoomLevel; 
				if($row["status"] == 1) $this->activeBaseLayer = $layergroupName;
				$aLayer["options"]= $layerOptions;
				if($row["transition"]==1) $layerParameters["transitionEffect"] = "resize";
				array_push($this->mapLayers, $aLayer);
			}
				
			elseif(!empty($this->mapsetGRID) && $layerType == WMTS_LAYER_TYPE){// WMTS
				$layerParameters=array();
				$layerParameters["name"] = $aLayer["name"];
				if(isset($row["url"])){
					//?????????????????? TODO ???????????????????????
					$layerParameters["url"] = $row["url"]."/{Style}/{TileMatrixSet}/{TileMatrix}/{TileCol}/{TileRow}.png";
				}
				else{
					if(!$mapproxy_url) continue;
					$layerParameters["requestEncoding"] = "REST";
					$layerParameters["style"] = empty($row["layers"])?$layergroupName:$row["layers"];
					$layerParameters["matrixSet"] = $this->mapsetGRID."_".$this->mapsetSRID;
					$layerParameters["url"] = $mapproxy_url."/wmts/{Style}/{TileMatrixSet}/{TileMatrix}/{TileCol}/{TileRow}.png";
				}

				if($row["status"] == 0) $layerParameters["visibility"] = false;
				$layerParameters["layer"] = empty($row["layers"])?$layergroupName:$row["layers"];
				$layerParameters["maxExtent"] = $this->tilesExtent;	
				$layerParameters["owsurl"] = $ows_url."?map=".$mapsetName;
				$layerParameters["isBaseLayer"] = $row["isbaselayer"]==1;
				$layerParameters["zoomOffset"] = $this->minZoomLevel; 

				//ALLA ROVESCIA RISPETTO A MAPSERVER
				if($row["layergroup_maxscale"]>0) $layerParameters["minScale"] = floatval($row["layergroup_maxscale"]);
				if($row["layergroup_minscale"]>0) $layerParameters["maxScale"] = floatval($row["layergroup_minscale"]);
				if($row["transition"]==1) $layerParameters["transitionEffect"] = "resize";
				if($row["gutter"]>0) $layerParameters["gutter"] = intval($row["gutter"]);
				$layerParameters["buffer"] = intval($row["buffer"]);
				$layerParameters["theme"] = $themeTitle;
				$layerParameters["title"] = $layergroupTitle;
				$layerParameters["theme_id"] = $row['theme_name'];
				//$layerParameters["displayInLayerSwitcher"] = true;
				$this->allOverlays = 0;
				$this->fractionalZoom = 0;
				$aLayer["parameters"] = $layerParameters;
				array_push($this->mapLayers, $aLayer);
			}

			elseif(!empty($this->mapsetGRID) && $layerType==TMS_LAYER_TYPE){//TMS
				$aLayer["url"] = isset($row["url"])?$row["url"]:GISCLIENT_TMS_URL;
				$layerOptions["serviceVersion"] =  isset($row["layers"])?$row["layers"]:$layergroupName."@".$this->mapsetGRID;
				$this->allOverlays = 0;
				$this->fractionalZoom = 0;
				$layerOptions["layername"] = GISCLIENT_TMS_VERSION;
				$layerOptions["owsurl"] = $ows_url."?map=".$mapsetName;
				$layerOptions["type"] = $row['outputformat_extension'];
				$layerOptions["isBaseLayer"] = $row["isbaselayer"]==1;	
				$layerOptions["zoomOffset"] = $this->minZoomLevel; 
				$layerOptions["buffer"] = intval($row["buffer"]);
				//$layerOptions["serverResolutions"] = $this->serverResolutions;
				$layerOptions["maxExtent"] = $this->tilesExtent;	
				$layerOptions["tileOrigin"] = array_slice($this->tilesExtent,0,2);
				$aLayer["options"]= $layerOptions;
				array_push($this->mapLayers, $aLayer);
			}		

			unset($aLayer);

			//OVERVIEW: FD add overview and legend  DA VEDERE PER FD
/*			if($row['refmap']){
				$aLayers[$themeName][$layergroupName]['overview'] = $row['refmap'];
				if($row['hide'] == 1) $aLayers[$themeName][$layergroupName]['hide'] = 1;
				if($this->getLegend) {
					$aLayers[$themeName][$layergroupName]['legend'] = $this->_getLegendArray($row['layergroup_id']);
				}
			}*/
		
		}
	}
	
	function _getThemeLayerIndex($themeName){
		$index = -1;
		foreach ($this->mapLayers as $key => $value){ 
			if($value["name"] == $themeName) $index = $key; 
		}  
		return $index;
	}

	function _getLegendArray($layergroupId) {
        // check if SLD is used
        $sql = "SELECT theme_name, layergroup_id, layergroup_name, sld FROM ".DB_SCHEMA.".layergroup INNER JOIN ".DB_SCHEMA.".theme USING(theme_id) WHERE layergroup_id=? ";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($layergroupId));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if(!empty($this->i18n)) $row['sld'] = $this->i18n->translate($row['sld'], 'layergroup', $row['layergroup_id'], 'sld');
		
        if (trim($row['sld']) != '') {
            if (is_null($this->oMap)) {
                $this->oMap = ms_newMapobj("../../map/{$this->mapsetName}.map");
            }
            if (!array_key_exists($row['sld'], $this->sldContents)) {
                $ch = curl_init($row['sld']);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
                curl_setopt($ch ,CURLOPT_TIMEOUT, 10); 
                $sldContent = curl_exec($ch);
                curl_close($ch);
                
                $this->oMap->applySLD($sldContent);
                $this->sldContents[$row['sld']] = true;
            }
            if ($this->sldContents[$row['sld']]) {
                $legendArray = array();
                $sql = "SELECT layer_name FROM ".DB_SCHEMA.".layer WHERE layergroup_id=? ORDER BY layer_order";
                print_debug($sql,null,'maplegend');
				$stmt = $this->db->prepare($sql);
				$stmt->execute(array($layergroupId));
                while($row2 = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $oLayer = $this->oMap->getLayerByName("{$row['layergroup_name']}.{$row2['layer_name']}");
                    $numClasses = $oLayer->numclasses;
                    for($classIndex=0; $classIndex<$numClasses; $classIndex++) {
                        $class = $oLayer->getClass($classIndex);
                        $legendArray[] = array(
                            'class_id' => $classIndex,
                            'class_name' => $class->name,
                            'class_title' => $class->name,
                            'legendtype_id' => 1
                        );
                    }
                }
                return $legendArray;
            }
        }
        
        // default mode
        $sqlLegend = "SELECT class_id, class_name, class_title, legendtype_id FROM ".DB_SCHEMA.".class INNER JOIN ".DB_SCHEMA.".layer USING(layer_id) WHERE layer.layergroup_id=? ORDER BY layer_order, class_order";
        print_debug($sqlLegend,null,'maplegend');
		$stmt = $this->db->prepare($sqlLegend);
		$stmt->execute(array($layergroupId));
		$rowset = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $legendArray = array();
        for($i=0; $i < count($rowset); $i++) {
			if(!empty($this->i18n)) $rowset[$i]['class_title'] = $this->i18n->translate($rowset[$i]['class_title'], 'class', $rowset[$i]['class_id'], 'class_title');
			array_push($legendArray, $rowset[$i]);
		}
		return $legendArray;
	}
	
	
	function _getFeatureTypes(){

		$wfsGeometryType = array("point" => "PointPropertyType","multipoint" => "MultiPointPropertyType","linestring" => "LineStringPropertyType","multilinestring" => "MultiLineStringPropertyType","polygon" => "PolygonPropertyType" ,"multipolygon" => "MultiPolygonPropertyType","geometry" => "GeometryPropertyType");
		
		$featureTypesLinks = $this->_getFeatureTypesLinks();
		
		//Restituisce le features e i range di scala
		$userGroupFilter = '';
        $user = new GCUser();
		if(!$user->isAdmin($this->projectName)) {
			$userGroup = '';
			if(!empty($this->authorizedGroups)) $userGroup =  " OR groupname in(".implode(',', $this->authorizedGroups).")";
			$userGroupFilter = ' (groupname IS NULL '.$userGroup.') AND ';
		}
		
		$sql = "SELECT theme.project_name, theme_name, theme_title, theme_single, theme_id, layergroup_id, layergroup_name, layergroup_name || '.' || layer_name as type_name, layer.layer_id, layer.searchable_id, coalesce(layer_title,layer_name) as layer_title, data_unique, data_geom, layer.data, catalog.catalog_id, catalog.catalog_url, private, layertype_id, classitem, labelitem, maxvectfeatures, zoom_buffer, selection_color, selection_width, field_id, field_name, filter_field_name, field_header, fieldtype_id, relation_name, relation_title, relationtype_id, searchtype_id, resultype_id, datatype_id, field_filter, layer.hidden, field.editable as field_editable, field_groups.groupname as field_group,field_groups.editable as group_editable, layer.data_type, field.lookup_table, field.lookup_id, field.lookup_name,relation.relation_id, relation.data_field_1, relation.table_field_1
				FROM ".DB_SCHEMA.".theme 
				INNER JOIN ".DB_SCHEMA.".layergroup using (theme_id) 
				INNER JOIN ".DB_SCHEMA.".mapset_layergroup using (layergroup_id)
				INNER JOIN ".DB_SCHEMA.".layer using (layergroup_id)
				INNER JOIN ".DB_SCHEMA.".catalog using (catalog_id)
				LEFT JOIN ".DB_SCHEMA.".field using(layer_id)
				LEFT JOIN ".DB_SCHEMA.".relation using(relation_id)
				LEFT JOIN ".DB_SCHEMA.".field_groups using(field_id)
				WHERE $userGroupFilter layer.queryable = 1 AND mapset_layergroup.mapset_name=:mapset_name ";
		$sql .= " ORDER BY theme_title, theme_id, layer_title, layer_name, field_order, field_header;";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($this->mapsetName));
		$featureTypes = array();
        $layersWith1n = array();

		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if(!empty($this->i18n)) {
				$row = $this->i18n->translateRow($row, 'layer', $row['layer_id'], array('layer_title','classitem','labelitem'));
				$row = $this->i18n->translateRow($row, 'field', $row['field_id'], array('field_name','field_header'));
			}
	
			//DETTAGLIO AUTORIZZAZIONI
			$typeName = $row["type_name"];
			if($row['private'] == 0) {
				if(!$this->isPublicLayerQueryable) continue;
			} else {
				if(@$_SESSION['GISCLIENT_USER_LAYER'][$row['project_name']][$typeName]['WFS'] != 1) continue;
			}
		
			$typeTitle = $row["layer_title"];
			$groupTitle = empty($row["theme_title"])?$row["theme_name"]:$row["theme_title"];
			$index = ($row['theme_single'] == 1 ? 'theme' : 'layergroup') . '_' . ($row['theme_single'] == 1 ? $row['theme_id'] : $row['layergroup_id']);
			if(!isset($featureTypes[$index])) $featureTypes[$index] = array();
			if(!isset($featureTypes[$index][$typeName])) $featureTypes[$index][$typeName] = array();
/*             if($row['relationtype_id'] == 2) {
                if(!isset($layersWith1n[$index])) $layersWith1n[$index] = array();
                if(!isset($layersWith1n[$index][$typeName])) $layersWith1n[$index][$typeName] = array();
                if(!in_array($row['relation_id'], $layersWith1n[$index][$typeName])) array_push($layersWith1n[$index][$typeName], $row);
                continue;
            } */
			
			$featureTypes[$index][$typeName]["WMSLayerName"] = $row['theme_single']?$row['theme_name']:$row['layergroup_name'];	
			$featureTypes[$index][$typeName]["typeName"] = $typeName;	
			$featureTypes[$index][$typeName]["title"] = $typeTitle;	
			$featureTypes[$index][$typeName]["group"] = $groupTitle;	

			if($row['field_editable'] == 1 && !isset($featureTypes[$index][$typeName]['towsFeatureType'])) {
				$featureTypes[$index][$typeName]['towsFeatureType'] = $row['data'];
			}
			if(!empty($row["catalog_url"])) $featureTypes[$index][$typeName]["docurl"] = $row["catalog_url"];	
			if(!empty($row["classitem"])) $featureTypes[$index][$typeName]["classitem"] = $row["classitem"];
			if(!empty($row["labelitem"])) $featureTypes[$index][$typeName]["labelitem"] = $row["labelitem"];	
			if(!empty($row["data_type"])) $featureTypes[$index][$typeName]["data_type"] = $row["data_type"];
			if(!empty($row["geometryType"])) $featureTypes[$index][$typeName]["data_type"] = $row["data_type"];
			if(!empty($row["maxvectfeatures"])) $featureTypes[$index][$typeName]["maxvectfeatures"] = intval($row["maxvectfeatures"]);
			if(!empty($row["zoom_buffer"])) $featureTypes[$index][$typeName]["zoomBuffer"] = intval($row["zoom_buffer"]);
			$featureTypes[$index][$typeName]['hidden'] = intval($row['hidden']);
			$featureTypes[$index][$typeName]['searchable'] = intval($row['searchable_id']);
			if(isset($featureTypesLinks[$row['layer_id']])) {
				$featureTypes[$index][$typeName]['link'] = $featureTypesLinks[$row['layer_id']];
			}
			
			$userCanEdit = false;
			if(@$_SESSION['GISCLIENT_USER_LAYER'][$row['project_name']][$typeName]['WFST'] == 1 || $user->isAdmin($this->projectName)) $userCanEdit = true;
			
			if(!empty($row["selection_color"]) && !empty($row["selection_width"])){
				$color = "RGB(".str_replace(" ",",",$row["selection_color"]).")";$size = intval($row["selection_width"]);
				if($row["layertype_id"] == 1) $featureTypes[$index][$typeName]["symbolizer"] = array("Point" =>array("fillColor"=>"$color","pointRadius"=>$size));
				if($row["layertype_id"] == 2 || $row["layertype_id"] == 3) $featureTypes[$index][$typeName]["symbolizer"] = array("Line" =>array("strokeColor"=>"$color","strokeWidth"=>$size));
			}
			
	
			//TODO DA VERIFICARE DA VEDERE ANCHE L'OPZIONE PER IL CAMPO EDITABILE CHE SOVRASCRIVE QUELLO DI DEFAULT
			if(($fieldName = $row["field_name"]) && (empty($row["field_group"]) || in_array($row["field_group"],$this->authorizedGroups) || $user->isAdmin($this->projectName))){//FORSE NON SERVONO TUTTI GLI ATTRIBUTI!!!
				/*
				if(!empty($row["relation_name"])){
					$fieldName = $row["relation_name"] . "_" . NameReplace($row["field_header"]);
				}
				*/
				//AGGIUNGO IL CAMPO GEOMETRIA COME PRIMO CAMPO			
				if(empty($featureTypes[$index][$typeName]["properties"])) $featureTypes[$index][$typeName]["properties"] = array(
					array(
						"name"=>$row['data_geom'], 
						"type"=>$wfsGeometryType[$row['data_type']]
					)
				);

				if($row['data_unique'] == $fieldName) {
					$isPrimaryKey = 1;
				} else {
					$isPrimaryKey = 0;
				}
				
				$aRel=array();
				if($row["relation_name"]){
					$aRel["relationName"] =  $row["relation_name"];
					$aRel["relationType"] = intval($row["relationtype_id"]);
					$aRel["relationTitle"] =  $row["relation_title"]?$row["relation_title"]:$row["relation_name"];
					if(!isset($featureTypes[$index][$typeName]["relations"])) $featureTypes[$index][$typeName]["relations"] = array();
				}
                if($aRel && (!in_array($aRel, $featureTypes[$index][$typeName]["relations"])))
                	$featureTypes[$index][$typeName]["relations"][] = $aRel;

				$fieldSpecs = array(
					"name"=>$fieldName,		
					"header"=>(strtoupper(CHAR_SET) != 'UTF-8')?utf8_encode($row["field_header"]):$row["field_header"],
					"type"=>"String",//TODO
					"fieldId"=>intval($row["field_id"]),
					"fieldType"=>intval($row["fieldtype_id"]),
					"dataType"=>intval($row["datatype_id"]),
					"searchType"=>intval($row["searchtype_id"]),
					'editable'=>$userCanEdit ? intval($row['field_editable']) : 0,
					"resultType"=>intval($row["resultype_id"]),
					'isPrimaryKey'=>$isPrimaryKey
				);

                if($row["relation_name"]){
					$fieldSpecs["relationName"] =  $row["relation_name"];
                }
				if($row["filter_field_name"]){
					$fieldSpecs["filterFieldName"] = $row["filter_field_name"];intval($row["field_filter"]);
					$fieldSpecs["fieldFilter"] =  intval($row["field_filter"]);
				}

				if(!empty($row['lookup_table']) && !empty($row['lookup_id']) && !empty($row['lookup_name'])) {
					$fieldSpecs['lookup'] = array(
						'catalog'=>$row['catalog_id'],
						'table'=>$row['lookup_table'],
						'id'=>$row['lookup_id'],
						'name'=>$row['lookup_name']
					);
				}

				$featureTypes[$index][$typeName]["properties"][] = $fieldSpecs;
			}
		}
/*         foreach($layersWith1n as $index => $arr) {
            foreach($arr as $typeName => $Relations) {
                foreach($Relations as $Relation) {
                    $featureTypes[$index][$typeName]['relation1n'] = $Relation;
                    array_push($featureTypes[$index][$typeName]['properties'], array(
                        'name'=>'num_'.$Relation['relation_id'],
                        'header'=>'Num',
                        'type'=>'String',
                        'fieldId'=>9999999,
                        'fieldType'=>1,
                        'dataType'=>2,
                        'searchType'=>0,
                        'editable'=>0,
                        'relationType'=>null,
                        'resultType'=>1,
                        'filterFieldName'=>null,
                        'isPrimaryKey'=>false,
                        'is1nCountField'=>true
                    ));
                }
            }
        } */
		
		foreach($featureTypes as $index => $arr) {
			foreach($arr as $typeName => $ftype) {
				$this->featureTypes[] = $ftype;
			}
		}
		unset($featureTypes);

	}
	
	private function _getFeatureTypesLinks() {
        //FD: questi adesso sono qt_link oppure layer_link?!?!
        return array();
		$sql = "select layer_id, link_name, link_def, winw, winh ".
			" from ".DB_SCHEMA.".link inner join ".DB_SCHEMA.".qtlink using(link_id) ".
			" INNER JOIN ".DB_SCHEMA.".layer using (layer_id) ".
			" INNER JOIN ".DB_SCHEMA.".mapset_layergroup using (layergroup_id) ".
			" where mapset_layergroup.mapset_name=:mapset_name ORDER BY link_order;";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array(':mapset_name'=>$this->mapsetName));
		$links = array();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if(strtoupper(CHAR_SET) != 'UTF-8') $row["link_name"] = utf8_encode($row["link_name"]);
			$links[$row['layer_id']][] = array('name'=>$row["link_name"],'url'=>$row['link_def'],'width'=>$row['winw'],'height'=>$row['winh']);
		}
		return $links;
	}
	
	function _getSelgroup(){
		$sql = "SELECT selgroup.selgroup_id,selgroup_name,selgroup_title,layergroup_name||'.'||layer_name AS type_name 
		FROM ".DB_SCHEMA.".layer INNER JOIN ".DB_SCHEMA.".layergroup USING(layergroup_id) INNER JOIN ".DB_SCHEMA.".mapset_layergroup USING(layergroup_id) 
		INNER JOIN ".DB_SCHEMA.".selgroup_layer USING (layer_id) INNER JOIN ".DB_SCHEMA.".selgroup USING (selgroup_id) 
		WHERE layer.queryable=1 AND mapset_name=:mapset_name ORDER BY selgroup_order;";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array(':mapset_name'=>$this->mapsetName));
		$rowset = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $selgroupArray = array();
        for($i=0; $i < count($rowset); $i++) {
			if(!empty($this->i18n)) $rowset[$i]['selgroup_title'] = $this->i18n->translate($rowset[$i]['selgroup_title'], 'selgroup', $rowset[$i]['selgroup_id'], 'selgroup_title');
			$selgroupArray[$rowset[$i]["selgroup_name"]]["title"] = $rowset[$i]["selgroup_title"];
			$selgroupArray[$rowset[$i]["selgroup_name"]]["type_name"][] = $rowset[$i]["type_name"];
		}
		$this->selgroupList = $selgroupArray;
	}

	function _getTMSExtent($tilesExtent, $tilesExtentSRID){
		list($x0,$y0,$x1,$y1) = explode ($this->coordSep,$tilesExtent);
		//RIPROIETTO SE SRID DIVERSO DAL MAPSET
		if($tilesExtentSRID!=$this->mapsetSRID){
			$p1 = "SRID=$tilesExtentSRID;POINT($x0 $y0)";
			$p2 = "SRID=$tilesExtentSRID;POINT($x1 $y1)";
			$sqlExt = "SELECT X(st_transform('$p1'::geometry,".$this->mapsetSRID.")) as x0, ".
				" Y(st_transform('$p1'::geometry,".$this->mapsetSRID.")) as y0, ".
				" X(st_transform('$p2'::geometry,".$this->mapsetSRID.")) as x1, ".
				" Y(st_transform('$p2'::geometry,".$this->mapsetSRID.")) as y1;";
			$ext = $this->db->query($sqlExt)->fetch(PDO::FETCH_ASSOC);
			$extent = array(floatval($ext["x0"]),floatval($ext["y0"]),floatval($ext["x1"]),floatval($ext["y1"]));		
		}
		else
			$extent = array(floatval($x0),floatval($y0),floatval($x1),floatval($y1));
			
		return $extent;
	}	

	
	//Elenco delle librerie per i providers usati
	function _setMapProviders(){
		$jsText = "";
		foreach($this->listProviders as $key){
			$jsText .= "script = document.createElement('script');script.type = \"text/javascript\";";
			$jsText .= "script.src=\"".$this->mapProviders[$key]."\";";
			$jsText .= "document.getElementsByTagName('head')[0].appendChild(script);\n";
		}
		if($jsText) $jsText = "var script;".$jsText;
		return $jsText;
	}
	
	
	//RESITUTISCO GIA LA MAPPA OL
	function OLMap(){
		//FIX PER VERSIONE XJTJS DELLE MAPPE
		$this->mapProviders[GMAP_LAYER_TYPE].="&callback=GisClient.initMapset";
		//CONFIGURAZIONE OPENLAYERS MAP
		$aLayerText = array();
		foreach($this->mapConfig["layers"] as $layer){
			$aLayerText[] = $this->_OLlayerText($layer);
		}

		$loader=false;
		$jsText=$this->_setMapProviders();
		if($jsText) $loader = true;

		$this->mapConfig["mapOptions"]["allOverlays"] = false;
		$mapsetOptions = '"name":"'.addslashes($this->mapConfig["name"]).'","title":"'.addslashes($this->mapConfig["title"]).'","project":"'.addslashes($this->mapConfig["projectName"]).'","projectTitle":"'.addslashes($this->mapConfig["projectTitle"]).'","baseLayerName":"'.$this->activeBaseLayer.'","projectionDescription":"'.addslashes($this->mapConfig["projectionDescription"]).'","minZoomLevel":'.$this->mapConfig['mapOptions']['minZoomLevel'];
		//if(isset($this->mapConfig['selgroup'])) $mapsetOptions .=',"selgroup":'.json_encode($this->mapConfig['selgroup']);
		//$this->mapConfig["mapOptions"]["resolutions"] = array_slice($this->mapConfig["mapOptions"]["serverResolutions"],$this->mapConfig["mapOptions"]["minZoomLevel"],$this->mapConfig["mapOptions"]["numZoomLevels"]);
		$jsText .= "var GisClient = GisClient || {}; GisClient.mapset = GisClient.mapset || [];\n";
		$jsText .= 'GisClient.mapset.push({'.$mapsetOptions.',"map":'.json_encode($this->mapConfig["mapOptions"]).',"layers":['.implode(',',$aLayerText).'],"featureTypes":'.json_encode($this->mapConfig["featureTypes"]).'});';
		if($this->mapProviders[GMAP_LAYER_TYPE] && $loader) $jsText .= 'GisClient.loader=true;';
		return $jsText;
	}

	function _OLlayerText($aLayer){
		switch ($aLayer["type"]){
			case "WMS":
				$aLayer["options"]["group"] = $aLayer["options"]["theme"];
				return 'new OpenLayers.Layer.WMS("'.$aLayer["name"].'","'.$aLayer["url"].'",'.json_encode($aLayer["parameters"]).','.json_encode($aLayer["options"]).')';
			case "Google":
				$aLayer["options"]["group"] = $aLayer["options"]["theme"];
				if($this->mapsetSRID == GOOGLESRID || $this->mapsetSRID == 900913)
					return 'new OpenLayers.Layer.Google("'.$aLayer["name"].'",'.json_encode($aLayer["options"]).')';
				break;
			case "Bing":
				$aLayer["options"]["group"] = $aLayer["options"]["theme"];
				$aLayer["options"]["name"] = $aLayer["name"];
				if(defined('BINGKEY') && ($this->mapsetSRID == GOOGLESRID || $this->mapsetSRID == 900913))
					return 'new OpenLayers.Layer.Bing('.json_encode($aLayer["options"]).')';
				break;	
			case "OSM":
				$aLayer["options"]["group"] = $aLayer["options"]["theme"];
				if($this->mapsetSRID == GOOGLESRID || $this->mapsetSRID == 900913)
					return 'new OpenLayers.Layer.OSM("'.$aLayer["name"].'",null,'.json_encode($aLayer["options"]).')';
				break;
			case "WMTS":
				$aLayer["paramaters"]["group"] = $aLayer["parameters"]["theme"];
				$aLayer["parameters"]["name"]=$aLayer["name"];
				return 'new OpenLayers.Layer.WMTS('.json_encode($aLayer["parameters"]).')';
			case "TMS":
				$aLayer["options"]["group"] = $aLayer["options"]["theme"];
				return 'new OpenLayers.Layer.TMS("'.$aLayer["name"].'","'.$aLayer["url"].'/",{"visibility":'.(empty($aLayer["options"]["visibility"])?'true':'false').',"isBaseLayer":'.($aLayer["options"]["isBaseLayer"]?'true':'false').',"layername":"'.$aLayer["options"]["layername"].'","buffer":'.$aLayer["options"]["buffer"].',"type":"'.$aLayer["options"]["type"].'","tileOrigin":new OpenLayers.LonLat('.implode(",",$aLayer["options"]["tileOrigin"]).'),"zoomOffset":'.$aLayer["options"]["zoomOffset"].',"group":"'.$aLayer["options"]["group"].'"})';
			}
	}
	
    function _getScaleList() {        
        $sql = "SELECT mapset_scales FROM ".DB_SCHEMA.".mapset WHERE mapset_name=?";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($this->mapsetName));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		print_debug($sql,null,'mapoptions');
        if ($row['mapset_scales'] !='') {
            $ret = explode(',', $row['mapset_scales']);
        } else if (defined('SCALE')) {
            $ret = explode(',', SCALE);
        } else {
            $ret = GCAuthor::$defaultScaleList;
        }
        return $ret;
    }
	
	function _getResolutions($minScale,$maxScale,$sizeUnitId){

		//156543.03390625,78271.516953125,39135.7584765625,19567.87923828125,9783.939619140625,4891.9698095703125,2445.9849047851562,1222.9924523925781,611.4962261962891,305.74811309814453,152.87405654907226,76.43702827453613,38.218514137268066,19.109257068634033,9.554628534317017,4.777314267158508,2.388657133579254,1.194328566789627,0.5971642833948135,0.29858214169740677,0.14929107084870338,0.07464553542435169
				//Fattore di conversione tra dpi e unità della mappa
		$convFact = GCAuthor::$aInchesPerUnit[$sizeUnitId]*MAP_DPI;
		if(!$this->serverResolutions){
			//se mercatore sferico setto le risoluzioni di google altrimenti uso quelle predefinite dall'elenco scale
			$aRes = array();
			if($this->mapsetSRID == GOOGLESRID || $this->mapsetSRID == 900913){
			    for($lev=SERVICE_MIN_ZOOM_LEVEL; $lev<=SERVICE_MAX_ZOOM_LEVEL; ++$lev) 
					$aRes[] = SERVICE_MAX_RESOLUTION / pow(2,$lev);
			}
			else{
	            $scaleList = $this->_getScaleList();
				foreach($scaleList as $scaleValue)	$aRes[] = $scaleValue/$convFact;
			}
			$this->serverResolutions = $aRes;
		}

		$minResIndex = count($this->serverResolutions);
		$maxResIndex = 0;
		if($minScale){
			$res = (string)(floatval($minScale)/$convFact);
			if(array_index($this->serverResolutions,$res)!==false)
				$minResIndex = array_index($this->serverResolutions,$res);
		}
		
		if($maxScale){
			$res = (string)(floatval($maxScale)/$convFact);
			if(array_index($this->serverResolutions,$res)!==false)
				$maxResIndex = array_index($this->serverResolutions,$res);
		}

		$this->minZoomLevel = $maxResIndex;
		$this->maxZoomLevel = $minResIndex;
		$this->numZoomLevels = $minResIndex-$maxResIndex;

	}
	
	function _getProjectionDescription($authName, $authSrid) {
		$sql = "SELECT srtext FROM spatial_ref_sys WHERE auth_name=:auth_name AND auth_srid=:auth_srid";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array(':auth_name'=>$authName, ':auth_srid'=>$authSrid));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$parts = explode(',',$row['srtext']);
		return trim(substr($parts[0], strpos($parts[0], '[')+1), '"');
	}
	
	function _getProj4jsDefs() {
		$sql = "SELECT 'EPSG:'||auth_srid as epsg, proj4text||coalesce('+towgs84='||projparam,'') as srstext FROM ".DB_SCHEMA.".project_srs INNER JOIN spatial_ref_sys USING (srid) WHERE project_name=:project_name and srid not in (4326,900913)";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array(':project_name'=>$this->projectName));
		$list = array();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$list[$row["epsg"]]=$row["srstext"];
		}
		return $list;
	}

	function _getExtent($xCenter,$yCenter,$Resolution){
		$aExtent=array();
		$extent = $Resolution * TILE_SIZE / 2;
		//echo $extent;return;
		$aExtent[0] = $xCenter - $extent;
		$aExtent[1] = $yCenter - $extent;
		$aExtent[2] = $xCenter + $extent;
		$aExtent[3] = $yCenter + $extent;
		

		return $aExtent;
	}
	
	function _getUsercontext($contextId) {
        $user = new GCUser();
        if(!$user->isAuthenticated()) return array();
		//if(empty($_SESSION) || empty($_SESSION['USERNAME'])) return array();
		$sql = "SELECT context FROM ".DB_SCHEMA.".usercontext WHERE username=:username AND mapset_name=:mapset_name AND id=:id";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array(':username'=>$user->getUsername(), ':mapset_name'=>$this->mapsetName, ':id'=>$contextId));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if(!empty($row)) return json_decode($row["context"], true);
		else return array();
	}
    
    function _getMaxExtents() {
        $extents = array();
		$userGroupFilter = '';
		if(empty($_SESSION['USERNAME'])) {
			$userGroup = '';
			if(!empty($this->authorizedGroups)) $userGroup =  " OR groupname in(".implode(',', $this->authorizedGroups).")";
			$userGroupFilter = ' (groupname IS NULL '.$userGroup.') AND ';
		}
		
		$sql = "SELECT layergroup_id, layer_id, data_extent
				FROM ".DB_SCHEMA.".layer 
				INNER JOIN ".DB_SCHEMA.".layergroup using (layergroup_id) 
				INNER JOIN ".DB_SCHEMA.".mapset_layergroup using (layergroup_id)
				WHERE mapset_layergroup.mapset_name=:mapset_name 
                order BY layergroup_id ";
		
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($this->mapsetName));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        array_push($rows, array('layergroup_id'=>-1));
        //var_export($rows);
        $lgId = 0;
        $complete = true;
        $groupExtents = array();
        foreach($rows as $row) {
            if($lgId != $row['layergroup_id']) {
                if($complete && !empty($groupExtents)) {
                    $extent = array(null, null, null, null);
                    foreach($groupExtents as $ext) {
                        list($x1, $y1, $x2, $y2) = explode(' ', $ext);
                        if(empty($extent[0]) || $x1 < $extent[0]) $extent[0] = $x1;
                        if(empty($extent[1]) || $y1 < $extent[1]) $extent[1] = $y1;
                        if(empty($extent[2]) || $x2 > $extent[2]) $extent[2] = $x2;
                        if(empty($extent[3]) || $y2 > $extent[3]) $extent[3] = $y2;
                    }
                    $extents[$lgId] = $extent;
                }
                $complete = true;
                $groupExtents = array();
            }
            $lgId = $row['layergroup_id'];
            if(empty($row['data_extent'])) $complete = false;
            else {
                array_push($groupExtents, $row['data_extent']);
            }
        }
        //var_export($extents);
        return $extents;
    }
	
	
	
	function _array_limit($aList,$maxVal=false,$minVal=false){
		$ar=array();
		foreach($aList as $val){
			if($maxVal && $val>=$maxVal) $ar[]=$val;
			if($minVal && $val<$minVal) $ar[]=$val;
		}
		return array_values(array_diff($aList,$ar));
	}
	
	function _array_index($aList, $value){
		$retval=0;
		for($i=0;$i<count($aList);$i++){
			if($value<$aList[$i]) $retval=$i-1;
		}
		return $retval;
	}


	
}
