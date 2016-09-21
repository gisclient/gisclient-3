<?php

require_once('../../config/config.php');
require_once ROOT_PATH.'lib/ajax.class.php';
$ajax = new GCAjax();

if(empty($_REQUEST['field_id'])) $ajax->error('Undefined fieldId');
$params = array();
if(!empty($_REQUEST['suggest'])) {
    $inputString = '%' . $_REQUEST['suggest'] . '%';
    $params['input_string'] = $inputString;
}
if(!empty($_REQUEST["filtervalue"])) {
    $inputString = '%' . $_REQUEST['filtervalue'] . '%';
    $params['filter_string'] = $inputString;
}

$db = GCApp::getDB();

/* Recupero i dati del layer */
//qt_filter -> data_filter

$sql = 'select catalog_path, layer.data, layer.data_unique, layer.data_filter, qt_filter from '.DB_SCHEMA.'.layer inner join '.DB_SCHEMA.'.qt  using (layer_id) inner join '.DB_SCHEMA.'.catalog  using (catalog_id) inner join '.DB_SCHEMA.'.qt_field using(qt_id) where qt_field_id=:field_id';
$stmt = $db->prepare($sql);
$stmt->execute(array('field_id'=>$_REQUEST['field_id']));
$layer = $stmt->fetch(PDO::FETCH_ASSOC);

$dataDb = GCApp::getDataDB($layer['catalog_path']);
$datalayerSchema = GCApp::getDataDBSchema($layer['catalog_path']);
$datalayerTable = $layer["data"];
$datalayerKey = $layer["data_unique"];
$filters = array(); //in futuro si possono rimettere i campi filtrati per altri campi, filtri da sessione etc
if(!empty($layer['data_filter'])) array_push($filters, $layer['data_filter']);
if(!empty($layer['qt_filter'])) array_push($filters, $layer['qt_filter']);
$sTable = $datalayerSchema.".".$datalayerTable;


/* Recupero i dati del campo */
$sql = 'select qt_field.qt_field_id, qtfield_name, field_filter, catalog_path,  qt_relation.qtrelation_name, qt_relation_id, data_field_1, data_field_2, data_field_3, table_field_1, table_field_2, table_field_3, table_name, catalog_path, formula from '.DB_SCHEMA.'.qt_field left join '.DB_SCHEMA.'.qt_relation using (qt_relation_id) left join '.DB_SCHEMA.'.catalog using (catalog_id) where qt_field.qt_field_id=:field_id';
$stmt = $db->prepare($sql);
$stmt->execute(array('field_id'=>$_REQUEST['field_id']));
$field = $stmt->fetch(PDO::FETCH_ASSOC);

if(empty($field['qt_relation_id'])) {
    $field["qtrelation_name"] = DATALAYER_ALIAS_TABLE;//alias per la tabella del livello
    $field["schema"] = $datalayerSchema;
    $field["table_name"] = $datalayerTable;
}else{
    $field['schema'] = GCApp::getDataDBSchema($field['catalog_path']);
}

$fieldName = DATALAYER_ALIAS_TABLE . "." . $field["qtfield_name"];
if(!empty($field['formula'])) {
    $fieldName = $field['formula'];
}

$fromString = $sTable ." as " . DATALAYER_ALIAS_TABLE;

// +++++++++++++++++ FILTRO AUTOSUGGEST ++++++++++++++++++++++++++++++++++//
//Info campo che fa da filtro: ho passato una stringa di filtro a un campo che ha il campo filtro, devo cercare il campo di filtro stesso
$fieldFilterId = $field["field_filter"];

