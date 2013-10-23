<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once ("../../config/config.php");
require_once ADMIN_PATH."lib/gcSymbol.class.php";

$smb=new Symbol($_REQUEST['table']);

if($smb->table == 'class') {
    $smb->filter="class.class_id=".$_REQUEST['id'];
} else if($smb->table == 'symbol') {
    $smb->filter="symbol.symbol_name = '".urldecode($_REQUEST['id'])."'";
}

$img = $smb->createIcon();
header('Content-type:image/png');
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header ("Pragma: no-cache"); // HTTP/1.0
if ($img) {
	echo $img;
} else {
	readfile(ROOT_PATH.'public/images/warning.png');
}