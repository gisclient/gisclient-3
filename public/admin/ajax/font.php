<?php
include_once "../../../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';
include_once ADMIN_PATH.'lib/Font.php';

$ajax = new GCAjax();

if(empty($_REQUEST['action'])) {
	$ajax->error();
}

switch($_REQUEST['action']) {
	case 'newSymbol':
		if(empty($_REQUEST['font_name'])) {
			$ajax->error('missing parameter font_name');
		} elseif (empty($_REQUEST['symbol_name'])) {
			$ajax->error('missing parameter symbol_name');
		} elseif (empty($_REQUEST['symbol_code'])) {
			$ajax->error('missing parameter symbol_code');
		} else {
			$font = new Font();
			try {
				$newSymbol = $font->newSymbol($_REQUEST['font_name'], $_REQUEST['symbol_code'], $_REQUEST['symbol_name']);
				if (false === $newSymbol) {
					$ajax->error('error insert symbol');
				}
			} catch (Exception $e) {
				$ajax->error($e->getMessage());
			}
		}
		$ajax->success();
	break;

	case 'import':
		if(empty($_REQUEST['font_file'])) {
			$ajax->error('missing parameter font_file');
		} elseif (empty($_REQUEST['file_name'])) {
			$ajax->error('missing parameter file_name');
		} else {
			$font = new Font();
			try {
				$uploadFont = $font->upload($_REQUEST['file_name'], $_REQUEST['font_file']);
				if (false === $uploadFont) {
					$ajax->error('error import font');
				}
			} catch (Exception $e) {
				$ajax->error($e->getMessage());
			}
		}
		$ajax->success();
	break;
}
