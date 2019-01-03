<?php

namespace GisClient\Author\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use GisClient\Author\Utils\GCMap;
use GisClient\Author\Utils\GWGCMap;
use GisClient\Author\Utils\R3GisGCMap;
use GisClient\Author\Utils\SenchaTouchUtils;

class ClientController
{
    private function getOutputHeaders()
    {
        return array(
            'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT', // Date in the past
            'Last-Modified' => gmdate("D, d M Y H:i:s").' GMT', // always modified
            'Cache-Control' => 'no-cache, must-revalidate', // HTTP/1.1
            'Pragma' => 'no-cache', // HTTP/1.0
            'Content-Type' => 'application/json; Charset=UTF-8',
            'Access-Control-Allow-Origin' => '*',
        );
    }
    
    /**
     * Get the initialization object to create the map
     *
     * @deprecated change to gcmapConfigAction
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function gcmapAction(Request $request)
    {
        // check for required queryString parameter
        $mapset = $request->query->get('mapset');
        if (empty($mapset)) {
            return new JsonResponse(array(
                'error' => 200,
                'message' => 'No mapset name',
            ), JsonResponse::HTTP_BAD_REQUEST);
        }
        
        $getLegend = $request->query->get('legend', 0) == 1;
        $languageId = $request->query->get('lang');
        $showAsPublic = $request->query->get('show_as_public') == 1;
        
        // choose customer gcmap
        $jsonformat = $request->query->get('jsonformat');
        if (empty($jsonformat)) {
            $objMapset = new GCMap($mapset, $getLegend, $languageId, $showAsPublic);
        } else {
            $objMapset = new R3GisGCMap($mapset, $getLegend, $languageId, $showAsPublic);
        }
        
        // get output
        if (empty($jsonformat)) {
            $output = $objMapset->mapConfig;
        } else {
            $output = $objMapset->mapOptions;
            
            if ($jsonformat == 'senchatouch') {
                $output = SenchaTouchUtils::toSenchaTouch($output);
            }
        }
        
        $callback = $request->query->get('callback');
        if (empty($callback)) {
            return new JsonResponse($output, JsonResponse::HTTP_OK, $this->getOutputHeaders());
        } else {
            return new Response(sprintf('%s(%s)', $callback, $output), Response::HTTP_OK, $this->getOutputHeaders());
        }
    }
    
    /**
     * Get the initialization object to create the map
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function gcmapConfigAction(Request $request)
    {
        // check for required queryString parameter
        $mapset = $request->query->get('mapset');
        if (empty($mapset)) {
            return new JsonResponse(array(
                'error' => 200,
                'message' => 'No mapset name',
            ), JsonResponse::HTTP_BAD_REQUEST);
        }
        
        $getLegend = $request->query->get('legend', 0) == 1;
        $languageId = $request->query->get('lang');
        $showAsPublic = $request->query->get('show_as_public') == 1;
        
        $objMapset = new GCMap($mapset, $getLegend, $languageId, $showAsPublic);
        
        // get output
        $jsonformat = $request->query->get('jsonformat');
        if (empty($jsonformat)) {
            $output = $objMapset->mapConfig;
        } else {
            $output = $objMapset->mapOptions;
        }
        
        return new JsonResponse($output, JsonResponse::HTTP_OK, $this->getOutputHeaders());
    }
}
