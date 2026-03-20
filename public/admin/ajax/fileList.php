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


$result = [
    'steps' => 1,
    'data' => [],
    'data_objects' => [],
    'step' => 1,
    'fields' => [
        'file' => 'File',
    ],
];
$n = 0;

$path = ADMIN_PATH . 'export/';
if ($handle = opendir($path)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry == "." || $entry == "..") {
            continue;
        }
        $result['data'][$n] = [
            'file' => $entry,
        ];
        $result['data_objects'][$n] = [
            'filename' => $entry,
        ];
        $n++;
    }
    closedir($handle);
}


$ajax->success($result);
