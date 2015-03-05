<?php

//print("<pre>");
//print_r($_REQUEST);

require_once ("../../../config/config.php");
require_once (ADMIN_PATH."lib/functions.php");
require_once (ADMIN_PATH."lib/gcSymbol.class.php");
/*
$action=$_REQUEST["action"];
$table=$_REQUEST["table"];
$category=$_REQUEST["category"];
$font=$_REQUEST["font"];
$name=$_REQUEST["name"];
$class=$_REQUEST["class"];
$layer=$_REQUEST["layer"];
$mapset=$_REQUEST["mapset"];
$layergroup=$_REQUEST["layergroup"];
$theme=$_REQUEST["theme"];
$project=$_REQUEST["project"];
*/
extract($_REQUEST);

$oSymbol=new Symbol($table);
$filter = array();
if($table=="symbol"){
	if(isset($category)) $filter[]="symbolcategory_name='$category'";
	if(isset($name)) $filter[]="lower(symbol_name)=lower('$name')";
	$oSymbol->filter=implode (" AND ",$filter);
}
elseif($table=="symbol_ttf"){
	if(isset($category)) $filter[]="symbolcategory_name='$category'";
	if(isset($font)) $filter[]="font_name='$font'";
	if(isset($name)) $filter[]="lower(symbol_ttf_name)=lower('$name')";
	$oSymbol->filter=implode (" AND ",$filter);
}
elseif($table=="class"){
	if(isset($class))$filter[]="class.class_id=$class";
	if(isset($layer))$filter[]="layer.layer_name='$layer'";
	if(isset($layergroup))$filter[]="layergroup.layergroup_name='$layergroup'";
	if(isset($theme))$filter[]="theme.theme_name='$theme'";		
	if(isset($project))$filter[]="project.project_name='$project'";
	if(isset($filter)) $oSymbol->filter=implode (" AND ",$filter);
}
$oSymbol->createIcon();



$smbList = $oSymbol->getList();

//print_array($smbList);
$htmlTable = "<table border=\"1\" cellspacing=\"1\" cellpadding=\"2\">\n";
$htmlTable .= "<tr><th>".implode("</th><th>",$smbList["headers"])."</th></tr>\n";
for($i=0;$i<count($smbList["values"]);$i++){
	$row="<td><img src=\"../getImage.php?".$smbList["values"][$i][0]."\" alt=\"ID ".$smbList["values"][$i][0]."\" /></td>";
	for($j=1;$j<count($smbList["values"][$i]);$j++)
		$row.="<td>".$smbList["values"][$i][$j]."</td>";
	$htmlTable .= "<tr>$row</tr>\n";
}
$htmlTable .= "</table>\n";
echo $htmlTable;

?>