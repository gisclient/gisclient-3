<?php
require_once '../../config/config.php';
require_once ROOT_PATH.'lib/ajax.class.php';
require_once ROOT_PATH.'lib/export.php';

$ajax = new GCAjax();
$auth = new GCUser();
$db = GCApp::getDb();

if (!isset($data))
{
    $inputJSONText = file_get_contents('php://input');
    if (($data = json_decode($inputJSONText, true)) === null) {
        $data = $_REQUEST;
    }
}

switch($data['export_format']) {
    case 'dxf':
    case 'shp':
        if(empty($data['tables']) || !is_array($data['tables'])) $ajax->error('Empty tables');
        
        $tables = array();
        foreach($data['tables'] as $table) {
            $dataDb = null;
            if(isset($table['table'])) {
                if(isset($table['catalog'])) {
                    $catalogPath = GCApp::getCatalogPath($table['catalog']);
                } else {
                    if(!defined('GC_EXPORT_CATALOG')) $ajax->error('Undefined export catalog');
                    $catalogPath = GC_EXPORT_CATALOG;
                }
                $dbParams = GCApp::getDataDBParams($catalogPath);
                $dataDb = GCApp::getDataDB($catalogPath);
                array_push($tables, array(
                    'tablename'=>$table['table'],
                    'schema'=>$dbParams['schema'],
                    'dbName'=>$dbParams['db_name'],
                    'db'=>$dataDb
                ));
            } else if(isset($table['layer'])) {
                $authorizedLayers = $auth->getAuthorizedLayers(array('mapset_name'=>$data['mapset']));
                
                $sql = 'select catalog_path, layer.data as tablename, layer_id from '.DB_SCHEMA.'.catalog 
                    inner join '.DB_SCHEMA.'.layer using(catalog_id)
                    inner join '.DB_SCHEMA.'.layergroup using(layergroup_id)
                    where layergroup_name = :layergroup and layer_name = :layer';
                $stmt = $db->prepare($sql);
                list($layergroup, $layer) = explode('.', $table['layer']);
                $stmt->execute(array(
                    'layergroup'=>$layergroup,
                    'layer'=>$layer
                ));
                $layer = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if(empty($layer) || !in_array($layer['layer_id'], $authorizedLayers)) continue;
                
                $dbParams = GCApp::getDataDBParams($layer['catalog_path']);
                $dataDb = GCApp::getDataDB($layer['catalog_path']);
                array_push($tables, array(
                    'tablename'=>$layer['tablename'],
                    'schema'=>$dbParams['schema'],
                    'dbName'=>$dbParams['db_name'],
                    'db'=>$dataDb
                ));
            }
        }
        
        $exportTables = array();
        if(!empty($data['extent'])) {
            if(!defined('GC_EXPORT_TMP_SCHEMA')) $ajax->error('Undefined export tmp schema');
            if(!is_array($data['extent']) || count($data['extent']) != 4) $ajax->error('Wrong extent type');
            if(empty($data['srid'])) $ajax->error('Empty srid');
            if(strpos($data['srid'], ':') !== false) list($auth, $srid) = explode(':', $data['srid']);
            else $srid = $data['srid'];
            
            $sql = 'select st_setsrid(st_makebox2d(st_point(:p0, :p1), st_point(:p2, :p3)), :srid)';
            $stmt = $db->prepare($sql);
            $stmt->execute(array(
                'p0'=>$data['extent'][0],
                'p1'=>$data['extent'][1],
                'p2'=>$data['extent'][2],
                'p3'=>$data['extent'][3],
                'srid'=>$srid
            ));
            $extent = $stmt->fetchColumn(0);
            
            foreach($tables as $table) {
                if(!GCApp::tableExists($table['db'], $table['schema'], $table['tablename'])) continue;
                $columns = GCApp::getColumns($table['db'], $table['schema'], $table['tablename']);
                $geomColIndex = array_search('the_geom', $columns);
                if($geomColIndex === false) continue;
                unset($columns[$geomColIndex]);
                $tmpTableName = 'export_'.$table['tablename'].'_'.session_id().'_'.rand(0,999999);
                $sql = 'create table '.GC_EXPORT_TMP_SCHEMA.'.'.$tmpTableName.' as '.
                    ' select '.implode(', ', $columns).', st_intersection(the_geom, :geom) as the_geom '.
                    ' from '.$dbParams['schema'].'.'.$table['tablename'].
                    ' where st_intersects(the_geom, :geom) ';
                $stmt = $table['db']->prepare($sql);
                $stmt->execute(array('geom'=>$extent));
                
                $sql = 'select count(*) from '.GC_EXPORT_TMP_SCHEMA.'.'.$tmpTableName;
                $count = $table['db']->query($sql)->fetchColumn(0);
                if(empty($count)) {
                    $table['db']->exec('drop table '.GC_EXPORT_TMP_SCHEMA.'.'.$tmpTableName);
                    continue;
                }
                
                $sql = 'insert into geometry_columns(f_table_catalog, f_table_schema, f_table_name, f_geometry_column, coord_dimension, srid, type) '.
                    ' select f_table_catalog, :tmp_table_schema, :tmp_table_name, f_geometry_column, 2, srid, type from geometry_columns '.
                    ' where f_table_schema = :schema and f_table_name = :table';
                $stmt = $table['db']->prepare($sql);
                $stmt->execute(array(
                    'tmp_table_schema'=>GC_EXPORT_TMP_SCHEMA,
                    'tmp_table_name'=>$tmpTableName,
                    'schema'=>$table['schema'],
                    'table'=>$table['tablename']
                ));
                array_push($exportTables, array(
                    'db'=>$table['dbName'],
                    'db_instance'=>$table['db'],
                    'table'=>$tmpTableName,
                    'schema'=>GC_EXPORT_TMP_SCHEMA,
                    'name'=>$table['tablename']
                ));
            }
        } else {
            foreach($tables as $table) {
                if(!GCApp::tableExists($table['db'], $table['schema'], $table['tablename'])) continue;
                array_push($exportTables, array(
                    'db'=>$table['dbName'],
                    'db_instance'=>$table['db'],
                    'table'=>$table['tablename'],
                    'schema'=>$table['schema'],
                    'name'=>$table['tablename']
                ));
            }
        }
        
        if($data['export_format'] == 'shp') {
            $zipFile = null;
            foreach($exportTables as $table) {
                $export = new GCExport($table['db_instance'], 'shp');
                $zipFile = $export->export(array($table), array(
                    'name'=>'export',
                    'add_to_zip'=>$zipFile,
                    'return_url'=>false
                ));
            }
            $zipFile = $export->getExportUrl() . $zipFile;
        } else if($data['export_format'] == 'dxf') {
            $zipFile = null;
            foreach($exportTables as $table) {
                $export = new GCExport($table['db_instance'], 'dxf');
                $zipFile = $export->export(array($table), array(
                    'name'=>$table['name'],
                    'add_to_zip'=>$zipFile,
                    'return_url'=>false,
                    'extent'=>$data['extent'],
                    'srid'=>$srid
                ));
            }
        }
        $zipFile = $export->getExportUrl() . $zipFile;
        if(!empty($data['extent'])) {
            foreach($exportTables as $table) {
                $dataDb->exec('drop table '.GC_EXPORT_TMP_SCHEMA.'.'.$table['table']);
                $sql = 'delete from geometry_columns where f_table_schema=:tmp_schema and f_table_name=:tmp_table';
                $stmt = $db->prepare($sql);
                $stmt->execute(array('tmp_schema'=>GC_EXPORT_TMP_SCHEMA, 'tmp_table'=>$table['table']));
            }
        }
        $ajax->success(array('file'=>$zipFile));
    break;
    case 'xls':
        require_once('include/php-excel.class.php');
        /*
        input array
        array(
            'data' => data from wfs getFeature
            'fields' => array( // ordered list of fields to be used in export
                0 => array('name'=>'field_name','title'=>'Field Title'),
                ...
            ),
            'export_format'=>'xls' // xls or pdf
        );

        output
        jsonArray(
            'result'=>string // ok o error
            'file'=>string //url to file, se result==ok
            'error'=>string //exception message, se result==error
        )
        */

        if(empty($data['data']) || !is_array($data['data'])) {
            die(json_encode(array('result' => 'error', 'error' => 'Empty data')));
        }

        if(empty($data['fields']) || !is_array($data['fields'])) {
            die(json_encode(array('result' => 'error', 'error' => 'Empty fields')));
        }

        if(empty($data['export_format']) || !in_array($data['export_format'], array('xls', 'pdf'))) {
            die(json_encode(array('result' => 'error', 'error' => 'Invalid export format')));
        }

        $excel = new Excel_XML();
        $fields = array();
        foreach($data['fields'] as $field) array_push($fields, $field['title']);
        $excel->addRow($fields);

        foreach($data['data'] as $row) {
            $dataRow = array();
            foreach($data['fields'] as $field) {
                if(!isset($row[$field['field_name']]) || empty($row[$field['field_name']]) || $row[$field['field_name']] == 'null') {
                    $dataRow[$field['field_name']] = null;
                    continue;
                }
                $dataRow[$field['field_name']] = $row[$field['field_name']];
            }
            $excel->addRow($dataRow);
        }

        if(empty($data['feature_type'])) {
            $filename = GCApp::getUniqueRandomTmpFilename(GC_WEB_TMP_DIR, 'export', 'xls');
        } else {
            $parts = explode('.', $data['feature_type']);
            if(count($parts) > 1) $filename = $parts[1];
            else $filename = $parts[0];
            
            $filename .= '_'.date('Y-m-d_H-i').'_'.rand(0,999).'.xls';
        }
        $content = $excel->generateXML();
        file_put_contents(GC_WEB_TMP_DIR.$filename, $content);
        die(json_encode(array('result'=>'ok','file'=>GC_WEB_TMP_URL.$filename)));
    break;
    case 'pdf':
        
        if(empty($data['data']) || !is_array($data['data'])) {
            die(json_encode(array('result' => 'error', 'error' => 'Empty data')));
        }

        if(empty($data['fields']) || !is_array($data['fields'])) {
            die(json_encode(array('result' => 'error', 'error' => 'Empty fields')));
        }

        if(empty($data['export_format']) || !in_array($data['export_format'], array('xls', 'pdf'))) {
            die(json_encode(array('result' => 'error', 'error' => 'Invalid export format')));
        }
        
	if(!file_exists(GC_FOP_LIB)) {
            die(json_encode(array('result'=>'error','error' => 'fop lib does not exist')));
        }
	
        require_once GC_FOP_LIB;
        
        $_REQUEST['request_type'] = 'table';
        require_once 'include/printDocument.php';
        
        try {
            $printTable = new printDocument();

            if(!empty($_REQUEST['lang'])) {
                    $printTable->setLang($_REQUEST['lang']);
            }
            if(!empty($_REQUEST['logoSx'])) $printTable->setLogo($_REQUEST['logoSx']);
                else if(defined('GC_PRINT_LOGO_SX')) $printTable->setLogo(GC_PRINT_LOGO_SX);
            if(!empty($_REQUEST['logoDx'])) $printTable->setLogo($_REQUEST['logoDx'], 'dx');
                else if(defined('GC_PRINT_LOGO_DX')) $printTable->setLogo(GC_PRINT_LOGO_DX, 'dx');

            $TmpPath = GC_WEB_TMP_DIR;
            $file = $printTable->printTablePDF($data);
         } 
         catch (Exception $e) {
            die(json_encode(array('result'=>'error','error' => $e->getMessage())));
         }
         die(json_encode(array('result'=>'ok','file'=>$file)));
    break;   
}
