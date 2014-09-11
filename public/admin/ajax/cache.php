<?php
include_once "../../../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';

$ajax = new GCAjax();

if(empty($_REQUEST['action'])) $ajax->error();
if(empty($_REQUEST['project'])) $ajax->error('No project');

switch($_REQUEST['action']) {
    case 'list':
        $files = array();

        $configFile = MAPPROXY_CONFIG_PATH.$_REQUEST['project'].'.yaml';
        if(!file_exists($configFile)) $ajax->success($files);

        $content = file_get_contents($configFile);

        $config = yaml_parse($content);

        foreach($config['caches'] as $name => $cache) {
            $file = TILES_CACHE.$_REQUEST['project'].'/'.$cache['cache']['filename'];
            if(!file_exists($file)) continue;
            $size = filesize($file);
            
            array_push($files, array(
                'layer'=>$name,
                'name'=>$cache['cache']['filename'],
                'size'=>formatBytes($size)
            ));
        }

        $ajax->success(array('files'=>$files));
    break;
    case 'empty':
        if(empty($_REQUEST['file'])) $ajax->error();
        $file = TILES_CACHE.$_REQUEST['project'].'/'.$_REQUEST['file'];
        if(!file_exists($file)) $ajax->error('File does not exist');
        $ret = @unlink($file);
        if($ret) {
            $ajax->success();
        } else {
            $ajax->error();
        }
    break;
    default:
        $ajax->error('Invalid action');
    break;
}


function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 

    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 

    // Uncomment one of the following alternatives
    $bytes /= pow(1024, $pow);
    // $bytes /= (1 << (10 * $pow)); 

    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 
