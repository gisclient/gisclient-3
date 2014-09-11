<?php

	require_once "../../config/config.php";
	include_once ADMIN_PATH."lib/functions.php";
	include_once ADMIN_PATH."lib/gcFeature.class.php";
	$azione=$_REQUEST["azione"];
	$db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
	if(!$db->db_connect_id)  die( "Impossibile connettersi al database");
	switch($azione){
		case "classify_save":
			include_once ADMIN_PATH."lib/savedata.class.php";
			require_once ADMIN_PATH."db/db.layer.php";
			return;
			break;
		case "classify":
			$_REQUEST["mode"]='';
			$layerId=$_REQUEST['prm_layer'];
			$field=$_REQUEST['classField'];
			$startC=explode(' ',$_REQUEST["startCol"]);
			$endC=explode(' ',$_REQUEST["endCol"]);
			$totClass=$_REQUEST["nbins"];
			
			$feature=new gcFeature();
			$feature->initFeature($layerId);

			$fld=$feature->getFeatureField();
			
			foreach($fld as $id=>$f){
				if($field==$f["name"]) $field=$f;
			}
			
			$newConn=pg_connect($field["connection_string"]);
			$sql="SELECT DISTINCT $field[name] as val FROM $field[schema].$field[table] ORDER BY 1;";
			$result = pg_query($newConn, $sql);
			if(!$totClass){
				$totClass=pg_num_rows($result);
				$delta_r = ($endC[0] - $startC[0]) / ($totClass - 1);
				$delta_g = ($endC[1] - $startC[1]) / ($totClass - 1);
				$delta_b = ($endC[2] - $startC[2]) / ($totClass - 1);
				for($i=0;$i<$totClass;$i++){
					$ris=pg_fetch_array($result,$i);
					$colors = sprintf('%02X', $startC[0]) . sprintf('%02X', $startC[1]) . sprintf('%02X', $startC[2]);
					$res[]=Array(
						"val"=>$ris["val"],
						"color"=>$colors,
						"name"=>'class_'.($i+1),
						"title"=>$ris["val"],
						"condition"=>"([$field[name]] = '$ris[val]')",
						"legend_type"=>1
						);
					$startC[0] += $delta_r;
					$startC[1] += $delta_g;
					$startC[2] += $delta_b;
				}
			}
			else{
				$delta_r = ($endC[0] - $startC[0]) / ($totClass - 1);
				$delta_g = ($endC[1] - $startC[1]) / ($totClass - 1);
				$delta_b = ($endC[2] - $startC[2]) / ($totClass - 1);
				switch($field["data_type"]){
					case 1:		//CAMPO TESTO
						for($i=0;$i<$totClass;$i++){
							$ris=pg_fetch_array($result,$i);
								
							$colors = sprintf('%02X', $startC[0]) . sprintf('%02X', $startC[1]) . sprintf('%02X', $startC[2]);
							$res[]=Array(
								"val"=>"classe ".($i+1),
								"color"=>$colors,
								"name"=>"class_".($i+1),
								"title"=>"class ".($i+1),
								"condition"=>"",
								"legend_type"=>1
								
							);
							$startC[0] += $delta_r;
							$startC[1] += $delta_g;
							$startC[2] += $delta_b;
						}
						break;
					case 2:		//CAMPO NUMERICO
					case 3:		//CAMPO DATA
						$sql="SELECT max($field[name]) as maxVal,min($field[name]) as minVal,(max($field[name])-min($field[name]))/$totClass as delta FROM $field[schema].$field[table];";
						$result = pg_query($newConn, $sql);
						$ris=pg_fetch_row($result);
					
						$delta=$ris[2];
						$minV=$ris[1];
						$maxV=$ris[0];
						$startV=$minV;
						$delta_r = ($endC[0] - $startC[0]) / ($totClass - 1);
						$delta_g = ($endC[1] - $startC[1]) / ($totClass - 1);
						$delta_b = ($endC[2] - $startC[2]) / ($totClass - 1);
						for ($i = 0; $i < $totClass; $i++) {
							$colors = sprintf('%02X', $startC[0]) . sprintf('%02X', $startC[1]) . sprintf('%02X', $startC[2]);
							$startC[0] += $delta_r;
							$startC[1] += $delta_g;
							$startC[2] += $delta_b;
							if ($field["data_type"]==2) 
								$sql="SELECT round($startV +($delta*($i+1)),2) as val";
							else
								$sql="SELECT '\''||($startV::date +(($delta::varchar||' day')::interval*($i+1))::date)::varchar||'\'' as val";
							$db->sql_query($sql);
							$endV=$db->sql_fetchfield('val');
							
							$res[]=Array(
								"val"=>$startV.' - '.$endV,
								"color"=>$colors,
								"name"=>"class_".($i+1),
								"title"=>'class '.($i+1),//$startV.' - '.$endV,
								"condition"=>"(([$field[name]] > $startV) AND ([$field[name]] < $endV))",
								"name"=>"classe ".($i+1),
								"legend_type"=>"1"
							);
							$startV=$endV;
						}
						
						
						break;
					
					default:
						break;
				}
			}
			
			
			/*if ($field["data_type"]==1){	//SE NON E' UN DATO NUMERICO
				
				
				if(!$_REQUEST["nbins"]) 
				
				
				$ris=Array("val"=>"");
				for($i=0;$i<$totClass;$i++){
					$ris=(!$_REQUEST["nbins"])?(pg_fetch_array($result,$i)):(Array("val"=>""));
					else
						
					$colors = sprintf('%02X', $startC[0]) . sprintf('%02X', $startC[1]) . sprintf('%02X', $startC[2]);
					$res[]=Array("val"=>$ris["val"],"color"=>$colors,"condition"=>$field["name"]."='".$ris["val"]."'");
					$startC[0] += $delta_r;
					$startC[1] += $delta_g;
					$startC[2] += $delta_b;
				}
			}
			else{
				$sql="SELECT max($field[name]) as maxVal,min($field[name]) as minVal,(max($field[name])-min($field[name]))/$totClass as delta FROM $field[schema].$field[table];";
				$result = pg_query($newConn, $sql);
				$ris=pg_fetch_row($result);
			
				$delta=$ris[2];
				$minV=$ris[1];
				$maxV=$ris[0];
				$startV=$minV;
				$delta_r = ($endC[0] - $startC[0]) / ($totClass - 1);
				$delta_g = ($endC[1] - $startC[1]) / ($totClass - 1);
				$delta_b = ($endC[2] - $startC[2]) / ($totClass - 1);
				
				for ($i = 0; $i < $totClass; $i++) {
					$colors = sprintf('%02X', $startC[0]) . sprintf('%02X', $startC[1]) . sprintf('%02X', $startC[2]);
					$startC[0] += $delta_r;
					$startC[1] += $delta_g;
					$startC[2] += $delta_b;
					if ($field["data_type"]==2) 
						$sql="SELECT $startV +($delta*($i+1)) as val";
					else
						$sql="SELECT '\''||($startV::date +(($delta::varchar||' day')::interval*($i+1))::date)::varchar||'\'' as val";
					$db->sql_query($sql);
					$endV=$db->sql_fetchfield('val');
					
					$res[]=Array("val"=>$startV.' - '.$endV,"color"=>$colors,"condition"=>"($field[name] > $startV) AND ($field[name] < $endV)");
					$startV=$endV;
				}
			}*/
			
			header('Cache-Control: no-cache, must-revalidate');
			//header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			header('Content-type: application/json');
			echo json_encode($res);
			return;

			break;
		case "getExportObject":
			$level=$_REQUEST["level"];
			$project=$_REQUEST["project"];
			switch ($level){
				case 2:
					$sql="SELECT project_name as id,project_name as name FROM ".DB_SCHEMA.".project WHERE project_name='$project' ORDER BY project_name";
					break;
				case 5:
					$sql="SELECT theme_id as id,theme_name as name FROM ".DB_SCHEMA.".theme LEFT JOIN ".DB_SCHEMA.".project using (project_name) WHERE project_name='$project' ORDER BY theme_name";
					break;
				case 10:
					$sql="SELECT layergroup_id as id,layergroup_name as name FROM ".DB_SCHEMA.".layergroup LEFT JOIN ".DB_SCHEMA.".theme using (theme_id) LEFT JOIN ".DB_SCHEMA.".project using (project_name) WHERE project_name='$project' ORDER BY layergroup_name";
					break;
				case 11:
					break;
				case 12:
					break;
				case 14:
					break;
			}
			if(!$db->sql_query($sql))
				echo "{error:'Impossibile eseguire la query'}";
			else{
				$ris=$db->sql_fetchrowset();
				$obj="";
				$opt[]="['-1','Seleziona un oggetto']";
				for($i=0;$i<count($ris);$i++){
					$o=$ris[$i];
					$val=parse_code($o["name"]);
					//$val=$o["name"];
					$opt[]="['$o[id]','$val']";
				}
				$res="{name:'obj_id', val:[".implode(",",$opt)."]}";
			}
			break;
		case "request":
			$fk=$_REQUEST["parent_level"]."_id";
			$table=$_REQUEST["level"];
			$id=$_REQUEST["id"];
			switch($table){
				case "layer":
					$fld=$table."_id as id,trim(".$table."_name) as title";
					break;
				default:
					$fld=$table."_id as id,trim(".$table."_title) as title";
					break;
			}
			$sql="SELECT $fld FROM ".DB_SCHEMA.".$table WHERE $fk=$id order by 2;";
			if(!$db->sql_query($sql))
				echo "{error:'Impossibile eseguire la query $sql'}";
			else{
				$ris=$db->sql_fetchrowset();
				$opt = array();
				foreach($ris as $v){
					array_push($opt, array('id'=>$v['id'], 'name'=>$v['title']));
				}
				$res = json_encode($opt).",'$table'";
			}
			break;
		default:
			break;
	}
	header("Content-Type: text/plain; Charset=".CHAR_SET);
	echo $res;
?>
