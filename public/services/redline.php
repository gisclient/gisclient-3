<?php
require_once "../../config/config.php";
require_once ROOT_PATH . 'lib/GCService.php';

$gcService = GCService::instance();
$gcService->startSession();

if(!isset($_REQUEST['REQUEST']) || !in_array($_REQUEST['REQUEST'], array('GetMap', 'SaveLayer', 'DeleteLayer', 'GetLayers', 'PrintMap'))) {
	die("REQUEST unknown");
}

if(!defined('REDLINE_TABLE') || !defined('REDLINE_SRID')) outputError('Missing config redline values');
if(!defined('REDLINE_SCHEMA')) define('REDLINE_SCHEMA', 'public');
if(!defined('REDLINE_FONT')) define('REDLINE_FONT', 'arial');
if(!defined('POSTGIS_TRANSFORM_GEOMETRY')) define('POSTGIS_TRANSFORM_GEOMETRY', 'Postgis_Transform_Geometry');


//Creazione di un geotiff con le annotazioni
if ($_REQUEST["REQUEST"] == "PrintMap") {
	$file = fopen('php://input', 'r');
	$fileContent = file_get_contents('php://input');
	$_REQUEST["options"] = $fileContent;
	require_once 'gcWMSMerge.php';
	die(json_encode(array("file"=>$mapConfig['file_name'])));

}

$db = GCApp::getDB();
$user = new GCUser();

//elenco dei layer di redline per l'utente corrente
if($_REQUEST["REQUEST"] == "GetLayers"){
	$sql = "SELECT DISTINCT redline_id, redline_title FROM ".REDLINE_SCHEMA.".".REDLINE_TABLE." WHERE project=:project AND mapset=:mapset AND username=:username ORDER BY redline_id;";
	$params = array(
		':project'=>$_REQUEST['PROJECT'],
		':mapset'=>$_REQUEST['MAPSET'],
		':username'=>$user->isAuthenticated() ? $user->getUsername() : 'GUEST'
	);
	$stmt = $db->prepare($sql);
	try {
		$stmt->execute($params);
	} catch(Exception $e) {
		//outputError($e->getMessage());
	}
	$layers = array();
	while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$layers[]=$row;
	}
	die(json_encode(array('layers'=>$layers)));
}

if($_REQUEST["REQUEST"] == "DeleteLayer"){
	$sql = "DELETE FROM ".REDLINE_SCHEMA.".".REDLINE_TABLE." WHERE redline_id=?;";
	$stmt = $db->prepare($sql);
	try {
		$stmt->execute(array($_REQUEST['REDLINEID']));
	} catch(Exception $e) {
		outputError($e->getMessage());
	}
	//Se ho una immagine salvata la elimino
	if(!empty($_REQUEST['IMAGEPATH'])){
		$fileName = $_REQUEST['IMAGEPATH'].$_REQUEST['REDLINEID'].".tif";
		@unlink($fileName);
	}
	
	die(json_encode(array('result'=>'OK')));
}

$geomTypes = array(
	'Point'=>array('db_type'=>'POINT', 'db_field'=>'point_geom', 'ms_type'=>MS_LAYER_POINT),
	'LineString'=>array('db_type'=>'LINESTRING', 'db_field'=>'line_geom', 'ms_type'=>MS_LAYER_LINE, 'label_function'=>'st_endpoint'),
	'Polygon'=>array('db_type'=>'POLYGON', 'db_field'=>'polygon_geom', 'ms_type'=>MS_LAYER_POLYGON, 'label_function'=>'st_centroid')
);

if(empty($_REQUEST['SRS'])) outputError('Missing geometry srid');
$parts = explode(':', $_REQUEST["SRS"]);
$mapSRID = $parts[1];
$SRS_params = array();
//unset($_SESSION[$_REQUEST['PROJECT']]["PROJPARAMS"]);
if($mapSRID != REDLINE_SRID){
	if(!isset($_SESSION[$_REQUEST['PROJECT']]["PROJPARAMS"][$mapSRID]))  getProjParams($mapSRID);
	if(!isset($_SESSION[$_REQUEST['PROJECT']]["PROJPARAMS"][REDLINE_SRID])) getProjParams(REDLINE_SRID);
	$SRS_params = $_SESSION[$_REQUEST['PROJECT']]["PROJPARAMS"];
};


