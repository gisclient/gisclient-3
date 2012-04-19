<?php
require_once "../../config/config.php";
$dbSchema=DB_SCHEMA;


$transform = defined('POSTGIS_TRANSFORM_GEOMETRY')?POSTGIS_TRANSFORM_GEOMETRY:'ST_Transform_Geometry';
// Setto qui i parametri di trasformazione... troppo casino ricavarli dal progetto corrente
$SRS = array(
	'3003'=>'+proj=tmerc +lat_0=0 +lon_0=9 +k=0.999600 +x_0=1500000 +y_0=0 +ellps=intl +units=m +no_defs +towgs84=-104.1,-49.1,-9.9,0.971,-2.917,0.714,-11.68',
	'900913'=>'+proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1.0 +units=m +nadgrids=@null +towgs84=0,0,0 +no_defs',
	'32632'=>'+proj=utm +zone=32 +ellps=WGS84 +datum=WGS84 +units=m +no_defs',
	'4326','+proj=longlat +ellps=WGS84 +datum=WGS84 +no_defs'
);

$db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
		if(!$db->db_connect_id){
			print('Errore di connessione');
			die();
		};
		
if ($_REQUEST["request"]=='comuni'){
	$filter = '';
	if(!empty($_REQUEST["query"])){
		$filter .= "nome ILIKE '%".$_REQUEST["query"]."%'";
	}

	$sql = "SELECT codice_ist AS codice,nome,extent($geom) AS extent FROM dbtopo.comuni07 group by nome,codice_ist $filter ORDER BY 2;";
	$db->sql_query ($sql);
	$res=array();
	while($row = $db->sql_fetchrow()){
		$extent = $row[2];
		$extent = str_replace("BOX(","",$extent);
		$extent = str_replace(")","",$extent);
		$extent = str_replace(" ",",",$extent);
		
		$res[]=array("codice"=>$row[0],"nome"=>utf8_encode($row[1]),"extent"=>explode(",",$extent));
	}
	$aResult['comuniData'] = $res;
}		
		
if ($_REQUEST["request"]=='vie'){
	$filter = '';
	if(!empty($_REQUEST["comune"])) $filter ="comune = '".$_REQUEST["comune"]."'";
	if(!empty($_REQUEST["query"])){
		if($filter) $filter.=" AND ";
		$filter .= "nome ILIKE '%".$_REQUEST["query"]."%'";
	}
	if($filter) $filter = "WHERE $filter";
	$sql="SELECT codice,nome FROM vista_zoomto_vie $filter;";
	$db->sql_query ($sql);
	$res=array();
	while($row = $db->sql_fetchrow()){
		$res[]=array("codice"=>intval($row[0]),"nome"=>utf8_encode($row[1]));
	}
	$aResult['vieData'] = $res;
}
		
if ($_REQUEST["request"]=='civici'){

	//Verifica srid dei civici:
	$sql = "select srid(the_geom) as srid from vista_zoomto_civici limit 1;";
	$db->sql_query ($sql);
	$dataSRID = $db->sql_fetchfield('srid');

	if(empty($_REQUEST["srs"]) || $_REQUEST["srs"]=="EPSG:$dataSRID"){
		$geom = "the_geom";
	}
	else{
		$v = explode(':',$_REQUEST["srs"]);
		$mapSRID = $v[1];
		if(!isset($SRS["$dataSRID"])) die("Mancano i parametri per EPSG:$dataSRID");
		if(!isset($SRS["$mapSRID"])) die("Mancano i parametri per EPSG:$mapSRID");
		$fromProj = $SRS["$dataSRID"];
		$toProj = $SRS["$mapSRID"];
		$geom = "$transform(the_geom,'$fromProj','$toProj',$mapSRID)";
	}
	
	$sql = "select codice, numero, x($geom), y($geom) from vista_zoomto_civici where idvia=".$_REQUEST["codvia"].";";
	$db->sql_query ($sql);
	$res=array();
	while($row = $db->sql_fetchrow()){
		$res[]=array("codice"=>intval($row[0]),"numero"=>$row[1],"x"=>floatval($row[2]),"y"=>floatval($row[3]));
	}
	$aResult['civiciData'] = $res;
}		

