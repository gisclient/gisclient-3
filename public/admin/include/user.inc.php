<?php

require_once __DIR__ . '/../../../bootstrap.php';

if (isset($this->parametri["users"])) {
    $username = $this->parametri["users"];
} else {
    $username = null;
}

if(!empty($username)) {
    if(!isset($data) || !is_array($data)) {
        $data = array();
    }
    $user = GCApp::getUserProvider()->loadUserByUsername($username);
    array_push($data, array(
        'username' => $user->getUsername(),
        'nome' => $user->getNome(),
        'cognome' => $user->getCognome(),
    ));
}

if(empty($data)) {
    $msg = "Nessun Utente definito";
}

$btn[] = '<button name="azione" class="hexfield" type="submit" value="annulla">'.GCAuthor::t('button_cancel').'</button>';
$btn[] = '<button name="azione" class="hexfield" type="submit" value="salva">'.GCAuthor::t('button_save').'</button>';

$button=($this->currentMode=='view')?("modifica"):("nuovo");
