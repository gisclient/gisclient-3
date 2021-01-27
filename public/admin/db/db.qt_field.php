<?php
$save=new saveData($_POST);
$p=$save->performAction($p);
$_db = GCApp::getDB();
if($save->action == "salva" && !$save->hasErrors){
	require_once (ADMIN_PATH."lib/functions.php");
	$sql = "select catalog_path from ".DB_SCHEMA.".layer l join ".DB_SCHEMA.".catalog c using(catalog_id) join gisclient_3.qt using (layer_id) where qt_id=:qtId";
	try {
		$stmt = $_db->prepare($sql);
		$stmt->execute(array('qtId' => $_POST["qt"]));
		$connStr = $stmt->fetchAll(PDO::FETCH_ASSOC)[0]["catalog_path"];
		$datalayerSchema = GCApp::getDataDBSchema($connStr);
		$tableName = 'gw_qt_' . $_POST["qt"];
		if (preg_match('/user=([^ ]*)/', $connStr, $charMatches))
				$connStr = str_replace('user='.$charMatches[1], 'user='.DB_USER, $connStr);
		if (preg_match('/password=([^ ]*)/', $connStr, $charMatches))
				$connStr = str_replace('password='.$charMatches[1], 'password='.DB_PWD, $connStr);
		$dataDB = new GCDataDB($connStr);
		$dataDB->db->query('DROP MATERIALIZED VIEW IF EXISTS ' . $datalayerSchema . '.' . $tableName);
	}
	catch (Exception $e) {
		GCError::registerException($e);
		print_debug($sql,null,"report");
	}
}
?>
