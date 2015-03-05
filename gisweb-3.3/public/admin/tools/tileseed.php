<?php
/******************************************************************************
*
* Purpose: genera i tiles per i layers con estensione quella definite per il campo tiles extent in "livelli in mappa" 
     
* Author:  Roberto Starnini, Gis & Web Srl, roberto.starnini@gisweb.it
*
******************************************************************************
*
* Copyright (c) 2009-2010 Gis & Web Srl www.gisweb.it
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version. See the COPYING file.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with p.mapper; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*
******************************************************************************/
require_once('../../../config/config.php');
require_once (ADMIN_PATH.'lib/functions.php');

$unitsId = 5; //meters cablati come sulla mappa TODO!!!!

if(!isset($argv[1]) || !isset($argv[2])){
	echo "---------- SEEDING DEI TILES -------------\n";
	echo "Uso: php tileseed.php livello srid buffer extent\n";
	echo "livello: progetto.tema.livellomappa \n";
	echo "srid: codice srid per i tiles\n";
	echo "buffer: buffer sui tiles per non tagliare le etichette (opzionale tra 1 e 100)\n";
	echo "extent: extent ridotto per aggiornare solo alcune zone (opzionale)\n";
	//die();
}

ini_set('max_execution_time',200000);
ini_set('memory_limit','1024M');

$db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
if(!$db->db_connect_id){
	echo "Connessione al db fallita!";
	die();
};


if(empty($argv[1])){
	//SETTAGGIO FORZATO
	$layerTiles='siti.particelle';
	$sridTiles = 3003;
	$tileBuffer = 0;
	$startLevel = "0,0,0";
	$startLevel = "14,464,600";

}
else{
	$layerTiles=$argv[1];
	$sridTiles=$argv[2];
	$tileBuffer = (isset($argv[3]))?intval($argv[3]):0;
	$extent = (isset($argv[4]))?$argv[4]:false;
}


//TODO: AGGIUNGERE UN FILE DI LOG NELLA CARTELLA DEI TILES CON LE INFOMAZIONI SULL'ESTENSIONE... E ALTRO		
		
$dbSchema=DB_SCHEMA;
$sql="select project_name,layergroup_name,layergroup_id,tiles_extent,tiles_extent_srid,outputformat_extension from $dbSchema.project inner join $dbSchema.theme using(project_name) inner join $dbSchema.layergroup using(theme_id) inner join $dbSchema.e_outputformat using (outputformat_id) where project_name||'.'||layergroup_name = '$layerTiles';";
$db->sql_query($sql);

$res=$db->sql_fetchrow();

if(empty($res["tiles_extent"]) || empty($res["tiles_extent_srid"])){
	echo "Impossibile procedere manca estensione o srid dei tiles!";
	die();
}

$projectName = $res["project_name"];
$mapName = $res["project_name"];
$layerName = $res["layergroup_name"];
$tilesExtent = explode(" ",$res["tiles_extent"]);
$format = $res["outputformat_extension"];
if(count($tilesExtent) != 4){
	echo "Impossibile procedere estensione non corretta!";
	die();
}

print("\n");
print($sql);
print_r($res);

//Setto i parametri in mappa	
$oMap = ms_newMapobj("../../../map/$projectName/$mapName.map");
//$tileSize = 24*TILE_SIZE;
$tileSize = TILE_SIZE;
$size =  $tileSize + 2 * $tileBuffer;
$oMap->set("width", $size);
$oMap->set("height", $size);

$projString=false;
$aLayersIndexes = $oMap->getLayersIndexByGroup($layerName);
if(is_array($aLayersIndexes) && count($aLayersIndexes)>0){
	for($j=0;$j<count($aLayersIndexes);$j++){
		$oLayer=$oMap->getLayer($aLayersIndexes[$j]);		
		if(!$oLayer->getMetaData("gc_hide_layer"))	$oLayer->set("status",MS_ON);
		if($oLayer->getMetaData("ows_srs") == $sridTiles) $projString = $oLayer->getProjection();	
		
	}
}


if(isset($layers) && $layers!=''){
	$v=explode(",",$layers);
	for($i=0;$i<count($v);$i++){
		$lName = $layerName .".". $v[$i];
		if(@$oLayer=$oMap->getLayerByName($lName)){
			$oLayer->set("status",MS_ON);
			if($oLayer->getMetaData("ows_srs") == $sridTiles) $projString = $oLayer->getProjection();	
		}
	}
}

	

if($res["tiles_extent"] && $res["tiles_extent_srid"]){
	$tilesExtent = explode(" ",$res["tiles_extent"]);
	$sridExtent = $res["tiles_extent_srid"];
	if($sridExtent != $sridTiles){
		$p1 = "SRID=$sridExtent;POINT(".$tilesExtent[0]." ".$tilesExtent[1].")";
		$p2 = "SRID=$sridExtent;POINT(".$tilesExtent[2]." ".$tilesExtent[3].")";
		$sql="SELECT X(st_transform('$p1'::geometry,$sridTiles)) as x0, Y(st_transform('$p1'::geometry,$sridTiles)) as y0, X(st_transform('$p2'::geometry,$sridTiles)) as x1,Y(st_transform('$p2'::geometry,$sridTiles)) as y1;";
		echo $sql;
		$db->sql_query ($sql);
		$row = $db->sql_fetchrow();
		$tilesExtent = array(floatval($row[0]),floatval($row[1]),floatval($row[2]),floatval($row[3]));
	}
}
print_r($tilesExtent);



