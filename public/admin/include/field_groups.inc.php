<?php
require_once "../../config/config.php";
$qtfield=$this->parametri["field"];
$project=$this->parametri["project"];
$db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
if(!$db->db_connect_id) die("<p>Impossibile connettersi al database!</p>");
$JOIN=($this->mode==0)?(" INNER JOIN "):(" LEFT JOIN ");

$sql="SELECT X.groupname,CASE WHEN (SELECT count(*)=0 FROM ".DB_SCHEMA.".field_groups  WHERE field_id=$qtfield) THEN 1 WHEN coalesce(Y.groupname,'')='' THEN 0 ELSE 1 end as presente,coalesce(editable,0) as editable FROM ".DB_SCHEMA.".groups X LEFT JOIN (SELECT groupname,editable FROM ".DB_SCHEMA.".field_groups  WHERE field_id=$qtfield) Y USING(groupname);";
$msg=null;
if($db->sql_query($sql)){
	$ris=$db->sql_fetchrowset();
	if (count($ris)){
		foreach($ris as $val){
			extract($val);
			if($this->mode!=0 || $presente==1)
				$data[]=ARRAY("presente"=>$presente,"groupname"=>$groupname,"editable"=>$editable);
				
		}
	}
	else{
		$data=Array();
		$msg="";
	}

}
else{
	
	$data=Array();
	$msg="<b style=\"color:red\">Errore</b>";
}
	
$btn[] = '<button name="azione" class="hexfield" type="submit" value="annulla">'.GCAuthor::t('button_cancel').'</button>';
$btn[] = '<button name="azione" class="hexfield" type="submit" value="salva">'.GCAuthor::t('button_save').'</button>';
$button="modifica";
?>