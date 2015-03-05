<?php
	require_once "../../config/config.php";
	
	$db=new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
	if(!$db->db_connect_id)  die( "Impossibile connettersi al database");
	$map=$this->parametri["mapset"];
	
	$JOIN=($this->mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
	$sql="select distinct mapset_name,link_id,CASE WHEN COALESCE(mapset_name, '') <> '' THEN 1 ELSE 0 END  AS presente,link.link_name,link_def,link_order,project_name from ".DB_SCHEMA.".qt_link inner join (select distinct qt_id from ".DB_SCHEMA.".mapset_qt where mapset_name='$map') as foo using(qt_id) inner join ".DB_SCHEMA.".link using (link_id) $JOIN (select * from ".DB_SCHEMA.".mapset_link where mapset_name='$map') as foo1 using (link_id) order by link_name";
	//echo $sql;
	if($db->sql_query($sql)){
		$ris=$db->sql_fetchrowset();
		if (count($ris)){
			foreach($ris as $val){
				extract($val);
				if($this->mode!=0 || $presente==1)
				$data[]=Array("mapset_name"=>$mapset_name,"link_id"=>$link_id,"link_name"=>$link_name,"presente"=>$presente,"link_def"=>$link_def,"link_order"=>$link_order);
			}
			
		}
		else{
			$data=Array();
			$msg="Nessun Link definito nel mapset";
		}
		if(!count($data)) $msg="Nessun Link definito nel mapset";
	}
	else{
			$data=Array();
			$msg="<b style=\"color:red\">Errore</b>";
	}
		
	$btn[]="\n\t<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Annulla\">";
	$btn[]="<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Salva\">";
	$btn[]="<input type=\"button\" name=\"azione\" class=\"hexfield\" style=\"width:130px;margin-right:5px;margin-left:5px;\" value=\"Seleziona Tutti\" onclick=\"javascript:selectAll(this,'layergroup');\">\n";
	$button="modifica";
?>