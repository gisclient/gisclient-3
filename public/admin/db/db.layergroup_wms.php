<?php
require_once ADMIN_PATH.'lib/ParseXml.class.php';
$save=new saveData($_POST);
$p=$save->performAction($p);
$idcat=$_POST["dati"]["catalog_id"];
$lGroupName=$_POST["dati"]["wms"];
if(!$save->hasErrors && $save->action=="salva"){
	$tmp=$p->parametri;
	$layergroup_id=array_pop($tmp);
	$sql="select catalog_path,connection_type from ".DB_SCHEMA.".catalog where catalog_id=$idcat";
	if (!$save->db->sql_query($sql))
		print_debug($sql,null,"WMS");
	$ris=$save->db->sql_fetchrow();
	$xml = new ParseXml();
	// Fare il controllo se è un URL ben formata.
	$pageurl=$ris["catalog_path"]."?SERVICE=WMS&REQUEST=GetCapabilities";
	if($_SESSION["AUTHOR"]["WMS"]){
		$data=$_SESSION["AUTHOR"]["WMS"];
	}
	elseif($xml->LoadRemote($pageurl, 3)){
		$data = $xml->ToArray();
	}
	else{
		$error=1;
	}
	if(!$error){
		$serviceVersion=$data["@attributes"]["version"];
		$serviceName=$data["Service"]["Name"];
		$serviceTitle=$data["Service"]["Title"];
		$serviceAbstract=$data["Service"]["Abstract"];
		$formats=(is_array($data["Capability"]["Request"]["GetMap"]["Format"]))?($data["Capability"]["Request"]["GetMap"]["Format"]):(Array($data["Capability"]["Request"]["GetMap"]["Format"]));			// Formati Disponibili
		$formatsList=implode($formats,",");
		$format=$formats[0];
		$theme=$data["Capability"]["Layer"];
		$lThemeTitle=$theme["Title"];
		$lThemeSRS=(is_array($theme["SRS"]))?($theme["SRS"]):(Array($theme["SRS"]));
		$epsgList=implode($lThemeSRS," ");
		for($i=0;$i<count($theme["Layer"]);$i++){
			$lGrp=$theme["Layer"][$i];
			if ($lGrp["Name"]==$lGroupName){
				$lGroup[$i]["name"]=$lGrp["Name"];
				$lGroup[$i]["title"]=$lGrp["Title"];
				$lGroup[$i]["abstract"]=$lGrp["Abstract"];
				$epsg=($lGrp["SRS"] && is_array($lGrp["SRS"]))?($lGrp["SRS"][0]):(($lGrp["SRS"])?($lGrp["SRS"]):($lThemeSRS[0]));
				$lGroup[$i]["srs"]=$epsg;
				$epsg=(preg_match("|(.+):([0-9]+)|",$epsg,$out))?($out[2]):(-1);
				$lGroup[$i]["minscale"]=($lGrp["ScaleHint"]["@attributes"]["min"][0])?($lGrp["ScaleHint"]["@attributes"]["min"][0]):("null");
				$lGroup[$i]["maxscale"]=($lGrp["ScaleHint"]["@attributes"]["max"][0])?($lGrp["ScaleHint"]["@attributes"]["max"][0]):("null");
				if($lGrp["Style"]){
					if($lGrp["Style"]["Name"] && $lGrp["Style"]["Title"])
						$lGroup[$i]["layer"][0]=Array("name"=>$lGrp["Style"]["Name"],"title"=>$lGrp["Style"]["Title"]);
					else{
						for($j=0;$j<count($lGrp["Style"]);$j++){
							$lGroup[$i]["layer"][$j]=Array("name"=>$lGrp["Style"][$j]["Name"],"title"=>$lGrp["Style"][$j]["Title"]);
						}
					}
				}
				else{
					$lGroup[$i]["layer"][0]=Array("name"=>$lGrp["Name"],"title"=>$lGrp["Title"]);
				}
				for($j=0;$j<count($lGroup[$i]["layer"]);$j++){
					$layer=$lGroup[$i]["layer"][$j];
					$metaData="\"wms_srs\" \"$epsgList\"\\n\"wms_name\" \"$lGroupName\"\\n\"wms_server_version\" \"$serviceVersion\"\\n\"wms_format\" \"$format\"\\n\"wms_style\" \"$layer[name]\"\n\"wms_formatlist\" \"$formatsList\"";
					
					$sql="INSERT INTO ".DB_SCHEMA.".layer(layer_id,layergroup_id,layer_name,layertype_id,catalog_id,data,data_srid,minscale,maxscale,metadata) VALUES((SELECT ".DB_SCHEMA.".new_pkey('layer','layer_id',1)),$layergroup_id,'$layer[name]',4,$idcat,'no',$epsg,".$lGroup[$i]["minscale"].",".$lGroup[$i]["maxscale"].",'$metaData');";
					if(!$save->db->sql_query($sql))
						print_debug($sql,null,"WMS");
				}
			}
		}
	}
	$_SESSION["AUTHOR"]["WMS"]=null;
	
}
?>