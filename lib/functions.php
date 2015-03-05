<?php
	function vcheck($value){
	
		return (isset($value) && !empty($value))?$value:false;
	
	}
	
	function jsonString($myArray){
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
		echo $jsonstr;
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
				// vecchia riga modificata come sotto
				//$sql="select udt_name from information_schema.parameters where specific_name='".$ris[$i]["specific_name"]."' and specific_schema='$sk' order by ordinal_position";
				$sql="select udt_schema,udt_name from information_schema.parameters where specific_name='".$ris[$i]["specific_name"]."' and specific_schema='$sk' order by ordinal_position";
				$fld=Array();
				$result=pg_query($db,$sql);
				if(!$result) echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
				$flds=pg_fetch_all($result);
				//for($j=0;$j<count($flds);$j++) $fld[]=$flds[$j]["udt_name"]; vecchia istruzione
				//inizio modifiche carlio
				if (!$flds) continue;
				for($j=0;$j<count($flds);$j++) {
					if(($flds[$j]["udt_schema"] == 'pg_catalog') || ($flds[$j]["udt_schema"] == 'public')) {
						$fld[]=$flds[$j]["udt_name"];
					}
					else {
				    	$fld[]=$flds[$j]["udt_schema"].".".$flds[$j]["udt_name"];
					}
				}
				// fine modifiche carlio
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
	
	function writeRFile($d,$h){
		if(strtoupper(CHAR_SET)=='UTF-8')
			$subst=Array(" "=>"_",utf8_encode("à")=>"a",utf8_encode("è")=>"e",utf8_encode("ì")=>"i",utf8_encode("ò")=>"o",utf8_encode("ù")=>"u","%"=>"perc");
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
	
/*---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------*/
?>
