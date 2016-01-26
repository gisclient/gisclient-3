<?php

define('SKIP_INCLUDE', true);
require_once '../../config/config.php';
require_once ROOT_PATH . 'lib/GCService.php';
require_once ROOT_PATH . 'lib/i18n.php';
require_once __DIR__.'/include/OwsHandler.php';

$db = GCApp::getDB();
$gcService = GCService::instance();
$gcService->startSession(true);

if(!defined('GC_SESSION_NAME')) {
	throw new Exception('Undefined GC_SESSION_NAME in config');
}

// dirotta una richiesta PUT/DELETE GC_EDITMODE
if(($_SERVER['REQUEST_METHOD'] == 'POST' && strpos($_SERVER['REQUEST_URI'],'GC_EDITMODE=')!==false )|| $_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE'){
	include "./include/putrequest.php";
	exit(0);
}

// dirotta una richiesta POST di tipo OLWFS al cgi mapserv, per bug su loadparams
if (!empty($_REQUEST['gcRequestType']) && $_SERVER['REQUEST_METHOD'] == 'POST' && $_REQUEST['gcRequestType'] == 'OLWFS') {
	$url = MAPSERVER_URL.'map='.ROOT_PATH.'map/'.$_REQUEST['PROJECT'].'/'.$_REQUEST['MAP'].'.map';
	$postFields = file_get_contents('php://input');
	$owsHandler = new OwsHandler();
	$owsHandler->post($url, $postFields);
	exit(0);
}

if(defined('DEBUG') && DEBUG == true) {
	ini_set('display_errors', 'On');
	error_reporting(E_ALL ^ E_NOTICE);
}

$objRequest = ms_newOwsrequestObj();
$skippedParams = array();
$invertedAxisOrderSrids = array(31465,31466,31467,31468,31254,31255,31256,31257,31258,31259);

foreach ($_REQUEST as $k => $v) {
	// SLD parameter is handled later (to work also with getlegendgraphic)
	// skipping this parameter does avoid a second request made by mapserver
	// 
	// filter handling is delayed, for issues with axis ordering
	// 
	// transparent handling is delayed in order to check, if the target format
	// really supports tranparent pixels
	if (in_array(strtolower($k), array('sld', 'filter', 'transparent'))) {
		$skippedParams[strtolower($k)] = $v;
		continue;
	}
	
	if (is_string($v)) {
		$objRequest->setParameter($k, stripslashes($v));
	}
}

$parameterName = null;
$requestedFormat = strtolower($objRequest->getValueByName('format'));
// avoid that transparent is requested, when the format does not support
// transparency
if (!empty($skippedParams['transparent'])) {
	if (strtolower($skippedParams['transparent']) == 'true') {
		if ($requestedFormat == 'image/jpeg') {
			unset($skippedParams['transparent']);
		}
	}
}
if (isset($skippedParams['transparent'])) {
	if (is_string($skippedParams['transparent'])) {
		print_debug('apply transparent="' .stripslashes($skippedParams['transparent']). '"', null, 'system');
		$objRequest->setParameter('transparent', stripslashes($skippedParams['transparent']));
		unset($skippedParams['transparent']);
	}
}

// recupero lista layer dal parametro layers
$layersParameter = null;
if (strtolower($objRequest->getValueByName('service')) == 'wms') {
	$parameterName = 'LAYERS';
	$layersParameter = $objRequest->getValueByName('layers');
	if ($requestedFormat == 'kmz') {
		// KMZ is requested as KML and packaged later on
		// this is dome in this way to allow icons to be bundeled
		// in the ZIP archive
		$objRequest->setParameter('format', 'kml');
	}
} elseif (strtolower($objRequest->getValueByName('service')) == 'wfs') {
	$parameterName = 'TYPENAME';
	$layersParameter = $objRequest->getValueByName('typename');
	if (isset($skippedParams['filter'])) {
		$owsHandler = new OwsHandler();
		$prunedFilter = $owsHandler->pruneSrsFromFilter($skippedParams['filter'], $invertedAxisOrderSrids);
		$objRequest->setParameter('filter', $prunedFilter);
	}
}

