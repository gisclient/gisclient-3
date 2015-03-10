<?php
// FILE CHE PERMETTE DI RECUPERARE I GRUPPI ESISTENTI UN' ELENCO ESISTENTE (STANDARD GRUPPI PLONE)

	require_once "../../config/config.php";

	if(!defined('USER_SCHEMA')){
		$msg="<p>Amministratori Locali dei Progetti non previsti</p>";
		$btn[]="\n\t<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Annulla\">";
		return;
	}
	if($_SESSION["USERNAME"]!=SUPER_USER){
		$msg="<p>Impossibile modificare gli amministratori del progetto.<br>Non si dispone dei diritti necessari.</p>";
		$btn[]="\n\t<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Annulla\">";
		return;
	}
	$project=$prm["project"];
    
    $db = GCApp::getDB();
    $JOIN=($this->mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
    $sql="select distinct '$project' as project_name,user_group.username,case when (coalesce(X.username,'')<>'') then 1 else 0 end as presente from ".USER_SCHEMA.".user_group $JOIN (SELECT username FROM ".DB_SCHEMA.".project_admin WHERE project_name='$project') X on (user_group.username=X.username) where coalesce(user_group.username,'')<>'' order by user_group.username";
    $results = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

	if (is_array($results) && count($results)>0){
		foreach ($results as $val){
			$data[]=Array("presente"=>$val["presente"],"project_name"=>$val["project_name"],"username"=>$val["username"]);
		}
	}
	else{
		$msg=(!defined('USER_SCHEMA'))?("Utenti non previsti"):("Nessun dato presente");
		$enabled=(!defined('USER_SCHEMA'))?(0):(1);
	}
	$btn[]="\n\t<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Annulla\">";
	$btn[]="<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Salva\">";
	$btn[]="<input type=\"button\" name=\"azione\" class=\"hexfield\" style=\"width:130px;margin-right:5px;margin-left:5px;\" value=\"Seleziona Tutti\" onclick=\"javascript:selectAll(this,'username');\">\n";
	$button="modifica";
?>