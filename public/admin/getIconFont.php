<?php
require_once "../../config/config.php";
require_once ADMIN_PATH."lib/gcSymbol.class.php";

$smb = new Symbol('');

$code = $_REQUEST['code'];
$font = $_REQUEST['font'];

$img = $smb->createFontIcon($font, $code);

header('Content-type:image/png');
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache"); // HTTP/1.0

if ($img) {
	echo $img;
} else {
	readfile(ROOT_PATH.'public/images/warning.png');
}
