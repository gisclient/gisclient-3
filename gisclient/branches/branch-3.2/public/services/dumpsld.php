<?php
//CREAZIONE DI UN FILE SLD TEMPORANEO   !!!!!!!!!!!!!!!!!!!!RIEDERE!!!!!!!!!!!!!!!!!!!!
//PRENDO IL PATH DA CONFIG?????? O LO PASSO????
//ATTENZIONE https

define('IMAGE_PATH','/ms4w/tmp/ms_tmp/');
define('IMAGE_URL','/ms_tmp/');
define('HOST','http://127.0.0.1');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$filename = 'sld_' . (microtime(true)*10000).'.xml';
	file_put_contents (IMAGE_PATH.$filename,file_get_contents('php://input', 'r'));
	echo HOST.IMAGE_URL.$filename;	
}

?>