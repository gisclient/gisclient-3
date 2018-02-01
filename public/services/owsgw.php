<?php
//define('DEBUG', true);
define('SKIP_INCLUDE', true);
require_once __DIR__ . '/../../bootstrap.php';

use Symfony\Component\HttpFoundation\Request;
use GisClient\Author\Utils\OwsHandler;
use GisClient\Author\Security\Guard\BasicAuthAuthenticator;
use GisClient\Author\Security\Guard\TrustedAuthenticator;

$gcService = GCService::instance();
$gcService->startSession();

// dirotta una richiesta PUT/DELETE GC_EDITMODE
if(($_SERVER['REQUEST_METHOD'] == 'POST' && strpos($_SERVER['REQUEST_URI'],'GC_EDITMODE=')!==false )|| $_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE'){
	include ("./include/putrequest.php");
	die();
}

// dirotta una richiesta POST di tipo OLWFS al cgi mapserv, per bug su loadparams
// ADESSO NON SERVE PIU SECONDO ME!
if (!empty($_REQUEST['gcRequestType']) && $_SERVER['REQUEST_METHOD'] == 'POST' && $_REQUEST['gcRequestType'] == 'OLWFS') {
	$url = MAPSERVER_URL.'map='.ROOT_PATH.'map/'.$_REQUEST['PROJECT'].'/'.$_REQUEST['MAP'].'.map';
	
	$fileContent = file_get_contents('php://input');
	file_put_contents('/tmp/postrequest.xml', $fileContent);
	
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	
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

$project = $objRequest->getvaluebyname('project');
$map = $objRequest->getvaluebyname('map');
$useTemporaryMapfile = !empty($objRequest->getvaluebyname('tmp'));
$lang = $objRequest->getvaluebyname('lang');

$mapObjFactory = \GCApp::getMsMapObjFactory();
$oMap = $mapObjFactory->create($project, $map, $useTemporaryMapfile, $lang);

$resolution = $objRequest->getvaluebyname('resolution');
if(!empty($resolution) && $resolution != 72) {
	$oMap->set('resolution', (int)$objRequest->getvaluebyname('resolution'));
	$oMap->set('defresolution', 96);
}


$projectName = $oMap->getMetaData("project_name");
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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
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

if(!empty($_REQUEST['GCFILTERS'])){

	$v = explode(',',stripslashes($_REQUEST['GCFILTERS']));
	for($i=0;$i<count($v);$i++){
		list($layerName,$gcFilter)=explode('@',$v[$i]);

		@$oLayer = $oMap->getLayerByName($layerName);
		if($oLayer) {
                    OwsHandler::applyGCFilter($oLayer,$gcFilter);
                }
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

if(!$gcService->has('GISCLIENT_USER_LAYER') && !empty($layersParameter) && empty($_REQUEST['GISCLIENT_MAP'])) {
	$hasPrivateLayers = false;
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
	if($hasPrivateLayers) {
            if(!empty($_REQUEST['PRINTSERVICE'])) {
                $guard = new TrustedAuthenticator('printservice', 'printservice$');
                $authHandler = \GCApp::getAuthenticationHandler(null, $guard);
                $authHandler->login(Request::createFromGlobals());
            } else {
                if (!isset($_SERVER['PHP_AUTH_USER'])) {
                        header('WWW-Authenticate: Basic realm="Gisclient"');
                        header('HTTP/1.0 401 Unauthorized');
                } else {
                    $guard = new BasicAuthAuthenticator();
                    $authHandler = \GCApp::getAuthenticationHandler(null, $guard);
                    $authHandler->login(Request::createFromGlobals());
                }

            }

            if($authHandler->isAuthenticated()) {
                if(defined('PROJECT_MAPFILE') && PROJECT_MAPFILE) {
                    // get layers to populate session with GISCLIENT_USER_LAYER
                    GCApp::getLayerAuthorizationChecker()->getLayers(array(
                        'project_name' => $objRequest->getValueByName('map')
                    ));
                } else {
                    // get layers to populate session with GISCLIENT_USER_LAYER
                    GCApp::getLayerAuthorizationChecker()->getLayers(array(
                        'mapset_name' => $objRequest->getValueByName('map')
                    ));
                }
            }
	}
}

// close the session, because all relevant data are already writte into it
$gcService->getSession()->save();

if(!empty($layersParameter)) {
	$layersArray = OwsHandler::getRequestedLayers($oMap, $objRequest, $layersParameter);
	
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
			//array_push($layersToRemove, $layer->name);
			//continue;
		}
		// layer privato
		$privateLayer = $layer->getMetaData('gc_private_layer');
		if(!empty($privateLayer)) {
			if(!checkLayer($projectName, $objRequest->getvaluebyname('service'), $layer->name)) {
				array_push($layersToRemove, $layer->name); // al quale l'utente non ha accesso
				continue;
			}
		}
		
		if (null !== ($layerAuthorizations = $gcService->get('GISCLIENT_USER_LAYER'))) {
            if(!empty($layerAuthorizations[$layer->name])) {
                $filter = $layer->getFilterString();
                $filter = trim($filter, '"');
                if(!empty($filter)) {
                    $filter = $filter.' AND ('.$layerAuthorizations[$layer->name].')';
                } else {
                    $filter = $layerAuthorizations[$layer->name];
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
//TODO VERIFICARE PERCHÈ SENZA @ DA ERRORE
@$oMap->owsdispatch($objRequest);



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
	if ($owsCacheTTL > 0) {
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



function checkLayer($project, $service, $layerName){
	$check = false;
        if (null !== ($layerAuthorizations = \GCService::instance()->get('GISCLIENT_USER_LAYER'))) {
            if (!empty($layerAuthorizations[$project][$layerName])) {
		$layerAuth = $layerAuthorizations[$project][$layerName];
		if((strtoupper($service) == 'WMS' && ($layerAuth['WMS']==1)) || (strtoupper($service) == 'WFS' && ($layerAuth['WFS']==1 ))) $check = true;
            }
	}
	return $check;
}

