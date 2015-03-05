<?php
//define('DEBUG', true);
define('SKIP_INCLUDE', true);
require_once '../../config/config.php';



$user = new GCUser();
$user->setAuthorizedLayers(array('project_name'=>'geoweb_genova'));
$userLayers = $user->getMapLayers(array('project_name'=>'geoweb_genova'));

$ret = array();
$layers = array();

foreach($userLayers as $theme) {
	foreach($theme as $layergroup) {
		if(isset($layergroup["name"])){
			$layers[$layergroup["name"]] = array("tile"=>true,"map"=>true,"featureinfo"=>true,"legendgraphic"=>true);
			//$layers[$layergroup["name"]] = array("tile"=>true,"map"=>true,"featureinfo"=>true,"legendgraphic"=>true,"limited_to"=>array("geometry"=>"qualcosa","srs"=>"EPSG:3003"));

		}
		else{
			for($i=0;$i<count($layergroup);$i++)
				$layers[$layergroup[$i]["name"]] = array("tile"=>true,"map"=>true,"featureinfo"=>true,"legendgraphic"=>true);
				//$layers[$layergroup[$i]["name"]] = array("tile"=>true,"map"=>true,"featureinfo"=>true,"legendgraphic"=>true,"limited_to"=>array("geometry"=>"qualcosa","srs"=>"EPSG:3003"));

		}

	}
}

$ret["authorized"] = "partial";

//TODO SE HO SETTAO UN LIMITE PER TUTTLE LE RICHIESTE DELL'UTENTE LO METTO QUI
//dove lo setto il limite utente????
//if(true) $ret["limit_to"] = array("geometry"=>"qualcosaaltro ma dove???","srs"=>"EPSG:3003");
$ret["layers"]=$layers;






header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header ("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header ("Pragma: no-cache"); // HTTP/1.0
header("Content-Type: application/json; Charset=UTF-8");

echo json_encode(array("wms"=>$ret));




		die();
        //$extents = $this->_getMaxExtents();
		//print_array($userLayers);


die();

$user = new GCUser();
$authLayers = $user->getAuthorizedLayers(array('project_name'=>$_REQUEST['project']));
print_array($authLayers);
print_array($_SESSION);
die();











// dirotta una richiesta PUT/DELETE GC_EDITMODE
if(($_SERVER['REQUEST_METHOD'] == 'POST' && strpos($_SERVER['REQUEST_URI'],'GC_EDITMODE=')!==false )|| $_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE'){
	include ("./include/putrequest.php");
	die();
}

// dirotta una richiesta POST di tipo OLWFS al cgi mapserv, per bug su loadparams
if (!empty($_REQUEST['gcRequestType']) && $_SERVER['REQUEST_METHOD'] == 'POST' && $_REQUEST['gcRequestType'] == 'OLWFS') {
	$url = MAPSERVER_URL.'map='.ROOT_PATH.'map/'.$_REQUEST['PROJECT'].'/'.$_REQUEST['MAP'].'.map';
	
	$fileContent = file_get_contents('php://input');
	file_put_contents('/tmp/postrequest.xml', $fileContent);
	
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, true);
	
	curl_setopt($curl, CURLOPT_POSTFIELDS, array('file' => '@/tmp/postrequest.xml'));
	$return = curl_exec($curl);
	if(!$return) var_export(curl_error($curl));
	exit;
}

if(defined('DEBUG') && DEBUG == true) {
	ini_set('display_errors', 'On');
	error_reporting(E_ALL ^ E_NOTICE);
}

$objRequest = ms_newOwsrequestObj();
foreach ($_REQUEST as $k => $v) if (is_string($v)) $objRequest->setParameter($k, stripslashes($v));

//OGGETTO MAP MAPSCRIPT
$directory = ROOT_PATH."map/".$objRequest->getvaluebyname('project')."/";

// se è definita una lingua, apro il relativo mapfile
$mapfile = $objRequest->getvaluebyname('map');
if($objRequest->getvaluebyname('lang') && file_exists($directory.$objRequest->getvaluebyname('map').'_'.$objRequest->getvaluebyname('lang').'.map')) {
	$mapfile = $objRequest->getvaluebyname('map').'_'.$objRequest->getvaluebyname('lang');
}
//Files temporanei
$showTmpMapfile = $objRequest->getvaluebyname('tmp');
if(!empty($showTmpMapfile)) {
	$mapfile = "tmp.".$mapfile;
}

