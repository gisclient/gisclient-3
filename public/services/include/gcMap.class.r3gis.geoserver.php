<?php
/******************************************************************************
*
* Purpose: Inizializzazione dei parametri per la creazione della mappa basato su geoserver
*    
******************************************************************************/

class gcMapGeoServerUtils {
	
	static public function getBaseUrl()
    {
        $urlPart = parse_url(GEOSERVER_URL);
        return "{$urlPart['scheme']}://{$urlPart['host']}:{$urlPart['port']}{$urlPart['path']}/";
    }
    
    static public function getWmsBaseUrl($projectName, $mapsetName)
    {
        $basePath = self::getBaseUrl();
        $path = $basePath . urlencode($projectName) . '-' . urlencode($mapsetName) . '/ows?';
        return $path;
    }
    
    static public function fixLayerData($layerData) {
        $result = $layerData;
  
        if (!empty($result['layers']) && is_array($result['layers'])) {
            foreach($result['layers'] as $key => $name) {
                $result['layers'][$key] = "{$layerData['project']}-{$layerData['map']}:{$name}";
            }
        }
        return $result;
    }
}
