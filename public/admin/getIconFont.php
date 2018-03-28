<?php
require_once "../../config/config.php";
require_once ADMIN_PATH."lib/gcSymbol.class.php";

$smb = new Symbol('symbol');

$code = $_REQUEST['code'];
$font = $_REQUEST['font'];

if ($font) {
    $smb->filter = "font_name='" . $font . "'";
    if ($code) {
        $smb->filter .= " AND ascii_code='" . $code . "'";
    }
}
 

$img = $smb->createIcon();

header('Content-type:image/png');
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache"); // HTTP/1.0

if ($img) {
	echo $img;
} else {
	readfile(ROOT_PATH.'public/images/warning.png');
}
