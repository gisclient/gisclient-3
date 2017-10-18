<?php

define('SKIP_INCLUDE', true);
require_once __DIR__ . '/../../bootstrap.php';
require_once ROOT_PATH . 'lib/GCService.php';
require_once ROOT_PATH . 'lib/i18n.php';
require_once __DIR__.'/include/OwsHandler.php';

use Symfony\Component\HttpFoundation\Request;
use GisClient\MapServer\MsMapObjFactory;
use GisClient\Author\Security\Guard\BasicAuthAuthenticator;

$gcService = GCService::instance();
$gcService->startSession(true);

if (!defined('GC_SESSION_NAME')) {
    throw new Exception('Undefined GC_SESSION_NAME in config');
}

// dirotta una richiesta PUT/DELETE GC_EDITMODE
if (($_SERVER['REQUEST_METHOD'] == 'POST' && strpos($_SERVER['REQUEST_URI'], 'GC_EDITMODE=')!==false )|| $_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE') {
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

if (defined('DEBUG') && DEBUG == true) {
    ini_set('display_errors', 'On');
    error_reporting(E_ALL ^ E_NOTICE);
}

$objRequest = ms_newOwsrequestObj();
$skippedParams = array();
$invertedAxisOrderSrids = array(2178,31465,31466,31467,31468,31254,31255,31256,31257,31258,31259);

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

$project = $objRequest->getvaluebyname('project');
$map = $objRequest->getvaluebyname('map');
$useTemporaryMapfile = !empty($objRequest->getvaluebyname('tmp'));
$lang = $objRequest->getvaluebyname('lang');

$mapObjFactory = new MsMapObjFactory();
$oMap = $mapObjFactory->create($project, $map, $useTemporaryMapfile, $lang);

$resolution = $objRequest->getvaluebyname('resolution');
if (!empty($resolution) && $resolution != 72) {
    $oMap->set('resolution', (int)$objRequest->getvaluebyname('resolution'));
    $oMap->set('defresolution', 96);
}


// APPLY SLD FOR WMS REQUEST
$requestService = strtolower($objRequest->getValueByName('service'));
$requestRequest = strtolower($objRequest->getValueByName('request'));
if (strtolower($objRequest->getValueByName('service')) == 'wms' &&
    in_array($requestRequest, array('getlegendgraphic', 'getmap'))) {
    $db = GCApp::getDB();
    $i18n = new GCi18n($project, $objRequest->getvaluebyname('lang'));
    OwsHandler::applyWmsSld($db, $i18n, $oMap, $objRequest);
}

//CAMBIA EPSG CON QUELLO CON PARAMETRI DI CORREZIONE SE ESISTE 
if ($objRequest->getvaluebyname('srsname')) {
    $objRequest->setParameter('srs', $objRequest->getvaluebyname('srsname'));// QUANTUM GIS PASSAVA SRSNAME... DA VERIFICARE
}
if ($objRequest->getvaluebyname('srs') && $oMap->getMetaData($objRequest->getvaluebyname('srs'))) {
    $objRequest->setParameter("srs", $oMap->getMetaData($objRequest->getvaluebyname('srs')));
}
if ($objRequest->getvaluebyname('srs')) {
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
$oMap->setMetaData("ows_onlineresource", $url.'?project='.$objRequest->getvaluebyname('project')."&map=".$objRequest->getvaluebyname('map'));

if (!empty($_REQUEST['GCFILTERS'])) {
    $v = explode(',', stripslashes($_REQUEST['GCFILTERS']));
    for ($i=0; $i<count($v); $i++) {
        list($layerName, $gcFilter)=explode('@', $v[$i]);

        $oLayer = $oMap->getLayerByName($layerName);
        if ($oLayer) {
            OwsHandler::applyGCFilter($oLayer, $gcFilter);
        }
    }
}

if (!$gcService->has('GISCLIENT_USER_LAYER') && !empty($layersParameter) && empty($_REQUEST['GISCLIENT_MAP'])) {
    $hasPrivateLayers = false;
    $layersArray = array();
    if (!empty($layersParameter)) {
        $layersArray = OwsHandler::getRequestedLayers($oMap, $objRequest, $layersParameter);
    }
    
    foreach ($layersArray as $layer) {
        $privateLayer = $layer->getMetaData('gc_private_layer');
        if (!empty($privateLayer)) {
            $hasPrivateLayers = true;
            break;
        }
    }
    
    if ($hasPrivateLayers) {
        $authHandler = \GCApp::getAuthenticationHandler(null, new BasicAuthAuthenticator());
        $isAuthenticated = $authHandler->isAuthenticated();

        // user does not have an open session, try to log in
        if (!$isAuthenticated) {
            $authHandler->login(Request::createFromGlobals());
            $isAuthenticated = $authHandler->isAuthenticated();
        }

        // user could not even log in, send correct headers and exit
        if (!$isAuthenticated) {
            print_debug('unauthorized access', null, 'system');
            header('WWW-Authenticate: Basic realm="Gisclient"');
            header('HTTP/1.0 401 Unauthorized');
            echo "<h1>Authorization required</h1>";
            exit(0);
        }
        
        // get layers to populate session with GISCLIENT_USER_LAYER
        GCApp::getLayerAuthorizationChecker()->getLayers(array(
            'mapset_name' => $objRequest->getValueByName('map')
        ));
    }
}

if (!empty($layersParameter)) {
    $layersArray = OwsHandler::getRequestedLayers($oMap, $objRequest, $layersParameter);
    
    // stabilisco i layer da rimuovere (nascosti, privati e con filtri obbligatori non definiti) e applico i filtri
    $layersToRemove = array();
    $layersToInclude = array();
    foreach ($layersArray as $layer) {
        //espressione per le label (0 le rimuove)
        $labelrequires = $objRequest->getvaluebyname('labelrequires');
        if (isset($labelrequires)) {
            $layer->set('labelrequires', $labelrequires);
        }

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
            
            if (!\GCApp::getAuthenticationHandler()->isAuthenticated()) {
                array_push($layersToRemove, $layer->name); // al quale l'utente non ha accesso
                continue;
            }
        }
        $n = 0;

        if (null !== ($layerAuthorizations = $gcService->get('GISCLIENT_USER_LAYER'))) {
            if (!empty($layerAuthorizations[$layer->name])) {
                $filter = $layer->getFilterString();
                $filter = trim($filter, '"');
                if (!empty($filter)) {
                    $filter = $filter.' AND ('.$layerAuthorizations[$layer->name].')';
                } else {
                    $filter = $layerAuthorizations[$layer->name];
                }
                $layer->setFilter($filter);
            }
        }

        
        if (!in_array($layer->name, $layersToRemove)) {
            $filter = $layer->getFilterString();

            if ($filter) {
                $filter = trim($filter, '"');
                $p1 = strpos($layer->data, '(');
                $p2 = strrpos($layer->data, ')', $p1);
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
//die;
// Cache part 1
$owsCacheTTL = defined('OWS_CACHE_TTL') ? OWS_CACHE_TTL : 0;
$owsCacheTTLOpen = defined('OWS_CACHE_TTL_OPEN') ? OWS_CACHE_TTL_OPEN : 0;
if ((isset($_REQUEST['REQUEST']) && strtolower($_REQUEST['REQUEST']) == 'getmap')
    || (isset($_REQUEST['request']) && strtolower($_REQUEST['request']) == 'getmap')) {
    if ($owsCacheTTL > 0 && isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && time() - strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]) < $owsCacheTTL) {
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

header('Access-Control-Allow-Origin: *');
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
            foreach ($layersToInclude as $currentLayer) {
                if (in_array($currentLayer, $dynamicLayers)) {
                    $hasDynamicLayer = true;
                    break;
                }
            }
        }
    }

    // Cache part 2
    if ($owsCacheTTL > 0) {
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
} elseif ($objRequest->getValueByName('outputformat') == 'GEOJSON') {
    ms_iostripstdoutbuffercontentheaders();
    header("Content-Type: application/json");
    
    ms_iogetStdoutBufferBytes();
} else {
    header("Content-Type: application/xml");
    
    ms_iogetStdoutBufferBytes();
}

ms_ioresethandlers();
