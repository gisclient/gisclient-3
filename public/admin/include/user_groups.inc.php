<?php

require_once __DIR__ . '/../../../bootstrap.php';

$db = \GCApp::getDB();
        
    $sql = 'SELECT groupname, description FROM '.DB_SCHEMA.'.groups';
    $groups = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    if(!isset($data) || !is_array($data)) $data = array();

	$username = isset($this->parametri["users"])?$this->parametri["users"]:null;
    if(!empty($username)) {
        $sql = '
            SELECT groupname FROM '.DB_SCHEMA.'.user_group
            WHERE username=:user
        ';
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
            'user'=>$username
        ));
        
        $userGroups = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
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
