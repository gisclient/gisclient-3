<?php
include_once "../../../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';
include_once ADMIN_PATH.'lib/functions.php';
include_once ADMIN_PATH.'lib/gcLevels.class.php';

class GCCopyLevels extends GCLevels {
	static protected $config = array(
		'project'=>array('pkey'=>'project_name', 'title'=>'project_title'),
		'theme'=>array('pkey'=>'theme_id', 'title'=>'theme_title'),
		'layergroup'=>array('pkey'=>'layergroup_id', 'title'=>'layergroup_title'),
		'layer'=>array('pkey'=>'layer_id', 'title'=>'layer_title'),
		'class'=>array('pkey'=>'class_id', 'title'=>'class_title'),
        'style'=>array('pkey'=>'style_id', 'title'=>'style_name')
	);
}

$ajax = new GCAjax();
$db = GCApp::getDB();

if(empty($_REQUEST['action'])) $ajax->error('missin action');
if(empty($_REQUEST['level'])) $ajax->error('missing level');

switch($_REQUEST['action']) {
	case 'get-form':
		if(empty($_REQUEST['mode'])) $ajax->error('missing mode');
		
		try {
			$parentLevels = GCCopyLevels::getParents($_REQUEST['level'], array('use_copy_limits'=>true));
		} catch(Exception $e) {
			$ajax->error($e->getMessage());
		}
		
		$filters = array();
		$parent = null;
		$hasProject = false;
		foreach($parentLevels as $level) {
			if($level == 'project') $hasProject = true;
			$filter = array(
                'label'=>GCAuthor::t($level),
				'level'=>$level,
				'parent'=>!empty($parent) ? $parent : ''
			);
			array_push($filters, $filter);
			$parent = $level;
		}
		if($_REQUEST['mode'] != 'move') {
			array_push($filters, array('label'=>GCAuthor::t($_REQUEST['level']), 'level'=>$_REQUEST['level'], 'parent'=>!empty($level) ? $level :  null));
		}
		$ajax->success(array('title'=>GCAuthor::t($_REQUEST['level']), 'filters'=>$filters, 'has_project'=>(int)$hasProject));
	break;
	case 'get-data':
		try {
			$parent = GCCopyLevels::getParent($_REQUEST['level']);
		} catch(Exception $e) {
			$ajax->error($e->getMessage());
		}
		
		$filter = '';
		$params = array();
		if(!empty($_REQUEST['parent_id'])) {
			$parentConfig = GCCopyLevels::getConfig($parent);
			$filter = 'where '.$parentConfig['pkey'].' = :filter_value';
			$params[':filter_value'] = $_REQUEST['parent_id'];
		}
		$levelConfig = GCCopyLevels::getConfig($_REQUEST['level']);
		$sql = "select ".$levelConfig['pkey']." as key, ".$levelConfig['title']." as value ".
			" from ".DB_SCHEMA.".".$_REQUEST['level']." ".$filter;
		$stmt = $db->prepare($sql);
		$stmt->execute($params);
		$data = array();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$data[$row['key']] = $row['value'];
		}
		$ajax->success(array('data'=>$data));
	break;
}
?>