$oMap = ms_newMapobj($directory.$mapfile.".map");

$resolution = $objRequest->getvaluebyname('resolution');
if(!empty($resolution) && $resolution != 72) {
	$oMap->set('resolution', (int)$objRequest->getvaluebyname('resolution'));
	$oMap->set('defresolution', 96);
}

// visto che mapserver non riesce a scaricare il file sld, lo facciamo noi, con l'url nel parametro SLD_BODY o SLD
if(!empty($_REQUEST['SLD_BODY']) && substr($_REQUEST['SLD_BODY'],-4)=='.xml'){
	$sldContent = file_get_contents($_REQUEST['SLD_BODY']);
	if($sldContent !== false) {
        $objRequest->setParameter('SLD_BODY', $sldContent);
        $oMap->applySLD($sldContent); // for getlegendgraphic
    }
} else if(!empty($_REQUEST['SLD'])) {
    $ch = curl_init($_REQUEST['SLD']);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
    curl_setopt($ch ,CURLOPT_TIMEOUT, 10); 
    $sldContent = curl_exec($ch);
    curl_close($ch);
    
	if($sldContent !== false) {
        $objRequest->setParameter('SLD_BODY', $sldContent);
        $oMap->applySLD($sldContent); // for getlegendgraphic
    }
}


//CAMBIA EPSG CON QUELLO CON PARAMETRI DI CORREZIONE SE ESISTE 
if($objRequest->getvaluebyname('srsname')) $objRequest->setParameter('srs', $objRequest->getvaluebyname('srsname'));// QUANTUM GIS PASSAVA SRSNAME... DA VERIFICARE
if($objRequest->getvaluebyname('srs') && $oMap->getMetaData($objRequest->getvaluebyname('srs'))) $objRequest->setParameter("srs", $oMap->getMetaData($objRequest->getvaluebyname('srs')));
if($objRequest->getvaluebyname('srs')) $oMap->setProjection($projString="+init=".strtolower($objRequest->getvaluebyname('srs')));


$url = currentPageURL();
$oMap->setMetaData("ows_onlineresource",$url.'?project='.$objRequest->getvaluebyname('project')."&map=".$objRequest->getvaluebyname('map'));


if(!empty($_REQUEST['GCFILTERS'])){

	$v = explode(',',stripslashes($_REQUEST['GCFILTERS']));
	for($i=0;$i<count($v);$i++){
		list($layerName,$gcFilter)=explode('@',$v[$i]);

		@$oLayer = $oMap->getLayerByName($layerName);
		if($oLayer) applyGCFilter($oLayer,$gcFilter);
		//print_debug($oLayer->getFilterString());
	}

}

/* ------ stabilisco i layer da usare ------ */

// recupero lista layer dal parametro layers
$layersParameter = null;
$parameterName = null;
if($objRequest->getValueByName('service') == 'WMS') {
	$parameterName = 'LAYERS';
	$layersParameter = $objRequest->getValueByName('layers');
} else if($objRequest->getValueByName('service') == 'WFS') {
	$parameterName = 'TYPENAME';
	$layersParameter = $objRequest->getValueByName('typename');
}

// avvio la sessione
if(!isset($_SESSION)) {
    if(defined('GC_SESSION_NAME')) session_name(GC_SESSION_NAME);
	if(isset($_REQUEST['GC_SESSION_ID']) && !empty($_REQUEST['GC_SESSION_ID'])) {
		session_id($_REQUEST['GC_SESSION_ID']);
	}
	session_start();
}

$cacheExpireTimeout = isset($_SESSION['GC_SESSION_CACHE_EXPIRE_TIMEOUT']) ? $_SESSION['GC_SESSION_CACHE_EXPIRE_TIMEOUT'] : null;
if(!isset($_SESSION['GISCLIENT_USER_LAYER']) && !empty($layersParameter) && empty($_REQUEST['GISCLIENT_MAP'])) {
	$hasPrivateLayers = false;
	if(!empty($layersParameter)) {
		$layersArray = getRequestedLayers($layersParameter);
	}
	foreach($layersArray as $layer) {
		$privateLayer = $layer->getMetaData('gc_private_layer');
		if(!empty($privateLayer)) {
			$hasPrivateLayers = true;
			break;
		}
	}
	if($hasPrivateLayers) {
		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			header('WWW-Authenticate: Basic realm="Gisclient"');
			header('HTTP/1.0 401 Unauthorized');
		} else {
            $user = new GCUser();
            if($user->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
                $user->setAuthorizedLayers(array('mapset_name'=>$objRequest->getValueByName('map')));
            }
		}
	}
}

