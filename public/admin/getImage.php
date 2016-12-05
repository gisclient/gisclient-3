<?php
require_once "../../config/config.php";
require_once ADMIN_PATH."lib/gcSymbol.class.php";

$db = GCApp::getDB();

if(defined('GEOSERVER_URL') && GEOSERVER_URL){
    $dbSchema = DB_SCHEMA;
    $sql = "SELECT * FROM {$dbSchema}.symbol WHERE symbol_name=:symbol_name";
    $stmt = $db->prepare($sql);
    $stmt->execute(array('symbol_name'=>$_REQUEST['id']));
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!empty($data['symbol_def'])) {
        $fileName = ROOT_PATH . substr($data['symbol_def'], 25, -1);
        if (file_exists($fileName)) {
            echo file_get_contents($fileName);
        }
    }
    die;
}

$smb=new Symbol($_REQUEST['table']);

if($smb->table == 'class') {
    $smb->filter="class.class_id=".$db->quote($_REQUEST['id']);
} else if($smb->table == 'symbol') {
    $smb->filter="symbol.symbol_name=".$db->quote($_REQUEST['id']);
}

$img = $smb->createIcon();
header('Content-type:image/png');
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header ("Pragma: no-cache"); // HTTP/1.0
if ($img) {
	echo $img;
} else {
	readfile(ROOT_PATH.'public/images/warning.png');
}