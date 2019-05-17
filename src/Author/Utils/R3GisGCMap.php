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

namespace GisClient\Author\Utils;

use GisClient\Author\Security\LayerAuthorizationChecker;
use GisClient\Author\LayerGroup;

define('GOOGLESRID', 3857);

if (!defined('SERVICE_MAX_RESOLUTION')) {
    define('SERVICE_MAX_RESOLUTION', 156543.03390625);
}
if (!defined('SERVICE_MIN_ZOOM_LEVEL')) {
    define('SERVICE_MIN_ZOOM_LEVEL', 0);
}
if (!defined('SERVICE_MAX_ZOOM_LEVEL')) {
    define('SERVICE_MAX_ZOOM_LEVEL', 21);
}

class R3GisGCMap
{
    const SCALE_TYPE_USER = 0;
    const SCALE_TYPE_POWEROF2 = 1;
    public $db;

    private $baseUrl;
        
    /**
     * Layer authorization checker
     *
     * @var LayerAuthorizationChecker
     */
    private $layerAuthChecker;
        
    public $authorizedLayers;
    public $authorizedGroups = array();
    public $selgroupList = array();
    public $mapLayers =  array();
    public $projectName;
    public $mapsetName;
    public $mapsetScaleType;
    public $mapOptions;
    public $maxResolution;
    public $minResolution;
    public $mapsetSRID;
    public $activeBaseLayer = '';
    public $isPublicLayerQueryable = true; //FLAG CHE SETTA I LAYER PUBBLICI ANCHE INTERROGABILI
    public $fractionalZoom = 0;
    public $allOverlays = 0;
    public $conversionFactor;
    public $coordSep = ' ';
    public $listProviders = array(); //Elenco dei provider settati per il mapset
    public $aUnitDef = array(1=>"m",2=>"ft",3=>"inches",4=>"km",5=>"m",6=>"mi",7=>"dd");//units tables (force pixel ->m)
    public $getLegend;
    private $onlyPublicLayers;

    public $mapProviders = array(
            LayerGroup::VMAP_LAYER_TYPE => "http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.3",
            LayerGroup::YMAP_LAYER_TYPE => "http://api.maps.yahoo.com/ajaxymap?v=3.0&appid=euzuro-openlayers",
            LayerGroup::OSM_LAYER_TYPE => "http://openstreetmap.org/openlayers/OpenStreetMap.js",
            LayerGroup::GMAP_LAYER_TYPE => "http://maps.google.com/maps/api/js?callback=GisClient.initMapset&sensor=false");//Elenco dei provider di mappe OSM GMap VEMap YMap come mappati in tabelle e_owstype
    
    private $i18n;
    protected $oMap;
    protected $sldContents = array();
    
    
    public function __construct($baseUrl, $mapsetName, $getLegend = false, $languageId = null, $onlyPublicLayers = false)
    {
        $this->baseUrl = $baseUrl;
        $this->getLegend = $getLegend;
        $this->onlyPublicLayers = $onlyPublicLayers;

        $this->db = \GCApp::getDB();
        $this->layerAuthChecker = \GCApp::getLayerAuthorizationChecker();
        
        $sql = "SELECT mapset.*, ".
            " st_x(st_transform(st_geometryfromtext('POINT('||xc||' '||yc||')',project_srid),mapset_srid)) as xc, ".
            " st_y(st_transform(st_geometryfromtext('POINT('||xc||' '||yc||')',project_srid),mapset_srid)) as yc, ".
            " max_extent_scale, project_title FROM ".DB_SCHEMA.".mapset ".
            " INNER JOIN ".DB_SCHEMA.".project USING (project_name) WHERE mapset_name=?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($mapsetName));
        
        if ($stmt->rowCount() == 0) {
            $msg = "Il mapset \"{$mapsetName}\" non esiste<br /><br />\n\n";
            print_debug($msg.': '.$stmt->queryString, null, 'service');
            throw new \Exception($msg);
        }
        
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!empty($languageId)) {
            $this->i18n = new \GCi18n($row['project_name'], $languageId);
            $row['mapset_title'] = $this->i18n->translate(
                $row['mapset_title'],
                'mapset',
                $row['mapset_name'],
                'mapset_title'
            );
            $row['project_title'] = $this->i18n->translate(
                $row['project_title'],
                'project',
                $row['project_name'],
                'project_title'
            );
        }
        
        $this->projectName = $row["project_name"];
        $this->mapsetName = $row["mapset_name"];
        $this->mapsetScaleType = $row["mapset_scale_type"];
        $sizeUnitId = empty($row["sizeunits_id"]) ? 5 : intval($row["sizeunits_id"]);
        if ($row["mapset_srid"]==4326) {
            $sizeUnitId = 7;
        } //Forzo dd se in 4326
        
        $mapOptions=array();
        $mapOptions["mapset"] = $row["mapset_name"];
        $mapOptions["title"] = (strtoupper(CHAR_SET) != 'UTF-8')?utf8_encode($row["mapset_title"]):$row["mapset_title"];
        $mapOptions["project"] = $row["project_name"];
        if (!empty($row["project_title"])) {
            $mapOptions["projectTitle"] = (strtoupper(CHAR_SET) != 'UTF-8') ?
                utf8_encode($row["project_title"]) :
                $row["project_title"];
        }
        $mapOptions["units"] = $this->aUnitDef[$sizeUnitId];
        $mapOptions["dpi"] = MAP_DPI;
        $mapOptions["projection"] = "EPSG:".$row["mapset_srid"];
        $mapOptions['projectionDescription'] = $this->getProjectionDescription('EPSG', $row['mapset_srid']);
        if (!empty($row["displayprojection"])) {
            $mapOptions["displayProjection"] = "EPSG:".$row["displayprojection"];
            $mapOptions["displayProjectionDescription"] = $this->getProjectionDescription(
                'EPSG',
                $row['displayprojection']
            );
        }
        $this->mapsetSRID = $row["mapset_srid"];
        $this->fractionalZoom = 1;
        
        //Fattore di conversione tra dpi e unità della mappa
        $pixelsPerUnit = \GCAuthor::$aInchesPerUnit_old[$sizeUnitId]*MAP_DPI;
        $this->conversionFactor = $pixelsPerUnit;
        
        // resolution is m/px
        if ($row["max_extent_scale"] > 0) {
            $maxRes = $row["max_extent_scale"]/$pixelsPerUnit;
            if ($row["maxscale"] > 0) {
                $maxRes = min($maxRes, floatval($row["maxscale"])/$pixelsPerUnit);
            }
        } elseif ($row["maxscale"] > 0) {
            $maxRes = floatval($row["maxscale"])/$pixelsPerUnit;
        } else {
            $maxRes = false;
        }
        
        if ($row["minscale"] > 0) {
            $minRes = floatval($row["minscale"])/$pixelsPerUnit;
        } else {
            $minRes = false;
        }
        
        //Normalizzo rispetto all'array delle risoluzioni
        $mapOptions["resolutions"] = $this->getResolutions($this->mapsetScaleType);
        $mapOptions["minZoomLevel"] = $this->arrayIndex($mapOptions["resolutions"], $maxRes);
        $mapOptions["maxResolution"] = $mapOptions["resolutions"][0];
        $this->maxResolution = $mapOptions["maxResolution"];
        $mapOptions["minResolution"] = $mapOptions["resolutions"][count($mapOptions["resolutions"])-1];
        $this->minResolution = $mapOptions["minResolution"];
        $mapOptions["maxExtent"] = $this->getExtent($row["xc"], $row["yc"], $maxRes);
        
        //Limita estensione:
        if (($row["mapset_extent"])) {
            $ext = explode($this->coordSep, $row["mapset_extent"]);
            $mapOptions["restrictedExtent"] = array(
                floatval($ext[0]),
                floatval($ext[1]),
                floatval($ext[2]),
                floatval($ext[3])
            );
        }
        
