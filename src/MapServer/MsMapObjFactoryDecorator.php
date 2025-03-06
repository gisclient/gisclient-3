<?php

namespace GisClient\MapServer;

use DOMDocument;
use DOMXPath;
use Exception;
use GisClient\Author\Utils\UrlChecker;
use GisClient\MapServer\MsMapObjFactory;
use LogicException;

/**
 * Decorator for the MsMapObjFactory
 *  to maninupalte the mapfile with custom logic from R3-GIS
 *
 * @author Daniel Degasperi <daniel.degasperi@r3-gis.com>
 */
class MsMapObjFactoryDecorator
{
    /**
     * @var MsMapObjFactory
     */
    private $factory;

    /**
     * Constructor
     *
     * @param MsMapObjFactory $factory
     */
    public function __construct($factory)
    {
        $this->factory = $factory;
    }

    public function from(\OwsrequestObj $request): \mapObj
    {
        $isUsingVsi = false;
        $oMap = $this->factory->from($request);

        $authHandler = \GCApp::getAuthenticationHandler();
        if ($authHandler->isAuthenticated()) {
            $user = $authHandler->getToken()->getUser();
            $extras = $user->getExtras();
            if (empty($extras['object_types_using_geojson'])) {
                $extras['object_types_using_geojson'] = [];
            }

            // change custom processing directive "SETROLE"
            $totLayers = $oMap->numlayers;
            for ($i = 0; $i < $totLayers; $i++) {
                $layer = $oMap->getLayer($i);
                $setRoleForThisLayer = false;

                $processingInstructions = $layer->getProcessing();
                $processingToApply = [];
                foreach ($processingInstructions as $processing) {
                    if (strpos($processing, 'SETROLE') === 0) {
                        $setRoleForThisLayer = true;
                        continue;
                    }
                    $processingToApply[] = $processing;
                }
                $processingInstructions = $processingToApply;

                $objectType = $layer->getMetaData('obj_t');
                if ($setRoleForThisLayer && in_array($objectType, $extras['object_types_using_geojson'])) {
                    $geoJsonSource = $layer->getMetaData('geojson_source');
                    if (empty($geoJsonSource)) {
                        throw new Exception('Metadata "geojson_source" is not defined');
                    }
                    UrlChecker::checkUrl($geoJsonSource, true);

                    $geoJsonLayer = $layer->getMetaData('geojson_layer');
                    if (empty($geoJsonLayer)) {
                        throw new Exception('Metadata "geojson_layer" is not defined');
                    }

                    $geoJsonSource = $this->addQueryStringParams(
                        $geoJsonSource,
                        $this->getGeoJsonQueryStringFromRequest($request, $layer->name)
                    );

                    $layer->setConnectionType(MS_OGR);
                    $layer->set('connection', sprintf('/vsicurl_streaming/%s', $geoJsonSource));
                    $layer->set('data', $geoJsonLayer);

                    $isUsingVsi = true;
                    $processingToApply = [];
                    foreach ($processingInstructions as $processing) {
                        if (false !== strpos($processing, 'NATIVE_FILTER')) {
                            continue;
                        } elseif (false !== strpos($processing, 'CLOSE_CONNECTION')) {
                            $processing = str_replace('DEFER', 'ALWAYS', $processing);
                        }

                        $processingToApply[] = $processing;
                    }
                    $processingInstructions = $processingToApply;

                    $layer->getMetaData('obj_t');
                } elseif ($setRoleForThisLayer) {
                    $layer->set(
                        'connection',
                        sprintf(
                            'user=%s password=%s dbname=%s host=%s port=5432',
                            getenv('GISCLIENT__APP__USER'),
                            getenv('GISCLIENT__APP__PASSWORD'),
                            DB_NAME,
                            DB_HOST
                        )
                    );

                    if (!empty($extras['us_db_role_name'])) {
                        $processingInstructions[] = sprintf("SETROLE=%s", $extras['us_db_role_name']);
                    }
                }

                if ($setRoleForThisLayer) {
                    $layer->clearProcessing();
                    foreach ($processingInstructions as $processing) {
                        $layer->setProcessing($processing);
                    }
                }
            }
        }

        if ($isUsingVsi) {
            $oMap->setConfigOption('VSI_CACHE', 'FALSE');

            if (isset($_COOKIE['authorization']) && false !== strpos($_COOKIE['authorization'], 'Bearer')) {
                $oMap->setConfigOption('GDAL_HTTP_HEADERS', sprintf('Authorization: %s', $_COOKIE['authorization']));
            }

            $oMap->applyconfigoptions();
        }

        return $oMap;
    }

