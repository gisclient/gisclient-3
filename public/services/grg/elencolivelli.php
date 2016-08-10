<?php
require_once "../../../config/config.php";

$dbSchema=DB_SCHEMA;
$transform = defined('POSTGIS_TRANSFORM_GEOMETRY')?POSTGIS_TRANSFORM_GEOMETRY:'Transform_Geometry';
// Setto qui i parametri di trasformazione... troppo casino ricavarli dal progetto corrente
$SRS = array(
	'3003'=>'+proj=tmerc +lat_0=0 +lon_0=9 +k=0.999600 +x_0=1500000 +y_0=0 +ellps=intl +units=m +no_defs +towgs84=-104.1,-49.1,-9.9,0.971,-2.917,0.714,-11.68',
	'900913'=>'+proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1.0 +units=m +nadgrids=@null +towgs84=0,0,0 +no_defs',
	'3857'=>'+proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +units=m +k=1.0 +nadgrids=@null +no_defs',
	'4326'=>'+proj=longlat +ellps=WGS84 +datum=WGS84 +no_defs',
	'25832'=>'+proj=utm +zone=32 +ellps=GRS80 +units=m +no_defs'
);


$db = GCApp::getDB();
$sql = "select theme_title as tema,layergroup_name,layer_name,split_part(catalog_path,'/',2)||'.'||data as tabella,data_geom as geom from gisclient_3.layer inner join gisclient_3.layergroup using (layergroup_id) inner join gisclient_3.mapset_layergroup using(layergroup_id) inner join gisclient_3.theme using (theme_id) inner join gisclient_3.catalog using(project_name,catalog_id) where theme.project_name='geoweb_genova' and mapset_name='reti_grg_tb' and data_geom is not null
and not layer_name ilike '%_TBL';";


$stmt = $db->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchall(PDO::FETCH_ASSOC);

foreach($rows as $key => $row){

	$sql = "select count(*) from " . $row["tabella"] ." where " . $row["geom"] . ";"; 
	try {
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$res = $stmt->fetch();
		print_array($res);
	} catch(Exception $e) {
		//throw $e;
	}




}
die();



$key = "condotta";
$table = $ELEMENTS[$key]["featureType"]["table"];
$sql = "SELECT ST_XMin(ST_Extent($geom)),ST_YMin(ST_Extent($geom)),ST_XMax(ST_Extent($geom)),ST_YMax(ST_Extent($geom)) FROM $SCHEMA.$table WHERE $FID_FIELD IN (".implode(",",$elements["condotta"]).");";

$stmt = $db->prepare($sql);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_NUM);
for($i=0;$i<4;$i++) $row[$i] = round($row[$i],2);
$ELEMENTS["features_extent"] = $row;

//AGGIUNGO LE ENTITA' TROVATE A ELEMENTS
if(empty($_REQUEST["exclude"])) 
	$exclude = "0 as escluso";
else
	$exclude = "case when $FID_FIELD in (".$_REQUEST["exclude"].") then 1 else 0 end as escluso";

//CONDOTTE:
$key = "condotta";
$fields = array();
$table = $ELEMENTS[$key]["featureType"]["table"];
$ELEMENTS[$key]["featureType"]["typeName"] = $key;
unset ($ELEMENTS[$key]["featureType"]["table"]);
//ELENCO DEI CAMPI PER LA QUERY
foreach($ELEMENTS[$key]["featureType"]["properties"] as $field) $fields[]=$field["name"];
$sql = "SELECT $FID_FIELD,ST_AsText($geom) as geom,".implode(",",$fields)." FROM $SCHEMA.$table WHERE $FID_FIELD IN (".implode(",",$elements[$key]).");";

print_debug($sql,null,'condotta');
$stmt = $db->prepare($sql);
$stmt->execute();

$features = array();
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
	$properties = array();
	//encode in utf8
	foreach($ELEMENTS[$key]["featureType"]["properties"] as $field) $properties[$field["name"]]=utf8_encode($row[$field["name"]]);
	$properties["escluso"]=0;
	$g = str_replace("LINESTRING(","",$row["geom"]);
	$g = str_replace(")","",$g);
	$g = explode(",",$g);
	foreach($g as $idx=>$value){
		list($x,$y) = explode(" ",$g[$idx]);
		$g[$idx]=array(round($x,2),round($y,2));
	}
	$features[] = array("type"=>"Feature","id"=>$key.".".$row[$FID_FIELD],"properties"=>$properties,"geometry"=>array("type"=>"LineString","coordinates"=>$g));		
}

$ELEMENTS[$key]["features"] = array("type"=>"FeatureCollection","features"=>$features);
unset($elements[$key]);

foreach($elements as $key => $idList){
	$features = array();
	if(count($idList)>0){
		$fields = array();
		$table = $ELEMENTS[$key]["featureType"]["table"];
		$ELEMENTS[$key]["featureType"]["typeName"] = $key;
		unset ($ELEMENTS[$key]["featureType"]["table"]);
		foreach($ELEMENTS[$key]["featureType"]["properties"] as $field) $fields[]=$field["name"];
		$sql = "SELECT $FID_FIELD,ST_AsText($geom) as geom, $exclude,".implode(",",$fields)." FROM $SCHEMA.$table WHERE $FID_FIELD IN (SELECT id_elemento FROM grafo.nodi WHERE id_nodo IN(".implode(",",$idList)."));";
	
		print_debug($sql,null,'condotta');
		$stmt = $db->prepare($sql);
		$stmt->execute();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$properties = array();
			foreach($ELEMENTS[$key]["featureType"]["properties"] as $field) $properties[$field["name"]]=utf8_encode($row[$field["name"]]);
			$properties["escluso"]=$row["escluso"];
			$g = str_replace("POINT(","",$row["geom"]);
			$g = str_replace(")","",$g);
			list($x,$y) = explode(" ",$g);
			$g = array(round($x,2),round($y,2));
			$features[] = array("type"=>"Feature","id"=>$key.".".$row[$FID_FIELD],"properties"=>$properties,"geometry"=>array("type"=>"Point","coordinates"=>$g));		
		}
	}
	$ELEMENTS[$key]["features"] = array("type"=>"FeatureCollection","features"=>$features);
	
} 

header("Content-Type: application/json");
die(json_encode($ELEMENTS));
?>
