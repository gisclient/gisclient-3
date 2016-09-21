<?php
include_once "../../../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';
include_once ADMIN_PATH.'lib/functions.php';

$ajax = new GCAjax();

$db = GCApp::getDB();

if(empty($_REQUEST['selectedField'])) $ajax->error('field');
$selectedField = $_REQUEST['selectedField'];

if(!empty($_REQUEST['relation_id'])) {
	$relationId = $_REQUEST['relation_id'];
} else if(!empty($_REQUEST['qt_relation_id'])) {
	$qtrelationId = $_REQUEST['qt_relation_id'];
} else if(!empty($_REQUEST['layer'])) {
	$layerId = $_REQUEST['layer'];
} else if(!empty($_REQUEST['qt'])) {
	$qtId = $_REQUEST['qt'];
} else {
	if(empty($_REQUEST['catalog_id']) || !is_numeric($_REQUEST['catalog_id']) || $_REQUEST['catalog_id'] < 1) $ajax->error('catalog_id');
	$catalogId = $_REQUEST['catalog_id'];

	if(!empty($_REQUEST['data'])) $data = $_REQUEST['data'];
	else if(!empty($_REQUEST['table_name'])) $data = $_REQUEST['table_name'];
	else $ajax->error('data');
}

$result = array('steps'=>1, 'data'=>array(), 'data_objects'=>array(), 'step'=>1, 'fields'=>array('field'=>GCAuthor::t('field')));
$n = 0;

if(!empty($relationId)) {
	$sql="select catalog_path, connection_type, relation.table_name from ".DB_SCHEMA.".relation left join ".DB_SCHEMA.".catalog  USING (catalog_id) where relation_id = ?";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($relationId));
	$catalogData = $stmt->fetch(PDO::FETCH_ASSOC);
	$data = $catalogData['table_name'];
} else if(!empty($qtrelationId)) {
	$sql="select catalog_path, connection_type, qt_relation.table_name from ".DB_SCHEMA.".qt_relation left join ".DB_SCHEMA.".catalog  USING (catalog_id) where qt_relation_id = ?";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($qtrelationId));
	$catalogData = $stmt->fetch(PDO::FETCH_ASSOC);
	$data = $catalogData['table_name'];
} else if(!empty($layerId)) {
	$sql = "select catalog_path, connection_type, layer.data from ".DB_SCHEMA.".layer left join ".DB_SCHEMA.".catalog USING (catalog_id) where layer_id=?";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($layerId));
	$catalogData = $stmt->fetch(PDO::FETCH_ASSOC);
	$data = $catalogData['data'];
} else if(!empty($qtId)) {
	$sql = "select catalog_path, connection_type, layer.data from ".DB_SCHEMA.".layer left join ".DB_SCHEMA.".qt USING (layer_id) left join ".DB_SCHEMA.".catalog USING (catalog_id) where qt_id=?";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($qtId));
	$catalogData = $stmt->fetch(PDO::FETCH_ASSOC);
	$data = $catalogData['data'];
} else {
	$sql="select catalog_path,connection_type from ".DB_SCHEMA.".catalog  where catalog_id=?";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($catalogId));
	$catalogData = $stmt->fetch(PDO::FETCH_ASSOC);
}
if($catalogData['connection_type'] != 6) $ajax->error('not implemented');

list($connStr, $schema) = connAdminInfofromPath($catalogData["catalog_path"]);

$dataDb = GCApp::getDataDB($catalogData['catalog_path']);
$sql = "SELECT column_name from information_schema.columns " .
		"WHERE table_schema=:schema AND table_name=:table " .
		" ORDER BY ordinal_position";
$stmt = $dataDb->prepare($sql);
$stmt->execute(array(':schema'=>$schema, ':table'=>$data));

while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {	
	$result['data'][$n] = array('field'=>$row['column_name']);
	$result['data_objects'][$n] = array($selectedField => $row['column_name']);
	$n++;
}

$ajax->success($result);