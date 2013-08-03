<?php
require_once "../../config/config.php";
	
	$db=new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
	if(!$db->db_connect_id)  die( "Impossibile connettersi al database");
	$project=$this->parametri["project"];
	$JOIN=($this->mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
	
	$sql="select '$project' as project_name,groupname as group_name,case when coalesce(project_name,'')='' then 0 else 1 end as presente from (select 'Authenticated Users' as groupname UNION select distinct groupname from ".USER_SCHEMA.".groups) X $JOIN (select * from ".DB_SCHEMA.".project_groups where project_name='$project') Y on (groupname=group_name) order by group_name";
	if($db->sql_query($sql)){
		$ris=$db->sql_fetchrowset();
		if (count($ris)){
			foreach($ris as $val){
				extract($val);
				if($this->mode!=0 || $presente==1)
				$data[]=Array("project_name"=>$project_name,"group_name"=>$group_name,"presente"=>$presente);
			}
			
		}
		else{
			$data=Array();
			$msg="Nessun Gruppo definito nel portale";
		}
		if(!count($data)) $msg="Nessun Gruppo definito nel portale";
	}
	else{
		
			$data=Array();
			//$msg=(!defined('USER_SCHEMA'))?("Gruppi di utenti non previsti"):("<b style=\"color:red\">Errore</b>");
			$msg="Nessun Gruppo definito nel portale";
			//$enabled=(!defined('USER_SCHEMA'))?(0):(1);
		}
		
	$btn[]="\n\t<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Annulla\">";
	$btn[]="<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Salva\">";
	$btn[]="<input type=\"button\" name=\"azione\" class=\"hexfield\" style=\"width:130px;margin-right:5px;margin-left:5px;\" value=\"Seleziona Tutti\" onclick=\"javascript:selectAll(this,'group');\">\n";
	$button="modifica";
?>