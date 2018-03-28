<?php
$save=new saveData($_POST);
$p=$save->performAction($p);
if(!$save->hasErrors && $save->action=="salva"){
	GCAuthor::refreshMapfile($p->parametri['project'],$p->parametri['mapset']);
}
?>