<?php
require_once '../../config/config.php';
require_once ROOT_PATH.'lib/ajax.class.php';
require_once ROOT_PATH . 'lib/GCService.php';

$gcService = GCService::instance();
$gcService->startSession();

$ajax = new GCAjax();

if (empty($GEOLOCATOR_CONFIG) && empty($GEOLOCATOR_CONFIG_PATH)) {
    $ajax->error('Missing geolocator configuration');
}

if (empty($_REQUEST['action']) || !in_array($_REQUEST['action'], array('search', 'get-geom'))) {
    $ajax->error('Invalid action');
}

if (empty($_REQUEST['mapset'])) {
    $ajax->error('Undefined mapset');
}
$mapset = $_REQUEST['mapset'];

$config = null;
if (!empty($GEOLOCATOR_CONFIG_PATH)) {
    $configFile = $GEOLOCATOR_CONFIG_PATH . "{$mapset}/geolocator.json";

    if (is_file($configFile)) {
        $json = json_decode(file_get_contents($configFile), true);
        if (!empty($_REQUEST['lang'])) {
            foreach ($json as $lang => $c) {
                if ($lang == $_REQUEST['lang']) {
                    $config = $c;
                }
            }
            if (empty($config)) {
                // language mapset configuration not available
                $config = array_values($json)[0];
            }
        } else {
            $config = array_values($json)[0];
        }
    }
}
if (!empty($GEOLOCATOR_CONFIG) && empty($config)) {
    if (!empty($_REQUEST['lang']) && !empty($GEOLOCATOR_CONFIG["{$mapset}_{$_REQUEST['lang']}"])) {
        $config = $GEOLOCATOR_CONFIG["{$mapset}_{$_REQUEST['lang']}"];
    } else if (!empty($GEOLOCATOR_CONFIG[$mapset])) {
        $config = $GEOLOCATOR_CONFIG[$mapset];
    }
}

if (empty($config)) {
    $ajax->error("Missing geolocator configuration \"{$mapset}\"");
}

$db = GCApp::getDB();

$sql = 'select catalog_path from '.DB_SCHEMA.'.catalog INNER JOIN '.DB_SCHEMA.'.mapset USING(project_name) where catalog_name=:name AND mapset_name=:mapset';

$stmt = $db->prepare($sql);
$stmt->execute(array('name'=>$config['catalogname'], 'mapset'=>$mapset));
$catalogPath = $stmt->fetchColumn(0);
if (empty($catalogPath)) {
    $ajax->error("Invalid catalog name \"{$config['catalogname']}\" in configuration");
}
$dataDb = GCApp::getDataDB($catalogPath);


if ($_REQUEST['action'] == 'search') {
    if (empty($_REQUEST['key'])) {
        $ajax->error('Undefined key');
    }
    $key = str_replace(' ', '%', trim($_REQUEST['key']));
    $key = str_replace('%%', '%', trim($key));
    $key = str_replace('%%', '%', trim($key));
    
    $sql = ' select '.$config['namefield'].' as name, '.$config['idfield'].' as id from '.$config['tablename'].' where '.$config['namefield'].' ilike :key ';
    if (!empty($config['where'])) {
        $sql .= ' and '.$config['where'];
    }
    if (!empty($config['order'])) {
        $sql .= ' order by '.$config['order'];
    }
    $sql .= ' limit :limit ';
    $sql .= ' offset :offset';

    try {
        $stmt = $dataDb->prepare($sql);
        $stmt->execute(array(
            'key'=>'%'.$key.'%',
            'limit' => !empty($_REQUEST['limit'])? $_REQUEST['limit'] : 30,
            'offset' => !empty($_REQUEST['offset'])? $_REQUEST['offset'] : 0,
        ));
        $results = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($results, $row);
        }
    } catch (Exception $e) {
        $ajax->error($e->getMessage());
    }
    $ajax->success(array('data'=>$results));
} else if ($_REQUEST['action'] == 'get-geom') {
    if (empty($_REQUEST['id'])) {
        $ajax->error('Undefined id');
    }
    
    $sql = ' select st_astext(ST_Force_2D('.$config['geomfield'].')) from '.$config['tablename'].' where '.$config['idfield'].' = :id ';
    try {
        $stmt = $dataDb->prepare($sql);
        $stmt->execute(array('id'=>$_REQUEST['id']));
        $data = $stmt->fetchColumn(0);
    } catch (Exception $e) {
        $ajax->error($e->getMessage());
    }
    $ajax->success(array('data'=>$data));
}
