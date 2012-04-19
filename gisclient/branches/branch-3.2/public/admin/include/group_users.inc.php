<?php
require_once "../../config/config.php";

	$group=isset($this->parametri["groups"])?$this->parametri["groups"]:array();
	$usr=new userApps(null);
	$ris=$usr->getUsersList($group,$this->mode);
	if (is_array($ris) && count($ris)>0){
		foreach($ris as $val){
			extract($val);
			if($this->mode!=0 || $presente==1)
				$data[]=Array("username"=>$username,"groupname"=>$groupname,"presente"=>$presente);
		}
	}
	else{
		$data=Array();
		$msg="Nessun Utente definito";
	}
	$btn[] = '<button name="azione" class="hexfield" type="submit" value="annulla">'.GCAuthor::t('button_cancel').'</button>';
	$btn[] = '<button name="azione" class="hexfield" type="submit" value="salva">'.GCAuthor::t('button_save').'</button>';
	
	if($usr->editGroup==1) $button="modifica";
?>
