<?php
require_once "../../config/config.php";
require_once 'include/mapImage.php';
if(!isset($_REQUEST['REQUEST']) || $_REQUEST['REQUEST'] != 'GetMap') die('Invalid request');

if(!defined('PRINT_VECTORS_TABLE') || !defined('PRINT_VECTORS_SRID')) outputError('Missing config print vectors values');
$tableName = PRINT_VECTORS_TABLE;
$schema = defined('PRINT_VECTORS_SCHEMA') ? PRINT_VECTORS_SCHEMA : 'public';

if(!file_exists(ROOT_PATH.'config/printVectorsSLD.xml')) outputError('Missing SLD');
$sld = file_get_contents(ROOT_PATH.'config/printVectorsSLD.xml');

$db = GCApp::getDB();

$geomTypes = mapImage::$vectorTypes;


if(empty($_REQUEST['SRS'])) outputError('Missing geometry srid');
$parts = explode(':', $_REQUEST["SRS"]);
$mapSRID = $parts[1];
$SRS_params = array();


if($_REQUEST["REQUEST"] == "GetMap" && isset($_REQUEST["SERVICE"]) && $_REQUEST["SERVICE"]=="WMS") {

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

    //file_put_contents('debug.txt', var_export($types, true));
	if(empty($types)) { // empty geom, che facciamo?
	}


	$oMap=ms_newMapObj('');
	if(defined('PROJ_LIB')) $oMap->setConfigOption("PROJ_LIB", PROJ_LIB);
	$aExtent = explode(",",$_REQUEST['BBOX']);

	$oMap->extent->setextent($aExtent[0], $aExtent[1], $aExtent[2], $aExtent[3]);
	$oMap->setSize(intval($_REQUEST['WIDTH']), intval($_REQUEST['HEIGHT']));	
	$oMap->setProjection("init=".strtolower($_REQUEST['SRS']));
    //$oMap->set('debug', 5);
    //$oMap->setconfigoption('MS_ERRORFILE', '/data/sites/gc/author-32/public/services/msdebug.txt');
    
    $oMap->web->updateFromString('
WEB
	METADATA
        # for mapserver 6.0
        "ows_enable_request" "*"
		"ows_title"	"r3-signs"
	
		"wfs_encoding"	"UTF-8"
		"wms_encoding"	"UTF-8"

    	"wms_onlineresource" "http://192.168.0.13/gc/author-32/public/services/vectors.php"
    	"wfs_onlineresource" "http://192.168.0.13/gc/author-32/public/services/vectors.php"
		"wms_feature_info_mime_type"	"text/html"
		"wfs_namespace_prefix"	"feature"
		"wms_srs"	"EPSG:32632"
	

	END
	IMAGEPATH "/tmp/"
	IMAGEURL "/tmp/"	
END	
    ');
	
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
        //$oLay->set('sizeunits', MS_MILES);
        //$oLay->set('units', MS_PIXELS);
        /* switch($type['ms_type']) {
            case MS_LAYER_POLYGON: 
                $oClass = ms_newClassObj($oLay);
                $oClass->updateFromString("
                    CLASS
                        STYLE
                            COLOR 0 180 180
                            OPACITY 30
                        END
                        STYLE
                            OUTLINECOLOR 0 0 180
                            OPACITY 60
                            WIDTH 2
                        END
                    END
                ");
            break;
            case MS_LAYER_LINE:
                $oClass = ms_newClassObj($oLay);
                $oClass->updateFromString("
                    CLASS
                        STYLE
                            SYMBOL \"CIRCLE\"
                            OUTLINECOLOR 0 180 180
                            SIZE 2
                            OPACITY 60
                            WIDTH 2
                        END
                    END
                ");
            break;
            case MS_LAYER_POINT:
                $oClass->updateFromString("
                    CLASS
                        NAME \"printvectors_POINT\"
                        STYLE
                            SYMBOL \"CIRCLE\"
                            COLOR 0 180 180
                            OUTLINECOLOR 0 180 180
                            SIZE 12
                            OPACITY 50
                            WIDTH 1
                        END
                    END
                ");
            break;
        } */
		$oLay->set('status', MS_ON);
        $oLay->applySLD($sld);
	}
    
    $objRequest->setParameter('LAYERS', implode(",",$layersToInclude));
    
    if(defined('DEBUG') && DEBUG == 1) {
        $oMap->save(DEBUG_DIR."printvectors.map");
    }
    
    ms_ioinstallstdouttobuffer(); 
    
    $oMap->owsdispatch($objRequest);
    $contenttype = ms_iostripstdoutbuffercontenttype(); 
    //$ctt = explode("/",$contenttype); 
    header('Content-type: image/png'); 
    ms_iogetStdoutBufferBytes(); 
    ms_ioresethandlers();
    die();
    

	ms_ResetErrorList();	
	$oImage=$oMap->draw();

    $oMap->save('debug.map');
    
	$error = ms_GetErrorObj();
	if($error->code != MS_NOERR){
		while($error->code != MS_NOERR){
			print("CREATE MAP ERROR <br>");
			printf("Error in %s: %s<br>\n", $error->routine, $error->message);
			$error = $error->next();
		}
		die();
	}

	header("Content-type:image/png");
	$oImage->saveImage("");
	die();
}




function outputError($msg) {
	header("Status: 500 Internal Server Error");
	die(json_encode(array('error'=>$msg)));
	//die("error\n".$msg);
}