if(!empty($layersParameter)) {
	$layersArray = getRequestedLayers($layersParameter);
	
	// stabilisco i layer da rimuovere (nascosti, privati e con filtri obbligatori non definiti) e applico i filtri
	$layersToRemove = array();
	$layersToInclude = array();
	foreach($layersArray as $layer) {
	
		//layer aggiunto x highlight
		$highlight = $objRequest->getvaluebyname('highlight');
		if(strtoupper($objRequest->getvaluebyname('request')) == 'GETMAP' && !empty($highlight)) $layer->set('sizeunits',MS_PIXELS);
	
		// layer nascosto
		$hideLayer = $layer->getMetaData("gc_hide_layer");
		if(strtoupper($objRequest->getvaluebyname('request')) == 'GETMAP' && !empty($hideLayer)) {
			array_push($layersToRemove, $layer->name);
			continue;
		}
		// layer privato
		$privateLayer = $layer->getMetaData('gc_private_layer');
		if(!empty($privateLayer)) {
			if(!checkLayer($objRequest->getvaluebyname('project'), $objRequest->getvaluebyname('service'), $layer->name)) {
				array_push($layersToRemove, $layer->name); // al quale l'utente non ha accesso
				continue;
			}
		}
		
		if(!empty($_SESSION['GC_LAYER_FILTERS'])) {
            if(!empty($_SESSION['GC_LAYER_FILTERS'][$layer->name])) {
                $filter = $layer->getFilterString();
                $filter = trim($filter, '"');
                if(!empty($filter)) {
                    $filter = $filter.' AND ('.$_SESSION['GC_LAYER_FILTERS'][$layer->name].')';
                } else {
                    $filter = $_SESSION['GC_LAYER_FILTERS'][$layer->name];
                }
                $layer->setFilter($filter);
            }
        }
        
        if($objRequest->getValueByName('format') == 'kml') {
            $layer->set('labelmaxscaledenom', 999999999999);
            $layer->set('labelminscaledenom', 1);
            for($i = 0; $i < $layer->numclasses; $i++) {
                $class = $layer->getClass($i);
                for($j = 0; $j < $class->numstyles; $j++) {
                    $style = $class->getStyle($j);
                    $style->set('symbol', null);
                }
            }
        }
		
		if(!in_array($layer->name, $layersToRemove)) array_push($layersToInclude, $layer->name);
	}
	
	// rimuovo i layer che l'utente non può visualizzare
	foreach($layersToRemove as $layerName) {
		$layer = $oMap->getLayerByName($layerName);
		$oMap->removeLayer($layer->index);
	}
	// aggiorno il parametro layers con i soli layers che l'utente può vedere 
	$objRequest->setParameter($parameterName, implode(",",$layersToInclude));		
}
session_write_close();

// Cache part 1
$owsCacheTTL = defined('OWS_CACHE_TTL') ? OWS_CACHE_TTL : 0;
$owsCacheTTLOpen = defined('OWS_CACHE_TTL_OPEN') ? OWS_CACHE_TTL_OPEN : 0;
if ((isset($_REQUEST['REQUEST']) && 
     strtolower($_REQUEST['REQUEST']) == 'getmap') || 
	(isset($_REQUEST['request']) && 
     strtolower($_REQUEST['request']) == 'getmap')) {
	
	if ($owsCacheTTL > 0 && isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]) < time() - $owsCacheTTL) {
		header('HTTP/1.1 304 Not Modified');
		die(); // Dont' return image
	}
}

if(strtoupper($objRequest->getvaluebyname('request')) == 'GETLEGENDGRAPHIC') {
	//include './include/wmsGetLegendGraphic.php';
}

//SE NON SONO IN CGI CARICO I PARAMETRI
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) != 'cgi') {
	if ($objRequest->getvaluebyname('service') != "WFS" && $objRequest->type == -1) $oMap->loadowsparameters($objRequest);
}


/* Enable output buffer */ 
ms_ioinstallstdouttobuffer(); 

/* Eexecute request */ 
$oMap->owsdispatch($objRequest);


$contenttype = ms_iostripstdoutbuffercontenttype(); 
$ctt = explode("/",$contenttype); 

