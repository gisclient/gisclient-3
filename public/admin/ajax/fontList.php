<?php

use GisClient\Author\Security\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../../../bootstrap.php';
include_once ROOT_PATH . 'lib/ajax.class.php';

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
$dbSchema = DB_SCHEMA;

$font = 'r3-map-symbols.ttf';
$fontName = basename($font, '.ttf');

$result = [
    'steps' => 1,
    'data' => [],
    'data_objects' => [],
    'step' => 1,
];

$result['fields'] = [
    'image' => GCAuthor::t('image'),
    'symbol' => GCAuthor::t('symbol'),
    'code' => GCAuthor::t('code'),
    'name' => GCAuthor::t('name'),
];

for ($i = 33; $i <= 126; $i++) {
    $sql = "SELECT symbol_name FROM $dbSchema.symbol WHERE symbol_def LIKE :like";
    $like = '%FONT "' . $fontName . '"%CHARACTER "&#' . $i . ';"';
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':like' => $like,
    ]);
    $name = $stmt->fetchColumn();

    $result['data'][] = [
        'image' => '',
        'symbol' => chr($i),
        'code' => $i,
        'name' => '<input type="text" value="' . $name . '" name="char' . $i . '" style="text-transform:uppercase;">',
    ];
}

$ajax->success($result);
