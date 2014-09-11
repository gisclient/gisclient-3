<?php

define('PK_FILE',ADMIN_PATH."include/primary_keys.xml");

function _writeHeader($pr,$lev,$name,$file){
	$f=fopen(ADMIN_PATH."export/$file",'w+');
	$str="--Project:$pr\n--Type:$lev\n--Name:$name\nset search_path to @DB_SCHEMA@,public;\n";
	fwrite($f,$str);
	fclose($f);
}
function _getChild($lev,$export){		//FUNZIONE CHE RECUPERA I FIGLI DEL LIVELLO
	$db=new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
	if(!$db->db_connect_id)  die( "Impossibile connettersi al database");
	$sql=($export)?("SELECT id,name,leaf FROM ".DB_SCHEMA.".e_level WHERE export>0 AND struct_parent_id=(select id from ".DB_SCHEMA.".e_level WHERE name='$lev') order by export;"):("SELECT id,name,leaf FROM ".DB_SCHEMA.".e_level WHERE struct_parent_id=$lev;");
	
	$ris=Array();
	if(!$db->sql_query($sql)) print_debug($sql,null,"export");
	else
		$ris=$db->sql_fetchrowset();
	return $ris;
}

function _getFieldName($level){
	switch($level){
		default:
			$fieldName=$level."_name";
			break;
	}
	return $fieldName;
}
function _getFieldValue($table,$fld,$pk,$pkVal){		// SI PUO' ANCHE MODIFICARE PER FAR RESTITUIRE UNA LISTA DI CAMPI
	$db=new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
	if(!$db->db_connect_id)  die( "Impossibile connettersi al database");
	for($i=0;$i<count($pk);$i++){
		$flt[]=$pk[$i]."='".$pkVal[$i]."'";
	}
	$filter.=implode(' AND ',$flt);
	$sql="SELECT $fld FROM ".DB_SCHEMA.".$table WHERE $filter;";
	if(!$db->sql_query($sql)) echo "<p>$sql</p>";
	return $db->sql_fetchfield($fld);
}



