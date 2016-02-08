<?php
require_once '../../config/config.php';
require_once ROOT_PATH . 'lib/GCService.php';

$gcService = GCService::instance();
$gcService->startSession();

if(empty($_REQUEST['project']) || empty($_REQUEST['map']) || empty($_REQUEST['feature_type']) || empty($_REQUEST['primary_key'])) {
	die(json_encode(array('result' => 'error', 'error' => 'Missing mandatory fields')));
}

if(empty($_REQUEST['action']) || !in_array($_REQUEST['action'], array('edit','delete','new'))) {
	die(json_encode(array('result' => 'error', 'error' => 'Missing or invalid action')));
}

if(empty($_REQUEST['primary_key']) || ($_REQUEST['action'] == 'edit' && empty($_REQUEST['primary_key_value']))) {
	die(json_encode(array('result' => 'error', 'error' => 'Missing primary key data')));
}

try {
	$edit = new GCEditFeature($_REQUEST['project'], $_REQUEST['map'], $_REQUEST['feature_type'], $_REQUEST['primary_key']);
	switch($_REQUEST['action']) {
		case 'delete':
			$edit->delete($_REQUEST['primary_key_value']);
		break;
		case 'edit':
			if(!empty($_REQUEST['data'])) {
				$edit->update($_REQUEST['primary_key_value'], $_REQUEST['data']);
			}
			if(!empty($_REQUEST['geometry'])) {
				$edit->updateGeometry($_REQUEST['primary_key_value'], $_REQUEST['geometry']);
			}
		break;
		case 'new':
			$id = $edit->insert($_REQUEST['data']);
			$edit->updateGeometry($id, $_REQUEST['geometry']);
		break;
	}
} catch(Exception $e) {
	die(json_encode(array('result'=>'error', 'error'=>$e->getMessage())));
}
die(json_encode(array('result' => 'ok')));

class GCEditFeature {
	private $dataDB;
	private $table;
	private $primaryKey;
	
	function __construct($project, $map, $featureType, $primaryKey) {
	
		list($layergrupName, $layerName) = $this->_splitFeatureType($featureType);
		
		$db = GCApp::getDB();
		$sql = "select data, data_unique, data_geom, catalog_path from ".DB_SCHEMA.".layer ".
			" inner join ".DB_SCHEMA.".layergroup using(layergroup_id) ".
			" inner join ".DB_SCHEMA.".theme using(theme_id) ".
			" inner join ".DB_SCHEMA.".catalog using(catalog_id) ".
			" where layer_name = :layer_name and theme.project_name = :project_name ";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':layer_name'=>$layerName, ':project_name'=>$project));
		$layerData = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if(empty($layerData)) {
			throw new Exception('Cannot find layer');
		}
		if($layerData['data_unique'] != $primaryKey) {
			throw new Exception('Invalid primary key');
		}
		
		if(!$this->_checkPermission($project, $map, $featureType)) {
			throw new Exception('Permission denied');
		}
		
		$this->dataDB = GCApp::getDataDB($layerData['catalog_path']);
		$this->schema = GCApp::getDataDBSchema($layerData['catalog_path']);
		$this->table = $layerData['data'];
		$this->primaryKey = $layerData['data_unique'];
		$this->geomField = $layerData['data_geom'];
	}
	
	public function delete($id) {
		$sql = "delete from ".$this->schema.".".$this->table.
			" where ".$this->primaryKey." = :id ";
		$stmt = $this->dataDB->prepare($sql);
		$stmt->execute(array($id));
	}
	
	public function update($id, $data) {
		if(empty($data)) return;
		
		$updates = array();
		$params = array();
		foreach($data as $key => $val) {
			array_push($updates, $key.'=:'.$key);
			$params[':'.$key] = $val;
		}
		
		$sql = "update ".$this->schema.".".$this->table.
			" set ".implode(',', $updates).
			" where ".$this->primaryKey." = :GisClient_pkey_value ";
		$params[':GisClient_pkey_value'] = $id;
		
		$stmt = $this->dataDB->prepare($sql);
		foreach($params as $key => $val) {
			if(empty($val)) {
				$stmt->bindValue($key, $val, PDO::PARAM_NULL);
			} else {
				$stmt->bindValue($key, $val);
			}
		}
		$stmt->execute();
	}
	
	public function insert($data) {
		if(empty($data)) throw new Exception('Empty data');
		
		$columns = array();
		$params = array();
		$n = 0;
		foreach($data as $key => $val) {
			$columns[':gcpdo_col_'.$n] = $key;
			$params[':'.$key] = $val;
			$n++;
		}
		
		$sql = "insert into ".$this->schema.".".$this->table.
				" (" . implode(',', array_keys($columns)). ") ".
				" values (". implode(',', array_keys($params)). ") ";
				//echo $sql;
		$stmt = $this->dataDB->prepare($sql);
		
		foreach($params as $key => $val) {
			if(empty($val)) {
				$stmt->bindValue($key, $val, PDO::PARAM_NULL);
			} else {
				$stmt->bindValue($key, $val);
			}
		}
		$stmt->execute();
		return $this->dataDB->lastInsertId();
	}
	
	public function updateGeometry($id, $geomData) {
		if(empty($geomData)) throw new Exception('Empty geom data');
		if(empty($geomData['srid'])) throw new Exception('Missing srid');
		if(strpos($geomData['srid'], ':') !== false) {
			list($authSrid, $srid) = explode(':', $geomData['srid']);
		} else {
			$srid = $geomData['srid'];
		}
		$sql = "update ".$this->schema.".".$this->table.
			" set ".$this->geomField." = st_geomfromtext(:wkt, :srid) ".
			" where ".$this->primaryKey." = :GisClient_pkey_value ";
		$stmt = $this->dataDB->prepare($sql);
		$params = array(
			':wkt' => $geomData['wkt'],
			':srid' => $srid,
			':GisClient_pkey_value' => $id
		);
		$stmt->execute($params);
	}
	
	private function _splitFeatureType($featureType) {
		return explode('.', $featureType);
	}
	
	private function _checkPermission($project, $map, $featureType) {
		return true;
		if(!isset($_SESSION)) {
            if(defined('GC_SESSION_NAME')) session_name(GC_SESSION_NAME);
            session_start();
        }
		if(empty($_SESSION['GISCLIENT_USER_LAYER'])) return false;
		$layers = $_SESSION['GISCLIENT_USER_LAYER'];
		if(!isset($layers[$project]) || !isset($layers[$project][$map]) || !isset($layers[$project][$map][$featureType])) return false;
		if(empty($layers[$project][$map][$featureType]['WFST'])) return false;
		return true;
	}
}