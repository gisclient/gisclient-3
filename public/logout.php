<?php

require_once __DIR__ . '/../bootstrap.php';
include_once ROOT_PATH.'lib/ajax.class.php';

$gcService = GCService::instance();
$gcService->startSession();

$authHandler = GCApp::getAuthenticationHandler();

$ajax = new GCAjax();

$authHandler->logout();
$ajax->success();
