<?php
require_once "../../config/config.php";

	$groupname = (isset($this->parametri["groups"]))?$this->parametri["groups"]:array();
    
    if(!empty($groupname)) {
        if(!isset($data) || !is_array($data)) $data = array();
        array_push($data, GCUser::getGroupData($groupname));
    }
    if(empty($data)) $msg = "Nessun Utente definito";
    
    
    
    
/* 	$usr=new userApps(null);
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
	} */

	$btn[] = '<button name="azione" class="hexfield" type="submit" value="annulla">'.GCAuthor::t('button_cancel').'</button>';
	$btn[] = '<button name="azione" class="hexfield" type="submit" value="salva">'.GCAuthor::t('button_save').'</button>';
	$button=($this->currentMode=='view')?("modifica"):("nuovo");
?>
