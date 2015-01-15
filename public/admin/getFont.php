<?php
require_once "../../config/config.php";
require_once ADMIN_PATH."lib/Font.php";

$fontName = basename($_REQUEST['font']);
$file = ROOT_PATH . 'fonts/' . $fontName;

if (is_readable($file)) {
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename='.basename($file));
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Length: ' . filesize($file));
	readfile($file);
	exit;
} else {
	echo('not found');
}
