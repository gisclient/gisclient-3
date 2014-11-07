<?php
include_once "../../../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';
include_once ADMIN_PATH.'lib/gcSymbol.class.php';

$ajax = new GCAjax();

if(empty($_REQUEST['action'])) {
	$ajax->error();
}

switch($_REQUEST['action']) {
	case 'delete':
		if(empty($_REQUEST['symbol_name'])) {
			$ajax->error('missing parameter symbol_name');
		} else {
			$smb = new Symbol('symbol');
			$removedSymbols = $smb->removeByName($_REQUEST['symbol_name']);
			if ($removedSymbols != 1) {
				$ajax->error("removed $removedSymbols symbols");
			}
		}
		$ajax->success();
	break;
}