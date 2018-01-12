<?php
include_once "../../../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';
include_once ADMIN_PATH."lib/functions.php";

$ajax = new GCAjax();

if(empty($_REQUEST['action'])) $ajax->error();

switch($_REQUEST['action']) {
  case 'compile':
    if(empty($_REQUEST['target'])) $ajax->error(1);
    if(empty($_REQUEST['project'])) $ajax->error(2);
    $publicTarget = (bool)($_REQUEST['target'] == 'public');
    //Project_Mapfile non viene definito da nessuna parte... per ora tengo vuoto il metodo
    if(defined('PROJECT_MAPFILE') && PROJECT_MAPFILE){
      GCAuthor::compileProjectMapfile($_REQUEST['project'], $publicTarget);
    } else if(empty($_REQUEST['mapset'])) {
      GCAuthor::compileMapfiles($_REQUEST['project'], $publicTarget);
    } else {
      GCAuthor::compileMapfile($_REQUEST['project'], $_REQUEST['mapset'], $publicTarget);
    }
    $errors = GCError::get();
    if(!empty($errors)) {
      $ajax->error(array('type'=>'mapfile_errors', 'text'=>prepareOutputForError($errors)));
    } else
      $ajax->success();
    break;
}
