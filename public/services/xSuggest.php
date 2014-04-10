<?php

require_once('../../config/config.php');
require_once ROOT_PATH.'lib/ajax.class.php';
$ajax = new GCAjax();

if(empty($_REQUEST['field_id'])) $ajax->error('Undefined fieldId');
if(empty($_REQUEST['suggest'])) $ajax->error('Undefined suggest');
$inputString = '%' . $_REQUEST['suggest'] . '%';

$db = GCApp::getDB();

/* Recupero i dati del layer */
//qt_filter -> data_filter
//mapset_filter -> non c'è più
$sql = 'select catalog_path, layer.data, layer.data_unique, layer.data_filter from '.DB_SCHEMA.'.layer inner join '.DB_SCHEMA.'.catalog  using (catalog_id) inner join '.DB_SCHEMA.'.qtfield using(layer_id) where qtfield_id=:field_id';
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
$sql = 'select qtfield.qtfield_id, qtfield_name, catalog_path,  qtrelation.qtrelation_name, qtrelation_id, data_field_1, data_field_2, data_field_3, table_field_1, table_field_2, table_field_3, table_name, catalog_path, formula from '.DB_SCHEMA.'.qtfield left join '.DB_SCHEMA.'.qtrelation using (qtrelation_id) left join '.DB_SCHEMA.'.catalog using (catalog_id) where qtfield.qtfield_id=:field_id';
$stmt = $db->prepare($sql);
$stmt->execute(array('field_id'=>$_REQUEST['field_id']));
$field = $stmt->fetch(PDO::FETCH_ASSOC);

if(empty($field['qtrelation_id'])) {
    $field["qtrelation_name"] = DATALAYER_ALIAS_TABLE;//alias per la tabella del livello
    $field["schema"] = $datalayerSchema;
    $field["table_name"] = $datalayerTable;
}else{
    $field['schema'] = GCApp::getDataDBSchema($field['catalog_path']);
}

// +++++++++++++++++ FILTRO AUTOSUGGEST ++++++++++++++++++++++++++++++++++//
//Info campo che fa da filtro: ho passato una stringa di filtro a un campo che ha il campo filtro, devo cercare il campo di filtro stesso
//$qtfieldFilterId = $field["field_filter"];
//$qtfiltervalue = $_REQUEST["filtervalue"];
$joinList = array();
$joinString = $sTable ." as " . DATALAYER_ALIAS_TABLE;
$datalayerFilter = implode(' AND ', $filters);

$fieldName = $field["qtfield_name"];
if(!empty($field['formula'])) {
    $fieldName = $field['formula'];
}

if(!empty($field["qtrelation_id"])) {//il campo oggetto di autosuggest è su tabella secondaria
    if(empty($field['formula'])) {
        $fieldName = $field['qtrelation_name'] . '.' . $fieldName;
    }
    
    if($field["data_field_1"] && $field["table_field_1"]) $joinList[] = DATALAYER_ALIAS_TABLE.".".$field["data_field_1"]."=\"".$field["qtrelation_name"]."\".".$field["table_field_1"];
    if($field["data_field_2"] && $field["table_field_2"]) $joinList[] = DATALAYER_ALIAS_TABLE.".".$field["data_field_2"]."=\"".$field["qtrelation_name"]."\".".$field["table_field_2"];
    if($field["data_field_3"] && $field["table_field_3"]) $joinList[] = DATALAYER_ALIAS_TABLE.".".$field["data_field_3"]."=\"".$field["qtrelation_name"]."\".".$field["table_field_3"];
    $joinFields = implode(" AND ",$joinList);
    $joinString .= " inner join ". $field["schema"].".".$field["table_name"]." as ". $field["qtrelation_name"]." on ($joinFields) ";
    $sqlQuery = "SELECT DISTINCT ". $fieldName ." as value FROM " .$joinString ." WHERE ". $fieldName ." ilike :input_string $datalayerFilter";
} else { //caso elementare: il campo è su tabella del layer
    $sqlQuery = "SELECT DISTINCT ". $fieldName ." as value FROM " . $field["schema"].".". $field["table_name"] ." as " .DATALAYER_ALIAS_TABLE. " WHERE ". $fieldName ." ilike :input_string $datalayerFilter";
}
try {
    $stmt = $dataDb->prepare($sqlQuery);
    $stmt->execute(array('input_string'=>$inputString));
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    echo $sqlQuery;
    $ajax->error($e->getMessage());
}

$ajax->success(array('data'=>$results));
