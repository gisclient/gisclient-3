<?php
include_once "../../../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';

$ajax = new GCAjax();
$user = new GCUser();

if(empty($_REQUEST['action'])) $ajax->error();

switch($_REQUEST['action']) {
	case 'refresh':
		if(empty($_REQUEST['target'])) $ajax->error(1);
		if(empty($_REQUEST['project'])) $ajax->error(2);
		if(defined('PROJECT_MAPFILE') && PROJECT_MAPFILE){
			$user->authGCService(array('project_name' => $_REQUEST['project']));
            GCAuthor::refreshProjectMapfile($_REQUEST['project'], ($_REQUEST['target'] == 'public'));
        } else {
            if(empty($_REQUEST['mapset'])) {
				$user->authGCService(array('project_name' => $_REQUEST['project']));
                GCAuthor::refreshMapfiles($_REQUEST['project'], ($_REQUEST['target'] == 'public'));
            } else {
				$user->authGCService(array('mapset_name' => $_REQUEST['mapset']));
                GCAuthor::refreshMapfile($_REQUEST['project'], $_REQUEST['mapset'], ($_REQUEST['target'] == 'public'));
				print_debug($_SESSION['GISCLIENT_USER_LAYER'], null, 'system');
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
