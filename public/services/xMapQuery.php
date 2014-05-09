<?php

require_once('../../config/config.php');
require_once (ROOT_PATH.'lib/functions.php');
require_once(ROOT_PATH.'lib/gcPgQuery.class.php');//Definizione dell'oggetto PgQuery

$db = GCApp::getDB();

$request = $_REQUEST;

$sql = 'select layer_id from '.DB_SCHEMA.'.layer
    inner join '.DB_SCHEMA.'.layergroup on layer.layergroup_id = layergroup.layergroup_id
    inner join '.DB_SCHEMA.'.mapset_layergroup on mapset_layergroup.layergroup_id = layergroup.layergroup_id
    where mapset_name = :mapset_name and layergroup_name = :layergroup_name and layer_name = :layer_name';
$stmt = $db->prepare($sql);

list($layergroupName, $layerName) = explode('.', $_REQUEST['featureType']);

$stmt->execute(array(
    'mapset_name'=>$_REQUEST['mapsetName'],
    'layergroup_name'=>$layergroupName,
    'layer_name'=>$layerName
));

$request['layer_id'] = $stmt->fetchColumn(0);

$oQuery = new PgQuery($request);

die(json_encode($oQuery->query($request['layer_id'])));








die();
ini_set('display_errors', 'On');
error_reporting(E_ALL);

/*
Ricerca da form senza filtro geometrico:
gisclient/services/xMapQuery.php?action=info&bufferSelected=0&geoPix=0.959240700661&imageHeight=667&imageWidth=911&item=2&mapset=reti_grg_tb&mode=search&oXgeo=1018083.23&oYgeo=5527050.37&op_qf%5B189%5D=%3D&op_qf%5B23%5D=%3D&op_qf%5B84%5D=%3D&qf%5B189%5D=AV&qf%5B23%5D=VIA%20CAPPELLETTA%20-%20AV&qf%5B84%5D=PE&queryOp=AND&resultAction=2&resultype=1&selectMode=0&spatialQuery=0

Ricerca da form vista corrente: (la geometria non viene passata perché uso il mapextent in sessione)
gisclient/services/xMapQuery.php?action=info&bufferSelected=0&geoPix=0.959240700661&imageHeight=667&imageWidth=911&item=2&mapset=reti_grg_tb&mode=search&oXgeo=1018083.23&oYgeo=5527050.37&op_qf%5B189%5D=%3D&op_qf%5B23%5D=%3D&op_qf%5B84%5D=%3D&qf%5B189%5D=AV&qf%5B23%5D=VIA%20CAPPELLETTA%20-%20AV&qf%5B84%5D=PE&queryOp=AND&resultAction=2&resultype=1&selectMode=0&spatialQuery=1

Ricerca da box di selezione in mappa: X e Y sono array contenti le coordinate del box
gisclient/services/xMapQuery.php?action=info&bufferSelected=0&geoPix=0.959240700661&imageHeight=667&imageWidth=911&imgX%5B0%5D=309&imgX%5B1%5D=309&imgX%5B2%5D=662&imgX%5B3%5D=662&imgX%5B4%5D=309&imgY%5B0%5D=367&imgY%5B1%5D=642&imgY%5B2%5D=642&imgY%5B3%5D=367&imgY%5B4%5D=367&item=2&mapset=reti_grg_tb&mode=search&oXgeo=1018083.23&oYgeo=5527050.37&resultAction=2&resultype=1&selectMode=0&spatialQuery=6



*/


$_SESSION["MAPSET_reti_grg_tb"] = array(
    "PROJECT_NAME" => "geoweb_genova",
    "SRID" => 900913,
    "MAPSET_EXTENT" => array (954497,5498871,1068173,5571752),
    "MAP_EXTENT" => array (1016504.94242,5534712.73231,1017133.64423,5535298.5995),
 	"GROUPS_ON" => array(231)
);



$_REQUEST = array(
    'layer_id'=>783,
    'project'=>'geoweb_genova',
);








//scelta da menu radio
define('QUERY_EXTENT',0);
define('QUERY_WINDOW',1);
define('QUERY_CURRENT',2);
define('QUERY_RESULT',3);
//selezione sulla mappa
define('QUERY_POINT',5);
define('QUERY_POLYGON',6);
define('QUERY_CIRCLE',7);

define('OBJ_POINT',1);
define('OBJ_CIRCLE',2);
define('OBJ_POLYGON',3);

define('AND_CONST','&&');
define('OR_CONST','||');
define('LT_CONST','<');
define('LE_CONST','<=');
define('GT_CONST','>');
define('GE_CONST','>=');
define('NEQ_CONST','!=');
define('JOLLY_CHAR','*');

define('STANDARD_FIELD_TYPE',1);
define('LINK_FIELD_TYPE',2);
define('EMAIL_FIELD_TYPE',3);
define('HEADER_GROUP_TYPE',10);
define('IMAGE_FIELD_TYPE',8);
define('SECONDARY_FIELD_LINK',99);

/* 
define('SUM_FIELD_TYPE',6);
define('COUNT_FIELD_TYPE',7);
define('AVG_FIELD_TYPE',8);
*/

define('RESULT_TYPE_SINGLE',1);
define('RESULT_TYPE_TABLE',2);
define('RESULT_TYPE_ALL',3);
define('RESULT_TYPE_NONE',4);

define('ONE_TO_ONE_RELATION',1);
define('ONE_TO_MANY_RELATION',2);

define('AGGREGATE_NULL_VALUE',' ');

define('ORDER_FIELD_ASC',1);
define('ORDER_FIELD_DESC',2);



//Definizione dell'oggetto GCMap
//configurazione del sistema
require_once('../../config/config.php');
require_once (ROOT_PATH.'lib/functions.php');
require_once(ROOT_PATH.'lib/gcPgQuery.class.php');//Definizione dell'oggetto PgQuery

$jsObject = array();
$oQuery=new PgQuery();





if(isset($_REQUEST["printTable"]) && $_REQUEST["printTable"]==1){
	$dataQuery = $oQuery->allQueryResults[0];
	$updateMap = 0;//da vedere se aggiungere al pdf la mappa con la selezione
	if(isset($_REQUEST["destination"]) && $_REQUEST["destination"]=='pdf') 
		$printPdfTable = 1;
	elseif(isset($_REQUEST["destination"]) && $_REQUEST["destination"]=='xls') 
		$printXLSTable = 1;//$printCSVTable = 1;
	else{
		$jsObject["resultype"] = 2;
		$jsObject['queryresult'] = $oQuery->allQueryResults;
	}
}
else{
	$jsObject["resultype"] = intval($_REQUEST["resultype"]);
	$jsObject['queryresult'] = $oQuery->allQueryResults;
	$updateMap = $oQuery->mapToUpdate;//valore che indica se devo o meno aggiornare la mappa
}


	
	//Se non ci sono errori setto il controllo a 0
	if(empty($jsObject['error'])) $jsObject['error']=0;	
	jsonString($jsObject);
?>
