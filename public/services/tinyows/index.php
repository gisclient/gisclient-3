<?php
include '../../../config/config.php';
require_once ROOT_PATH . 'lib/GCService.php';

$gcService = GCService::instance();
$gcService->startSession();

$debugTinyOWS = defined('DEBUG') && DEBUG == 1;

$parts = explode('/', $_SERVER['REQUEST_URI']);
$startIndex = array_search('tinyows', $parts);
if(!isset($parts[$startIndex+1]) || !isset($parts[$startIndex+2])) {
	throw new Exception("parameter tinyows was found at position {$startIndex}, at least two more parameters for project and typename are needed");
}
$project = $parts[$startIndex+1];
$typeName = $parts[$startIndex+2];

$configFile = ROOT_PATH.'map/'.$project.'/'.$typeName.'.xml';
if(!file_exists($configFile)) {
	throw new Exception("Configuration file \"{$configFile}\" not found");
}

$requestMethod = $_SERVER['REQUEST_METHOD'];
if($debugTinyOWS) {
	file_put_contents(DEBUG_DIR.'tinyows-logs.txt', "HTTP method: $requestMethod\n");
	file_put_contents(DEBUG_DIR.'tinyows-logs.txt', "_GET:\n".var_export($_GET, true)."\n\n", FILE_APPEND);
	file_put_contents(DEBUG_DIR.'tinyows-logs.txt', "_POST:\n".var_export($_POST, true)."\n\n", FILE_APPEND);
	file_put_contents(DEBUG_DIR.'tinyows-logs.txt', "_COOKIE:\n".var_export($_COOKIE, true)."\n\n", FILE_APPEND);
	file_put_contents(DEBUG_DIR.'tinyows-logs.txt', "body:\n".file_get_contents('php://input')."\n\n", FILE_APPEND);
}

// try to start session
if(!isset($_SESSION)) {
	if(defined('GC_SESSION_NAME') && isset($_COOKIE[GC_SESSION_NAME])) {
		session_name(GC_SESSION_NAME);
	}
	session_start();
}

$autoUpdateUser = (defined('LAST_EDIT_USER_COL_NAME') && LAST_EDIT_USER_COL_NAME);
if($autoUpdateUser) {
    $xml = simplexml_load_file($configFile);
    $connection = $xml->pg->attributes();
    $params = array();
    foreach($connection as $k => $v) $params[$k] = (string)$v;
    $table = $xml->layer->attributes();
    foreach($table as $k => $v) $params[$k] = (string)$v;
    $dataDb = GCApp::getDataDb($params['dbname'].'/'.$params['schema']);
}


$descriptorspec = array(
   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
   2 => array("file", DEBUG_DIR."tinyows-errors.txt", "a") // stderr is a file to write to
);

$envVars = array(
	'TINYOWS_CONFIG_FILE' => $configFile,
	'REQUEST_METHOD' => $requestMethod,
);

$db = GCApp::getDB();
list($layergroupName, $layerName) = explode('.', $typeName);
$sql = 'select project_name from '.DB_SCHEMA.'.theme 
	inner join '.DB_SCHEMA.'.layergroup using(theme_id) 
	inner join '.DB_SCHEMA.'.layer using(layergroup_id)
	where layergroup_name=:lg_name and layer_name=:l_name';
$stmt = $db->prepare($sql);
$stmt->execute(array(':lg_name'=>$layergroupName, ':l_name'=>$layerName));
$projectName = $stmt->fetchColumn(0);
if(empty($projectName)) {
		throw new Exception("Missing project name for layergroup $layergroupName and layer $layerName");
}

if (!isset($_SESSION['GISCLIENT_USER_LAYER'])) {
	if (!isset($_SERVER['PHP_AUTH_USER'])) {
		file_put_contents(DEBUG_DIR . 'tinyows-logs.txt', "PHP_AUTH_USER not set, request authentication\n", FILE_APPEND);
		header('WWW-Authenticate: Basic realm="Gisclient"');
		header('HTTP/1.0 401 Unauthorized');
	} else {
		$user = new GCUser();
		if ($user->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
			$user->setAuthorizedLayers(array('project_name' => $projectName));
		}
	}
}

$authorized = false;
//if(!empty($_SESSION['USERNAME']) && $_SESSION['USERNAME'] == SUPER_USER) $authorized = true; non serve piu
if(!empty($_SESSION['GISCLIENT_USER_LAYER'][$project][$typeName]['WFST'])) {
	$authorized = true;
} else {
	print_debug(var_export($_SESSION['GISCLIENT_USER_LAYER'], true), null, 'tinyows');
}

