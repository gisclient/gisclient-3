<?php
require_once('../../config/config.php');
require_once ROOT_PATH.'lib/ajax.class.php';
$ajax = new GCAjax();
/*
input array
array(
	'action'=>'list', // create, delete, get
	'mapset'=>'mapset',
	'context'=>object // se create
	'id'=>int // se delete o get
);

output
jsonArray(
	'result'=>string // ok o error
	'context'=>object // se get
)
*/

if(empty($_REQUEST['action']) || !in_array($_REQUEST['action'], array('list', 'create', 'replace', 'delete', 'get'))) {
    $ajax->error('Invalid action');
}

$db = GCApp::getDB();
$user = new GCUser();

if($_REQUEST['action'] != 'get') {
	if($_REQUEST['action'] != 'delete') {
		if(empty($_REQUEST['mapset'])) $ajax->error('Empty mapset');
	}
	//$username = getUsername();
    if(!$user->isAuthenticated()) $ajax->error('Permission denied');
}

switch($_REQUEST['action']) {
	case 'list':
		$sql = "select usercontext_id as id, title from ".DB_SCHEMA.".usercontext where username=:username and mapset_name=:mapset order by id desc";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':username'=>$user->getUsername(), ':mapset'=>$_REQUEST['mapset']));
		
		$contextes = array();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) array_push($contextes, $row);
        $ajax->success(array('contextes'=>$contextes));
	break;

	case 'replace':	
	case 'create':
		if(empty($_REQUEST['context'])) $ajax->error('Empty context');
        $username = $user->getUsername();
        if(empty($username)) $ajax->error('Permission denied');
		$context = json_encode($_REQUEST['context']);
		if($_REQUEST['action'] == 'replace') {
			$sql = 'delete from '.DB_SCHEMA.'.usercontext where username=?';
			$db->prepare($sql)->execute(array($username));
		}
		
		$sql = "insert into ".DB_SCHEMA.".usercontext (username, mapset_name, title, context) ".
			" values (:username, :mapset, :title, :context) ";
		try {
			$stmt = $db->prepare($sql);
			$stmt->execute(array(':username'=>$username, ':mapset'=>$_REQUEST['mapset'], ':title'=>$_REQUEST['title'], ':context'=>$context));
		} catch(Exception $e) {
            $ajax->error($e->getMessage());
		}
        $ajax->success();
	break;
	
	case 'delete':
		if(empty($_REQUEST['id'])) $ajax->error('Empty id');
		
		$sql = "select username from ".DB_SCHEMA.".usercontext where usercontext_id=:id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':id'=>$_REQUEST['id']));
		if($stmt->fetchColumn(0) != $username) $ajax->error('Permission denied');
		
		$sql = "delete from ".DB_SCHEMA.".usercontext where usercontext_id=:id";
		try {
			$stmt = $db->prepare($sql);
			$stmt->execute(array(':id'=>$_REQUEST['id']));
		} catch(Exception $e) {
            $ajax->error($e->getMessage());
		}
        $ajax->success();
	break;
	
	case 'get':
		$field = 'usercontext_id';
		if(empty($_REQUEST['id'])) {
			$field = 'username';
			$param = $user->getUsername();
            if(!$param) $ajax->success(array('context'=>array()));
		} else {
			$param = $_REQUEST['id'];
		}
		$sql = "select mapset_name, title, context from ".DB_SCHEMA.".usercontext where $field = ?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($param));
        if($stmt->rowCount() == 0) $ajax->success(array('context'=>array()));
		
		$context = $stmt->fetch(PDO::FETCH_ASSOC);
		$context['context'] = json_decode($context['context']);
		$ajax->success(array('context'=>$context['context'], 'title'=>$context['title'], 'mapset'=>$context['mapset_name']));
	break;
}

