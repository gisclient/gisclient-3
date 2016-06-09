<?php
include_once "../../../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';
include_once ADMIN_PATH.'lib/functions.php';

$ajax = new GCAjax();
$db = GCApp::getDB();

if(empty($_REQUEST['action'])) $ajax->error('missin action');
if(empty($_REQUEST['level'])) $ajax->error('missing level');

switch($_REQUEST['action']) {
	case 'get-form':
		if(empty($_REQUEST['mode'])) $ajax->error('missing mode');
		
		try {
			$parentLevels = GCLevels::getParents($_REQUEST['level'], array('use_copy_limits'=>true));
		} catch(Exception $e) {
			$ajax->error($e->getMessage());
		}
		
		$filters = array();
		$parent = null;
		$hasProject = false;
		foreach($parentLevels as $level) {
			if($level == 'project') $hasProject = true;
			$filter = array(
				'level'=>$level,
				'parent'=>!empty($parent) ? $parent : ''
			);
			array_push($filters, $filter);
			$parent = $level;
		}
		if($_REQUEST['mode'] != 'move') {
			array_push($filters, array('level'=>$_REQUEST['level'], 'parent'=>!empty($level) ? $level :  null));
		}
		$ajax->success(array('filters'=>$filters, 'has_project'=>(int)$hasProject));
	break;
	case 'get-data':
		try {
			$parent = GCLevels::getParent($_REQUEST['level']);
		} catch(Exception $e) {
			$ajax->error($e->getMessage());
		}
		
		$filter = '';
		$params = array();
		if(!empty($_REQUEST['parent_id'])) {
			$parentConfig = GCLevels::getConfig($parent);
			$filter = 'where '.$parentConfig['pkey'].' = :filter_value';
			$params[':filter_value'] = $_REQUEST['parent_id'];
		}
		$levelConfig = GCLevels::getConfig($_REQUEST['level']);
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


class GCLevels {
	static private $levelsParent = array();
	static private $levelsChildren = array();
	static private $levels = array();
	static private $config = array(
		'project'=>array('pkey'=>'project_name', 'title'=>'project_title'),
		'theme'=>array('pkey'=>'theme_id', 'title'=>'theme_title'),
		'layergroup'=>array('pkey'=>'layergroup_id', 'title'=>'layergroup_title'),
		'layer'=>array('pkey'=>'layer_id', 'title'=>'layer_title'),
		'class'=>array('pkey'=>'class_id', 'title'=>'class_title'),
                'style'=>array('pkey'=>'style_id', 'title'=>'style_name')
	);
	static private $copyLimits = array(
		'theme'=>'theme',
		'layergroup'=>'theme',
		'layer'=>'theme',
		'class'=>'project',
                'style'=>'project'
	);
	
	public function getLevels() {
		if(empty(self::$levels)) self::_getLevels();
		return self::$levels;
	}
	
	public static function getChildren($level) {
		if(empty(self::$levels)) self::_getLevels();		
		
		$children = array($level => array());
		foreach(self::$levelsChildren[$level] as $childLevel) {
			array_push($children[$level], self::getChildren($childLevel));
		}
		return $children;
	}
	
	public static function getParent($level) {
		if(empty(self::$levels)) self::_getLevels();
		if(!isset(self::$levelsParent[$level])) throw new Exception('Invalid level '.$level);
		return self::$levelsParent[$level];
	}
	
	public static function getParents($level, $options = array()) {
		$defaultOptions = array(
			'use_copy_limits'=>false
		);
		$options = array_merge($defaultOptions, $options);
		
		if(empty(self::$levels)) self::_getLevels();
		if(!isset(self::$levelsParent[$level])) throw new Exception('Invalid level '.$level);
		$parents = array();
		
		$stopLevel = $options['use_copy_limits'] ? self::$copyLimits[$level] : 'root';
		
		while($level != $stopLevel && isset(self::$levelsParent[$level])) {
			array_push($parents, self::$levelsParent[$level]);
			$level = self::$levelsParent[$level];
		}
		return array_reverse($parents);
	}
	
	public static function getConfig($level = null) {
		if(empty($level)) return self::$config;
		return self::$config[$level];
	}
	
	private static function _getLevels() {
		$db = GCApp::getDB();
		
		$sql = "select id, name, coalesce(parent_id, 0) as parent_id from ".DB_SCHEMA.".e_level order by parent_id";
		$rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
		
		foreach($rows as $row) {
			self::$levels[$row['id']] = $row['name'];
		}
		foreach($rows as $row) {
			self::$levelsParent[$row['name']] = isset(self::$levels[$row['parent_id']]) ? self::$levels[$row['parent_id']] : null;
		}
		foreach($rows as $row) {
			self::$levelsChildren[$row['name']] = array_keys(self::$levelsParent, $row['name']);
		}
	}
}