if($_REQUEST["REQUEST"] == "SaveLayer"){

	$sql = "CREATE TABLE ".REDLINE_SCHEMA.".".REDLINE_TABLE." (id serial, project varchar, mapset varchar, username varchar, redline_id numeric, redline_title varchar, date timestamp, note text,color varchar, CONSTRAINT annotazioni_pkey PRIMARY KEY (id));";
	try {
		$db->exec($sql);
		foreach($geomTypes as $type) {
			$db->exec("select addgeometrycolumn('".REDLINE_SCHEMA."', '".REDLINE_TABLE."', '".$type['db_field']."', ".REDLINE_SRID.", '".$type['db_type']."', 2)");
		}
	} catch(Exception $e) { //table already exists
	}

	$featureCollection = json_decode($_REQUEST['features'], true);
	$redlineId = date('YmdHis');
	$redlineTitle = !empty($_REQUEST['TITLE']) ? $_REQUEST['TITLE'] : null;
    
    $inserted = false;

	foreach($featureCollection['features'] as $feature) {
		if(!isset($feature['geometry'])) continue;
		$geom = $feature['geometry'];
		//if($geom['type']=='LineString' && count($geom['coordinates'])<2) continue;
		//if($geom['type']=='Polygon' && count($geom['coordinates'])<3) continue;

		if(!isset($geomTypes[$geom['type']])) outputError('Geometry type not implemented');
		$sql = "insert into ".REDLINE_SCHEMA.".".REDLINE_TABLE." (project, mapset, username, redline_id, redline_title, date, note, color) values (:project, :mapset, :username, :redline_id, :redline_title, now(), :note, :color)";
		$stmt = $db->prepare($sql);
		$params = array(
			':project'=>$_REQUEST['PROJECT'],
			':mapset'=>$_REQUEST['MAPSET'],
			':username'=>$user->isAuthenticated() ? $user->getUsername() : 'GUEST',
			':redline_id'=>$redlineId,
			':redline_title'=>$redlineTitle,
			':note'=>!empty($feature['properties']['note']) ? $feature['properties']['note'] : null,
			':color'=>!empty($feature['properties']['color']) ? $feature['properties']['color'] : null
		);
		try {
			$stmt->execute($params);
		} catch(Exception $e) {
			outputError($e->getMessage());
		}
		$rowId = $db->lastInsertId(REDLINE_SCHEMA.'.'.REDLINE_TABLE.'_id_seq');
		
		$wktGeom = parseGeoJSONGeomtry($feature['geometry'], $mapSRID);
		
		$sql = "update ".REDLINE_SCHEMA.".".REDLINE_TABLE." set ".$geomTypes[$geom['type']]['db_field']." = $wktGeom where id = :id";
		$stmt = $db->prepare($sql);

		try {
			$stmt->execute(array(':id'=>$rowId));
		} catch(Exception $e) {
			outputError($e->getMessage()."\n\n--".$rowId);
		}
        $inserted = true;
	}

    if($inserted) {
        die(json_encode(array('redlineId'=>$redlineId, 'redlineTitle'=>$redlineTitle)));
    } else {
        outputError('Invalid format');
    }
}


