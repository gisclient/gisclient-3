<?php
include('../../../config/config.php');
require_once ADMIN_PATH."lib/functions.php";
require_once ADMIN_PATH.'lib/gcFeature.class.php';
require_once ADMIN_PATH.'lib/gcMapfile.class.php';
require_once ROOT_PATH."lib/i18n.php";

$db = GCApp::getDB();

if(empty($_REQUEST['layergroup_id'])) die('Missing required params');
$layergroupId = (int)$_REQUEST['layergroup_id'];

$mapfile = new gcMapfile();
$mapfile->setTarget("tmp");
$tmpMap = $mapfile->writeMap("layergroup",$layergroupId);

$sql = "select project_name, theme_name, project_srid, xc, yc, max_extent_scale, layergroup_name, layergroup_title".
	" from ".DB_SCHEMA.".project ".
	" inner join ".DB_SCHEMA.".theme using(project_name) ".
	" inner join ".DB_SCHEMA.".layergroup using(theme_id) ".
	" where layergroup_id = ?";

$stmt = $db->prepare($sql);
$stmt->execute(array($layergroupId));

$mapConfig = $stmt->fetch(PDO::FETCH_ASSOC);
if(empty($mapConfig['project_srid'])) die('Missing project srid');
if(empty($mapConfig['xc']) || empty($mapConfig['yc'])) die('Missing project center');
if(empty($mapConfig['max_extent_scale'])) die('Missing project max extent');

$layerTitle = $mapConfig['layergroup_title'];
$layerName = $mapConfig['layergroup_name'];
//$user = new userApps(array());
//$user->status = true;
$user = new GCUser();
$user->setAuthorizedLayers(array('theme_name'=>$mapConfig['theme_name']));

$scales = explode(',',SCALE);
$resolutions = array();
foreach($scales as $scale) {
	if($scale > $mapConfig['max_extent_scale']) continue;
	array_push($resolutions, $scale / (39.3701*MAP_DPI));
}
$maxExtent = array(
	$mapConfig['xc'] - $resolutions[0] * TILE_SIZE,
	$mapConfig['yc'] - $resolutions[0] * TILE_SIZE,
	$mapConfig['xc'] + $resolutions[0] * TILE_SIZE,
	$mapConfig['yc'] + $resolutions[0] * TILE_SIZE
);

?><!DOCTYPE HTML><html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Author - Preview Map</title>
<script type="text/javascript" src="<?php echo OPENLAYERS ?>"></script>
<script type="text/javascript">
function init() {
	var layerParameters = {
		project: '<?php echo $mapConfig['project_name'] ?>',
		map: '<?php echo $tmpMap ?>',
		layers: '<?php echo $layerName ?>',
		//tmp: 1,
		format: 'image/png; mode=24bit'
	};
	var mapOptions = {
		projection: new OpenLayers.Projection('EPSG:<?php echo $mapConfig['project_srid'] ?>'),
		units: 'm',
		maxExtent: new OpenLayers.Bounds.fromArray([<?php echo implode(',', $maxExtent) ?>]),
		resolutions: [<?php echo implode(',', $resolutions) ?>]
	};

	var map = new OpenLayers.Map('map', mapOptions);
	var layer = new OpenLayers.Layer.WMS('<?php echo $layerName ?>', '<?php echo GISCLIENT_OWS_URL ?>', layerParameters, {singleTile:true});
	map.addLayer(layer);
	
	map.setCenter(new OpenLayers.LonLat(<?php echo $mapConfig['xc'] ?>, <?php echo $mapConfig['yc'] ?>));
}
</script>
<style>
body, html {
	margin: 0px; 
	padding: 0px;
}
#map {
	width: 450px;
	height: 450px;
}
</style>
</head>
<body onload="init();">
<div id="map">
</div>
</body>
</html>