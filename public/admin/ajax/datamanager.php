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

$autoUpdaters = array(
    'last_edit_user' => defined('LAST_EDIT_USER_COL_NAME') ? LAST_EDIT_USER_COL_NAME : false,
    'last_edit_date' => defined('LAST_EDIT_DATE_COL_NAME') ? LAST_EDIT_DATE_COL_NAME : false,
    'area' => defined('MEASURE_AREA_COL_NAME') ? MEASURE_AREA_COL_NAME : false,
    'length' => defined('MEASURE_LENGTH_COL_NAME') ? MEASURE_LENGTH_COL_NAME : false,
    'pointx'=> defined('COORDINATE_X_COL_NAME') ? COORDINATE_X_COL_NAME : false,
    'pointy'=> defined('COORDINATE_Y_COL_NAME') ? COORDINATE_Y_COL_NAME : false
);

// real path per browsing
$ajax = new GCAjax();
$db = GCApp::getDB();

if(empty($_REQUEST['action'])){
	$ajax->error("Required parameter 'action' is missing");
}

if (!is_dir(IMPORT_PATH)) {
	 $ajax->error(IMPORT_PATH . ' is not a directory');
}
if (!is_writable(IMPORT_PATH)) {
	 $ajax->error(IMPORT_PATH . ' is not writable');
}

