<?php
/******************************************************************************
*
* Purpose: Inizializzazione dei parametri per la creazione della mappa 
     
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
define('RESULT_TYPE_SINGLE',1);
define('RESULT_TYPE_TABLE',2);
define('RESULT_TYPE_ALL',3);
define('RESULT_TYPE_NONE',4);

define('ONE_TO_ONE_RELATION',1);
define('ONE_TO_MANY_RELATION',2);

define('AGGREGATE_NULL_VALUE','----');

define('ORDER_FIELD_ASC',1);
define('ORDER_FIELD_DESC',2);

define('STANDARD_FIELD_TYPE',1);
define('LINK_FIELD_TYPE',2);
define('EMAIL_FIELD_TYPE',3);
define('HEADER_GROUP_TYPE',10);
define('IMAGE_FIELD_TYPE',8);
define('SECONDARY_FIELD_LINK',99);

//$aUnitDef = array(1=>"m",2=>"ft",3=>"inches",4=>"km",5=>"m",6=>"mi",7=>"dd");//units tables (force pixel ->m)
//$aInchesPerUnit = array(1=>39.3701,2=>12,3=>1,4=>39370.1,5=>39.3701,6=>63360,7=>4374754);



$gMapMaxZoomLevels = array('G_HYBRID_MAP'=>19,'G_NORMAL_MAP'=>21,'G_PHYSICAL_MAP'=>15,'G_SATELLITE_MAP'=>19,'VEMapStyle.Road'=>21,'VEMapStyle.Aerial'=>21,'VEMapStyle.Shaded'=>21,'VEMapStyle.Hybrid'=>21,'YAHOO_MAP_HYB'=>21,'YAHOO_MAP_REG'=>21,'YAHOO_MAP_SAT'=>21,'Mapnik'=>21,'Osmarender'=>21,'CycleMap'=>17);


	function vcheck($value){
	
		return (isset($value) && !empty($value))?$value:false;
	
	}
	
	function jsonString($myArray,$callback=false){
		require_once "json.php";
		$json = new Services_JSON();
		$jsonstr = $json->encode($myArray);	
		$jsonstr = str_replace(chr(13),"<br>",$jsonstr);
		$jsonstr = str_replace(chr(10),"<br>",$jsonstr);
		//print_debug($jsonstr,null,'json');
		
		header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
		header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
		header ("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header ("Pragma: no-cache"); // HTTP/1.0
		header("Content-Type: application/json; Charset=". CHAR_SET);
		//header("Content-Type: text/html; Charset=". CHAR_SET);
		if($callback)
			echo $callback."(".$jsonstr.")";
		else
			echo $jsonstr;
	}	
	
	function array_limit($aList,$maxVal=false,$minVal=false){
		$ar=array();
		foreach($aList as $val){
			if($maxVal && $val>=$maxVal) $ar[]=$val;
			if($minVal && $val<$minVal) $ar[]=$val;
		}
		return array_values(array_diff($aList,$ar));
	}
	
	function array_index($aList, $value){
		$retval=false;
		for($i=0;$i<count($aList);$i++){
			if($value<=$aList[$i]) $retval=$i;
		}
		return $retval;
	}
	
	
	function getResolutions($srid,$convFact,$maxRes=false,$minRes=false){
		//se mercatore sferico setto le risoluzioni di google altrimenti uso quelle predefinite dall'elenco scale
		
		$aRes=array();
		if(($srid==900913)|($srid==3857)){
			$aRes = array_limit(array_slice(GCAuthor::$gMapResolutions,GMAP_MIN_ZOOM_LEVEL),$maxRes,$minRes);
		}
		else{
			foreach(GCAuthor::$defaultScaleList as $scaleValue) $aRes[]=$scaleValue/$convFact;
			$aRes=array_limit($aRes,$maxRes,$minRes);
		}
		return $aRes;
	}
	
	function getExtent($xCenter,$yCenter,$Resolution){
		//4tiles
		$aExtent=array();
		$aExtent[0] = $xCenter - $Resolution * TILE_SIZE ;
		$aExtent[1] = $yCenter - $Resolution * TILE_SIZE ;
		$aExtent[2] = $xCenter + $Resolution * TILE_SIZE ;
		$aExtent[3] = $yCenter + $Resolution * TILE_SIZE ;
		return $aExtent;
		
	}
	
	
	function crop_border($image, $border){
		if (2*$border > imagesx($image)) {
			$width = 0;
			$src_x = 0;
		} else {
			$width = imagesx($image) - 2*$border;
			$src_x = $border;
		} 
		if (2*$border > imagesy($image)) {
			$height = 0;
			$src_y = 0;
		} else {
			$height = imagesy($image) - 2*$border;
			$src_y = $border;
		} 
		$croppped_img = imagecreatetruecolor($width, $height);
		imagesavealpha($croppped_img, true);
		$transparent = imagecolorallocatealpha($croppped_img, 0, 0, 0, 127);
		imagefill($croppped_img, 0, 0, $transparent);
		imagecopy($croppped_img, $image, 0, 0, $src_x, $src_y, $width, $height);
		return $croppped_img;
	}
	
	function composeURL($URL) {
		$prot="(((ht|f)tp(s?))\://)+";
	    $domain = "((([[:alpha:]][-[:alnum:]]*[[:alnum:]])(\.[[:alpha:]][-[:alnum:]]*[[:alpha:]])+(\.[[:alpha:]][-[:alnum:]]*[[:alpha:]])+)|(([1-9]{1}[0-9]{0,2}\.[1-9]{1}[0-9]{0,2}\.[1-9]{1}[0-9]{0,2}\.[1-9]{1}[0-9]{0,2})+))";
	    $dir = "(/[[:alpha:]][-[:alnum:]]*[[:alnum:]])*";
	    $page = "(/[[:alpha:]][-[:alnum:]]*\.[[:alpha:]]{3,5})?";
	    $getstring = "(\?([[:alnum:]][-_%[:alnum:]]*=[-_%[:alnum:]]+)(&([[:alnum:]][-_%[:alnum:]]*=[-_%[:alnum:]]+))*)?";
	    $pattern1 = "^".$domain.$dir.$page.$getstring."$";
		$pattern2 = "^".$prot.$domain.$dir.$page.$getstring."$";
	    if(eregi($pattern1, $URL))
			return "http://".$URL;
		elseif(eregi($pattern2, $URL))
			return $URL;
		else
			return null;
	}
	function setLink($str,$param,$docPath,$projPath){
		if(strlen($str)==0) return ''; 
		if($param)
			$str=(strpos('?',$str)===false)?"$str?$param":"$str&$param";
		if($url=composeURL($str))
			return $url;
		elseif($url=composeURL($docPath.$str))
			return $url;
		elseif($url=composeURL($projPath.$docPath.$str))
			return $url;
		else
			return "http://".$projPath.$docPath.$str;
		/*if (preg_match('(([:/~a-zA-Z0-9_\-\.]+)\.([:/~a-zA-Z0-9]+))',$str))
			$str = preg_replace ('(([:/~a-zA-Z0-9_\-\.]+)\.([:/~a-zA-Z0-9]+))', 'http://$1.$2', $str);	
		elseif (preg_match('(([:/~a-zA-Z0-9_\-\.]+)\.([:/~a-zA-Z0-9]+))',$docPath.$str))
			$str = preg_replace ('(([:/~a-zA-Z0-9_\-\.]+)\.([:/~a-zA-Z0-9]+))', 'http://$1.$2', $docPath.$str);
		elseif (preg_match('(([:/~a-zA-Z0-9_\-\.]+)\.([:/~a-zA-Z0-9]+))',$projPath.$docPath.$str))
			$str = preg_replace ('(([:/~a-zA-Z0-9_\-\.]+)\.([:/~a-zA-Z0-9]+))', 'http://$1.$2', $projPath.$docPath.$str);		
		else
			$str = '';
		return str_ireplace("http://http://","http://",$str);	
		*/
	}
	
	function check_aggFunct($text){

		$fnList=Array("avg",
		"bit_and",
		"bit_or",
		"bool_or",
		"count",
		"every",
		"max",
		"min",
		"sum",
		"stddev",
		"variance");

		foreach($fnList as $fn){
			$fn=trim($fn);
			$regexp="|".$fn."((.+))|";
			
			if(preg_match($regexp,$text)){
				echo "<p>$regexp</p>";
				return true;
			}
		}
		return false;
	}
	
	function connInfofromPath($sPath){
		$pathInfo = explode("/",$sPath);
		if(defined('MAP_USER')){
			$mapUser = MAP_USER;
			$mapPwd = MAP_PWD;
		}
		else{
			$mapUser = DB_USER;
			$mapPwd = DB_PWD;
		}

		if(count($pathInfo)==1){//Mancano le informazioni di connessione, ho solo lo schema e il db ï¿½ quello del gisclient
			$connString = "user=".$mapUser." password=".$mapPwd." dbname=".DB_NAME." host=".DB_HOST." port=".DB_PORT;
			$datalayerSchema = $pathInfo[0];
		}
		else{//Abbiamo db e schema
			$datalayerSchema = $pathInfo[1];
			$connInfo=explode(" ",$pathInfo[0]);
			if(count($connInfo)==1)//abbiamo il nome del db
				$connString = "user=".$mapUser." password=".$mapPwd." dbname=".$connInfo[0]." host=".DB_HOST." port=".DB_PORT;
			else//abbiamo la stringa di connessione
				$connString = $pathInfo[0];
		}
		return array($connString,$datalayerSchema);
	}
    
	function connAdminInfofromPath($sPath){
		if(!isset($sPath)) return;
		$pathInfo = explode("/",$sPath);
		$datalayerSchema = $pathInfo[1];
		$connInfo=explode(" ",$pathInfo[0]);
		
		if(count($connInfo)==1)//abbiamo il nome del db
			$connString = "user=".DB_USER." password=".DB_PWD." dbname=".$connInfo[0]." host=".DB_HOST." port=".DB_PORT;
		else//abbiamo la stringa di connessione
			$connString = $pathInfo[0];
		
		return array($connString,$datalayerSchema);
	}
		
	function setDBPermission($db,$sk,$usr,$type,$mode,$table=''){
		if($type=='EXECUTE'){
			$sql="select specific_name,routine_name from information_schema.routines where routine_schema='$sk'";
			$result=pg_query($db,$sql);
			if(!$result) echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
			
			$ris=pg_fetch_all($result);
			for($i=0;$i<count($ris);$i++){
				$sql="select udt_name from information_schema.parameters where specific_name='".$ris[$i]["specific_name"]."' and specific_schema='$sk' order by ordinal_position";
				$fld=Array();
				$result=pg_query($db,$sql);
				if(!$result) echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
				$flds=pg_fetch_all($result);
				for($j=0;$j<count($flds);$j++) $fld[]=$flds[$j]["udt_name"];
				$prm=implode(',',$fld);
				
				if($ris[$i]["routine_name"]){
					$fName=$sk.'.'.$ris[$i]["routine_name"]."($prm)";
					$sql=($mode=='GRANT')?("GRANT EXECUTE ON FUNCTION $fName TO $usr"):("REVOKE EXECUTE ON FUNCTION $fName FROM $usr");
					$result=pg_query($db,$sql);
					if(!$result) echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
				}
			}
		}
		else{
			
			$sql=($mode=='GRANT')?("GRANT USAGE ON SCHEMA $sk TO $usr;"):("REVOKE USAGE ON SCHEMA $sk FROM $usr;");
			if(!$table){
				$result=pg_query($db,$sql);
				if(!$result) echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
			}
			else{
				$result=1;
			}
			if($result){
				$filter=($sk=='public')?("and table_name IN ('geometry_columns','spatial_ref_sys')"):(($table)?("and table_name ='$table'"):(""));
				$sql="select '$sk.'||table_name as tb from information_schema.tables where table_schema='$sk' $filter order by table_name";
				$result=pg_query($db,$sql);
				if(!$result) echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
				$ris=pg_fetch_all($result);
				for($i=0;$i<count($ris);$i++){
					$sql=($mode=='GRANT')?("GRANT SELECT ON TABLE ".$ris[$i]["tb"]." TO $usr;"):("REVOKE SELECT ON TABLE ".$ris[$i]["tb"]." FROM $usr;");
					$result=pg_query($db,$sql);
					if(!$result) echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
				}
			}
		}
	}
	function setLongApp(){
		ini_set('max_execution_time',LONG_EXECUTION_TIME);
		ini_set('memory_limit',LONG_EXECUTION_MEMORY);
	}
	function resetLongApp(){
		ini_restore('memory_limit');
		ini_restore('max_execution_time');
	}

	
	function rgb2html($r, $g=-1, $b=-1) {
		if (is_array($r) && sizeof($r) == 3)
			list($r, $g, $b) = $r;

		$r = intval($r); $g = intval($g);
		$b = intval($b);

		$r = dechex($r<0?0:($r>255?255:$r));
		$g = dechex($g<0?0:($g>255?255:$g));
		$b = dechex($b<0?0:($b>255?255:$b));

		$color = (strlen($r) < 2?'0':'').$r;
		$color .= (strlen($g) < 2?'0':'').$g;
		$color .= (strlen($b) < 2?'0':'').$b;
		return '#'.$color;
	}
	
	function html2rgb($color){
		if ($color[0] == '#')
			$color = substr($color, 1);

		if (strlen($color) == 6)
			list($r, $g, $b) = array($color[0].$color[1],
									 $color[2].$color[3],
									 $color[4].$color[5]);
		elseif (strlen($color) == 3)
			list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
		else
			return false;

		$r = hexdec($r); $g = hexdec($g); $b = hexdec($b);

		return array($r, $g, $b);
	}
	
	
	
	
	
	
	
	
	
