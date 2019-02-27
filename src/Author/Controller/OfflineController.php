<?php

namespace GisClient\Author\Controller;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
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

    public function setContainer(ContainerInterface $container = null)
    {
        $this->offlineMap = $container->get(OfflineMap::class);
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

        $themeName = $request->query->get('theme');
        $target = $request->query->get('target');

        $layers = [];
        foreach ($mapObj->getThemes() as $theme) {
            if ($theme->getName() === $themeName) {
                $layers[] = $theme;
            }
        }

        $this->offlineMap->start($mapObj, $layers, $target);

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

        $themeName = $request->query->get('theme');
        $target = $request->query->get('target');

        $layers = [];
        foreach ($mapObj->getThemes() as $theme) {
            if ($theme->getName() === $themeName) {
                $layers[] = $theme;
            }
        }

        $this->offlineMap->stop($mapObj, $layers, $target);

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

        $themeName = $request->query->get('theme');
        $target = $request->query->get('target');

        $layers = [];
        foreach ($mapObj->getThemes() as $theme) {
            if ($theme->getName() === $themeName) {
                $layers[] = $theme;
            }
        }

        $this->offlineMap->clear($mapObj, $layers, $target);

        return new JsonResponse($result);
    }
    
    /**
     * Download offline data
     *
     * @param string $project
     * @param string $map
     * @return JsonResponse
     */
    public function downloadAction($project, $map, $format)
    {
        $mapObj = $this->getMap($project, $map);

        $zipFile = ROOT_PATH . 'var/' . $map . '.zip';

        $this->offlineMap->createZip($mapObj, $zipFile);

        if ($format === 'json') {
            $result = [
                'result' => 'ok',
                'file' => $zipFile,
            ];
    
            return new JsonResponse($result);
        }

        $response = new BinaryFileResponse($zipFile);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($zipFile));

        return $response;
    }
}