        if ($this->onlyPublicLayers) {
            // force a recolulation of the visible layers
                    $allUserLayers = $this->layerAuthChecker->getLayers(array(
                        'mapset_name' => $mapsetName,
                        'show_as_public' => 1
                    ));
            $this->authorizedLayers = array();
        } else {
            $allUserLayers = $this->layerAuthChecker->getLayers(array(
                        'mapset_name' => $mapsetName,
                    ));
            $this->authorizedLayers = $allUserLayers['authorized_layers'];
        }

                
        $this->mapLayers = $allUserLayers['map_layers'];
        
        
        $mapOptions["theme"] = $this->getLayers();
        $this->getSelgroup();
        if ($this->selgroupList) {
            $mapOptions["selgroup"] = $this->selgroupList;
        }

        // SE HO DEFINITO UN CONTESTO AGGIUNGO LE OPZIONI DI CONTESTO
        //  (PER ORA AGGIUNGO I LAYER DEL REDLINE) (TODO FRANCESCO)
        // SOVRASCRIVO GLI ATTRIBUTI DI mapOptions E AGGIUNGO I LAYER DEL CONTEXT
        // LASCEREI IL DOPPIO PASSAGGIO JSONENCODE JSONDECODE PER IL CONTROLLO DEGLI ERRORI ..... DA VEDERE
        
        if (!empty($_REQUEST['context'])) {
            $userContext = $this->_getUserContext($_REQUEST['context']);
            if (!empty($userContext) && !empty($userContext['layers'])) {
                $mapOptions["context_layers"] = $userContext['layers'];
            }
        }
        
        // background diverso da bianco/trasparente
        if (!empty($row['bg_color']) && $row['bg_color'] != '255 255 255') {
            $mapOptions['bg_color'] = $row['bg_color'];
        }
        
