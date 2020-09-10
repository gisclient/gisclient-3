<?php

require_once('../../config/config.php');
require_once (ROOT_PATH.'lib/functions.php');
require_once ROOT_PATH . 'lib/GCService.php';

function parseCondition($condExpr) {
	if (!is_array($condExpr))
		return FALSE;
	if (empty($condExpr['operator']) || empty($condExpr['expressions']))
		return FALSE;

	$opLogic = ' ' . strtoupper($condExpr['operator']) . ' ';
	$retFilter = '';

	foreach ($condExpr['expressions'] as $expression) {
		$field = $expression['colval'];
		$op = $expression['opval'];
		$value = $expression['val'];
		if (strlen($retFilter) > 0)
			$retFilter .= $opLogic;
		switch ($op) {
			case equalto:
			$retFilter .= "('[$field]'='$value')";
			break;
        	case notequalto:
			$retFilter .= "('[$field]'!='$value')";
			break;
        	case contains:
			$retFilter .= "('[$field]'~'$value')";
			break;
        	case startswith:
			$retFilter .= "('[$field]'~'^$value')";
			break;
        	case endswith:
			$retFilter .= "('[$field]'~'$value$')";
			break;
        	case isnull:
			$retFilter .= "(length('[$field]')=0)";
			break;
        	case isnotnull:
			$retFilter .= "(length('[$field]')>0)";
			break;
        	case lessthan:
			$retFilter .= "('[$field]'<'$value')";
			break;
        	case greaterthan:
			$retFilter .= "('[$field]'>'$value')";
			break;
		}
	}

	foreach ($condExpr['nestedexpressions'] as $innerExpr) {
		$retN = parseCondition($innerExpr);
		if ($retN !== FALSE)
			$retFilter .= $opLogic . $retN;
	}

	if (count($condExpr['expressions']) > 1 || count($condExpr['nestedexpressions']) > 0)
		$retFilter = '(' . $retFilter . ')';

	return $retFilter;
}

$gcService = GCService::instance();
$gcService->startSession(true);

if(!defined('GC_SESSION_NAME')) {
	throw new Exception('Undefined GC_SESSION_NAME in config');
}

if (!isset($_SESSION['GC_LAYER_FILTERS'])) {
	$_SESSION['GC_LAYER_FILTERS'] = array();
	$_SESSION['GC_LAYER_FILTERS_C'] = array();
}

if (empty($_REQUEST['mapsetName']))
    die(json_encode(array('error' => 200, 'message' => 'No mapset name')));
$mapsetName = $_REQUEST['mapsetName'];

$response = array('result' => 'ok');
$response['filters'] = array();
$response['conditions'] = array();

if ($_REQUEST['action'] == 'set') {
	if (empty($_REQUEST['featureType']))
	    die(json_encode(array('error' => 200, 'message' => 'No layer name')));
	$featureTypes = explode(',', $_REQUEST['featureType']);
	$db = GCApp::getDB();
	foreach ($featureTypes as $featureType) {
		$sql = 'select layer_id from '.DB_SCHEMA.'.layer
		    inner join '.DB_SCHEMA.'.layergroup on layer.layergroup_id = layergroup.layergroup_id
		    inner join '.DB_SCHEMA.'.mapset_layergroup on mapset_layergroup.layergroup_id = layergroup.layergroup_id
		    where mapset_name = :mapset_name and layergroup_name = :layergroup_name and layer_name = :layer_name';
		$stmt = $db->prepare($sql);

		list($layergroupName, $layerName) = explode('.', $featureType);

		$stmt->execute(array(
		    'mapset_name'=>$mapsetName,
		    'layergroup_name'=>$layergroupName,
		    'layer_name'=>$layerName
		));

		$res = $stmt->fetchAll();
		if (count($res) < 1)
		    die(json_encode(array('error' => 200, 'message' => 'Layer '.$featureType.' not found in mapset '.$mapsetName)));

		if (!isset($_SESSION['GC_LAYER_FILTERS'][$mapsetName])) {
		    $_SESSION['GC_LAYER_FILTERS'][$mapsetName] = array();
			$_SESSION['GC_LAYER_FILTERS_C'][$mapsetName] = array();
		}

		if (isset($_REQUEST['filter'])) {
		    $_SESSION['GC_LAYER_FILTERS'][$mapsetName][$featureType] = $_REQUEST['filter'];
			$response['filters'][$featureType] = $_REQUEST['filter'];
		}
		else if (isset($_REQUEST['condition'])) {
			$condition = json_decode($_REQUEST['condition'], true);
			$res = parseCondition($condition);
			if ($res === FALSE) {
				die(json_encode(array('error' => 200, 'message' => 'Error parsing condition')));
			}
			$_SESSION['GC_LAYER_FILTERS'][$mapsetName][$featureType] = $res;
			$_SESSION['GC_LAYER_FILTERS_C'][$mapsetName][$featureType] = $condition;
			$response['filters'][$featureType] = $res;
			$response['conditions'][$featureType] = $condition;
		}
		else {
		    unset($_SESSION['GC_LAYER_FILTERS'][$mapsetName][$featureType]);
			unset($_SESSION['GC_LAYER_FILTERS_C'][$mapsetName][$featureType]);
		}
	}
	die(json_encode($response));
}
else if ($_REQUEST['action'] == 'list') {
	if (isset($_SESSION['GC_LAYER_FILTERS'][$mapsetName])) {
	    $response['filters'] = $_SESSION['GC_LAYER_FILTERS'][$mapsetName];
		$response['conditions'] = $_SESSION['GC_LAYER_FILTERS_C'][$mapsetName];
	}
	die(json_encode($response));
}

die(json_encode(array('error' => 200, 'message' => 'No valid action')));

?>
