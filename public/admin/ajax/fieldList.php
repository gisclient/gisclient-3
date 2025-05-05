<?php

use GisClient\Author\Security\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../../../bootstrap.php';
include_once ROOT_PATH . 'lib/ajax.class.php';
include_once ADMIN_PATH . 'lib/functions.php';

$gcService = GCService::instance();
$gcService->startSession();

$authHandler = GCApp::getAuthenticationHandler();
$isAuthenticated = $authHandler->isAuthenticated();

// user does not have an open session, try to log in
if (!$isAuthenticated) {
    $authHandler->login(Request::createFromGlobals());
    $isAuthenticated = $authHandler->isAuthenticated();
}

$user = $authHandler->getToken()->getUser();
$isAdmin = $isAuthenticated && $user instanceof UserInterface && $user->isAdmin();

// user could not even log in, send correct headers and exit
if (!$isAuthenticated) {
    print_debug('unauthorized access', null, 'system');
    header('WWW-Authenticate: Basic realm="Gisclient"');
    header('HTTP/1.1 401 Unauthorized');
    echo "<h1>Authorization required</h1>";
    exit(0);
} elseif (!$isAdmin) {
    print_debug('unauthorized access', null, 'system');
    header('HTTP/1.1 403 Forbidden');
    echo "<h1>Forbidden</h1>";
    exit(0);
}

$ajax = new GCAjax();

$db = GCApp::getDB();

if (empty($_REQUEST['selectedField'])) {
    $ajax->error('field');
}
$selectedField = $_REQUEST['selectedField'];

if (!empty($_REQUEST['relation_id'])) {
    $relationId = $_REQUEST['relation_id'];
} elseif (!empty($_REQUEST['layer'])) {
    $layerId = $_REQUEST['layer'];
} else {
    if (empty($_REQUEST['catalog_id']) || !is_numeric($_REQUEST['catalog_id']) || $_REQUEST['catalog_id'] < 1) {
        $ajax->error('catalog_id');
    }
    $catalogId = $_REQUEST['catalog_id'];

    if (!empty($_REQUEST['data'])) {
        $data = $_REQUEST['data'];
    } elseif (!empty($_REQUEST['table_name'])) {
        $data = $_REQUEST['table_name'];
    } else {
        $ajax->error('data');
    }
}

$result = [
    'steps' => 1,
    'data' => [],
    'data_objects' => [],
    'step' => 1,
    'fields' => [
        'field' => GCAuthor::t('field'),
    ],
];
$n = 0;

if (!empty($relationId)) {
    $sql = "select catalog_path, connection_type, relation.table_name from " . DB_SCHEMA . ".relation left join " . DB_SCHEMA . ".catalog  USING (catalog_id) where relation_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$relationId]);
    $catalogData = $stmt->fetch(PDO::FETCH_ASSOC);
    $data = $catalogData['table_name'];
} elseif (!empty($layerId)) {
    $sql = "select catalog_path, connection_type, layer.data from " . DB_SCHEMA . ".layer left join " . DB_SCHEMA . ".catalog USING (catalog_id) where layer_id=?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$layerId]);
    $catalogData = $stmt->fetch(PDO::FETCH_ASSOC);
    $data = $catalogData['data'];
} else {
    $sql = "select catalog_path,connection_type from " . DB_SCHEMA . ".catalog  where catalog_id=?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$catalogId]);
    $catalogData = $stmt->fetch(PDO::FETCH_ASSOC);
}
if ($catalogData['connection_type'] != 6) {
    $ajax->error('not implemented');
}

[, $schema] = connAdminInfofromPath($catalogData["catalog_path"]);

$dataDb = GCApp::getDataDB($catalogData['catalog_path']);
$sql = "SELECT column_name from information_schema.columns " .
        "WHERE table_schema=:schema AND table_name=:table " .
        " ORDER BY ordinal_position";
$stmt = $dataDb->prepare($sql);
$stmt->execute([
    ':schema' => $schema,
    ':table' => $data,
]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $result['data'][$n] = [
        'field' => $row['column_name'],
    ];
    $result['data_objects'][$n] = [
        $selectedField => $row['column_name'],
    ];
    $n++;
}

$ajax->success($result);