// sanitize project as part of the path
$mapfileDir = ROOT_PATH.'map/';

$project = $objRequest->getvaluebyname('project');
$projectDirectory = $mapfileDir.$objRequest->getvaluebyname('project')."/";
if (strpos(realpath($projectDirectory), realpath($mapfileDir)) !== 0) {
	// if the the project directory is not a subdir of map/, something
	// bad is happening
	print_debug('project map files dir "'.$projectDirectory.'" is not in '.$mapfileDir, null, 'system');
	header('HTTP/1.0 400 Bad Request');
	echo "invalid PROJECT name";
	exit(1);
} 

// se è definita una lingua, apro il relativo mapfile
$mapfileBasename = $objRequest->getvaluebyname('map');
if($objRequest->getvaluebyname('lang')) {
	$maplang = $objRequest->getvaluebyname('map').'_'.$objRequest->getvaluebyname('lang');
	if (file_exists($projectDirectory.$maplang.'.map')) {
		$mapfileBasename = $maplang;
	} else {
		print_debug('mapfile not found for lang '.$objRequest->getvaluebyname('lang'), null, 'system');
	}
}

//Files temporanei
$showTmpMapfile = $objRequest->getvaluebyname('tmp');
if(!empty($showTmpMapfile)) {
	$mapfileBasename = "tmp.".$mapfileBasename;
}

$mapfile = $projectDirectory.$mapfileBasename.".map";
if (strpos(realpath($mapfile), realpath($projectDirectory)) !== 0) {
	// if the the map is not in the project dir, something
	// bad is happening
	print_debug('mapfile "' .realpath($mapfile). '" is not in project dir "'. realpath($projectDirectory).'"', null, 'system');
	header('HTTP/1.0 400 Bad Request');
	echo "invalid MAP name";
	exit(1);
} 
if (!is_readable($mapfile)) {
	// map file not found
	print_debug('mapfile ' .$mapfile. ' not readable', null, 'system');
	header('HTTP/1.0 400 Bad Request');
	echo "invalid MAP name";
	exit(1);
} 

$oMap = ms_newMapobj($mapfile);
print_debug('opened mapfile "' .realpath($mapfile). '": '.get_class($oMap), null, 'system');

$resolution = $objRequest->getvaluebyname('resolution');
if(!empty($resolution) && $resolution != 72) {
	$oMap->set('resolution', (int)$objRequest->getvaluebyname('resolution'));
	$oMap->set('defresolution', 96);
}

if (empty($_REQUEST['SLD'])) {
	// check if SLD is used
	$sql = "SELECT layergroup_id, sld FROM ".DB_SCHEMA.".layergroup WHERE layergroup_name=? AND sld IS NOT NULL ";
	$stmt = $db->prepare($sql);

	$i18n = new GCi18n($project, $objRequest->getvaluebyname('lang'));
	foreach (explode(',', $_REQUEST['LAYERS']) as $layergroup) {
		if (strpos($layergroup, '.') !== false) {
			list($layergroup, $layername) = explode('.', $layergroup);
		}
		$stmt->execute(array($layergroup));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if ($row !== false) {
			$sld = $i18n->translate($row['sld'], 'layergroup', $row['layergroup_id'], 'sld');

			$sldContent = OwsHandler::getSldContent($sld);
			$objRequest->setParameter('SLD_BODY', $sldContent);
			$oMap->applySLD($sldContent); // for getlegendgraphic
		}
	}
}