//Layers
$sqlLayers = "select layer_name from $dbSchema.layer where hidden<>1 and layergroup_id = " . $res["layergroup_id"];
echo $sqlLayers;
$db->sql_query ($sqlLayers);
$layerList = array();
while($lay = $db->sql_fetchrow()){
	$layerList[]=$lay["layer_name"];
}
$layers=implode(",",$layerList);

//zoom level di partenza in relazione all'array completo delle risoluzioni (non quello limitato dal progetto)
//in questo modo non devo rifare i tiles se cambia l'estensione del progetto
$convFact = GCAuthor::$aInchesPerUnit[$unitsId]*MAP_DPI;
$resolutions = getResolutions($sridTiles,$convFact);

print_r($resolutions);
print ($layers);

/*
if(isset($maxScale))
	$offsetLevel = array_index($resolutions ,$maxScale/$convFact);
else
	$offsetLevel = 0;
*/

if(isset($minScale))
	$maxLevel = array_index($resolutions ,$minScale/$convFact) + 1;
else
	$maxLevel = count($resolutions);

if(!isset($tilesExtent))
	$tilesExtent = getExtent($xc,$yc,$resolutions[0]);

if(isset($sridTiles)){
	$mapProjString = ($projString)?$projString:"+init=epsg:$sridTiles";
	$oMap->setProjection($mapProjString);
	echo $mapProjString;
}

$ext='png';


$path = TILES_CACHE;
if(!is_dir($path)) mkdir($path);
$path.=$projectName."/";
if(!is_dir($path)) mkdir($path);
$path.="EPSG_".$sridTiles."/";
if(!is_dir($path)) mkdir($path);
$path.=$layerName."/";
if(!is_dir($path)) mkdir($path);

$fp = fopen ($path."error.log", "w");	

list($startLevel,$startXTiles,$startYTiles) = explode(",",$startLevel);

print "Z=$startLevel, X=$startXTiles, Y=$startYTiles, MAXLEVEL=$maxLevel\n";


for($z=$startLevel;$z < $maxLevel;$z++){
	$res = $resolutions[$z];
	$numXTiles = ceil((($tilesExtent[2]-$tilesExtent[0])/($res * $tileSize)));
	$numYTiles = ceil((($tilesExtent[3]-$tilesExtent[1])/($res * $tileSize)));
	print("Z=$z XTiles=$numXTiles YTiles=$numYTiles\n");
	if(!is_dir($path.$z))	mkdir($path.$z);	

	$offsetXTiles = 0;
	if($z == $startLevel) $offsetXTiles = $startXTiles;
	for($j=$offsetXTiles;$j<$numXTiles;$j++){	
		$X0 = $tilesExtent[0] + $j * $res * $tileSize;
		$X1 = $X0 + $res * $tileSize;
		
		$offsetYTiles = 0;
		if(($z == $startLevel) && ($j == $startXTiles)) $offsetYTiles = $startYTiles;;
		for($i=$offsetYTiles;$i<$numYTiles;$i++){
			$Y0 = $tilesExtent[1] + $i * $res * $tileSize;
			$Y1 = $Y0 + $res * $tileSize;
			if($X0<=$tilesExtent[2] && $X1>=$tilesExtent[0] && $Y0<=$tilesExtent[3] && $Y1>=$tilesExtent[1]){
				ms_ResetErrorList();
				//creo cartella se non esiste		
				if(!is_dir($path.$z."/".$j)) mkdir($path.$z."/".$j);
				//echo ("$X0 - $res*$tileBuffer, $Y0 - $res*$tileBuffer, $X1 + $res*$tileBuffer,$Y1 + $res*$tileBuffer");
				$oMap->setExtent($X0 - $res*$tileBuffer, $Y0 - $res*$tileBuffer, $X1 + $res*$tileBuffer,$Y1 + $res*$tileBuffer);
				$oImage=$oMap->draw();
				$filename=$path.$z."/".$j."/".$i.".".$ext;
				$error = ms_GetErrorObj();
				if($error->code != MS_NOERR){
					while($error->code != MS_NOERR){
						fwrite($fp, "Error \n". $error->routine."\n".$error->message);
						print ("Error $filename\n". $error->routine."\n".$error->message);
						$error = $error->next();
					}
				}	
				elseif($tileBuffer > 0){
					ob_start();
					$oImage->saveImage('');
					$image_data = ob_get_contents();
					$img = imagecreatefromstring($image_data);
					$cropped_img = crop_border($img, $tileBuffer);
					if($ext=='png') imagepng($cropped_img,$filename);
					if($ext=='jpg') imagejpg($cropped_img,$filename);
					//fwrite($fp, getimagesize($filename)."\n");
					imagedestroy($img);
					imagedestroy($cropped_img);
					ob_end_clean();
					$oImage->free();
				}else{
					$oImage->saveImage($filename);
					$oImage->free();
				}
				print ($z."/".$j."/".$i."\n");
			}
		}
	}
}
fclose($fp);





?>

