<?php
require_once('../../config/config.php');
require_once ROOT_PATH.'lib/ajax.class.php';
$ajax = new GCAjax();

if(empty($_REQUEST['field_id'])) $ajax->error('Undefined fieldId');

$ajax = new GCAjax();
$db = GCApp::getDB();

$sql = 'select qtfield_name, qtrelation_id, layer_id, formula from '.DB_SCHEMA.'.qtfield where qtfield_id=:id';
$stmt = $db->prepare($sql);
$stmt->execute(array('id'=>$_REQUEST['field_id']));
$field = $stmt->fetch(PDO::FETCH_ASSOC);
if(empty($field)) $ajax->error('Field '.$_REQUEST['field_id'].' does not exists');

$isLayer = true;

if(!empty($field['qtrelation_id'])) {
    $sql = 'select catalog_path, table_name as table, qtrelation_name as alias from '.DB_SCHEMA.'.catalog inner join '.DB_SCHEMA.'.qtrelation using(catalog_id) '.
        ' where qtrelation_id = :id';
    $params = array('id'=>$field['qtrelation_id']);
    $isLayer = false;
} else {
    $sql = 'select catalog_path, data as table, data_filter from '.DB_SCHEMA.'.catalog inner join '.DB_SCHEMA.'.layer using(catalog_id) '.
        ' where layer_id = :id';
    $params = array('id'=>$field['layer_id']);
}
$stmt = $db->prepare($sql);
$stmt->execute($params);
$catalog = $stmt->fetch(PDO::FETCH_ASSOC);
if(empty($catalog)) $ajax->error('Unexisting catalog');

$dataDb = GCApp::getDataDB($catalog['catalog_path']);
$schema = GCApp::getDataDBSchema($catalog['catalog_path']);

$constraints = array();
$params = array();

$fieldName = $field['qtfield_name'];
$alias = 'aliastable';
if($isLayer) {
    if(!empty($catalog['data_filter'])) array_push($constraints, $catalog['data_filter']);
} else {
    $alias = $catalog['alias'];
    $fieldName = $field['formula'];
}

if(!empty($_REQUEST['filter'])) {
    array_push($constraints, ' '.$fieldName.' ilike :filter');
    $params['filter'] = '%'.$_REQUEST['filter'].'%';
}

$sql = 'select distinct '.$fieldName.' from '.$schema.'.'.$catalog['table'].' as '.$alias;
if(!empty($constraints)) {
    $sql .= ' where '.implode(' and ', $constraints);
}
$sql .= ' order by '.$fieldName;

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
} catch(Exception $e) {
    $ajax->error($e->getMessage());
}
$ajax->success(array('data'=>$results));
