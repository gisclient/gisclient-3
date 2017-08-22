<?php

require_once __DIR__ . '/../../../bootstrap.php';

$db = \GCApp::getDB();

	$groupname = (isset($this->parametri["groups"]))?$this->parametri["groups"]:array();
    
    if(!empty($groupname)) {
        if(!isset($data) || !is_array($data)) $data = array();
        
        $sql = '
            SELECT groupname, description FROM '.DB_SCHEMA.'.groups
            WHERE groupname=:group
        ';
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
            'group' => $groupname
        ));
        
        array_push($data, $stmt->fetch(\PDO::FETCH_ASSOC));
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
