<?php
require "../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';

$ajax = new GCAjax();

$user = new GCUser();

$result = array();
$result['username'] = $_SESSION['USERNAME'];
$result['groups'] = $_SESSION['GROUPS'];
	
if(!empty($result['username'])) $ajax->success($result);
else $ajax->error();
