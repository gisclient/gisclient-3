<?php
$save=new saveData($_POST);
$p=$save->performAction($p);
if(!$save->hasErrors && $save->action=="salva" && !defined('LEGEND_CACHE_PATH')){
	GCAuthor::refreshMapfile($p->parametri['project'],$p->parametri['mapset']);
}
?>
