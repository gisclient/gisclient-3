<?php

require_once __DIR__ . '/../../../bootstrap.php';

$gcService = \GCService::instance();
$gcService->startSession();

$authHandler = GCApp::getAuthenticationHandler();
$db = \GCApp::getDB();

$stmtDelete = $db->prepare('
    DELETE FROM '.DB_SCHEMA.'.users_options
    WHERE option_key=:key AND username=:username
');

$stmtInsert = $db->prepare('
    INSERT INTO '.DB_SCHEMA.'.users_options (username, option_key, option_value)
    VALUES (:username, :key, :value)
');

$userName = $authHandler->getToken()->getUserName();
foreach(array('auto_refresh_mapfiles', 'save_to_tmp_map') as $key) {
	$value = (isset($_POST[$key]) && $_POST[$key] == 'checked');
        $gcService->set($key, $value);
	$stmtDelete->execute(array(
            'key' => $key,
            'username' => $userName
        ));
        $stmtInsert->execute(array(
            'username' => $userName,
            'key' => $key,
            'value' => $value
        ));
}

echo json_encode(array('result'=>'ok'));
