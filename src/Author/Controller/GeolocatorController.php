<?php

namespace GisClient\Author\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller to search by geolocator config
 *
 * @author Daniel Degasperi <daniel.degasperi@r3-gis.com>
 */
class GeolocatorController
{
    /**
     * Create error response
     *
     * @param string $errorMessage
     * @param integer $httpStatus
     * @return JsonResponse
     */
    private function createErrorResponse($errorMessage, $httpStatus = JsonResponse::HTTP_BAD_REQUEST)
    {
        print_debug($errorMessage, null, 'download');
        $data = [
            'result' => 'error',
            'error' => $errorMessage
        ];
        return new JsonResponse($data, $httpStatus);
    }

    private function getGeolocatorConfig($mapset, $lang = null)
    {
        $database = \GCApp::getDB();
        $sql = "SELECT geolocator FROM ".DB_SCHEMA.".mapset WHERE mapset_name=?";
        $stmt = $database->prepare($sql);
        $stmt->execute([$mapset]);
        $geolocatorConfig = $stmt->fetchColumn(0);
        if (empty($geolocatorConfig)) {
            return null;
        }

        $geolocatorConfig = json_decode($geolocatorConfig, true);
        if ($geolocatorConfig === null) {
            throw new \Exception(json_last_error_msg());
        }

        $result = isset($geolocatorConfig[$mapset]) ? $geolocatorConfig[$mapset] : null
        if ($lang !== null) {
            $mapset = $mapset.'_'.$lang;
        }

        return isset($geolocatorConfig[$mapset]) ? $geolocatorConfig[$mapset] : $result;
    }

    private function getDatabaseFromConfig(array $config, $mapset)
    {
        $database = \GCApp::getDB();

        $sql = 'SELECT catalog_path
                FROM '.DB_SCHEMA.'.catalog
                INNER JOIN '.DB_SCHEMA.'.mapset USING(project_name)
                WHERE catalog_name=:name
                AND mapset_name=:mapset
        ';
        $stmt = $database->prepare($sql);
        $stmt->execute([
            'name' => $config['catalogname'],
            'mapset' => $mapset
        ]);
        $catalogPath = $stmt->fetchColumn(0);
        if (empty($catalogPath)) {
            throw new \Exception(sprintf('Invalid catalog name "%" in configuration', $config['catalogname']));
        }
        return \GCApp::getDataDB($catalogPath);
    }

    private function search(array $config, $mapset, $key, $limit = 30, $offset = 0)
    {
        $database = $this->getDatabaseFromConfig($config, $mapset);

        $key = str_replace(' ', '%', trim($key));
        $key = str_replace('%%', '%', trim($key));
        $key = str_replace('%%', '%', trim($key));
        
        $sql = 'SELECT ' .
                    $config['namefield'].' AS name, ' .
                    $config['idfield'].' AS id ' .
                'FROM '.$config['tablename'].' '.
                'WHERE '.$config['namefield'].' ILIKE :key
        ';
        if (!empty($config['where'])) {
            $sql .= ' AND '.$config['where'];
        }
        if (!empty($config['order'])) {
            $sql .= ' ORDER BY '.$config['order'];
        }
        $sql .= ' LIMIT :limit ';
        $sql .= ' OFFSET :offset';

        $stmt = $database->prepare($sql);
        $stmt->execute([
            'key'=>'%'.$key.'%',
            'limit' => $limit,
            'offset' => $offset,
        ]);
        $results = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            array_push($results, $row);
        }
        return $results;
    }

    private function getGeom(array $config, $mapset, $id)
    {
        $database = $this->getDatabaseFromConfig($config, $mapset);

        $sql = 'SELECT st_astext(ST_Force_2D('.$config['geomfield'].'))
                FROM '.$config['tablename'].' 
                WHERE '.$config['idfield'].' = :id
        ';
        $stmt = $database->prepare($sql);
        $stmt->execute([
            'id' => $id
        ]);
        $result = $stmt->fetchColumn(0);
        return $result;
    }

    public function getAction(Request $request)
    {
        $action = $request->query->get('action');
        $mapset = $request->query->get('mapset');
        $lang = $request->query->get('lang');
        if (empty($mapset)) {
            return $this->createErrorResponse('Undefined mapset');
        }

        // get geolocator config
        $geolocatorConfig = $this->getGeolocatorConfig($mapset, $lang);
        if (empty($geolocatorConfig)) {
            return $this->createErrorResponse('Missing geolocator configuration');
        }

        switch ($action) {
            case 'search':
                $key = $request->query->get('key');
                $limit = $request->query->get('limit', 30);
                $offset = $request->query->get('offset', 0);
                if (empty($key)) {
                    return $this->createErrorResponse('Undefined key');
                }
                $data = $this->search($geolocatorConfig, $mapset, $key, $limit, $offset);
                break;
            case 'get-geom':
                $id = $request->query->get('id');
                if (empty($id)) {
                    return $this->createErrorResponse('Undefined id');
                }
                $data = $this->getGeom($geolocatorConfig, $mapset, $id);
                break;
            default:
                return $this->createErrorResponse('Invalid action');
        }

        return new JsonResponse([
            'result' => 'ok',
            'data' => $data,
        ]);
    }
}
