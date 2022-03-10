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
if(!empty($_REQUEST["filtervalue"]) && !empty($_REQUEST["filterfields"])) {
    $filterFields = explode(',', $_REQUEST["filterfields"]);
    $filterValue = explode(',', $_REQUEST["filtervalue"]);
    for ($i = 0; $i < count($filterFields); $i++) {
        $inputString = $filterValue[$i];
        $params['field_' . $filterFields[$i]] = $inputString;
    }
}

$db = GCApp::getDB();

/* Recupero i dati del layer */
//qt_filter -> data_filter

$sql = 'select catalog_path, layer.data, layer.data_unique, layer.data_filter, qt_filter, qt.qt_id, qt.materialize from '.DB_SCHEMA.'.layer inner join '.DB_SCHEMA.'.qt  using (layer_id) inner join '.DB_SCHEMA.'.catalog  using (catalog_id) inner join '.DB_SCHEMA.'.qt_field using(qt_id) where qt_field_id=:field_id';
$stmt = $db->prepare($sql);
$stmt->execute(array('field_id'=>$_REQUEST['field_id']));
$layer = $stmt->fetch(PDO::FETCH_ASSOC);

$dataDb = GCApp::getDataDB($layer['catalog_path']);
$datalayerSchema = GCApp::getDataDBSchema($layer['catalog_path']);
$materialize = $layer["materialize"];
$datalayerTable = $materialize === 1 ? "gw_qt_" .$layer["qt_id"] : $layer["data"];
$datalayerKey = $layer["data_unique"];
$filters = array(); //in futuro si possono rimettere i campi filtrati per altri campi, filtri da sessione etc
if(!empty($layer['data_filter']) && $materialize != 1) array_push($filters, '('.$layer['data_filter'].')');
if(!empty($layer['qt_filter']) && $materialize != 1) array_push($filters, $layer['qt_filter']);
$sTable = $datalayerSchema.".".$datalayerTable;


/* Recupero i dati del campo */
$sql = 'select qt_field.qt_field_id, qt_field_name, field_filter, catalog_path,  qt_relation.qt_relation_name, qt_relation_id, data_field_1, data_field_2, data_field_3, table_field_1, table_field_2, table_field_3, table_name, catalog_path, formula from '.DB_SCHEMA.'.qt_field left join '.DB_SCHEMA.'.qt_relation using (qt_relation_id) left join '.DB_SCHEMA.'.catalog using (catalog_id) where qt_field.qt_field_id=:field_id';
$stmt = $db->prepare($sql);
$stmt->execute(array('field_id'=>$_REQUEST['field_id']));
$field = $stmt->fetch(PDO::FETCH_ASSOC);

if(empty($field['qt_relation_id']) || $materialize === 1) {
    $field["qt_relation_name"] = DATALAYER_ALIAS_TABLE;//alias per la tabella del livello
    $field["schema"] = $datalayerSchema;
    $field["table_name"] = $datalayerTable;
}else{
    $field['schema'] = GCApp::getDataDBSchema($field['catalog_path']);
}

$fieldName = DATALAYER_ALIAS_TABLE . "." . $field["qt_field_name"];
if(!empty($field['formula']) && $materialize != 1) {
    $fieldName = $field['formula'];
}

$fromString = $sTable ." as " . DATALAYER_ALIAS_TABLE;

// +++++++++++++++++ FILTRO AUTOSUGGEST ++++++++++++++++++++++++++++++++++//
// **** Query su campo principale
if(!empty($field["qt_relation_id"]) && $materialize != 1) {//il campo oggetto di autosuggest è su tabella secondaria
    if(empty($field['formula'])) {
        $fieldName = $field['qt_relation_name'] . '.' . $field["qt_field_name"];
    }

    $joinList = array();

    if($field["data_field_1"] && $field["table_field_1"]) $joinList[] = DATALAYER_ALIAS_TABLE.".".$field["data_field_1"]."=\"".$field["qt_relation_name"]."\".".$field["table_field_1"];
    if($field["data_field_2"] && $field["table_field_2"]) $joinList[] = DATALAYER_ALIAS_TABLE.".".$field["data_field_2"]."=\"".$field["qt_relation_name"]."\".".$field["table_field_2"];
    if($field["data_field_3"] && $field["table_field_3"]) $joinList[] = DATALAYER_ALIAS_TABLE.".".$field["data_field_3"]."=\"".$field["qt_relation_name"]."\".".$field["table_field_3"];
    $joinFields = implode(" AND ",$joinList);
    $fromString = "(" . $fromString . " inner join ". $field["schema"].".".$field["table_name"]." as ". $field["qt_relation_name"]." on ($joinFields)) ";
}

