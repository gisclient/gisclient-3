<?php

namespace GisClient\Author\Controller;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use GisClient\Author\LayerLevelInterface;
use GisClient\Author\Map;
use GisClient\Author\OfflineMap;

class OfflineController implements ContainerAwareInterface
{
    /**
     * Offline maps handler
     *
     * @var OfflineMap
     */
    private $offlineMap;

    private $tmpDir;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->offlineMap = $container->get(OfflineMap::class);
        $this->tmpDir = $container->getParameter('tmp_dir');
    }

    private function getMap($project, $map)
    {
        return new Map($project, $map);
    }
    
    /**
     * Get data for offline maps
     *
     * @param string $project
     * @param string $map
     * @return JsonResponse
     */
    public function getDataAction($project, $map)
    {
        $mapObj = $this->getMap($project, $map);

        $result = [
            'result' => 'ok',
            'data' => $this->offlineMap->status($mapObj),
        ];

        return new JsonResponse($result);
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
     * Start generation of offline data for theme
     *
     * @param string $project
     * @param string $map
     * @param Request $request
     * @return JsonResponse
     */
    public function startAction($project, $map, Request $request)
    {
        $mapObj = $this->getMap($project, $map);

        $result = [
            'result' => 'ok'
        ];

        $target = $request->query->get('target');
        $layer = $this->getLayer($mapObj, $request->query->get('layertype'), $request->query->get('layer'));
        $this->offlineMap->start($layer, $target);

        return new JsonResponse($result);
    }
    
    /**
     * Stop generation of offline data for theme
     *
     * @param string $project
     * @param string $map
     * @param Request $request
     * @return JsonResponse
     */
    public function stopAction($project, $map, Request $request)
    {
        $mapObj = $this->getMap($project, $map);

        $result = [
            'result' => 'ok'
        ];

        $target = $request->query->get('target');
        $layer = $this->getLayer($mapObj, $request->query->get('layertype'), $request->query->get('layer'));
        $this->offlineMap->stop($layer, $target);

        return new JsonResponse($result);
    }
    
    /**
     * Rmove offline data for theme
     *
     * @param string $project
     * @param string $map
     * @param Request $request
     * @return JsonResponse
     */
    public function clearAction($project, $map, Request $request)
    {
        $mapObj = $this->getMap($project, $map);

        $result = [
            'result' => 'ok'
        ];

        $target = $request->query->get('target');
        $layer = $this->getLayer($mapObj, $request->query->get('layertype'), $request->query->get('layer'));
        $this->offlineMap->clear($layer, $target);

        return new JsonResponse($result);
    }
    
    /**
     * Download offline data
     *
     * @param string $project
     * @param string $map
     * @return JsonResponse
     */
    public function downloadAction($project, $map, $format, Request $request)
    {
        $fs = new Filesystem();
        $mapObj = $this->getMap($project, $map);

        $zipFile = $fs->tempnam($this->tmpDir, 'offline_zip');

        $formats = [
            'mbtiles' => $request->query->get('mbtiles', 0) == 1,
            'mvt' => $request->query->get('mvt', 0) == 1,
            'sqlite' => $request->query->get('sqlite', 1) == 1,
        ];

        $this->offlineMap->createZip($mapObj, $zipFile, $formats);

        if ($format === 'json') {
            $result = [
                'result' => 'ok',
                'file' => $zipFile,
            ];
    
            return new JsonResponse($result);
        }

        $response = new BinaryFileResponse($zipFile);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $map . '.zip');
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