/*-----------------------------------------------------------------------------------------------  Funzioni per ristrutturare ARRAY DATA in funzione di output pdf -----------------------------------------------------------------------------*/	
function getPdfParameters($data,$tableW){
	$j=0;
	$empty=0;
	$w=0;
	print_debug($data["tableheaders"],null,'paramPDF');
	//print_debug($data,null,'paramPDF');
	for($i=0;$i<count($data["tableheaders"]);$i++){
		if(in_array($data["fieldtype"][$i],Array(STANDARD_FIELD_TYPE,EMAIL_FIELD_TYPE))) {
			
			
			$h[]=to_latin($data["tableheaders"][$i]);
			if(is_numeric($data["columnwidth"][$i])) {
				$width[]=$data["columnwidth"][$i];
				$w+=$data["columnwidth"][$i];
			}
			else{
				$empty++;
				$width[]=0;
			}
		}
		 
	}
	if($w > 100){
		$w=0;
		$empty=count($width);
	}
	$avgWidth=floor((100.0-$w)/$empty);
	
	for($i=0;$i<count($width);$i++) {
		$cols[$h[$i]]=$h[$i];
		$colWidth[$h[$i]]["width"]=($width[$i]==0 || $empty==count($width))?($avgWidth*($tableW/100)):($width[$i]*($tableW/100));
		print_debug($h[$i]." : ".$colWidth[$h[$i]],null,'paramPDF');
	}
	$result["colWidth"]=$colWidth;
	$result["cols"]=$cols;
	return $result;
}

