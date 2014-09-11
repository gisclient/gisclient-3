<?php
require_once "../../config/config.php";
$layer=$this->parametri["layer"];

$db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
if(!$db->db_connect_id) die("<p>Impossibile connettersi al database!</p>");
$JOIN=($this->mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
$sql="select X.*,$layer as layer_id,Y.wms,Y.wfs,Y.wfst,case when coalesce(Y.groupname,'')='' then 0 else 1 end as presente from (select distinct groupname from ".DB_SCHEMA.".groups order by groupname) X LEFT JOIN (SELECT * FROM ".DB_SCHEMA.".layer_groups WHERE layer_id=$layer)  Y using (groupname)";
if($db->sql_query($sql)){
	$ris=$db->sql_fetchrowset();
	if (count($ris)){
		foreach($ris as $val){
			extract($val);
			if($this->mode!=0 || $presente==1)
				$data[]=ARRAY("presente"=>$presente,"groupname"=>$groupname,"wms"=>$wms,"wfs"=>$wfs,"wfst"=>$wfst);
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
