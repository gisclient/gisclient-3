<?php

require_once __DIR__ . '/../bootstrap.php';
include_once ROOT_PATH.'lib/ajax.class.php';

use GisClient\Author\Security\User\GCUser;

$ajax = new GCAjax();

$user = new GCUser();
$user->logout();
$ajax->success();
