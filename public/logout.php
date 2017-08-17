<?php

require_once __DIR__ . '/../bootstrap.php';
include_once ROOT_PATH.'lib/ajax.class.php';

$ajax = new GCAjax();

$user = new GCUser();
$user->logout();
$ajax->success();
