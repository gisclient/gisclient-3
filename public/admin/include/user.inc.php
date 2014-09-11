<?php
require_once "../../config/config.php";

	$username = (isset($this->parametri["users"]))?$this->parametri["users"]:null;
    if(!empty($username)) {
        if(!isset($data) || !is_array($data)) $data = array();
        array_push($data, GCUser::getUserData($username));
    }

    if(empty($data)) $msg = "Nessun Utente definito";
	$btn[] = '<button name="azione" class="hexfield" type="submit" value="annulla">'.GCAuthor::t('button_cancel').'</button>';
	$btn[] = '<button name="azione" class="hexfield" type="submit" value="salva">'.GCAuthor::t('button_save').'</button>';
	
	$button=($this->currentMode=='view')?("modifica"):("nuovo");
    