    private function addQueryStringParams($url, $queryStringParams): string
    {
        // Parse the URL and existing query string
        $parsedUrl = parse_url($url);
        parse_str($parsedUrl['query'] ?? '', $queryStringParamsFromUrl);

        // Build the new query string
        $newQueryString = http_build_query(array_merge($queryStringParamsFromUrl, $queryStringParams));

        // Reconstruct the URL
        $newUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
        if (!empty($newQueryString)) {
            $newUrl .= '?' . $newQueryString;
        }

        return $newUrl;
    }

    private function getGeoJsonQueryStringFromRequest(\OwsrequestObj $request, string $currentLayerName): array
    {
        $queryStringParams = [];
        $filters = [];
        switch ($request->getvaluebyname('service')) {
            case 'WMS':
                $bbox = $request->getvaluebyname('bbox');
                if (!empty($bbox)) {
                    $filters['bbox'] = $bbox;
                }
                break;
            case 'WFS':
                $typeName = $request->getvaluebyname('typename');
                if ($typeName === $currentLayerName) {
                    $filter = $request->getvaluebyname('filter');
                    if (!empty($filter)) {
                        $wfsFilters = $this->parseXML($filter);
                        foreach ($wfsFilters as $wfsFilter) {
                            $filterField = key($wfsFilter);
                            $filters[$filterField] = $wfsFilter[$filterField][1];
                        }
                    }
                }

                break;
        }

        if (!empty($filters)) {
            $queryStringParams['filter'] = $filters;
        }

        return $queryStringParams;
    }

    private function parseXML($xmlString): array
    {
        // Load the XML string into a SimpleXMLElement object
        $doc = new DOMDocument();
        $doc->loadXML($xmlString);

        // Initialize an array to store key-value pairs
        $keyValuePairs = [];

        // Define the XPath to search for filters
        $xpath = new DOMXPath($doc);

        // Register the namespace with prefix 'ogc'
        $xpath->registerNamespace('ogc', 'http://www.opengis.net/ogc');

        $conditionNodes = $xpath->query('//ogc:PropertyIsEqualTo');
        // TODO: support other kind of searches?
        //$conditionNodes = $xpath->query('//PropertyIsEqualTo | //PropertyIsNotEqualTo');

        // Iterate over the found conditions
        foreach ($conditionNodes as $conditionNode) {
            // Get the PropertyName and Literal elements for each condition
            $propertyNameNode = $xpath->query('ogc:PropertyName', $conditionNode);
            $literalValueNode = $xpath->query('ogc:Literal', $conditionNode);

            // Check if both PropertyName and Literal elements exist
            if ($propertyNameNode->length > 0 && $literalValueNode->length > 0) {
                $propertyName = $propertyNameNode->item(0)->nodeValue;
                $literalValue = $literalValueNode->item(0)->nodeValue;

                // Determine if it's a "not equal" condition and format accordingly
                switch ($conditionNode->nodeName) {
                    case 'PropertyIsEqualTo':
                        $conditionType = '==';
                        break;
                        /*case 'PropertyIsNotEqualTo':
                            $conditionType = '!=';
                            break;*/
                    default:
                        throw new LogicException(
                            sprintf("Condition element '%s' is not supported yet", $conditionNode->nodeName)
                        );
                }

                // Add the condition to the key-value array with its condition type
                $keyValuePairs[] = [$propertyName => [$conditionType, $literalValue]];
            }
        }

        // Return the list of key-value pairs
        return $keyValuePairs;
    }
}
