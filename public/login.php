<?php

require_once __DIR__ . '/../bootstrap.php';
include_once ROOT_PATH.'lib/ajax.class.php';

use GisClient\Author\Security\User\GCUser;

$ajax = new GCAjax();

$user = new GCUser();

if(empty($_POST['username']) || empty($_POST['password'])) {
    $ajax->error();
}

$success = $user->login($_POST['username'], $_POST['password']);

if($success) $ajax->success();
else $ajax->error();