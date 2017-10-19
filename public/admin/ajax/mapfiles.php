<?php

require_once __DIR__ . '/../../../bootstrap.php';
include_once ROOT_PATH . 'lib/ajax.class.php';

$ajax = new GCAjax();

if (empty($_REQUEST['action'])) {
    http_response_code(400);
    $ajax->error("Missing parameter 'action'");
}

switch ($_REQUEST['action']) {
    case 'refresh':
        if (empty($_REQUEST['target'])) {
            http_response_code(400);
            $ajax->error("Missing parameter 'target'");
        }
        if (empty($_REQUEST['project'])) {
            http_response_code(400);
            $ajax->error("Missing parameter 'project'");
        }
        if (defined('PROJECT_MAPFILE') && PROJECT_MAPFILE) {
            GCAuthor::refreshProjectMapfile($_REQUEST['project'], ($_REQUEST['target'] == 'public'));
        } else {
            $refreshLayerMapfile = defined('ENABLE_OGC_SINGLE_LAYER_WMS') && ENABLE_OGC_SINGLE_LAYER_WMS === true;
            $publish = $_REQUEST['target'] == 'public';
            if (empty($_REQUEST['mapset'])) {
                GCAuthor::refreshMapfiles($_REQUEST['project'], $publish, $refreshLayerMapfile);
            } else {
                GCAuthor::refreshMapfile($_REQUEST['project'], $_REQUEST['mapset'], $publish, $refreshLayerMapfile);
            }
        }
        $errors = GCError::get();
        if (!empty($errors)) {
            foreach ($errors as $e => $error) {
                $errors[$e] = str_replace(array('"', "\n"), array('\"', '<br>'), $error);
            }
            
            http_response_code(500);
            $ajax->error(array(
                'type' => 'mapfile_errors',
                'text' => implode('<br>', $errors)
            ));
        }
        $ajax->success();
        break;
    default:
        http_response_code(400);
        $ajax->error(sprtinf('Unknown action "%s".', $_REQUEST['action']));
}
