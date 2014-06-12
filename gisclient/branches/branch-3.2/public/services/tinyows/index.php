<?php
include('../../../config/config.php');

$project = null;
$typeName = null;
$parts = explode('/', $_SERVER['REQUEST_URI']);
$startIndex = array_search('tinyows', $parts);
if(!isset($parts[$startIndex+1])) die('a');
else $project = $parts[$startIndex+1];
if(!isset($parts[$startIndex+2])) die('b');
else $typeName = $parts[$startIndex+2];

$configFile = ROOT_PATH.'map/'.$project.'/'.$typeName.'.xml';

if(!file_exists($configFile)) die('c');

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

if(defined('DEBUG') && DEBUG == 1) {
	$string = var_export($_REQUEST, true)."\n\n".file_get_contents('php://input');
	file_put_contents(DEBUG_DIR.'tinyows-logs.txt', $string."\n\n\n");
}

$descriptorspec = array(
   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
   2 => array("file", DEBUG_DIR."tinyows-errors.txt", "a") // stderr is a file to write to
);

$envVars = array(
	'TINYOWS_CONFIG_FILE' => $configFile
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
if(empty($projectName)) die('d');

if(!isset($_SESSION['GISCLIENT_USER_LAYER'])) {
	if (!isset($_SERVER['PHP_AUTH_USER'])) {
		header('WWW-Authenticate: Basic realm="Gisclient"');
		header('HTTP/1.0 401 Unauthorized');
	} else {
        $user = new GCUser();
        if($user->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
            $user->setAuthorizedLayers(array('project_name'=>$projectName));
        }
	}
}

$authorized = false;
//if(!empty($_SESSION['USERNAME']) && $_SESSION['USERNAME'] == SUPER_USER) $authorized = true; non serve piu
if(!empty($_SESSION['GISCLIENT_USER_LAYER'][$project][$typeName]['WFST'])) $authorized = true;
if(!$authorized) die('<?xml version="1.0" encoding="UTF-8"?><ServiceExceptionReport xmlns="http://www.opengis.net/ogc" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.opengis.net/ogc http://schemas.opengis.net/wms/1.1.1/OGC-exception.xsd" version="1.2.0"><ServiceException code="PermissionDenied">Permission Denied</ServiceException></ServiceExceptionReport>');


if(!empty($_GET)) {
	$envVars['REQUEST_METHOD'] = 'GET';
	$envVars['QUERY_STRING'] = $_SERVER['QUERY_STRING'];
} else {
	$fileContent = file_get_contents('php://input');
    
	$envVars['REQUEST_METHOD'] = 'POST';
	$envVars['CONTENT_LENGTH'] = strlen($fileContent);
	$envVars['CONTENT_TYPE'] = 'text/xml';
	if(defined('DEBUG') && DEBUG == 1) file_put_contents(DEBUG_DIR.'tinyows-input.txt', $fileContent);
}
if(defined('DEBUG') && DEBUG == 1) file_put_contents(DEBUG_DIR.'tinyows-input.txt', var_export($envVars, true), FILE_APPEND);
$pipes = array();

if($autoUpdateUser) {
    if(!GCApp::tableExists($dataDb, 'public', CURRENT_EDITING_USER_TABLE)) {
        file_put_contents(DEBUG_DIR.'tinyows-logs.txt', 'creo la tabella '.CURRENT_EDITING_USER_TABLE."\n\n", FILE_APPEND);
        $sql = 'create table '.CURRENT_EDITING_USER_TABLE.' (id integer, username text, editingdate timestamp without time zone default NOW(), CONSTRAINT current_editing_user_pkey PRIMARY KEY (id));';
        $dataDb->exec($sql);
    }

    $n = 0;
    while(anotherUserIsEditing($dataDb)) {
        file_put_contents(DEBUG_DIR.'tinyows-logs.txt', 'another user is editing .. '.$n."\n\n", FILE_APPEND);
        if($n > 4) {
            file_put_contents(DEBUG_DIR.'tinyows-errors.txt', 'current_editing_table is not empty after 2 minutes... give up!');
            die('Another user is currently editing, please try again');
        }
        sleep(30);
        $n++;
    }
    
    try {
        $sql = 'insert into '.CURRENT_EDITING_USER_TABLE.' (id, username) values (1, :username)';
        $stmt = $dataDb->prepare($sql);
        $stmt->execute(array('username'=>$_SESSION['USERNAME']));
        file_put_contents(DEBUG_DIR.'tinyows-logs.txt', 'inserted user '.$_SESSION['USERNAME']."\n\n", FILE_APPEND);
    } catch(Exception $e) {
        file_put_contents(DEBUG_DIR.'tinyows-errors.txt', 'cannot insert into '.CURRENT_EDITING_USER_TABLE.', maybe there is still an user there!');
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
	
	$pos = strpos($response, '<?xml');
	if($pos !== false) {
		$response = substr($response, $pos);
	}
	if(defined('DEBUG') && DEBUG == 1) file_put_contents(DEBUG_DIR.'tinyows-output.txt', $response);
    header('Content-Type: text/xml; charset=utf-8');
	echo $response;
} else {
	if(defined('DEBUG') && DEBUG == 1) file_put_contents(DEBUG_DIR.'tinyows-errors.txt', var_export($envVars, true)."\n\n".$fileContent);
}
if($autoUpdateUser) {
    $sql = 'delete from '.CURRENT_EDITING_USER_TABLE;
    $dataDb->exec($sql);
    file_put_contents(DEBUG_DIR.'tinyows-logs.txt', 'deleted user '."\n\n", FILE_APPEND);
}



function anotherUserIsEditing($db) {
    
    $sql = "delete from ".CURRENT_EDITING_USER_TABLE." where editingdate < (NOW() - interval '5 minutes')";
    $db->exec($sql);
    
    $sql = "select count(*) from ".CURRENT_EDITING_USER_TABLE;
    return ($db->query($sql)->fetchColumn(0) > 0);
}
