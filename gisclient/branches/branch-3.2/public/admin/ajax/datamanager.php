<?php
include_once "../../../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';
include_once ADMIN_PATH.'lib/functions.php';
include_once ROOT_PATH.'lib/export.php';

define('IMPORT_PATH', ROOT_PATH.'import/');
$extensions = array(
	'shp'=>array('shp', 'shx', 'dbf'),
	'raster'=>array('tif', 'tiff', 'ecw', 'jpg', 'jpeg', 'png')
);
$exportExtensions = array('shp', 'shx', 'dbf', 'prj', 'cpg');
$columnTypes = array('double precision', 'text', 'date');

// real path per browsing

$ajax = new GCAjax();
$db = GCApp::getDB();

if(empty($_REQUEST['action'])) $ajax->error();

switch($_REQUEST['action']) {
	case 'get-available-imports':
		$imports = array(
			0=>'shp',
			1=>'raster',
			2=>'postgis',
			3=>'xls'
		);
		if(empty($_REQUEST['catalog_id'])) $ajax->error();
		$dir = filesPathFromCatalog($_REQUEST['catalog_id']);
		if(!$dir) unset($imports[1]);
		if(!defined('USE_PHP_EXCEL') || USE_PHP_EXCEL == false) unset($imports[3]);
		$ajax->success(array('imports'=>$imports));
	break;
	case 'upload-xls':
	case 'upload-shp':
		$tempFile = $_FILES['Filedata']['tmp_name'];
		$targetFile = IMPORT_PATH . $_FILES['Filedata']['name'];
		move_uploaded_file($tempFile, $targetFile);
		echo str_replace($_SERVER['DOCUMENT_ROOT'], '', $targetFile);
	break;
	case 'upload-raster':
		if(empty($_REQUEST['directory'])) $ajax->error();
		$targetDir = addFinalSlash($_REQUEST['directory']);
		if(empty($_REQUEST['catalog_id'])) $ajax->error();
		
		$basePath = filesPathFromCatalog($_REQUEST['catalog_id']);
		
		if(!is_dir($basePath.$targetDir)) {
			mkdir($basePath.$targetDir);
		}
		
		$tempFile = $_FILES['Filedata']['tmp_name'];
		$targetFile = $basePath.$targetDir.$_FILES['Filedata']['name'];
		move_uploaded_file($tempFile, $targetFile);
		echo str_replace($_SERVER['DOCUMENT_ROOT'], '', $targetFile);
	break;
	case 'get-uploaded-files':
		include_once(ADMIN_PATH.'lib/filesystem.php');
		
		if($_REQUEST['file_type'] == 'shp') {
			$files = elenco_file(IMPORT_PATH, array('shp'));
		} else if($_REQUEST['file_type'] == 'raster') {
			if(empty($_REQUEST['catalog_id'])) $ajax->error();
			$dir = filesPathFromCatalog($_REQUEST['catalog_id']);
			if(!is_dir($dir)) $ajax->error();
			$files = elenco_dir($dir);
		} else if($_REQUEST['file_type'] == 'xls') {
			$files = elenco_file(IMPORT_PATH, array('xls','xlsx'));
		} else $ajax->error();
		
		$data = array();
		foreach($files as $file) {
			array_push($data, array('file_name'=>$file));
		}
		$ajax->success(array('data'=>$data));
		
	break;
	case 'get-postgis-tables':
		if(empty($_REQUEST['catalog_id'])) $ajax->error();
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		
		$dataDb = GCApp::getDataDB($catalogPath);
		$schema = GCApp::getDataDBSchema($catalogPath);
		
		$sql = 'select table_name as name, coord_dimension as dim, srid, type '.
			' from information_schema.tables '.
			' left outer join geometry_columns on tables.table_name=geometry_columns.f_table_name and f_table_schema = :schema '.
			' where table_schema = :schema order by table_name ';
		$stmt = $dataDb->prepare($sql);
		$stmt->execute(array(':schema'=>$schema));
		$data = array();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) array_push($data, $row);
		$ajax->success(array('data'=>$data));
	break;
	case 'delete-table':
		if(empty($_REQUEST['catalog_id'])) $ajax->error();
		if(empty($_REQUEST['table_name'])) $ajax->error();
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		
		$dataDb = GCApp::getDataDB($catalogPath);
		$schema = GCApp::getDataDBSchema($catalogPath);
		
		$sql = "select dropgeometrytable(:schema, :table)";
		try {
			$stmt = $dataDb->prepare($sql);
			$stmt->execute(array(':schema'=>$schema, ':table'=>$_REQUEST['table_name']));
		} catch(Exception $e) {
			$ajax->error($e->getMessage());
		}
		$ajax->success();
	break;
	case 'delete-file':
		if(empty($_REQUEST['file_name'])) $ajax->error();
		if(empty($_REQUEST['file_type'])) $ajax->error();
		
		if($_REQUEST['file_type'] == 'shp') {
			if(!file_exists(IMPORT_PATH.$_REQUEST['file_name'])) $ajax->error();
			$fileName = substr($_REQUEST['file_name'], 0, strrpos($_REQUEST['file_name'], '.'));
			foreach($extensions['shp'] as $extension) {
				if(file_exists(IMPORT_PATH.$fileName.'.'.$extension)) @unlink(IMPORT_PATH.$fileName.'.'.$extension);
			}
		} else if($_REQUEST['file_type'] == 'raster') {
			if(empty($_REQUEST['catalog_id'])) $ajax->error();
			$dir = filesPathFromCatalog($_REQUEST['catalog_id']);
			if(!is_dir($dir)) $ajax->error();
			rrmdir($dir.$_REQUEST['file_name']);
		} else if($_REQUEST['file_type'] == 'xls') {
            if(!file_exists(IMPORT_PATH.$_REQUEST['file_name'])) $ajax->error();
        } else $ajax->error();
		
		@unlink(IMPORT_PATH.$_REQUEST['file_name']);
		
		$ajax->success();
		
	break;
	case 'export-xls':
		if(empty($_REQUEST['catalog_id'])) $ajax->error('catalog_id');
		if(empty($_REQUEST['table_name'])) $ajax->error('table_name');
		
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		$dataDb = GCApp::getDataDB($catalogPath);
		$dbParams = GCApp::getDataDBParams($catalogPath);
		
		if(!tableAlreadyExists($dataDb, $dbParams['schema'], $_REQUEST['table_name'])) $ajax->error('table does not exist');
		
        $sql = "SELECT column_name FROM information_schema.columns WHERE " .
                "  table_schema=:schema AND table_name=:table ORDER BY ordinal_position";
		$stmt = $dataDb->prepare($sql);
		$stmt->execute(array('schema'=>$dbParams['schema'], 'table'=>$_REQUEST['table_name']));
		$columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
		
		require_once ROOT_PATH.'lib/external/PHPExcel/IOFactory.php';
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setTitle("Export ".$_REQUEST['table_name']);
		$sheet = $objPHPExcel->setActiveSheetIndex(0);
		foreach($columns as $n => $col) {
			$sheet->setCellValueByColumnAndRow($n, 1, $col);
		}
		$sql = 'select '.implode(',',$columns).' from '.$dbParams['schema'].'.'.$_REQUEST['table_name'];
		$data = $dataDb->query($sql)->fetchAll(PDO::FETCH_ASSOC);
		foreach($data as $nRow => $row) {
			$colCount = 0;
			foreach($row as $nCell => $cell) {
				$sheet->setCellValueByColumnAndRow($colCount, ($nRow+2), $cell);
				$colCount++;
			}
		}
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$fileName = $_REQUEST['table_name'].'_'.date('YmdHis').'_'.rand(0,9999);
		$objWriter->save(ROOT_PATH.'public/admin/export/'.$fileName.'.xlsx');
		$ajax->success(array('filename'=>$fileName.'.xlsx'));
	break;
	case 'export-shp':
		if(empty($_REQUEST['catalog_id'])) $ajax->error('catalog_id');
		if(empty($_REQUEST['table_name'])) $ajax->error('table_name');
		include_once(ADMIN_PATH.'lib/filesystem.php');
		
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		$dataDb = GCApp::getDataDB($catalogPath);
		$dbParams = GCApp::getDataDBParams($catalogPath);
		
		if(!tableAlreadyExists($dataDb, $dbParams['schema'], $_REQUEST['table_name'])) $ajax->error('table does not exist');
		
        $export = new GCExport('shp');
        $tables = array(
            array(
                'db'=>$dbParams['db_name'],
                'schema'=>$dbParams['schema'],
                'table'=>$_REQUEST['table_name']
            )
        );
        $zipFile = $export->export($tables, array('name'=>$_REQUEST['table_name']));
		
		$ajax->success(array('filename'=>$zipFile));
	break;
	case 'import-shp':
		if(empty($_REQUEST['file_name'])) $ajax->error('file_name');
		if(empty($_REQUEST['catalog_id'])) $ajax->error('catalog_id');
		if(empty($_REQUEST['srid'])) $ajax->error('srid');
		if(empty($_REQUEST['mode']) || !in_array($_REQUEST['mode'], array('create', 'append', 'replace'))) $ajax->error('mode');
		$_REQUEST['srid'] = trim($_REQUEST['srid']);
		if(empty($_REQUEST['table_name'])) $ajax->error('table_name');
		$_REQUEST['table_name'] = trim($_REQUEST['table_name']);
		
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		
		$dataDb = GCApp::getDataDB($catalogPath);
		$schema = GCApp::getDataDBSchema($catalogPath);
		
		$tableExists = tableAlreadyExists($dataDb, $schema, $_REQUEST['table_name']);
		if($_REQUEST['mode'] == 'create' && $tableExists) $ajax->error('Table '.$_REQUEST['table_name'].' already exists');
		if($_REQUEST['mode'] != 'create' && !$tableExists) $ajax->error('Table '.$_REQUEST['table_name'].' does not exist');
		
		if($_REQUEST['mode'] == 'create' && $_REQUEST['table_name'] != niceName($_REQUEST['table_name'])) {
			$ajax->error('Invalid table name');
		}
		$tableName = $_REQUEST['table_name'];
		
		if(!file_exists(IMPORT_PATH.$_REQUEST['file_name'])) $ajax->error('File does not exists');
		
		$fileName = substr($_REQUEST['file_name'], 0, strrpos($_REQUEST['file_name'], '.'));
		foreach($extensions['shp'] as $extension) {
			if(!file_exists(IMPORT_PATH.$fileName.'.'.$extension)) $ajax->error('Missing required '.$extension.' file');
		}
		
		$charset = null;
		if(!empty($_REQUEST['charset'])) $charset = $_REQUEST['charset'];
				
		$outputFile = IMPORT_PATH.$fileName.'.sql';
		$errorFile = ROOT_PATH.'config/debug/'.$fileName.'.err';
		
		$options = array(
			'charset'=>$charset,
			'mode'=>$_REQUEST['mode']
		);
		if(!shp2pgsql(IMPORT_PATH.$_REQUEST['file_name'], (int)$_REQUEST['srid'], $tableName, $outputFile, $errorFile, $options)) {
			$errorText = file_get_contents($errorFile);
			$ajax->error('Shape Import Error:<br>'.$errorText);
		}
		
		$dataDb->beginTransaction();
		$sql = "set search_path = $schema, public;\n";
		$sql .= file_get_contents($outputFile);
		try {
			$dataDb->exec($sql);
		} catch(Exception $e) {
			$ajax->error($e->getMessage());
		}
		$sql = "GRANT SELECT ON TABLE $schema.$tableName TO ".MAP_USER.";";
		try {
			$dataDb->exec($sql);
		} catch(Exception $e) {
			$ajax->error($e->getMessage());
		}
		@unlink($outputFile);
		$dataDb->commit();
		
		$ajax->success();
	break;
	case 'import-xls':
		if(empty($_REQUEST['file_name'])) $ajax->error('file_name');
		if(empty($_REQUEST['catalog_id'])) $ajax->error('catalog_id');
		if(empty($_REQUEST['table_name'])) $ajax->error('table_name');
		$_REQUEST['table_name'] = trim($_REQUEST['table_name']);
		
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		
		$dataDb = GCApp::getDataDB($catalogPath);
		$schema = GCApp::getDataDBSchema($catalogPath);
		
		if(tableAlreadyExists($dataDb, $schema, $_REQUEST['table_name'])) $ajax->error('Table '.$_REQUEST['table_name'].' already exists');
		
		if($_REQUEST['table_name'] != niceName($_REQUEST['table_name'])) $ajax->error('Invalid table name '.$_REQUEST['table_name']);
		
		require_once ROOT_PATH.'lib/external/PHPExcel/IOFactory.php';
		
		$objPHPExcel = PHPExcel_IOFactory::load(IMPORT_PATH.$_REQUEST['file_name']);
		
		$columns = array();
		$data = array();
		
		$worksheet = $objPHPExcel->getWorksheetIterator()->current();
		$lastRow = $worksheet->getHighestRow(); // e.g. 10
		$lastColumn = $worksheet->getHighestColumn(); // e.g 'F'
		$lastColumnIndex = PHPExcel_Cell::columnIndexFromString($lastColumn);
		for ($row = 1; $row <= $lastRow; ++ $row) {
			if($row > 1) $data[$row] = array();
			for ($col = 0; $col < $lastColumnIndex; ++ $col) {
				$cell = $worksheet->getCellByColumnAndRow($col, $row);
				$val = $cell->getValue();
				if($row == 1) $columns[$col] = $val;
				else $data[$row][$col] = $val;
			}
		}
		
		$colTypes = array();
		foreach($columns as $colIndex => $colName) {
			if($colName != niceName($colName)) $ajax->error('Invalid column name '.$colName);
			$colTypes[$colIndex] = 'bigint';
		}
		foreach($data as $row) {
			foreach($row as $colIndex => $val) {
				if($colTypes[$colIndex] == 'text') continue;
				if($colTypes[$colIndex] == 'double') {
					if(!is_numeric($val)) $colTypes[$colIndex] = 'text';
				} else if($colTypes[$colIndex] == 'bigint') {
					if((int)$val != $val) $colTypes[$colIndex] = 'double';
					if(!is_numeric($val)) $colTypes[$colIndex] = 'text';
				}
			}
		}
		
		$sqlColumns = array();
		$sqlParams = array();
		foreach($columns as $colIndex => $colName) {
			array_push($sqlColumns, $colName.' '.$colTypes[$colIndex]);
			array_push($sqlParams, ':param_'.$colIndex);
		}

		$dataDb->beginTransaction();
		
		$sql = 'create table '.$schema.'.'.$_REQUEST['table_name'].' ('.implode(',', $sqlColumns).');';
		try {
			$dataDb->exec($sql);
		} catch(Exception $e) {
			$ajax->error($e->getMessage());
		}
		
		$sql = 'insert into '.$schema.'.'.$_REQUEST['table_name'].' ('.implode(',', $columns).') values ('.implode(',', $sqlParams).');';
		try {
			$stmt = $dataDb->prepare($sql);
			foreach($data as $rowIndex => $row) {
				$params = array();
				foreach($row as $colIndex => $val) {
					$params['param_'.$colIndex] = $val;
				}
				$stmt->execute($params);
			}
		} catch(Exception $e) {
			$ajax->error($e->getMessage());
		}
		
		$dataDb->commit();
		
		$ajax->success();
	break;
	case 'create-table':
		if(empty($_REQUEST['table_name'])) $ajax->error();
		$_REQUEST['table_name'] = strtolower(trim($_REQUEST['table_name']));
		if($_REQUEST['table_name'] != niceName($_REQUEST['table_name'])) {
			$ajax->error('Invalid table name');
		}
		if(empty($_REQUEST['catalog_id'])) $ajax->error();
		if(empty($_REQUEST['srid'])) $ajax->error();
		if(empty($_REQUEST['geometry_type'])) $ajax->error();
		if(empty($_REQUEST['coordinate_dimension'])) $ajax->error();
		
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		
		$dataDb = GCApp::getDataDB($catalogPath);
		$schema = GCApp::getDataDBSchema($catalogPath);
		
		$columns = array('gid serial not null primary key');
		foreach($_REQUEST['columns'] as $column) {
			if(!in_array($column['type'], $columnTypes)) $ajax->error('Invalid column type');
			if($column['name'] != niceName($column['name'])) $ajax->error('Invalid column name');
			array_push($columns, $column['name'].' '.$column['type']);
		}
		$sql = "create table $schema.".$_REQUEST['table_name']." (".implode(', ', $columns).")";
		$dataDb->beginTransaction();
		try {
			$dataDb->exec($sql);
			$sql = "select addgeometrycolumn('$schema', :table, 'the_geom', :srid, :type, :dimension)";
			$stmt = $dataDb->prepare($sql);
			$stmt->execute(array(':table'=>$_REQUEST['table_name'], ':srid'=>$_REQUEST['srid'], ':type'=>$_REQUEST['geometry_type'], ':dimension'=>$_REQUEST['coordinate_dimension']));
		} catch(Exception $e) {
			$ajax->error($e->getMessage());
		}
		$sql = "GRANT SELECT ON TABLE $schema.".$_REQUEST['table_name']." TO ".MAP_USER.";";
		try {
			$dataDb->exec($sql);
		} catch(Exception $e) {
			$ajax->error($e->getMessage());
		}
		$dataDb->commit();
		$ajax->success();
	break;
	case 'create-tileindex':
		if(empty($_REQUEST['file_name'])) $ajax->error();
		if(empty($_REQUEST['catalog_id'])) $ajax->error();
		if(empty($_REQUEST['srid'])) $ajax->error();
		$_REQUEST['srid'] = trim($_REQUEST['srid']);
		if(empty($_REQUEST['table_name'])) $ajax->error();
		$_REQUEST['table_name'] = strtolower(trim($_REQUEST['table_name']));
		
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		
		$dataDb = GCApp::getDataDB($catalogPath);
		$schema = GCApp::getDataDBSchema($catalogPath);
		
		if(tableAlreadyExists($dataDb, $schema, $_REQUEST['table_name'])) $ajax->error('Table already exists');
		
		if($_REQUEST['table_name'] != niceName($_REQUEST['table_name'])) {
			$ajax->error('Invalid table name');
		}
		
		$baseDir = filesPathFromCatalog($_REQUEST['catalog_id']);
		if(!is_dir($baseDir.$_REQUEST['file_name'])) $ajax->error();
		$filesDir = $baseDir.addFinalSlash($_REQUEST['file_name']);
		
		$shapeFile = IMPORT_PATH.$_REQUEST['file_name'].'.shp';
		
		$cmd = 'gdaltindex '.escapeshellarg($shapeFile).' '.escapeshellarg($filesDir).'*';
		$gdalOutput = array();
		$retVal = -1;
		
        exec($cmd, $gdalOutput, $retVal);
		if($retVal != 0) $ajax->error('gdal tileindex error');
		
		$outputFile = IMPORT_PATH.$_REQUEST['file_name'].'.sql';
		$errorFile = ROOT_PATH.'config/debug/'.$_REQUEST['file_name'].'.err';
		
		if(!shp2pgsql($shapeFile, (int)$_REQUEST['srid'], $_REQUEST['table_name'], $outputFile, $errorFile)) $ajax->error('Shape to Postgres Error');
		
		$dataDb->beginTransaction();
		$sql = "set search_path = $schema, public;\n";
		$sql .= file_get_contents($outputFile);
		try {
			$dataDb->exec($sql);
		} catch(Exception $e) {
			$ajax->error($e->getMessage());
		}
		$sql = "GRANT SELECT ON TABLE $schema.".$_REQUEST['table_name']." TO ".MAP_USER.";";
		try {
			$dataDb->exec($sql);
		} catch(Exception $e) {
			$ajax->error($e->getMessage());
		}
		$dataDb->commit();
		
		deleteShapefile($shapeFile);
		@unlink($outputFile);
		
		$ajax->success();
		
	break;
	case 'check-upload-folder':
		if(empty($_REQUEST['directory'])) $ajax->error();
		$targetDir = addFinalSlash($_REQUEST['directory']);
		if(empty($_REQUEST['catalog_id'])) $ajax->error();
		
		if(strtolower($targetDir) != $targetDir || !simpleCharsOnly(str_replace('/', '', $targetDir))) {
			$ajax->success(array('data'=>'Invalid directory name (Allowed characters are a-z 0-9 _)'));
		}
		
		$basePath = filesPathFromCatalog($_REQUEST['catalog_id']);
		
		if(!is_dir($basePath.$targetDir)) {
			if(!mkdir($basePath.$targetDir)) $ajax->success(array('data'=>'Unable to create directory'));
		}
		$ajax->success(array('data'=>'ok'));
	break;
	default:
		$ajax->error();
	break;
}

