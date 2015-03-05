<?php
include_once "../../../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';
include_once ADMIN_PATH.'lib/functions.php';

$ajax = new GCAjax();

if(empty($_REQUEST['layer'])) $ajax->error('layer');
$layerId = $_REQUEST['layer'];

$db = GCApp::getDB();

$sql = "select catalog_path, connection_type, layer.data from ".DB_SCHEMA.".layer left join ".DB_SCHEMA.".catalog USING (catalog_id) where layer_id=?";
$stmt = $db->prepare($sql);
$stmt->execute(array($layerId));
$catalogData = $stmt->fetch(PDO::FETCH_ASSOC);
$tableName = $catalogData['data'];
list($connStr, $schema) = connAdminInfofromPath($catalogData["catalog_path"]);
$dataDb = GCApp::getDataDB($catalogData['catalog_path']);

$result = array('steps'=>3, 'data'=>array(), 'data_objects'=>array());
$n = 0;

if(empty($_REQUEST['step'])) {
	$result['step'] = 1;
	$result['fields'] = array('table'=>GCAuthor::t('table'));
	
	$sql = "SELECT c.relname AS table
                FROM pg_catalog.pg_class c
                LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                WHERE c.relkind IN ('r','v','') AND n.nspname = :schema order by c.relname";
	try {
		$stmt = $dataDb->prepare($sql);
		$stmt->execute(array('schema'=>$schema));
	} catch(Exception $e) {
		$ajax->error();
	}
	
	while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$result['data'][$n] = $row;
		$result['data_objects'][$n] = array(
			'lookup_table'=>$row['table']
		);
		$n++;
	}
} else if($_REQUEST['step'] == 2) {
	$result['step'] = 2;
	$result['fields'] = array('lookup_id'=>GCAuthor::t('lookup_id'));
	
	$sql = 'select table_name from information_schema.tables where table_schema=:schema and table_name=:table';
	$stmt = $dataDb->prepare($sql);
	$stmt->execute(array(':schema'=>$schema, ':table'=>$_REQUEST['lookup_table']));
	$dbTableName = $stmt->fetchColumn(0);
	if($dbTableName != $_REQUEST['lookup_table']) $ajax->error(54);
	
	$sql = "SELECT column_name as lookup_id FROM information_schema.columns WHERE table_schema=:schema AND table_name=:table ORDER BY column_name;";
	$stmt = $dataDb->prepare($sql);
	$stmt->execute(array(':schema'=>$schema, ':table'=>$_REQUEST['lookup_table']));
	while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$result['data'][$n] = $row;
		$result['data_objects'][$n] = $row;
		$n++;
	}
} else if($_REQUEST['step'] == 3) {
	$result['step'] = 3;
	$result['fields'] = array('lookup_name'=>GCAuthor::t('lookup_name'));
	
	$sql = 'select table_name from information_schema.tables where table_schema=:schema and table_name=:table';
	$stmt = $dataDb->prepare($sql);
	$stmt->execute(array(':schema'=>$schema, ':table'=>$_REQUEST['lookup_table']));
	$dbTableName = $stmt->fetchColumn(0);
	if($dbTableName != $_REQUEST['lookup_table']) $ajax->error(72);
	
	$sql = "SELECT column_name as lookup_name FROM information_schema.columns WHERE table_schema=:schema AND table_name=:table ORDER BY column_name;";
	$stmt = $dataDb->prepare($sql);
	$stmt->execute(array(':schema'=>$schema, ':table'=>$_REQUEST['lookup_table']));
	while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$result['data'][$n] = $row;
		$result['data_objects'][$n] = $row;
		$n++;
	}
} else {
	$ajax->error('Invalid step');
}
$ajax->success($result);