function import($f,$parentId,$parentName,$newName='',$parentkey=null){
	//$pkey=parse_ini_file(ADMIN_PATH."include/primary_keys.ini");
	$pkey=_getPKeys();
	$standardTime=ini_get('max_execution_time');
	$standardMem=ini_get('memory_limit');
	ini_set('max_execution_time',LONG_EXECUTION_TIME);
	ini_set('memory_limit',LONG_EXECUTION_MEMORY);
	$db=new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
	if(!$db->db_connect_id)  die( "Impossibile connettersi al database");
	$fName=$f;
	$rows=file($fName);
	$type=str_replace("--Type:","",trim($rows[1]));
	$sql="SELECT name FROM ".DB_SCHEMA.".e_level WHERE id=$type;";
	if ($qt)
	$name=str_replace("--Name:","",$rows[2]);
	$newName=($newName)?($newName):($name);
	$arrSubst=Array("@PARENTID@"=>"'".$parentId."'","@PARENTKEY@"=>$parentkey,"@PROJECTNAME@"=>$parentName,"@DB_SCHEMA@"=>DB_SCHEMA,"@OBJECTNAME@"=>$newName,"\\n"=>"\n");

	if(!file_exists($fName)){
		$err[]="Il File $f non esiste.";
		return $err;
	}
	
	$handle=fopen(ROOT_PATH.'config/debug/test_import.sql','w+');
	for($i=2;$i<count($rows);$i++){
		$sql=trim(str_replace("\n",'',str_replace("\r","",$rows[$i])));
		foreach($arrSubst as $key=>$value){
			$sql=str_replace($key,$value,$sql);
		}
		$flt=Array();
		$tables=Array();
		if(preg_match_all('|@FOREIGNKEY(.+)@|Ui',$sql,$out,PREG_SET_ORDER)){
			for($k=0;$k<count($out);$k++){
				$str=$out[$k][0];
				if($out[$k][1]=="[qtrelation][0]"){
					$newVal="0";
				}
				elseif($out[$k][1]=="[catalog][]"){
					$newVal="-1";
				}
				else{
					if(preg_match_all('|(\[(.+)\]\[(.*)\])+|Ui',$str,$out1,PREG_SET_ORDER)){
						$fld=$out1[0][2].".".$out1[0][2]."_id";
						for($j=0;$j<count($out1);$j++){
							$tables[]=DB_SCHEMA.".".$out1[$j][2];
							$flt[]=$out1[$j][2].".".$out1[$j][2]."_name='".$out1[$j][3]."'";
						}
					}

					$flt[]="project.project_name='$parentName'";
					$tables[]=DB_SCHEMA.'.project';
					$sqlVal="SELECT $fld as val FROM ".implode(",",array_unique($tables))." WHERE ".implode(' AND ',array_unique($flt)).";";
					if($db->sql_query($sqlVal)){
						$newVal=$db->sql_fetchfield('val');
					}
					else 
						echo "<p>$sqlVal</p>";
				}	
				$sql=str_replace($out[$k][0],$newVal,$sql);	
			}
		}
		if(preg_match_all('|@KEY\[(.+)\]\[(.+)\]@|Ui',$sql,$newkey)){
			for($j=0;$j<count($newkey[0]);$j++){
				if(!$newid[$newkey[1][$j]][$newkey[2][$j]]) $newid[$newkey[1][$j]][$newkey[2][$j]]=$newkey[2][$j];
				$sql=str_replace($newkey[0][$j],$newid[$newkey[1][$j]][$newkey[2][$j]],$sql);	
			}
		}
		if(preg_match("|@NEWKEY_I\[(.+)\]\[(.+)\]@|Ui",$sql,$out)) {
			if($out[1]=='qtrelation' && !$out[2])
				$newId[$out[1]][$out[2]]="0";
			else{
				$table=str_replace('_id','',$out[1]);
				$sqlId="select ".DB_SCHEMA.".new_pkey('$table','".$out[1]."_id') as newid;";
				$db->sql_query($sqlId);
				$newid[$out[1]][$out[2]]=$db->sql_fetchfield('newid');
			}
		}
		elseif(preg_match("|@NEWKEY_V\[(.+)\]\[(.+)\]@|Ui",$sql,$out)) {
			if(in_array($out[1],Array("username","group")) ){
				$newid[$out[1]][$out[2]]=$out[2];
			}
			else{
				$table=str_replace('_name','',$out[1]);
				$sqlId="select ".DB_SCHEMA.".new_pkey_varchar('$table','".$out[1]."_name','$out[2]') as newid;";
				$db->sql_query($sqlId);
				$newid[$out[1]][$out[2]]=$db->sql_fetchfield('newid');
			}
		}
		//if($out){
		//	echo "<p>Sostituzione di $out[0] con ".$newid[$out[1]][$out[2]]." in :<br>$sql</p>";
			$sql=str_replace($out[0],$newid[$out[1]][$out[2]],$sql);	
		//}
		fwrite($handle,$sql."\n");
		$out=Array();
	
		if(!$db->sql_query($sql)){
			for($j=0;$j<count($db->error_message);$j++) $err[]="ROW $i : ".$db->error_message[$j]["text"]."\n<p>$sql</>";
			$db->error_message=Array();
		}
	
	}
	fclose($handle);
	ini_set('max_execution_time',$standardTime);
	ini_set('memory_limit',$standardMem);	
	return $err;
}
function import_raster($d,$ext,$layergroup_id,$catalog_id,$srid=-1,$filtro="",$delete=0){
	//$shapeDir="/shape/";
	$db=new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
	if(!$db->db_connect_id)  die( "Impossibile connettersi al database");
	$sql="select coalesce(base_path,'')||'/".$shapeDir."'||coalesce(shape_dir,'')||'/".$d."' as dir from ".DB_SCHEMA.".catalog inner join ".DB_SCHEMA.".project using (project_name) where catalog_id=$catalog_id";
	if(!$db->sql_query($sql)) return -1;
	$dir=str_replace("//","/",$db->sql_fetchfield("dir"));
	require_once "filesystem.php";
	$fileList=Array();
	foreach($ext as $e){
		$tmpF=elenco_file($dir,$e,$filtro);
		for($i=0;$i<count($tmpF);$i++) $fileList[]=$tmpF[$i];
	}
	if($delete) {
		$sql="DELETE FROM ".DB_SCHEMA.".layer WHERE layergroup_id=$layergroup_id;";
		if(!$db->sql_query($sql)){
			for($j=0;$j<count($db->error_message);$j++) $err[]="ROW $i : ".$db->error_message[$j]["text"]."\n<p>$sql</>";
			$db->error_message=Array();
		}
	}
	foreach($fileList as $f){
		$tmp=explode(".",$f);
		array_pop($tmp);
		$fname=@implode("",$tmp);
		$sql="INSERT INTO ".DB_SCHEMA.".layer(layer_id,layergroup_id,layer_name,catalog_id,layertype_id,data,data_srid,layer_order) VALUES(".DB_SCHEMA.".new_pkey('layer','layer_id'),$layergroup_id,'".$fname."',$catalog_id,4,'".str_replace("//","/",$d."/".$f)."',$srid,-1)";
		if(!$db->sql_query($sql)){
			for($j=0;$j<count($db->error_message);$j++) $err[]="ROW $i : ".$db->error_message[$j]["text"]."\n<p>$sql</>";
			$db->error_message=Array();
		}
	}
	return (count($err))?($err):(Array());
	
}
function _getPKeys_orig(){
	require_once ADMIN_PATH.'lib/ParseXml.class.php';
	$xml = new ParseXml();
	$xml->LoadFile(PK_FILE);
	$ris=$xml->ToArray();
	return $ris;
}
function _getPKeys(){
	require_once ADMIN_PATH.'lib/ParseXml.class.php';
	$xml = new ParseXml();
	$xml->LoadFile(PK_FILE);
	$ris=$xml->ToArray();
	foreach($ris as $key=>$val){
		$struct["pkey"][$key]=(is_array($ris[$key]["pkey"]))?($ris[$key]["pkey"]):(Array($ris[$key]["pkey"]));
		$struct["parent"][$key]=$ris[$key]["parent"];
		$struct["table"][$key]=$ris[$key]["table"];
	}
	return $struct;
}
function _getListValue($level,$val,$db){
	$db=new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);			//CONNESSIONE AL DB
	if(!$db->db_connect_id)  die( "Impossibile connettersi al database");
	$pk=_getPKeys();
	if($level=='project') $result[]="[$level][$val]";
	else
	{
		while(trim($pk["parent"][$level])){
			$table=$pk["table"][$level];
			if(count($pk['pkey'][$level])>1){
				
			}
			else{
				if($pk["parent"][$level]) $parentPK=$pk["pkey"][$pk["parent"][$level]][0];
				else
					$parentPK="null";
				$sql="SELECT ".$level."_name as name,$parentPK as parentpk FROM ".DB_SCHEMA.".$table WHERE ".$pk['pkey'][$level][0]."='$val';";
				
			}
			if(!$db->sql_query($sql)) echo "<p>$sql</p>";
			$name=$db->sql_fetchfield('name');
			if($level=="qtrelation" && !$val){
				$result[]="[$level][0]";
				return implode("",$result);
			}
			else
				$result[]="[$level][$name]";
			$level=$pk["parent"][$level];
			$val=$db->sql_fetchfield('parentpk');
			
		}
	}
	return implode("",$result);
}
function _isPKey($fld,$pk,$lev=""){
	if($lev!="") $pk=Array("$lev"=>$pk[$lev]);
	foreach($pk as $key=>$arr)
		if(count($arr)==1) if($arr[0]==$fld) return true;
		if(count($arr)>1) for($i=0;$i<count($arr);$i++) if($arr[$i]==$fld) return true;
	return false;
}
function _export($fileName="export.sql",$currentLevel,$projName,$structure,$start=0,$startName,$parentValue,&$valutatedKey,&$Errors=Array()){
	//MODIFICO I PARAMETRI DEL PHP PER PERMETTERE LE ESPORTAZIONI
	
	$standardTime=ini_get('max_execution_time');
	$standardMem=ini_get('memory_limit');
	ini_set('max_execution_time',LONG_EXECUTION_TIME);
	ini_set('memory_limit',LONG_EXECUTION_MEMORY);
	
	$db=new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);			//CONNESSIONE AL DB
	if(!$db->db_connect_id)  die( "Impossibile connettersi al database");
	
	$pkey=$structure["pkey"];		//RECUPERO LE CHIAVI PRIMARIE DELLA STRUTTURA
	$parent=$structure["parent"][$currentLevel];
	if($start){
		_writeHeader($projName,$currentLevel,$currentLevel,$fileName);			//Scrivo le intestazioni del file di EXPORT
		$startName=_getFieldName($currentLevel);
	}
	$child=_getChild($currentLevel,1);
	$parentKey=$pkey[$parent];		//CHIAVI PRIMARIE DEL LIVELLO PADRE
	if($parentKey && !$start)											//FILTRO PER RECUPERARE I DATI DEL LIVELLO CORRENTE
		for($i=0;$i<count($parentKey);$i++) $filter[]="$parentKey[$i]='".$parentValue[$parentKey[$i]]."'";
	else
		for($i=0;$i<count($pkey[$currentLevel]);$i++) $filter[]=$pkey[$currentLevel][$i]."='".$parentValue[$pkey[$currentLevel][$i]]."'";

	$filter=(count($filter))?(implode(' AND ',$filter)):('');
	$sql="SELECT * FROM ".DB_SCHEMA.".".$structure["table"][$currentLevel]." WHERE $filter;";
	if(!$db->sql_query($sql)) {
		
		echo "<p>Errore nell'estrazione dei Dati del Livello $currentLevel<br>$sql</p>";
		$Errors[]="<p>Errore nell'estrazione dei Dati del Livello $currentLevel</p>";
	}
	$recordSet=$db->sql_fetchrowset();
	for($i=0;$i<count($recordSet);$i++){	//RISULTATI DA INSERIRE NEL FILE
		$rec=$recordSet[$i];
		$j=0;
		$fldIns=Array();
		$valIns=Array();
		$j=0;
		
		foreach($rec as $key=>$val){		//Ciclo su tutti i campi
			//SFRUTTO IL PRIMO GIRO PER ESTRARRE I TIPI DI DATO
			if($i==0) $fldType[$key]=$db->sql_fieldtype($j);
			
			/*MODIFICHE*/
			if($valutatedKey[$key][$val]){	//CHIAVE GIA' VALUTATA (SONO TUTTE LE PARENT KEY)
				$lev=str_replace('_name','',str_replace('_id','',$key));
				$values[$key]=($key==$startName)?("'@OBJECTNAME@'"):("'@KEY[$lev][$val]@'");
			}
			if($start && $key=="layer_id" && $currentLevel=="qt"){
				$values[$key]="@PARENTKEY@";
				$valutatedKey[$key][$val]=1;
			}
			elseif($start && in_array($key,$parentKey)){	//CHIAVE PRIMARIA DEL PARENT (METTO IL VALORE PASSATO NELL'IMPORT)
				$values[$key]="@PARENTID@";
				$valutatedKey[$key][$val]=1;
			}
			elseif(in_array($key,$pkey[$currentLevel])){		//CHIAVI PRIMARIE DEL LIVELLO
				$lev=str_replace('_name','',str_replace('_id','',$key));
				if(count($pkey[$currentLevel])==1){
					$values[$key]=($key=="srid")?($val):(($fldType[$key]=="int4")?("'@NEWKEY_I[$lev][$val]@'"):(($key==$startName)?("'@OBJECTNAME@'"):("'@NEWKEY_V[$lev][$val]@'")));
					$valutatedKey[$key][$val]=1;
					$pkeyVal[$key]=$val;
				}
				else{
					if($key=="srid")
						$values[$key]=$val;
					elseif($valutatedKey[$key][$val]==1){
						
						$values[$key]=($key==$startName)?("'@OBJECTNAME@'"):("'@KEY[$lev][$val]@'");
					}
					else{
						$tree=_getListValue(str_replace('_id','',str_replace('_name','',$key)),$val);
						$values[$key]=($tree)?("@FOREIGNKEY".$tree."@"):("'$val'");
					}
					//if($key=="qt_id") echo "<p>".$values[$key]."</p>";;
				}
			}

			elseif(_isPKey($key,$pkey)){					//CHIAVI ESTERNE
				if(($key=='qtrelation_id' && !$val) || ($key=='catalog_id' && $val==-1)){
					$values[$key]="$val";
					$valutatedKey[$key][$val]=1;
				}
				elseif($start && $key=="theme_id"){
					$values[$key]="@PARENTID@";
					$valutatedKey[$key][$val]=1;
				}
				else{
					$lev=str_replace('_name','',str_replace('_id','',$key));
					$tree=_getListValue($lev,$val);
					$values[$key]=($key==$startName)?("'@OBJECTNAME@'"):(($valutatedKey[$key][$val])?("'@KEY[$lev][$val]@'"):("@FOREIGNKEY".$tree."@"));
				}
			}
			elseif ($key==$startName){												//CAMPO NOME NOME DEL LIVELLO DI PARTENZA
					$values[$key]="'@OBJECTNAME@'";
			}
			else{
				$values[$key]=($val)?("'".addslashes(trim(str_replace(chr(13),"\\n",$val)))."'::".$fldType[$key]):((!isset($val))?("null::".$fldType[$key]):(($val==='')?('\'\''):("0::".$fldType[$key])));
			}
			$j++;
		}
	
		$list_value=@implode(",",$values);
		$list_flds=@implode(",",array_keys($values));
		$s="INSERT INTO ".$structure["table"][$currentLevel]."($list_flds) VALUES($list_value);\n";
		$f=fopen(ADMIN_PATH."export/$fileName",'a+');
		$sql=str_replace(chr(13),"",str_replace(chr(10),"",$s))."\n";print_debug($sql,null,'testExport');
		if(!fwrite($f,$sql)) echo "<p>IMPOSSIBILE SCRIVERE SUL FILE $fileName:<br>$sql</p>";
		fclose($f);
		//CHIAMATA RICORSIVA	
		if($child){
			foreach($child as $ch){
				$struct["child"][$currentLevel]=_export($fileName,$ch["name"],$projName,$structure,0,$startName,$pkeyVal,$valutatedKey,$Errors);
			}
		}
		$values=Array();	//Svuoto Array deli Valori
		
	}
	ini_set('max_execution_time',$standardTime);
	ini_set('memory_limit',$standardMem);
	return $struct;
	
}
function _exportNew($fileName="export.sql",$arr,$lev,$project,$start=0,$startName='',$parentValue=Array(),&$valutatedKey){
	//MODIFICO I PARAMETRI DEL PHP PER PERMETTERE LE ESPORTAZIONI
	
	$standardTime=ini_get('max_execution_time');
	$standardMem=ini_get('memory_limit');
	ini_set('max_execution_time',LONG_EXECUTION_TIME);
	ini_set('memory_limit',LONG_EXECUTION_MEMORY);
	$pkey=parse_ini_file(ADMIN_PATH."include/primary_keys.ini");		//Recupero le Chiavi Primarie della Struttura
	$db=new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);			//CONNESSIONE AL DB
	if(!$db->db_connect_id)  die( "Impossibile connettersi al database");
	
	
	$struct["name"]=$arr[$lev]["name"];	//LIVELLO CORRENTE
	$el=$arr[$lev];						//INFO LIVELLO CORRENTE
	
	
	$child=_getChild($lev,1);
	$parent_key=$pkey[$arr[$arr[$lev]["parent"]]["name"]];		//CHIAVI PRIMARIE DEL LIVELLO PADRE
	//QUERY CHE RECUPERA I VALORI DELLE PK DEL LIVELLO ATTUALE DA ESPORTARE (SOLO nel CASO start=0) altrimenti i valori solo quelli dell'id

	if($start){
		_writeHeader($project,$lev,$struct["name"],$fileName);			//Scrivo le intestazioni del file di EXPORT
		$startName=_getFieldName($struct["name"]);
		echo $startName;
	}
	if($parent_key && !$start)											//FILTRO PER RECUPERARE I DATI DEL LIVELLO CORRENTE
		for($i=0;$i<count($parent_key);$i++) $filter[]="$parent_key[$i]='".$parentValue[$parent_key[$i]]."'";
	else
		for($i=0;$i<count($pkey[$el["name"]]);$i++) $filter[]=$pkey[$el["name"]][$i]."='".$parentValue[$pkey[$el["name"]][$i]]."'";
	$filter=(count($filter))?(implode(' AND ',$filter)):('');
	$sql="SELECT * FROM ".DB_SCHEMA.".$struct[name] WHERE $filter;";
	if(!$db->sql_query($sql)) echo "<p>Errore $sql</p>";
	$recordSet=$db->sql_fetchrowset();
	$child=_getChild($lev,1);
	echo $startName;
	for($i=0;$i<count($recordSet);$i++){	//RISULTATI DA INSERIRE NEL FILE
		$rec=$recordSet[$i];
		$j=0;
		$fldIns=Array();
		$valIns=Array();
		
		foreach($rec as $key=>$val){		//Ciclo su tutti i campi
			if($key==$startName) echo "$level $key=>$val<br>";
			//SFRUTTO IL PRIMO GIRO PER ESTRARRE I TIPI DI DATO
			if($i==0) $fldType[$key]=$db->sql_fieldtype($j);
			
			/*FINE MODIFICHE*/
			if(in_array($key,$parent_key)){									//CHIAVI DEL PARENT
				if(preg_match('/(.+)_id$/Ui',$key,$out)){
					$table=$out[1];
					$name=_getFieldValue($table,$table."_name",Array($key),Array($val));
					$values[$key]=($start)?("@PARENTID@"):("@NEWPARENTKEY[".$table."][".$val."]@");
				}
				else{
					$values[$key]=($startName==$key)?("'@OBJECTNAME@'"):("@NEWPARENTKEY[".$struct["name"]."][".$val."]@");
				}
			}
			elseif(in_array($key,$pkey[$el["name"]])){						// CHIAVI PRIMARIE
				$valutatedKey[$key][$val]=1;
				if(count($pkey[$el["name"]])>1 && preg_match('/(.+)_id$/Ui',$key,$out)){
					$table=$out[1];
					$name=_getFieldValue($table,$table."_name",Array($key),Array($val));
					$values[$key]="@NEWPARENTKEY[".$table."][".$val."]@";
				}
				elseif(preg_match('/(.+)_id$/Ui',$key)){
					$values[$key]="@NEWKEY[".$struct["name"]."][".$val."]@";
				}
				else{
					$values[$key]=($startName==$key)?("'@OBJECTNAME@'"):("@NEWKEY[".$struct["name"]."][".$val."]@");//("'$val'::$fldType[$key]");
				}
				$pkeyVal[$key]=$val;
			}
			elseif(_isPKey($key,$pkey)){										// FOREIGN KEY
				if(preg_match('|(.+)_id$|Ui',$key,$out)){
					if($start && $struct["name"]=="qt"){
						$values[$key]="@PARENTKEY@";
					}
					else{
						$table=str_replace("_id","",$key);
						if($valutatedKey[$key][$val]) 
							$values[$key]="@NEWPARENTKEY[".$table."][".$val."]@";
						else{
							$tree=_getListValue($table,$val);
							$values[$key]="@FOREIGNKEY".$tree."@";
						}
					}
				}
				else
					$values[$key]="'$val'::$fldType[$key]";
			}
			elseif ($key==$startName){												//CAMPO NOME NOME DEL LIVELLO DI PARTENZA
					$values[$key]="'@OBJECTNAME@'";
			}
			else{																	//CAMPO NORMALE
				/*switch($key){
					case "catalog_id":
						$catalogName=($startName=="catalog_name")?("@OBJECTNAME@"):(_getFieldValue('catalog','catalog_name',Array($key),Array($val)));
						if(!$catalogName) $values[$key]="-1";
						else
							$values[$key]="(SELECT catalog_id FROM ".DB_SCHEMA.".catalog WHERE catalog_name='$catalogName' AND project_name='@PROJECTNAME@')";
						break;
					case "theme_id":
						$themeName=($startName=="theme_name")?("@OBJECTNAME@"):(_getFieldValue('theme','theme_name',Array($key),Array($val)));
						$values[$key]="(SELECT theme_id FROM ".DB_SCHEMA.".theme WHERE theme_name='$themeName' AND project_name='@PROJECTNAME@')";
						break;
					default:
						$values[$key]=($val)?("'".addslashes(trim(str_replace(chr(13),"\\n",$val)))."'::".$fldType[$key]):((!isset($val))?("null::".$fldType[$key]):(($val==='')?('\'\''):("0::".$fldType[$key])));
						break;
				}*/
				$values[$key]=($val)?("'".addslashes(trim(str_replace(chr(13),"\\n",$val)))."'::".$fldType[$key]):((!isset($val))?("null::".$fldType[$key]):(($val==='')?('\'\''):("0::".$fldType[$key])));
			}
			$j++;
		}
		$list_value=@implode(",",$values);
		$list_flds=@implode(",",array_keys($values));
		$s="INSERT INTO ".$struct["name"]."($list_flds) VALUES($list_value);\n";
		$f=fopen(ADMIN_PATH."export/$fileName",'a+');
		$sql=str_replace(chr(13),"",str_replace(chr(10),"",$s))."\n";
		fwrite($f,$sql);
		fclose($f);
		//CHIAMATA RICORSIVA
		if($child){
			
			foreach($child as $ch){
				$tb=$ch["name"];
				$struct["child"][$lev]=_exportNew($fileName,$arr,$ch["id"],$project,0,$startName,$pkeyVal,$valutatedKey);
			}
		}
		$values=Array();	//Svuoto Array deli Valori
	}
	ini_set('max_execution_time',$standardTime);
	ini_set('memory_limit',$standardMem);
	return $struct;
}
?>