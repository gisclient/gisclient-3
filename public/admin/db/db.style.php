<?php
$save=new saveData($_POST);
$p=$save->performAction($p);

if(!$save->hasErrors && $save->action=="salva"){
	$class_id=$save->parent_flds["class"];
	$sql="SELECT legendtype_id as type FROM ".DB_SCHEMA.".class WHERE class_id=?";
	$db = GCApp::getDB();
	$stmt = $db->prepare($sql);
	$stmt->execute(array($class_id));
	$type = $stmt->fetchColumn(0);
	if($type == 1){
		require_once ADMIN_PATH."lib/gcSymbol.class.php";
		$smb=new Symbol("class");
		$smb->table='class';
		$smb->filter="class.class_id=$class_id";
		$smb->createIcon();	
	};
	
}
