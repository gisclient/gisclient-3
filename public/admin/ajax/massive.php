<?php
include_once "../../../config/config.php";
include_once ADMIN_PATH."lib/export.php";
include_once ROOT_PATH.'lib/ajax.class.php';
include_once ADMIN_PATH.'lib/functions.php';
include_once ADMIN_PATH.'lib/gcLevels.class.php';

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