switch($_REQUEST['action']) {
	case 'get-available-imports':
        $results = array();
		$imports = array(
			0=>'shp',
			1=>'raster',
			2=>'postgis',
			3=>'xls',
			// 4=>'csv', // not working, yet
		);
		checkMissingParameters($ajax, $_REQUEST, array('catalog_id'));
		$dir = filesPathFromCatalog($_REQUEST['catalog_id']);
		if(!$dir) unset($imports[1]);
		if(!defined('USE_PHP_EXCEL') || USE_PHP_EXCEL == false) unset($imports[3]);
        $results['imports'] = $imports;
        
        $results['lastEditColumn'] = $autoUpdaters['last_edit_user'] || $autoUpdaters['last_edit_date'];
        $results['measureColumn'] = $autoUpdaters['area'] || $autoUpdaters['length'];
        
		$ajax->success(array('imports'=>$imports));
	break;
	case 'upload-xls':
	case 'upload-csv':
	case 'upload-shp':
		$tempFile = $_FILES['Filedata']['tmp_name'];
		$targetFile = IMPORT_PATH . $_FILES['Filedata']['name'];
		if (false === move_uploaded_file($tempFile, $targetFile)) {
			throw new Exception("Could not move_uploaded_file($tempFile, $targetFile)");
		}
		echo str_replace($_SERVER['DOCUMENT_ROOT'], '', $targetFile);
	break;
	case 'upload-raster':
		checkMissingParameters($ajax, $_REQUEST, array('catalog_id', 'directory'));
		$targetDir = addFinalSlash($_REQUEST['directory']);
		
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
			checkMissingParameters($ajax, $_REQUEST, array('catalog_id'));
			$dir = filesPathFromCatalog($_REQUEST['catalog_id']);
			if(!is_dir($dir)) {
				$ajax->error("'$dir' is not a directory");
			}
			$files = elenco_dir($dir);
		} else if($_REQUEST['file_type'] == 'xls') {
			$files = elenco_file(IMPORT_PATH, array('xls','xlsx'));
		} else if($_REQUEST['file_type'] == 'csv') {
			$files = elenco_file(IMPORT_PATH, array('csv'));
		} else {
			$ajax->error("can not handle file_type '{$_REQUEST['file_type']}'");
		}
        if(empty($files) || !is_array($files)) $files = array();
        
		$data = array();
		foreach($files as $file) {
			array_push($data, array('file_name'=>$file));
		}
		$ajax->success(array('data'=>$data));
		
	break;
	case 'get-postgis-tables':
		checkMissingParameters($ajax, $_REQUEST, array('catalog_id'));
        $alphaOnly = !empty($_REQUEST['alhpaOnly']) && $_REQUEST['alhpaOnly'] != 'false';
        $geomOnly = !empty($_REQUEST['geomOnly']) && $_REQUEST['geomOnly'] != 'false';
        
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		
		$dataDb = GCApp::getDataDB($catalogPath);
		$schema = GCApp::getDataDBSchema($catalogPath);
		
		$sql = 'select table_name as name, coord_dimension as dim, srid, type '.
			' from information_schema.tables '.
			' left outer join geometry_columns on tables.table_name=geometry_columns.f_table_name and f_table_schema = :schema '.
			' where table_schema = :schema ';
        if($alphaOnly) $sql .= ' and coord_dimension is null ';
        if($geomOnly) $sql .= ' and coord_dimension is not null ';
        $sql .= ' order by table_name ';
		$stmt = $dataDb->prepare($sql);
		$stmt->execute(array(':schema'=>$schema));
		$data = array();
        
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            foreach($autoUpdaters as $type => $colName) {
                if(!$colName) continue;
                $row['has_'.$type.'_column'] = GCApp::columnExists($dataDb, $schema, $row['name'], $colName);
            }
            array_push($data, $row);
        }
		$ajax->success(array('data'=>$data));
	break;
    case 'add-column':
		checkMissingParameters($ajax, $_REQUEST, array('catalog_id', 'table_name', 'column_name', 'column_type'));
		
		$_REQUEST['column_name'] = strtolower(trim($_REQUEST['column_name']));
		if($_REQUEST['column_name'] != niceName($_REQUEST['column_name'])) {
			$ajax->error('Invalid column name');
		}
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		
		$dataDb = GCApp::getDataDB($catalogPath);
		$schema = GCApp::getDataDBSchema($catalogPath);
        
        if(!GCApp::tableExists($dataDb, $schema, $_REQUEST['table_name'])) {
			$ajax->error("table '{$_REQUEST['table_name']}' does not exist");
		}
        
        try {
            $sql = 'alter table '.$schema.'.'.$_REQUEST['table_name'].' add column '.$_REQUEST['column_name'].' '.$_REQUEST['column_type'];
            $dataDb->exec($sql);
        } catch(Exception $e) {
            $ajax->error($e->getMessage());
        }
        $ajax->success();
    break;
	case 'add-last-edit-column':
		checkMissingParameters($ajax, $_REQUEST, array('catalog_id', 'table_name'));
		
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		
		$dataDb = GCApp::getDataDB($catalogPath);
		$schema = GCApp::getDataDBSchema($catalogPath);
        $results = array();
        
        if(!GCApp::tableExists($dataDb, $schema, $_REQUEST['table_name'])) {
			$ajax->error("table '{$_REQUEST['table_name']}' does not exist");
		}
		
        
        $dataDb->beginTransaction();
        
        if($autoUpdaters['last_edit_user']) {
            array_push($results, 'usiamo last_edit_user');
            $sql = 'select count(*) from information_schema.routines where routine_name = :functionName and routine_schema = :schema';
            $stmt = $dataDb->prepare($sql);
            $stmt->execute(array('schema'=>'public', 'functionName'=>'gc_auto_update_user'));
            $updateUserExists = ($stmt->fetchColumn(0) > 0);
            
            try {
				if(!$updateUserExists) {
					array_push($results, 'non esiste la funzione gc_auto_update_user');
					if (!defined('CURRENT_EDITING_USER_TABLE')) {
						throw new Exception("constant CURRENT_EDITING_USER_TABLE is not defined");
					}
					createAutoUpdateUserFunction($dataDb, CURRENT_EDITING_USER_TABLE);
					array_push($results, 'creata la funzione gc_auto_update_user');
				}
                $sql = 'alter table '.$schema.'.'.$_REQUEST['table_name'].' add column '.$autoUpdaters['last_edit_user'].' text';
                $dataDb->exec($sql);
                array_push($results, 'creata la colonna '.$autoUpdaters['last_edit_user']);
                
                $sql = "CREATE TRIGGER trigger_".$_REQUEST['table_name']."_last_edit_user_auto_updater BEFORE INSERT OR UPDATE ON $schema.".$_REQUEST['table_name']." FOR EACH ROW
                        EXECUTE PROCEDURE public.gc_auto_update_user('".$autoUpdaters['last_edit_user']."');";
                $dataDb->exec($sql);
                array_push($results, 'creato il trigger ..._last_edit_user_auto_updater ');
            } catch(Exception $e) {
                $ajax->error($e->getMessage());
            }
        }
        
        if($autoUpdaters['last_edit_date']) {
            array_push($results, 'usiamo last_edit_date');
            $sql = 'select count(*) from information_schema.routines where routine_name = :functionName and routine_schema = :schema';
            $stmt = $dataDb->prepare($sql);
            $stmt->execute(array('schema'=>'public', 'functionName'=>'gc_auto_update_date'));
            $updateDateExists = ($stmt->fetchColumn(0) > 0);
            
            try {
	            if(!$updateDateExists) {
		            array_push($results, 'non esiste la funzione gc_auto_update_date');
                    createAutoUpdateDateFunction($dataDb);
                    array_push($results, 'creata la funzione gc_auto_update_date');
			    }
            
                $sql = 'alter table '.$schema.'.'.$_REQUEST['table_name'].' add column '.$autoUpdaters['last_edit_date'].' timestamp without time zone';
                $dataDb->exec($sql);
                array_push($results, 'aggiunta la colonna '.$autoUpdaters['last_edit_date']);
                
                $sql = "CREATE TRIGGER trigger_".$_REQUEST['table_name']."_last_edit_date_auto_updater BEFORE INSERT OR UPDATE ON $schema.".$_REQUEST['table_name']." FOR EACH ROW
                        EXECUTE PROCEDURE public.gc_auto_update_date('".$autoUpdaters['last_edit_date']."');";
                $dataDb->exec($sql);
                array_push($results, 'aggiunto il trigger ..._last_edit_date_auto_updater');
            } catch(Exception $e) {
                $ajax->error($e->getMessage());
            }
        }
        $dataDb->commit();

		$ajax->success($results);
	break;
	case 'add-measure-column':
		checkMissingParameters($ajax, $_REQUEST, array('catalog_id', 'table_name'));
		
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		
		$dataDb = GCApp::getDataDB($catalogPath);
		$schema = GCApp::getDataDBSchema($catalogPath);
        
        $sql = 'select type, f_geometry_column as column_name from public.geometry_columns where f_table_schema = :schema and f_table_name = :table';
        $stmt = $dataDb->prepare($sql);
        $stmt->execute(array('schema'=>$schema, 'table'=>$_REQUEST['table_name']));
        $geomColumn = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$geomColumn) {
			$ajax->error("Could not find the geometry column for $schema.{$_REQUEST['table_name']}");
		}
		
        $dataDb->beginTransaction();
        
        // controllo e inserimento funzione per aggiornare lunghezza e area
        $sql = 'select count(*) from information_schema.routines where routine_name = :functionName and routine_schema = :schema';
        $stmt = $dataDb->prepare($sql);
        $stmt->execute(array('schema'=>'public', 'functionName'=>'gc_auto_update_measure'));
        $res = $stmt->fetchColumn(0);
        
        if(empty($res)) {
            try {
                createAutoUpdateMeasureFunction($dataDb);
            } catch(Exception $e) {
                $ajax->error($e->getMessage());
            }
        }
        
        // controllo e inserimento funzione per aggiornare le coordinate del punto
        $sql = 'select count(*) from information_schema.routines where routine_name = :functionName and routine_schema = :schema';
        $stmt = $dataDb->prepare($sql);
        $stmt->execute(array('schema'=>'public', 'functionName'=>'gc_auto_update_coordinates'));
        $res = $stmt->fetchColumn(0);
        
        if(empty($res)) {
            try {
                createAutoUpdateCoordinatesFunction($dataDb);
            } catch(Exception $e) {
                $ajax->error($e->getMessage());
            }
        }

        //controllo tipo geometria per area/lunghezza
        $columnName = $measureFunction = null;
        if(in_array($geomColumn['type'], array('POLYGON', 'MULTIPOLYGON')) && $autoUpdaters['area']) {
            $columnName = $autoUpdaters['area'];
            $measureFunction = 'st_area';
        } else if(in_array($geomColumn['type'], array('LINESTRING', 'MULTILINESTRING')) && $autoUpdaters['length']) {
            $columnName = $autoUpdaters['length'];
            $measureFunction = 'st_length';
        }
        
        if($columnName && $measureFunction) { //aggiungo colonne e trigger per lunghezza/area
            try {
                $sql = 'DROP TRIGGER IF EXISTS trigger_'.$_REQUEST['table_name'].'_measure_auto_updater ON '.$schema.'.'.$_REQUEST['table_name'];
                $dataDb->exec($sql);
                
                $sql = 'alter table '.$schema.'.'.$_REQUEST['table_name'].' add column '.$columnName.' float';
                $dataDb->exec($sql);
                
                $sql = 'update '.$schema.'.'.$_REQUEST['table_name'].' set '.$columnName.' = '.$measureFunction.'('.$geomColumn['column_name'].')';
                $dataDb->exec($sql);
                
                $sql = "CREATE TRIGGER trigger_".$_REQUEST['table_name']."_measure_auto_updater BEFORE INSERT OR UPDATE ON $schema.".$_REQUEST['table_name']." FOR EACH ROW
                        EXECUTE PROCEDURE public.gc_auto_update_measure('$columnName', '$measureFunction', '".$geomColumn['column_name']."');";
                $dataDb->exec($sql);
                
            } catch(Exception $e) {
                $ajax->error($e->getMessage() .' on '.$sql);
            }
            //aggiungo colonne e trigger per coordinate
        } else if(in_array($geomColumn['type'], array('POINT')) && $autoUpdaters['pointx'] && $autoUpdaters['pointy']) {
            try {
                $sql = 'DROP TRIGGER IF EXISTS trigger_'.$_REQUEST['table_name'].'_coordinates_auto_updater ON '.$schema.'.'.$_REQUEST['table_name'];
                $dataDb->exec($sql);
                
                $sql = 'alter table '.$schema.'.'.$_REQUEST['table_name'].' add column '.$autoUpdaters['pointx'].' float';
                $dataDb->exec($sql);
                $sql = 'alter table '.$schema.'.'.$_REQUEST['table_name'].' add column '.$autoUpdaters['pointy'].' float';
                $dataDb->exec($sql);
                
                $sql = 'update '.$schema.'.'.$_REQUEST['table_name'].' set '.$autoUpdaters['pointx'].'=st_x('.$geomColumn['column_name'].'), '.$autoUpdaters['pointy'].'=st_y('.$geomColumn['column_name'].')';
                $dataDb->exec($sql);
                
                $sql = "CREATE TRIGGER trigger_".$_REQUEST['table_name']."_coordinates_auto_updater BEFORE INSERT OR UPDATE ON $schema.".$_REQUEST['table_name']." FOR EACH ROW
                        EXECUTE PROCEDURE public.gc_auto_update_coordinates('".$autoUpdaters['pointx']."', '".$autoUpdaters['pointy']."', '".$geomColumn['column_name']."');";
                $dataDb->exec($sql);
            } catch(Exception $e) {
                $ajax->error($e->getMessage() .' on '.$sql);
            }
        }
        $dataDb->commit();
        
		$ajax->success();
        
	break;
    case 'empty-table':
		checkMissingParameters($ajax, $_REQUEST, array('catalog_id', 'table_name'));
		
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		
		$dataDb = GCApp::getDataDB($catalogPath);
		$schema = GCApp::getDataDBSchema($catalogPath);
        
        if(!GCApp::tableExists($dataDb, $schema, $_REQUEST['table_name'])) $ajax->error('table does not exist');
        
        $sql = 'truncate table '.$_REQUEST['table_name'];
        try {
            $db->exec($sql);
        } catch(Exception $e) {
            $ajax->error($e->getMessage() .' on '.$sql);
        }

    break;
	case 'delete-table':
		checkMissingParameters($ajax, $_REQUEST, array('catalog_id', 'table_name'));
		
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
		checkMissingParameters($ajax, $_REQUEST, array('file_name', 'file_type'));
		
		$filePath = IMPORT_PATH.$_REQUEST['file_name'];
		if($_REQUEST['file_type'] == 'shp') {
			if(!file_exists($filePath)) {
				$ajax->error("File '$filePath' does not exist");
			}
			try {
				deleteFilegroupByExtension($filePath, $extensions['shp']);
			} catch (Exception $e) {
				$ajax->error($e->getMessage());
			}
		} else if($_REQUEST['file_type'] == 'raster') {
			if(empty($_REQUEST['catalog_id'])) {
				$ajax->error("missing parameter 'catalog_id'");
			}
			$dir = filesPathFromCatalog($_REQUEST['catalog_id']);
			if(!is_dir($dir)) {
				$ajax->error("'$dir' is not a directory");
			}
			try {
				rrmdir($dir.$_REQUEST['file_name']);
			} catch (Exception $e) {
				// TODO: add to log
				$ajax->error($e->getMessage());
			}
		} else if($_REQUEST['file_type'] == 'xls') {
            if(!file_exists($filePath)) {
				$ajax->error("File '$filePath' does not exist");
			}
			if (false === @unlink($filePath)) {
				$ajax->error("File '$filePath' could not be removed, $php_errormsg");
			}
        } else {
			$ajax->error("file type '{$_REQUEST['file_type']}' can not be handled");
		}
		
		if (file_exists($filePath)) {
			$ajax->error("Internal error: File '$filePath' was not deleted");
		}
		
		$ajax->success();
		
	break;
	
    case 'export-csv':
		checkMissingParameters($ajax, $_REQUEST, array('catalog_id', 'table_name'));
		
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		$dataDb = GCApp::getDataDB($catalogPath);
		$dbParams = GCApp::getDataDBParams($catalogPath);
		
		if(!GCApp::tableExists($dataDb, $dbParams['schema'], $_REQUEST['table_name'])) $ajax->error('table does not exist');
        
        $sql = 'select * from '.$dbParams['schema'].'.'.$_REQUEST['table_name'];
        $data = $dataDb->query($sql)->fetchAll(PDO::FETCH_ASSOC);

		$fileName = $_REQUEST['table_name'].'_'.date('YmdHis').'_'.rand(0,9999);
        $filePath = ROOT_PATH.'public/admin/export/'.$fileName.'.csv';
        $handle = fopen($filePath, 'w');
        fputcsv($handle, array_keys(reset($data)));
        foreach($data as $row) fputcsv($handle, $row);
        fclose($handle);
        
		$ajax->success(array('filename'=>$fileName.'.csv'));
    break;
	case 'export-xls':
		checkMissingParameters($ajax, $_REQUEST, array('catalog_id', 'table_name'));
		
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		$dataDb = GCApp::getDataDB($catalogPath);
		$dbParams = GCApp::getDataDBParams($catalogPath);
		
		if(!GCApp::tableExists($dataDb, $dbParams['schema'], $_REQUEST['table_name'])) $ajax->error('table does not exist');
		
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
		checkMissingParameters($ajax, $_REQUEST, array('catalog_id', 'table_name'));
		
		include_once(ADMIN_PATH.'lib/filesystem.php');
		
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		$dataDb = GCApp::getDataDB($catalogPath);
		$dbParams = GCApp::getDataDBParams($catalogPath);
		
		if(!GCApp::tableExists($dataDb, $dbParams['schema'], $_REQUEST['table_name'])) $ajax->error('table does not exist');
		
        $export = new GCExport($dataDb, 'shp');
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
		checkMissingParameters($ajax, $_REQUEST, array('catalog_id', 'table_name', 'srid', 'file_name', 'mode'));
		if(!in_array($_REQUEST['mode'], array('create', 'append', 'replace'))) {
            $ajax->error('mode');
        }
		$_REQUEST['srid'] = trim($_REQUEST['srid']);
		$_REQUEST['table_name'] = trim($_REQUEST['table_name']);
		
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		
		$dataDb = GCApp::getDataDB($catalogPath);
		$schema = GCApp::getDataDBSchema($catalogPath);
		
		$tableExists = GCApp::tableExists($dataDb, $schema, $_REQUEST['table_name']);
		if ($_REQUEST['mode'] == 'create' && $tableExists) {
			$ajax->error('Table ' . $_REQUEST['table_name'] . ' already exists');
		}
		if ($_REQUEST['mode'] != 'create' && !$tableExists) {
			$ajax->error('Table ' . $_REQUEST['table_name'] . ' does not exist');
		}
		if ($_REQUEST['mode'] == 'create' && $_REQUEST['table_name'] != niceName($_REQUEST['table_name'])) {
			$ajax->error('Invalid table name');
		}
		$tableName = $_REQUEST['table_name'];

		if (!file_exists(IMPORT_PATH . $_REQUEST['file_name'])) {
			$ajax->error('File does not exists');
		}

		$fileName = substr($_REQUEST['file_name'], 0, strrpos($_REQUEST['file_name'], '.'));
		foreach ($extensions['shp'] as $extension) {
			if (!file_exists(IMPORT_PATH . $fileName . '.' . $extension)) {
				$ajax->error('Missing required ' . $extension . ' file');
			}
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
			$sql = "GRANT SELECT ON TABLE $schema.$tableName TO ".MAP_USER.";";
			$dataDb->exec($sql);
		} catch(Exception $e) {
			$ajax->error($e->getMessage());
		}
		@unlink($outputFile);
		$dataDb->commit();
		
		$ajax->success();
	break;
    case 'import-csv':
		checkMissingParameters($ajax, $_REQUEST, array('catalog_id', 'file_name', 'table_name'));
		$_REQUEST['table_name'] = trim($_REQUEST['table_name']);
		
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		
		$dataDb = GCApp::getDataDB($catalogPath);
		$schema = GCApp::getDataDBSchema($catalogPath);
		
		$tableExists = GCApp::tableExists($dataDb, $schema, $_REQUEST['table_name']);
		if($_REQUEST['mode'] == 'create' && $tableExists) $ajax->error('Table '.$_REQUEST['table_name'].' already exists');
		if($_REQUEST['mode'] != 'create' && !$tableExists) $ajax->error('Table '.$_REQUEST['table_name'].' does not exist');
		
		if($_REQUEST['table_name'] != niceName($_REQUEST['table_name'])) $ajax->error('Invalid table name '.$_REQUEST['table_name']);
        
        // TODO
    break;
	case 'import-xls':
		checkMissingParameters($ajax, $_REQUEST, array('catalog_id', 'file_name', 'table_name'));
		$_REQUEST['table_name'] = trim($_REQUEST['table_name']);
		
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		
		$dataDb = GCApp::getDataDB($catalogPath);
		$schema = GCApp::getDataDBSchema($catalogPath);
		
		$tableExists = GCApp::tableExists($dataDb, $schema, $_REQUEST['table_name']);
		if($_REQUEST['mode'] == 'create' && $tableExists) $ajax->error('Table '.$_REQUEST['table_name'].' already exists');
		if($_REQUEST['mode'] != 'create' && !$tableExists) $ajax->error('Table '.$_REQUEST['table_name'].' does not exist');
		
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
				if($row == 1) {
                    if(!empty($val)) $columns[$col] = $val;
                } else {
                    if(isset($columns[$col])) $data[$row][$col] = $val;
                }
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
        
        $create = ($_REQUEST['mode'] == 'create' || $_REQUEST['mode'] == 'replace');
        if($_REQUEST['mode'] == 'replace') {
            $sql = 'drop table '.$schema.'.'.$_REQUEST['table_name'];
            $dataDb->exec($sql);
        }
		
		try {
	        if($create) {
		        $sql = 'create table '.$schema.'.'.$_REQUEST['table_name'].' ('.implode(',', $sqlColumns).');';
                $dataDb->exec($sql);
			}
			$sql = 'insert into '.$schema.'.'.$_REQUEST['table_name'].' ('.implode(',', $columns).') values ('.implode(',', $sqlParams).');';
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
        
        $dataDb->exec('GRANT SELECT ON TABLE '.$schema.'.'.$_REQUEST['table_name'].' TO '.MAP_USER);
		$dataDb->commit();
		$ajax->success();
	break;
	case 'create-table':
		checkMissingParameters($ajax, $_REQUEST, array('catalog_id', 'table_name', 'srid', 'geometry_type', 'coordinate_dimension'));
		$_REQUEST['table_name'] = strtolower(trim($_REQUEST['table_name']));
		if($_REQUEST['table_name'] != niceName($_REQUEST['table_name'])) {
			$ajax->error('Invalid table name');
		}
		
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
			$sql = "GRANT SELECT ON TABLE $schema.".$_REQUEST['table_name']." TO ".MAP_USER.";";
			$dataDb->exec($sql);
		} catch(Exception $e) {
			$ajax->error($e->getMessage());
		}
		$dataDb->commit();
		$ajax->success();
	break;
	case 'create-tileindex':
		checkMissingParameters($ajax, $_REQUEST, array('catalog_id', 'file_name', 'table_name', 'srid'));
		$_REQUEST['srid'] = trim($_REQUEST['srid']);
		$_REQUEST['table_name'] = strtolower(trim($_REQUEST['table_name']));
		
		$sql = "select catalog_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':catalog_id'=>$_REQUEST['catalog_id']));
		$catalogPath = $stmt->fetchColumn(0);
		
		$dataDb = GCApp::getDataDB($catalogPath);
		$schema = GCApp::getDataDBSchema($catalogPath);
		
		if(GCApp::tableExists($dataDb, $schema, $_REQUEST['table_name'])) $ajax->error('Table already exists');
		
		if($_REQUEST['table_name'] != niceName($_REQUEST['table_name'])) {
			$ajax->error('Invalid table name');
		}
		
		$baseDir = filesPathFromCatalog($_REQUEST['catalog_id']);
		if(!is_dir($baseDir.$_REQUEST['file_name'])) {
			$ajax->error("'".$baseDir.$_REQUEST['file_name']." is not a directory");
		}
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
		
		try {
			$dataDb->beginTransaction();
			$sql = "set search_path = $schema, public;\n";
			$sql .= file_get_contents($outputFile);
			$dataDb->exec($sql);
			$sql = "GRANT SELECT ON TABLE $schema.".$_REQUEST['table_name']." TO ".MAP_USER.";";
			$dataDb->exec($sql);
		} catch(Exception $e) {
			$ajax->error($e->getMessage());
		}
		$dataDb->commit();
		
		deleteFilegroupByExtension($shapeFile, $extensions['shp']);
		@unlink($outputFile);
		
		$ajax->success();
		
	break;
	case 'create-pyramid-raster':
		checkMissingParameters($ajax, $_REQUEST, array('catalog_id', 'file_name'));
		
		$baseDir = filesPathFromCatalog($_REQUEST['catalog_id']);
		if(!is_dir($baseDir.$_REQUEST['file_name'])) {
			$ajax->error("'".$baseDir.$_REQUEST['file_name']." is not a directory");
		}
		$filesDir = $baseDir.addFinalSlash($_REQUEST['file_name']);
		
		$cmd = 'for i in `find '.escapeshellarg($filesDir).'*.{png,jpeg,jpg,tif,gif,tiff} 2> /dev/null`;do gdaladdo -r average $i 3 9 27 81; done';
		$gdalOutput = array();
		$retVal = -1;
		
        exec($cmd, $gdalOutput, $retVal);
		if($retVal != 0) $ajax->error('gdaladdo error');
		
		$ajax->success();
		
	break;
	case 'check-upload-folder':
		checkMissingParameters($ajax, $_REQUEST, array('catalog_id', 'directory'));
		$targetDir = addFinalSlash($_REQUEST['directory']);
		
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
		$ajax->error("action {$_REQUEST['action']} can not be handled");
	break;
}

