<?php

namespace GisClient\GeoServer;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use GisClient\Logger\EmptyLogger;
use GisClient\GeoServer\Utils\SldFilter;
use GisClient\GeoServer\Utils\Color;

class AuthorSldGenerator implements LoggerAwareInterface
{

    use LoggerAwareTrait;
    const XSD_SE = 'http://www.opengis.net/se';
    const XSD_OGC = 'http://www.opengis.net/ogc';

    /**
     * XML document
     * @var \SimpleXMLElement
     */
    private $xml;

    /**
     * List of files to copy (eg: style images)
     * @var type
     */
    private $filesToCopy = array();

    function __construct()
    {
        $this->xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>'.
            '<StyledLayerDescriptor version="1.0.0" '.
            'xmlns="http://www.opengis.net/sld" '.
            'xmlns:se="http://www.opengis.net/se" '.
            'xmlns:gml="http://www.opengis.net/gml" '.
            'xmlns:ogc="http://www.opengis.net/ogc" '.
            'xmlns:xlink="http://www.w3.org/1999/xlink" '.
            'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xsi:schemaLocation="http://www.opengis.net/sld http://schemas.opengis.net/sld/1.0.0/StyledLayerDescriptor.xsd" />');

        $this->filesToCopy = array();
        $this->setLogger(new EmptyLogger());
    }

    /**
     * Add files to copy to the list
     *
     * @param string $fileName
     */
    private function addFileToCopy($fileName)
    {
        if (!in_array($fileName, $this->filesToCopy)) {
            $this->filesToCopy[] = $fileName;
        }
    }

    /**
     * Return the files to copy
     *
     * @return array
     */
    public function getFilesToCopy()
    {
        return $this->filesToCopy;
    }

    /**
     * Add a child node if not empty
     *
     * @param \SimpleXMLElement $element
     * @param string $name                  node name
     * @param string $value                 node value
     * @param string $namespace             namespace
     */
    private function addChildIfNotEmpty(\SimpleXMLElement $element, $name, $value, $namespace = null)
    {
        if (!empty($value)) {
            $element->addChild($name, $value, $namespace);
        }
    }

    /**
     * Return the symbol definition
     * 
     * @param string $symbolName
     * @return string
     * @throws \Exception
     */
    private function getSymbolDefinition($symbolName)
    {
        $result = array();
        $result['name'] = strtolower($symbolName);
        $result['category'] = 'no-symbol';
        $result['symbol_name'] = 'none';

        $symbolData = AuthorWrapper::getSymbolDef($symbolName);
        if (empty($symbolData)) {
            return $result;
        }

        switch (strtolower($symbolData['symbolcategory_name'])) {
            case 'pixmap':
                $result['category'] = 'pixmap';
                $result['file_name'] = AuthorWrapper::getRootPath().substr($symbolData['symbol_def'], 25, -1);  // Extract file name from text
                if (!file_exists($result['file_name'])) {
                    throw new \Exception("Symbol file not found for symbol \"{$symbolName}\". File: \"{$result['file_name']}\"");
                }
                break;
            case 'mapserver':
                // Standard symbology. No extra definition
                $result['category'] = 'standard';
                switch (strtolower($symbolName)) {
                    case '':
                        // No style
                        break;
                    case 'circle':
                    case 'square':
                    case 'triangle':
                    case 'star':
                    case 'cross':
                    case 'x':
                        $result['symbol_name'] = strtolower($symbolName);
                        break;
                    case 'horizontal':
                        $result['symbol_name'] = 'x';  // Geoserver symbol name
                        break;
                    default:
                        $this->logger->error("Unknown MapServer symbol \"{$symbolName}\"");
                    //throw new \Exception("Unknown MapServer symbol \"{$symbolName}\"");
                }
                break;
            case 'marker':
                // Known and unsupported symbol. Use SVG
                $result['category'] = 'marker';
                break;
            default:
                $this->logger->error("Unknown symbol \"{$symbolData['symbolcategory_name']}\" for symbol \"{$symbolName}\"");
            //throw new \Exception("Unknown symbol \"{$symbolData['symbolcategory_name']}\" for symbol \"{$symbolName}\"");
        }
        return $result;
    }

    /**
     * Add a sld (simple) filter
     *
     * @param \SimpleXMLElement $node
     * @param string $filter        the filter expression
     * @param string $expression    the class expression
     * @see http://docs.geoserver.org/stable/en/user/styling/sld-reference/filters.html
     */
    private function addFilters(\SimpleXMLElement $node, $filter, $expression)
    {
        $logicalOperatorsMap = array('and' => 'And', 'or' => 'Or');
        $compareOperatorsMap = array(
            '=' => 'PropertyIsEqualTo',
            '==' => 'PropertyIsEqualTo',
            'eq' => 'PropertyIsEqualTo',
            'ne' => 'PropertyIsNotEqualTo',
            '!=' => 'PropertyIsNotEqualTo',
            '<>' => 'PropertyIsNotEqualTo',
            '<' => 'PropertyIsLessThan',
            '>' => 'PropertyIsGreaterThan',
            '<=' => 'PropertyIsLessThanOrEqualTo',
            '>=' => 'PropertyIsGreaterThanOrEqualTo',
        );

        $parsedFilters = SldFilter::parseForFiltersAndExpression($filter, $expression);

        if (count($parsedFilters) > 0) {
            $filterElement = $node->addChild('Filter', null, self::XSD_OGC);
            $operator = key($parsedFilters);
            if ($operator == '') {
                $newNode = $filterElement;
            } else {
                $newNode = $filterElement->addChild($logicalOperatorsMap[$operator], null, self::XSD_OGC);
            }
            foreach (current($parsedFilters) as $f) {
                if (count($f['parts']) != 2) {
                    // Add dummy filter to prevent crash
                    $filterNode = $newNode->addChild('PropertyIsEqualTo', null, self::XSD_OGC);
                    $filterNode->addChild('PropertyName', $f['parts'][0], self::XSD_OGC);
                    $filterNode->addChild('Literal', 'dummy-value', self::XSD_OGC);
                    throw new \Exception("Filter evaluation error. ".print_r($parsedFilters, true)."\nSkipped");
                } else {
                    $value = $f['parts'][1];
                    if ($value[0] == "'" && $value[strlen($value) - 1] == "'") {
                        $value = trim(substr($value, 1, -1));
                    }
                    $filterNode = $newNode->addChild($compareOperatorsMap[$f['operator']], null, self::XSD_OGC);
                    $filterNode->addChild('PropertyName', $f['parts'][0], self::XSD_OGC);
                    $filterNode->addChild('Literal', $value, self::XSD_OGC);
                }
            }
        }
    }

    /**
     * Add style for a point geometry
     *
     * @param \SimpleXMLElement $node
     * @param array $classData
     * @param array $styleData
     */
    private function addPointStyle(\SimpleXMLElement $node, $classData, $styleData)
    {
        $pointElement = $node->addChild('PointSymbolizer');
        $graphicElement = $pointElement->addChild('Graphic');
        $symbolDef = $this->getSymbolDefinition($styleData['symbol_name']);

        // Fallback
        if ($symbolDef['category'] == 'marker' && in_array($symbolDef['name'], array('horizontal'))) {
            $this->logger->warning("Fallback symbol from \"{$symbolDef['category']}\" \"{$symbolDef['name']}\" to \"standard\" \"circle\"");
            $symbolDef = array(
                'name' => 'circle',
                'category' => 'standard',
                'symbol_name' => 'circle');
        }

        switch ($symbolDef['category']) {
            case 'no-symbol':
                // No style
                break;
            case 'standard':
                $markElement = $graphicElement->addChild('Mark');
                $markElement->addChild('WellKnownName', $symbolDef['symbol_name']);
                if (!empty($styleData['color'])) {
                    $fillElement = $markElement->addChild('Fill');
                    $cssParameterElement = $fillElement->addChild('CssParameter'); //, $this->colorConvert( $styleData['color'] ));
                    $cssParameterElement->addAttribute('name', 'fill');
                    $cssParameterElement->addChild('Literal', Color::convert($styleData['color']), self::XSD_OGC);
                }
                if (!empty($styleData['outlinecolor'])) {
                    $strokeElement = $markElement->addChild('Stroke');
                    $cssParameterElement = $strokeElement->addChild('CssParameter'); //, $this->colorConvert( $styleData['outlinecolor'] ));
                    $cssParameterElement->addAttribute('name', 'stroke');
                    $cssParameterElement->addChild('Literal', Color::convert($styleData['outlinecolor']), self::XSD_OGC);
                    if (!empty($styleData['width']) && $styleData['width'] > 0) {
                        $cssParameterElement = $strokeElement->addChild('CssParameter'); //, $styleData['width']);
                        $cssParameterElement->addAttribute('name', 'stroke-width');
                        $cssParameterElement->addChild('Literal', round($styleData['width'] / 1, 2), self::XSD_OGC);  // non serve round e /1. solo test
                    }
                }
                if (!empty($styleData['size'])) {
                    $graphicElement->addChild('Size', round($styleData['size'] / 1), 2);
                }
                if (!empty($styleData['opacity'])) {
                    $graphicElement->addChild('Opacity', $styleData['size'] / 100);
                }
                break;
            case 'marker':
                $this->logger->warning("Symbol \"{$styleData['symbol_name']}\" not implemented for category \"{$symbolDef['category']}\"");
                break;
            case 'pixmap':
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($symbolDef['file_name']);
                $externalGraphicElement = $graphicElement->addChild('ExternalGraphic');
                $OnlineResourceElement = $externalGraphicElement->addChild('OnlineResource');
                $OnlineResourceElement->addAttribute('xlink:type', 'simple');
                $OnlineResourceElement->addAttribute('xlink:href', basename($symbolDef['file_name']));
                $externalGraphicElement->addChild('Format', $mime);
                if (!empty($styleData['size'])) {
                    $graphicElement->addChild('Size', round($styleData['size'] / 1), 2);
                }
                if (!empty($styleData['size'])) {
                    $graphicElement->addChild('Size', round($styleData['size'] / 1), 2);
                }
                if (!empty($styleData['opacity'])) {
                    $graphicElement->addChild('Opacity', $styleData['size'] / 100);
                }

                $this->addFileToCopy($symbolDef['file_name']);
                break;
            default:
                $this->logger->error("Unknown symbol category \"{$symbolDef['category']}\"");
                break;
        }
    }

    /**
     * Generate the SLD
     * @param string $layergroupName
     * @param string $layerName
     * @return \SimpleXMLElement
     */
    public function generateSldForLayer($layergroupName, $layerName)
    {
        $layerData = AuthorWrapper::getLayerData($layergroupName, $layerName);
        if (!in_array($layerData['data_type'], array('point'))) {
            $this->logger->error("Unsupported data type \"{$layerData['data_type']}\"");
            return null;
        }

        $namedLayerElement = $this->xml->addChild('NamedLayer');
        $this->addChildIfNotEmpty($namedLayerElement, 'Name', "{$layergroupName}.{$layerName}");
        $this->addChildIfNotEmpty($namedLayerElement, 'Description', $layerData['layer_title'], self::XSD_SE);
        $userStyleElement = $namedLayerElement->addChild('UserStyle');
        $userStyleElement->addChild('Name', "{$layergroupName}.{$layerName}");
        $this->addChildIfNotEmpty($userStyleElement, 'Title', $layerData['layer_title']);
        $featureTypeStyleElement = $userStyleElement->addChild('FeatureTypeStyle');
        $this->logger->debug("Generate style for {$layergroupName}.{$layerName}");

        $layerClassData = AuthorWrapper::getLayerClass($layergroupName, $layerName);
        foreach ($layerClassData as $classData) {
            $ruleElement = $featureTypeStyleElement->addChild('Rule');
            $this->addChildIfNotEmpty($ruleElement, 'Title', $classData['class_name']);
            try {
                $this->addFilters($ruleElement, $classData['data_filter'], $classData['expression']);
            } catch (\Exception $e) {
                $this->logger->error("Filter evaluation error: {$e->getMessage()}");
            }
            foreach (AuthorWrapper::getStylesByClassId($classData['class_id']) as $row2) {
                switch (trim($classData['layertype_name'])) {
                    case 'point':
                        $this->addPointStyle($ruleElement, $classData, $row2);
                        break;
                    case 'line':
                        $this->logger->warning("Line geometry not implemented");
                        break;
                    case 'chart':
                        $this->logger->warning("Chart geometry not implemented");
                        break;
                    default:
                        $this->logger->error("Unknown layertype name \"{$classData['layertype_name']}\"");
                        break;
                }
            }
        }
        return $this->xml;
    }

    /**
     * Generate the SLD and return it as a text
     *
     * @param string $layergroupName
     * @param string $layerName
     * @return string
     */
    public function generateSldForLayerAsText($layergroupName, $layerName)
    {
        $this->generateSldForLayer($layergroupName, $layerName);
        $dom = dom_import_simplexml($this->xml)->ownerDocument;
        $dom->formatOutput = true;
        return $dom->saveXML();
    }
}