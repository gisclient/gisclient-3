<?php
require_once('../../config/config.php');
require_once(ROOT_PATH.'lib/ajax.class.php');
require_once(ROOT_PATH.'lib/export.php');
$ajax = new GCAjax();

switch($_REQUEST['export_format']) {
    case 'dxf':
    case 'shp':
        if(empty($_REQUEST['tables']) || !is_array($_REQUEST['tables'])) $ajax->error('Empty tables');
        if(!defined('GC_EXPORT_CATALOG')) $ajax->error('Undefined export catalog');
        
        $db = GCApp::getDB();
        
        $sql = 'select catalog_path from '.DB_SCHEMA.'.catalog where catalog_name=:catalog_name';
        $stmt = $db->prepare($sql);
        $stmt->execute(array('catalog_name'=>GC_EXPORT_CATALOG));
        $catalogPath = $stmt->fetchColumn(0);
        if(empty($catalogPath)) $ajax->error('Undefined catalog '.GC_EXPORT_CATALOG);
        
		$dataDb = GCApp::getDataDB($catalogPath);
		$dbParams = GCApp::getDataDBParams($catalogPath);
        
        $tables = array();
        
        if(!empty($_REQUEST['extent'])) {
            if(!defined('GC_EXPORT_TMP_SCHEMA')) $ajax->error('Undefined export tmp schema');
            if(!is_array($_REQUEST['extent']) || count($_REQUEST['extent']) != 4) $ajax->error('Wrong extent type');
            if(empty($_REQUEST['srid'])) $ajax->error('Empty srid');
            if(strpos($_REQUEST['srid'], ':') !== false) list($auth, $srid) = explode(':', $_REQUEST['srid']);
            else $srid = $_REQUEST['srid'];
            
            $sql = 'select st_setsrid(st_makebox2d(st_point(:p0, :p1), st_point(:p2, :p3)), :srid)';
            $stmt = $db->prepare($sql);
            $stmt->execute(array(
                'p0'=>$_REQUEST['extent'][0],
                'p1'=>$_REQUEST['extent'][1],
                'p2'=>$_REQUEST['extent'][2],
                'p3'=>$_REQUEST['extent'][3],
                'srid'=>$srid
            ));
            $extent = $stmt->fetchColumn(0);
            
            foreach($_REQUEST['tables'] as $table) {
                if(!GCApp::tableExists($dataDb, $dbParams['schema'], $table)) continue;
                $columns = GCApp::getColumns($dataDb, $dbParams['schema'], $table);
                $geomColIndex = array_search('the_geom', $columns);
                if($geomColIndex === false) continue;
                unset($columns[$geomColIndex]);
                $tmpTableName = 'export_'.$table.'_'.session_id().'_'.rand(0,999999);
                $sql = 'create table '.GC_EXPORT_TMP_SCHEMA.'.'.$tmpTableName.' as '.
                    ' select '.implode(', ', $columns).', st_intersection(the_geom, :geom) as the_geom '.
                    ' from '.$dbParams['schema'].'.'.$table.
                    ' where st_intersects(the_geom, :geom) ';
                $stmt = $dataDb->prepare($sql);
                $stmt->execute(array('geom'=>$extent));
                
                $sql = 'select count(*) from '.GC_EXPORT_TMP_SCHEMA.'.'.$tmpTableName;
                $count = $dataDb->query($sql)->fetchColumn(0);
                if(empty($count)) {
                    $dataDb->exec('drop table '.GC_EXPORT_TMP_SCHEMA.'.'.$tmpTableName);
                    continue;
                }
                
                $sql = 'insert into geometry_columns(f_table_catalog, f_table_schema, f_table_name, f_geometry_column, coord_dimension, srid, type) '.
                    ' select f_table_catalog, :tmp_table_schema, :tmp_table_name, f_geometry_column, 2, srid, type from geometry_columns '.
                    ' where f_table_schema = :schema and f_table_name = :table';
                $stmt = $dataDb->prepare($sql);
                $stmt->execute(array(
                    'tmp_table_schema'=>GC_EXPORT_TMP_SCHEMA,
                    'tmp_table_name'=>$tmpTableName,
                    'schema'=>$dbParams['schema'],
                    'table'=>$table
                ));
                
                array_push($tables, array(
                    'db'=>$dbParams['db_name'],
                    'schema'=>GC_EXPORT_TMP_SCHEMA,
                    'table'=>$tmpTableName,
                    'name'=>$table
                ));
            }
        } else {
            foreach($_REQUEST['tables'] as $table) {
                if(!GCApp::tableExists($dataDb, $dbParams['schema'], $table)) continue;
                array_push($tables, array(
                    'db'=>$dbParams['db_name'],
                    'schema'=>$dbParams['schema'],
                    'table'=>$table,
                    'name'=>$table
                ));
            }
        }
        
        if($_REQUEST['export_format'] == 'shp') {
            $export = new GCExport($dataDb, 'shp');
            $zipFile = $export->export($tables, array('name'=>'dbt'));
        } else if($_REQUEST['export_format'] == 'dxf') {
            $export = new GCExport($dataDb, 'dxf');
            $zipFile = $export->export($tables, array('name'=>'dbt', 'extent'=>$_REQUEST['extent'], 'srid'=>$srid));
        }
        if(!empty($_REQUEST['extent'])) {
            foreach($tables as $table) {
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

        if(empty($_REQUEST['data']) || !is_array($_REQUEST['data'])) {
            die(json_encode(array('result' => 'error', 'error' => 'Empty data')));
        }

        if(empty($_REQUEST['fields']) || !is_array($_REQUEST['fields'])) {
            die(json_encode(array('result' => 'error', 'error' => 'Empty fields')));
        }

        if(empty($_REQUEST['export_format']) || !in_array($_REQUEST['export_format'], array('xls', 'pdf'))) {
            die(json_encode(array('result' => 'error', 'error' => 'Invalid export format')));
        }

        $excel = new Excel_XML();
        $fields = array();
        foreach($_REQUEST['fields'] as $field) array_push($fields, $field['title']);
        $excel->addRow($fields);

        foreach($_REQUEST['data'] as $row) {
            $dataRow = array();
            foreach($_REQUEST['fields'] as $field) {
                if(!isset($row[$field['field_name']]) || empty($row[$field['field_name']]) || $row[$field['field_name']] == 'null') {
                    $dataRow[$field['field_name']] = null;
                    continue;
                }
                $dataRow[$field['field_name']] = $row[$field['field_name']];
            }
            $excel->addRow($dataRow);
        }

        if(empty($_REQUEST['feature_type'])) {
            $filename = GCApp::getUniqueRandomTmpFilename(GC_WEB_TMP_DIR, 'export', 'xls');
        } else {
            $parts = explode('.', $_REQUEST['feature_type']);
            if(count($parts) > 1) $filename = $parts[1];
            else $filename = $parts[0];
            
            $filename .= '_'.date('Y-m-d_H-i').'_'.rand(0,999).'.xls';
        }
        $content = $excel->generateXML();
        file_put_contents(GC_WEB_TMP_DIR.$filename, $content);
        die(json_encode(array('result'=>'ok','file'=>GC_WEB_TMP_URL.$filename)));
    break;
}