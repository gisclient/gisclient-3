<?php

require_once __DIR__ . '/../bootstrap.php';
require_once ROOT_PATH . 'lib/GCService.php';
include_once ROOT_PATH.'lib/ajax.class.php';

$authHandler = GCApp::getAuthenticationHandler();

$ajax = new GCAjax();

$authHandler->logout();
$ajax->success();