function setPdfData($data,$level=0,$header=Array(),$groupheader=Array(),$datagroupheader=Array()){

	if(isset($data["tableheaders"])){
		for($i=0;$i<count($data["tableheaders"]);$i++){
			$h=$data["tableheaders"][$i];
			$t=$data["fieldtype"][$i];
			$w=$data["columnwidth"][$i];
			if(in_array($t,Array(STANDARD_FIELD_TYPE,EMAIL_FIELD_TYPE))) $header[$i]=to_latin($h);
			if(in_array($t,Array(HEADER_GROUP_TYPE))) $groupheader[$i]=to_latin($h);
		}
	}
	if (!empty($data["groupheaders"])) $datagroupheader=$data["groupheaders"];
	if (empty($result)) $result=Array();

	if (isset($data["group"]) && is_array($data["group"])){						//  RAGGRUPPAMENTO
		foreach($data["group"] as $key=>$val){
			$text=$groupheader[$level].": ".to_latin($key);
			$result[]=Array("data"=>$text,"level"=>$level,"type"=>"text-aggregate");
			//if($val["groupdata"]) $result[]=getAggregate($val["groupdata"],$level,$datagroupheader);
			if(isset($val["groupdata"])){ 
				for($i=0;$i<count($val["groupdata"]);$i++){
					$text=$datagroupheader[$i].": ".to_latin($val["groupdata"][$i]);
					$result[]=Array("data"=>$text,"level"=>$level,"type"=>"text-aggregate");
				}
			}
			$result=array_merge($result,setPdfData($val,$level+1,$header,$groupheader,$datagroupheader));
		}
	}	
	else{	
		//  TABELLA DATI
		if (!$result) $result=Array();
		if($data["data"]){
			$result[]=getTable($data["data"],$level,$header);
			
		}
	}
	
	return $result;
}
function getXLSData($d){
	for($i=0;$i<count($d);$i++){
		$tmp=Array();
		if(!is_array($d[$i]["data"])){
			$tmp=@array_fill(0,(int)$d[$i]["level"],"");
			$tmp[]=$d[$i]["data"];
			$col=0;
			$result[]=$tmp;
		}
		else{
			$l = (int)$d[$i]["level"];
			if ($l>0)
				$tmp=array_fill(0,$l,"");
			else
				$tmp=Array();
			$header=array_merge($tmp,array_keys($d[$i]["data"][0]));
			$result[]=$header;
			for($j=0;$j<count($d[$i]["data"]);$j++){
				$data=array_merge($tmp,array_values($d[$i]["data"][$j]));
				$result[]=$data;
			}
			$result[]=Array();
		}
	}
	return $result;
}

