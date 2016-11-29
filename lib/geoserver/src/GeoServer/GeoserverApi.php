<?php

namespace GisClient\GeoServer;

class GeoserverApi
{
    protected $url;
    protected $user;
    protected $pass;
    protected $options;
    protected $workspace;

    /**
     * Constructor 
     * @param string $url   Geoserver URL (username and password allowed here)
     * @param string $user   Geoserver administrator user
     * @param string $pass   Geoserver administrator password
     */
    public function __construct($url, $user, $pass, array $options)
    {
        $defaultUrlInfo = array('scheme' => 'http', 'host' => '127.0.0.1', 'path' => '/');
        $defaultOptions = array('timeout' => null, 'rest_path' => 'rest/');  // Proxy?
        // Parse URL
        $urlInfo = parse_url($url);
        $urlInfo = array_merge($defaultUrlInfo, $urlInfo);
        if (substr($urlInfo['path'], -1) !== '/') {
            $urlInfo['path'] = "{$urlInfo['path']}/";
        }
        $urlPart = "{$urlInfo['scheme']}://{$urlInfo['host']}".(!empty($urlInfo['port']) ? ":{$urlInfo['port']}" : '')."{$urlInfo['path']}";
        $this->url = $urlPart;
        $this->user = !empty($user['user']) && empty($user) ? $user['user'] : $user;
        $this->pass = !empty($user['pass']) && empty($pass) ? $user['pass'] : $pass;
        $this->options = array_merge($defaultOptions, $options);
    }

