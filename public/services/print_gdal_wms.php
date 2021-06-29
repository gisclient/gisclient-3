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

if(!defined('PRINT_GDAL_WMS_XML')) outputError('Missing config for GDAL WMS print layers');
/*
$tableName = PRINT_VECTORS_TABLE;
$schema = defined('PRINT_VECTORS_SCHEMA') ? PRINT_VECTORS_SCHEMA : 'public';

if(!file_exists(ROOT_PATH.'config/printVectorsSLD.xml')) outputError('Missing SLD');
$sld = file_get_contents(ROOT_PATH.'config/printVectorsSLD.xml');
*/
$enableDebug = false;
$logfile = "/tmp/mapfile.print_gdal_wms.debug";
if (defined('DEBUG') && DEBUG) {
	$enableDebug = true;
	$logfile = DEBUG_DIR . "/mapfile.print_gdal_wms.debug";
}

$db = GCApp::getDB();

$geomTypes = mapImage::$vectorTypes;

if(empty($_REQUEST['SRS'])) outputError('Missing geometry srid');
//$parts = explode(':', $_REQUEST["SRS"]);
$mapSRID = "EPSG:3857"; // **** TODO : different epsg for ext layers?
if ($_REQUEST['SRS'] !== $mapSRID)
	$mapSRID .= " " . $_REQUEST['SRS'];
//$SRS_params = array();

$msVersion = substr(ms_GetVersionInt(), 0, 1);

if($_REQUEST["REQUEST"] == "GetMap" && isset($_REQUEST["SERVICE"]) && $_REQUEST["SERVICE"] == "WMS") {

	ms_ResetErrorList();
    $objRequest = ms_newOwsrequestObj();
    foreach ($_REQUEST as $k => $v) {
        if (is_string($v)) {
            $objRequest->setParameter($k, stripslashes($v));
        }
    }

	if(empty($_REQUEST['LAYERS'])) outputError('Missing layers');
	$arrExtLayers = explode(',', $_REQUEST['LAYERS']);

	$oMap=ms_newMapObj('');

        // set MAXSIZE of mapfile to the value defined in the configuration
        if (defined('MAPFILE_MAX_SIZE')) {
            $oMap->set('maxsize',  MAPFILE_MAX_SIZE);
        } else {
            $oMap->set('maxsize',  '4096');
        }

	if(defined('PROJ_LIB')) $oMap->setConfigOption("PROJ_LIB", PROJ_LIB);
	$aExtent = explode(",",$_REQUEST['BBOX']);

	$oMap->extent->setextent($aExtent[0], $aExtent[1], $aExtent[2], $aExtent[3]);
	$oMap->setSize(intval($_REQUEST['WIDTH']), intval($_REQUEST['HEIGHT']));
	$oMap->setProjection("init=".strtolower($_REQUEST['SRS']));
	if ($enableDebug) {
		$oMap->set('debug', 5);
		$oMap->setconfigoption('MS_ERRORFILE', $logfile);
	}
	$onlineUrl = printDocument::addPrefixToRelativeUrl(PUBLIC_URL.'services/print_gdal_wms.php');
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
		"wms_srs"	"$mapSRID"
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

	$layersToInclude = array();

	foreach($arrExtLayers as $extLayer) {
        array_push($layersToInclude, $extLayer);
		$oLay = ms_newLayerObj($oMap);
		$oLay->set('name', $extLayer);
		$oLay->set('group', 'gdal_wms_layers');
		$oLay->set('type', MS_LAYER_RASTER);
		$oLay->set('data', PRINT_GDAL_WMS_XML . $extLayer . '.xml');
		// **** Old Snapo - Mapserver 7 compatibility
		// **** Layer FILTERs must use MapServer expression syntax only.
		// **** use NATIVE_FILTER processing key instead.
		$oLay->setProjection("init=epsg:3857"); // **** TODO : different epsg for ext layers?
        $oLay->set('sizeunits', MS_PIXELS);
		$oLay->set('status', MS_ON);
	}

    $objRequest->setParameter('LAYERS', implode(",",$layersToInclude));

    if($enableDebug) {
        $oMap->save(DEBUG_DIR."print_gdal_wms.map");
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
