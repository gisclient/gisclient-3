<?php
include '../../../config/config.php';
require_once ADMIN_PATH."lib/functions.php";
require_once ADMIN_PATH.'lib/gcFeature.class.php';
require_once ADMIN_PATH.'lib/gcMapfile.class.php';
require_once ROOT_PATH.'lib/gcapp.class.php';
require_once ROOT_PATH."lib/i18n.php";


$layerTitle = "";
$layerName = "";
$tmpMap = "";
$fileName = "";
$closing = !empty($_REQUEST['closeWindow']);
if(empty($_REQUEST['layergroup_id']) && empty($_REQUEST['layer_id']))
  die("Missing required parameter 'layergroup_id' or 'layer_id'");
else if(!empty($_REQUEST['layergroup_id']))
  $mapConfig = manageLayerGroupRequest($layerTitle,$layerName,$tmpMap, $fileName, GCApp::getDB(), $closing);
else
  $mapConfig = manageLayerRequest($layerTitle,$layerName,$tmpMap, $fileName, GCApp::getDB(), $closing);
if(!$closing) {
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

  if (!defined('OPENLAYERS')) {
	// FIXME: handle error in more sensible way
	// this generates an empty page when display_error = Off
	// handle like the above error check
	throw new Exception ("constant OPENLAYERS not defined");
  }
} else {
  die();
}

function manageLayerGroupRequest(&$layerTitle, &$layerName, &$tmpMap, &$fileName, $db, $closing) {
  $layergroupId = (int)$_REQUEST['layergroup_id'];
  $mapfile = new gcMapfile();
  $mapfile->setTarget("tmp");
  $sql = "select project_name, theme_name, project_srid, xc, yc, max_extent_scale, layergroup_name, layergroup_title, sld ".
	" from ".DB_SCHEMA.".project ".
	" inner join ".DB_SCHEMA.".theme using(project_name) ".
	" inner join ".DB_SCHEMA.".layergroup using(theme_id) ".
	" where layergroup_id = ?";
  $stmt = $db->prepare($sql);
  $stmt->execute(array($layergroupId));
  $mapConfig = $stmt->fetch(PDO::FETCH_ASSOC);
  if($closing) {
    $mapfile->_deleteFile($mapConfig['project_name'], $mapConfig['layergroup_name']);
  } else {
    $tmpMap = $mapfile->writeMap("layergroup",$layergroupId);
    if(empty($mapConfig['project_srid'])) die('Missing project srid');
    if(empty($mapConfig['xc']) || empty($mapConfig['yc'])) die('Missing project center');
    if(empty($mapConfig['max_extent_scale'])) die('Missing project max extent');
    $layerTitle = $mapConfig['layergroup_title'];
    $layerName = $mapConfig['layergroup_name'];
    $fileName = $mapConfig['layergroup_name'];
  }
  return $mapConfig;
}

function manageLayerRequest(&$layerTitle, &$layerName, &$tmpMap, &$fileName, $db, $closing) {
  $layerId = (int)$_REQUEST['layer_id'];
  $mapfile = new gcMapfile();
  $mapfile->setTarget("tmp");
  $sql = "select project_name, theme_name, project_srid, xc, yc, max_extent_scale, layergroup_name ,layer_name, layer_title, sld ".
	" from ".DB_SCHEMA.".project ".
	" inner join ".DB_SCHEMA.".theme using(project_name) ".
    " inner join ".DB_SCHEMA.".layergroup using(theme_id) ".
    " inner join ".DB_SCHEMA.".layer using(layergroup_id) ".
	" where layer_id = ?";
  $stmt = $db->prepare($sql);
  $stmt->execute(array($layerId));
  $mapConfig = $stmt->fetch(PDO::FETCH_ASSOC);
  if($closing) {
    $mapfile->_deleteFile($mapConfig['project_name'], $mapConfig['layer_name']);
  } else {
    $tmpMap = $mapfile->writeMap("layer",$layerId);
    $layerTitle = $mapConfig['layer_title'];
    $layerName = $mapConfig['layergroup_name'].".".$mapConfig['layer_name'];
    $fileName = $mapConfig['layer_name'];
  }
  return $mapConfig;
}

?><!DOCTYPE HTML><html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Author - Preview Map</title>
<script type="text/javascript" src="<?php echo OPENLAYERS; ?>"></script>
<script type="text/javascript">
function init() {
<?php
  GCAuthor::compileMapfile($mapConfig['project_name'], $fileName);
  $errors = GCError::get();
  if(empty($errors)) {
?>
	var layerParameters = {
		project: '<?php echo $mapConfig['project_name']; ?>',
		map: '<?php echo $tmpMap; ?>',
		layers: '<?php echo $layerName; ?>',
		//tmp: 1,
		format: 'image/png; mode=24bit'
	};
    <?php if(!empty($mapConfig['sld'])) { ?>
    layerParameters.sld = '<?php echo $mapConfig['sld']; ?>';
    <?php } ?>
	
	if (typeof OpenLayers === 'undefined') {
		// OpenLayers could not be loaded
		// alert user and avoid to work with that variable
		alert("Could not load OpenLayers from <?php echo OPENLAYERS; ?>")
	} else {
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
<?php
  } else {
?>
  document.getElementById("map").innerHTML += '<?php echo prepareOutputForError($errors); ?>';
<?php
  }
?>
}
</script>
<style>
body, html {
	margin: 0px; 
	padding: 0px;
}
#map {
	width: 745px;
	height: 685px;
}
</style>
</head>
<body onload="init();">
<div id="map">
</div>
</body>
</html>
