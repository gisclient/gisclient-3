<?php
header("Content-Type: text/html; Charset=ISO-8859-15");
header("Cache-Control: no-cache, must-revalidate, private, pre-check=0, post-check=0, max-age=0");
header("Expires: " . gmdate('D, d M Y H:i:s', time()) . " GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Pragma: no-cache");

include "../../config/config.php";
include ADMIN_PATH."lib/tabella_v.class.php";
error_reporting (E_ERROR | E_PARSE);

//print_array($_REQUEST);
$file_config=$_REQUEST["config"];
switch($file_config){
	case "wms_server":
		$titolo="Parametri del Layer del Servizo WMS Server";
		break;
	case "wms_client":
		$titolo="Parametri del Layer del Servizo WMS Client";
		break;
	case "map_wms_server":
		$titolo="Parametri di Mappa del Servizio WMS";
		break;
	case "wfs_server":
		$titolo="Parametri del Layer del Servizo WFS Server";
		break;
	case "wfs_client":
		$titolo="Parametri del Layer del Servizo WFS Client";
		break;
	case "map_wfs_server":
		$titolo="Parametri di Mappa del Servizio WFS";
		break;
}
$data=(isset($_REQUEST["wmsInfo"]))?$_REQUEST["wmsInfo"]:array();
$key=array_keys($data);
$tb=new Tabella_v($file_config.".tab","standard");
$dataStr=explode(",",$tb->elenco_campi);
$hidden=array_diff($key,$dataStr);

$tb->set_titolo($titolo,"");
$tb->set_dati($data);





?>
<html>
<head>
	<title>Author</title>
	<LINK media="screen" href="../css/styles.css" type="text/css" rel="stylesheet">
	<SCRIPT language="javascript" src="js/Author.js" type="text/javascript"></SCRIPT>

</head>
<body>
<form id='frm_data'>
<?php
$tb->get_titolo();
$tb->edita();
//Scrittura dei campi nascosti
if(count($hidden))
	foreach($hidden as $val){
		echo "<input type=\"hidden\" name=\"dati[$val]\" id=\"$val\" value=\"".$data[$val]."\">";
	}

?>
</form>
</body>
</html>