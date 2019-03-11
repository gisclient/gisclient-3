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

class MvtController
{
    private function getMap($project, $map)
    {
        return new Map($project, $map);
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
        $db = \GCApp::getDB();

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
        
        $sqlMvt = "SELECT ST_AsMVT(q, 'internal-layer-name', 4096, 'geom') as mvt"
            . " FROM ("
            . "     SELECT gid,"
            . "         ST_AsMVTGeom(ST_Transform(the_geom,3857), ST_MakeEnvelope({$data1['minx']}, {$data1['miny']}, {$data1['maxx']}, {$data1['maxy']},3857), 4096, 256, true) geom"
            . "     FROM r3gis.un_vol c"
            . "      "
            . " ) q";
        $stmtMvt = $db->prepare($sqlMvt);
        $stmtMvt->bindColumn('mvt', $data);
        $stmtMvt->execute();
        if (!$stmtMvt->fetch()) {
            throw new \Exception("Could not load data from db");
        }

        // echo $sqlMvt;
        // var_dump($data1);

        // var_dump($data);die;
        
        $response = new Response();
        $response->setContent($data);
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'application/x-protobuf');

        return $response;
    }
}
