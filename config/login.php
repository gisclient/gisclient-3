<?php
require_once(ROOT_PATH.'/lib/gcuser.class.php');

class GCUser extends AbstractUser {
}


/*

//Accesso validato al GisClient
if(defined('USER_SCHEMA') && USER_SCHEMA == "public"){ //Utenti PLONE
	require_once ROOT_PATH."config/users/user.Plone4.class.php";
	$usrObj=Array("user"=>"username","pwd"=>"enc_password","auth"=>"authstring","cookie"=>"gw_pass","passw"=>"password");
} else {
	if(!defined('USER_SCHEMA')) define('USER_SCHEMA',DB_SCHEMA);
	require_once ROOT_PATH."lib/user.GisClient.class.php";
	$usrObj=Array("user"=>"username","pwd"=>"enc_password");
}

$usr=new userApps($usrObj);
//echo $usr->encryptAuthString($usr->createAuthString('GisClient'));


//Accesso all'Author da superutente
if ((SUPER_PWD=='') ||(isset($_POST["username"]) && $_POST["username"]==SUPER_USER && $_POST["enc_password"]==md5(SUPER_PWD))||(isset($_SESSION["USERNAME"]) && $_SESSION["USERNAME"]==SUPER_USER && empty($_REQUEST["logout"])) ){
	$_SESSION["USERNAME"]=SUPER_USER;
	$usr->status=true;
	$usr->setUserOptions();
}
else{
	$usr->context=(dirname($_SERVER["SCRIPT_FILENAME"])."/"==ADMIN_PATH)?('author'):('gisclient');	
	if(!empty($usr->data["logout"])) $usr->logout();
	if(!$usr->checkUser()) {
        if(isset($_POST['username'])) $message='Errore login';
    }
}
*/