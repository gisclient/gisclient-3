<?php
require_once "../../config/config.php";

	$user=(isset($this->parametri["users"]))?$this->parametri["users"]:null;
	$usr=new userApps(null);
	$ris=$usr->getUser($user,$this->mode);
	
	if (is_array($ris) && count($ris)>0){
		foreach($ris as $val){
			extract($val);
			$data[]=Array("username"=>$username,"cognome"=>$cognome,"nome"=>$nome,"pwd"=>isset($pwd)?$pwd:'');
		}
		
	}
	else{
		$data=Array();
		$msg="Nessun Utente definito";
	}
	$btn[] = '<button name="azione" class="hexfield" type="submit" value="annulla">'.GCAuthor::t('button_cancel').'</button>';
	$btn[] = '<button name="azione" class="hexfield" type="submit" value="salva">'.GCAuthor::t('button_save').'</button>';
	
	if($usr->editUser==1) $button=($this->currentMode=='view')?("modifica"):("nuovo");
?>