/* Send response with appropriate header */ 
if ($ctt[0] == 'image') {

	$hasDynamicLayer = false;
	if (defined('DYNAMIC_LAYERS')) {
		$dynamicLayers = explode(',', DYNAMIC_LAYERS);
		if (isset($layersToInclude)) {
			foreach($layersToInclude as $currentLayer) {
				if (in_array($currentLayer, $dynamicLayers)) {
					$hasDynamicLayer = true;
					break;
				}
			}
		}
    }

	header('Content-type: image/'. $ctt[1]); 
    
    // Cache part 2
	if (!$hasDynamicLayer && $cacheExpireTimeout > 0 && $cacheExpireTimeout > time()) {
		$cacheTime = gmdate("D, d M Y H:i:s", time() + $owsCacheTTLOpen) . " GMT";
		$serverTime = gmdate("D, d M Y H:i:s", time()) . " GMT";
		header("Cache-Control: public, max-age={$owsCacheTTLOpen}, pre-check={$owsCacheTTLOpen}	");
		header("Pragma: public");
        header("Date: {$serverTime}");
		header("Cache-Control: max-age={$owsCacheTTLOpen}");
		header("Last-Modified: {$serverTime}");
		header("Expires: {$cacheTime}");
	} else if ($owsCacheTTL > 0) {
		// OL FIX: Prevent multiple request for the same layer. Fixed setting cache to 60 sec
		$cacheTime = gmdate("D, d M Y H:i:s", time() + $owsCacheTTL) . " GMT";
		$serverTime = gmdate("D, d M Y H:i:s", time()) . " GMT";
		header("Cache-Control: public, max-age={$owsCacheTTL}, pre-check={$owsCacheTTL}	");
		header("Pragma: public");
        header("Date: {$serverTime}");
		header("Cache-Control: max-age={$owsCacheTTL}");
		header("Last-Modified: {$serverTime}");
		header("Expires: {$cacheTime}");
	}
    
	ms_iogetStdoutBufferBytes(); 
} else if($ctt[1] == 'vnd.google-earth.kml+xml') {
    header("content-type: application/vnd.google-earth.kml+xml");
    header('Content-Disposition: attachment; filename="export.kml"');
    ms_iogetStdoutBufferBytes(); 
} else {
    //vnd.google-earth.kml+xml
	header("Content-Type: application/xml"); 
	ms_iogetStdoutBufferBytes(); 
} 

ms_ioresethandlers();




function currentPageURL() {
	$pageURL = 'http';
	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') $pageURL .= 's';
	$pageURL .= '://';
	if($_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["PHP_SELF"];
	else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"];
	return $pageURL;
}

function applyGCFilter(&$oLayer,$layerFilter){
	if($oLayer->getFilterString()) $layerFilter = str_replace("\"","",$oLayer->getFilterString())." AND " .$layerFilter;
	$oLayer->setFilter($layerFilter);
}

function checkLayer($project, $service, $layerName){
	$check = false;
	if(!empty($_SESSION['GISCLIENT_USER_LAYER']) && !empty($_SESSION['GISCLIENT_USER_LAYER'][$project][$layerName])) {
		$layerAuth = $_SESSION['GISCLIENT_USER_LAYER'][$project][$layerName];
		if((strtoupper($service) == 'WMS' && ($layerAuth['WMS']==1)) || (strtoupper($service) == 'WFS' && ($layerAuth['WFS']==1 ))) $check = true;
	}
	return $check;
}

function getRequestedLayers($layersParameter) {
	global $oMap, $objRequest;
	$layersArray = array();
	$layerNames = explode(',', $layersParameter);
	// ciclo i layers e costruisco un array di singoli layers
	foreach($layerNames as $name) {
		$layerIndexes = $oMap->getLayersIndexByGroup($name);
        if(!$layerIndexes && count($layerNames) == 1 && $name == $objRequest->getvaluebyname('map')) {
            $layerIndexes = array_keys($oMap->getAllLayerNames());
        }
		// è un layergroup (mapserver 6 restituisce sempre un array)
		if(is_array($layerIndexes) && count($layerIndexes)>0) {
			foreach($layerIndexes as $index) {
				array_push($layersArray, $oMap->getLayer($index));
			}
		// è un singolo layer
		} else {
			array_push($layersArray, $oMap->getLayerByName($name));
		}
	}
	return $layersArray;
}