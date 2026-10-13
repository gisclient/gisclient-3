<?php

use GisClient\Author\Utils\PrintDocument;
use PHPUnit\Framework\TestCase;

class PrintDocumentProjectNameTest extends TestCase
{
    public function testReadsLowercaseProjectSentByMapClient(): void
    {
        $this->assertSame('cliente_a', PrintDocument::projectNameFromTiles([
            [
                'parameters' => [
                    'project' => 'cliente_a',
                    'map' => 'mappa',
                    'layers' => 'x',
                ],
            ],
        ]));
    }

    public function testReadsUppercaseProject(): void
    {
        $this->assertSame('cliente_a', PrintDocument::projectNameFromTiles([
            [
                'parameters' => [
                    'PROJECT' => 'cliente_a',
                    'MAP' => 'mappa',
                ],
            ],
        ]));
    }

    public function testSkipsTilesWithoutProject(): void
    {
        $this->assertSame('cliente_a', PrintDocument::projectNameFromTiles([
            [
                'parameters' => [
                    'layers' => 'osm',
                ],
            ],
            [
                'parameters' => [
                    'project' => 'cliente_a',
                ],
            ],
        ]));
    }

    public function testReturnsNullForExternalLayersOnly(): void
    {
        $this->assertNull(PrintDocument::projectNameFromTiles([
            [
                'parameters' => [
                    'layers' => 'osm',
                ],
            ],
            [
                'url' => 'http://example.com/wms',
            ],
        ]));
    }
}
