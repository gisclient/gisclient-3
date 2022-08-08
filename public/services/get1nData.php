<?php

require_once __DIR__ . '/../../bootstrap.php';
require_once ROOT_PATH.'lib/ajax.class.php';

$gcService = GCService::instance();
$gcService->startSession();

/**
 * Replace all null values with an empty string, so that JSON serialization
 * does not show 'null'
 *
 * @param array $data array of array
 */
function replaceNullWithBlank(array &$data)
{
    foreach ($data as $rowNum => $rowData) {
        foreach ($rowData as $colNum => $item) {
            if (is_null($item)) {
                $data[$rowNum][$colNum] = '';
            }
        }
    }
}

$ajax = new GCAjax();
$db = GCApp::getDB();

if (empty($_REQUEST['relation_id'])) {
    $ajax->error('Undefined relation_id');
}
if (empty($_REQUEST['f_key_value'])) {
    $ajax->error('Undefined f_key_value');
}

$sql = 'select relation_id, catalog.catalog_id, catalog_path, relation_name, relationtype_id, data_field_1, table_name, table_field_1, layer_id, layer.data as layer_table
    from '.DB_SCHEMA.'.relation inner join '.DB_SCHEMA.'.catalog using(catalog_id) inner join '.DB_SCHEMA.'.layer using(layer_id) where relation_id = :relation_id';

$stmt = $db->prepare($sql);
$stmt->execute(array('relation_id'=>$_REQUEST['relation_id']));
$relation = $stmt->fetch(PDO::FETCH_ASSOC);
if (empty($relation)) {
    $ajax->error('Invalid relation_id');
}

$sql = 'select field_name, field_header, relation_id from '.DB_SCHEMA.'.field where searchtype_id not in (4,5) and relation_id in (0, '.$relation['relation_id'].') and layer_id = '.$relation['layer_id'];
$fields = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
if (empty($fields)) {
    $ajax->error('No fields defined');
}

$fieldsName = array();
foreach ($fields as $field) {
    array_push($fieldsName, 't_'.$field['relation_id'].'.'.$field['field_name']);
}

$sql = 'select catalog_path from '.DB_SCHEMA.'.layer inner join '.DB_SCHEMA.'.catalog using(catalog_id) where layer_id = '.$relation['layer_id'];
$layerCatalogPath = $db->query($sql)->fetchColumn(0);

$layerDataDb = GCApp::getDataDB($layerCatalogPath);
$layerSchema = GCApp::getDataDBSchema($layerCatalogPath);
$relationDataDb = GCApp::getDataDB($relation['catalog_path']);
$relationSchema = GCApp::getDataDBSchema($relation['catalog_path']);

$layerTable = $relation['layer_table'];
$relationTable = $relation['table_name'];

$sql = 'select '.implode(', ', $fieldsName).' from '.$layerSchema.'.'.$layerTable.' as t_0 left join '.$relationSchema.'.'.$relationTable.' as t_'.$relation['relation_id'].'  on t_'.$relation['relation_id'].'.'.$relation['table_field_1'].' = t_0.'.$relation['data_field_1'].' where t_0.'.$relation['data_field_1'].' = :value';

$stmt = $layerDataDb->prepare($sql);

$stmt->execute(array('value'=>$_REQUEST['f_key_value']));
$results = array(
    'fields'=>$fields,
    'results'=>$stmt->fetchAll(PDO::FETCH_ASSOC)
);

replaceNullWithBlank($results['results']);
$ajax->success(array('data'=>$results));
