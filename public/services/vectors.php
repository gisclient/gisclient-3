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
$customFields = implode(', ', array_keys(mapImage::$vectorFields));
$customFieldsDecl = str_replace('=', ' ', http_build_query(mapImage::$vectorFields, '', ', '));
$customFieldsVal = ':' . implode(', :', array_keys(mapImage::$vectorFields));
// **** Dash line styles available on client
$olDashStyles = array('dot'=>'1 3','dash'=>'3 3','dashdot'=>'3 3 1 3','longdash'=>'5 3','longdashdot'=>'5 3 1 3');

if(empty($_REQUEST['SRS'])) outputError('Missing geometry srid');
//$parts = explode(':', $_REQUEST["SRS"]);
$mapSRID = "EPSG:" . PRINT_VECTORS_SRID;
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

	if (defined('PRINT_VECTORS_USE_SLD') && PRINT_VECTORS_USE_SLD == FALSE) {
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
		$nId1 = ms_newSymbolobj($oMap, "TRIANGLE");
	    $oSymbol1 = $oMap->getsymbolobjectbyid($nId1);
	    $oSymbol1->set("filled", MS_TRUE);
	    $oSymbol1->set("type", MS_SYMBOL_VECTOR);
	    $oSymbol1->set("inmapfile", MS_TRUE);
	    $aPoints1 = array(0,1,.5,0,1,1,0,1);
	    $oSymbol1->setpoints($aPoints1);
	}

	$layersToInclude = array();

	foreach($types as $type) {
        array_push($layersToInclude, 'printvectors_'.$type['db_type']);
		$oLay = ms_newLayerObj($oMap);
		$oLay->set('name', 'printvectors_'.$type['db_type']);
		$oLay->set('group', 'printvectors');
		$oLay->set('type', $type['ms_type']);
		$oLay->setConnectionType(MS_POSTGIS);
		$oLay->set('connection', "user=".DB_USER." password=".DB_PWD." dbname=".DB_NAME." host=".DB_HOST." port=".DB_PORT);
        $data = "the_geom from (select gid, print_id, $customFields, ".$type['db_field']." as the_geom from $schema.$tableName) as foo using unique gid using srid=".PRINT_VECTORS_SRID;
		$oLay->set('data', $data);
		// **** Old Snapo - Mapserver 7 compatibility
		// **** Layer FILTERs must use MapServer expression syntax only.
		// **** use NATIVE_FILTER processing key instead.
		if ($msVersion >= 7) {
			$oLay->setProcessing("NATIVE_FILTER=(print_id=".$_REQUEST['LAYERS'].")");
		}
		else {
			$oLay->setFilter("print_id=".$_REQUEST['LAYERS']);
		}
		$oLay->setProjection("init=epsg:".PRINT_VECTORS_SRID);
		$vectorOpacity = 50;
		if (defined('PRINT_VECTORS_OPACITY')) {
			$vectorOpacity = PRINT_VECTORS_OPACITY;
		}
        $oLay->set('opacity', $vectorOpacity);
        $oLay->set('sizeunits', MS_PIXELS);
		$oLay->set('symbolscaledenom', 500);
		$oLay->set('status', MS_ON);
		$oLay->set('labelitem', 'label');

		if (defined('PRINT_VECTORS_USE_SLD') && PRINT_VECTORS_USE_SLD == FALSE) {
			// **** Set class
			$oClass = new classObj($oLay);
			$oClass->set('name', 'PRINTVECTORS_CLASS_' . $type['db_type']);
			// **** Set label
			$oLabel = new labelObj();
			$oLabel->setBinding(MS_LABEL_BINDING_COLOR, 'fontcolor');
			$oLabel->updateFromString('LABEL SIZE([fontsize]/2) END');
			//$oLabel->setBinding(MS_LABEL_BINDING_FONT, 'fontfamily');
			$oLabel->setBinding(MS_LABEL_BINDING_ANGLE, 'angle');
			//$oLabel->color->setHex('#000000');
			$oLabel->set('position', MS_UC);
			//$oLabel->set('size', '15');
			//$oLabel->set('maxsize', '20');
			//$oLabel->set('minsize', '10');
			$oClass->addLabel($oLabel);
			// **** Set style
			switch ($type['ms_type']) {
				case MS_LAYER_POINT:
					$oClass->setExpression("(length('[graphicname]') > 0)");
					$oStyle = ms_newStyleObj($oClass);
					$oStyle->setBinding(MS_STYLE_BINDING_SIZE, 'pointradius');
					//$oStyle->updateFromString('STYLE SIZE ( pointradius]/2 ) END');
					$oStyle->setBinding(MS_STYLE_BINDING_COLOR, 'fillcolor');
					$oStyle->setBinding(MS_STYLE_BINDING_OUTLINECOLOR, 'strokecolor');
					$oStyle->setBinding(MS_STYLE_BINDING_ANGLE, 'rotation');
					$oStyle->updateFromString('STYLE OPACITY [strokeopacity] END');
					$oStyle->updateFromString('STYLE SYMBOL [graphicname] END');
					// **** Set symbol class
					$oClassS = new classObj($oLay);
					$oClassS->set('name', 'PRINTVECTORS_CLASS_SYMBOL_' . $type['db_type']);
					$oLabelS = new labelObj();
					$oLabelS->setBinding(MS_LABEL_BINDING_COLOR, 'fontcolor');
					$oLabelS->updateFromString('LABEL SIZE([fontsize]/2) END');
					$oLabelS->setBinding(MS_LABEL_BINDING_ANGLE, 'angle');
					$oLabelS->set('position', MS_UC);
					$oClassS->addLabel($oLabelS);
					$oClassS->setExpression("(length('[externalgraphic]') > 0)");
					$oStyleS = new styleObj($oClassS);
					$oStyleS->set('size', '12');
					$oStyleS->setBinding(MS_STYLE_BINDING_COLOR, 'fillcolor');
					$oStyleS->setBinding(MS_STYLE_BINDING_OUTLINECOLOR, 'strokecolor');
					$oStyleS->setBinding(MS_STYLE_BINDING_ANGLE, 'rotation');
					$oStyleS->updateFromString('STYLE OPACITY [strokeopacity] END');
					$oStyleS->updateFromString('STYLE SYMBOL [externalgraphic] END');
					$oLay->moveClassUp(1);
					break;
				case MS_LAYER_LINE:
					$oStyle = ms_newStyleObj($oClass);
					$oStyle->setBinding(MS_STYLE_BINDING_WIDTH, 'strokewidth');
					$oStyle->setBinding(MS_STYLE_BINDING_COLOR, 'strokecolor');
					$oStyle->updateFromString('STYLE OPACITY [strokeopacity] END');
					$styleIdx = 0;
					foreach($olDashStyles as $dashStyleName =>$dashStylePattern) {
						$styleIdx++;
						$oClassS = new classObj($oLay);
						$oClassS->set('name', 'PRINTVECTORS_CLASS_' . $dashStyleName . '_' . $type['db_type']);
						$oLabelS = new labelObj();
						$oLabelS->setBinding(MS_LABEL_BINDING_COLOR, 'fontcolor');
						$oLabelS->updateFromString('LABEL SIZE([fontsize]/2) END');
						$oLabelS->setBinding(MS_LABEL_BINDING_ANGLE, 'angle');
						$oLabelS->set('position', MS_UC);
						$oClassS->addLabel($oLabelS);
						$oClassS->setExpression("('[strokedashstyle]' = '$dashStyleName')");
						$oStyleS = new styleObj($oClassS);
						$oStyleS->setBinding(MS_STYLE_BINDING_WIDTH, 'strokewidth');
						$oStyleS->setBinding(MS_STYLE_BINDING_COLOR, 'strokecolor');
						$oStyleS->updateFromString('STYLE OPACITY [strokeopacity] END');
						$oStyleS->updateFromString("STYLE PATTERN $dashStylePattern END");
						$oLay->moveClassUp($styleIdx);
					}
					break;
				case MS_LAYER_POLYGON:
					$oStyle = ms_newStyleObj($oClass);
					$oStyle->setBinding(MS_STYLE_BINDING_COLOR, 'fillcolor');
					$oStyle->updateFromString('STYLE OPACITY [fillopacity] END');
					$oStyleIn = ms_newStyleObj($oClass);
					$oStyleIn->setBinding(MS_STYLE_BINDING_OUTLINECOLOR, 'strokecolor');
					$oStyleIn->updateFromString('STYLE OPACITY [strokeopacity] END');
					break;
			}
		}
		else {
        	$oLay->applySLD($sld);
		}
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