function writeXLSTableData($tableheaders,$groupheaders,$fieldtype,$columnwidth,$result,$level,$row,&$objPHPExcel){


	/*	$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'Hello')
            ->setCellValue('B2', 'world!')
            ->setCellValue('C1', 'Hello')
            ->setCellValue('D2', 'world!');

		return;
*/
	if($result["group"]){
		foreach($result["group"] as $key =>$value) {
			$title = $tableheaders[$level] . ": ". $key;
			$objPHPExcel->getActiveSheet(0)->setCellValueByColumnAndRow($level, $row,  $title);
			$row++;
			if($groupheaders) {
				for($i=0;$i<count($groupheaders);$i++){
					$objPHPExcel->getActiveSheet(0)->setCellValueByColumnAndRow($level, $row++,  $groupheaders[$i] . ": " . to_latin($result["group"][$key]["groupdata"][$i]));
				}
			}
			writeTableData($tableheaders,$groupheaders,$fieldtype,$columnwidth,$result["group"][$key],$level+1,$row,$objPHPExcel);
		}
	}
	else{//Livello di tabella
			

				$objPHPExcel->getActiveSheet(0)->setCellValueByColumnAndRow($level-1, $row, "Dettaglio:");

				$fieldcheck = false;
				
				//titoli tabella di dettaglio
				for($k=$level;$k<count($tableheaders);$k++){
					$objPHPExcel->getActiveSheet(0)->setCellValueByColumnAndRow($k, $row,  $tableheaders[$k]);
					if($fieldtype[$k]<10) $fieldcheck = true;
				}
				
				//dati tabella di dettaglio
				for($datarow=0;$datarow < count($result["data"]);$datarow++){//Record	
					for($datacol=0;$datacol<count($result["data"][$datarow]);$datacol++){//campi 	
						$objPHPExcel->getActiveSheet(0)->setCellValueByColumnAndRow($level-1+$datacol, $row++,  $result["data"][$datarow][$datacol]);
					}				
				}
	}

}