if(isset($fieldFilterId) && isset($_REQUEST["filtervalue"])){
    /* Recupero i dati del campo filtro */
    $sql = 'select qt_field.qt_field_id, qtfield_name, field_filter, qt_relation.qtrelation_name, qt_relation_id, data_field_1, data_field_2, data_field_3, table_field_1, table_field_2, table_field_3, table_name, catalog_path, formula from '.DB_SCHEMA.'.qt_field left join '.DB_SCHEMA.'.qt_relation using (qt_relation_id) left join '.DB_SCHEMA.'.catalog using (catalog_id) where qt_field.qt_field_id=:field_id';
    $stmt = $db->prepare($sql);
    $stmt->execute(array('field_id'=>$fieldFilterId));
    $fieldFilter = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!empty($fieldFilter['qt_relation_id'])) {
        $fieldFilter['schema'] = GCApp::getDataDBSchema($fieldFilter['catalog_path']);
    }

    $fieldFilterName = $fieldFilter["qtfield_name"];
    if(!empty($fieldFilter['formula'])) {
        $fieldFilterName = $fieldFilter['formula'];
}
}

if(!empty($field["qt_relation_id"])) {//il campo oggetto di autosuggest è su tabella secondaria
    if(empty($field['formula'])) {
        $fieldName = $field['qtrelation_name'] . '.' . $fieldName;
    }
    
    $joinList = array();
    
    if($field["data_field_1"] && $field["table_field_1"]) $joinList[] = DATALAYER_ALIAS_TABLE.".".$field["data_field_1"]."=\"".$field["qtrelation_name"]."\".".$field["table_field_1"];
    if($field["data_field_2"] && $field["table_field_2"]) $joinList[] = DATALAYER_ALIAS_TABLE.".".$field["data_field_2"]."=\"".$field["qtrelation_name"]."\".".$field["table_field_2"];
    if($field["data_field_3"] && $field["table_field_3"]) $joinList[] = DATALAYER_ALIAS_TABLE.".".$field["data_field_3"]."=\"".$field["qtrelation_name"]."\".".$field["table_field_3"];
    $joinFields = implode(" AND ",$joinList);
    $fromString = "(" . $fromString . " inner join ". $field["schema"].".".$field["table_name"]." as ". $field["qtrelation_name"]." on ($joinFields)) ";
}

if(!empty($fieldFilter["qt_relation_id"])) {//il campo oggetto di autosuggest è su tabella secondaria
    if(empty($fieldFilter['formula'])) {
        $fieldFilterName = $fieldFilter['qtrelation_name'] . '.' . $fieldFilterName;
    }
    
    $joinList = array();
    
    if($fieldFilter["data_field_1"] && $fieldFilter["table_field_1"]) $joinList[] = DATALAYER_ALIAS_TABLE.".".$fieldFilter["data_field_1"]."=\"".$fieldFilter["qtrelation_name"]."\".".$fieldFilter["table_field_1"];
    if($fieldFilter["data_field_2"] && $fieldFilter["table_field_2"]) $joinList[] = DATALAYER_ALIAS_TABLE.".".$fieldFilter["data_field_2"]."=\"".$fieldFilter["qtrelation_name"]."\".".$fieldFilter["table_field_2"];
    if($fieldFilter["data_field_3"] && $fieldFilter["table_field_3"]) $joinList[] = DATALAYER_ALIAS_TABLE.".".$fieldFilter["data_field_3"]."=\"".$fieldFilter["qtrelation_name"]."\".".$fieldFilter["table_field_3"];
    $joinFields = implode(" AND ",$joinList);
    $fromString = "(" . $fromString . " inner join ". $fieldFilter["schema"].".".$fieldFilter["table_name"]." as ". $fieldFilter["qtrelation_name"]." on ($joinFields)) ";
}

$sqlQuery = "SELECT DISTINCT ". $fieldName ." as value FROM " . $fromString;


if(!empty($params)) {
    if (isset($params['input_string']))
        array_push($filters, $fieldName . ' ilike :input_string ');
    if (isset($params['filter_string']))
        array_push($filters, $fieldFilterName . ' ilike :filter_string ');
}

if(!empty($filters)) {
    $sqlQuery .= ' where '.implode(' and ', $filters);
}

$sqlQuery .= " order by ".$fieldName." limit 25";

try {
    $stmt = $dataDb->prepare($sqlQuery);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    echo $sqlQuery;
    $ajax->error($e->getMessage());
}

$ajax->success(array('data'=>$results));
