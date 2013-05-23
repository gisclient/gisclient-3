<?php
require_once "../../config/config.php";

    $groups = GCUser::getGroups();
    if(!isset($data) || !is_array($data)) $data = array();

	$username = isset($this->parametri["users"])?$this->parametri["users"]:null;
    if(!empty($username)) {
        $userGroups = GCUser::getUserGroups($username);
    }
    if(empty($userGroups)) $userGroups = array();
    
    foreach($groups as $group) {
        $presente = (int)in_array($group['groupname'], $userGroups);
        if(!empty($this->mode) || !empty($presente)) {
            array_push($data, array(
                'username'=>$username,
                'groupname'=>$group['groupname'],
                'presente'=>$presente
            ));
        }
    }
    
    if(empty($data)) $msg = "Nessun Gruppo definito";
	$btn[] = '<button name="azione" class="hexfield" type="submit" value="annulla">'.GCAuthor::t('button_cancel').'</button>';
	$btn[] = '<button name="azione" class="hexfield" type="submit" value="salva">'.GCAuthor::t('button_save').'</button>';
	
	$button="modifica";
?>