function writeTableData($tableheaders,$groupheaders,$fieldtype,$columnwidth,$result,$level,&$csvTable){

	if($result["group"]){	//intestazione di gruppo (+ eventuale risultati aggregati) 

		foreach($result["group"] as $key =>$value) {
			//$csvRow=array();
			$csvRow=array_fill(0,count($tableheaders),'');
			$title = $tableheaders[$level] . ": ". $key;
			if(!isset($htmlTable)) $htmlTable='';
			$htmlTable .= "<div style=\"font-weight:bold;margin-left:".($level*10)."px;\">$title</div>"; 
			$csvRow[$level] = $title;
			$csvTable[] = $csvRow;
			if($groupheaders) $htmlTable .= writeGroupTableData($groupheaders,$result["group"][$key]["groupdata"],$level+1);
			if($groupheaders) {
				for($i=0;$i<count($groupheaders);$i++){
					$csvRow=array_fill(0,count($tableheaders),'');
					$csvRow[$level] = $groupheaders[$i] . ": " . to_latin($result["group"][$key]["groupdata"][$i]);
					$csvTable[] = $csvRow;
				}
			}
			//$level++;
			$htmlTable = writeTableData($tableheaders,$groupheaders,$fieldtype,$columnwidth,$result["group"][$key],$level+1,$csvTable);

		}
	}

	else{//Livello di tabella
			
				$rowCount=0;
				$htmlTable="<span style=\"font-weight:bold;\">Dettaglio:</span>";
				
				//Completo le righe della tabella con gli elementi mancanti
				$csvRow=array_fill(0,count($tableheaders),'');
				$csvRow[$level - 1] = "Dettaglio:";
				$csvTable[] = $csvRow;
				$htmlTable .="<table cellpadding=\"0\" cellspacing=\"0\" border=\"1\" class=\"tabinfo\"  style=\"width:100%\"  >";

				$tablerow = "";

					
				$fieldcheck = false;
				$csvRow=array_fill(0,count($tableheaders),'');
				for($k=$level;$k<count($tableheaders);$k++){
					$w = $columnwidth[$k]?'width=' . $columnwidth[$k] . '%':'';
					$tablerow .= "<th class=\"colonna1\" " . $w . " >" . $tableheaders[$k] . "</th>";
					
					$csvRow[$k-1]=$tableheaders[$k];
					if($fieldtype[$k]<10) $fieldcheck = true;
					//$csvTable[][$level]
				}
				$csvTable[]=$csvRow;
				//se non ci sono campi in tabella dettaglio esco
				if (!$fieldcheck) return '';
				$htmlTable .="<tr>" . $tablerow . "</tr>";//table headers
				
				for($row=0;$row < count($result["data"]);$row++){//Record	
					$tablerow = "";	
					$csvRow=array_fill(0,count($tableheaders),'');
					$offset = count($fieldtype) - count($result["data"][$row]);
					for($col=0;$col<count($result["data"][$row]);$col++){//campi 	
						$csvRow[$level-1+$col] = $result["data"][$row][$col];
						$idxCol = count($result["data"][$row]) - $col;
						$tablerow .= writeTableField($fieldtype[($offset + $col)],$result["data"][$row][$col]);
					}
					$htmlTable .="<tr>" . $tablerow . "</tr>";//table row
					$csvTable[]=$csvRow;					
				}
				
				$htmlTable .="</table>";
				$htmlTable = "<div style=\"margin-left:".($level*10)."px;\">".$htmlTable."</div>";
				

	}
	
	return $htmlTable;

}


function writeGroupTableData($groupheaders,$groupData,$level){

	$tablerow='';
	$htmlTable='';
	for($i=0;$i<count($groupheaders);$i++){
		$htmlTable .= "<p>" . $groupheaders[$i] . ": " . $groupData[$i] . "</p>";
	}
	$htmlTable = "<div style=\"margin-left:".($level*10)."px;\">".$htmlTable."</div>";
	return $htmlTable;

}

function writeTableField($ftype,$val){

	$htmlcell = "<td class=\"colonna2\">" . $val . "</td>";
	return $htmlcell;
	
}




function print_data(&$data,$key){



	if (is_array($data)){
	
		print(implode(";",array_values($data)));
	
	}
	else{
		
		//$key = key($data["group"]);
		//print_debug($key,null,'tabellacsv');
	
	}
	

}

function setCSVData($data){

	if (is_array($data["group"])){
		$key = key($data["group"])."";
		print_debug($key,null,'tabellacsv');
		print_debug($data["group"]["$key"],null,'tabellacsv');
		setCSVData($data["group"]["$key"]);
		
		
	}
	
	else{
	


		print_debug("FINITO",null,'tabellacsv');
		
	
	}

	return;




	if($data["tableheaders"]){
		for($i=0;$i<count($data["tableheaders"]);$i++){
			$h=$data["tableheaders"][$i];
			$t=$data["fieldtype"][$i];
			$w=$data["columnwidth"][$i];
			if(in_array($t,Array(STANDARD_FIELD_TYPE,EMAIL_FIELD_TYPE))) $header[$i]=to_latin($h);
			if(in_array($t,Array(HEADER_GROUP_TYPE))) $groupheader[$i]=to_latin($h);
		}
		print_debug($groupheader,null,'csvheader');
	}
	if ($data["groupheaders"]) $datagroupheader=$data["groupheaders"];
	if (!$result) $result=Array();
	

	if (is_array($data["group"])){						//  RAGGRUPPAMENTO
		foreach($data["group"] as $key=>$val){
			$text=$groupheader[$level].": ".to_latin($key);
			$result[]=Array("data"=>$text,"level"=>$level,"type"=>"text-aggregate");
			//if($val["groupdata"]) $result[]=getAggregate($val["groupdata"],$level,$datagroupheader);
			if($val["groupdata"]){ 
				for($i=0;$i<count($val["groupdata"]);$i++){
					$text=$datagroupheader[$i].": ".to_latin($val["groupdata"][$i]);
					$result[]=Array("data"=>$text,"level"=>$level,"type"=>"text-aggregate");
				}
			}
			$result=array_merge($result,setCSVData($val,$level+1,$header,$groupheader,$datagroupheader));
		}
	}	
	else{	
		//  TABELLA DATI
		if (!$result) $result=Array();
		if($data["data"]){
			//$result[]=Array("data"=>"Dettaglio : ","level"=>$level);
			$result[]=getTable($data["data"],$level,$header);
		}
	}
	
	return $result;
}


function to_latin(&$item) 
{
	$item=(strtoupper(CHAR_SET)=='UTF-8')?utf8_decode($item):$item;
	return $item;
}


