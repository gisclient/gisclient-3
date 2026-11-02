<?php

namespace GisClient\Author\Utils;

/** Standard Web Mercator XYZ raster sources, also usable by static image exports. */
class XyzPrintSource
{
    public const WORLD_EXTENT = 20037508.342789244;

    public static function maxZoom(): int
    {
        $value = getenv('GC_XYZ_MAX_ZOOM');
        if ($value === false || $value === '') {
            return 19;
        }
        if (!ctype_digit($value) || (int) $value > 22) {
            throw new \InvalidArgumentException('GC_XYZ_MAX_ZOOM must be an integer between 0 and 22');
        }
        return (int) $value;
    }

    public static function urls(string $configuredUrls): array
    {
        $urls = preg_split('/[\r\n,]+/', trim($configuredUrls), -1, PREG_SPLIT_NO_EMPTY);
        if (!$urls) {
            throw new \InvalidArgumentException('An XYZ URL is required');
        }
        return array_map(function (string $url): string {
            $url = trim($url);
            UrlChecker::checkUrl($url);
            if (!parse_url($url, PHP_URL_HOST)) {
                throw new \InvalidArgumentException('An XYZ URL host is required');
            }
            foreach (['x', 'y', 'z'] as $coordinate) {
                $url = str_replace('${' . $coordinate . '}', '{' . $coordinate . '}', $url);
                if (strpos($url, '{' . $coordinate . '}') === false) {
                    throw new \InvalidArgumentException('XYZ URL must contain {z}, {x} and {y}');
                }
                $url = str_replace('{' . $coordinate . '}', '${' . $coordinate . '}', $url);
            }
            return $url;
        }, $urls);
    }

    public static function browserOptions(int $srid): array
    {
        if (!in_array($srid, [3857, 900913], true)) {
            throw new \InvalidArgumentException('Direct XYZ requires a Web Mercator mapset; use WMS/MapProxy for other CRSs');
        }
        $resolutions = [];
        for ($zoom = 0; $zoom <= self::maxZoom(); $zoom++) {
            $resolutions[] = (2 * self::WORLD_EXTENT / 256) / pow(2, $zoom);
        }
        return [
            'sphericalMercator' => true,
            'projection' => 'EPSG:' . $srid,
            'maxExtent' => [-self::WORLD_EXTENT, -self::WORLD_EXTENT, self::WORLD_EXTENT, self::WORLD_EXTENT],
            'serverResolutions' => $resolutions,
            'zoomOffset' => 0,
        ];
    }

    public static function fromLayer(array $layer): string
    {
        $parameters = $layer['PARAMETERS'] ?? [];
        $url = self::configuredUrl(\GCApp::getDB(), $parameters);
        $cache = ROOT_PATH . 'var/cache/xyz-print';
        if (!is_dir($cache) && !mkdir($cache, 0775, true) && !is_dir($cache)) {
            throw new \RuntimeException('Cannot create XYZ print cache');
        }
        $xml = self::buildDataset($url, $cache, self::maxZoom());
        return self::writeDataset($cache, $xml);
    }

    public static function configuredUrl(\PDO $db, array $parameters): string
    {
        $stmt = $db->prepare(
            'SELECT lg.url FROM ' . DB_SCHEMA . '.layergroup lg
             JOIN ' . DB_SCHEMA . '.mapset_layergroup ml USING (layergroup_id)
             JOIN ' . DB_SCHEMA . '.mapset m USING (mapset_name)
             WHERE lg.layergroup_id = :id AND lg.owstype_id = 9
               AND ml.mapset_name = :map AND m.project_name = :project'
        );
        $stmt->execute([
            ':id' => $parameters['LAYERGROUP_ID'] ?? null,
            ':map' => $parameters['MAP'] ?? null,
            ':project' => $parameters['PROJECT'] ?? null,
        ]);
        $url = $stmt->fetchColumn();
        if (!is_string($url) || trim($url) === '') {
            throw new \RuntimeException('XYZ source is not configured for this mapset');
        }
        // Pick one of the configured mirrors. Never use an URL supplied by the print caller.
        return self::urls($url)[0];
    }

    private static function writeDataset(string $cache, string $xml): string
    {
        $path = $cache . '/' . hash('sha256', $xml) . '.xml';
        if (!file_exists($path)) {
            $temporary = tempnam($cache, 'xyz-');
            if ($temporary === false) {
                throw new \RuntimeException('Cannot create XYZ print dataset');
            }
            try {
                if (file_put_contents($temporary, $xml) === false || !rename($temporary, $path)) {
                    throw new \RuntimeException('Cannot write XYZ print dataset');
                }
            } finally {
                if (file_exists($temporary)) {
                    unlink($temporary);
                }
            }
        }
        return $path;
    }

    public static function buildDataset(string $url, string $cachePath, int $maxZoom = 19): string
    {
        if ($maxZoom < 0 || $maxZoom > 22) {
            throw new \InvalidArgumentException('Invalid XYZ maximum zoom (0–22)');
        }
        $url = self::urls($url)[0];
        $url = htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $cachePath = htmlspecialchars($cachePath, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        return '<GDAL_WMS><Service name="TMS"><ServerUrl>' . $url . '</ServerUrl></Service>' .
            '<DataWindow><UpperLeftX>-20037508.342789244</UpperLeftX>' .
            '<UpperLeftY>20037508.342789244</UpperLeftY>' .
            '<LowerRightX>20037508.342789244</LowerRightX>' .
            '<LowerRightY>-20037508.342789244</LowerRightY>' .
            '<TileLevel>' . $maxZoom . '</TileLevel><TileCountX>1</TileCountX>' .
            '<TileCountY>1</TileCountY><YOrigin>top</YOrigin></DataWindow>' .
            '<Projection>EPSG:3857</Projection><BlockSizeX>256</BlockSizeX>' .
            '<BlockSizeY>256</BlockSizeY><BandsCount>4</BandsCount>' .
            '<Cache><Path>' . $cachePath . '/tiles</Path></Cache>' .
            '<Timeout>10</Timeout><MaxConnections>4</MaxConnections>' .
            '<ZeroBlockHttpCodes>204,404</ZeroBlockHttpCodes></GDAL_WMS>';
    }
}
