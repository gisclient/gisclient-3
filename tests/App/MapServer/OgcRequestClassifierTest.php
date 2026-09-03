<?php

use GisClient\MapServer\Connection\OgcRequestClassifier;
use PHPUnit\Framework\TestCase;

class OgcRequestClassifierTest extends TestCase
{
    /**
     * @dataProvider readOnlyRequests
     */
    public function testRoutedToTheReplica(string $service, string $request)
    {
        $this->assertTrue(
            OgcRequestClassifier::isReadOnly($service, $request, 'GET'),
            sprintf('%s %s should be served by the replica', $service, $request)
        );
    }

    public function readOnlyRequests(): array
    {
        return [
            'WMS GetMap' => ['WMS', 'GetMap'],
            'WMS GetFeatureInfo' => ['WMS', 'GetFeatureInfo'],
            'WMS GetCapabilities' => ['WMS', 'GetCapabilities'],
            'WFS GetFeature' => ['WFS', 'GetFeature'],
            'WFS DescribeFeatureType' => ['WFS', 'DescribeFeatureType'],
            'WFS GetCapabilities' => ['WFS', 'GetCapabilities'],
        ];
    }

    public function testReadOnlyClassificationIsCaseInsensitive()
    {
        // OGC clients are inconsistent about casing; QGIS and OpenLayers differ.
        $this->assertTrue(OgcRequestClassifier::isReadOnly('wms', 'getmap', 'GET'));
        $this->assertTrue(OgcRequestClassifier::isReadOnly('WMS', 'GETMAP', 'get'));
        $this->assertTrue(OgcRequestClassifier::isReadOnly(' WfS ', ' GetFeature ', 'POST'));
    }

    /**
     * @dataProvider writeRequests
     */
    public function testKeptOnThePrimary(
        ?string $service,
        ?string $request,
        ?string $method,
        array $params = [],
        ?string $uri = null
    ) {
        $this->assertFalse(OgcRequestClassifier::isReadOnly($service, $request, $method, $params, $uri));
    }

    public function writeRequests(): array
    {
        return [
            'WFS Transaction' => ['WFS', 'Transaction', 'POST'],
            'HTTP PUT' => ['WMS', 'GetMap', 'PUT'],
            'HTTP DELETE' => ['WMS', 'GetMap', 'DELETE'],
            'POST with GC_EDITMODE' => ['WFS', 'GetFeature', 'POST', [], '/services/ows.php?GC_EDITMODE=1'],
            'OLWFS proxied to the CGI' => ['WFS', 'GetFeature', 'POST', $this->olwfsParams()],
        ];
    }

    /**
     * @return array<string,string>
     */
    private function olwfsParams(): array
    {
        return [
            'gcRequestType' => 'OLWFS',
        ];
    }

    /**
     * @dataProvider ambiguousRequests
     */
    public function testUnknownRequestsFallBackToThePrimary(?string $service, ?string $request)
    {
        // Fail-safe: losing the offload costs nothing, a write on a replica fails.
        $this->assertFalse(OgcRequestClassifier::isReadOnly($service, $request, 'GET'));
    }

    public function ambiguousRequests(): array
    {
        return [
            'missing service' => [null, 'GetMap'],
            'missing request' => ['WMS', null],
            'empty strings' => ['', ''],
            'unknown service' => ['WCS', 'GetCoverage'],
            'unknown WMS operation' => ['WMS', 'DescribeLayer'],
            'GetLegendGraphic is not in the routed set' => ['WMS', 'GetLegendGraphic'],
            'WMS Transaction is not a thing' => ['WMS', 'Transaction'],
            'WFS operation offered to WMS' => ['WMS', 'GetFeature'],
        ];
    }

    public function testWriteMethodBeatsAReadOnlyOperationName()
    {
        // A PUT carrying GetMap parameters must not be treated as a read.
        $this->assertFalse(OgcRequestClassifier::isReadOnly('WMS', 'GetMap', 'PUT'));
        $this->assertFalse(OgcRequestClassifier::isReadOnly('WFS', 'GetFeature', 'DELETE'));
    }

    public function testEditModeMarkerAnywhereInTheUriBlocksRouting()
    {
        $this->assertFalse(OgcRequestClassifier::isReadOnly(
            'WFS',
            'GetFeature',
            'POST',
            [],
            '/services/ows.php?project=x&GC_EDITMODE=1&map=y'
        ));
    }
}
