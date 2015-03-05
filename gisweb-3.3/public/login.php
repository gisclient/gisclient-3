<?php
require "../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';

$ajax = new GCAjax();

$user = new GCUser();

if(empty($_POST['username']) || empty($_POST['password'])) {
    $ajax->error();
}

$success = $user->login($_POST['username'], $_POST['password']);

if($success) $ajax->success();
else $ajax->error();