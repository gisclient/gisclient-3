<?php

class OwsHandler {

	/**
	 * 
	 * 
	 * @param string $url
	 * @param string $postFields (xml)
	 * @throws RuntimeException
	 */
	function post($url, $postFields) {

		file_put_contents('/tmp/postrequest.xml', $postFields);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('file' => '@/tmp/postrequest.xml'));
		$return = curl_exec($ch);
		if ($return === false) {
			throw new RuntimeException("Call to $url return with error:" . var_export(curl_error($ch), true));
		}
		if (200 != ($httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE))) {
			throw new RuntimeException("Call to $url return HTTP code $httpCode");
		}
		curl_close($ch);
	}
	
	

static function currentPageURL() {
	$pageURL = 'http';
	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') $pageURL .= 's';
	$pageURL .= '://';
	if($_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["PHP_SELF"];
	else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"];
	return $pageURL;
}

static function applyGCFilter(&$oLayer,$layerFilter){
	if($oLayer->getFilterString()) $layerFilter = str_replace("\"","",$oLayer->getFilterString())." AND " .$layerFilter;
	$oLayer->setFilter($layerFilter);
}

static function checkLayer($project, $service, $layerName){
	$check = false;
	if(!empty($_SESSION['GISCLIENT_USER_LAYER']) && !empty($_SESSION['GISCLIENT_USER_LAYER'][$project][$layerName])) {
		$layerAuth = $_SESSION['GISCLIENT_USER_LAYER'][$project][$layerName];
		// There is a misaligment in $layerAuth. From code it seems, that it is based on SERVICE
		if(strtoupper($service) == 'WMS' && ($layerAuth == 1 || $layerAuth['WMS']==1)) {
			$check = true;
		} else if (strtoupper($service) == 'WFS' && ($layerAuth == 1 || $layerAuth['WFS']==1 )) {
			$check = true;
		}
	}
	return $check;
}

static function getRequestedLayers($oMap, $objRequest, $layersParameter) {
	
	$layersArray = array();
	$layerNames = explode(',', $layersParameter);
	// ciclo i layers e costruisco un array di singoli layers
	foreach($layerNames as $name) {
		$layerIndexes = $oMap->getLayersIndexByGroup($name);
        if(!$layerIndexes && count($layerNames) == 1 && $name == $objRequest->getvaluebyname('map')) {
            $layerIndexes = array_keys($oMap->getAllLayerNames());
        }
		// è un layergroup
		if(is_array($layerIndexes) && count($layerIndexes) > 0) {
			foreach($layerIndexes as $index) {
				array_push($layersArray, $oMap->getLayer($index));
			}
		// è un singolo layer
		} else {
			array_push($layersArray, $oMap->getLayerByName($name));
		}
	}
	return $layersArray;
}
	
	/**
	 * Remove the srsName Attribute from the filter, when the SRID is in the
	 * list of inverted axis SRIDs. This is a temporarily hack, since some
	 * operations depend on axis order, while others don't
	 * 
	 * @param type $filter
	 * @param array $invertedAxisOrderSrids
	 * @return string
	 */
	function pruneSrsFromFilter($filter, array $invertedAxisOrderSrids) {
		$filterDoc = new DOMDocument();
		$filterDoc->loadXML($filter);
		$xpath = new DOMXPath($filterDoc);
		// find all elements with an attribute srsName
		$bboxes = $xpath->query("//*[@srsName]");
		$filterHasChanged = false;
		foreach($bboxes as $bbox) {
			$bbox->nodeName;
			$srsNameAttrib = $bbox->getAttributeNode('srsName');
			$sridParts = explode(':', $srsNameAttrib->value);
			$srid = (int)$sridParts[count($sridParts) - 1];
			// if srs has inverted axis order
			if (in_array($srid, $invertedAxisOrderSrids)) {
				$srsNameAttrib->value = '';
				$filterHasChanged = true;
			}
		}
		if ($filterHasChanged) {
			$filter = $filterDoc->saveXML();
		}
		return $filter;
	}
}
