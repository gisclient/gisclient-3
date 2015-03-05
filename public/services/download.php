<?php
require_once '../../config/config.php';
require_once 'include/mapImage.php';
require_once ROOT_PATH . 'lib/GCService.php';

$gcService = GCService::instance();
$gcService->startSession();

// TODO: handle request parameteers in a more systematic way, suing a single
// procedure checking required/optional, type and value range

$options = array('image_format'=>'gtiff', 'output_format'=>'geotiff');

if($_REQUEST['format'] == 'png') {
	$options['image_format'] = 'png';
	$options['output_format'] = 'png';
	$options['save_image'] = true;
	$options['file_name'] = GCApp::getUniqueRandomTmpFilename(GC_WEB_TMP_DIR, 'gc_mapimage', 'png');
} else if($_REQUEST['format'] == 'jpeg') {
	$options['image_format'] = 'jpeg';
	$options['output_format'] = 'jpeg';
	$options['save_image'] = true;
	$options['file_name'] = GCApp::getUniqueRandomTmpFilename(GC_WEB_TMP_DIR, 'gc_mapimage', 'jpeg');
}

if(!empty($_REQUEST['tiles']) && is_array($_REQUEST['tiles'])) {
	$tiles = $_REQUEST['tiles'];
} else {
    print_debug('No tiles',null,'download');
	die(json_encode(array('result' => 'error', 'error' => 'No tiles')));
}

if(empty($_REQUEST['viewport_size']) || !is_array($_REQUEST['viewport_size']) || count($_REQUEST['viewport_size']) != 2) {
    print_debug('No tiles',null,'size');
	die(json_encode(array('result' => 'error', 'error' => 'No size')));
}

if(empty($_REQUEST['srid'])){
    print_debug('No tiles',null,'No srid');
	die(json_encode(array('result' => 'error', 'error' => 'No srid')));
}
$srid = $_REQUEST['srid'];
if(strpos($_REQUEST['srid'], ':') !== false) {
	list($options['auth_name'], $srid) = explode(':', $_REQUEST['srid']);
}

if(!empty($_REQUEST['scale_mode'])) $options['scale_mode'] = $_REQUEST['scale_mode'];
if(!empty($_REQUEST['fixed_size'])) $options['fixed_size'] = $_REQUEST['fixed_size'];
if(!empty($_REQUEST['scale'])) {
	$options['scale'] = $_REQUEST['scale'];
} else {
	print_debug('missing mandatory parameter "scale"',null,'download');
    die(json_encode(array('result' => 'error', 'error' => 'missing mandatory parameter "scale"')));
}
if(!empty($_REQUEST['center'])) $options['center'] = $_REQUEST['center'];
$options['dpi'] = 96;
if(!empty($_REQUEST['dpi']) && is_numeric($_REQUEST['dpi'])){
	$options['dpi'] = (int) $_REQUEST['dpi'];
	
}

if(!empty($_REQUEST['extent'])) {
	$options['extent'] = explode(',', $_REQUEST['extent']);
} else {
	// we could eventually try to reconstruct the extent from the viewport size, but is it worth to complicate the code?
	print_debug('missing mandatory parameter "extent"',null,'download');
    die(json_encode(array('result' => 'error', 'error' => 'missing mandatory parameter "extent"')));
}

$imageSize = array(
	0 => (int) $options['dpi'] * 100 / 2.54 * ($options['extent'][2] - $options['extent'][0]) / $options['scale'],
	1 => (int) $options['dpi'] * 100 / 2.54 * ($options['extent'][3] - $options['extent'][1]) / $options['scale'],
);

if(isset($_REQUEST['scalebar'])) {
	$options['scalebar'] = $_REQUEST['scalebar'];
}
			
try {
	$mapImage = new mapImage($tiles, $imageSize, $srid, $options);
	$imageUrl = $mapImage->getImageUrl();
} catch (Exception $e) {
    print_debug($e->getTraceAsString(),null,'download');
    print_debug($e->getMessage(),null,'download');
    die(json_encode(array('result' => 'error', 'error' => $e->getMessage())));
}
die(json_encode(array('result' => 'ok', 'file' => $imageUrl, 'format' => $options['output_format'])));
