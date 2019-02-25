<?php

namespace GisClient\Author\Controller;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use GisClient\Author\Map;
use GisClient\Author\OfflineMap;

class OfflineController
{
    private function getMap($project, $map)
    {
        return new Map($project, $map);
    }

    private function getOfflineMap(Map $map)
    {
        return new OfflineMap($map);
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
        $offlineMapObj = $this->getOfflineMap($mapObj);

        $result = [
            'result' => 'ok'
        ];

        $themes = $mapObj->getThemes();
        $result['themes'] = array();
        foreach ($themes as $theme) {
            $themeStatus = $offlineMapObj->status($theme)[$theme->getName()];
            $themeStatus['name'] = $theme->getName();
            $themeStatus['title'] = $theme->getTitle();
            $result['themes'][] = $themeStatus;
        }

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
        $offlineMapObj = $this->getOfflineMap($mapObj);

        $result = [
            'result' => 'ok'
        ];

        $themeName = $request->query->get('theme');
        $target = $request->query->get('target');

        $theme = null;
        foreach ($mapObj->getThemes() as $t) {
            if ($t->getName() == $themeName) {
                $theme = $t;
            }
        }

        $offlineMapObj->start($theme, $target);

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
        $offlineMapObj = $this->getOfflineMap($mapObj);

        $result = [
            'result' => 'ok'
        ];

        $themeName = $request->query->get('theme');
        $target = $request->query->get('target');

        $theme = null;
        foreach ($mapObj->getThemes() as $t) {
            if ($t->getName() == $themeName) {
                $theme = $t;
            }
        }

        $offlineMapObj->stop($theme, $target);

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
        $offlineMapObj = $this->getOfflineMap($mapObj);

        $result = [
            'result' => 'ok'
        ];

        $themeName = $request->query->get('theme');
        $target = $request->query->get('target');

        $theme = null;
        foreach ($mapObj->getThemes() as $t) {
            if ($t->getName() == $themeName) {
                $theme = $t;
            }
        }

        $offlineMapObj->clear($theme, $target);

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
        $offlineMapObj = $this->getOfflineMap($mapObj);

        $zipFile = ROOT_PATH . 'var/' . $map . '.zip';

        $offlineMapObj->createZip($zipFile);

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
