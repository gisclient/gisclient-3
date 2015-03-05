<?php
if (in_array('classify',array_keys($_REQUEST)) && $_REQUEST["classify"]==1 ){
	require_once ADMIN_PATH."lib/gcSymbol.class.php";
	require_once ADMIN_PATH."lib/functions.php";
	$layerId=$_REQUEST["layer"];
	$param=Array(
		"mode"=>"",
		"modo"=>""
	);
	$save=new saveData($param);
	$_db = GCApp::getDB();
	$sql="DELETE FROM ".DB_SCHEMA.".class WHERE layer_id=:layerId";
	try {
	    $stmt = $_db->prepare($sql);
	    $stmt->execute(array('layerId' => $layerId));
	} catch (Exception $e) {
	    GCError::registerException($e);
	}	
	
		
	for($i=0;$i<count($_REQUEST["dati"]["class"]);$i++){
		$cls=$_REQUEST["dati"]["class"][$i];
		$style=$_REQUEST["dati"]["style"][$i];
		$classId = GCApp::getNewPKey(DB_SCHEMA, DB_SCHEMA, 'class','class_id');
		$order = ($i+1)*10;
		$sql="INSERT INTO ".DB_SCHEMA.".class(class_id,layer_id,class_name,class_title,expression,legendtype_id,class_order) 
			VALUES(:layerId, :className, :classTitle, :expr, :legendType, :ordr)";
		$sqlParams = array('classId' => $classId,
		    'layerId' => $layerId,
		    'className' => $cls['class_name'],
		    'classTitle' => $cls['class_title'],
		    'expr' => $cls['expression'],
		    'legendType' => $cls['legend_type'],
		    'ordr' => $order);
		try {
		    $stmt = $_db->prepare($sql);
		    $stmt->execute($sqlParams);
		} catch (Exception $e) {
		    GCError::registerException($e);
		    print_debug($sql,null,'TEST.LAYER');
		}
				
		$styleId = GCApp::getNewPKey(DB_SCHEMA, DB_SCHEMA, 'style','style_id');		
		$tmp=html2rgb("#".$style["color"]);
		$color=implode(" ",$tmp);
		$sql="INSERT INTO ".DB_SCHEMA.".style(style_id,class_id,style_name,color,outlinecolor,width) 
			VALUES(:styleId, :classId, :styleName, :color,'0 0 0',1)";
		$sqlParams = array('styleId' => $styleId, 
		    'classId' => $classId, 
		    'styleName' => $style[style_name], 
		    'color' => $color);
		try {
		    $stmt = $_db->prepare($sql);
		    $stmt->execute($sqlParams);
		} catch (Exception $e) {
		    GCError::registerException($e);
		    print_debug($sql,null,'TEST.LAYER');
		}		
		if(!$db->sql_query($sql)){
			print_debug($sql,null,'TEST.LAYER');
		}
		else{
			$smb=new Symbol("class");
			$smb->table='class';
			$smb->filter="class.class_id=$classId";
			$smb->createIcon();
		}
	}
	$save->hasErrors = true;
	$save->action="classify";
} else {
	$save=new saveData($_POST);
	$p=$save->performAction($p);
	
	$_db = GCApp::getDB();
	if($save->action == "salva" && !$save->hasErrors){
		require_once (ADMIN_PATH."lib/functions.php");

		$sql = "select catalog_path, connection_type from ".DB_SCHEMA.".catalog where catalog_id=:catId";
		try {
		    $stmt = $_db->prepare($sql);
		    $stmt->execute(array('catId' => $_POST["dati"]["catalog_id"]));
		    $catalog = $stmt->fetch(PDO::FETCH_ASSOC);
		} catch (Exception $e) {
		    GCError::registerException($e);
		    print_debug($sql,null,"elenco");
		}

		if($catalog["connection_type"]==6){
			$dataDb = GCApp::getDataDB($catalog["catalog_path"]);
			$schema = GCApp::getDataDBSchema($catalog['catalog_path']);
			//list($connStr,$schema)=connAdminInfofromPath($catalog["catalog_path"]);
			//$newdb=pg_connect($connStr);
			//setDBPermission($newdb,$schema,MAP_USER,'SELECT','GRANT',$_POST["dati"]["data"]);
		}

		if($save->mode == 'new' && $catalog['connection_type'] == 6) {
			$sql = "SELECT column_name, data_type, udt_name FROM information_schema.columns WHERE table_schema=:schema AND table_name=:table";
			try {
			    $stmt = $dataDb->prepare($sql);
			    $stmt->execute(array(':schema' => $schema, ':table' => $_POST["dati"]["data"]));
			    $rows = $stmt->fetchAll();
			} catch (Exception $e) {
			    GCError::registerException($e);
			}
			if (is_array($rows)) {
			    foreach ($rows as $row) {
					$newid = GCApp::getNewPKey(DB_SCHEMA, DB_SCHEMA, 'field', 'field_id');						
					$dataType = GCAuthor::GCTypeFromDbType($row['udt_name']);
					if(!$dataType) continue;
					$sqlParams = array(
						':field_id' => $newid,
						':field_name' => $row['column_name'],
						':field_header' => $row['column_name'],
						':searchtype_id' => 1, //FD: soluzione migliore?
						':resultype_id' => 4, // nascosto di default
						':datatype_id' => $dataType,
						':layer_id' => $save->data['layer_id']
					);
					$sql = "insert into ".DB_SCHEMA.".field (field_id, field_name, field_header, searchtype_id, resultype_id, datatype_id, layer_id) 
						values (:field_id, :field_name, :field_header, :searchtype_id, :resultype_id, :datatype_id, :layer_id)";
					try {
						$stmt = $_db->prepare($sql);
						$stmt->execute($sqlParams);
					} catch (Exception $e) {
						GCError::registerException($e);
					}
			    }
			}
		}
	}
}