if ($_REQUEST["request"]=='foglio'){
	//Verifica srid dei fogli:
	$sql = "select srid(the_geom) as srid from vista_zoomto_fogli limit 1;";
	$db->sql_query ($sql);
	$dataSRID = $db->sql_fetchfield('srid');

	if(empty($_REQUEST["srs"]) || $_REQUEST["srs"]=="EPSG:$dataSRID"){
		$geom = "the_geom";
	}
	else{
		$v = explode(':',$_REQUEST["srs"]);
		$mapSRID = $v[1];
		if(!isset($SRS["$dataSRID"])) die("Mancano i parametri per EPSG:$dataSRID");
		if(!isset($SRS["$mapSRID"])) die("Mancano i parametri per EPSG:$mapSRID");
		$fromProj = $SRS["$dataSRID"];
		$toProj = $SRS["$mapSRID"];
		$geom = "$transform(the_geom,'$fromProj','$toProj',$mapSRID)";
	}
	$filter = '';
	//if(!empty($_REQUEST["comune"])) $filter ="where comune = '".$_REQUEST["comune"]."'";
	$sql = "select codice, numero, round(xmin(box3d($geom))::numeric, 2) as xmin, round(ymin(box3d($geom))::numeric, 2) AS ymin, round(xmax(box3d($geom))::numeric, 2) AS xmax, round(ymax(box3d($geom))::numeric, 2) AS ymax from vista_zoomto_fogli $filter;";
	$db->sql_query ($sql);
	$res=array();
	while($row = $db->sql_fetchrow()){
		$res[]=array("codice"=>intval($row[0]),"numero"=>$row[1],"extent"=>array(floatval($row[2]),floatval($row[3]),floatval($row[4]),floatval($row[5])));
	}
	$aResult['fogliData'] = $res;
}

if ($_REQUEST["request"]=='particella'){
	//Verifica srid delle particelle:
	$sql = "select srid(the_geom) as srid from vista_zoomto_particelle limit 1;";
	$db->sql_query ($sql);
	$dataSRID = $db->sql_fetchfield('srid');

	if(empty($_REQUEST["srs"]) || $_REQUEST["srs"]=="EPSG:$dataSRID"){
		$geom = "the_geom";
	}
	else{
		$v = explode(':',$_REQUEST["srs"]);
		$mapSRID = $v[1];
		if(!isset($SRS["$dataSRID"])) die("Mancano i parametri per EPSG:$dataSRID");
		if(!isset($SRS["$mapSRID"])) die("Mancano i parametri per EPSG:$mapSRID");
		$fromProj = $SRS["$dataSRID"];
		$toProj = $SRS["$mapSRID"];
		$geom = "$transform(the_geom,'$fromProj','$toProj',$mapSRID)";
	}
	
	$sql = "select codice, numero, round(xmin(box3d($geom))::numeric, 2) as xmin, round(ymin(box3d($geom))::numeric, 2) AS ymin, round(xmax(box3d($geom))::numeric, 2) AS xmax, round(ymax(box3d($geom))::numeric, 2) AS ymax from vista_zoomto_particelle where foglio='".$_REQUEST["foglio"]."';";
	$db->sql_query ($sql);
	$res=array();
	while($row = $db->sql_fetchrow()){
		$res[]=array("codice"=>intval($row[0]),"numero"=>$row[1],"extent"=>array(floatval($row[2]),floatval($row[3]),floatval($row[4]),floatval($row[5])));
	}
	$aResult['particelleData'] = $res;

}

header("Content-Type: application/json; Charset=". CHAR_SET);
echo json_encode($aResult);
?>