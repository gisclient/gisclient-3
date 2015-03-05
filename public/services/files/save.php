<?php
include_once "../../../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';
include_once ADMIN_PATH.'lib/functions.php';
require_once ROOT_PATH . 'lib/GCService.php';

$gcService = GCService::instance();
$gcService->startSession();


$ajax = new GCAjax();

if(!defined('UPLOADED_FILES_PRIVATE_PATH')) $ajax->error('undefined upload path');

$result = array();

$key = $_REQUEST['key'];
if(empty($key)) {
	$ajax->error("parameter 'key' is missing");
}
$result['key'] = $key;

$result['uploadNum'] = $_REQUEST['uploadNum'];

$fileName = basename($_FILES[$key]['name']);
if(empty($fileName)) $ajax->error('invalid filename');

$parts = explode('.', $fileName);
$fileNameWOExt = $parts;
$ext = array_pop($fileNameWOExt);
$fileNameWOExt = implode('.', $fileNameWOExt);

$newName = niceName($fileNameWOExt);
if(empty($newName)) $ajax->error('invalid filename');

$newName = getUniqueFileName(UPLOADED_FILES_PRIVATE_PATH, $newName, $ext);
$newName = $newName . '.' . $ext;

if(!is_uploaded_file($_FILES[$key]['tmp_name'])) {
	$ajax->error('error uploading file');
}

$filePath = UPLOADED_FILES_PRIVATE_PATH . $newName;

if(!move_uploaded_file($_FILES[$key]['tmp_name'], $filePath)) {
	$ajax->error("could not move file to {$filePath}");
}

$result['name'] = $newName;
file_put_contents(DEBUG_DIR.'upload.txt', var_export($_REQUEST, true)."\n".var_export($_FILES, true)."\n".$newName."\n\n");
$ajax->success($result);

$numRec = 0;
function getUniqueFileName($path, $fileName, $ext) {
    global $numRec;
    if($numRec > 10) throw new Exception('Che cazzo sta succedendo???? '.$path. ' - ' . $fileName);
    $numRec++;
    if(!file_exists($path.$fileName.'.'.$ext)) return $fileName;
    
    $parts = explode('-', $fileName);
    $fileNameWONum = $parts;
    $num = array_pop($fileNameWONum);
    if(ctype_digit($num)) {
        $num++;
    } else {
        array_push($fileNameWONum, $num);
        $num = 1;
    }
    array_push($fileNameWONum, $num);
    $fileName = implode('-', $fileNameWONum);
    
    return getUniqueFileName($path, $fileName, $ext);
}