function filesPathFromCatalog($catalogId) {
	$db = GCApp::getDB();
	
	$sql = "select files_path from ".DB_SCHEMA.".catalog where catalog_id=:catalog_id";
	$stmt = $db->prepare($sql);
	$stmt->execute(array(':catalog_id'=>$catalogId));
	$basePath = $stmt->fetchColumn(0);
	if(empty($basePath)) return false;
	return addFinalSlash($basePath);
}

/**
 * 
 * @param array $request
 * @param array $mandatoryFields
 */
function checkMissingParameters($ajax, array $request, array $mandatoryFields) {
	$missingKeys = array_diff($mandatoryFields, array_keys($request));
	if (count($missingKeys) > 0) {
		$msg = "'" . implode("', '", $missingKeys) . "'";
		$ajax->error("Mandatory parameters missing in request: ". $msg);
	}
}

/**
 * Delete all files with share the same basename as the file
 * 
 * @param string $fileFullPath
 * @param array $groupExtensions
 * @throws Exception
 */
function deleteFilegroupByExtension($fileFullPath, array $groupExtensions) {
	// TODO: optional parameter: fail_on_missing
	// since shape files may have a differing number of files, this might make sense
	$pathWoExtension = substr($fileFullPath, 0, strrpos($fileFullPath, '.'));
	foreach ($groupExtensions as $extension) {
		$delendum = $pathWoExtension . '.' . $extension;
		if (file_exists($delendum)) {
			if (false === @unlink($delendum)) {
				throw new Exception("Could not remove '$delendum', $php_errormsg");
			}
		}
	}
}

