<?php
require_once "../../config/config.php";
require_once 'include/mapImage.php';
require_once ROOT_PATH . 'lib/GCService.php';

$gcService = GCService::instance();
$gcService->startSession(true);

function outputError($msg) {
	header("Status: 500 Internal Server Error");
	die(json_encode(array('error'=>$msg)));
}


if(!isset($_REQUEST['REQUEST']) || $_REQUEST['REQUEST'] != 'GetMap') die('Invalid request');

if(!defined('PRINT_VECTORS_TABLE') || !defined('PRINT_VECTORS_SRID')) outputError('Missing config print vectors values');

$tableName = PRINT_VECTORS_TABLE;
$schema = defined('PRINT_VECTORS_SCHEMA') ? PRINT_VECTORS_SCHEMA : 'public';
if (!defined('PRINT_VECTORS_HOST')) define('PRINT_VECTORS_HOST',DB_HOST);
if (!defined('PRINT_VECTORS_PORT')) define('PRINT_VECTORS_PORT',DB_PORT);
if (!defined('PRINT_VECTORS_DB')) define('PRINT_VECTORS_DB',DB_NAME);
if (!defined('PRINT_VECTORS_USER')) define('PRINT_VECTORS_USER',DB_USER);
if (!defined('PRINT_VECTORS_PWD')) define('PRINT_VECTORS_PWD',DB_PWD);

if(!file_exists(ROOT_PATH.'config/printVectorsSLD.xml')) outputError('Missing SLD');
$sld = file_get_contents(ROOT_PATH.'config/printVectorsSLD.xml');

$enableDebug = false;
$logfile = "/tmp/mapfile.vector.debug";
if (defined('DEBUG') && DEBUG) {
	$enableDebug = true;
	$logfile = DEBUG_DIR . "/mapfile.vector.debug";
}

$connStr = 'user=' . PRINT_VECTORS_USER .
		' password=' . PRINT_VECTORS_PWD .
		' dbname=' . PRINT_VECTORS_DB .
		' host=' . PRINT_VECTORS_HOST .
		' port=' . PRINT_VECTORS_PORT .
		'/' . $schema;
$dataDB = new GCDataDB($connStr);
$db = $dataDB->db;

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

$msVersion = substr(mapscript::msGetVersionInt(), 0, 1);

