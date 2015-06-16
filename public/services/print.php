<?php
require_once '../../config/config.php';
require_once ROOT_PATH.'lib/ajax.class.php';
require_once 'include/printDocument.php';
require_once 'include/mapImage.php';
require_once ROOT_PATH . 'lib/GCService.php';

$gcService = GCService::instance();
$gcService->startSession();

$ajax = new GCAjax();

if (isset($_REQUEST['format']) && $_REQUEST['format'] == 'PDF') {
	if(!file_exists(GC_FOP_LIB)) $ajax->error('fop lib does not exist');
	require_once GC_FOP_LIB;
}

try {
    $printMap = new printDocument();

	if(!empty($_REQUEST['request_type']) && $_REQUEST['request_type'] == 'get-box') {
		$box = $printMap->getBox();
		$pages = $printMap->getDimensions();
		$ajax->success(array('box'=>$box, 'pages'=>$pages));
	}

	if(!empty($_REQUEST['lang'])) {
		$printMap->setLang($_REQUEST['lang']);
	}
    if(!empty($_REQUEST['logoSx'])) $printMap->setLogo($_REQUEST['logoSx']);
	else if(defined('GC_PRINT_LOGO_SX')) $printMap->setLogo(GC_PRINT_LOGO_SX);
    if(!empty($_REQUEST['logoDx'])) $printMap->setLogo($_REQUEST['logoDx'], 'dx');
	else if(defined('GC_PRINT_LOGO_DX')) $printMap->setLogo(GC_PRINT_LOGO_DX, 'dx');

    if ($_REQUEST['format'] == 'HTML') {
        $file = $printMap->printMapHTML();
    } else if ($_REQUEST['format'] == 'PDF') {
        $TmpPath = GC_WEB_TMP_DIR;
        $file = $printMap->printMapPDF();
    }
} catch (Exception $e) {
	$ajax->error($e->getMessage());
}
$ajax->success(array('file'=>$file, 'format'=>$_REQUEST['format']));
