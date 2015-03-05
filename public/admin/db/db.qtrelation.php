<?php
$save=new saveData($_POST);
$p=$save->performAction($p);
if(!$save->hasErrors) {
	require_once (ADMIN_PATH."lib/functions.php");
	

	if($save->mode == 'new') {
		$db = GCApp::getDB();
		$sql = "select connection_type, catalog_path from ".DB_SCHEMA.".catalog where catalog_id=?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($save->data['catalog_id']));
		$catalog = $stmt->fetch(PDO::FETCH_ASSOC);
		if($catalog['connection_type'] == 6) {
			list($connStr,$schema)=connAdminInfofromPath($catalog["catalog_path"]);
			
			$table_name = $save->data['table_name'];
			
			$alreadyInserted = array();
			$sql = "select qtfield_name from ".DB_SCHEMA.".qtfield where layer_id=?";
			$stmt = $db->prepare($sql);
			$stmt->execute(array($save->parent_flds['layer']));
			while($array = $stmt->fetch(PDO::FETCH_ASSOC)) {
				array_push($alreadyInserted, $array['qtfield_name']);
			}
			
			$db2 = pg_connect($connStr);
			$sql = "SELECT column_name, data_type, udt_name FROM information_schema.columns WHERE table_schema='$schema' AND table_name='$table_name'";
			$query = pg_query($db2,$sql);
			while($array = pg_fetch_assoc($query)) {
				if(in_array($array['column_name'], $alreadyInserted)) continue;
				$sql="select ".DB_SCHEMA.".new_pkey('".DB_SCHEMA."','qtfield','qtfield_id');";
				$newid = $db->query($sql)->fetchColumn(0);
				$dataType = GCAuthor::GCTypeFromDbType($array['udt_name']);
				if(!$dataType) continue;
				$params = array(
					'qtfield_id'=>$newid,
					'qtfield_name'=>"'".$array['column_name']."'",
					'field_header'=>"'".$array['column_name']."'",
					'searchtype_id'=>1, //FD: soluzione migliore?
					'resultype_id'=>1,
					'datatype_id'=>$dataType,
					'layer_id'=>$save->parent_flds['layer'],
					'qtrelation_id'=>$save->data['qtrelation_id']
				);
				$sql = "insert into ".DB_SCHEMA.".qtfield (".
					implode(',',array_keys($params)).") values (".
					implode(',',$params).")";
				$db->exec($sql);
			}
		}
	}
}