<?php
include_once "../../../config/config.php";
include_once ADMIN_PATH."lib/export.php";
include_once ROOT_PATH.'lib/ajax.class.php';
include_once ADMIN_PATH.'lib/functions.php';

$ajax = new GCAjax();
$db = GCApp::getDB();

if(empty($_REQUEST['action'])) $ajax->error('missin action');
if(empty($_REQUEST['level'])) $ajax->error('missing level');

switch($_REQUEST['action']) {
  case 'simulate':
  case 'preview':
    $isPreview = (strcmp('preview',$_REQUEST['action'])==0);
    if(empty($_REQUEST['id'])) $ajax->error('missing id');
    if(empty($_REQUEST['searchName'])) $ajax->error('missing search name');
    //if($isPreview && empty($_REQUEST['field'])) $ajax->error('missing search attribute');
    $pk=_getPKeys();
    $currKey = $pk["pkey"][$_REQUEST["searchName"]];
    $flt = array();
    $indexes = explode(" ", $_REQUEST["id"]);
    //se search e level sono diversi, mettere il gioco delle tabelle
    $selectElements = array();
    $sql = "select ".($isPreview ? "" : "count(*)");
    if($isPreview) {
      $levelConfig = GCLevels::getConfig($_REQUEST['level']);
      $selectElements[] = ($_REQUEST['level'].".".$_REQUEST['field']);
      $selectElements[] = ($_REQUEST['level'].".".$levelConfig['title']);
    }
    if(strcmp($_REQUEST["searchName"], $_REQUEST['level']) == 0)
      $fromCondition = DB_SCHEMA.".".$_REQUEST['level'];
    else {
      //search è sicuramente minore di level...
      $fromCondition = DB_SCHEMA.".".$_REQUEST['level']." ".$_REQUEST['level'];
      $aux = $_REQUEST['level'];
      $parents = array_reverse(GCLevels::getParents($_REQUEST['level']));
      foreach ($parents as $singleParent) {
        if($isPreview) {
          $levelConfig = GCLevels::getConfig($singleParent);
          $selectElements[] = ($singleParent.".".$levelConfig['title']);
        }
        $auxKey = $pk["pkey"][$singleParent];
        $fromCondition .= " join ".DB_SCHEMA.".".$singleParent." ".$singleParent." on (";
        foreach($pk["pkey"][$singleParent] as $singleKey)
          $fromCondition .= "$aux.$singleKey=$singleParent.$singleKey AND";
        $fromCondition = substr_replace($fromCondition, "", strrpos($fromCondition, "AND"), strlen("AND")).")";
        $aux=$singleParent;
        if(strcmp($singleParent, $_REQUEST['searchName']) == 0)
          break;
      }
    }
    $sql .= empty($selectElements) ? "" : implode(",",$selectElements);
    $sql.= " from ".$fromCondition." where ";
    for($i = 0; $i < count($currKey); $i++)
      $flt[] = $_REQUEST["searchName"].".".$currKey[$i]."='".$indexes[$i]."'";
    $sql .= implode(" AND ",$flt);
    if(!empty($_REQUEST["clause"])) {
      $sql .= " AND ".$_REQUEST["clause"];
    }
    $result = "";
    try {
      $result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
	} catch(Exception $e) {
	  $ajax->error($e->getMessage());
	}
    $ajax->success(array('fields'=>$selectElements,'data'=>$result,'sql'=>$sql));
    break;
  case 'load':
    if(empty($_REQUEST['id'])) $ajax->error('missing id');
    try {
      $childrenLevels = GCLevels::getChildren($_REQUEST['level']);
    } catch(Exception $e) {
      $ajax->error($e->getMessage());
    }
    print_r(json_encode($childrenLevels));
    break;
  case 'load-fields':
    $sql = "select column_name from information_schema.columns where table_schema='".DB_SCHEMA."' and table_name='".$_REQUEST['level']."' and is_updatable = 'YES' order by column_name";
	$ajax->success(array('data'=>$db->query($sql)->fetchAll(PDO::FETCH_ASSOC)));
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
      //possono essere due....
      $key = is_array($parentConfig['pkey']) ? $parentConfig['pkey'][0] : $parentConfig['pkey'];
      $filter = 'where '.$key.' = :filter_value';
	  $params[':filter_value'] = $_REQUEST['parent_id'];
	}
	$levelConfig = GCLevels::getConfig($_REQUEST['level']);
	$data = array();
    if(isset($levelConfig['pkey']) && $levelConfig['title']) {
      $key = is_array($levelConfig['pkey']) ? ($levelConfig['pkey'][0]." || ' ' || ".$levelConfig['pkey'][1]) : $levelConfig['pkey'];
      
      $sql = "select ".$key." as key, ".$levelConfig['title']." as value ".
	    " from ".DB_SCHEMA.".".$_REQUEST['level']." ".$filter;
	  $stmt = $db->prepare($sql);
	  $stmt->execute($params);
	  while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	    $data[$row['key']] = $row['value'];
	  }
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