function getAggregate($data,$level,$header){
	
	$ris["data"]=Array(array_combine($header,$data));
	$ris["data"]=$header[0]." ".$data[0];
	if(strtoupper(CHAR_SET)=='UTF-8') array_walk_recursive ($ris,'to_latin');	
	$ris["level"]=$level;
	$ris["type"]="aggregate";
	$ris=$header." ".$data;
	return $ris;
}


function getTable($data,$level,$header){
    $d=array();
	for($i=0;$i<count($data);$i++){
		$j=0;
		foreach($header as $h){
			$d[$i][$h]=$data[$i][$j];
			$j++;
		}
	}
	if(strtoupper(CHAR_SET)=='UTF-8') array_walk_recursive ($d,'to_latin');
	$ris["data"]=$d;
	$ris["level"]=$level;
	$ris["type"]="data";
	return $ris;
}

	
/*-----------------------------------------------------------------------------------------------  Funzioni di prova per estrarre i dati per i grafici dall'ARRAY DATA  -----------------------------------------------------------------------------*/	
	function getRHeaders($head,$fieldtype,$limit=null){
		for($i=0;$i<count($fieldtype);$i++)
			if($fieldtype[$i]==1) $headers[]=$head[$i];
		
		if ($limit) $headers=array_slice($headers,0,$limit);
		return $headers;
	}
	function getRData($data,$xCol=null,$yCol=null){
		if (is_array($data["group"])){						//  RAGGRUPPAMENTO

			foreach($data["group"] as $key=>$val){
				$pippo=getRData($val,$xCol,$key);
				$result[$key]=$pippo[$key];
				$result[$xCol]=$pippo[$xCol];
			}
		}
		else{												//  TABELLA DATI
			if($data["data"]){
				return getCols($data["data"],$xCol,$yCol);
			}
		}

		return $result;
	}
	function getCols($d,$x,$y){
		for($i=0;$i<count($d);$i++){
			$r[$x][]=$d[$i][0];
			$r[$y][]=$d[$i][1];
		}
		return $r;
	}
	
	//TODO ||||||||||||||||||||||||||!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	function NameReplace($name){

		$search = explode(","," ,ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,e,i,ø,u,.");
		$replace = explode(",","_,c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,e,i,o,u,_");
		if(strtoupper(CHAR_SET)=='UTF-8'){
			for($i=0;$i<count($search);$i++){
				$name=str_replace($search[$i],$replace[$i],trim($name));
			}
		}
		else
			$name = str_replace($search, $replace, trim($name));
		
		return $name;
		//return strtolower($name);
		
	}
	
	function niceName($name) {
	        $name = preg_replace('/\s+/', '_', $name);
	        $name = preg_replace('/_{2,}/', '_', $name);
	        $name = preg_replace('/^_+/', '', $name);
	        $name = preg_replace('/_+$/', '', $name);
		$name = NameReplace($name);
		$name = preg_replace('/[^a-z0-9_]+/i', '', $name);
		return $name;
	}
	
	
	function writeRFile($d,$h){
		if(strtoupper(CHAR_SET)=='UTF-8')
			$subst=Array(" "=>"_","à"=>"a","è"=>"e","ì"=>"i","ò"=>"o","ù"=>"u","%"=>"perc");
		else
			$subst=Array(" "=>"_","à"=>"a","è"=>"e","ì"=>"i","ò"=>"o","ù"=>"u","%"=>"perc");
			
		$dataDir=IMAGE_PATH;
		//$dataDir=DEBUG_DIR;
		$fName="dati_".rand().".txt";
		$f=fopen($dataDir.$fName,'w+');
		$keys=array_keys($d);
		$keys=array_values(array_diff($keys,Array($h)));
		$head=trim($h);
		
		foreach($subst as $from=>$to){
			$head=str_replace($from,$to,trim($head));
		}
		$headers[]=$head;
		
		for($i=0;$i<count($keys);$i++){
			$head=$keys[$i];
			foreach($subst as $from=>$to) $head=str_replace($from,$to,trim($head));
			$headers[]=$head;
		}
		fwrite($f,implode("\t",$headers)."\n");
		for($i=0;$i<count($d[$h]);$i++){
			$tmp=Array(trim($d[$h][$i]));
			foreach($keys as $k){
				$tmp[]=trim($d[$k][$i]);
			}
			fwrite($f,implode("\t",$tmp)."\n");
		} 

		fclose($f);
		return $dataDir.$fName;
	}
	
    
    function readTilecache_file() {
        $array = Array();
        $lines = file (TILECACHE_CFG);
        
        foreach( $lines as $line ) {
			$line=trim($line);
			$statement = preg_match("/^(?!;)(?P<key>[\w+\.\-]+?)\s*=\s*(?P<value>.+?)\s*$/",$line,$match);
			$session = preg_match("/^\[(.+)\]$/",$line,$match1);

			if($session){
				$myKey=$match1[1];
				$array[$myKey]=Array();
			}
			elseif( $statement  ) {
					$key    = $match[ 'key' ];
					$value    = $match[ 'value' ];   
					$array[$myKey][$key] = $value;
			}
		
			
        }
        return $array;
    }

	function write_tilecache_file( $arraydata ) {
	
		$lines="#GisClient Tilecache config file " . date("F j, Y, g:i a");
		foreach ($arraydata as $session=>$data){
			$lines.="\n[$session]\n";
			foreach ($data as $key=>$value){
				$lines.="$key=$value\n";
			}
		}
		$f = fopen (TILECACHE_CFG,"w");
		$ret=fwrite($f, $lines);
		fclose($f);
	}
	
	
	
	function OLD_updateTileCacheConfig(){

	return;
	
		$dbSchema=DB_SCHEMA;
		$sql="select mapset.project_name,mapset.mapset_name,theme_name,mapset_srid,mapset_extent,xc,yc,maxscale,minscale,sizeunits_id,param,layergroup.layergroup_id,outputformat_type,layergroup_name,url,single from $dbSchema.mapset
		inner join $dbSchema.mapset_layergroup using(mapset_name)
		inner join $dbSchema.layergroup using(layergroup_id)
		inner join $dbSchema.theme using (theme_id)
		inner join $dbSchema.e_outputformat on(layergroup.outputformat_id=e_outputformat.outputformat_id)
		left join $dbSchema.project_srs on (mapset.project_name=project_srs.project_name and mapset_srid=srid)".$this->filter." and layergroup.owstype_id=1 and mapset_layergroup.tilecache=1 order by layergroup_name;";
		
		print_debug($sql,null,'writecfg');
		
		$this->db->sql_query($sql);
		if($this->db->sql_numrows()==0) return;
		
		$res=$this->db->sql_fetchrowset();	
		$mapsetData=$res[0];
		$mapsetName=NameReplace($mapsetData["mapset_name"]);
		$projectName=NameReplace($mapsetData["project_name"]);
		$prefix=$projectName."_".$mapsetName;
		
		$convFact = GCAuthor::$aInchesPerUnit[$mapsetData["sizeunits_id"]]*MAP_DPI;
		$maxRes = isset($mapsetData["maxscale"])?round($mapsetData["maxscale"]/$convFact,8):false;
		$minRes = isset($mapsetData["minscale"])?round($mapsetData["minscale"]/$convFact,8):false;
		
	
		//Normalizzo rispetto all'array delle risoluzioni
		$resolutions = getResolutions($mapsetData["mapset_srid"],$maxRes,$minRes);
		$mapsetExtent=array();
		$mapsetXc = $mapsetData["xc"];
		$mapsetYc = $mapsetData["yc"];
		$mapsetExtent[0]=$mapsetXc - $resolutions[0]*TILE_SIZE;
		$mapsetExtent[1]=$mapsetYc - $resolutions[0]*TILE_SIZE;
		$mapsetExtent[2]=$mapsetXc + $resolutions[0]*TILE_SIZE;
		$mapsetExtent[3]=$mapsetYc + $resolutions[0]*TILE_SIZE;
		
		$tilecacheLayers = parse_ini_file(TILECACHE_CFG,true);
		
		print_array($tilecacheLayers);

		
		for($i=0;$i<count($res);$i++){
			$row=$res[$i];
			$mapName=$row["single"]?NameReplace($row["theme_name"]):NameReplace(NameReplace($row["theme_name"].'_'.$row["layergroup_name"]));
			$tLayerName=$prefix."_".$mapName;
			$tilecacheLayers[$tLayerName]["type"]="WMSLayer";
			$tilecacheLayers[$tLayerName]["url"]=isset($row["url"])?$row["url"]:GISCLIENT_LOCAL_OWS_URL."?project=".$projectName."&map=".$mapName;
			if(isset($row["param"])) $tilecacheLayers[$tLayerName]["url"].="&projparam=".$row["param"];
			$tilecacheLayers[$tLayerName]["layers"][] = $row["single"]?$row["layergroup_name"]:$row["layers"];
			$tilecacheLayers[$tLayerName]["mime_type"]=$row["outputformat_type"];
			$tilecacheLayers[$tLayerName]["srs"]="EPSG:".$row["mapset_srid"];
			$tilecacheLayers[$tLayerName]["resolutions"]=implode(",",$resolutions);
			$tilecacheLayers[$tLayerName]["bbox"]=implode(",",$mapsetExtent);
		}	
		
		print_array($tilecacheLayers);

		$cfgText=array();
		foreach($tilecacheLayers as $lay=>$settings){
			$cfgText[]="[$lay]";
			foreach($settings as $key=>$value){
				if(is_array($value)) $value=implode(",",$value);
				$cfgText[]="$key=$value";
			}
		}
		print_array($cfgText);		
		
		$fileContent=implode("\n",$cfgText);
		$f = fopen (TILECACHE_CFG,"w");
		$ret=fwrite($f, $fileContent);
		fclose($f);
	}
		
