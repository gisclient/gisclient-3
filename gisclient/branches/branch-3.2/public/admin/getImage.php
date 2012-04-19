<?php
require_once ("../../config/config.php");
$dbSchema=DB_SCHEMA;
$db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
if(!$db->db_connect_id) die( "Impossibile connettersi al database ". DB_NAME);
$table=$_REQUEST["table"];
$fontFilter=(isset($_REQUEST["font"]) && $_REQUEST["font"])?(" AND font_name='".$_REQUEST["font"]."'"):("");
if($table=="class")
	$filter = "class_id=".$_REQUEST["id"];
else
	$filter = $table."_name='".$_REQUEST["id"]."'$fontFilter";

$sql="select ".$table."_image as image from $dbSchema.$table where $filter";

print_debug($sql,null,'getImage');
$db->sql_query($sql);
$img=$db->sql_fetchfield("image");
header('Content-type:image/png');
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header ("Pragma: no-cache"); // HTTP/1.0
if ($img) {
	echo pg_unescape_bytea($img);
} else {
	readfile(ROOT_PATH.'public/images/warning.png');
}
?>