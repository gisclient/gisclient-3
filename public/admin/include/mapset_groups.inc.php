<?php
require_once "../../config/config.php";
	
	$db=new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
	if(!$db->db_connect_id)  die( "Impossibile connettersi al database");
	$project=$this->parametri["project"];
	$map=$this->parametri["mapset"];
	$JOIN=($this->mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
	//$sql="SELECT DISTINCT coalesce(mapset_name,'') as mapset_name,group_name, CASE WHEN COALESCE(mapset_name, '') <> '' THEN 1 ELSE 0 END  AS presente,project_name,edit from ".DB_SCHEMA.".project_groups $JOIN (select * from ".DB_SCHEMA.".mapset_groups where mapset_name='$map') as foo using (group_name) where project_name='$project' order by group_name";
	$sqlVirtual= ($_SESSION["VIRTUAL_GROUPS"])?(" union (select '".implode("','",$_SESSION["VIRTUAL_GROUPS"])."' as groupname) "):("");
	
	$sql="SELECT DISTINCT coalesce(mapset_name,'') as mapset_name,X.groupname as group_name, CASE WHEN COALESCE(mapset_name, '') <> '' THEN 1 ELSE 0 END  AS presente,'$project' as project_name,edit,redline from ((select groupname from ".USER_SCHEMA.".groups) $sqlVirtual) X $JOIN (select * from ".DB_SCHEMA.".mapset_groups where mapset_name='$map') as foo on (X.groupname=group_name) order by X.groupname";
	//echo $sql;
	if($db->sql_query($sql)){
		$ris=$db->sql_fetchrowset();
		if (count($ris)){
			foreach($ris as $val){
				extract($val);
				if($this->mode!=0 || $presente==1)
				$data[]=Array("project_name"=>$project_name,"group_name"=>$group_name,"mapset_name"=>$mapset_name,"edit"=>$edit,"redline"=>$redline,"presente"=>$presente);
			}
			
		}
		else{
			$data=Array();
			$msg="Nessun Gruppo definito per il progetto";
		}
		if(!count($data)) $msg="Nessun Gruppo definito per il progetto";
	}
	else{
			$data=Array();
			$msg="<b style=\"color:red\">Errore</b>";
	}
	$btn[]="\n\t<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Annulla\">";
	$btn[]="<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Salva\">";
	$btn[]="<input type=\"button\" name=\"azione\" class=\"hexfield\" style=\"width:130px;margin-right:5px;margin-left:5px;\" value=\"Seleziona Tutti\" onclick=\"javascript:selectAll(this,'group');\">\n";
	$button="modifica";
?>
