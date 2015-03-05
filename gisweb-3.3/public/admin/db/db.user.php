<?php

//var_export($_REQUEST); die();

//accrocchio time
if(!empty($_POST['dati']) && !empty($_POST['dati']['pwd'])) {
    $_POST['dati']['enc_pwd'] = md5($_POST['dati']['pwd']);
    $_POST['dati']['pwd'] = null;
}

$save=new saveData($_POST);
$p=$save->performAction($p);