// visto che mapserver non riesce a scaricare il file sld, lo facciamo noi, con l'url nel parametro SLD_BODY o SLD
if(!empty($_REQUEST['SLD_BODY']) && substr($_REQUEST['SLD_BODY'],-4)=='.xml'){
	$sldContent = file_get_contents($_REQUEST['SLD_BODY']);
	if($sldContent !== false) {
		$objRequest->setParameter('SLD_BODY', $sldContent);
		$oMap->applySLD($sldContent); // for getlegendgraphic
	}
} else if(!empty($_REQUEST['SLD'])) {
	$sldContent = OwsHandler::getSldContent($_REQUEST['SLD']);
	$objRequest->setParameter('SLD_BODY', $sldContent);
	$oMap->applySLD($sldContent); // for getlegendgraphic
}

//CAMBIA EPSG CON QUELLO CON PARAMETRI DI CORREZIONE SE ESISTE 
if($objRequest->getvaluebyname('srsname')) {
	$objRequest->setParameter('srs', $objRequest->getvaluebyname('srsname'));// QUANTUM GIS PASSAVA SRSNAME... DA VERIFICARE
}
if($objRequest->getvaluebyname('srs') && $oMap->getMetaData($objRequest->getvaluebyname('srs'))) {
	$objRequest->setParameter("srs", $oMap->getMetaData($objRequest->getvaluebyname('srs')));
}
if($objRequest->getvaluebyname('srs')) {
	$srsParts = explode(':', strtolower($objRequest->getvaluebyname('srs')));
	if (count($srsParts) == 7) {
		// e.g.: 'urn:ogc:def:crs:EPSG::4306'
		$srs = $srsParts[4].':'.$srsParts[6];
	} elseif (count($srsParts) == 2) {
		// e.g.: 'EPSG:4306'
		$srs = $srsParts[0].':'.$srsParts[1];
	}
	$oMap->setProjection("+init=".strtolower($srs));
}

$url = OwsHandler::currentPageURL();
$oMap->setMetaData("ows_onlineresource",$url.'?project='.$objRequest->getvaluebyname('project')."&map=".$objRequest->getvaluebyname('map'));

if(!empty($_REQUEST['GCFILTERS'])){

	$v = explode(',',stripslashes($_REQUEST['GCFILTERS']));
	for($i=0;$i<count($v);$i++){
		list($layerName,$gcFilter)=explode('@',$v[$i]);

		$oLayer = $oMap->getLayerByName($layerName);
		if($oLayer) {
			OwsHandler::applyGCFilter($oLayer,$gcFilter);
		}
	}
}

$cacheExpireTimeout = isset($_SESSION['GC_SESSION_CACHE_EXPIRE_TIMEOUT']) ? $_SESSION['GC_SESSION_CACHE_EXPIRE_TIMEOUT'] : null;
if(!isset($_SESSION['GISCLIENT_USER_LAYER']) && !empty($layersParameter) && empty($_REQUEST['GISCLIENT_MAP'])) {
	$hasPrivateLayers = false;
	$layersArray = array();
	if(!empty($layersParameter)) {
		$layersArray = OwsHandler::getRequestedLayers($oMap, $objRequest, $layersParameter);
	}
	
	foreach($layersArray as $layer) {
		$privateLayer = $layer->getMetaData('gc_private_layer');
		if(!empty($privateLayer)) {
			$hasPrivateLayers = true;
			break;
		}
	}
	
	if ($hasPrivateLayers) {
		$user = new GCUser();
		$isAuthenticated = $user->isAuthenticated();

		// user does not have an open session, try to log in
		if (!$isAuthenticated &&
			isset($_SERVER['PHP_AUTH_USER']) &&
			isset($_SERVER['PHP_AUTH_PW'])) {
			if ($user->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
				$user->setAuthorizedLayers(array('mapset_name' => $objRequest->getValueByName('map')));
				$isAuthenticated = true;
			}
		}

		// user could not even log in, send correct headers and exit
		if (!$isAuthenticated) {
			print_debug('unauthorized access', null, 'system');
			header('WWW-Authenticate: Basic realm="Gisclient"');
			header('HTTP/1.0 401 Unauthorized');
			exit(0);
		}
	}
}

