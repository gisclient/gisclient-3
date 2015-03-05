<?php
include('config/config.php');

$db = GCApp::getDB();
$db->beginTransaction();

$sql = "select layer_id, data, data_geom, catalog_path from ".DB_SCHEMA.".layer inner join ".DB_SCHEMA.".catalog using(catalog_id)
    where data_extent is null or data_extent = ''";
foreach($db->query($sql, PDO::FETCH_ASSOC) as $row) {
    $dataDb = GCApp::getDataDB($row['catalog_path']);
    $schema = GCApp::getDataDBSchema($row['catalog_path']);
    try {
        $sql = 'select st_extent('.$row['data_geom'].') from '.$schema.'.'.$row['data'];
        $box = $dataDb->query($sql)->fetchColumn(0);
    } catch(Exception $e) {
        echo 'ERROR: '.$e->getMessage()." <br> \n";
        continue;
    }
    $extent = array();
    if(!empty($box)) {
        $extent = GCUtils::parseBox($box);
        $sql = 'update '.DB_SCHEMA.'.layer set data_extent = :extent where layer_id = :id';
        $stmt = $db->prepare($sql);
        $stmt->execute(array('extent'=>implode(' ', $extent), 'id'=>$row['layer_id']));
    }
}
$db->rollback();
