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

$db = GCApp::getDB();

/* Recupero i dati del layer */
//qt_filter -> data_filter
//mapset_filter -> non c'è più
$sql = 'select catalog_path, layer.data, layer.data_unique, layer.data_filter from '.DB_SCHEMA.'.layer inner join '.DB_SCHEMA.'.catalog  using (catalog_id) inner join '.DB_SCHEMA.'.field using(layer_id) where field_id=:field_id';
$stmt = $db->prepare($sql);
$stmt->execute(array('field_id'=>$_REQUEST['field_id']));
$layer = $stmt->fetch(PDO::FETCH_ASSOC);

$dataDb = GCApp::getDataDB($layer['catalog_path']);
$datalayerSchema = GCApp::getDataDBSchema($layer['catalog_path']);
$datalayerTable = $layer["data"];
$datalayerKey = $layer["data_unique"];
$filters = array(); //in futuro si possono rimettere i campi filtrati per altri campi, filtri da sessione etc
if(!empty($layer['data_filter'])) array_push($filters, $layer['data_filter']);
$sTable = $datalayerSchema.".".$datalayerTable;


/* Recupero i dati del campo */
//field_filter -> non c'è più
$sql = 'select field.field_id, field_name, catalog_path,  relation.relation_name, relation_id, data_field_1, data_field_2, data_field_3, table_field_1, table_field_2, table_field_3, table_name, catalog_path, formula from '.DB_SCHEMA.'.field left join '.DB_SCHEMA.'.relation using (relation_id) left join '.DB_SCHEMA.'.catalog using (catalog_id) where field.field_id=:field_id';
$stmt = $db->prepare($sql);
$stmt->execute(array('field_id'=>$_REQUEST['field_id']));
$field = $stmt->fetch(PDO::FETCH_ASSOC);

if(empty($field['relation_id'])) {
    $field["relation_name"] = DATALAYER_ALIAS_TABLE;//alias per la tabella del livello
    $field["schema"] = $datalayerSchema;
    $field["table_name"] = $datalayerTable;
}else{
    $field['schema'] = GCApp::getDataDBSchema($field['catalog_path']);
}

// +++++++++++++++++ FILTRO AUTOSUGGEST ++++++++++++++++++++++++++++++++++//
//Info campo che fa da filtro: ho passato una stringa di filtro a un campo che ha il campo filtro, devo cercare il campo di filtro stesso
//$fieldFilterId = $field["field_filter"];
//$filtervalue = $_REQUEST["filtervalue"];
$joinList = array();
$joinString = $sTable ." as " . DATALAYER_ALIAS_TABLE;
//$datalayerFilter = implode(' AND ', $filters);

$fieldName = $field["field_name"];
if(!empty($field['formula'])) {
    $fieldName = $field['formula'];
}

if(!empty($field["relation_id"])) {//il campo oggetto di autosuggest è su tabella secondaria
    if(empty($field['formula'])) {
        $fieldName = $field['relation_name'] . '.' . $fieldName;
    }
    
    if($field["data_field_1"] && $field["table_field_1"]) $joinList[] = DATALAYER_ALIAS_TABLE.".".$field["data_field_1"]."=\"".$field["relation_name"]."\".".$field["table_field_1"];
    if($field["data_field_2"] && $field["table_field_2"]) $joinList[] = DATALAYER_ALIAS_TABLE.".".$field["data_field_2"]."=\"".$field["relation_name"]."\".".$field["table_field_2"];
    if($field["data_field_3"] && $field["table_field_3"]) $joinList[] = DATALAYER_ALIAS_TABLE.".".$field["data_field_3"]."=\"".$field["relation_name"]."\".".$field["table_field_3"];
    $joinFields = implode(" AND ",$joinList);
    $joinString .= " inner join ". $field["schema"].".".$field["table_name"]." as ". $field["relation_name"]." on ($joinFields) ";
    $sqlQuery = "SELECT DISTINCT ". $fieldName ." as value FROM " .$joinString;
} else { //caso elementare: il campo è su tabella del layer
    $sqlQuery = "SELECT DISTINCT ". $fieldName ." as value FROM " . $field["schema"].".". $field["table_name"] ." as " .DATALAYER_ALIAS_TABLE;
}

if(!empty($params)) {
    array_push($filters, $fieldName . ' ilike :input_string ');
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
