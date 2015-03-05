<?php
require_once('../../config/config.php');
require_once(ROOT_PATH.'lib/ajax.class.php');
require_once(ROOT_PATH.'lib/export.php');
$ajax = new GCAjax();
$db = GCApp::getDB();

if(empty($_REQUEST['srid'])) die('empty srid');

if(empty($_REQUEST['catalog'])) die('empty catalog');
        
$sql = 'select catalog_path from '.DB_SCHEMA.'.catalog where catalog_name=:catalog_name';
$stmt = $db->prepare($sql);
$stmt->execute(array('catalog_name'=>$_REQUEST['catalog']));
$catalogPath = $stmt->fetchColumn(0);
if(empty($catalogPath)) $ajax->error('Undefined catalog '.$_REQUEST['catalog']);

$dataDb = GCApp::getDataDB($catalogPath);
$dbParams = GCApp::getDataDBParams($catalogPath);

if(empty($_REQUEST['docOptions']) || !is_array($_REQUEST['docOptions'])) die('empty or invalid doc options');
$docOptions = $_REQUEST['docOptions'];
if(empty($docOptions['template']) || !file_exists(ROOT_PATH.'config/'.$docOptions['template'])) die('empty or non existing template');

$assign = array(
    'formValues'=>array(),
    'arts'=>array(),
    'table'=>array(
        'groups'=>array(),
        'rows'=>array(
        
        )
    )
);

// creo una lista di gruppi da usare per raggruppare le features
$groupBy = false;
if(!empty($docOptions['groupBy']) && is_array($docOptions['groupBy'])) {
    $groupBy = $docOptions['groupBy'];
    
    $sql = 'select catalog_path from '.DB_SCHEMA.'.catalog where catalog_name=:catalog_name';
    $stmt = $db->prepare($sql);
    $stmt->execute(array('catalog_name'=>$groupBy['catalog']));
    $catalogPath = $stmt->fetchColumn(0);
    if(empty($catalogPath)) $ajax->error('Undefined catalog '.$groupBy['catalog']);
    
    $groupByDb = GCApp::getDataDB($catalogPath);
    $groupByDbParams = GCApp::getDataDBParams($catalogPath);
    
    if(!GCApp::tableExists($groupByDb, $groupByDbParams['schema'], $groupBy['table'])) die('table '.$groupBy['table'].' does not exist');
    $columnNames = GCApp::getColumns($groupByDb, $groupByDbParams['schema'], $groupBy['table']);
    if(!in_array($groupBy['field'], $columnNames)) $ajax->error('Column '.$groupBy['field'].' of table '.$groupBy['table'].' does not exist');
    
    $sql = 'select distinct '.$docOptions['groupBy']['field'].' from '.$groupByDbParams['schema'].'.'.$groupBy['table'];
    foreach($db->query($sql, PDO::FETCH_NUM) as $row) {
        $assign['table']['groups'][$row[0]] = null;
    }
}

if(!empty($_REQUEST['formValues']) && is_array($_REQUEST['formValues'])) {
    foreach($_REQUEST['formValues'] as $field) $assign['formValues'][$field['name']] = $field['value'];
}

if(empty($_REQUEST['intersections']) || !is_array($_REQUEST['intersections'])) {
    $ajax->error('At least one layer shall be selected');
}

// controllo la configurazione delle intersezioni e creo una lista di tutte le intersezioni
foreach($_REQUEST['intersections'] as $intersection) {
    if(!GCApp::tableExists($dataDb, $dbParams['schema'], $intersection['tableName'])) $ajax->error('Table '.$intersection['tableName'].' does not exist');
    
    $columnNames = GCApp::getColumns($dataDb, $dbParams['schema'], $intersection['tableName']);
    if(!in_array($intersection['artField'], $columnNames)) $ajax->error('Column '.$intersection['artField'].' of table '.$intersection['tableName'].' does not exist');
    if(!in_array($intersection['descField'], $columnNames)) $ajax->error('Column '.$intersection['descField'].' of table '.$intersection['tableName'].' does not exist');
    
    $sql = 'select distinct '.$intersection['artField'].' as art_field from '.$dbParams['schema'].'.'.$intersection['tableName'];
    $arts = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach($arts as $art) $assign['arts'][str_replace('.', '_', $art['art_field'])] = null;
}

// controllo le features selezionate da mappa
if(empty($_REQUEST['features']) || !is_array($_REQUEST['features'])) {
    $ajax->error('At least one feature shall be selected');
}
$features = $_REQUEST['features'];
$sql = 'select st_geomfromtext(:geom, :srid)';
$getGeom = $db->prepare($sql);
foreach($features as $key => &$feature) {
    $getGeom->execute(array(
        'geom'=>$feature['the_geom'],
        'srid'=>$_REQUEST['srid']
    ));
    $feature['the_geom'] = $getGeom->fetchColumn(0);
    $feature['intersections'] = array();
    $assign['table']['rows'][$key] = $feature;
}
unset($feature);