/*---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------*/

/* funzione http_build_url , se manca */

if (!function_exists('http_build_url'))
{
	define('HTTP_URL_REPLACE', 1);				// Replace every part of the first URL when there's one of the second URL
	define('HTTP_URL_JOIN_PATH', 2);			// Join relative paths
	define('HTTP_URL_JOIN_QUERY', 4);			// Join query strings
	define('HTTP_URL_STRIP_USER', 8);			// Strip any user authentication information
	define('HTTP_URL_STRIP_PASS', 16);			// Strip any password authentication information
	define('HTTP_URL_STRIP_AUTH', 32);			// Strip any authentication information
	define('HTTP_URL_STRIP_PORT', 64);			// Strip explicit port numbers
	define('HTTP_URL_STRIP_PATH', 128);			// Strip complete path
	define('HTTP_URL_STRIP_QUERY', 256);		// Strip query string
	define('HTTP_URL_STRIP_FRAGMENT', 512);		// Strip any fragments (#identifier)
	define('HTTP_URL_STRIP_ALL', 1024);			// Strip anything but scheme and host
	
	// Build an URL
	// The parts of the second URL will be merged into the first according to the flags argument. 
	// 
	// @param	mixed			(Part(s) of) an URL in form of a string or associative array like parse_url() returns
	// @param	mixed			Same as the first argument
	// @param	int				A bitmask of binary or'ed HTTP_URL constants (Optional)HTTP_URL_REPLACE is the default
	// @param	array			If set, it will be filled with the parts of the composed url like parse_url() would return 
	function http_build_url($url, $parts=array(), $flags=HTTP_URL_REPLACE, &$new_url=false)
	{
		$keys = array('user','pass','port','path','query','fragment');
		
		// HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
		if ($flags & HTTP_URL_STRIP_ALL)
		{
			$flags |= HTTP_URL_STRIP_USER;
			$flags |= HTTP_URL_STRIP_PASS;
			$flags |= HTTP_URL_STRIP_PORT;
			$flags |= HTTP_URL_STRIP_PATH;
			$flags |= HTTP_URL_STRIP_QUERY;
			$flags |= HTTP_URL_STRIP_FRAGMENT;
		}
		// HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
		else if ($flags & HTTP_URL_STRIP_AUTH)
		{
			$flags |= HTTP_URL_STRIP_USER;
			$flags |= HTTP_URL_STRIP_PASS;
		}
		
		// Parse the original URL
		$parse_url = parse_url($url);
		
		// Scheme and Host are always replaced
		if (isset($parts['scheme']))
			$parse_url['scheme'] = $parts['scheme'];
		if (isset($parts['host']))
			$parse_url['host'] = $parts['host'];
		
		// (If applicable) Replace the original URL with it's new parts
		if ($flags & HTTP_URL_REPLACE)
		{
			foreach ($keys as $key)
			{
				if (isset($parts[$key]))
					$parse_url[$key] = $parts[$key];
			}
		}
		else
		{
			// Join the original URL path with the new path
			if (isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH))
			{
				if (isset($parse_url['path']))
					$parse_url['path'] = rtrim(str_replace(basename($parse_url['path']), '', $parse_url['path']), '/') . '/' . ltrim($parts['path'], '/');
				else
					$parse_url['path'] = $parts['path'];
			}
			
			// Join the original query string with the new query string
			if (isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY))
			{
				if (isset($parse_url['query']))
					$parse_url['query'] .= '&' . $parts['query'];
				else
					$parse_url['query'] = $parts['query'];
			}
		}
			
		// Strips all the applicable sections of the URL
		// Note: Scheme and Host are never stripped
		foreach ($keys as $key)
		{
			if ($flags & (int)constant('HTTP_URL_STRIP_' . strtoupper($key)))
				unset($parse_url[$key]);
		}
		
		
		$new_url = $parse_url;
		
		return 
			 ((isset($parse_url['scheme'])) ? $parse_url['scheme'] . '://' : '')
			.((isset($parse_url['user'])) ? $parse_url['user'] . ((isset($parse_url['pass'])) ? ':' . $parse_url['pass'] : '') .'@' : '')
			.((isset($parse_url['host'])) ? $parse_url['host'] : '')
			.((isset($parse_url['port'])) ? ':' . $parse_url['port'] : '')
			.((isset($parse_url['path'])) ? $parse_url['path'] : '')
			.((isset($parse_url['query'])) ? '?' . $parse_url['query'] : '')
			.((isset($parse_url['fragment'])) ? '#' . $parse_url['fragment'] : '')
		;
	}
}

