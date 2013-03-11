<?php
require_once('../../config/config.php');
require_once ROOT_PATH.'lib/ajax.class.php';
$ajax = new GCAjax();

if(empty($_REQUEST['field_id'])) $ajax->error('Undefined fieldId');
$lang = !empty($_REQUEST['lang']) ? $_REQUEST['lang'] : null;

$ajax = new GCAjax();
$db = GCApp::getDB();

$sql = 'select qtfield_id, qtfield_name, qtrelation_id, layer_id, formula from '.DB_SCHEMA.'.qtfield where qtfield_id=:id';
$stmt = $db->prepare($sql);
$stmt->execute(array('id'=>$_REQUEST['field_id']));
$field = $stmt->fetch(PDO::FETCH_ASSOC);
if(empty($field)) $ajax->error('Field '.$_REQUEST['field_id'].' does not exists');

$isLayer = true;

if(!empty($field['qtrelation_id'])) {
    $sql = 'select catalog.project_name, catalog_path, table_name as table, qtrelation_name as alias from '.DB_SCHEMA.'.catalog inner join '.DB_SCHEMA.'.qtrelation using(catalog_id) '.
        ' where qtrelation_id = :id';
    $params = array('id'=>$field['qtrelation_id']);
    $isLayer = false;
} else {
    $sql = 'select catalog.project_name, catalog_path, data as table, data_filter from '.DB_SCHEMA.'.catalog inner join '.DB_SCHEMA.'.layer using(catalog_id) '.
        ' where layer_id = :id';
    $params = array('id'=>$field['layer_id']);
}
$stmt = $db->prepare($sql);
$stmt->execute($params);
$catalog = $stmt->fetch(PDO::FETCH_ASSOC);
if(empty($catalog)) $ajax->error('Unexisting catalog');

if($lang) {
    $sql = "select i18nf_id from ".DB_SCHEMA.".i18n_field where table_name='qtfield' and field_name='qtfield_name'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $i18nFieldId = $stmt->fetchColumn(0);
    if($i18nFieldId) {
        $sql = 'select value from '.DB_SCHEMA.'.localization where i18nf_id=:i18nf_id and pkey_id=:pkey and language_id=:lang';
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
            'i18nf_id'=>$i18nFieldId,
            'pkey'=>$field['qtfield_id'],
            'lang'=>$lang
        ));
        $localized = $stmt->fetchColumn(0);
        if($localized) {
            $field['qtfield_name'] = $localized;
        } else echo '!localized';
    } else echo '!i18nfield';
} else echo '!lang';


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
if(!empty($_REQUEST['do_id'])) {
    array_push($constraints, ' do_id = :do_id ');
    $params['do_id'] = $_REQUEST['do_id'];
}

$sql = 'select distinct '.$fieldName.' from '.$schema.'.'.$catalog['table'].' as '.$alias;
if(!empty($constraints)) {
    $sql .= ' where '.implode(' and ', $constraints);
}
$sql .= ' order by '.$fieldName;

try {
    $stmt = $dataDb->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
} catch(Exception $e) {
    $ajax->error($e->getMessage());
}
$ajax->success(array('data'=>$results));
