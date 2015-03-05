<?php
require_once('../../config/config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$filename = 'sld_' . (microtime(true)*10000).'.xml';
	file_put_contents (GC_WEB_TMP_DIR.$filename, file_get_contents('php://input', 'r'));
	echo GC_WEB_TMP_URL.$filename;	
}
