<?php
require_once "../../config/config.php";
require_once 'include/mapImage.php';
require_once ROOT_PATH . 'lib/GCService.php';

$gcService = GCService::instance();
$gcService->startSession();

function outputError($msg) {
	header("Status: 500 Internal Server Error");
	die(json_encode(array('error'=>$msg)));
}


if(!isset($_REQUEST['REQUEST']) || $_REQUEST['REQUEST'] != 'GetMap') die('Invalid request');

if(!defined('PRINT_VECTORS_TABLE') || !defined('PRINT_VECTORS_SRID')) outputError('Missing config print vectors values');
$tableName = PRINT_VECTORS_TABLE;
$schema = defined('PRINT_VECTORS_SCHEMA') ? PRINT_VECTORS_SCHEMA : 'public';

if(!file_exists(ROOT_PATH.'config/printVectorsSLD.xml')) outputError('Missing SLD');
$sld = file_get_contents(ROOT_PATH.'config/printVectorsSLD.xml');

$enableDebug = false;
$logfile = "/tmp/mapfile.vector.debug";
if (defined('DEBUG') && DEBUG) {
	$enableDebug = true;
	$logfile = DEBUG_DIR . "/mapfile.vector.debug";
}

$db = GCApp::getDB();

$geomTypes = mapImage::$vectorTypes;

if(empty($_REQUEST['SRS'])) outputError('Missing geometry srid');
$parts = explode(':', $_REQUEST["SRS"]);
$mapSRID = $parts[1];
$SRS_params = array();


if($_REQUEST["REQUEST"] == "GetMap" && isset($_REQUEST["SERVICE"]) && $_REQUEST["SERVICE"] == "WMS") {
	
	ms_ResetErrorList();
    $objRequest = ms_newOwsrequestObj();
    foreach ($_REQUEST as $k => $v) {
        if (is_string($v)) {
            $objRequest->setParameter($k, stripslashes($v));
        }
    }

	if(empty($_REQUEST['LAYERS'])) outputError('Missing layers');
	$geomFields = array();
	foreach($geomTypes as $type) array_push($geomFields, $type['db_field']);
	$sql = "select ".implode(',', $geomFields)." from $schema.$tableName".
		" where print_id = ?";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($_REQUEST['LAYERS']));
	$redline = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$types = array();
    foreach($redline as $row) {
        foreach($geomTypes as $index => $type) {
            if(!empty($row[$type['db_field']]) && !in_array($geomTypes[$index], $types)) {
                array_push($types, $geomTypes[$index]);
            }
        }        
    }

	if(empty($types)) { // empty geom, che facciamo?
	}

	$oMap=ms_newMapObj('');
	if(defined('PROJ_LIB')) $oMap->setConfigOption("PROJ_LIB", PROJ_LIB);
	$aExtent = explode(",",$_REQUEST['BBOX']);

	$oMap->extent->setextent($aExtent[0], $aExtent[1], $aExtent[2], $aExtent[3]);
	$oMap->setSize(intval($_REQUEST['WIDTH']), intval($_REQUEST['HEIGHT']));	
	$oMap->setProjection("init=".strtolower($_REQUEST['SRS']));
	if ($enableDebug) { 
		$oMap->set('debug', 5);
		$oMap->setconfigoption('MS_ERRORFILE', $logfile);
	}
	$onlineUrl = printDocument::addPrefixToRelativeUrl(PUBLIC_URL.'services/vectors.php');
	$mapfileBase = <<<EOMAP
WEB
	METADATA
        # for mapserver 6.0
        "ows_enable_request" "*"
		"ows_title"	"r3-signs"
		"wfs_encoding"	"UTF-8"
		"wms_encoding"	"UTF-8"
    	"wms_onlineresource" "$onlineUrl"
    	"wfs_onlineresource" "$onlineUrl"
		"wms_feature_info_mime_type"	"text/html"
		"wfs_namespace_prefix"	"feature"
		"wms_srs"	"EPSG:32632"
	END
	IMAGEPATH "/tmp/"
	IMAGEURL "/tmp/"	
END

EOMAP;
    $oMap->web->updateFromString($mapfileBase);
 	$oMap->outputformat->set('name','png');
	$oMap->outputformat->set('mimetype','image/png');
	$oMap->outputformat->set('driver','AGG/PNG');
	$oMap->outputformat->set('extension','png');
	$oMap->outputformat->set('imagemode',MS_IMAGEMODE_RGBA);
	$oMap->outputformat->set('transparent',MS_ON);

    $oMap->selectOutputFormat('png');
	
	$oMap->setFontSet(ROOT_PATH.'fonts/fonts.list');		
    
    $nId = ms_newsymbolobj($oMap, "CIRCLE");
    $oSymbol = $oMap->getsymbolobjectbyid($nId);
    $oSymbol->set("filled", MS_TRUE);
    $oSymbol->set("type", MS_SYMBOL_ELLIPSE);
    $oSymbol->set("sizex", 1);
    $oSymbol->set("sizey", 1);
    $oSymbol->set("inmapfile", MS_TRUE);

    $aPoints[0] = 1;
    $aPoints[1] = 1;
    $oSymbol->setpoints($aPoints);

	$layersToInclude = array();
    
	foreach($types as $type) {
        array_push($layersToInclude, 'printvectors_'.$type['db_type']);
		$oLay = ms_newLayerObj($oMap);
		$oLay->set('name', 'printvectors_'.$type['db_type']);
		$oLay->set('group', 'printvectors');
		$oLay->set('type', $type['ms_type']);
		$oLay->setConnectionType(MS_POSTGIS);
		$oLay->set('connection', "user=".DB_USER." password=".DB_PWD." dbname=".DB_NAME." host=".DB_HOST." port=".DB_PORT);
        $data = "the_geom from (select gid, print_id, ".$type['db_field']." as the_geom from $schema.$tableName) as foo using unique gid using srid=".PRINT_VECTORS_SRID;
		$oLay->set('data', $data);
		$oLay->setFilter("print_id=".$_REQUEST['LAYERS']);
		$oLay->setProjection("init=epsg:".PRINT_VECTORS_SRID);
        $oLay->set('opacity', 50);
        $oLay->set('sizeunits', MS_PIXELS);
		$oLay->set('status', MS_ON);
        $oLay->applySLD($sld);
	}
    
    $objRequest->setParameter('LAYERS', implode(",",$layersToInclude));
    
    if($enableDebug) {
        $oMap->save(DEBUG_DIR."printvectors.map");
    }
    
    ms_ioinstallstdouttobuffer(); 
    
    $oMap->owsdispatch($objRequest);
    $contenttype = ms_iostripstdoutbuffercontenttype(); 
    header('Content-type: image/png'); 
    ms_iogetStdoutBufferBytes(); 
    ms_ioresethandlers();
	
	// check if something bad happenend
	$error = ms_GetErrorObj();
	$errMsg = '';
	while($error && $error->code != MS_NOERR) {
		$errMsg .= sprintf("Error in %s: %s<br>\n", $error->routine, $error->message);
		$error = $error->next();
	}
	if ($errMsg != '') {
		outputError($errMsg);
	}
}