if(!$authorized) {
	file_put_contents(DEBUG_DIR.'tinyows-logs.txt', "missing authorization\n", FILE_APPEND);
	header('HTTP/1.0 401 Unauthorized');
	echo '<?xml version="1.0" encoding="UTF-8"?><ServiceExceptionReport xmlns="http://www.opengis.net/ogc" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.opengis.net/ogc http://schemas.opengis.net/wms/1.1.1/OGC-exception.xsd" version="1.2.0"><ServiceException code="PermissionDenied">Permission Denied</ServiceException></ServiceExceptionReport>';
	exit(0);
}

if($requestMethod == 'GET') {
	$envVars['QUERY_STRING'] = $_SERVER['QUERY_STRING'];
} elseif ($requestMethod == 'POST') {
	$fileContent = file_get_contents('php://input');
	
	$envVars['CONTENT_LENGTH'] = strlen($fileContent);
	$envVars['CONTENT_TYPE'] = 'text/xml';
	print_debug("input content:\n".$fileContent, null, 'tinyows');
} else {
	throw new Exception("HTTP method $requestMethod not handled");	
}

print_debug("envVars:\n".var_export($envVars, true), null, 'tinyows');
$pipes = array();

if($autoUpdateUser) {
	if (!defined('CURRENT_EDITING_USER_TABLE')) {
		throw new Exception("constant CURRENT_EDITING_USER_TABLE is not defined");
	}
    if(!GCApp::tableExists($dataDb, 'public', CURRENT_EDITING_USER_TABLE)) {
        $sql = 'create table public.'.CURRENT_EDITING_USER_TABLE.' (id integer primary key, username text, editingdate timestamp without time zone default NOW());';
		print_debug('creo la tabella public.'.CURRENT_EDITING_USER_TABLE."\n$sql", null, 'tinyows');
        $dataDb->exec($sql);
    }

    $n = 0;
    while(anotherUserIsEditing($dataDb, CURRENT_EDITING_USER_TABLE)) {
		print_debug('another user is editing .. '.$n, null, 'tinyows');
        if($n > 4) {
            file_put_contents(DEBUG_DIR.'tinyows-logs.txt', 'current_editing_table is not empty after 2 minutes... give up!', FILE_APPEND);
            die('Another user is currently editing, please try again');
        }
        sleep(30);
        $n++;
    }
    
    try {
        $sql = 'insert into '.CURRENT_EDITING_USER_TABLE.' (id, username) values (1, :username)';
        $stmt = $dataDb->prepare($sql);
        $stmt->execute(array('username'=>$_SESSION['USERNAME']));
		print_debug('inserted user '.$_SESSION['USERNAME'], null, 'tinyows');
    } catch(Exception $e) {
		print_debug('cannot insert into '.CURRENT_EDITING_USER_TABLE.', maybe there is still an user there!', null, 'tinyows');
        die('Another user is currently editing, please try again');
    }
}

$process = proc_open(TINYOWS_EXEC, $descriptorspec, $pipes, TINYOWS_PATH, $envVars);
if(is_resource($process)) {
	if($envVars['REQUEST_METHOD'] == 'POST') {
		fwrite($pipes[0], $fileContent);
		fclose($pipes[0]);
	}
	$response = stream_get_contents($pipes[1]);
	fclose($pipes[1]);
	$return = proc_close($process);
	print_debug("process returned with $return", null, 'tinyows');
	print_debug("response:\n" .$response, null, 'tinyows');
	$pos = strpos($response, '<?xml');
	if($pos !== false) {
		$response = substr($response, $pos);
	}
    header('Content-Type: text/xml; charset=utf-8');
	echo $response;
} else {
	print_debug("\$envVars:\n" .var_export($envVars, true), null, 'tinyows');
	header("HTTP/1.1 500 Internal Server Error");
	echo "failed to run tinyows";
	exit(0);
}

if($autoUpdateUser) {
    $sql = 'delete from '.CURRENT_EDITING_USER_TABLE;
    $dataDb->exec($sql);
    file_put_contents(DEBUG_DIR.'tinyows-logs.txt', 'deleted user '."\n\n", FILE_APPEND);
}


function anotherUserIsEditing($db, $currentEditingUserTable) {
	$sql = "delete from {$currentEditingUserTable} where editingdate < (NOW() - interval '5 minutes')";
    $db->exec($sql);
    
	$sql = "select count(*) from {$currentEditingUserTable}";
    return ($db->query($sql)->fetchColumn(0) > 0);
}
