<?php
require_once '../../bootstrap.php';
require_once ROOT_PATH.'lib/ajax.class.php';
require_once ROOT_PATH.'lib/export.php';

use GisClient\Author\Db;
use GisClient\Author\Layer;

$ajax = new \GCAjax();
$db = new Db();

$inputJSONText = file_get_contents('php://input');
if (($data = json_decode($inputJSONText, true)) === null) {
    $data = $_REQUEST;
}

if (!is_array($data)) {
    $data = array($data);
}

$exports = array();
foreach ($data as $expConf) {
    if (!isset($exports[$expConf['export_format']])) {
        $exports[$expConf['export_format']] = array();
    }
    $featureType = $expConf['feature_type'];
    list($layergroupName, $layerName) = explode('.', $featureType);

    $sql = "SELECT layer_id "
        . " FROM {$db->getParams()['schema']}.layer "
        . " INNER JOIN {$db->getParams()['schema']}.layergroup using(layergroup_id) "
        . " WHERE layergroup_name = :layergroup and layer_name = :layer ";
    $stmt = $db->getDb()->prepare($sql);
    $stmt->execute(array(
        'layergroup'=>$layergroupName,
        'layer'=>$layerName
    ));
    $layerId = $stmt->fetchColumn(0);

    $layer = new Layer($layerId);
    $catalog = $layer->getCatalog();
    $layerDb = new Db($catalog);

    $fields = array();
    $layerFields = $layer->getFields();
    foreach ($expConf['fields'] as $eField) {
        foreach ($layerFields as $lField) {
            if ($lField->getName() == $eField['field_name']) {
                array_push($fields, $eField);
                break;
            }
        }
    }
    $expConf['fields'] = $fields;
    $expConf['layer'] = $layer;

    $where = 'true';
    if (isset($expConf['data'])) {
        $ids = array();
        foreach ($expConf['data'] as $key) {
            if (isset($key[$layer->getPrimaryColumn()])) {
                array_push($ids, $db->getDb()->quote($key[$layer->getPrimaryColumn()]));
            } else {
                throw new \Exception("Error: Invalid data, missing primary key", 1);
            }
        }
        $where .= " AND {$layer->getPrimaryColumn()} IN (";
        $where .= implode(', ', $ids);
        $where .= ')';
    } else if (isset($expConf['extent'])) {
        $extentString = implode(', ', $expConf['extent']);
        $where = "ST_Intersects({$layer->getGeomColumn()}, ST_MakeEnvelope({$extentString}, {$layer->getGeomSrid()}))";
    }

    //Create view
    $fieldsNames = array_map(function ($element) {
        return $element['field_name'];
    }, $fields);
    array_push($fieldsNames, $layer->getGeomColumn());
    $viewName = 'export_' . $layer->getTable() . '_' . session_id() . '_' . rand(0, 999999);
    $sql = "CREATE VIEW public.{$viewName} AS "
        . " SELECT " . implode(', ', $fieldsNames)
        . " FROM {$layerDb->getParams()['schema']}.{$layer->getTable()}"
        . " WHERE {$where}";

    $db->getDb()->query($sql);

    $expConf['srid'] = $layer->getGeomSrid();

    array_push($exports[$expConf['export_format']], array(
        'config' => array(
            'db' => $layerDb->getParams()['db_name'],
            'db_instance' => $layerDb->getDb(),
            'table' => $viewName,
            'schema' => 'public',
            'name' => $layer->getName(),
            'pk' => $layer->getPrimaryColumn(),
            'geom' => $layer->getGeomColumn()
        ),
        'extras' => $expConf
    ));
}

$zipFile = null;
if (isset($exports['shp'])) {
    foreach ($exports['shp'] as $exp) {
        $export = new \GCExport($exp['config']['db_instance'], 'shp');
        $url = $export->export(array($exp['config']), array(
            'name' => 'export_shp',
            'add_to_zip' => &$zipFile,
            'return_url' => true,
            'fields' => $exp['extras']['fields']
        ));

        $db->getDb()->query("DROP VIEW IF EXIST {$exp['config']['schema']}.{$exp['config']['table']}");
    }
}

if (isset($exports['dxf'])) {
    foreach ($exports['dxf'] as $exp) {
        $export = new \GCExport($exp['config']['db_instance'], 'dxf');
        $url = $export->export(array($exp['config']), array(
            'name' => 'export_dxf',
            'add_to_zip' => &$zipFile,
            'return_url' => true,
            'extent' => $exp['extras']['extent'],
            'srid' => $exp['extras']['srid'],
            'layer' => $exp['extras']['layer']
        ));

        $db->getDb()->query("DROP VIEW IF EXIST {$exp['config']['schema']}.{$exp['config']['table']}");
    }
}

if (isset($exports['xls'])) {
    foreach ($exports['xls'] as $exp) {
        $export = new \GCExport($exp['config']['db_instance'], 'xls');
        $url = $export->export(array($exp['config']), array(
            'name' => 'export_xls',
            'add_to_zip' => &$zipFile,
            'return_url' => true,
            'fields' => $exp['extras']['fields']
        ));

        $db->getDb()->query("DROP VIEW IF EXIST {$exp['config']['schema']}.{$exp['config']['table']}");
    }
}

if (isset($exports['kml'])) {
    foreach ($exports['kml'] as $exp) {
        $export = new \GCExport($exp['config']['db_instance'], 'kml');
        $url = $export->export(array($exp['config']), array(
            'name' => 'export_kml',
            'add_to_zip' => &$zipFile,
            'return_url' => true,
            'fields' => $exp['extras']['fields'],
            'extent' => $exp['extras']['extent'],
            'srid' => $exp['extras']['srid'],
            'layer' => $exp['extras']['layer']
        ));

        $db->getDb()->query("DROP VIEW IF EXIST {$exp['config']['schema']}.{$exp['config']['table']}");
    }
}



$ajax->success(array('file'=> $url));
