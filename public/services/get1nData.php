<?php
require_once '../../config/config.php';
require_once ROOT_PATH.'lib/ajax.class.php';
require_once ROOT_PATH . 'lib/GCService.php';

$gcService = GCService::instance();
$gcService->startSession();

/**
 * Replace all null values with an empty string, so that JSON serialization
 * does not show 'null'
 * 
 * @param array $data array of array
 */
function replaceNullWithBlank(array &$data) {
	foreach($data as $rowNum => $rowData) {
		foreach($rowData as $colNum => $item) {
			if (is_null($item)) {
				$data[$rowNum][$colNum] = '';
			}
		}
	}
}

if(empty($_REQUEST['qtrelation_id'])) $ajax->error('Undefined qtrelation_id');
if(empty($_REQUEST['f_key_value'])) $ajax->error('Undefined f_key_value');

$ajax = new GCAjax();
$db = GCApp::getDB();

$sql = 'select qtrelation_id, catalog.catalog_id, catalog_path, qtrelation_name, qtrelationtype_id, data_field_1, table_name, table_field_1, layer_id, layer.data as layer_table
    from '.DB_SCHEMA.'.qtrelation inner join '.DB_SCHEMA.'.catalog using(catalog_id) inner join '.DB_SCHEMA.'.layer using(layer_id) where qtrelation_id = :qtrelation_id';

$stmt = $db->prepare($sql);
$stmt->execute(array('qtrelation_id'=>$_REQUEST['qtrelation_id']));
$qtRelation = $stmt->fetch(PDO::FETCH_ASSOC);
if(empty($qtRelation)) $ajax->error('Invalid qtrelation_id');

$sql = 'select qtfield_name, field_header, qtrelation_id from '.DB_SCHEMA.'.qtfield where searchtype_id not in (4,5) and qtrelation_id in (0, '.$qtRelation['qtrelation_id'].') and layer_id = '.$qtRelation['layer_id'];
$fields = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
if(empty($fields)) $ajax->error('No fields defined');

$fieldsName = array();
foreach($fields as $field) array_push($fieldsName, 't_'.$field['qtrelation_id'].'.'.$field['qtfield_name']);

$sql = 'select catalog_path from '.DB_SCHEMA.'.layer inner join '.DB_SCHEMA.'.catalog using(catalog_id) where layer_id = '.$qtRelation['layer_id'];
$layerCatalogPath = $db->query($sql)->fetchColumn(0);

$layerDataDb = GCApp::getDataDB($layerCatalogPath);
$layerSchema = GCApp::getDataDBSchema($layerCatalogPath);
$relationDataDb = GCApp::getDataDB($qtRelation['catalog_path']);
$relationSchema = GCApp::getDataDBSchema($qtRelation['catalog_path']);

$layerTable = $qtRelation['layer_table'];
$relationTable = $qtRelation['table_name'];

$sql = 'select '.implode(', ', $fieldsName).' from '.$layerSchema.'.'.$layerTable.' as t_0 left join '.$relationSchema.'.'.$relationTable.' as t_'.$qtRelation['qtrelation_id'].'  on t_'.$qtRelation['qtrelation_id'].'.'.$qtRelation['table_field_1'].' = t_0.'.$qtRelation['data_field_1'].' where t_0.'.$qtRelation['data_field_1'].' = :value';

$stmt = $layerDataDb->prepare($sql);

$stmt->execute(array('value'=>$_REQUEST['f_key_value']));
$results = array(
    'fields'=>$fields,
    'results'=>$stmt->fetchAll(PDO::FETCH_ASSOC)
);

replaceNullWithBlank($results['results']);
$ajax->success(array('data'=>$results));
