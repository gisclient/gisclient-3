<?php
require_once "../../../config/config.php";
$db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
if(!$db->db_connect_id) die("<p>Impossibile connettersi al database!</p>");
$sql="SELECT layer_id,data,data_geom,split_part(catalog_path,'/',2) as data_schema FROM ".DB_SCHEMA.".layer INNER JOIN ".DB_SCHEMA.".catalog USING (catalog_id) where connection_type=6;";
print "<p>$sql</p>";
if($db->sql_query($sql)){
	$ris=$db->sql_fetchrowset();
	if (count($ris)){
		foreach($ris as $val){
			extract($val);
			$sql="SELECT st_xmin(st_extent($data_geom))||' '||st_ymin(st_extent($data_geom))||' '||st_xmax(st_extent($data_geom))||' '||st_ymax(st_extent($data_geom)) as extent FROM $data_schema.$data";
			$sql="UPDATE ".DB_SCHEMA.".layer SET data_extent = ($sql) WHERE (select area(st_extent($data_geom)) from $data_schema.$data)>0 AND layer_id = $layer_id;";
			print "<p>$sql</p>";
			$db->sql_query($sql);
		}
	}
}
?>