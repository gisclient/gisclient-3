<?php

	$buffer = ms_iogetstdoutbufferstring();
	
	include_once "../admin/lib/xml2array.php";	
	//$array = xml2array(file_get_contents('php://input', 'r'));	
	$array = xml2array($buffer);
	print('<pre>');
	print_r($array);exit;
	
	echo $buffer;
	ms_ioresethandlers();
	
	
	
	/*	
	//TODO RESTITUZIONE DEI SOLI RISULTATI CON O SENZA GEOMETRIE IN JSON
	if(strtolower($_REQUEST["SERVICE"])=="wfs" && strtolower($_REQUEST["REQUEST"])=="getfeatureXXX"){

		$foo = new XMLReader();
		$foo->xml($buffer);
		$in_featureMember = false;
		$msObjectInit = false;
		$msAttributeInit = false;
		$msObjectName = NULL;
		$msAttributeName = NULL;
		$msAttributeText = FALSE;
		$msObject = NULL;
		$msFid = NULL;
	
	include_once "../admin/lib/xml2array.php";	
	//$array = xml2array(file_get_contents('php://input', 'r'));	
	$array = xml2array($sld_body);
	print('<pre>');
	print_r($array);exit;
		
		
		while ($foo->read()) {
			switch ($foo->nodeType) {
				case XMLREADER::ELEMENT:
					if ($foo->prefix == 'gml' && $foo->localName == 'featureMember') {
						$in_featureMember = true;
						$msAttributeText = FALSE;
					} else if ($foo->prefix == 'ms' && $in_featureMember && $msObjectInit == false) {
						$msObjectInit = true;
						$msObjectName = $foo->localName;
						$msFid = $foo->getAttribute('gc_objid');
						if (!!empty($msObject[$msObjectName]))
							$msObject[$msObjectName] = array();
						
						$msObject[$msObjectName][$msFid] = array();
						$msAttributeText = FALSE;
					} else if ($foo->prefix == 'ms' && $in_featureMember && $msObjectInit) {
						$msAttributeInit = true;
						$msAttributeName = $foo->localName;
						if ($msAttributeName != "bordo_gb")
							$msObject[$msObjectName][$msFid][$msAttributeName] = '';
						$msAttributeText = TRUE;
					} else {
						$msAttributeText = FALSE;
					}
					//echo $foo->prefix . ':' . $foo->localName . "<br>";
					//'<gml:featureMember>'
					break;
				case XMLREADER::END_ELEMENT:
					if ($foo->prefix == 'gml' && $foo->localName == 'featureMember') {
						$in_featureMember = false;
						$msObjectInit = false;
						$msObjectName = NULL;
						$msAttributeName = NULL;
						$msFid = NULL;
					} else if ($foo->prefix == 'ms' && $in_featureMember && $msAttributeInit == true) {
						$msAttributeName = NULL;
						$msAttributeInit == false;
					} else if ($foo->prefix == 'ms' && $in_featureMember) {
						$msObjectInit = false;
						$msObjectName = NULL;
					}
					break;
				case XMLREADER::TEXT:
					if ($in_featureMember && $msAttributeInit == true && $msAttributeName != 'the_geom' && $msAttributeText) {
						$msObject[$msObjectName][$msFid][$msAttributeName] = $foo->value;
					}
					break;
			}
		}

		echo json_encode($msObject);
		die();
	
	}

*/
	
	
	

?>