function shp2pgsql($shapefile, $srid, $tableName, $outputFile, $errorFile, array $options = array()) {
	$defaultOptions = array(
		'charset'=>'UTF-8',
		'mode'=>'create'
	);
	$options = array_merge($defaultOptions, $options);
	
    $index = '';
	switch($options['mode']) {
		case 'create':
			$mode = '-c';
            $index = '-I';
		break;
		case 'append':
			$mode = '-a';
		break;
		case 'replace':
			$mode = '-d';
            $index = '-I';
		break;
	}
    
    if(defined('SET_BYTEA_OUTPUT')) {
        putenv("PGOPTIONS=-c bytea_output=".SET_BYTEA_OUTPUT);
    }
	
	$cmd = "shp2pgsql $index -W '".escapeshellarg($options['charset'])."' -s $srid $mode " . escapeshellarg($shapefile) . " " . 
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

function simpleCharsOnly($string) {
	$pattern = '/^[a-z0-9_]*$/';
	return preg_match($pattern, $string) > 0;
}

/**
 * Recursively remove directory
 * 
 * @param type $dir
 */
function rrmdir($dir) {
	if (!is_dir($dir)) {
		// wouldn't it be better to simply remove the file?
		throw new Exception("'$dir' is not a directory");
	}
	$objects = scandir($dir);
	foreach ($objects as $object) {
		if ($object === "." ||  $object === "..") {
			continue;
		}
		if (filetype($dir . "/" . $object) == "dir") {
			rrmdir($dir . "/" . $object);
		} else {
			if (false === @unlink($dir . "/" . $object)) {
				throw new Exception("Could not remove file '$dir/$object', $php_errormsg");
			}
		}
	}
	reset($objects);
	if (false === @rmdir($dir)) {
		throw new Exception("Could not remove directory '$dir/$object', $php_errormsg");
	}
}

function createAutoUpdateUserFunction($dataDb, $currentEditingUserTable) {
    $sql = <<<EODDL
CREATE OR REPLACE FUNCTION public.gc_auto_update_user ()
	RETURNS trigger AS
	\$body\$
	DECLARE
		rec record;
		BEGIN
			BEGIN
				DELETE FROM temporary_trigger_function_user;
				INSERT INTO temporary_trigger_function_user SELECT NEW.*;
			EXCEPTION WHEN OTHERS THEN
				CREATE TEMPORARY TABLE temporary_trigger_function_user as SELECT NEW.*;
			END;

			EXECUTE 'UPDATE temporary_trigger_function_user set ' || TG_ARGV[0] || '= (SELECT username FROM {$currentEditingUserTable} WHERE id = 1)';

			SELECT * FROM temporary_trigger_function_user INTO rec;
			RETURN rec;
		END;
	\$body\$
LANGUAGE 'plpgsql' VOLATILE CALLED ON NULL INPUT SECURITY INVOKER COST 100;
EODDL;
    $dataDb->exec($sql);
}

function createAutoUpdateDateFunction($dataDb) {
    $sql = 'CREATE OR REPLACE FUNCTION public.gc_auto_update_date ()
            RETURNS trigger AS
            $body$'.
            " DECLARE
                rec record;
                BEGIN
                    BEGIN
                        DELETE FROM temporary_trigger_function_date;
                        INSERT INTO temporary_trigger_function_date SELECT NEW.*;
                    EXCEPTION WHEN OTHERS THEN
                        CREATE TEMPORARY TABLE temporary_trigger_function_date as SELECT NEW.*;
                    END;
                    
                    execute 'UPDATE temporary_trigger_function_date set ' || TG_ARGV[0] || '= NOW()';
                    SELECT * from temporary_trigger_function_date into rec;
                    return rec;
                END;".
            '$body$'.
            "LANGUAGE 'plpgsql' VOLATILE CALLED ON NULL INPUT SECURITY INVOKER COST 100;";
    $dataDb->exec($sql);
}

function createAutoUpdateMeasureFunction($dataDb) {
    $sql = 'CREATE OR REPLACE FUNCTION public.gc_auto_update_measure ()
            RETURNS trigger AS
            $body$'.
            "DECLARE
                rec record;
                val double precision;
                BEGIN
                    BEGIN
                        DELETE FROM temporary_trigger_function_measure;
                        INSERT INTO temporary_trigger_function_measure SELECT NEW.*;
                    EXCEPTION WHEN OTHERS THEN
                        CREATE TEMPORARY TABLE temporary_trigger_function_measure as SELECT NEW.*;
                    END;

                    execute 'SELECT ' || TG_ARGV[1] || '(($1).' || TG_ARGV[2] || ') ' into val using new;                                    
                    execute 'UPDATE temporary_trigger_function_measure set ' || TG_ARGV[0] || '=' || val;
                    SELECT * from temporary_trigger_function_measure into rec;
                    --DROP TABLE temporary_trigger_function_measure;
                    return rec;
                END;".
            '$body$'.
            "LANGUAGE 'plpgsql' VOLATILE CALLED ON NULL INPUT SECURITY INVOKER COST 100;";
    $dataDb->exec($sql);
}

function createAutoUpdateCoordinatesFunction($dataDb) {
    $sql = 'CREATE OR REPLACE FUNCTION public.gc_auto_update_coordinates ()
            RETURNS trigger AS
            $body$'.
            "DECLARE
                rec record;
                x double precision;
                y double precision;
                BEGIN
                
                    BEGIN
                        DELETE FROM temporary_trigger_function_coordinates;
                        INSERT INTO temporary_trigger_function_coordinates SELECT NEW.*;
                    EXCEPTION WHEN OTHERS THEN
                        CREATE TEMPORARY TABLE temporary_trigger_function_coordinates as SELECT NEW.*;
                    END;
                
                    execute 'SELECT st_x(($1).' || TG_ARGV[2] || ') ' into x using new;
                    execute 'UPDATE temporary_trigger_function_coordinates set ' || TG_ARGV[0] || '=' || x;
                    execute 'SELECT st_y(($1).' || TG_ARGV[2] || ') ' into y using new;
                    execute 'UPDATE temporary_trigger_function_coordinates set ' || TG_ARGV[1] || '=' || y;
                    SELECT * from temporary_trigger_function_coordinates into rec;
                    return rec;
                END;".
            '$body$'.
            "LANGUAGE 'plpgsql' VOLATILE CALLED ON NULL INPUT SECURITY INVOKER COST 100;";
    $dataDb->exec($sql);
}
