<?php
require_once "../../config/config.php";
	$group=(isset($this->parametri["groups"]))?$this->parametri["groups"]:array();

	$usr=new userApps(null);
	$ris=$usr->getGroup($group);
	if (is_array($ris) && count($ris)>0){
		foreach($ris as $val){
			extract($val);
			$data[]=Array("groupname"=>$groupname,"description"=>isset($description)?$description:'');
		}

	}
	else{
		$data=Array();
		$msg="Nessun Gruppo definito";
	}

	$btn[] = '<button name="azione" class="hexfield" type="submit" value="annulla">'.GCAuthor::t('button_cancel').'</button>';
	$btn[] = '<button name="azione" class="hexfield" type="submit" value="salva">'.GCAuthor::t('button_save').'</button>';
	if($usr->editUser==1) $button=($this->currentMode=='view')?("modifica"):("nuovo");
    if($usr->editUser==-1) $button=($this->currentMode=='view')?(""):("");
?>
