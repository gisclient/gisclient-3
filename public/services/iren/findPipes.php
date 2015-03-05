<?php
require_once "findPipes.config.php";
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

$x = floatval($_REQUEST["x"]);
$y = floatval($_REQUEST["y"]);
$distance = floatval($_REQUEST["distance"]);

$db = GCApp::getDB();
$sql = "SET statement_timeout TO $TIME_OUT;";
$stmt = $db->prepare($sql);
$stmt->execute();


if($_REQUEST["srs"] == "EPSG:".$GEOM_SRID){
	$point ="SRID=".$GEOM_SRID.";POINT($x $y)";
	$geom = "the_geom";
}
else{
	$v = explode(':',$_REQUEST["srs"]);
	$srid = $v[1];
	$point ="SRID=$srid;POINT($x $y)";
	$geom = $transform."(the_geom,'".$SRS[$GEOM_SRID]."','".$SRS[$srid]."',".$srid.")";
}


//ANALISI DEL GRAFO
$excludeVertex = false;
$aVertex=array();
//Valvole da escludere:
if(!empty($_REQUEST["exclude"])){
	$sql = "SELECT id_nodo from grafo.nodi where id_elemento in (".$_REQUEST["exclude"].")";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
		$aVertex[]=$row['id_nodo'];
	}
	$excludeVertex = implode(',',$aVertex);
	$excludeFilter = array('','');
	$joinFilter = "(sg.da_tipo<>'altro' AND sg.da_nodo NOT IN ($excludeVertex)) OR (sg.a_tipo<>'altro' AND sg.a_nodo NOT IN ($excludeVertex))";
}
else
	$joinFilter = "(sg.da_tipo<>'altro' OR sg.a_tipo<>'altro')";

//TROVO LA CONDOTTA SELEZIONATA COME ARCO DEL GRAFO

if($excludeVertex)
	$ff ="(da_tipo<>'altro' AND da_nodo NOT IN ($excludeVertex)) OR (a_tipo<>'altro' AND a_nodo NOT IN ($excludeVertex))";
else
	$ff = "da_tipo<>'altro' OR a_tipo<>'altro'";

$sql = "SELECT id_arco, case when ($ff) then 1 else 0 end as flag FROM grafo.archi as sg WHERE ST_DISTANCE('$point',$geom) < $distance ORDER BY ST_DISTANCE('$point',$geom) LIMIT 1;";

$stmt = $db->prepare($sql);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$selectedPipe = $row["id_arco"];
$flag = $row["flag"];

if(!$selectedPipe) die();

//PATCH PERCHE' NON ATTIVA LA RICORSIONE SE INZIA DA UN ARCO TERMINALE: TROVO IL PRIMO ARCO NON TERMINALE SE QUELLO SELEZIONATO NON LO E'
if($flag == 1){
	$sql = "SELECT sg.id_arco FROM grafo.archi g, grafo.archi sg WHERE g.id_arco = $selectedPipe AND g.id_arco <> sg.id_arco 
		AND (sg.a_nodo = g.da_nodo OR sg.a_nodo = g.a_nodo OR sg.da_nodo = g.da_nodo OR sg.da_nodo = g.a_nodo)";
	if($excludeVertex)
		$sql.=" AND ((sg.da_tipo='altro' OR sg.da_nodo IN ($excludeVertex)) AND (sg.a_tipo='altro' OR sg.a_nodo IN ($excludeVertex)));";
	else
		$sql.=" AND (sg.da_tipo='altro' AND sg.a_tipo='altro');";

	$stmt = $db->prepare($sql);
	$stmt->execute();
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	$selectedNextPipe = $row["id_arco"];

	//CASO DI 2 ARCHI CON NODI TERMINALI UNITI DA NODO NON TERMINALE (?????)  VALVOLA - ALTRO - VALVOLA
	if(!$selectedNextPipe){
		$sql="SELECT id_arco,da_nodo,a_nodo, da_tipo,a_tipo FROM grafo.archi WHERE id_arco = $selectedPipe 
			UNION
			SELECT g.id_arco, g.da_nodo, g.a_nodo, g.da_tipo, g.a_tipo FROM grafo.archi g, grafo.archi sg 
			WHERE sg.id_arco = $selectedPipe AND g.id_arco <> sg.id_arco";
		if($excludeVertex)
			$sql.=" AND  ((g.a_nodo=sg.da_nodo AND (g.a_tipo='altro' OR g.a_nodo IN ($excludeVertex))) OR (g.da_nodo=sg.a_nodo AND (g.da_tipo='altro' OR g.da_nodo IN ($excludeVertex))) OR (g.a_nodo=sg.a_nodo AND (g.a_tipo='altro' OR g.a_nodo IN ($excludeVertex))) OR (g.da_nodo=sg.da_nodo AND (g.da_tipo='altro' OR g.da_nodo IN ($excludeVertex))));";
		else
			$sql.=" AND ((g.a_nodo=sg.da_nodo AND g.a_tipo='altro') OR (g.da_nodo=sg.a_nodo AND g.da_tipo='altro') OR (g.a_nodo=sg.a_nodo AND g.a_tipo='altro')OR (g.da_nodo=sg.da_nodo AND g.da_tipo='altro'));";

		$flag = 2;
	}else
		$selectedPipe = $selectedNextPipe;	
}
if(!$selectedPipe) die();

if($flag != 2){
	$sql = "WITH RECURSIVE search_graph(id_arco, da_nodo, a_nodo, da_tipo, a_tipo, the_geom, depth, path, stop) AS (
		SELECT g.id_arco, g.da_nodo, g.a_nodo, g.da_tipo, g.a_tipo, g.the_geom, 1,
		  ARRAY[g.id_arco],
		  false
		FROM grafo.archi g where g.id_arco = $selectedPipe
		UNION ALL
		SELECT g.id_arco, g.da_nodo, g.a_nodo, g.da_tipo, g.a_tipo, g.the_geom, sg.depth + 1,
		  path || g.id_arco,
		  g.id_arco = ANY(path) OR ($joinFilter) 
		FROM grafo.archi g, search_graph sg
		WHERE (sg.a_nodo = g.da_nodo OR sg.a_nodo = g.a_nodo OR sg.da_nodo = g.da_nodo OR sg.da_nodo = g.a_nodo) AND g.id_arco<>sg.id_arco AND NOT stop
		)
		SELECT DISTINCT id_arco, da_nodo, a_nodo, da_tipo, a_tipo FROM search_graph WHERE NOT stop LIMIT 1000";
}

//ELENCO DEGLI OGGETTI TROVATI INDICIZZATI PER TIPO
$stmt = $db->prepare($sql);
$stmt->execute();

$elements = array();	
foreach($ELEMENTS as $key=>$value) $elements[$key] = array();
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
	$elements["condotta"][] = $row["id_arco"];
	if($row["da_tipo"]!="altro") $elements[$row["da_tipo"]][] = $row["da_nodo"];
	if($row["a_tipo"]!="altro") $elements[$row["a_tipo"]][] = $row["a_nodo"];
}

print_debug($sql,null,'condotta');
print_debug($elements,null,'condotta');

//EXTENT
if($_REQUEST["srs"] == "EPSG:".$GEOM_SRID)
	$geom = $GEOM_FIELD;
else
	$geom = $transform."($GEOM_FIELD,'".$SRS[$GEOM_SRID]."','".$SRS[$srid]."',".$srid.")";

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
