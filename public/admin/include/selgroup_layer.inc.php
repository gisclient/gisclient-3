<?php
require_once "../../config/config.php";
	$project=$this->parametri["project"];
	$selgroup=$this->parametri["selgroup"];
	$db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
	if(!$db->db_connect_id) die("<p>Impossibile connettersi al database!</p>");
	$JOIN=($this->mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
	$sql="SELECT DISTINCT 
    selgroup_id,A.layer_name,coalesce(layer_id,0) as layer_id,
    CASE WHEN COALESCE(selgroup_id, 0) > 0 THEN 1 ELSE 0 END  AS presente,
    coalesce(layergroup_title,'') as layergroup_name,
    coalesce(theme_title,'') as theme_title
    from ((".DB_SCHEMA.".layer inner join ".DB_SCHEMA.".layergroup using(layergroup_id)) inner join ".DB_SCHEMA.".theme using(theme_id)) A $JOIN
    (select * from ".DB_SCHEMA.".selgroup_layer inner join ".DB_SCHEMA.".selgroup using(selgroup_id) where selgroup_id=$selgroup) as foo using (layer_id) WHERE queryable=1 and A.project_name='$project' order by theme_title,layergroup_name,layer_name";
	//echo "<p>$sql</p>";
	if($db->sql_query($sql)){
		$ris=$db->sql_fetchrowset();
		if (count($ris)){
			foreach($ris as $val){
				extract($val);
				if($this->mode!=0 || $presente==1)
					$data[]=Array("project_name"=>$project_name,"selgroup_id"=>$selgroup_id,"layer_id"=>$layer_id,"presente"=>$presente,"layer_name"=>$layer_name,"layergroup_name"=>$layergroup_name,"theme_title"=>$selgroup_name,"theme_title"=>$theme_title);
			}
			
		}
		else{
			$data=Array();
			$msg="Nessun Livello Interrogabile definito nel Gruppo di selezione";
		}
		if(!count($data)) $msg="Nessun Livello Interrogabile definito nel Gruppo di selezione";
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