if (!empty($layersParameter)) {
	$layersArray = OwsHandler::getRequestedLayers($oMap, $objRequest, $layersParameter);
	
	// stabilisco i layer da rimuovere (nascosti, privati e con filtri obbligatori non definiti) e applico i filtri
	$layersToRemove = array();
	$layersToInclude = array();
	foreach ($layersArray as $layer) {
		//layer aggiunto x highlight
		$highlight = $objRequest->getvaluebyname('highlight');
		if (strtoupper($objRequest->getvaluebyname('request')) == 'GETMAP' && !empty($highlight)) {
			$layer->set('sizeunits', MS_PIXELS);
		}

		// layer privato
		$privateLayer = $layer->getMetaData('gc_private_layer');
		if (!empty($privateLayer)) {
			if (!OwsHandler::checkLayer($objRequest->getvaluebyname('project'), $objRequest->getvaluebyname('service'), $layer->name)) {
				array_push($layersToRemove, $layer->name); // al quale l'utente non ha accesso
				continue;
			}
		}
		$n = 0;
		// se ci sono filtri definiti per il layer, li ciclo
		while ($authFilter = $layer->getMetaData('gc_authfilter_'.$n)) {
			if (empty($authFilter)) break; // se l'ennesimo filtro +1 non è definito, interrompo il ciclo
			$required = $layer->getMetaData('gc_authfilter_'.$n.'_required');
			$n++;
			// se il filtro è obbligatorio
			if (!empty($required)) {
				if (!isset($_SESSION['AUTHFILTERS'][$authFilter])) { // e se l'utente non ha quel filtro definito
					array_push($layersToRemove, $layer->name); // rimuovo il layer
					break;
				}
			}
			// se ci sono filtri definiti
			if (isset($_SESSION['AUTHFILTERS'][$authFilter])) {
				$filter = $layer->getFilterString();
				$filter = trim($filter, '"');
				if (!empty($filter)) { // se esiste già un filtro lo aggiungo
					$filter = $filter.' AND '.$_SESSION['AUTHFILTERS'][$authFilter];
				} else {
					$filter = $_SESSION['AUTHFILTERS'][$authFilter];
				}
				// aggiorno il FILTER del layer
				$layer->setFilter($filter);
			}
		}
		
		if (!empty($_SESSION['GC_LAYER_FILTERS'])) {
			if (!empty($_SESSION['GC_LAYER_FILTERS'][$layer->name])) {
				$filter = $layer->getFilterString();
				$filter = trim($filter, '"');
				if (!empty($filter)) {
					$filter = $filter.' AND ('.$_SESSION['GC_LAYER_FILTERS'][$layer->name].')';
				} else {
					$filter = $_SESSION['GC_LAYER_FILTERS'][$layer->name];
				}
				$layer->setFilter($filter);
			}
		}
		
		if (!in_array($layer->name, $layersToRemove)) {
			$filter = $layer->getFilterString();

			if ($filter) {
				$filter = trim($filter, '"');
				$p1 = strpos($layer->data, '(');
				$p2 = strpos($layer->data, ')', $p1);
				$part1 = substr($layer->data, 0, $p1);
				$part2 = substr($layer->data, $p1+1, $p2-$p1-1);
				$part3 = substr($layer->data, $p2+1);

				$part2 = "SELECT * FROM ({$part2}) AS foo2 WHERE ({$filter})";
				$sql = "{$part1}({$part2}){$part3}";

				$layer->data = $sql;
				$layer->set('data', $sql);
				$layer->setFilter('');
			}

			array_push($layersToInclude, $layer->name);
		}
	}
	
	// rimuovo i layer che l'utente non può visualizzare
	foreach ($layersToRemove as $layerName) {
		$layer = $oMap->getLayerByName($layerName);
		$oMap->removeLayer($layer->index);
	}
	// aggiorno il parametro layers con i soli layers che l'utente può vedere
	$objRequest->setParameter($parameterName, implode(",", $layersToInclude));
}
session_write_close();