function filesPathFromCatalog($catalogId) {
	global $ajax;
	$db = GCApp::getDB();
	
	$sql = "select files_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
	$stmt = $db->prepare($sql);
	$stmt->execute(array(':catalog_id'=>$catalogId));
	$basePath = $stmt->fetchColumn(0);
	if(empty($basePath)) return false;
	return addFinalSlash($basePath);
}

function deleteShapefile($fileFullPath) {
	global $extensions;
	
	$pathWoExtension = substr($fileFullPath, 0, strrpos($fileFullPath, '.'));
	foreach($extensions['shp'] as $extension) {
		@unlink($pathWoExtension.'.'.$extension);
	}
}

function shp2pgsql($shapefile, $srid, $tableName, $outputFile, $errorFile, array $options = array()) {
	$defaultOptions = array(
		'charset'=>'UTF-8',
		'mode'=>'create'
	);
	$options = array_merge($defaultOptions, $options);
	
	switch($options['mode']) {
		case 'create':
			$mode = '-c';
		break;
		case 'append':
			$mode = '-a';
		break;
		case 'replace':
			$mode = '-d';
		break;
	}
	
	$cmd = "shp2pgsql -W '".escapeshellarg($options['charset'])."' -s $srid $mode " . escapeshellarg($shapefile) . " " . 
		escapeshellarg($tableName) . " > " . 
		escapeshellarg($outputFile) . " 2> " . escapeshellarg($errorFile);

	$shp2pgsqlOutput = array();
	$retVal = -1;
	
	exec($cmd, $shp2pgsqlOutput, $retVal);
	
	if($retVal != 0) {
		file_put_contents($errorFile, $cmd, FILE_APPEND);
		return false;
	}
	return true;	
}

function tableAlreadyExists($dataDb, $schema, $tableName) {
    //deprecated, sostituire
    return GCApp::tableExists($dataDb, $schema, $tableName);
}

function simpleCharsOnly($string) {
	$pattern = '/^[a-z0-9_]*$/';
	return preg_match($pattern, $string) > 0;
}

function rrmdir($dir) { 
	if (is_dir($dir)) {
		$objects = scandir($dir); 
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") { 
				if (filetype($dir."/".$object) == "dir") @rrmdir($dir."/".$object); else @unlink($dir."/".$object); 
			}
		} 
		reset($objects); 
		@rmdir($dir); 
	} 
}