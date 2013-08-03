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
	$usr=new userApps();
	$ris=$usr->getGisclientAdmin($project,$this->mode);
	if (is_array($ris) && count($ris)>0){
		foreach ($ris as $val){
			$data[]=Array("presente"=>$val["presente"],"project_name"=>$val["project_name"],"username"=>$val["username"],"groupname"=>$groupname);
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