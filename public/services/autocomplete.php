<?php
require_once '../../config/config.php';
require_once ROOT_PATH.'lib/ajax.class.php';
require_once ROOT_PATH . 'lib/GCService.php';

$gcService = GCService::instance();
$gcService->startSession();

// limit the number of results for the autocomplete option, since
// the browser hangs, if thoundends of items are sent
$maxNumResults = 100;

$ajax = new GCAjax();
$db = GCApp::getDB();

$lang = !empty($_REQUEST['lang']) ? $_REQUEST['lang'] : null;
if(empty($_REQUEST['field_id']) || !is_numeric($_REQUEST['field_id']) || (int)$_REQUEST['field_id'] != $_REQUEST['field_id']) {
    $ajax->error('Undefined or invalid field_id');
} else {
    $fieldId = (int)$_REQUEST['field_id'];
}

$dbSchema = DB_SCHEMA;
$sql = "SELECT qtfield_id, qtfield_name, qtrelation_id, layer_id, formula, filter_field_name, layergroup_name, layer_name 
        FROM {$dbSchema}.qtfield 
        INNER JOIN {$dbSchema}.layer USING(layer_id)
        INNER JOIN {$dbSchema}.layergroup USING(layergroup_id)
        WHERE qtfield_id=:id";
$stmt = $db->prepare($sql);
$stmt->execute(array('id'=>$fieldId));
$field = $stmt->fetch(PDO::FETCH_ASSOC);
if(empty($field)) {
    $ajax->error('Field '.$fieldId.' does not exists');
}
$isLayer = true;

// Check for related filters (filter of filter)
function getRecursiveFilterField($db, $filterFieldName, $layerId) {
    $result = array();
    $sql = 'SELECT qtfield_id, qtfield_name, qtrelation_id, layer_id, formula, filter_field_name FROM '.DB_SCHEMA.'.qtfield WHERE qtfield_name=:qtfield_name AND layer_id=:layer_id';
    $stmt = $db->prepare($sql);
    $stmt->execute(array('qtfield_name'=>$filterFieldName, 'layer_id'=>$layerId));
    $field = $stmt->fetch(PDO::FETCH_ASSOC);
    $result[] = $field;
    if(!empty($field['filter_field_name'])) {
        $result = array_merge($result, getRecursiveFilterField($db, $field['filter_field_name'], $field['layer_id']));
    }
    return $result;
}
$recursiveFields = getRecursiveFilterField($db, $field['filter_field_name'], $field['layer_id']);


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
if(empty($catalog)){
    $ajax->error('No catalog found for layer_id '. $field['layer_id']);
}

if($lang) {
    $sql = "select i18nf_id from ".DB_SCHEMA.".i18n_field where table_name='field' and field_name='field_name'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $i18nFieldId = $stmt->fetchColumn(0);
    if($i18nFieldId) {
        $sql = 'select value from '.DB_SCHEMA.'.localization where i18nf_id=:i18nf_id and pkey_id=:pkey and language_id=:lang';
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
            'i18nf_id'=>$i18nFieldId,
            'pkey'=>$field['field_id'],
            'lang'=>$lang
        ));
        $localized = $stmt->fetchColumn(0);
        if($localized) {
            $field['field_name'] = $localized;
        }
    }
}

$dataDb = GCApp::getDataDB($catalog['catalog_path']);
$schema = GCApp::getDataDBSchema($catalog['catalog_path']);

$constraints = array();
$params = array();

if (!empty($recursiveFields)) {
    foreach($recursiveFields as $recursiveField) {
        if (!empty($_REQUEST[$recursiveField['qtfield_name']])) {
            array_push($constraints, "{$recursiveField['qtfield_name']}=:{$recursiveField['qtfield_name']}_search");
            $params["{$recursiveField['qtfield_name']}_search"] = $_REQUEST[$recursiveField['qtfield_name']];
        }
    }
}

// Add gc-filter if present
$gcLayerFilters = "{$field['layergroup_name']}.{$field['layer_name']}";
if (!empty($_SESSION['GC_LAYER_FILTERS'][$gcLayerFilters])) {
    array_push($constraints, $_SESSION['GC_LAYER_FILTERS'][$gcLayerFilters]);
}

$fieldName = $field['qtfield_name'];
$alias = 'aliastable';
if($isLayer) {
    if(!empty($catalog['data_filter'])) {
        array_push($constraints, '('.$catalog['data_filter'].')');
    }
} else {
    $alias = $catalog['alias'];
    $fieldName = $field['formula'];
}

if(!empty($_REQUEST['filter'])) {
    array_push($constraints, ' '.$fieldName.' ilike :filter');
    $params['filter'] = '%'.$_REQUEST['filter'].'%';
}
if (!empty($_REQUEST['do_id'])) {
    if (!is_numeric($_REQUEST['do_id']) || (int) $_REQUEST['do_id'] != $_REQUEST['do_id']) {
        $ajax->error('invalid value of do_id');
    } else {
        array_push($constraints, ' do_id = :do_id ');
        $params['do_id'] = (int) $_REQUEST['do_id'];
    }
}
$sql = "SELECT DISTINCT {$fieldName} FROM {$schema}.{$catalog['table']} AS {$alias}";
if(!empty($constraints)) {
    $sql .= ' WHERE ('.implode(') AND (', $constraints) . ')';
}
$sql .= " ORDER BY {$fieldName} LIMIT {$maxNumResults}";

try {
    $stmt = $dataDb->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
} catch(Exception $e) {
    $ajax->error($e->getMessage());
}
$ajax->success(array('data'=>$results));
