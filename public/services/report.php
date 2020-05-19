<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once '../../config/config.php';
require_once ADMIN_PATH."lib/functions.php";
require_once ROOT_PATH."lib/i18n.php";
require_once ROOT_PATH . 'lib/GCService.php';
require_once 'include/gcReport.class.php';

// **** Used to launch from command line/asyncronous
if (isset($argv)) {
	foreach ($argv as $arg) {
	    $e=explode("=",$arg);
	    if(count($e)==2)
	        $_REQUEST[$e[0]]=$e[1];
	    else
	        $_REQUEST[$e[0]]=0;
	}
}

$gcService = GCService::instance();
$gcService->startSession();

$getLegend = false;
if(isset($_REQUEST['legend']) && $_REQUEST['legend'] == 1) {
	$getLegend = true;
}
$languageId = null;
if(!empty($_REQUEST['lang'])) {
	$languageId = $_REQUEST['lang'];
}

$onlyPublicLayers = false;
if (!empty($_REQUEST['show_as_public'])) {
	$onlyPublicLayers = true;
}

if(empty($_REQUEST['mapset'])) die(json_encode(array('error' => 200, 'message' => 'No mapset name')));
if(empty($_REQUEST['action'])) die(json_encode(array('error' => 200, 'message' => 'No action specified')));

$objReport = new gcReport($_REQUEST["mapset"], $languageId, $onlyPublicLayers);
header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header ("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header ("Pragma: no-cache"); // HTTP/1.0
header("Content-Type: application/json; Charset=UTF-8");

if ($_REQUEST['action'] == 'list'){
    $objReport->displayReports();
	foreach($objReport->reportConfig["reportDefs"] as $reportDef) {
		if (!empty($reportDef["materializeReport"])) {
			$phpPath = exec("which php");
			shell_exec('cd ' . getcwd() . ' && '.$phpPath.' report.php mapset='.$_REQUEST['mapset'].' report_id='.$reportDef['reportID'].' action=materialize &');
		}
	}
    $output = $objReport->reportConfig;
}
else if ($_REQUEST['action'] == 'query'){
    $objReport->queryReport($_REQUEST);
    $output = $objReport->reportQueryResult;
}
else if ($_REQUEST['action'] == 'xls' || $_REQUEST['action'] == 'pdf'){
    $objReport->exportReport($_REQUEST);
    $data = $objReport->reportQueryResult;
    if ($data['result'] != 'ok')
        die(json_encode($data));
    require_once('./export.php');
}
else if ($_REQUEST['action'] == 'materialize'){
    $objReport->materializeReport($_REQUEST);
    $output = $objReport->reportQueryResult;
}

if(empty($_REQUEST["callback"]))
	die(json_encode($output));
else
	die($_REQUEST["callback"]."(".json_encode($output).")");
