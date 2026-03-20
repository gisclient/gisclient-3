<?php

require_once __DIR__ . '/../../../bootstrap.php';
include_once ROOT_PATH . 'lib/ajax.class.php';
include_once ADMIN_PATH . 'lib/functions.php';

$gcService = GCService::instance();
$gcService->startSession();

$ajax = new GCAjax();

$db = GCApp::getDB();

$result = [
    'steps' => 1,
    'data' => [],
    'data_objects' => [],
    'step' => 1,
    'fields' => [
        'layer_name' => 'Layer',
        'layer_title' => 'Titolo',
    ],
];
$n = 0;

$sql = 'select layer_id, layer_name, layer_title from ' . DB_SCHEMA . '.layer inner join ' . DB_SCHEMA . '.layergroup using(layergroup_id) where theme_id = :theme';
$stmt = $db->prepare($sql);
$stmt->execute([
    'theme' => $_REQUEST['theme'],
]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $result['data'][$n] = [
        'layer_title' => $row['layer_title'],
        'layer_name' => $row['layer_name'],
    ];
    $result['data_objects'][$n] = [
        'layer_id' => $row['layer_id'],
        'fk_layer_id' => (!empty($row['layer_title']) ? $row['layer_title'] : $row['layer_name']),
    ];
    $n++;
}

$ajax->success($result);
