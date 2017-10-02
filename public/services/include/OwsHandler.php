<?php

class OwsHandler {

    /**
     * Send a POST request
     * 
     * @param string $url
     * @param string $postFields (xml)
     * @throws RuntimeException
     */
    function post($url, $postFields) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/xml",
        ));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
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
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
            $pageURL .= 's';
        $pageURL .= '://';
        if ($_SERVER["SERVER_PORT"] != "80")
            $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["PHP_SELF"];
        else
            $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["PHP_SELF"];
        return $pageURL;
    }

    static function applyGCFilter(&$oLayer, $layerFilter) {
        if ($oLayer->getFilterString())
            $layerFilter = str_replace("\"", "", $oLayer->getFilterString()) . " AND " . $layerFilter;
        $oLayer->setFilter($layerFilter);
    }

    static function checkLayer($project, $service, $layerName) {
        $check = false;
        if (!empty($_SESSION['GISCLIENT_USER_LAYER']) && !empty($_SESSION['GISCLIENT_USER_LAYER'][$project][$layerName])) {
            $layerAuth = $_SESSION['GISCLIENT_USER_LAYER'][$project][$layerName];
            // There is a misaligment in $layerAuth. From code it seems, that it is based on SERVICE
            if (strtoupper($service) == 'WMS' && ($layerAuth == 1 || $layerAuth['WMS'] == 1)) {
                $check = true;
            } else if (strtoupper($service) == 'WFS' && ($layerAuth == 1 || $layerAuth['WFS'] == 1 )) {
                $check = true;
            }
        }
        return $check;
    }

    static function getRequestedLayers($oMap, $objRequest, $layersParameter) {

        $layersArray = array();

        if (empty($layersParameter)) {
            return $layersArray;
        }
        
        $wfsNamespace = $oMap->getMetaData('wfs_namespace_prefix');
        $layerNames = explode(',', $layersParameter);
        // ciclo i layers e costruisco un array di singoli layers
        foreach ($layerNames as $name) {
            $layerIndexes = $oMap->getLayersIndexByGroup($name);
            if (!$layerIndexes && count($layerNames) == 1 && $name == $objRequest->getvaluebyname('map')) {
                $layerIndexes = array_keys($oMap->getAllLayerNames());
            }
            // è un layergroup
            if (is_array($layerIndexes) && count($layerIndexes) > 0) {
                foreach ($layerIndexes as $index) {
                    array_push($layersArray, $oMap->getLayer($index));
                }
                // è un singolo layer
            } else {
                // Remove namespace from requested layer name [QGIS 2.18]
                $wfsNamespace = $oMap->getMetaData('wfs_namespace_prefix');
                if (!empty($wfsNamespace)) {
                    $name = str_replace("{$wfsNamespace}:", '', $name);
                }
                array_push($layersArray, $oMap->getLayerByName($name));
            }
        }
        return $layersArray;
    }

    static function getRequestedLayersById($oMap, $objRequest, $layersIdList) {
        
        $layersArray = array();
        
        if (empty($layersIdList)) {
            return $layersArray;
        }
        $layerIds = explode(',', $layersIdList);
        // ciclo i layers e costruisco un array di singoli layers
        foreach ($layerIds as $index) {
            try {
                $layer = @$oMap->getLayer($index);
            } catch (Exception $e) {
                $layer = false;
            }

            if ($layer !== false) {
                array_push($layersArray, $layer);
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
        $filterHasChanged = false;
        if (!empty($filter)) {
            $filterDoc = new DOMDocument();
            $filterDoc->loadXML($filter);
            $xpath = new DOMXPath($filterDoc);
            // find all elements with an attribute srsName
            $bboxes = $xpath->query("//*[@srsName]");
            foreach ($bboxes as $bbox) {
                $bbox->nodeName;
                $srsNameAttrib = $bbox->getAttributeNode('srsName');
                $sridParts = explode(':', $srsNameAttrib->value);
                $srid = (int) $sridParts[count($sridParts) - 1];
                // if srs has inverted axis order
                if (in_array($srid, $invertedAxisOrderSrids)) {
                    $srsNameAttrib->value = '';
                    $filterHasChanged = true;
                }
            }
        }
        if ($filterHasChanged) {
            $filter = $filterDoc->saveXML();
        }
        return $filter;
    }

    function getHttp($url) {
        $ch = curl_init($_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $content = curl_exec($ch);
        if ($content === false) {
            throw new RuntimeException("Call to $url return with error:" . var_export(curl_error($ch), true));
        }
        if (200 != ($httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE))) {
            throw new RuntimeException("Call to $url return HTTP code $httpCode and body " . $content);
        }
        curl_close($ch);
        return $content;
    }

    /**
     * Get the KML file from MapServer.
     * Download the symbol images, bundle them into the KMZ file and adapt
     * the href nodes in the KML file.
     */
    function assembleKmz($kmlString) {
        $kmlDoc = new DOMDocument();
        $kmlDoc->loadXML($kmlString);

        $kmzFilename = tempnam(sys_get_temp_dir(), 'kmz_');
        $kmzArchive = new ZipArchive();
        if ($kmzArchive->open($kmzFilename, ZipArchive::CREATE) !== TRUE) {
            throw new Exception("cannot open <$kmzFilename>");
        }
        $hrefNodes = $kmlDoc->getElementsByTagName('href');
        // find all href elements
        if (false && $hrefNodes->length == 0) {
            // nothing to substitute?
            $kmzArchive->addFromString('exportdata.kml', $kmlString);
            $kmzArchive->close();
            if (false === ($kmzContent = file_get_contents($kmzFilename))) {
                throw new Exception("Could not read $kmzFilename");
            }
            if (false === unlink($kmzFilename)) {
                throw new Exception("Could not unlink($kmzFilename)");
            }
            return $kmzContent;
        }
        foreach ($hrefNodes as $hrefNode) {
            if (false === ($iconString = file_get_contents($hrefNode->textContent))) {
                throw new Exception("Could not get icon from " . $hrefNode->textContent);
            }
            $urlParts = parse_url($hrefNode->textContent);
            $localname = basename($urlParts['path']);
            $kmzArchive->addFromString($localname, $iconString);
            $hrefNode->textContent = $localname;
        }
        $kmzArchive->addFromString('exportdata.kml', $kmlDoc->saveXML());
        $kmzArchive->close();
        if (false === ($kmzContent = file_get_contents($kmzFilename))) {
            throw new Exception("Could not read $kmzFilename");
        }
        if (false === unlink($kmzFilename)) {
            throw new Exception("Could not unlink($kmzFilename)");
        }
        return $kmzContent;
    }

	public  static function getSldContent($sldUrl) {
		$ch = curl_init($sldUrl);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch ,CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
		$sldContent = curl_exec($ch);
		
		if($sldContent === false) {
			throw new RuntimeException("Call to {$sldUrl} return with error:". var_export(curl_error($ch), true));
		}
		if (200 != ($httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE))) {
			throw new RuntimeException("Call to {$sldUrl} return HTTP code $httpCode and body ".$sldContent);
		}
		curl_close($ch);

		return $sldContent;
	}
    
    /**
     * Return the SLD url from request (using php $_REQUEST to prevent applying sld to mapserver and performance issue)
     * Return null if no parameter found
     */
    public static function getParameterFromRequest(array $keys) {
        $sldUrl = null;
        foreach($keys as $param) {
            if (!empty($_REQUEST[$param])) {
                $sldUrl = $_REQUEST[$param];
                break;
            }
        }
        return $sldUrl;
    }
    
    /**
     * Remove the layers not present in the request
     * Speed improvement (eg SLD)
     */
    public static function removeLayersNotInRequest($oMap, $objRequest, $requestLayers) {
        $layersArray = self::getRequestedLayers($oMap, $objRequest, $requestLayers);
        $layersFromRequest = array();
        foreach($layersArray as $l) {
            $layersFromRequest[] = $l->name;
        }
        for ($i = $oMap->numlayers - 1; $i >= 0; $i--) {
            $l = $oMap->getLayer($i);
            if (!in_array($l->name, $layersFromRequest)) {
                $oMap->removeLayer($i);
            }
        }
    }
    
    /**
     * Apply sld to the current request (GetMap, GetLegendGraphic, form request parameter SLD, SDL_BODY or author) 
     */
    public static function applyWmsSld(\PDO $db, \GCi18n $i18n, $oMap, $objRequest) {
        $requestService = strtolower($objRequest->getValueByName('service'));
        $requestRequest = strtolower($objRequest->getValueByName('request'));
        $requestSldUrl = self::getParameterFromRequest(array('SLD', 'sld'));
        $requestSldBody = self::getParameterFromRequest(array('SLD_BODY', 'sld_body'));
        
        if ($requestService !== 'wms') {
            throw new \Exception("Can't apply SLD to a non WMS request ({$requestService})");
        }
        
        if ($requestRequest == 'getlegendgraphic') {
            $requestLayers = $objRequest->getValueByName('layer');
        } else if ($requestRequest == 'getmap') {
            $requestLayers = $objRequest->getValueByName('layers');
        } else {
            throw new \Exception("Can't apply SLD to WMS/{$objRequest->getValueByName('request')} request. Only GetLegendGraphic and GetMap allowed");
        }
        
        if (empty($requestLayers)) {
            $layerParamName = $requestRequest == 'getlegendgraphic' ? 'LAYER' : 'LAYERS';
            throw new \Exception("Missing {$layerParamName} parameter");
        }
        
        $layerList = explode(',', $requestLayers);
        if (empty($requestSldUrl) && empty($requestSldBody)) {
            // No SLD from request. 
            // Apply SLD from author database
            $dbSchema = DB_SCHEMA;
            $sql = "SELECT layergroup_id, sld 
                    FROM {$dbSchema}.layergroup
                    INNER JOIN {$dbSchema}.mapset_layergroup USING (layergroup_id)
                    WHERE mapset_name=:mapset_name AND layergroup_name=:layergroup_name AND sld IS NOT NULL ";
            $stmt = $db->prepare($sql);
            $layersWithSld = array();
            // Group all the different SLD and apply less times as possible (to prevent performance issue)
            foreach ($layerList as $layerGroup) {
                list($layerGroup) = explode('.', $layerGroup, 1);  // Extract layer group
                $stmt->execute(array(
                    'mapset_name'=>$objRequest->getValueByName('map'), 
                    'layergroup_name'=>$layerGroup));
                if (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
                    $sld = $i18n->translate($row['sld'], 'layergroup', $row['layergroup_id'], 'sld');
                    if (!in_array($sld, $layersWithSld)) {
                        $layersWithSld[] = $sld;
                    }
                }
            }
            if (count($layersWithSld) > 0) {
                self::removeLayersNotInRequest($oMap, $objRequest, $requestLayers);
                foreach($layersWithSld as $sld) {
                    $sldContent = self::getSldContent($sld);
                    $oMap->applySLD($sldContent);
                }
            }
        } else if (!empty($requestSldUrl)) {
            self::removeLayersNotInRequest($oMap, $objRequest, $requestLayers);
            $sldContent = self::getSldContent($requestSldUrl);
            $oMap->applySLD($sldContent); // for getlegendgraphic
        }
    }
    
}
