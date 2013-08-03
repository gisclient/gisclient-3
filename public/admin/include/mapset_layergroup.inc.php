<?php
require_once "../../config/config.php";
$project=$this->parametri["project"];
$mapset=$this->parametri["mapset"];
$db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
if(!$db->db_connect_id) die("<p>Impossibile connettersi al database!</p>");
$JOIN=($this->mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
$sql="SELECT DISTINCT coalesce(mapset_name,'') as mapset_name,X.layergroup_id, CASE WHEN COALESCE(mapset_name, '') <> '' THEN 1 ELSE 0 END  AS presente,'$project' as project_name,X.layergroup_name,X.layergroup_title,X.theme_title,COALESCE(status::integer, 0) AS status,COALESCE(hide ::integer, 0) AS hide,COALESCE(refmap::integer, 0) AS refmap from (select layergroup_id,layergroup_name,layergroup_title,theme_title from ".DB_SCHEMA.".layergroup inner join ".DB_SCHEMA.".theme using (theme_id) where project_name='$project') as X $JOIN (select * from ".DB_SCHEMA.".mapset_layergroup where mapset_name='$mapset') as foo using(layergroup_id) order by X.theme_title,X.layergroup_title";
//echo $sql;
if($db->sql_query($sql)){
	$ris=$db->sql_fetchrowset();
	if (count($ris)){
		foreach($ris as $val){
			extract($val);
			if($this->mode!=0 || $presente==1)
				$data[]=ARRAY("presente"=>$presente,"layergroup_id"=>$layergroup_id,"layergroup_title"=>$layergroup_title,"theme_title"=>$theme_title,"status"=>$status,"hide"=>$hide,"refmap"=>$refmap);
		}
	}
	else{
		$data=Array();
		$msg="Nessun layer definito nel mapset";
}
	if(!count($data)) $msg="Nessun layer definito";
}
else{
		$data=Array();
		$msg="<b style=\"color:red\">Errore</b>";
	}
	
$btn[] = '<button name="azione" class="hexfield" type="submit" value="annulla">'.GCAuthor::t('button_cancel').'</button>';
$btn[] = '<button name="azione" class="hexfield" type="submit" value="salva">'.GCAuthor::t('button_save').'</button>';
$button="modifica";
?>