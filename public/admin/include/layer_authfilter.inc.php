<?php
require_once "../../config/config.php";
$db = GCApp::getDB();

$joinType = ($this->mode==0) ? " INNER JOIN " : " LEFT JOIN ";
$sql = "select af.filter_id, filter_name, filter_description, laf.required, ".
	" case when laf.layer_id is not null then 1 else 0 end as presente ".
	" from ".DB_SCHEMA.".authfilter af $joinType ".DB_SCHEMA.".layer_authfilter laf ".
	" on af.filter_id=laf.filter_id and laf.layer_id = ?".
	" order by filter_name ";
try {
	$stmt = $db->prepare($sql);
	$stmt->execute(array($this->parametri['layer']));
	$filters = $stmt->fetchAll();
} catch(Exception $e) {
	$filters = array();
}

$data = array();
foreach($filters as $filter) {
	array_push($data, $filter);
}
if(empty($data)) $msg = "Nessun filtro definito";

$btn[] = '<button name="azione" class="hexfield" type="submit" value="annulla">'.GCAuthor::t('button_cancel').'</button>';
$btn[] = '<button name="azione" class="hexfield" type="submit" value="salva">'.GCAuthor::t('button_save').'</button>';

$button="modifica";