<?php
include_once "../../../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';

$ajax = new GCAjax();

if(empty($_REQUEST['action'])) $ajax->error();

switch($_REQUEST['action']) {
	case 'refresh':
		if(empty($_REQUEST['target'])) $ajax->error(1);
		if(empty($_REQUEST['project'])) $ajax->error(2);
        if(defined('GEOSERVER_URL') && GEOSERVER_URL){
            // GeoServer support
            require_once ROOT_PATH."lib/geoserver/vendor/autoload.php";
            $geoServerWrapper = new GisClient\GeoServer\Author2Geoserver();
            $geoServerWrapper->setLogger(new GisClient\Logger\AuthorLogger ());
            $geoServerWrapper->sync($_REQUEST['project'], $_REQUEST['mapset'], $_REQUEST['target'] == 'public');
		}else if(defined('PROJECT_MAPFILE') && PROJECT_MAPFILE){
            GCAuthor::refreshProjectMapfile($_REQUEST['project'], ($_REQUEST['target'] == 'public'));
        } else {
            $refreshLayerMapfile = defined('ENABLE_OGC_SINGLE_LAYER_WMS') && ENABLE_OGC_SINGLE_LAYER_WMS === true;
            $publish = $_REQUEST['target'] == 'public';
            if(empty($_REQUEST['mapset'])) {
                GCAuthor::refreshMapfiles($_REQUEST['project'], $publish, $refreshLayerMapfile);
            } else {
                GCAuthor::refreshMapfile($_REQUEST['project'], $_REQUEST['mapset'], $publish, $refreshLayerMapfile);
            }
        }
		$errors = GCError::get();
		if(!empty($errors)) {
			foreach($errors as &$error) $error = str_replace(array('"', "\n"), array('\"', '<br>'), $error);
			unset($error);
			$ajax->error(array('type'=>'mapfile_errors', 'text'=>implode('<br>', $errors)));
		}
		$ajax->success();
	break;
}