<?php
require_once "../../config/config.php";
require_once ROOT_PATH.'lib/ajax.class.php';
include_once ADMIN_PATH.'lib/functions.php';

$ajax = new GCAjax();

if(empty($_REQUEST['catalog'])) $ajax->error('catalog');
if(empty($_REQUEST['table'])) $ajax->error('table');
if(empty($_REQUEST['id'])) $ajax->error('id');
if(empty($_REQUEST['name'])) $ajax->error('name');

$sql = 'select catalog_path from '.DB_SCHEMA.'.catalog where catalog_id=?';
try {
	$db = GCApp::getDB();
	$stmt = $db->prepare($sql);
	$stmt->execute(array($_REQUEST['catalog']));
	$catalogPath = $stmt->fetchColumn(0);
	list($connStr,$schema)=connAdminInfofromPath($catalogPath);
	$dataDb = GCApp::getDataDB($catalogPath);
	
	$sql = 'select table_name from information_schema.tables where table_schema=:schema and table_name=:table';
	$stmt = $dataDb->prepare($sql);
	$stmt->execute(array(':schema'=>$schema, ':table'=>$_REQUEST['table']));
	$dbTableName = $stmt->fetchColumn(0);
	if($dbTableName != $_REQUEST['table']) $ajax->error();
	
	$sql = 'select column_name from information_schema.columns where table_schema=:schema and table_name=:table and column_name=:column';
	$stmt = $dataDb->prepare($sql);
	$stmt->execute(array(':schema'=>$schema, ':table'=>$_REQUEST['table'], ':column'=>$_REQUEST['id']));
	$dbColumnName = $stmt->fetchColumn(0);
	if($dbColumnName != $_REQUEST['id']) $ajax->error();
	
	$sql = 'select column_name from information_schema.columns where table_schema=:schema and table_name=:table and column_name=:column';
	$stmt = $dataDb->prepare($sql);
	$stmt->execute(array(':schema'=>$schema, ':table'=>$_REQUEST['table'], ':column'=>$_REQUEST['name']));
	$dbColumnName = $stmt->fetchColumn(0);
	if($dbColumnName != $_REQUEST['name']) $ajax->error();
	
	$sql = 'select '.$_REQUEST['id'].' as id, '.$_REQUEST['name'].' as name from '.$schema.'.'.$_REQUEST['table'].' order by '.$_REQUEST['name'];
	$rows = $dataDb->query($sql)->fetchAll(PDO::FETCH_ASSOC);
	$data = array();
	foreach($rows as $row) {
		$data[$row['id']] = $row['name'];
	}
} catch(Exception $e) {
	var_export($e);
	$ajax->error();
}
$ajax->success(array('data'=>$data));