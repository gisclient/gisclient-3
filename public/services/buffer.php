<?php
if(empty($_REQUEST['features'])) die(json_encode(array('result'=>'error', 'error'=>'missing features')));
if(empty($_REQUEST['projection'])) die(json_encode(array('result'=>'error', 'error'=>'missing projection')));
if(empty($_REQUEST['buffer'])) die(json_encode(array('result'=>'error', 'error'=>'missing buffer')));

require_once "../../config/config.php";

list($auth, $srid) = explode(':', $_REQUEST['projection']);
if(empty($auth) || empty($srid) || !is_numeric($srid)) die(json_encode(array('result'=>'error', 'error'=>'invalid projection')));

if(!is_numeric($_REQUEST['buffer'])) die(json_encode(array('result'=>'error', 'error'=>'invalid buffer')));

$db = GCApp::getDB();

$sql = "select st_astext(st_buffer(st_geomfromtext(:geom, :srid), :buffer))";
$params = array('geom'=>$_REQUEST['features'], 'srid'=>$srid, 'buffer'=>$_REQUEST['buffer']);
try {
	$stmt = $db->prepare($sql);
	$stmt->execute($params);
	$bufferedGeoms = $stmt->fetchColumn(0);
} catch(Exception $e) {
	die(json_encode(array('result'=>'error', 'error'=>'buffer error', 'sql'=>$sql, 'params'=>$params, 'message'=>$e->getMessage())));
}
if(empty($bufferedGeoms)) die(json_encode(array('result'=>'error', 'error'=>'empty buffered geoms', 'sql'=>$sql, 'params'=>$params)));

die(json_encode(array('result'=>'ok', 'geometries'=>$bufferedGeoms)));