if(!empty($params)) {
    if (isset($params['input_string'])) {
        array_push($filters, $fieldName . '::text ilike :input_string ');
    }
}

//Info campo che fa da filtro: ho passato una stringa di filtro a un campo che ha il campo filtro, devo cercare il campo di filtro stesso
if(isset($field["field_filter"]) && isset($filterFields)){
    /* Recupero i dati del campo filtro */
    $sql = 'select qt_field.qt_field_id, qt_field_name, field_filter, qt_relation.qt_relation_name, qt_relation_id, data_field_1, data_field_2, data_field_3, table_field_1, table_field_2, table_field_3, table_name, catalog_path, formula from '.DB_SCHEMA.'.qt_field left join '.DB_SCHEMA.'.qt_relation using (qt_relation_id) left join '.DB_SCHEMA.'.catalog using (catalog_id) where qt_field.qt_field_id in (' . implode(',', $filterFields) . ')';
    $stmt = $db->query($sql);

    while ($fieldFilter = $stmt->fetch(PDO::FETCH_ASSOC)) {

        if(!empty($fieldFilter['qt_relation_id']) && $materialize != 1) {
            $fieldFilter['schema'] = GCApp::getDataDBSchema($fieldFilter['catalog_path']);

            $fieldFilterName = '"' . $fieldFilter['qt_relation_name'] . '"."' . $fieldFilter["qt_field_name"] . '"';
            if(!empty($fieldFilter['formula'])) {
                $fieldFilterName = $fieldFilter['formula'];
            }

            $joinList = array();

            if($fieldFilter["data_field_1"] && $fieldFilter["table_field_1"]) $joinList[] = DATALAYER_ALIAS_TABLE.".".$fieldFilter["data_field_1"]."=\"".$fieldFilter["qt_relation_name"]."\".".$fieldFilter["table_field_1"];
            if($fieldFilter["data_field_2"] && $fieldFilter["table_field_2"]) $joinList[] = DATALAYER_ALIAS_TABLE.".".$fieldFilter["data_field_2"]."=\"".$fieldFilter["qt_relation_name"]."\".".$fieldFilter["table_field_2"];
            if($fieldFilter["data_field_3"] && $fieldFilter["table_field_3"]) $joinList[] = DATALAYER_ALIAS_TABLE.".".$fieldFilter["data_field_3"]."=\"".$fieldFilter["qt_relation_name"]."\".".$fieldFilter["table_field_3"];
            $joinFields = implode(" AND ",$joinList);
            $strFromAdd =  " inner join ". $fieldFilter["schema"].".".$fieldFilter["table_name"]." as ". $fieldFilter["qt_relation_name"]." on ($joinFields)";
            if (strpos($fromString, $strFromAdd) === false)
                $fromString = "(" . $fromString . $strFromAdd . ") ";
        }
        else {
            $fieldFilterName = DATALAYER_ALIAS_TABLE.".".$fieldFilter["qt_field_name"];
            if(!empty($fieldFilter['formula']) && $materialize != 1) {
                $fieldFilterName = $fieldFilter['formula'];
            }
        }
        array_push($filters, $fieldFilterName . '::text = :field_' . $fieldFilter['qt_field_id'] . ' ');
    }

}

$sqlQuery = "SELECT DISTINCT ". $fieldName ." as value FROM " . $fromString;

if(!empty($filters)) {
    $sqlQuery .= ' where '.implode(' and ', $filters);
}

$sqlQuery .= " order by ".$fieldName." limit 50";

print_debug($sqlQuery, null, 'xsuggest');

try {
    $stmt = $dataDb->prepare($sqlQuery);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    echo $sqlQuery;
    $ajax->error($e->getMessage());
}

$ajax->success(array('data'=>$results));
