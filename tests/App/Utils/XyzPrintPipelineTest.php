<?php

use GisClient\Author\Utils\MapImage;
use GisClient\Author\Utils\PrintDocument;
use PHPUnit\Framework\TestCase;

class XyzPrintPipelineTest extends TestCase
{
    public function testXyzOnlyPrintRetainsProjectForLogosAndHasNoWmsLegend(): void
    {
        $tiles = [[
            'service' => 'XYZ',
            'parameters' => [
                'PROJECT' => 'customer',
                'MAP' => 'map',
                'LAYERGROUP_ID' => 7,
            ],
        ]];
        $this->assertSame('customer', PrintDocument::projectNameFromTiles($tiles));
        $reflection = new ReflectionClass(PrintDocument::class);
        $document = $reflection->newInstanceWithoutConstructor();
        $property = $reflection->getProperty('tiles');
        $property->setAccessible(true);
        $property->setValue($document, $tiles);
        $method = $reflection->getMethod('getLegendsFromMapfile');
        $method->setAccessible(true);
        $this->assertSame([
            'themes' => [],
        ], $method->invoke($document));
    }

    public function testStaticImagePayloadDoesNotRequireOrForwardAnUrl(): void
    {
        if (!defined('MS_LAYER_POINT')) {
            $this->markTestSkipped('MapImage requires the MapScript constants');
        }
        $reflection = new ReflectionClass(MapImage::class);
        $image = $reflection->newInstanceWithoutConstructor();
        $property = $reflection->getProperty('tiles');
        $property->setAccessible(true);
        $property->setValue($image, [[
            'type' => 'XYZ',
            'url' => 'https://caller-supplied.example/not-used',
            'parameters' => [
                'project' => 'customer',
                'map' => 'map',
                'layergroup_id' => 7,
            ],
            'opacity' => 0,
        ]]);
        $method = $reflection->getMethod('buildWmsList');
        $method->setAccessible(true);
        $method->invoke($image);
        $this->assertSame([[
            'URL' => '',
            'SERVICE' => 'XYZ',
            'PARAMETERS' => [
                'PROJECT' => 'customer',
                'MAP' => 'map',
                'LAYERGROUP_ID' => 7,
                'OPACITY' => 0,
            ],
        ]], $image->getWmsList());
    }
}