$minIntersectionArea = !empty($docOptions['minIntersectionArea']) ? (float)$docOptions['minIntersectionArea'] : 0;

foreach($_REQUEST['intersections'] as $intersection) {
    $sql = 'select st_area(st_intersection(the_geom, :geom)) / st_area(:geom) as intersection_ratio, '.$intersection['artField'].' as art_field, '.$intersection['descField'].' as desc_field from '.$dbParams['schema'].'.'.$intersection['tableName'].' where st_intersects(the_geom, :geom) ';
    if(!empty($intersection['areaOnly'])) $sql .= ' and st_area(st_intersection(the_geom, :geom)) > '.$minIntersectionArea.' ';
    $intersectStmt = $db->prepare($sql);
    
    foreach($features as $key => $feature) {
        $intersectStmt->execute(array('geom'=>$feature['the_geom']));
        $results = $intersectStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($results as $result) {
            $result['areaOnly'] = !empty($intersection['areaOnly']);
            array_push($assign['table']['rows'][$key]['intersections'], $result);
            $assign['arts'][str_replace('.', '_', $result['art_field'])] = ' ';
        }
    }
}

file_put_contents('debug.txt', var_export($assign, true));

$tolerance = null;
$partialText = null;
if(!empty($docOptions['partialText']) && !empty($docOptions['partialIntersectionTolerance'])) {
    $tolerance = (float)$docOptions['partialIntersectionTolerance'] / 100;
    $partialText = $docOptions['partialText'];
}

foreach($assign['table']['rows'] as &$row) {
    $arts = array();
    foreach($row['intersections'] as $intersection) {
        if(!isset($arts[$intersection['art_field']])) {
            $arts[$intersection['art_field']] = array_merge($intersection, array('intersection_ratio' => 0));
        }
        if($intersection['areaOnly']) $arts[$intersection['art_field']]['intersection_ratio'] += $intersection['intersection_ratio'];
        else $arts[$intersection['art_field']]['intersection_ratio'] = 1;
    }
    $row['intersections'] = array();
    foreach($arts as $art) {
        if(!empty($tolerance) && $art['areaOnly'] && $art['intersection_ratio'] < $tolerance) {
            $art['desc_field'] = $partialText . ' ' . $art['desc_field'];
        }
        array_push($row['intersections'], $art);
    }
}
unset($row);

//raggruppo gli oggetti per il campo field di groupBy
if($groupBy) {
    $assign['table']['groupedrows'] = array();
    foreach($assign['table']['rows'] as $k => $feature) {
        if(!isset($assign['table']['groupedrows'][$feature[$groupBy['field']]])) {
            $assign['table']['groups'][$feature[$groupBy['field']]] = ' ';
            $assign['table']['groupedrows'][$feature[$groupBy['field']]] = array();
        }
        array_push($assign['table']['groupedrows'][$feature[$groupBy['field']]], $feature);
    }
}



//var_export($assign);
//var_export($assign['table']['groupedrows']);


// TBS

require_once ROOT_PATH . 'lib/external/tbs_class.php'; // TinyButStrong template engine
include_once ROOT_PATH . 'lib/external/tbs_plugin_opentbs.php';


$TBS = new clsTinyButStrong; // new instance of TBS
$TBS->Plugin(TBS_INSTALL, OPENTBS_PLUGIN); // load OpenTBS plugin
$TBS->PlugIn(OPENTBS_DELETE_COMMENTS);
$TBS->LoadTemplate(ROOT_PATH.'config/'.$docOptions['template'], OPENTBS_ALREADY_UTF8);
$TBS->MergeField('data', $assign['formValues']);
$TBS->MergeBlock('datatable', $assign['table']['rows']);
if($groupBy) {
    foreach($assign['table']['groupedrows'] as $key => $val) {
        $TBS->MergeBlock('groupedtable_'.$key, $val);
    }
    $TBS->MergeField('groups', $assign['table']['groups']);
}
    
$TBS->MergeField('arts', $assign['arts']);

$block1 = array(
    array('name'=>'art36'),
    array('name'=>'art37')
);



$filename = $docOptions['filename'].rand(0,1000000).'.docx';

$TBS->Show(OPENTBS_FILE, GC_WEB_TMP_DIR.$filename);
//FINE TBS


$ajax->success(array('url'=>GC_WEB_TMP_URL.$filename, 'assign'=>$assign));