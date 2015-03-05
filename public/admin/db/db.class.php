<?php
$config_file=$_POST["config_file"];
$parent=$_POST["parametri"][count($_POST["parametri"])-2];
$pkeys=$_POST["parametri"][count($_POST["parametri"])-1];
$save=new saveData($_POST);
$p=$save->performAction($p);
/*
if($save->status==1 && $_POST["dati"]["legendtype_id"]==2 && $_FILES["legend_icon"]){
	$img=getimagesize($_FILES["legend_icon"]["tmp_name"]);
	if(!$img){
		$p->errors["legend_icon"]="<p>Il File caricato non è di tipo immagine</p>";
	}
	else{
		$handle=fopen($_FILES["legend_icon"]["tmp_name"],'r');
		$img_data=fread($handle,filesize($_FILES["legend_icon"]["tmp_name"]));
		if(!$img_data){
			$p->errors["legend_icon"]= "<p>Errore nel Caricamento dell'Immagine!</p>";
		}
		else{
			$sql="UPDATE ".DB_SCHEMA.".class SET class_image='".pg_escape_bytea($img_data) ."' WHERE class_id=".$p->parametri[$p->livello];
			if(!$save->db->sql_query($sql)){
				$p->errors["legend_icon"]= "<p>ERRORE NELL'AGGIORNAMENTO DELL'IMMAGINE!</p>";
				print_debug($sql,null,"save.class");
			}
		}
	}
	if($p->errors["legend_icon"]) echo $p->errors["legend_icon"];
}
else*/
if(!$save->hasErrors && $save->action=="salva"){
	if($_POST["dati"]["legendtype_id"]!=0){
		require_once ADMIN_PATH."lib/gcSymbol.class.php";
		$smb=new Symbol("class");
		$smb->table='class';
		$smb->filter="class.class_id=".$p->parametri[$p->livello];
		$smb->createIcon();
	}

}


$p->get_conf();
?>