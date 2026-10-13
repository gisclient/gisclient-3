<?php

require_once __DIR__ . '/../../bootstrap.php';
require_once ROOT_PATH . 'lib/ajax.class.php';

use GisClient\Author\Utils\PrintDocument;
use GisClient\Author\Utils\ProjectPrintLogoProvider;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

$gcService = GCService::instance();
$gcService->startSession();

$inputJSONText = file_get_contents('php://input');
if (($data = json_decode($inputJSONText, true)) !== null) {
    $_REQUEST = $data;
}

$ajax = new GCAjax();

if (isset($_REQUEST['format']) && $_REQUEST['format'] == 'PDF') {
    if (!file_exists(GC_FOP_LIB)) {
        $ajax->error('fop lib does not exist');
    }
    require_once GC_FOP_LIB;
}

try {
    $printMap = new PrintDocument(rtrim(PUBLIC_URL, '/'));

    if (!empty($_REQUEST['request_type']) && $_REQUEST['request_type'] == 'get-box') {
        $box = $printMap->getBox();
        $pages = $printMap->getDimensions();
        $ajax->success([
            'box' => $box,
            'pages' => $pages,
        ]);
    }

    if (!empty($_REQUEST['lang'])) {
        $printMap->setLang($_REQUEST['lang']);
    }
    $projectLogos = [];
    $projectName = $printMap->getProjectName();
    if ($projectName !== null) {
        // Senza loghi di progetto la stampa continua con quelli da env o di default
        try {
            $projectLogos = (new ProjectPrintLogoProvider(GCApp::getDB()))->getDataUris($projectName);
        } catch (Exception $e) {
            error_log('Could not read print logos of project ' . $projectName . ': ' . $e->getMessage());
        }
    }
    if (!empty($_REQUEST['logoSx'])) {
        $printMap->setLogo($_REQUEST['logoSx']);
    } elseif (isset($projectLogos['sx'])) {
        $printMap->setLogo($projectLogos['sx']);
    } elseif (defined('GC_PRINT_LOGO_SX')) {
        $printMap->setLogo(str_replace(PUBLIC_URL, INTERNAL_URL, GC_PRINT_LOGO_SX));
    }
    if (!empty($_REQUEST['logoDx'])) {
        $printMap->setLogo($_REQUEST['logoDx'], 'dx');
    } elseif (isset($projectLogos['dx'])) {
        $printMap->setLogo($projectLogos['dx'], 'dx');
    } elseif (defined('GC_PRINT_LOGO_DX')) {
        $printMap->setLogo(str_replace(PUBLIC_URL, INTERNAL_URL, GC_PRINT_LOGO_DX), 'dx');
    }

    if ($_REQUEST['format'] == 'HTML') {
        $file = $printMap->printMapHTML();
    } elseif ($_REQUEST['format'] == 'PDF') {
        $file = $printMap->printMapPDF();
    }
} catch (Exception $e) {
    $ajax->error($e->getMessage());
}
$ajax->success([
    'file' => $file ?? null,
    'format' => $_REQUEST['format'],
]);
