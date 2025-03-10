<?php

require_once __DIR__ . '/../../../bootstrap.php';
include_once ROOT_PATH . 'lib/ajax.class.php';
include_once ADMIN_PATH . 'lib/functions.php';

$gcService = GCService::instance();
$gcService->startSession();

$ajax = new GCAjax();


$result = [
    'steps' => 1,
    'data' => [],
    'data_objects' => [],
    'step' => 1,
    'fields' => [
        'file' => 'File',
    ],
];
$n = 0;

$path = ADMIN_PATH . 'export/';
if ($handle = opendir($path)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry == "." || $entry == "..") {
            continue;
        }
        $result['data'][$n] = [
            'file' => $entry,
        ];
        $result['data_objects'][$n] = [
            'filename' => $entry,
        ];
        $n++;
    }
    closedir($handle);
}


$ajax->success($result);
