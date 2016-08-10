<?php
include_once "../../../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';
include_once ADMIN_PATH.'lib/Font.php';

$ajax = new GCAjax();

if(empty($_REQUEST['action'])) {
	$ajax->error();
}

switch($_REQUEST['action']) {
	case 'saveFontSymbols': 
		if(empty($_REQUEST['font_name'])) {
			$ajax->error('missing parameter font_name');
		} elseif(empty($_REQUEST['symbols'])) {
			$ajax->error('missing symbols');
		} else {
			$font_name = $_REQUEST['font_name'];
			$symbols = $_REQUEST['symbols'];
			for ($i=0; $i < count($symbols); $i++) {
				if (empty($symbols[$i]['action'])) {
					$ajax->error('missing parameter action');
				} elseif (empty($symbols[$i]['symbol_code'])) {
					$ajax->error('missing parameter symbol_code');
				} else {
					if ($symbols[$i]['action'] == 'new') {
						if (empty($symbols[$i]['symbol_name'])) {
							$ajax->error('missing parameter symbol_name');
						} else {
							$font = new Font();
							try {
								$newSymbol = $font->newSymbol($font_name, $symbols[$i]['symbol_code'], $symbols[$i]['symbol_name']);
								if (false === $newSymbol) {
									$ajax->error('error insert symbol');
								}
							} catch (Exception $e) {
								$ajax->error($e->getMessage());
							}
						}
					} elseif ($symbols[$i]['action'] == 'del') {
						$font = new Font();
						try {
							$removeSymbol = $font->removeSymbol($font_name, $symbols[$i]['symbol_code']);
							if (false === $removeSymbol) {
								$ajax->error('error remove symbol');
							}
						} catch (Exception $e) {
							$ajax->error($e->getMessage());
						}
					} else {
						$ajax->error('invalid action');
					}
				}
			}
			$ajax->success();
		}
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
