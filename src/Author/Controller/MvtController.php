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

        $sqlBox = "SELECT "
            . "     -params.max1 + (x * params.res) as minx,"
            . "     params.max1 - (y * params.res) as miny,"
            . "     -params.max1 + (x * params.res) + params.res as maxx,"
            . "     params.max1 - (y *  params.res) - params.res as maxy"
            . " FROM (SELECT q.z, q.x, q.y, q.max1, q.max1 * 2 / 2^z as res"
            . "     FROM (SELECT $z as z, $x as x, $y as y, 6378137 * pi() as max1) as q"
            . " ) as params;";

        $stmtBox = $db->prepare($sqlBox);
        $stmtBox->execute();
        $data1 = $stmtBox->fetch(\PDO::FETCH_ASSOC);
        
        $sqlMvt = "SELECT ST_AsMVT(q, '{$layer->getName()}', 4096, 'geom') as mvt"
            . " FROM ("
            . "     SELECT {$fieldsText} ST_AsMVTGeom(geom3857, bbox, 4096, 256, true) as geom"
            . "     FROM ( "
            . "         SELECT *, ST_Transform({$layer->getGeomColumn()}, 3857) as geom3857, ST_MakeEnvelope({$data1['minx']}, {$data1['miny']}, {$data1['maxx']}, {$data1['maxy']}, 3857) as bbox"
            . "         FROM {$dbParams['schema']}.{$layer->getTable()} ) c"
            . "     WHERE ST_Intersects(geom3857, bbox)"
            . " ) q";
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
