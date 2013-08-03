<?php
if (in_array(strtolower($_POST["azione"]),$arr_action)){
	// Parte di Salvataggio e UPLOAD del File
	array_push($param,Array($p->livello=>$p->parametri[$p->livello]));
	$p->mode=1;
		
	
	
	if(count($Errors)>0){
		$p->livello=$p->last_livello;
		$p->mode=$p->arr_mode[$_POST["modo"]];
	}
	if(in_array(strtolower($_POST["azione"]),Array("elimina","cancella"))){
		$p->livello="catalog";
		array_pop($p->parametri);
		array_pop($param);
	}
}
else{
	$p->livello="catalog";
	array_pop($p->parametri);
	array_pop($param);
}
	
$p->get_conf();
?>