<?php

use GisClient\Author\Utils\XyzPrintSource;
use PHPUnit\Framework\TestCase;

class XyzPrintSourceTest extends TestCase
{
    private $previousMaxZoom;

    protected function setUp(): void
    {
        $this->previousMaxZoom = getenv('GC_XYZ_MAX_ZOOM');
        putenv('GC_XYZ_MAX_ZOOM');
    }

    protected function tearDown(): void
    {
        putenv($this->previousMaxZoom === false ? 'GC_XYZ_MAX_ZOOM' : 'GC_XYZ_MAX_ZOOM=' . $this->previousMaxZoom);
    }

    public function testBuildsWebMercatorDatasetWithTopOriginAndEscapedQuery(): void
    {
        $xml = XyzPrintSource::buildDataset('https://tiles.example/{z}/{x}/{y}.png?a=1&b=2', '/tmp/cache', 18);
        $document = new DOMDocument();
        $this->assertTrue($document->loadXML($xml));
        $this->assertSame('https://tiles.example/${z}/${x}/${y}.png?a=1&b=2', $document->getElementsByTagName('ServerUrl')->item(0)->textContent);
        $this->assertSame('top', $document->getElementsByTagName('YOrigin')->item(0)->textContent);
        $this->assertSame('EPSG:3857', $document->getElementsByTagName('Projection')->item(0)->textContent);
        $this->assertSame('18', $document->getElementsByTagName('TileLevel')->item(0)->textContent);
        $this->assertSame('4', $document->getElementsByTagName('BandsCount')->item(0)->textContent);
    }

    public function testPreservesOpenLayersTemplateSyntax(): void
    {
        $this->assertSame(
            XyzPrintSource::buildDataset('https://tiles.example/{z}/{x}/{y}.jpg', '/tmp/cache'),
            XyzPrintSource::buildDataset('https://tiles.example/${z}/${x}/${y}.jpg', '/tmp/cache')
        );
    }

    public function testRejectsNonXyzUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        XyzPrintSource::buildDataset('https://tiles.example/wms', '/tmp/cache');
    }

    public function testRejectsExcessiveZoom(): void
    {
        $this->expectException(InvalidArgumentException::class);
        XyzPrintSource::buildDataset('https://tiles.example/{z}/{x}/{y}.png', '/tmp/cache', 23);
    }

    public function testNormalizesBrowserUrlsAndMirrors(): void
    {
        $this->assertSame([
            'https://a.example/${z}/${x}/${y}.png',
            'https://b.example/${z}/${x}/${y}.png',
        ], XyzPrintSource::urls(" https://a.example/{z}/{x}/{y}.png\n https://b.example/\${z}/\${x}/\${y}.png\n"));
    }

    public function testBrowserGridHasGlobalOriginAndNoNegativeZoomOffset(): void
    {
        putenv('GC_XYZ_MAX_ZOOM=18');
        $options = XyzPrintSource::browserOptions(3857);
        $this->assertCount(19, $options['serverResolutions']);
        $this->assertSame(19, $options['numZoomLevels']);
        $this->assertEqualsWithDelta(156543.033928, $options['serverResolutions'][0], 0.00001);
        $this->assertSame(0, $options['zoomOffset']);
        $this->assertSame(-XyzPrintSource::WORLD_EXTENT, $options['maxExtent'][0]);
        $this->assertSame('EPSG:900913', XyzPrintSource::browserOptions(900913)['projection']);
    }

    public function testLocalCrsCannotBeSilentlyUsedForDirectBrowserTiles(): void
    {
        $this->expectException(InvalidArgumentException::class);
        XyzPrintSource::browserOptions(32632);
    }

    public function testZeroZoomIsSupported(): void
    {
        putenv('GC_XYZ_MAX_ZOOM=0');
        $this->assertSame(0, XyzPrintSource::maxZoom());
        $this->assertCount(1, XyzPrintSource::browserOptions(3857)['serverResolutions']);
        $this->assertSame(1, XyzPrintSource::browserOptions(3857)['numZoomLevels']);
    }

    public function testBrowserPreservesCustomMapsetScalesWithoutShiftingTileZooms(): void
    {
        $mapResolutions = [38.218514, 19.109257, 9.5546285];
        $options = XyzPrintSource::browserOptions(3857, $mapResolutions);
        $this->assertSame($mapResolutions, $options['resolutions']);
        $this->assertSame(3, $options['numZoomLevels']);
        $this->assertCount(20, $options['serverResolutions']);
        $this->assertSame(0, $options['zoomOffset']);
    }

    public function testInvalidEnvironmentValueIsRejected(): void
    {
        putenv('GC_XYZ_MAX_ZOOM=invalid');
        $this->expectException(InvalidArgumentException::class);
        XyzPrintSource::maxZoom();
    }

    public function testSourceMustBelongToRequestedProjectAndMapset(): void
    {
        if (!defined('DB_SCHEMA')) {
            define('DB_SCHEMA', 'gisclient_34');
        }
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("ATTACH DATABASE ':memory:' AS " . DB_SCHEMA);
        $db->exec('CREATE TABLE ' . DB_SCHEMA . '.layergroup (layergroup_id INTEGER, owstype_id INTEGER, url TEXT)');
        $db->exec('CREATE TABLE ' . DB_SCHEMA . '.mapset_layergroup (layergroup_id INTEGER, mapset_name TEXT)');
        $db->exec('CREATE TABLE ' . DB_SCHEMA . '.mapset (mapset_name TEXT, project_name TEXT)');
        $db->exec("INSERT INTO " . DB_SCHEMA . ".layergroup VALUES (7, 9, 'https://configured.example/{z}/{x}/{y}.png')");
        $db->exec("INSERT INTO " . DB_SCHEMA . ".mapset_layergroup VALUES (7, 'map_a')");
        $db->exec("INSERT INTO " . DB_SCHEMA . ".mapset VALUES ('map_a', 'project_a')");
        $parameters = [
            'LAYERGROUP_ID' => 7,
            'MAP' => 'map_a',
            'PROJECT' => 'project_a',
        ];
        $this->assertSame('https://configured.example/${z}/${x}/${y}.png', XyzPrintSource::configuredUrl($db, $parameters));
        $parameters['PROJECT'] = 'different_project';
        $this->expectException(RuntimeException::class);
        XyzPrintSource::configuredUrl($db, $parameters);
    }
}
