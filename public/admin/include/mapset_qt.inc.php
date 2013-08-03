<?php
require_once "../../config/config.php";
	
	$db=new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
	if(!$db->db_connect_id)  die( "Impossibile connettersi al database");
	$project=$this->parametri["project"];
	$map=$this->parametri["mapset"];
	$JOIN=($this->mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
	$sql="SELECT DISTINCT coalesce(mapset_name,'') as mapset_name,layer_id,CASE WHEN COALESCE(mapset_name, '') <> '' THEN 1 ELSE 0 END  AS presente,'$project' as project_name,coalesce(layer.layer_name,'') as layer_name,layer_order,coalesce(theme_title,'') as theme_title FROM 
		(".DB_SCHEMA.".layer inner join ".DB_SCHEMA.".layergroup USING(layergroup_id) inner join ".DB_SCHEMA.".theme using(theme_id)) $JOIN (select * from ".DB_SCHEMA.".mapset_qt where mapset_name='$map') as foo using (qt_id) WHERE project_name='$project' and layer_id in (select distinct layer_id from ".DB_SCHEMA.".mapset_layergroup inner join ".DB_SCHEMA.".layer using(layergroup_id) where mapset_name='$map') 
		order by theme_title,qt_name";

	if($db->sql_query($sql)){
		$ris=$db->sql_fetchrowset();
		if (count($ris)){
			foreach($ris as $val){
				extract($val);
				if($this->mode!=0 || $presente==1)
					$data[]=Array("project_name"=>$project_name,"mapset_name"=>$mapset_name,"qt_name"=>$qt_name,"qt_id"=>$qt_id,"qt_order"=>$qt_order,"theme_title"=>$theme_title,"presente"=>$presente);
			}
		}
		else{
			$data=Array();
			$msg="Nessun query template definito nel mapset";
		}
		if(!count($data)) $msg="Nessun query template definito";
	}
	else{
			$data=Array();
			$msg="<b style=\"color:red\">Errore</b>";
		}
		
	$btn[]="\n\t<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Annulla\">";
	$btn[]="<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Salva\">";
	$btn[]="<input type=\"button\" name=\"azione\" class=\"hexfield\" style=\"width:130px;margin-right:5px;margin-left:5px;\" value=\"Seleziona Tutti\" onclick=\"javascript:selectAll(this,'qt');\">\n";
	$button="modifica";	
?>