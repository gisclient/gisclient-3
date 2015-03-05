<?php
require_once('../../config/config.php');
require_once ROOT_PATH.'lib/ajax.class.php';
$ajax = new GCAjax();

if(empty($_REQUEST['action']) || !in_array($_REQUEST['action'], array('search', 'get-geom'))) $ajax->error('Invalid action');
if(empty($_REQUEST['mapset'])) $ajax->error('Undefined mapset');
if(empty($GEOLOCATOR_CONFIG) || empty($GEOLOCATOR_CONFIG[$_REQUEST['mapset']])) $ajax->error('Manca configurazione geolocator');
$config = $GEOLOCATOR_CONFIG[$_REQUEST['mapset']];

$ajax = new GCAjax();
$db = GCApp::getDB();

$sql = 'select catalog_path from '.DB_SCHEMA.'.catalog where catalog_name=:name';
$stmt = $db->prepare($sql);
$stmt->execute(array('name'=>$config['catalogname']));
$catalogPath = $stmt->fetchColumn(0);
$dataDb = GCApp::getDataDB($catalogPath);

if($_REQUEST['action'] == 'search') {
    if(empty($_REQUEST['key'])) $ajax->error('Undefined key');
    $key = str_replace(' ', '%', trim($_REQUEST['key']));
    $key = str_replace('%%', '%', trim($key));
    $key = str_replace('%%', '%', trim($key));
    
    $sql = ' select '.$config['namefield'].' as name, '.$config['idfield'].' as id from '.$config['tablename'].' where '.$config['namefield'].' ilike :key ';
    if(!empty($config['where'])) $sql .= ' and '.$config['where'];
    if(!empty($config['order'])) $sql .= ' order by '.$config['order'];
    $sql .= ' limit 30';

    try {
        $stmt = $dataDb->prepare($sql);
        $stmt->execute(array('key'=>'%'.$key.'%'));
        $results = array();
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($results, $row);
        }
    } catch(Exception $e) {
        $ajax->error($e->getMessage());
    }
    $ajax->success(array('data'=>$results));
} else if($_REQUEST['action'] == 'get-geom') {
    if(empty($_REQUEST['id'])) $ajax->error('Undefined id');
    
    $sql = ' select astext('.$config['geomfield'].') from '.$config['tablename'].' where '.$config['idfield'].' = :id ';
    try {
        $stmt = $dataDb->prepare($sql);
        $stmt->execute(array('id'=>$_REQUEST['id']));
        $data = $stmt->fetchColumn(0);
    } catch(Exception $e) {
        $ajax->error($e->getMessage());
    }
    $ajax->success(array('data'=>$data));
}