        $this->mapOptions = $mapOptions;
    }
    
    private function getLayers()
    {
        $aLayers = array();
        
        $featureTypes = $this->getFeatureTypes();
        $extents = $this->getMaxExtents();

        $sqlParams = array();
        $sqlAuthorizedLayers = "FALSE";
        if (count($this->authorizedLayers)) {
            $sqlAuthorizedLayers = "layer_id IN (".implode(',', $this->authorizedLayers).")";
        }
        $sqlLayers = "SELECT theme_id, theme_name, theme_title, theme_single, theme.radio, theme.copyright_string,
                             layergroup.*, mapset_layergroup.*, outputformat_mimetype, outputformat_extension
                      FROM ".DB_SCHEMA.".layergroup 
                      INNER JOIN ".DB_SCHEMA.".mapset_layergroup USING (layergroup_id) 
                      INNER JOIN ".DB_SCHEMA.".theme USING(theme_id) 
                      LEFT JOIN ".DB_SCHEMA.".e_outputformat USING(outputformat_id) 
			          WHERE mapset_name = :mapset_name AND 
                            layergroup_id IN (
                                SELECT layergroup_id 
                                FROM ".DB_SCHEMA.".layer 
                                INNER JOIN ".DB_SCHEMA.".layergroup using(layergroup_id) 
                                INNER JOIN ".DB_SCHEMA.".mapset_layergroup USING (layergroup_id) 
                                INNER JOIN ".DB_SCHEMA.".mapset USING (mapset_name) 
                                WHERE mapset_name = :mapset_name
                                AND ({$sqlAuthorizedLayers} OR (layer.private=0 AND mapset.private=0)) 
                                UNION
                                SELECT layergroup_id 
                                FROM ".DB_SCHEMA.".layergroup 
                                LEFT JOIN ".DB_SCHEMA.".layer 
                                USING (layergroup_id) 
                                WHERE layer_id IS NULL)
                      ORDER BY theme.theme_order,theme.theme_title, layergroup.layergroup_order,
                               layergroup.layergroup_title;";
        $stmt = $this->db->prepare($sqlLayers);
        $stmt->bindValue(':mapset_name', $this->mapsetName);
        $stmt->execute();

        $ows_url = $this->baseUrl . '/services/ows.php';
        $tiles_cache_url = $this->baseUrl . '/services/tms/';

        $rowset = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        for ($i=0; $i < count($rowset); $i++) {
            $row = $rowset[$i];
            if (!empty($this->i18n)) {
                $row = $this->i18n->translateRow($row, 'theme', $row['theme_id'], array(
                    'theme_title', 'copyright_string'
                ));
                $row = $this->i18n->translateRow($row, 'layergroup', $row['layergroup_id'], array(
                    'layergroup_title', 'sld'
                ));
            }
            
            $themeName = $row['theme_name'];
            $mapsetName = $row['mapset_name'];
            $themeTitle = empty($row['theme_title']) ?
                $theme_name :
                ((strtoupper(CHAR_SET) != 'UTF-8') ?
                    utf8_encode($row["theme_title"]) :
                    $row["theme_title"]
                );
            $layergroupName = $row['layergroup_name'];
            $layergroupTitle = empty($row['layergroup_title']) ?
                $layergroupName :
                ((strtoupper(CHAR_SET) != 'UTF-8') ?
                    utf8_encode($row["layergroup_title"]) :
                    $row["layergroup_title"]
                );
            $layerId = $this->projectName.".".$layergroupName;
            $layerType = intval($row["owstype_id"]);
            
            //SE METTO LA / NON METTE GRUPPO
            if (empty($row['tree_group'])) {
                $layerTreeGroup = $themeTitle;
            } elseif ($row['tree_group']=="/") {
                $layerTreeGroup = "";
            } else {
                $layerTreeGroup = (strtoupper(CHAR_SET) != 'UTF-8')?utf8_encode($row["tree_group"]):$row["tree_group"];
            }
                
            $layerOptions=array();
            if ($row["status"] == 0) {
                $layerOptions["visibility"] = false;
            }
            if ($row["hidden"] == 1) {
                $layerOptions["displayInLayerSwitcher"] = false;
            }
            if (!empty($row['copyright_string'])) {
                $layerOptions["attribution"] = (strtoupper(CHAR_SET) != 'UTF-8') ?
                    utf8_encode($row["copyright_string"]) :
                    $row["copyright_string"];
            }
            if ($row["isbaselayer"] == 1 && $row["status"] == 1) {
                $this->activeBaseLayer = $layerId;
            }
            if ($row['opacity'] != null && $row['opacity'] != 100) {
                $layerOptions['opacity'] = $row['opacity']/100;
            }
            if (!empty($row['metadata_url'])) {
                $layerOptions['metadataUrl'] = $row['metadata_url'];
            }
            if (!empty($extents[$row['layergroup_id']])) {
                $layerOptions['maxExtent'] = $extents[$row['layergroup_id']];
            }
            
            //ALLA ROVESCIA RISPETTO A MAPSERVER
            if ($row["layergroup_maxscale"]>0) {
                $layerOptions["minScale"] = floatval($row["layergroup_maxscale"]);
            }
            if ($row["layergroup_minscale"]>0) {
                $layerOptions["maxScale"] = floatval($row["layergroup_minscale"]);
            }

            
            if (empty($aLayers[$themeName])) {
                $aLayers[$themeName] = array();
                $aLayers[$themeName]["title"] = $themeTitle;
                if ($row["radio"] == 1) {
                    $aLayers[$themeName]["radio"] = 1;
                }
                //if($row['hide'] == 1) $aLayers[$themeName]['hide'] = 1;
            }

            if ($layerType == LayerGroup::WMS_LAYER_TYPE) {
                $layerUrl = isset($row["url"])?$row["url"]:$ows_url;
                $layerParameters=array();
                $layerParameters["project"] = $this->projectName;
                $layerParameters["map"] = $mapsetName;// AGGIUNGIAMO LA LINGUA ??? $row["theme_name"];
                $layerParameters["exceptions"] = (defined('DEBUG') && DEBUG==1)?'xml':'blank';
                $layerParameters["format"] = $row["outputformat_mimetype"];
                $layerParameters["transparent"] = true;
                $layerParameters['gisclient_map'] = 1;
                if (!empty($_REQUEST["tmp"])) {
                    $layerParameters['tmp'] = 1;
                }
                
                if (!empty($row['url']) && (!empty($row['layers']) || $row['layers'] == '0')) {
                    $layerParameters["layers"] = $row['layers'];
                } elseif ($row["theme_single"] == 1) {
                    $list=array();
                    foreach ($this->mapLayers[$themeName] as $layergroupLayers) {
                        $list = array_merge($list, $layergroupLayers);
                    }
                    $layerParameters["layers"] = $list;
                } elseif ($row["layergroup_single"] == 1) {
                    $layerParameters["layers"] = array($layergroupName);
                } else {
                    $layerParameters["layers"] = $this->mapLayers[$themeName][$layergroupName];
                    $list=array();
                    foreach ($this->mapLayers[$themeName][$layergroupName] as $layer) {
                        if (isset($layer["name"])) {
                            array_push($list, $layer["name"]);
                        }
                    }
                    $layerParameters["layers"] = $list;
                }
                
                if (!empty($row['sld'])) {
                    $layerParameters["sld"] = $row["sld"];
                }
                    
                // TODO: check for layergroup.layername
                $layerOptions["buffer"] = intval($row["buffer"]);
                if ($row["isbaselayer"]==1) {
                    $layerOptions["isBaseLayer"] = true;
                }
                if ($row["transition"]==1) {
                    $layerOptions["transitionEffect"] = "resize";
                }
                if ($row["gutter"]>0) {
                    $layerOptions["gutter"] = intval($row["gutter"]);
                }
                if ($row["tiletype_id"]==0) {
                    $layerOptions["singleTile"] = true;
                }

                if ($row["theme_single"]==1) {
                    //setto tutti i parametri come da 1 layer
                    if (empty($aLayers[$themeName]["type"])) {
                        $aLayers[$themeName]["type"] = $layerType;
                        $aLayers[$themeName]["title"] = $themeTitle;
                        $aLayers[$themeName]["url"] = $layerUrl;
                        
                        if (empty($aLayers[$themeName]["options"])) {
                            $aLayers[$themeName]["options"] = array("minScale"=>false,"maxScale"=>false);
                        }
                        //Conservo i range di scala più estesi
                        if ($row["layergroup_maxscale"] > 0 ||
                            $row["layergroup_maxscale"] < $aLayers[$themeName]["options"]["minScale"]
                        ) {
                            $layerOptions["minScale"] = intval($row["layergroup_maxscale"]);
                        }
                        if ($row["layergroup_minscale"] > 0 ||
                            $row["layergroup_minscale"] > $aLayers[$themeName]["options"]["maxScale"]
                        ) {
                            $layerOptions["maxScale"] = intval($row["layergroup_minscale"]);
                        }
                        $aLayers[$themeName]["options"] = $layerOptions;
                        $aLayers[$themeName]["options"]["gc_id"] = $themeName;
                        if (!empty($row['tree_group'])) {
                            $aLayers[$themeName]["options"]["group"] = $row['tree_group'];
                        }
                        $aLayers[$themeName]["parameters"] = $layerParameters;
                        
                        if (isset($featureTypes['theme_'.$row['theme_id']])) {
                            $aLayers[$themeName]["options"]['featureTypes'] = array_values(
                                $featureTypes['theme_'.$row['theme_id']]
                            );
                        }
                    }
                } else {
                    if (empty($aLayers[$themeName][$layergroupName])) {
                        $aLayers[$themeName][$layergroupName] = array();
                    }
                    $aLayers[$themeName][$layergroupName]["type"] = $layerType;
                    $aLayers[$themeName][$layergroupName]["title"] = $layergroupTitle;
                    $aLayers[$themeName][$layergroupName]["url"] = $layerUrl;
                    $layerOptions["gc_id"] = $layerId;
                    $layerOptions["group"] = $layerTreeGroup;
                    $aLayers[$themeName][$layergroupName]["parameters"] = $layerParameters;

                    if (isset($featureTypes['layergroup_'.$row['layergroup_id']])) {
                        $layerOptions['featureTypes'] = array_values(
                            $featureTypes['layergroup_'.$row['layergroup_id']]
                        );
                    }
                    
                    $aLayers[$themeName][$layergroupName]["options"] = $layerOptions;
                }
            } elseif (in_array($layerType, array(
                LayerGroup::GMAP_LAYER_TYPE,
                LayerGroup::BING_LAYER_TYPE,
                LayerGroup::VMAP_LAYER_TYPE,
                LayerGroup::YMAP_LAYER_TYPE))) {//Google VE Yahoo
                $this->allOverlays = 0;
                $this->fractionalZoom = 0;
                
                if (!in_array($layerType, $this->listProviders) && $layerType!=LayerGroup::BING_LAYER_TYPE) {
                    $this->listProviders[] = $layerType;
                }

                $layerOptions["type"] = empty($row["layers"])?"null":$row["layers"];
                $layerOptions["minZoomLevel"] = SERVICE_MIN_ZOOM_LEVEL;
                $layerOptions["maxZoomLevel"] = SERVICE_MAX_ZOOM_LEVEL;
                $layerOptions["gc_id"] = $layerId;
                $layerOptions["group"] = $layerTreeGroup;
                $aLayers[$themeName]["title"] = $themeTitle;
                $aLayers[$themeName][$layergroupName]["type"] = $layerType;
                $aLayers[$themeName][$layergroupName]["title"] = $layergroupTitle;
                $aLayers[$themeName][$layergroupName]["options"]= $layerOptions;
                if ($row["status"] == 1) {
                    $this->activeBaseLayer = $layerId;
                }
            } elseif ($layerType==LayerGroup::OSM_LAYER_TYPE) {//OSM
                $this->allOverlays = 0;
                $this->fractionalZoom = 0;
                if (!in_array($layerType, $this->listProviders)) {
                    $this->listProviders[] = $layerType;
                }
                $layerOptions["gc_id"] = $layerId;
                $layerOptions["group"] = $layerTreeGroup;
                $layerOptions["minZoomLevel"] = SERVICE_MIN_ZOOM_LEVEL;
                $layerOptions["maxZoomLevel"] = SERVICE_MAX_ZOOM_LEVEL;
                $aLayers[$themeName]["title"] = $themeTitle;
                $aLayers[$themeName][$layergroupName]["type"] = $layerType;
                $aLayers[$themeName][$layergroupName]["title"] = $layergroupTitle;
                //$layerOptions["type"] = empty($row["layers"])?"null":$row["layers"];
                $aLayers[$themeName][$layergroupName]["options"]= $layerOptions;
                if ($row["status"] == 1) {
                    $this->activeBaseLayer = $layerId;
                }
            } elseif ($layerType==LayerGroup::TMS_LAYER_TYPE) {//TMS
                            $layerOptions["layers"] = $row['layers'];

                            
                $layerUrl = isset($row["url"])?$row["url"]:$tiles_cache_url.$this->projectName;
                $this->allOverlays = 0;
                $this->fractionalZoom = 0;
                $layerOptions["layername"] = $layergroupName;
                $layerOptions["serviceVersion"] = "EPSG_".$this->mapsetSRID;
                $layerOptions["owsurl"] = $ows_url."?project=".$this->projectName."&map=".$themeName;
                $layerOptions["type"] = $row['outputformat_extension'];
                if ($row["isbaselayer"]==1) {
                    $layerOptions["isBaseLayer"] = true;
                }
                //$layerOptions["getURL"] = "OpenLayers.Util.GisClient.TMSurl";
                $layerOptions["zoomOffset"] = $this->arrayIndex(
                    $this->getResolutions($this->mapsetScaleType),
                    $this->maxResolution
                );
                $layerOptions["buffer"] = intval($row["buffer"]);
                if (!empty($row["tile_origin"])) {
                    $layerOptions["tileOrigin"] = $row["tile_origin"];
                }
                if (!empty($row['tile_resolutions'])) {
                    $scales = explode(',', $row['tile_resolutions']);
                    $layerOptions['serverResolutions'] = array();
                    foreach ($scales as $scale) {
                        $layerOptions['serverResolutions'][] = $scale / $this->conversionFactor;
                    }
                }
                if (!empty($row["tiles_extent"]) && !empty($row["tiles_extent_srid"])) {
                    $layerOptions["maxExtent"] = $this->getTMSExtent($row["tiles_extent"], $row["tiles_extent_srid"]);
                }
                $layerOptions["gc_id"] = $layerId;
                $layerOptions["group"] = $layerTreeGroup;
                $layerOptions["minZoomLevel"] = SERVICE_MIN_ZOOM_LEVEL;
                $layerOptions["maxZoomLevel"] = SERVICE_MAX_ZOOM_LEVEL;
                $aLayers[$themeName][$layergroupName]["type"] = $layerType;
                $aLayers[$themeName][$layergroupName]["title"] = $layergroupTitle;
                $aLayers[$themeName][$layergroupName]["url"] = $layerUrl;
                $aLayers[$themeName][$layergroupName]["options"]= $layerOptions;
            } elseif ($layerType==LayerGroup::WMTS_LAYER_TYPE) {//TMS
                            $layerOptions["layers"] = $row['layers'];

                            
                $layerUrl = isset($row["url"])?$row["url"]:$tiles_cache_url.$this->projectName;
                $this->allOverlays = 0;
                $this->fractionalZoom = 0;
                $layerOptions["layername"] = $layergroupName;
                $layerOptions["serviceVersion"] = "EPSG_".$this->mapsetSRID;
                $layerOptions["owsurl"] = $ows_url."?project=".$this->projectName."&map=".$themeName;
                $layerOptions["type"] = $row['outputformat_extension'];
                if ($row["isbaselayer"]==1) {
                    $layerOptions["isBaseLayer"] = true;
                }
                //$layerOptions["getURL"] = "OpenLayers.Util.GisClient.TMSurl";
                $layerOptions["zoomOffset"] = SERVICE_MIN_ZOOM_LEVEL;
                $layerOptions["buffer"] = intval($row["buffer"]);
                if (!empty($row["tile_origin"])) {
                    $layerOptions["tileOrigin"] = $row["tile_origin"];
                } else {
                    throw new \Exception("tile_origin is required for wmts layers");
                }
                if (!empty($row["tile_matrix_set"])) {
                    $layerOptions["matrixSet"] = $row["tile_matrix_set"];
                } else {
                    throw new \Exception("tile_matrix_set is required for wmts layers");
                }
                if (!empty($row['style'])) {
                    $layerOptions["style"] = $row["style"];
                } else {
                    throw new \Exception("style is required for wmts layers");
                }
                if (!empty($row['tile_resolutions'])) {
                    $scales = explode(',', $row['tile_resolutions']);
                    $layerOptions['serverResolutions'] = array();
                    foreach ($scales as $scale) {
                        $layerOptions['serverResolutions'][] = $scale / $this->conversionFactor;
                    }
                }
                if (!empty($row["tiles_extent"]) && !empty($row["tiles_extent_srid"])) {
                    $layerOptions["maxExtent"] = $this->getTMSExtent($row["tiles_extent"], $row["tiles_extent_srid"]);
                }
                $layerOptions["gc_id"] = $layerId;
                $layerOptions["group"] = $layerTreeGroup;
                $layerOptions["minZoomLevel"] = SERVICE_MIN_ZOOM_LEVEL;
                $layerOptions["maxZoomLevel"] = SERVICE_MAX_ZOOM_LEVEL;
                $aLayers[$themeName][$layergroupName]["type"] = $layerType;
                $aLayers[$themeName][$layergroupName]["title"] = $layergroupTitle;
                $aLayers[$themeName][$layergroupName]["url"] = $layerUrl;
                $aLayers[$themeName][$layergroupName]["options"]= $layerOptions;
            } elseif ($layerType == LayerGroup::WFS_LAYER_TYPE) {
                $layerUrl = isset($row["url"])?$row["url"]:$ows_url;
                $layerParameters=array();
                $layerParameters["project"] = $this->projectName;
                $layerParameters["map"] = $mapsetName;// AGGIUNGIAMO LA LINGUA ??? $row["theme_name"];
                $layerParameters["exceptions"] = (defined('DEBUG') && DEBUG==1)?'xml':'blank';
                $layerParameters["format"] = $row["outputformat_mimetype"];
                $layerParameters["transparent"] = true;
                $layerParameters['gisclient_map'] = 1;
                if (!empty($_REQUEST["tmp"])) {
                    $layerParameters['tmp'] = 1;
                }

                if (!empty($row['url']) && (!empty($row['layers']) || $row['layers'] == '0')) {
                    $layerParameters["layers"] = $row['layers'];
                } elseif ($row["theme_single"] == 1) {
                    $list=array();
                    foreach ($this->mapLayers[$themeName] as $layergroupLayers) {
                        $list = array_merge($list, $layergroupLayers);
                    }
                    $layerParameters["layers"] = $list;
                } elseif ($row["layergroup_single"] == 1) {
                    $layerParameters["layers"] = array($layergroupName);
                } else {
                    $layerParameters["layers"] = $this->mapLayers[$themeName][$layergroupName];
                    $list=array();
                    foreach ($this->mapLayers[$themeName][$layergroupName] as $layer) {
                        if (isset($layer["name"])) {
                            array_push($list, $layer["name"]);
                        }
                    }
                    $layerParameters["layers"] = $list;
                }

                if (!empty($row['sld'])) {
                    $layerParameters["sld"] = $row["sld"];
                }

                // TODO: check for layergroup.layername
                $layerOptions["buffer"] = intval($row["buffer"]);
                if ($row["isbaselayer"]==1) {
                    $layerOptions["isBaseLayer"] = true;
                }
                if ($row["tiletype_id"]==0) {
                    $layerOptions["singleTile"] = true;
                }

                if ($row["theme_single"]==1) {
                    //setto tutti i parametri come da 1 layer
                    if (empty($aLayers[$themeName]["type"])) {
                        $aLayers[$themeName]["type"] = $layerType;
                        $aLayers[$themeName]["title"] = $themeTitle;
                        $aLayers[$themeName]["url"] = $layerUrl;

                        if (empty($aLayers[$themeName]["options"])) {
                            $aLayers[$themeName]["options"] = array("minScale"=>false,"maxScale"=>false);
                        }
                        //Conservo i range di scala più estesi
                        if ($row["layergroup_maxscale"] > 0 ||
                            $row["layergroup_maxscale"] < $aLayers[$themeName]["options"]["minScale"]
                        ) {
                            $layerOptions["minScale"] = intval($row["layergroup_maxscale"]);
                        }
                        if ($row["layergroup_minscale"] > 0 ||
                            $row["layergroup_minscale"] > $aLayers[$themeName]["options"]["maxScale"]
                        ) {
                            $layerOptions["maxScale"] = intval($row["layergroup_minscale"]);
                        }
                        $aLayers[$themeName]["options"] = $layerOptions;
                        $aLayers[$themeName]["options"]["gc_id"] = $themeName;
                        if (!empty($row['tree_group'])) {
                            $aLayers[$themeName]["options"]["group"] = $row['tree_group'];
                        }
                        $aLayers[$themeName]["parameters"] = $layerParameters;

                        if (isset($featureTypes['theme_'.$row['theme_id']])) {
                            $aLayers[$themeName]["options"]['featureTypes'] = array_values(
                                $featureTypes['theme_'.$row['theme_id']]
                            );
                        }
                    }
                } else {
                    if (empty($aLayers[$themeName][$layergroupName])) {
                        $aLayers[$themeName][$layergroupName] = array();
                    }
                    $aLayers[$themeName][$layergroupName]["type"] = $layerType;
                    $aLayers[$themeName][$layergroupName]["title"] = $layergroupTitle;
                    $aLayers[$themeName][$layergroupName]["url"] = $layerUrl;
                    $layerOptions["gc_id"] = $layerId;
                    $layerOptions["group"] = $layerTreeGroup;
                    $aLayers[$themeName][$layergroupName]["parameters"] = $layerParameters;

                    if (isset($featureTypes['layergroup_'.$row['layergroup_id']])) {
                        $layerOptions['featureTypes'] = array_values(
                            $featureTypes['layergroup_'.$row['layergroup_id']]
                        );
                    }

                    $aLayers[$themeName][$layergroupName]["options"] = $layerOptions;
                }
            }
            
            //FD add overview and legend
            $aLayers[$themeName][$layergroupName]['overview'] = $row['refmap'];
            if ($row['hide'] == 1) {
                $aLayers[$themeName][$layergroupName]['hide'] = 1;
            }
            if ($this->getLegend) {
                $aLayers[$themeName][$layergroupName]['legend'] = $this->getLegendArray($row['layergroup_id']);
            }
        }
        
        
        return $aLayers;
    }
    
    
    private function getLegendArray($layergroupId)
    {
        // check if SLD is used
        $sql = "
            SELECT theme_name, layergroup_id, layergroup_name, sld
            FROM ".DB_SCHEMA.".layergroup
            INNER JOIN ".DB_SCHEMA.".theme USING(theme_id)
            WHERE layergroup_id=?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($layergroupId));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!empty($this->i18n)) {
            $row['sld'] = $this->i18n->translate($row['sld'], 'layergroup', $row['layergroup_id'], 'sld');
        }
        
        if (trim($row['sld']) != '') {
            if (is_null($this->oMap)) {
                $this->oMap = \ms_newMapObjFromString(
                    file_get_contents("../../map/{$this->projectName}/{$this->mapsetName}.map")
                );
            }
            if (!array_key_exists($row['sld'], $this->sldContents)) {
                $ch = curl_init($row['sld']);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                if (false === ($sldContent = curl_exec($ch))) {
                    $errMsg = sprintf(
                        "Error getting data from '%s': %s. Server return code %d",
                        curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
                        curl_error($ch),
                        curl_getinfo($ch, CURLINFO_HTTP_CODE)
                    );
                    throw new \Exception($errMsg);
                }
                curl_close($ch);
                
                $this->oMap->applySLD($sldContent);
                $this->sldContents[$row['sld']] = true;
            }
            if ($this->sldContents[$row['sld']]) {
                $legendArray = array();
                $sql = "SELECT layer_name FROM ".DB_SCHEMA.".layer WHERE layergroup_id=? ORDER BY layer_order";
                print_debug($sql, null, 'maplegend');
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array($layergroupId));
                while ($row2 = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $oLayer = $this->oMap->getLayerByName("{$row['layergroup_name']}.{$row2['layer_name']}");
                    $numClasses = $oLayer->numclasses;
                    for ($classIndex=0; $classIndex<$numClasses; $classIndex++) {
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
        $sqlLegend = "
            SELECT class_id, class_name, class_title, legendtype_id
            FROM ".DB_SCHEMA.".class
            INNER JOIN ".DB_SCHEMA.".layer USING(layer_id)
            WHERE layer.layergroup_id=? ORDER BY layer_order, class_order
        ";
        print_debug($sqlLegend, null, 'maplegend');
        $stmt = $this->db->prepare($sqlLegend);
        $stmt->execute(array($layergroupId));
        $rowset = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $legendArray = array();
        for ($i=0; $i < count($rowset); $i++) {
            if (!empty($this->i18n)) {
                $rowset[$i]['class_title'] = $this->i18n->translate(
                    $rowset[$i]['class_title'],
                    'class',
                    $rowset[$i]['class_id'],
                    'class_title'
                );
            }
            array_push($legendArray, $rowset[$i]);
        }
        return $legendArray;
    }
    
    private function getFeatureTypes()
    {
        $returnFeatureTypes = array(
            'theme'=>array(),
            'layergroup'=>array()
        );
        $wfsGeometryType = array(
            "point" => "PointPropertyType",
            "multipoint" => "MultiPointPropertyType",
            "linestring" => "LineStringPropertyType",
            "multilinestring" => "MultiLineStringPropertyType",
            "polygon" => "PolygonPropertyType",
            "multipolygon" => "MultiPolygonPropertyType",
            "geometry" => "GeometryPropertyType"
        );
        
        $featureTypesLinks = $this->getFeatureTypesLinks();
        
        //Restituisce le features e i range di scala
        $userGroupFilter = '';
        if (!\GCApp::getAuthenticationHandler()->isAdmin($this->projectName)) {
            $userGroup = '';
            if (!empty($this->authorizedGroups)) {
                $userGroup =  " OR groupname in(".implode(',', $this->authorizedGroups).")";
            }
            $userGroupFilter = ' (groupname IS NULL '.$userGroup.') AND ';
        }
        
        $sql = "SELECT theme.project_name, theme_name, theme_single, theme_id, layergroup_id, 
                       layergroup_name || '.' || layer_name as type_name, layer.layer_id, layer.searchable_id, 
                       layer_title, data_unique, data_geom, layer.data, layer.hide_vector_geom, catalog.catalog_id, 
                       catalog.catalog_url, private, layertype_id, classitem, labelitem, maxvectfeatures, zoom_buffer, 
                       selection_color, selection_width, field_id, field_name, filter_field_name, field_header, 
                       fieldtype_id, relation_name, relationtype_id, searchtype_id, resultype_id, datatype_id, 
                       field_filter, layer.hidden, field.editable as field_editable, field.field_format, 
                       field_groups.groupname as field_group,field_groups.editable as group_editable, layer.data_type, 
                       field.lookup_table, field.lookup_id, field.lookup_name,relation.relation_id, 
                       relation.data_field_1, relation.table_field_1
				FROM ".DB_SCHEMA.".theme 
				INNER JOIN ".DB_SCHEMA.".layergroup using (theme_id) 
				INNER JOIN ".DB_SCHEMA.".mapset_layergroup using (layergroup_id)
				INNER JOIN ".DB_SCHEMA.".layer using (layergroup_id)
				INNER JOIN ".DB_SCHEMA.".catalog using (catalog_id)
				LEFT JOIN ".DB_SCHEMA.".field using(layer_id)
				LEFT JOIN ".DB_SCHEMA.".relation using(relation_id)
				LEFT JOIN ".DB_SCHEMA.".field_groups using(field_id)
				WHERE {$userGroupFilter} layer.queryable = 1 AND mapset_layergroup.mapset_name=:mapset_name
		        ORDER BY theme_order, theme_id, layergroup_order, layergroup_id, layer_order, field_order;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->mapsetName));
        $featureTypes = array();
        $layersWith1n = array();
        $layerAuthorizations = \GCService::instance()->get('GISCLIENT_USER_LAYER');
        //print_r($layerAuthorizations);
        
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (!empty($this->i18n)) {
                $row = $this->i18n->translateRow($row, 'layer', $row['layer_id'], array(
                    'layer_title','classitem','labelitem'
                ));
                $row = $this->i18n->translateRow($row, 'field', $row['field_id'], array(
                    'field_name','field_header'
                ));
            }
    
            //DETTAGLIO AUTORIZZAZIONI
            $typeName = $row["type_name"];
            if ($row['private'] == 0) {
                if (!$this->isPublicLayerQueryable) {
                    continue;
                }
            } else {
                //echo "[$typeName] ";
                if (isset($layerAuthorizations[$row['project_name']][$typeName]) &&
                    $layerAuthorizations[$row['project_name']][$typeName]['WFS'] != 1
                ) {
                    continue;
                }
                //echo "KO";
            }
        
            $typeTitle = empty($row["layer_title"])?$typeName:$row["layer_title"];
            $index = ($row['theme_single'] == 1 ? 'theme' : 'layergroup') . '_' .
                ($row['theme_single'] == 1 ? $row['theme_id'] : $row['layergroup_id']);
            if (!isset($featureTypes[$index])) {
                $featureTypes[$index] = array();
            }
            if (!isset($featureTypes[$index][$typeName])) {
                $featureTypes[$index][$typeName] = array();
            }
            if ($row['relationtype_id'] == 2) {
                if (!isset($layersWith1n[$index])) {
                    $layersWith1n[$index] = array();
                }
                if (!isset($layersWith1n[$index][$typeName])) {
                    $layersWith1n[$index][$typeName] = array();
                }
                if (!in_array($row['relation_id'], $layersWith1n[$index][$typeName])) {
                    array_push($layersWith1n[$index][$typeName], $row);
                }
                continue;
            }
            
            $featureTypes[$index][$typeName]["typeName"] = $typeName;
            $featureTypes[$index][$typeName]["title"] = $typeTitle;
            if ($row['field_editable'] == 1 && !isset($featureTypes[$index][$typeName]['towsFeatureType'])) {
                $featureTypes[$index][$typeName]['towsFeatureType'] = $row['data'];
            }
            if (!empty($row["catalog_url"])) {
                $featureTypes[$index][$typeName]["docurl"] = $row["catalog_url"];
            }
            if (!empty($row["classitem"])) {
                $featureTypes[$index][$typeName]["classitem"] = $row["classitem"];
            }
            if (!empty($row["labelitem"])) {
                $featureTypes[$index][$typeName]["labelitem"] = $row["labelitem"];
            }
            if (!empty($row["data_type"])) {
                $featureTypes[$index][$typeName]["data_type"] = $row["data_type"];
            }
            if (!empty($row["hide_vector_geom"])) {
                $featureTypes[$index][$typeName]["hide_vector_geom"] = $row["hide_vector_geom"];
            }
            if (!empty($row["maxvectfeatures"])) {
                $featureTypes[$index][$typeName]["maxvectfeatures"] = intval($row["maxvectfeatures"]);
            }
            if (!empty($row["zoom_buffer"])) {
                $featureTypes[$index][$typeName]["zoombuffer"] = intval($row["zoom_buffer"]);
            }
            $featureTypes[$index][$typeName]['hidden'] = $row['hidden'];
            $featureTypes[$index][$typeName]['searchable'] = $row['searchable_id'];
            if (isset($featureTypesLinks[$row['layer_id']])) {
                $featureTypes[$index][$typeName]['link'] = $featureTypesLinks[$row['layer_id']];
            }
            
            $userCanEdit = false;
            if (isset($layerAuthorizations[$row['project_name']][$typeName]) && (
                $layerAuthorizations[$row['project_name']][$typeName]['WFST'] == 1 ||
                \GCApp::getAuthenticationHandler()->isAdmin($this->projectName)
            )) {
                $userCanEdit = true;
            }
            
            if (!empty($row["selection_color"]) && !empty($row["selection_width"])) {
                $color = "RGB(".str_replace(" ", ",", $row["selection_color"]).")";
                $size = intval($row["selection_width"]);
                if ($row["layertype_id"] == 1) {
                    $featureTypes[$index][$typeName]["symbolizer"] = array(
                        "Point" =>array("fillColor"=>"$color","pointRadius"=>$size)
                    );
                }
                if ($row["layertype_id"] == 2 || $row["layertype_id"] == 3) {
                    $featureTypes[$index][$typeName]["symbolizer"] = array(
                        "Line" =>array("strokeColor"=>"$color","strokeWidth"=>$size)
                    );
                }
            }
            
    
            //TODO DA VERIFICARE DA VEDERE ANCHE L'OPZIONE PER IL CAMPO EDITABILE CHE SOVRASCRIVE QUELLO DI DEFAULT
            if (($fieldName = $row["field_name"]) && (empty($row["field_group"]) || in_array($row["field_group"], $this->authorizedGroups) || \GCApp::getAuthenticationHandler()->isAdmin($this->projectName))) {//FORSE NON SERVONO TUTTI GLI ATTRIBUTI!!!
                /*
                if(!empty($row["relation_name"])){
                    $fieldName = $row["relation_name"] . "_" . NameReplace($row["field_header"]);
                }
                */
                //AGGIUNGO IL CAMPO GEOMETRIA COME PRIMO CAMPO
                if (empty($featureTypes[$index][$typeName]["properties"])) {
                    $featureTypes[$index][$typeName]["properties"] = array(
                        array(
                            "name"=>$row['data_geom'],
                            "type"=>$wfsGeometryType[$row['data_type']]
                        )
                    );
                }

                if ($row['data_unique'] == $fieldName) {
                    $isPrimaryKey = 1;
                } else {
                    $isPrimaryKey = 0;
                }
                
                $fieldSpecs = array(
                    "name"=>$fieldName,
                    "header"=>(strtoupper(CHAR_SET) != 'UTF-8')?utf8_encode($row["field_header"]):$row["field_header"],
                    "type"=>"String",//TODO
                    "fieldId"=>intval($row["field_id"]),
                    "fieldType"=>intval($row["fieldtype_id"]),
                    "dataType"=>intval($row["datatype_id"]),
                    "searchType"=>intval($row["searchtype_id"]),
                    'editable'=>$userCanEdit ? $row['field_editable'] : 0,
                    "relationType"=>intval($row["relationtype_id"]),
                    "resultType"=>intval($row["resultype_id"]),
                                        "format"=>$row["field_format"],
                    'filterFieldName'=>$row['filter_field_name'],
                    "fieldFilter"=>intval($row["field_filter"]),
                    'isPrimaryKey'=>$isPrimaryKey
                );
                
                if (!empty($row['lookup_table']) && !empty($row['lookup_id']) && !empty($row['lookup_name'])) {
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
        foreach ($layersWith1n as $index => $arr) {
            foreach ($arr as $typeName => $relations) {
                foreach ($relations as $relation) {
                    $featureTypes[$index][$typeName]['relation1n'] = $relation;
                    array_push($featureTypes[$index][$typeName]['properties'], array(
                        'name'=>'num_'.$relation['relation_id'],
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
        }
        return $featureTypes;
    }
    
    private function getFeatureTypesLinks()
    {
        $sql = "select layer_id, link_name, link_def, winw, winh ".
            " from ".DB_SCHEMA.".link inner join ".DB_SCHEMA.".layer_link using(link_id) ".
            " INNER JOIN ".DB_SCHEMA.".layer using (layer_id) ".
            " INNER JOIN ".DB_SCHEMA.".mapset_layergroup using (layergroup_id) ".
            " where mapset_layergroup.mapset_name=:mapset_name ORDER BY link_order;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(':mapset_name'=>$this->mapsetName));
        $links = array();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (strtoupper(CHAR_SET) != 'UTF-8') {
                $row["link_name"] = utf8_encode($row["link_name"]);
            }
            $links[$row['layer_id']][] = array(
                'name'=>$row["link_name"],
                'url'=>$row['link_def'],
                'width'=>$row['winw'],
                'height'=>$row['winh']
            );
        }
        return $links;
    }
    
    private function getSelgroup()
    {
        $sql = "SELECT selgroup.selgroup_id,selgroup_name,selgroup_title,layergroup_name||'.'||layer_name AS type_name 
		FROM ".DB_SCHEMA.".layer
                INNER JOIN ".DB_SCHEMA.".layergroup USING(layergroup_id)
                INNER JOIN ".DB_SCHEMA.".mapset_layergroup USING(layergroup_id) 
		INNER JOIN ".DB_SCHEMA.".selgroup_layer USING (layer_id)
                INNER JOIN ".DB_SCHEMA.".selgroup USING (selgroup_id) 
		WHERE layer.queryable=1 AND mapset_name=:mapset_name ORDER BY selgroup_order;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(':mapset_name'=>$this->mapsetName));
        $rowset = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $selgroupArray = array();
        for ($i=0; $i < count($rowset); $i++) {
            if (!empty($this->i18n)) {
                $rowset[$i]['selgroup_title'] = $this->i18n->translate(
                    $rowset[$i]['selgroup_title'],
                    'selgroup',
                    $rowset[$i]['selgroup_id'],
                    'selgroup_title'
                );
            }
            $selgroupArray[$rowset[$i]["selgroup_name"]]["title"] = $rowset[$i]["selgroup_title"];
            $selgroupArray[$rowset[$i]["selgroup_name"]]["type_name"][] = $rowset[$i]["type_name"];
        }
        $this->selgroupList = $selgroupArray;
    }

    private function getTMSExtent($tilesExtent, $tilesExtentSRID)
    {
        list($x0, $y0, $x1, $y1) = explode($this->coordSep, $tilesExtent);
        //RIPROIETTO SE SRID DIVERSO DAL MAPSET
        if ($tilesExtentSRID!=$this->mapsetSRID) {
            $p1 = "SRID=$tilesExtentSRID;POINT($x0 $y0)";
            $p2 = "SRID=$tilesExtentSRID;POINT($x1 $y1)";
            $sqlExt = "SELECT st_X(st_transform('$p1'::geometry,".$this->mapsetSRID.")) as x0, ".
                " st_Y(st_transform('$p1'::geometry,".$this->mapsetSRID.")) as y0, ".
                " st_X(st_transform('$p2'::geometry,".$this->mapsetSRID.")) as x1, ".
                " st_Y(st_transform('$p2'::geometry,".$this->mapsetSRID.")) as y1;";

            $ext = $this->db->query($sqlExt)->fetch(\PDO::FETCH_ASSOC);
            $extent = array(floatval($ext["x0"]),floatval($ext["y0"]),floatval($ext["x1"]),floatval($ext["y1"]));
        } else {
            $extent = array(floatval($x0),floatval($y0),floatval($x1),floatval($y1));
        }
            
        return $extent;
    }

    
    //Elenco delle librerie per i providers usati
    private function setMapProviders()
    {
        $jsText = "";
        foreach ($this->listProviders as $key) {
            $jsText .= "script = document.createElement('script');script.type = \"text/javascript\";";
            $jsText .= "script.src=\"".$this->mapProviders[$key]."\";";
            $jsText .= "document.getElementsByTagName('head')[0].appendChild(script);\n";
        }
        if ($jsText) {
            $jsText = "var script;".$jsText;
        }
        return $jsText;
    }
    
    
    //RESITUTISCO GIA LA MAPPA OL
    public function OLMap()
    {

        //CONFIGURAZIONE OPENLAYERS MAP
        $mapOptions = array('"allOverlays":false');
        $mapOptions[] = '"tileSize":new OpenLayers.Size('.TILE_SIZE.','.TILE_SIZE.')';
        //$mapOptions[] = '"theme":null';per non caricare i css di OL
        $mapOptions[] = '"units":"'.$this->mapOptions['units'].'"';
        $mapOptions[] = '"controls":[]';
        $mapOptions[] = '"projection":new OpenLayers.Projection("'.$this->mapOptions['projection'].'")';
        if (!empty($this->mapOptions['displayProjection'])) {
            $mapOptions[] = '"displayProjection":new OpenLayers.Projection("'.
                $this->mapOptions['displayProjection'].
            '")';
        }
        $mapOptions[] = '"projectionDescription":"'.$this->mapOptions['projectionDescription'].'"';
        $mapOptions[] = '"minResolution":'.$this->mapOptions['minResolution'];
        $mapOptions[] = '"maxResolution":'.$this->mapOptions['maxResolution'];
        $mapOptions[] = '"numZoomLevels":'.count($this->mapOptions['resolutions']);
        $mapOptions[] = '"resolutions":['.implode(',', $this->mapOptions['resolutions']).']';
        $mapOptions[] = '"maxExtent":new OpenLayers.Bounds('.implode(',', $this->mapOptions['maxExtent']).')';
        if (!empty($this->mapOptions['restrictedExtent'])) {
            $mapOptions[] = '"restrictedExtent":new OpenLayers.Bounds('.
                implode(',', $this->mapOptions['restrictedExtent'])
            .')';
        }

        $themes = $this->getLayers();
        $baseLayer = 'new OpenLayers.Layer.Image('.
            '"Base vuota",'.
            'Ext.BLANK_IMAGE_URL,'.
            'new OpenLayers.Bounds('.implode(",", $this->mapOptions["maxExtent"]).'),'.
            'new OpenLayers.Size(1,1),'.
            '{'.
                '"gc_id":"GisClient_empty_base",'.
                '"isBaseLayer":true,'.
                '"maxResolution":'.$this->mapOptions["maxResolution"].','.
                '"displayInLayerSwitcher":true,'.
                '"group":""'.
            '}'.
        ')';
        $aLayerText = array($baseLayer);
        $aLayerText = array();
        
        foreach ($themes as $layers) {
            //PER ESSERE SICURI CHE E' UN TEMA A SINGOLA IMMAGINE
            if (!empty($layers["url"])) {
                $aLayerText[] = $this->layerText($layers);
            } else {
                foreach ($layers as $layer) {
                    if (is_array($layer)) {
                        $aLayerText[] = $this->layerText($layer);
                    }
                }
            }
        }
        $loader=false;
        $jsText=$this->setMapProviders();
        if ($jsText) {
            $loader = true;
        }
        $jsText .=
            'OpenLayers.IMAGE_RELOAD_ATTEMPTS = 3;'.
            'OpenLayers.Util.onImageLoadErrorColor = "transparent";'.
            'OpenLayers.DOTS_PER_INCH = '.$this->mapOptions["dpi"].";\n";
        $mapsetOptions =
            '"name":"'.addslashes($this->mapOptions["mapset"]).'",'.
            '"title":"'.addslashes($this->mapOptions["title"]).'",'.
            '"project":"'.addslashes($this->mapOptions["project"]).'",'.
            '"projectTitle":"'.addslashes($this->mapOptions["projectTitle"]).'",'.
            '"baseLayerId":"'.$this->activeBaseLayer.'",'.
            '"projectionDescription":"'.addslashes($this->mapOptions["projectionDescription"]).'",'.
            '"minZoomLevel":'.$this->mapOptions['minZoomLevel'];
        if (isset($this->mapOptions['selgroup'])) {
            $mapsetOptions .=',"selgroup":'.json_encode($this->mapOptions['selgroup']);
        }
        $jsText .= "var GisClient = GisClient || {}; GisClient.mapset = GisClient.mapset || [];\n";
        $jsText .= 'GisClient.mapset.push({'.
            $mapsetOptions.','.
            '"map":{'.implode(',', $mapOptions).',layers:['.implode(',', $aLayerText).']}'.
        '});';
        if ($this->mapProviders[LayerGroup::GMAP_LAYER_TYPE] && $loader) {
            $jsText .= 'GisClient.loader=true;';
        }
        return $jsText;
    }

    private function layerText($aLayer)
    {
        switch ($aLayer["type"]) {
            case LayerGroup::WMS_LAYER_TYPE:
                return 'new OpenLayers.Layer.WMS("'.
                    $aLayer["title"].'","'.
                    $aLayer["url"].'",'.
                    json_encode($aLayer["parameters"]).','.
                    json_encode($aLayer["options"])
                .')';
            case LayerGroup::GMAP_LAYER_TYPE:
                if ($this->mapsetSRID == GOOGLESRID) {
                    // do not use json_encode, to avoid quoting of new OpenLayers function
                    $options = '{
                        "type":"'.$aLayer["options"]["type"].'",
                        "sphericalMercator":true,
                        "minZoomLevel":'.$aLayer["options"]["minZoomLevel"].',
                        "maxZoomLevel":'.$aLayer["options"]["maxZoomLevel"].',
                        "gc_id":"'.$aLayer["options"]["gc_id"].'",
                        "group":"'.$aLayer["options"]["group"].'"
                    }';
                    return 'new OpenLayers.Layer.Google("'.
                        $aLayer["title"].'",'.
                        $options.
                    ')';
                }
                break;
            case LayerGroup::VMAP_LAYER_TYPE:
                if ($this->mapsetSRID == GOOGLESRID) {
                    // do not use json_encode, to avoid quoting of new OpenLayers function
                    $options = '{
                        "type":'.$aLayer["options"]["type"].',
                        "sphericalMercator":true,
                        "minZoomLevel":'.$aLayer["options"]["minZoomLevel"].',
                        "maxZoomLevel":'.$aLayer["options"]["maxZoomLevel"].',
                        "gc_id":"'.$aLayer["options"]["gc_id"].'",
                        "group":"'.$aLayer["options"]["group"].'"
                    }';
                    return 'new OpenLayers.Layer.VirtualEarth("'.
                        $aLayer["title"].'",'.
                        $options.
                    ')';
                }
                break;
            case LayerGroup::BING_LAYER_TYPE:
                if ($this->mapsetSRID == GOOGLESRID) {
                    // do not use json_encode, to avoid quoting of new OpenLayers function
                    $options = '{
                        "name":"'.$aLayer["title"].'",
                        "type":"'.$aLayer["options"]["type"].'",
                        "key":"'.BINGKEY.'",
                        "sphericalMercator":true,
                        "minZoomLevel":'.$aLayer["options"]["minZoomLevel"].',
                        "maxZoomLevel":'.$aLayer["options"]["maxZoomLevel"].',
                        "gc_id":"'.$aLayer["options"]["gc_id"].'",
                        "group":"'.$aLayer["options"]["group"].'"
                    }';
                    return 'new OpenLayers.Layer.Bing('.
                        $options.
                    ')';
                }
                break;
            case LayerGroup::YMAP_LAYER_TYPE:
                if ($this->mapsetSRID == GOOGLESRID) {
                    // do not use json_encode, to avoid quoting of new OpenLayers function
                    $options = '{
                        "type":'.$aLayer["options"]["type"].',
                        "sphericalMercator":true,
                        "minZoomLevel":'.$aLayer["options"]["minZoomLevel"].',
                        "maxZoomLevel":'.$aLayer["options"]["maxZoomLevel"].',
                        "gc_id":"'.$aLayer["options"]["gc_id"].'",
                        "group":"'.$aLayer["options"]["group"].'"
                    }';
                    
                    return 'new OpenLayers.Layer.Yahoo("'.
                        $aLayer["title"].'",'.
                        $options .
                    ')';
                }
                break;
            case LayerGroup::OSM_LAYER_TYPE:
                if ($this->mapsetSRID == GOOGLESRID) {
                    return 'new OpenLayers.Layer.OSM("'.$aLayer["title"].'",null,'.json_encode($aLayer["options"]).')';
                }
                break;
            case LayerGroup::TMS_LAYER_TYPE:
                // do not use json_encode, to avoid quoting of new OpenLayers function
                $options = '{
                    "visibility": '.(!empty($aLayer["options"]["visibility"])?"true":"false").',
                    "isBaseLayer": '.(empty($aLayer["options"]["isBaseLayer"])?"false":"true").',
                    "layername": "'.$aLayer["options"]["layername"].'",
                    "buffer": '.$aLayer["options"]["buffer"].',
                    "serviceVersion": "'.$aLayer["options"]["serviceVersion"].'",
                    "owsurl": "'.$aLayer["options"]["owsurl"].'",
                    "type": "'.$aLayer["options"]["type"].'",
                    "zoomOffset": '.$aLayer["options"]["zoomOffset"].',
                    "maxExtent": new OpenLayers.Bounds('.implode(",", $aLayer["options"]["maxExtent"]).'),
                    "tileOrigin": new OpenLayers.LonLat(' .
                        $aLayer["options"]["maxExtent"][0] .',' .
                        $aLayer["options"]["maxExtent"][1] .
                    '),
                    "gc_id": "'.$aLayer["options"]["gc_id"].'",
                    "minZoomLevel": '.$aLayer["options"]["minZoomLevel"].',
                    "maxZoomLevel": '.$aLayer["options"]["maxZoomLevel"].',
                    "group": "'.$aLayer["options"]["group"].'"
                }';
                return 'new OpenLayers.Layer.TMS("'.
                    $aLayer["title"].'","'.
                    $aLayer["url"].'/","'.
                    $options.
                ')';
            case LayerGroup::WMTS_LAYER_TYPE:
                throw new \Exception("wmts layer not supported");
        }
    }
    
    private function getScaleList()
    {
        $sql = "SELECT mapset_scales FROM ".DB_SCHEMA.".mapset WHERE mapset_name=?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array($this->mapsetName));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        print_debug($sql, null, 'mapoptions');
        if ($row['mapset_scales'] !='') {
            $ret = explode(',', $row['mapset_scales']);
        } elseif (defined('SCALE')) {
            $ret = explode(',', SCALE);
        } else {
            $ret = \GCAuthor::$defaultScaleList;
        }
        return $ret;
    }
    
    private function getResolutions($scaleType)
    {
        $aRes=array();
        if (self::SCALE_TYPE_POWEROF2 == $scaleType) {
            //calculate scale from scale level and base resolution
            for ($lev=SERVICE_MIN_ZOOM_LEVEL; $lev<=SERVICE_MAX_ZOOM_LEVEL; ++$lev) {
                $aRes[] = SERVICE_MAX_RESOLUTION / pow(2, $lev);
            }
        } elseif (self::SCALE_TYPE_USER == $scaleType) {
            $scaleList = $this->getScaleList();
            foreach ($scaleList as $scaleValue) {
                $aRes[]=$scaleValue/$this->conversionFactor;
            }
        } else {
            throw new \Exception("Unknown scale type");
        }
        return $aRes;
    }
    
    private function getProjectionDescription($authName, $authSrid)
    {
        $sql = "SELECT srtext FROM spatial_ref_sys WHERE auth_name=:auth_name AND auth_srid=:auth_srid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(':auth_name'=>$authName, ':auth_srid'=>$authSrid));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $parts = explode(',', $row['srtext']);
        return trim(substr($parts[0], strpos($parts[0], '[')+1), '"');
    }
    
    private function getExtent($xCenter, $yCenter, $Resolution)
    {
        //4tiles
        $aExtent=array();
        $aExtent[0] = $xCenter - $Resolution * TILE_SIZE ;
        $aExtent[1] = $yCenter - $Resolution * TILE_SIZE ;
        $aExtent[2] = $xCenter + $Resolution * TILE_SIZE ;
        $aExtent[3] = $yCenter + $Resolution * TILE_SIZE ;
        return $aExtent;
    }
    
    private function getUsercontext($contextId)
    {
        if (!\GCApp::getAuthenticationHandler()->isAuthenticated()) {
            return array();
        }
            
        $sql = "
            SELECT context FROM ".DB_SCHEMA.".usercontext
            WHERE username=:username AND mapset_name=:mapset_name AND id=:id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
                ':username'=>\GCApp::getAuthenticationHandler()->getToken()->getUserName(),
                ':mapset_name'=>$this->mapsetName,
                ':id'=>$contextId
            ));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!empty($row)) {
            return json_decode($row["context"], true);
        } else {
            return array();
        }
    }
    
    private function getMaxExtents()
    {
        $extents = array();
        $userGroupFilter = '';
        if (!\GCApp::getAuthenticationHandler()->isAdmin($this->projectName)) {
            $userGroup = '';
            if (!empty($this->authorizedGroups)) {
                $userGroup =  " OR groupname in(".implode(',', $this->authorizedGroups).")";
            }
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
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        array_push($rows, array('layergroup_id'=>-1));
        //var_export($rows);
        $lgId = 0;
        $complete = true;
        $groupExtents = array();
        foreach ($rows as $row) {
            if ($lgId != $row['layergroup_id']) {
                if ($complete && !empty($groupExtents)) {
                    $extent = array(null, null, null, null);
                    foreach ($groupExtents as $ext) {
                        list($x1, $y1, $x2, $y2) = explode(' ', $ext);
                        if (empty($extent[0]) || $x1 < $extent[0]) {
                            $extent[0] = $x1;
                        }
                        if (empty($extent[1]) || $y1 < $extent[1]) {
                            $extent[1] = $y1;
                        }
                        if (empty($extent[2]) || $x2 > $extent[2]) {
                            $extent[2] = $x2;
                        }
                        if (empty($extent[3]) || $y2 > $extent[3]) {
                            $extent[3] = $y2;
                        }
                    }
                    $extents[$lgId] = $extent;
                }
                $complete = true;
                $groupExtents = array();
            }
            $lgId = $row['layergroup_id'];
            if (empty($row['data_extent'])) {
                $complete = false;
            } else {
                array_push($groupExtents, $row['data_extent']);
            }
        }
        //var_export($extents);
        return $extents;
    }
    
    /**
     * Return key of first value, such that $aList[$retval] <= $value
     *
     * @param array $aList array with monotone descending values
     * @param type $value
     * @return type
     */
    private function arrayIndex(array $aList, $value)
    {
        $retval=false;
        for ($i=0; $i<count($aList); $i++) {
            if ($value<=$aList[$i]) {
                $retval=$i;
                break;
            }
        }
        return $retval;
    }
}
