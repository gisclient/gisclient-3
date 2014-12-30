<?php
include_once "../../../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';
include_once ADMIN_PATH.'lib/gcSymbol.class.php';
include_once ADMIN_PATH.'lib/PixmapSymbol.php';

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

	case 'import':
		if(empty($_REQUEST['symbol_file'])) {
			$ajax->error('missing parameter symbol_file');
		} elseif (empty($_REQUEST['file_name'])) {
			$ajax->error('missing parameter file_name');
		} else {
			$pixmap = new PixmapSymbol();
			try {
				$insertSymbol = $pixmap->upload($_REQUEST['file_name'], $_REQUEST['symbol_file']);
				if (false === $insertSymbol) {
					$ajax->error('error insert symbol');
				}
			} catch (Exception $e) {
				$ajax->error($e->getMessage());
			}
		}
		$ajax->success();

	break;
}