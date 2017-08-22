<?php

require_once __DIR__ . '/../bootstrap.php';
require_once ROOT_PATH . 'lib/GCService.php';
include_once ROOT_PATH.'lib/ajax.class.php';

$gcService = GCService::instance();
$gcService->startSession();

$authHandler = GCApp::getAuthenticationHandler();

$ajax = new GCAjax();

if(empty($_POST['username']) || empty($_POST['password'])) {
    $ajax->error();
}

$authHandler->login($_POST['username'], $_POST['password']);

if ($authHandler->isAuthenticated()) {
    $ajax->success();
} else {
    $ajax->error();
}