// Cache part 1
$owsCacheTTL = defined('OWS_CACHE_TTL') ? OWS_CACHE_TTL : 0;
$owsCacheTTLOpen = defined('OWS_CACHE_TTL_OPEN') ? OWS_CACHE_TTL_OPEN : 0;
if ((isset($_REQUEST['REQUEST']) && strtolower($_REQUEST['REQUEST']) == 'getmap')
	|| (isset($_REQUEST['request']) && strtolower($_REQUEST['request']) == 'getmap')) {
	
	if ($owsCacheTTL > 0 && isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]) < time() - $owsCacheTTL) {
		header('HTTP/1.1 304 Not Modified');
		die(); // Dont' return image
	}
}

if (strtoupper($objRequest->getvaluebyname('request')) == 'GETLEGENDGRAPHIC') {
	include './include/wmsGetLegendGraphic.php';
}

//SE NON SONO IN CGI CARICO I PARAMETRI
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) != 'cgi') {
	if (strtolower($objRequest->getvaluebyname('service')) != "wfs" && $objRequest->type == -1) {
		$oMap->loadowsparameters($objRequest);
	}
}

/* Enable output buffer */
ms_ioinstallstdouttobuffer();

/* Execute request */
$oMap->owsdispatch($objRequest);
$contenttype = ms_iostripstdoutbuffercontenttype();

/* Send response with appropriate header */ 
if (substr($contenttype, 0, 6) == 'image/') {

	header('Content-Type: '. $contenttype);
		// Prevent apache to zip imnage
		apache_setenv('no-gzip', 1);
		ini_set('zlib.output_compression', 0);

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

	// Cache part 2
	if (!$hasDynamicLayer && $cacheExpireTimeout > 0 && $cacheExpireTimeout > time()) {
		$cacheTime = gmdate("D, d M Y H:i:s", time() + $owsCacheTTLOpen) . " GMT";
		$serverTime = gmdate("D, d M Y H:i:s", time()) . " GMT";
		header("Cache-Control: public, max-age={$owsCacheTTLOpen}, pre-check={$owsCacheTTLOpen} ");
		header("Pragma: public");
		header("Date: {$serverTime}");
		header("Cache-Control: max-age={$owsCacheTTLOpen}");
		header("Last-Modified: {$serverTime}");
		header("Expires: {$cacheTime}");
	} else if ($owsCacheTTL > 0) {
		// OL FIX: Prevent multiple request for the same layer. Fixed setting cache to 60 sec
		$cacheTime = gmdate("D, d M Y H:i:s", time() + $owsCacheTTL) . " GMT";
		$serverTime = gmdate("D, d M Y H:i:s", time()) . " GMT";
		header("Cache-Control: public, max-age={$owsCacheTTL}, pre-check={$owsCacheTTL} ");
		header("Pragma: public");
		header("Date: {$serverTime}");
		header("Cache-Control: max-age={$owsCacheTTL}");
		header("Last-Modified: {$serverTime}");
		header("Expires: {$cacheTime}");
	}
	ms_iogetStdoutBufferBytes(); 
} elseif (strstr($contenttype, 'google-earth')) {
	
	if ($requestedFormat == 'kmz' &&
		strtolower($objRequest->getValueByName('format')) == 'kml') {
		header("Content-Type: application/vnd.google-earth.kmz");
		$kmlString = ms_iogetstdoutbufferstring();
		$owsHandler = new OwsHandler();
		$kmzString = $owsHandler->assembleKmz($kmlString);
		header('Content-Disposition: attachment; filename="layerdata.kmz"');
		echo $kmzString;
	} else {
		header("Content-Type: $contenttype");
		header('Content-Disposition: attachment; filename="layerdata.kml"');
		ms_iogetStdoutBufferBytes(); 
	}   
} else { 
	header("Content-Type: application/xml"); 
	ms_iogetStdoutBufferBytes(); 
}

ms_ioresethandlers();
