<?php
require_once('../../config/config.php');
require_once 'include/mapImage.php';

$options = array('image_format'=>'gtiff');

if(!empty($_REQUEST['tiles']) && is_array($_REQUEST['tiles'])) {
	$tiles = $_REQUEST['tiles'];
} else {
	die(json_encode(array('result' => 'error', 'error' => 'No tiles')));
}

if(empty($_REQUEST['viewport_size']) || !is_array($_REQUEST['viewport_size']) || count($_REQUEST['viewport_size']) != 2) {
	die(json_encode(array('result' => 'error', 'error' => 'No size')));
}
$imageSize = $_REQUEST['viewport_size'];

if(empty($_REQUEST['srid'])) die(json_encode(array('result' => 'error', 'error' => 'No srid')));
$srid = $_REQUEST['srid'];
if(strpos($_REQUEST['srid'], ':') !== false) {
	list($options['auth_name'], $srid) = explode(':', $_REQUEST['srid']);
}

if(!empty($_REQUEST['scale_mode'])) $options['scale_mode'] = $_REQUEST['scale_mode'];
if(!empty($_REQUEST['pixels_distance'])) $options['pixels_distance'] = $_REQUEST['pixels_distance'];
if(!empty($_REQUEST['center'])) $options['center'] = $_REQUEST['center'];
if(!empty($_REQUEST['dpi']) && is_numeric($_REQUEST['dpi'])) $options['dpi'] = (int) $_REQUEST['dpi'];
if(!empty($_REQUEST['extent'])) $options['extent'] = explode(',', $_REQUEST['extent']);
			
try {
	$mapImage = new mapImage($tiles, $imageSize, $srid, $options);
	$imageUrl = $mapImage->getImageUrl();
} catch (Exception $e) {
    die(json_encode(array('result' => 'error', 'error' => $e->getMessage())));
}
die(json_encode(array('result' => 'ok', 'file' => $imageUrl, 'format' => 'geotiff')));