if($_REQUEST["REQUEST"] == "GetMap" && isset($_REQUEST["SERVICE"]) && $_REQUEST["SERVICE"]=="WMS") {

	if(empty($_REQUEST['REDLINEID'])) die("MANCA ID"); 
	$geomFields = array();
	foreach($geomTypes as $type) array_push($geomFields, $type['db_field']);
	$sql = "select ".implode(',', $geomFields).", note, color from ".REDLINE_SCHEMA.".".REDLINE_TABLE.
		" where redline_id = ?";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($_REQUEST['REDLINEID']));
	$redline = $stmt->fetch(PDO::FETCH_ASSOC);
	$types = array();
	foreach($geomTypes as $index => $type) {
		if(!empty($redline[$type['db_field']])) {
			array_push($types, $geomTypes[$index]);
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
	
	$oMap->outputformat->set('name','PNG');
	$oMap->outputformat->set('driver','GD/PNG');
	$oMap->outputformat->set('extension','png');
	$oMap->outputformat->set('transparent',MS_ON);
	$oMap->outputformat->setOption("INTERLACE", "OFF");
	
	$oMap->setFontSet(ROOT_PATH.'fonts/fonts.list');		
	
	$layerProjString = (($mapSRID == REDLINE_SRID) || empty($SRS_params[REDLINE_SRID]))?"init=epsg:".REDLINE_SRID:$SRS_params[REDLINE_SRID];
	
	foreach($types as $type) {
		$oLay = ms_newLayerObj($oMap);
		$oLay->set('name', 'redline_'.$type['db_type']);
		$oLay->set('group', 'redline');
		$oLay->set('type', $type['ms_type']);
		$oLay->setConnectionType(MS_POSTGIS);
		$oLay->set('connection', "user=".DB_USER." password=".DB_PWD." dbname=".DB_NAME." host=".DB_HOST." port=".DB_PORT);
        $data = "the_geom from (select id, note, color, redline_id, ".$type['db_field']." as the_geom from ".REDLINE_SCHEMA.".".REDLINE_TABLE.") as foo using unique id using srid=".REDLINE_SRID;
		$oLay->set('data', $data);
		$oLay->setFilter("redline_id=".$_REQUEST['REDLINEID']);
		$oLay->setProjection($layerProjString);
		$oLay->set('sizeunits',MS_PIXELS);
		$oClass = ms_newClassObj($oLay);
		$oStyle = ms_newStyleObj($oClass);
		$oStyle->setbinding(MS_STYLE_BINDING_OUTLINECOLOR, "color");	
		$oStyle->set("width", 1);
		$oLay->set('status', MS_ON);

		//Annotazione
		$oLay = ms_newLayerObj($oMap);
		$oLay->set('name','redline_text_'.$type['db_type']);
		$oLay->set('group', 'redline');
		$oLay->set('type', MS_LAYER_ANNOTATION);
		$oLay->setConnectionType(MS_POSTGIS);
		$oLay->set('connection', "user=".DB_USER." password=".DB_PWD." dbname=".DB_NAME." host=".DB_HOST." port=".DB_PORT);
        $geom = !empty($type['label_function']) ? $type['label_function'].'('.$type['db_field'].')' : $type['db_field'];
		$oLay->set('data', "the_geom from (select id, note, color, redline_id, $geom as the_geom from ".REDLINE_SCHEMA.".".REDLINE_TABLE.") as foo using unique id using srid=".REDLINE_SRID);

		$oLay->setFilter("redline_id=".$_REQUEST['REDLINEID']);
		$oLay->setProjection($layerProjString);
		$oLay->set('sizeunits', MS_PIXELS);
		$oLay->set('labelitem', "note");
		
		// TODO: already called some lines before. Can this be removed?
		$oClass = ms_newClassObj($oLay);
		// Label properties
		$lbl = null;
		if (ms_GetVersionInt() < 60200) {
			$lbl = $oClass->label;
		} else if($oClass->numlabels > 0) {
			$lbl = $oClass->getLabel(0);
		}
		if ($lbl) {
			$lbl->set("position", MS_UR);
			$lbl->set("offsetx", 5);
			$lbl->set("offsety", 10);
			$lbl->set("font", REDLINE_FONT);
			$lbl->set("type", MS_TRUETYPE);
			$lbl->set("size", 14);
			$lbl->setbinding(MS_LABEL_BINDING_COLOR, "color");
		}
		$oLay->set('status', MS_ON);
	}

	ms_ResetErrorList();	
	$oImage=$oMap->draw();
			
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

function parseGeoJSONGeomtry($geom, $mapSRID) {
	global $geomTypes,$SRS_params;
	
	$wkt = "st_geomfromtext('".$geomTypes[$geom['type']]['db_type']."(";
	$points = array();
	$coordinates = $geom['coordinates'];
	if($geom['type'] == 'Polygon') {
		$coordinates = $geom['coordinates'][0];
		$wkt .= '(';
	}
	foreach($coordinates as $coord) {
		array_push($points, implode(' ', $coord));
	}
	$wkt .= implode(',', $points);
	if($geom['type'] == 'Polygon') $wkt .= ')';
	$wkt .= ")', $mapSRID)";
	
	if($mapSRID != REDLINE_SRID) {
		if(empty($SRS_params[REDLINE_SRID]) || empty($SRS_params[$mapSRID])){
			$wkt = "st_transform($wkt, ".REDLINE_SRID.")";
		}
		else{
			$paramFrom = $SRS_params[$mapSRID];
			$paramTo = $SRS_params[REDLINE_SRID];
			$wkt = POSTGIS_TRANSFORM_GEOMETRY."($wkt,'".$paramFrom."','".$paramTo."',".REDLINE_SRID.")";
		}
	}
	return $wkt;
}


function getProjParams($srid){
	$db = GCApp::getDB();
	$sql="SELECT proj4text,projparam FROM spatial_ref_sys LEFT JOIN ".DB_SCHEMA.".project_srs using(srid)
            WHERE srid = $srid AND (project_name IS NULL OR project_name = ?);";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($_REQUEST['PROJECT']));
	$projparams = $stmt->fetch(PDO::FETCH_ASSOC);
	$projString = $projparams["proj4text"];
	if(strpos($projString,"towgs84") === false && !empty($projparams["projparam"])) $projString .="+towgs84=".$projparams["projparam"];
	$_SESSION[$_REQUEST['PROJECT']]["PROJPARAMS"][$srid] = $projString;
}
