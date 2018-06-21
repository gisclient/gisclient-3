<?php

class GCLevels {
	static private $levelsParent = array();
	static private $levelsChildren = array();
	static private $levels = array();
    static protected $config = array(
		'project'=>array('pkey'=>'project_name', 'title'=>'project_title'),
		'theme'=>array('pkey'=>'theme_id', 'title'=>'theme_title'),
		'layergroup'=>array('pkey'=>'layergroup_id', 'title'=>'layergroup_title'),
		'layer'=>array('pkey'=>'layer_id', 'title'=>'layer_title'),
		'class'=>array('pkey'=>'class_id', 'title'=>'class_title'),
        'style'=>array('pkey'=>'style_id', 'title'=>'style_name'),
        'layer_groups'=>array('pkey'=>['layer_id', 'groupname'], 'title'=>'groupname'),/*doppia chiave*/
        'field'=>array('pkey'=>'field_id', 'title'=>'field_name'),
        'field_groups'=>array('pkey'=>['field_id', 'groupname'], 'title'=>'groupname'),/*doppia chiave*/
        'relation'=>array('pkey'=>'relation_id', 'title'=>'relation_name'),
        //'qt_link'=>array('pkey'=>['layer_id', 'link_id'], 'title'=>'link_id'),/*doppia chiave*/
        'qt'=>array('pkey'=>'qt_id', 'title'=>'qt_name'),
        'qt_field'=>array('pkey'=>'qt_field_id', 'title'=>'qtfield_name'),
        'qt_relation'=>array('pkey'=>'qt_relation_id', 'title'=>'qtrelation_name')
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

		$children = array($level => array(), "title" => GCAuthor::t($level));
		//$children["title"] = GCAuthor::t($level);
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

?>