    /**
     * Execute a Geoserver API call. HTTP Exception raised on error
     * @param type $method
     * @param type $url
     * @param type $data
     * @param type $contentType
     * @return string
     */
    protected function runApi($apiPath, $method = 'GET', $data = null, $contentType = 'application/json')
    {
        $url = "{$this->url}{$this->options['rest_path']}{$apiPath}";
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        if (!empty($this->user)) {
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->user}:{$this->pass}");
        }
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        } else if ($method == 'DELETE' || $method == 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $headers = array();
        $headers[] = "Accept: {$contentType}";
        if (!empty($data)) {
            $headers[] = "Content-Type: {$contentType}";
            $headers[] = "Content-Length: ".strlen($data);
        }
        if (count($headers) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, $this->options['timeout']);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // Don't check certificate for now

        $result = curl_exec($ch);
        $info = curl_getinfo($ch);

        // @see http://docs.geoserver.org/2.7.2/user/rest/api/details.html
        switch ($info['http_code']) {
            case 200: // OK
                break;
            case 0:   // Invalid http response (Conection faild or server critical error)
                throw new \Exception("HTTP error: Can't connect to server or fatal server error", $info['http_code']);
            case 201: // Created
                break;
            case 401:
                throw new \Exception("HTTP error {$info['http_code']}: Access denied", $info['http_code']);
            case 500:
                if (!empty($result)) {
                    throw new \Exception("HTTP error {$info['http_code']}: {$result}", $info['http_code']); // Remove ':'
                } else {
                    throw new \Exception("HTTP error {$info['http_code']}", $info['http_code']);
                }
            default:
                throw new \Exception("HTTP error {$info['http_code']}: {$result}", $info['http_code']);
        }
        return $result;
    }

    /**
     * Decode json
     * @staticvar array $jsonErrorTexts   error texts
     * @param string $jsonString          json string to decode
     * @return array
     * @throws \Exception
     */
    protected function jsonDecode($jsonString)
    {
        static $jsonErrorTexts = array(
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
            JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
            JSON_ERROR_SYNTAX => 'Syntax error',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
        );

        if (empty($jsonString)) {
            return null;
        }
        $result = json_decode($jsonString, true);
        if (($jsonErrorCode = json_last_error()) != JSON_ERROR_NONE) {
            throw new \Exception(
            "Json decode eror: ".(!empty($jsonErrorTexts[$jsonErrorCode]) ? $jsonErrorTexts[$jsonErrorCode] : ''));
        }
        return $result;
    }

    /**
     * Shortcut to perform a GET request
     * @param string $apiPath
     * @return array
     */
    public function JsonApiGet($apiPath)
    {
        $data = $this->runApi($apiPath);
        return $this->jsonDecode($data);
    }

    /**
     * Shortcut to perform a POST request
     * @param string $apiPath
     * @param array $data
     * @return array
     */
    public function JsonApiPost($apiPath, array $data)
    {
        $data = $this->runApi($apiPath, 'POST', json_encode($data));
        return $this->jsonDecode($data);
    }

    /**
     * Shortcut to perform a DELETE request
     * @param string $apiPath
     * @param array|null $data
     * @return array
     */
    public function JsonApiDelete($apiPath, $data = null)
    {
        $data = $this->runApi($apiPath, 'DELETE', json_encode($data));
        return $this->jsonDecode($data);
    }

    /**
     * Return the list of workspace
     * @return array
     */
    public function listWorkspaces()
    {
        return $this->jsonApiGet('workspaces')['workspaces']['workspace'];
    }

    /**
     * Return true if the given workspace exists
     * @param string $workspaceName
     * @return boolean
     */
    public function workspaceExists($workspaceName)
    {
        $result = false;
        foreach ($this->jsonApiGet('workspaces')['workspaces']['workspace'] as $workspace) {
            if ($workspace['name'] == $workspaceName) {
                $result = true;
                break;
            }
        }
        return $result;
    }

    /**
     * Return detailed information of the given workspace
     * @param string $workspaceName
     * @return array
     */
    public function getWorkspace($workspaceName)
    {
        $workspaceName = urlencode($workspaceName);
        return $this->jsonApiGet("workspaces/{$workspaceName}")['workspace'];
    }

    /**
     * Add a new workspace
     * @param string $workspaceName
     * @return array
     */
    public function addWorkspace($workspaceName)
    {
        return $this->JsonApiPost('workspaces', array('workspace' => array('name' => $workspaceName)));
    }

    /**
     * Delete the given workspace
     * @param string $workspaceName  the workspace name
     * @param boolean $force         if true no error given
     * @return array|null
     */
    public function delWorkspace($workspaceName, $force = false)
    {
        $workspaceName = urlencode($workspaceName);
        $data = null;
        try {
            $data = $this->JsonApiDelete("workspaces/{$workspaceName}.json");
        } catch (\Exception $e) {
            if (!$force || $e->getCode() != 404) {
                throw $e;
            }
        }
        return $data;
    }

    /**
     * Return the datastore list
     * @param string $workspaceName
     * @return array
     */
    public function listDatastores($workspaceName)
    {
        $workspaceName = urlencode($workspaceName);
        $list = $this->jsonApiGet("workspaces/{$workspaceName}/datastores.json");
        if (empty($list['dataStores']['dataStore'])) {
            return array();
        }
        return $list['dataStores']['dataStore'];
    }

    /**
     * Get detailed information from datastore
     *
     * @param string $workspaceName
     * @param string $datastoreName
     * @return array
     */
    public function getDatastore($workspaceName, $datastoreName)
    {
        $workspaceName = urlencode($workspaceName);
        $datastoreName = urlencode($datastoreName);
        $data = $this->jsonApiGet("workspaces/{$workspaceName}/datastores/{$datastoreName}")['dataStore'];

        // Normalize data
        $params = array();
        foreach ($data['connectionParameters']['entry'] as $param) {
            $params[$param['@key']] = $param['$'];
        }
        $data['connectionParameters'] = $params;
        return $data;
    }

    /**
     * Add a new datastore
     *
     * @param string $workspaceName
     * @param string $datastoreName
     * @param array $options
     * @return array
     */
    public function addDatastore($workspaceName, $datastoreName, array $options)
    {
        $defOptions = array(
            'name' => $datastoreName,
            'type' => 'PostGIS',
            'enabled' => true,
            'description' => empty($options['description']) ? null : $options['description'],
            'connectionParameters' => array(
                'dbtype' => 'postgis',
                'host' => '127.0.0.1',
                'port' => 5432,
                'database' => null,
                'user' => null,
                'passwd' => null,
                'schema' => 'pulic',
                'namespace' => "http://{$workspaceName}",
                'Connection timeout' => 30,
                'validate connections' => true,
                'encode functions' => false,
                'max connections' => 10,
                'Loose bbox' => true,
                'Expose primary keys' => false,
                'fetch size' => 1000,
                'Max open prepared statements' => 50,
                'preparedStatements' => true,
                'Estimated extends' => true,
                'min connection' => 1
            )
        );
        $opt = $defOptions;
        $opt['connectionParameters'] = array_merge($defOptions['connectionParameters'], $options);
        $workspaceNameEnc = urlencode($workspaceName);
        return $this->JsonApiPost("workspaces/{$workspaceNameEnc}/datastores", array('dataStore' => $opt));
    }

    /**
     * Delete the given datastore
     *
     * @param string $workspaceName
     * @param string $datastoreName
     * @param string $force
     * @return array
     * @throws \Exception
     */
    public function delDatastore($workspaceName, $datastoreName, $force = false)
    {
        $workspaceNameEnc = urlencode($workspaceName);
        $datastoreNameEnc = urlencode($datastoreName);
        $data = null;
        try {
            $data = $this->JsonApiDelete("workspaces/{$workspaceNameEnc}/datastores/{$datastoreNameEnc}.json");
        } catch (\Exception $e) {
            if (!$force || $e->getCode() != 404) {
                throw $e;
            }
        }
        return $data;
    }

    /**
     * Return the styles of the given workspace
     * @param string $workspaceName
     * @return array
     */
    public function listStyles($workspaceName = null)
    {
        $result = array();
        if (empty($workspaceName)) {
            $apiResult = $this->jsonApiGet("styles");
        } else {
            $apiResult = $this->jsonApiGet("workspaces/{$workspaceName}/styles");
        }
        if (!empty($apiResult['styles']['style'])) {
            $result = $apiResult['styles']['style'];
        }
        return $result;
    }

    /**
     * Get detailed style information
     *
     * @param string $styleName
     * @return type
     */
    public function getStyle($styleName)
    {
        $styleName = urlencode($styleName);
        $data = $this->jsonApiGet("styles/{$styleName}")['style'];
        if (!empty($data['filename'])) {
            $sldData = $this->runApi("styles/{$data['filename']}", 'GET', null, 'application/vnd.ogc.sld+xml');  // aggiungere application/vnd.ogc.se+xml
            $data['data'] = $sldData;
        }
        return $data;
    }

    /**
     * Add a new style
     *
     * @param string $styleName
     * @param string $sldText
     * @param string|null $workspace
     * @return array
     */
    public function addStyle($styleName, $sldText, $workspace = null)
    {
        $styleName = basename($styleName);
        $data = array('name' => $styleName, 'format' => 'sld', 'filename' => "{$styleName}.sld");
        if (!empty($workspace)) {
            $data['workspace'] = $workspace;
        }

        // Add style (name)
        $this->JsonApiPost("styles", array('style' => $data));

        // Add style (data)
        $styleName = urlencode($styleName);
        if (empty($workspace)) {
            $data = $this->runApi("styles/$styleName", 'PUT', $sldText, 'application/vnd.ogc.sld+xml');
        } else {
            $workspace = urlencode($workspace);
            $data = $this->runApi("workspaces/{$workspace}/styles/$styleName", 'PUT', $sldText,
                'application/vnd.ogc.sld+xml');
        }
        return $data;
    }

    /**
     * Delete a style
     *
     * @param string $styleName
     * @param string $workspace
     * @param boolean $force
     * @return array
     * @throws \Exception
     */
    public function delStyle($styleName, $workspace = null, $force = false)
    {
        $styleName = urlencode($styleName);
        $data = null;
        try {
            if (empty($workspace)) {
                $data = $this->JsonApiDelete("styles/{$styleName}.json", array('purge' => true));
            } else {
                $workspace = urlencode($workspace);
                $data = $this->JsonApiDelete("workspaces/{$workspace}/styles/$styleName.json", array('purge' => true));
            }
        } catch (\Exception $e) {
            if (!$force || $e->getCode() != 404) {
                throw $e;
            }
        }
        return $data;
    }

    /**
     * Return the layer list of the given workspace
     *
     * @param string $workspaceName
     * @param string $datastoreName
     * @return array
     */
    public function listLayers($workspaceName = null, $datastoreName = null)
    {
        if (empty($workspaceName) && empty($datastoreName)) {
            return $this->jsonApiGet("layers")['layers']['layer'];
        } else {
            $workspaceName = urlencode($workspaceName);
            $datastoreName = urlencode($datastoreName);
            $data = $this->jsonApiGet("workspaces/{$workspaceName}/datastores/{$datastoreName}/featuretypes.json");
            if (empty($data['featureTypes']['featureType'])) {
                return array();
            } else {
                return $data['featureTypes']['featureType'];
            }
        }
    }

    /**
     * Return detailed information of the given layer
     *
     * @param string $layerName
     * @return array
     */
    public function getLayer($layerName)
    {
        $layerName = urlencode($layerName);
        $data = $this->jsonApiGet("layers/{$layerName}")['layer'];
        return $data;
    }

    /**
     * Add a new layer
     *
     * @param string $workspaceName
     * @param string $datastoreName
     * @param string $tableName
     * @param string $layerName
     * @param string $title
     * @param string $description
     * @param string $sld
     * @return array
     */
    public function addLayer($workspaceName, $datastoreName, $tableName, $layerName = '', $title = '',
                             $description = '', $sld = '')
    {
        if (empty($layerName)) {
            $layerName = $tableName;
        }
        if (empty($title)) {
            $title = $tableName;
        }
        $workspaceName = urlencode($workspaceName);
        $datastoreName = urlencode($datastoreName);
        $data = array(
            'name' => $layerName,
            'nativeName' => $tableName,
            'title' => $title,
            "namespace" => array("name" => $workspaceName,
                "href" => "{$this->url}rest/namespaces/{$workspaceName}.json"    // Verificare con schema
            ),
            "enabled" => true,
            'store' => array("@class" => "dataStore",
                "name" => htmlentities($datastoreName, ENT_COMPAT)),
            'description' => $description);
        $datastoreName = urlencode($datastoreName);
        $data = $this->JsonApiPost("workspaces/{$workspaceName}/datastores/{$datastoreName}/featuretypes",
            array('featureType' => $data));
        if ($sld != null) {
            $dataIn = array(
                'layer' => array(
                    'defaultStyle' => array(
                        'name' => $sld,
                        'workspace' => "{$workspaceName}"
            )));
            $data2 = $this->runApi('layers/'.urlencode($workspaceName).':'.urlencode($layerName).'.json', 'PUT',
                json_encode($dataIn));
        }
        return $data;
    }

    /**
     * Delete the layer
     *
     * @param string $workspaceName
     * @param string $datastoreName
     * @param string $layerName
     * @param string $force
     * @return array
     * @throws \Exception
     */
    public function delLayer($workspaceName, $datastoreName, $layerName, $force = false)
    {
        $data = null;
        try {
            $workspaceName = urlencode($workspaceName);
            $datastoreName = urlencode($datastoreName);
            $layerName = urlencode($layerName);
            $data = $this->JsonApiDelete("workspaces/{$workspaceName}/datastores/$datastoreName/featuretypes/{$layerName}.json?recurse=".($force
                    ? 'true' : 'false')); //, array('recurse' => true));
        } catch (\Exception $e) {
            if (!$force || $e->getCode() != 404) {
                throw $e;
            }
        }
        return $data;
    }
}