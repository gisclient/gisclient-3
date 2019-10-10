<?php

namespace GisClient\Author\Controller;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use GisClient\Author\LayerLevelInterface;
use GisClient\Author\Map;
use GisClient\Author\Db;

class MvtController
{
    private function getMap($project, $map)
    {
        return new Map($project, $map);
    }

    private function getLayer(LayerLevelInterface $layer, $layerType, $layerName)
    {
        $category = strtolower(substr(strrchr(get_class($layer), '\\'), 1));
        if ($layerType === $category && $layer->getName() === $layerName) {
            return $layer;
        }

        $children = $layer->getChildren();
        foreach ($children as $layer) {
            if (($result = $this->getLayer($layer, $layerType, $layerName)) !== null) {
                return $result;
            }
        }

        return null;
    }
    
    /**
     * Get tile for mvt layer
     *
     * @param string $project
     * @param string $map
     * @param string $layer
     * @param int $z
     * @param int $x
     * @param int $y
     * @return Response
     */
    public function getTileAction($project, $map, $layer, $z, $x, $y)
    {
        $mapObj = $this->getMap($project, $map);

        list($themeName, $layerName) = explode('.', $layer);
        $layer = $this->getLayer($mapObj, 'layer', $layerName);

        $dbObj = new Db($layer->getCatalog());
        $dbParams = $dbObj->getParams();
        $db = $dbObj->getDB();

        $fields = $layer->getFields();
        $fieldsText = '';
        foreach ($fields as $field) {
            $fieldsText .= $field->getName() . ',';
        }

        $sqlMvt = "
            WITH extent AS (
                WITH tile AS (
                    SELECT {$z} as z, {$x} as x, {$y} as y, 6378137 * pi() as max1
                ), tile_res AS (
                    SELECT
                        tile.z, tile.x, tile.y, tile.max1, tile.max1 * 2 / 2^z as res
                    FROM tile
                ), box_coordinates AS (
                    SELECT
                        -max1 + (x * res) as minx,
                        max1 - (y * res) as miny,
                        -max1 + (x * res) + res as maxx,
                        max1 - (y *  res) - res as maxy
                    FROM tile_res
                )
                SELECT ST_MakeEnvelope(minx, miny, maxx, maxy, 3857) as bbox
                FROM box_coordinates
            ), data_source AS (
                SELECT
                    {$fieldsText}
                    ST_Transform({$layer->getGeomColumn()}, 3857) as geom3857
                FROM {$dbParams['schema']}.{$layer->getTable()}
            ), mvt_data_set AS (
                SELECT
                    {$fieldsText}
                    ST_AsMVTGeom(geom3857, bbox, 4096, 256, true) as geom
                FROM data_source, extent
                WHERE ST_Intersects(geom3857, bbox)
            )
            SELECT ST_AsMVT(mvt_data_set, '{$layer->getName()}', 4096, 'geom') as mvt
            FROM mvt_data_set
        ";

            $stmtMvt = $db->prepare($sqlMvt);
            $stmtMvt->bindColumn('mvt', $data);
            $stmtMvt->execute();
        if (!$stmtMvt->fetch()) {
            throw new \Exception("Could not load data from db");
        }
        
        $response = new Response();
        $response->setContent($data);
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'application/x-protobuf');

        return $response;
    }
}