function addFinalSlash($dir) {
	if(substr($dir, -1) != '/') return $dir.'/';
	return $dir;
}


function GetRGBColor($s, &$r, &$g, &$b) {
//  $s = str_replace("#", "", $s);
  $r = hexdec(substr($s, 0, 2));
  $g = hexdec(substr($s, 2, 2));
  $b = hexdec(substr($s, 4, 2));
}

// restituisce un array con valore e colore per la statistica scelta
  //   $data è un array con i dati
  //   $totClasses è il numero totali di classi
  //   $startColor è il colore di inizio in esadecimale
  //   $endColor è il colore di fine in esadecimale
  function getColorClassification($totClass, $startColor, $endColor) {
    $res = array();

    $colors = array();
    // Calcolo colori
    GetRGBColor($startColor, $srart_r, $srart_g, $srart_b);
    GetRGBColor($endColor,   $end_r, $end_g, $end_b);
    $delta_r = ($end_r - $srart_r) / ($totClass - 1);
    $delta_g = ($end_g - $srart_g) / ($totClass - 1);
    $delta_b = ($end_b - $srart_b) / ($totClass - 1);

    for ($i = 0; $i < $totClass; $i++) {
      $colors[$i] = sprintf('%02X', $srart_r) . sprintf('%02X', $srart_g) . sprintf('%02X', $srart_b);
      $srart_r += $delta_r;
      $srart_g += $delta_g;
      $srart_b += $delta_b;
    }
    return $colors;
  }
