<?php

require_once __DIR__ . '/../../bootstrap.php';
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
