<?php
include_once "../../../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';
include_once ADMIN_PATH.'lib/functions.php';

$ajax = new GCAjax();

$db = GCApp::getDB();

if(empty($_REQUEST['catalog_id']) || !is_numeric($_REQUEST['catalog_id']) || $_REQUEST['catalog_id'] < 1) {
	$ajax->error('catalog_id');
}
$catalogId = $_REQUEST['catalog_id'];

$layerTypeId = null;
if(!empty($_REQUEST['layertype_id']) && is_numeric($_REQUEST['layertype_id']) && $_REQUEST['layertype_id'] > 0) $layerTypeId = $_REQUEST['layertype_id'];

$sql = "select catalog_path,connection_type from ".DB_SCHEMA.".catalog where catalog_id=?";
$stmt = $db->prepare($sql);
$stmt->execute(array($catalogId));
$catalogData = $stmt->fetch(PDO::FETCH_ASSOC);

$postParams = array(); //parametri da passare in POST per lo step successivo

list($connStr,$schema)=connAdminInfofromPath($catalogData["catalog_path"]);

switch($catalogData["connection_type"]){
	case 1:		//Local Folder
		$result = array('steps'=>1, 'data'=>array(), 'data_objects'=>array(), 'step'=>null, 'fields'=>array('file'=>'File'));
		$n = 0;
		
		require_once ADMIN_PATH."lib/filesystem.php";
		$baseDir = addFinalSlash(trim($catalogData["catalog_path"]));
		if(substr($baseDir,0,1) != '/'){// SOTTO CARTELLA
			$sql="select base_path from ".DB_SCHEMA.".project where project_name=?";
			$stmt = $db->prepare($sql);
			$stmt->execute(array($_REQUEST['project']));
            $projectPath = $stmt->fetchColumn(0);
            if(!empty($projectPath)) {
                $projectPath = addFinalSlash($projectPath);
            } else {
                $projectPath = addFinalSlash(ROOT_PATH);
            }
			$baseDir = $projectPath.$baseDir;
		}
		$navDir = '';
		if(!empty($_REQUEST['directory'])) { // siamo in una sottocartella, includi anche il back
			$navDir = $_REQUEST['directory'];
			$result['data'][$n] = array('file'=>'..');
			$result['data_objects'][$n] = array('directory'=>$navDir.'../');
			$n++;
		}
		$sourceDir = $baseDir . $navDir;
		
		$directories = elenco_dir($sourceDir);
		sort($directories);
		foreach($directories as $directory) {
			$result['data'][$n] = array('file'=>$directory);
			$result['data_objects'][$n] = array('directory'=>$navDir.addFinalSlash($directory));
			$n++;
		}
		
		$allowedExtensions = explode(",",strtolower(CATALOG_EXT));
		foreach($allowedExtensions as $extension){
			$files = elenco_file($sourceDir, $extension);
			if(!$files) continue;
			sort($files);
			foreach($files as $file) {
				$result['data'][$n] = array('file'=>$file);
				$result['data_objects'][$n] = array('data'=>$file, 'is_final_step'=>1);
				$n++;
			}
		}
	break;
	case 6: // PostGIS
		$result = array(
			'steps'=>2,
			'data'=>array(),
			'data_objects'=>array()
		);
		$n = 0;
	
		$dataDb = GCApp::getDataDB($catalogData['catalog_path']);
		
		if (empty($_REQUEST["step"])) { //selezione tabella
			$result['fields'] = array('table'=>GCAuthor::t('table'), 'column'=>GCAuthor::t('column'));
			$result['step'] = 1;
			
			$sql="SELECT f_table_name as table, f_geometry_column as column, srid, lower(type) as type FROM geometry_columns WHERE f_table_schema=? order by f_table_name,f_geometry_column";
			try {
				$stmt = $dataDb->prepare($sql);
				$stmt->execute(array($schema));
			} catch(Exception $e) {
				$ajax->error();
			}
			
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$result['data'][$n] = $row;
				$result['data_objects'][$n] = array(
					'data'=>$row['table'],
					'data_geom'=>$row['column'],
					'data_type'=>$row['type'],
					'data_srid'=>$row['srid']
				);
				$n++;
			}
			
		} else { // selezione pkey
			$result['fields'] = array('pkey'=>GCAuthor::t('pkey'));
			$result['step'] = 2;
			
			$sql = 'select table_name from information_schema.tables where table_schema=:schema and table_name=:table';
			$stmt = $dataDb->prepare($sql);
			$stmt->execute(array(':schema'=>$schema, ':table'=>$_REQUEST['data']));
			$dbTableName = $stmt->fetchColumn(0);
			if($dbTableName != $_REQUEST['data']) $ajax->error('Cannot find table '.$schema.'.'.$_REQUEST['data']);
			
			$sql = 'select column_name from information_schema.columns where table_schema=:schema and table_name=:table and column_name=:column';
			$stmt = $dataDb->prepare($sql);
			$stmt->execute(array(':schema'=>$schema, ':table'=>$_REQUEST['data'], ':column'=>$_REQUEST['data_geom']));
			$dbColumnName = $stmt->fetchColumn(0);
			if($dbColumnName != $_REQUEST['data_geom']) $ajax->error('Cannot find column '.$_REQUEST['data_geom'].' for table '.$schema.'.'.$_REQUEST['data']);
			
			$sql = 'select st_extent('.$dbColumnName.') from '.$schema.'.'.$dbTableName;
			$box = $dataDb->query($sql)->fetchColumn(0);
			$extent = array();
			if(!empty($box)) {
				$extent = GCUtils::parseBox($box);
			}
			
			$sql = "SELECT column_name FROM information_schema.columns WHERE table_schema=:schema AND table_name=:table ORDER BY column_name;";
			$stmt = $dataDb->prepare($sql);
			$stmt->execute(array(':schema'=>$schema, ':table'=>$_REQUEST['data']));
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$result['data'][$n] = array('pkey'=>$row['column_name']);
				$result['data_objects'][$n] = array(
					'data_unique' => $row['column_name'],
				);
                                // add data_extent only when requested
                                if (isset($_REQUEST['data_extent'])) {
                                    $result['data_objects'][$n]['data_extent'] = implode(' ', $extent);
                                }
				$n++;
			}
		}
	break;
	case 7: //WMS
		$result = array(
			'steps'=>2,
			'data'=>array(),
			'data_objects'=>array()
		);
		$n = 0;
		
		if(empty($_REQUEST['step'])) {
			$result['step'] = 1;
			$result['fields'] = array('group'=>GCAuthor::t('group'), 'name'=>GCAuthor::t('name'), 'title'=>GCAuthor::t('title'));
			
			$defaultParameters = array("SERVICE"=>"WMS","REQUEST"=>"GetCapabilities",'VERSION'=>'1.1.1');
			$urlComponents = parse_url($catalogData["catalog_path"]);
			if(!empty($urlComponents['query'])) {
				parse_str($urlComponents['query'], $urlParameters);
			} else {
				$urlParameters = array();
			}
			$parameters = array();
			foreach($urlParameters as $key => $val) $parameters[strtoupper($key)] = $val;
			foreach($defaultParameters as $key => $val) $parameters[$key] = $val;
			$urlComponents['query'] = http_build_query($parameters);
			$url = http_build_url($catalogData['catalog_path'], $urlComponents);

			require_once ADMIN_PATH.'lib/ParseXml.class.php';
			$xml = new ParseXml();
			$xml->LoadRemote($url, 3);
			if(empty($xml->xmlStr)) break;
			
			$data = $xml->ToArray();
			//file_put_contents('wms_dump.php', var_export($data, true));
			if(empty($data)) break;
			
			$theme = $data["Capability"]["Layer"];
			$lThemeSRS = (is_array($theme["SRS"]))?($theme["SRS"]):(Array($theme["SRS"]));
			if(!empty($theme["Layer"]["Name"])) {
				$theme["Layer"] = array($theme['Layer']);
			}
			
			$mdEntries = array(
				'server_version' => $data["@attributes"]["version"],
				'format' => current($data["Capability"]["Request"]["GetMap"]["Format"]),
				'formatlist' => implode(' ',$data["Capability"]["Request"]["GetMap"]["Format"]),
				'epsglist' => implode(' ',$lThemeSRS)
			);
			$mdBuilder = new WMSMetadataBuilder($mdEntries);
			
			foreach($theme['Layer'] as $layergroup) {
				$layer = array(
					'group'=>$layergroup['Name']
				);
				$availableSrids = $lThemeSRS;
				if(!empty($layergroup['SRS'])) {
					if(is_array($layergroup['SRS'])) $availableSrids = $layergroup['SRS'];
					else $availableSrids = array($layergroup['SRS']);
				}
				
				if(!empty($layergroup['Style'])) {
					if(empty($layergroup['Style']['Name']) && empty($layergroup['Style']['Title'])) {
						$layergroup['Style'] = array($layergroup['Style']);
					}
					foreach($layergroup['Style'] as $style) {
						$layer['name'] = $layergroup['Style']['Name'];
						$layer['title'] = $layergroup['Style']['Title'];
						$layer['style'] = 1;
						$result['data'][$n] = $layer;
						$mdBuilder->setName($layer['group']);
						$result['data_objects'][$n] = array(
							'metadata'=>$mdBuilder->getMetadata(),
							'available_srids'=>$availableSrids
						);
						$n++;
					}
				}
			}
		} else {
			$result['step'] = 2;
			$result['fields'] = array('srid'=>'SRID');
			
			if(empty($_REQUEST['available_srids'])) $ajax->error();
			
			foreach($_REQUEST['available_srids'] as $srid) {
				$result['data'][$n] = array('srid'=>$srid);
				$result['data_objects'][$n] = array('data_srid'=>substr($srid, strpos($srid, ':')+1));
				$n++;
			}
		}
	break;
	case 9: //WFS
		$result = array(
			'steps'=>1,
			'step'=>1,
			'data'=>array(),
			'data_objects'=>array()
		);
		$n = 0;
		
		
		$result['fields'] = array('name'=>GCAuthor::t('name'), 'title'=>GCAuthor::t('title'), 'srid'=>'SRID');
		
		$defaultParameters = array("SERVICE"=>"WFS","REQUEST"=>"GetCapabilities",'VERSION'=>'1.0.0');
		$urlComponents = parse_url($catalogData["catalog_path"]);
		if(!empty($urlComponents['query'])) {
			parse_str($urlComponents['query'], $urlParameters);
		} else {
			$urlParameters = array();
		}
		$parameters = array();
		foreach($urlParameters as $key => $val) $parameters[strtoupper($key)] = $val;
		foreach($defaultParameters as $key => $val) $parameters[$key] = $val;
		$urlComponents['query'] = http_build_query($parameters);
		$url = http_build_url($catalogData['catalog_path'], $urlComponents);

		require_once ADMIN_PATH.'lib/ParseXml.class.php';
		$xml = new ParseXml();
		$xml->LoadRemote($url, 3);
		if(empty($xml->xmlStr)) break;
		
		$data = $xml->ToArray();
		//file_put_contents('wfs_dump.php', var_export($data, true));
		if(empty($data)) break;
			
		$theme = $data["FeatureTypeList"]['FeatureType'];
		foreach($theme as $featureType) {
			$result['data'][$n] = array(
				'name'=>$featureType['Name'],
				'title'=>$featureType['Title'],
				'srid'=>$featureType['SRS']
			);
			$result['data_objects'][$n] = array(
				'data_srid' => substr($featureType['SRS'], strpos($featureType['SRS'], ':')+1),
				'metadata' => '"wfs_name" "'.$featureType['Name'].'"'."\n".
					'"wfs_srs" "'.$featureType['SRS'].'"'."\n".
					'"wfs_request_method" "GET"'."\n".
					'"wfs_typename" "'.$featureType['Name'].'"'."\n".
					'"wfs_server_version" "1.0.0"'."\n".
					'"wfs_version" "1.0.0"'."\n"
			);
			$n++;
		}
	break;
}
$ajax->success($result);

class WMSMetadataBuilder {
	private $entries;
	private $wmsName;
	
	function __construct($entries) {
		$this->entries = $entries;
	}
	
	public function setName($name) {
		$this->wmsName = $name;
	}
	
	public function getMetadata() {
		$metadata = '"wms_name" "'.$this->wmsName.'"'."\n".
					'"wms_srs" "'.$this->entries['epsglist'].'"'."\n".
					'"wms_server_version" "'.$this->entries['server_version'].'"'."\n".
					'"wms_format" "'.$this->entries['format'].'"'."\n".
					'"wms_formatlist" "'.$this->entries['formatlist'].'"'."\n";
		return $metadata;
	}
}