if($_REQUEST["REQUEST"] == "GetMap" && isset($_REQUEST["SERVICE"]) && $_REQUEST["SERVICE"] == "WMS") {

	mapscript::msResetErrorList();
    $objRequest = new OWSRequest();
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

	if ( defined('PRINT_VECTORS_MAP_TEMPLATE')) {
		$oMap = @new gc_mapObj(ROOT_PATH.'config/'.PRINT_VECTORS_MAP_TEMPLATE);

	        // set MAXSIZE of mapfile to the value defined in the configuration
	        if (defined('MAPFILE_MAX_SIZE')) {
	            $oMap->set('maxsize',  MAPFILE_MAX_SIZE);
	        } else {
	            $oMap->set('maxsize',  '4096');
	        }

		if(defined('PROJ_LIB')) $oMap->setConfigOption("PROJ_LIB", PROJ_LIB);
		$aExtent = explode(",",$_REQUEST['BBOX']);

		$oMap->extent_setextent($aExtent[0], $aExtent[1], $aExtent[2], $aExtent[3]);
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

		$oMap->setFontSet(ROOT_PATH.'fonts/fonts.list');

		$layersToInclude = array();
		$geomFields = array();
		$mapLayers = $oMap->getAllLayerNames();
		foreach($types as $type) {
	        array_push($layersToInclude, 'printvectors_'.$type['db_type']);
			$geomFields['printvectors_'.$type['db_type']] = $type['db_field'];
		}
		foreach ($mapLayers as $idx => $layerName) {
			$oLay = $oMap->getLayerByName($layerName);
			if (!in_array($layerName, $layersToInclude)) {
				$oMap->removeLayer($oLay->index);
				continue;
			}
			$oLay->set('connection', "user=".PRINT_VECTORS_USER." password=".PRINT_VECTORS_PWD." dbname=".PRINT_VECTORS_DB." host=".PRINT_VECTORS_HOST." port=".PRINT_VECTORS_PORT);
	        $data = "the_geom from (select gid, print_id, $customFields, ".$geomFields[$layerName]." as the_geom from $schema.$tableName) as foo using unique gid using srid=".PRINT_VECTORS_SRID;
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

			if (isset($_SESSION['VECTOR_PRINT_OPTIONS'])) {
				foreach($_SESSION['VECTOR_PRINT_OPTIONS'] as $opt => $opt_val) {
					$oLay->set($opt, $opt_val);
				}
			}

			if (defined('PRINT_VECTORS_USE_SLD') && PRINT_VECTORS_USE_SLD == TRUE) {
	        	$oLay->applySLD($sld);
			}
		}
	}
	else {
		$oMap=new gc_mapObj();

	        // set MAXSIZE of mapfile to the value defined in the configuration
	        if (defined('MAPFILE_MAX_SIZE')) {
	            $oMap->set('maxsize',  MAPFILE_MAX_SIZE);
	        } else {
	            $oMap->set('maxsize',  '4096');
	        }

		if(defined('PROJ_LIB')) $oMap->setConfigOption("PROJ_LIB", PROJ_LIB);
		$aExtent = explode(",",$_REQUEST['BBOX']);

		$oMap->extent_setextent($aExtent[0], $aExtent[1], $aExtent[2], $aExtent[3]);
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
	 	$oMap->outputformat_set('name','png');
		$oMap->outputformat_set('mimetype','image/png');
		$oMap->outputformat_set('driver','AGG/PNG');
		$oMap->outputformat_set('extension','png');
		$oMap->outputformat_set('imagemode',MS_IMAGEMODE_RGBA);
		$oMap->outputformat_set('transparent',MS_ON);

	    $oMap->selectOutputFormat('png');

		$oMap->setFontSet(ROOT_PATH.'fonts/fonts.list');

		if (defined('PRINT_VECTORS_USE_SLD') && PRINT_VECTORS_USE_SLD == FALSE) {
			if (method_exists('symbolObj', 'set')) {
				$nId = ms_newSymbolObj($oMap, "CIRCLE");
			    $oSymbol = $oMap->getsymbolobjectbyid($nId);
			    $oSymbol->set("filled", MS_TRUE);
			    $oSymbol->set("type", MS_SYMBOL_ELLIPSE);
			    $oSymbol->set("sizex", 1);
			    $oSymbol->set("sizey", 1);
			    $oSymbol->set("inmapfile", MS_TRUE);
			    $aPoints[0] = 1;
			    $aPoints[1] = 1;
			    $oSymbol->setpoints($aPoints);
				$nId1 = ms_newSymbolObj($oMap, "TRIANGLE");
			    $oSymbol1 = $oMap->getsymbolobjectbyid($nId1);
			    $oSymbol1->set("filled", MS_TRUE);
			    $oSymbol1->set("type", MS_SYMBOL_VECTOR);
			    $oSymbol1->set("inmapfile", MS_TRUE);
			    $aPoints1 = array(0,1,.5,0,1,1,0,1);
			    $oSymbol1->setpoints($aPoints1);
			}
			else {
				$oSymbol = new symbolObj("CIRCLE");
				$oSymbol->filled = MS_TRUE;
			    $oSymbol->type = MS_SYMBOL_ELLIPSE;
			    $oSymbol->sizex = 1;
			    $oSymbol->sizey = 1;
			    $oSymbol->inmapfile = MS_TRUE;
				$point = new pointObj(1,1);
				$line = new lineObj();
				$line->add($point);
				$oSymbol->setPoints($line);
				$oMap->symbolset->appendSymbol($oSymbol);
				$oSymbol_t = new symbolObj("TRIANGLE");
				$oSymbol_t->filled = MS_TRUE;
			    $oSymbol_t->type = MS_SYMBOL_VECTOR;
			    $oSymbol_t->inmapfile = MS_TRUE;
				$line_t = new lineObj();
				$point1 = new pointObj(0,1);
				$line_t->add($point1);
				$point2 = new pointObj(0.5,0);
				$line_t->add($point2);
				$point3 = new pointObj(1,1);
				$line_t->add($point3);
				$point4 = new pointObj(0,1);
				$line_t->add($point4);
				$oSymbol_t->setPoints($line_t);
				$oMap->symbolset->appendSymbol($oSymbol_t);
			}
		}

		$layersToInclude = array();

		foreach($types as $type) {
	        array_push($layersToInclude, 'printvectors_'.$type['db_type']);
			$oLay = new gc_layerObj($oMap);
			$oLay->set('name', 'printvectors_'.$type['db_type']);
			$oLay->set('group', 'printvectors');
			$oLay->set('type', $type['ms_type']);
			$oLay->setConnectionType(MS_POSTGIS, null);
			$oLay->set('connection', "user=".PRINT_VECTORS_USER." password=".PRINT_VECTORS_PWD." dbname=".PRINT_VECTORS_DB." host=".PRINT_VECTORS_HOST." port=".PRINT_VECTORS_PORT);
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
			$oLay->set('status', MS_ON);
			$oLay->set('labelitem', 'label');

			if (isset($_SESSION['VECTOR_PRINT_OPTIONS'])) {
				foreach($_SESSION['VECTOR_PRINT_OPTIONS'] as $opt => $opt_val) {
					$oLay->set($opt, $opt_val);
				}
			}

			if (defined('PRINT_VECTORS_USE_SLD') && PRINT_VECTORS_USE_SLD == FALSE) {
				// **** Set class
				$oClass = new gc_classObj($oLay);
				$oClass->set('name', 'PRINTVECTORS_CLASS_' . $type['db_type']);
				// **** Set label
				$oLabel = new gc_labelObj();
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
						$oLabel->updateFromString('LABEL MAXSCALEDENOM 250 END');
						$oClass->setExpression("(length('[graphicname]') > 0)");
						$oStyle = new gc_styleObj($oClass);
						$oStyle->setBinding(MS_STYLE_BINDING_SIZE, 'pointradius');
						//$oStyle->updateFromString('STYLE SIZE ( pointradius]/2 ) END');
						$oStyle->setBinding(MS_STYLE_BINDING_COLOR, 'fillcolor');
						$oStyle->setBinding(MS_STYLE_BINDING_OUTLINECOLOR, 'strokecolor');
						$oStyle->setBinding(MS_STYLE_BINDING_ANGLE, 'rotation');
						$oStyle->updateFromString('STYLE OPACITY [strokeopacity] END');
						$oStyle->updateFromString('STYLE SYMBOL [graphicname] END');

						$oLabel1 = new gc_labelObj();
						$oLabel1->setBinding(MS_LABEL_BINDING_COLOR, 'fontcolor');
						$oLabel1->updateFromString('LABEL SIZE([fontsize]) END');
						$oLabel1->setBinding(MS_LABEL_BINDING_ANGLE, 'angle');
						$oLabel1->set('position', MS_UC);
						$oLabel1->updateFromString('LABEL MAXSCALEDENOM 500 END');
						$oLabel1->updateFromString('LABEL MINSCALEDENOM 251 END');
						$oClass->addLabel($oLabel1);

						$oLabel2 = new gc_labelObj();
						$oLabel2->setBinding(MS_LABEL_BINDING_COLOR, 'fontcolor');
						$oLabel2->updateFromString('LABEL SIZE([fontsize]*2) END');
						$oLabel2->setBinding(MS_LABEL_BINDING_ANGLE, 'angle');
						$oLabel2->set('position', MS_UC);
						$oLabel2->updateFromString('LABEL MAXSCALEDENOM 1000 END');
						$oLabel2->updateFromString('LABEL MINSCALEDENOM 501 END');
						$oClass->addLabel($oLabel2);

						$oLabel4 = new gc_labelObj();
						$oLabel4->setBinding(MS_LABEL_BINDING_COLOR, 'fontcolor');
						$oLabel4->updateFromString('LABEL SIZE([fontsize]*4) END');
						$oLabel4->setBinding(MS_LABEL_BINDING_ANGLE, 'angle');
						$oLabel4->set('position', MS_UC);
						$oLabel4->updateFromString('LABEL MAXSCALEDENOM 2000 END');
						$oLabel4->updateFromString('LABEL MINSCALEDENOM 1001 END');
						$oClass->addLabel($oLabel4);

						$oLabel8 = new gc_labelObj();
						$oLabel8->setBinding(MS_LABEL_BINDING_COLOR, 'fontcolor');
						$oLabel8->updateFromString('LABEL SIZE([fontsize]*8) END');
						$oLabel8->setBinding(MS_LABEL_BINDING_ANGLE, 'angle');
						$oLabel8->set('position', MS_UC);
						$oLabel8->updateFromString('LABEL MINSCALEDENOM 2001 END');
						$oClass->addLabel($oLabel8);

						// **** Set symbol class
						$oClassS = new gc_classObj($oLay);
						$oClassS->set('name', 'PRINTVECTORS_CLASS_SYMBOL_' . $type['db_type']);
						$oLabelS = new gc_labelObj();
						$oLabelS->setBinding(MS_LABEL_BINDING_COLOR, 'fontcolor');
						$oLabelS->updateFromString('LABEL SIZE([fontsize]/2) END');
						$oLabelS->setBinding(MS_LABEL_BINDING_ANGLE, 'angle');
						$oLabelS->set('position', MS_UC);
						$oClassS->addLabel($oLabelS);
						$oClassS->setExpression("(length('[externalgraphic]') > 0)");
						$oStyleS = new gc_styleObj($oClassS);
						$oStyleS->set('size', '12');
						$oStyleS->setBinding(MS_STYLE_BINDING_COLOR, 'fillcolor');
						$oStyleS->setBinding(MS_STYLE_BINDING_OUTLINECOLOR, 'strokecolor');
						$oStyleS->setBinding(MS_STYLE_BINDING_ANGLE, 'rotation');
						$oStyleS->updateFromString('STYLE OPACITY [strokeopacity] END');
						$oStyleS->updateFromString('STYLE SYMBOL [externalgraphic] END');
						$oLay->moveClassUp(1);
						break;
					case MS_LAYER_LINE:
						$oStyle = new gc_styleObj($oClass);
						$oStyle->setBinding(MS_STYLE_BINDING_WIDTH, 'strokewidth');
						$oStyle->setBinding(MS_STYLE_BINDING_COLOR, 'strokecolor');
						$oStyle->updateFromString('STYLE OPACITY [strokeopacity] END');
						$oLabel->updateFromString('LABEL SIZE ([fontsize]) END');
                        $oLabel->updateFromString('LABEL OFFSET 0 6 END');
						$styleIdx = 0;
						foreach($olDashStyles as $dashStyleName =>$dashStylePattern) {
							$styleIdx++;
							$oClassS = new gc_classObj($oLay);
							$oClassS->set('name', 'PRINTVECTORS_CLASS_' . $dashStyleName . '_' . $type['db_type']);
							$oLabelS = new gc_labelObj();
							$oLabelS->setBinding(MS_LABEL_BINDING_COLOR, 'fontcolor');
							$oLabelS->updateFromString('LABEL SIZE([fontsize]) END');
							$oLabelS->setBinding(MS_LABEL_BINDING_ANGLE, 'angle');
							$oLabelS->set('position', MS_UC);
							$oClassS->addLabel($oLabelS);
							$oClassS->setExpression("('[strokedashstyle]' = '$dashStyleName')");
							$oStyleS = new gc_styleObj($oClassS);
							$oStyleS->setBinding(MS_STYLE_BINDING_WIDTH, 'strokewidth');
							$oStyleS->setBinding(MS_STYLE_BINDING_COLOR, 'strokecolor');
							$oStyleS->updateFromString('STYLE OPACITY [strokeopacity] END');
							// **** Disabled due to bug in Mapserver 7.4+ - https://github.com/mapserver/mapserver/issues/5837
							//$oStyleS->updateFromString("STYLE PATTERN $dashStylePattern END");
							$oLay->moveClassUp($styleIdx);
						}
						break;
					case MS_LAYER_POLYGON:
						$oStyle = new gc_styleObj($oClass);
						$oStyle->setBinding(MS_STYLE_BINDING_COLOR, 'fillcolor');
						$oStyle->updateFromString('STYLE OPACITY [fillopacity] END');
						$oStyleIn = new gc_styleObj($oClass);
						$oStyleIn->setBinding(MS_STYLE_BINDING_OUTLINECOLOR, 'strokecolor');
						$oStyleIn->updateFromString('STYLE OPACITY [strokeopacity] END');
						break;
				}
			}
			else {
	        	$oLay->applySLD($sld);
			}
		}
	}

	unset($_SESSION['VECTOR_PRINT_OPTIONS']);

    $objRequest->setParameter('LAYERS', implode(",",$layersToInclude));

    if($enableDebug) {
        $oMap->save(DEBUG_DIR."printvectors.map");
    }

    mapscript::msIO_installStdoutToBuffer();

    $oMap->owsdispatch($objRequest);
    $contenttype = mapscript::msIO_stripStdoutBufferContentType();
    header('Content-type: image/png');
    echo mapscript::msIO_getStdoutBufferBytes();
    mapscript::msIO_resetHandlers();

	// check if something bad happenend
	$error = mapscript::msGetErrorObj();
	$errMsg = '';
	while($error && $error->code != MS_NOERR) {
		$errMsg .= sprintf("Error in %s: %s<br>\n", $error->routine, $error->message);
		$error = $error->next();
	}
	if ($errMsg != '') {
		outputError($errMsg);
	}
}
