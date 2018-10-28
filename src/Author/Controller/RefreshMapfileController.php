<?php

namespace GisClient\Author\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class RefreshMapfileController
{

    /**
     * Refresh Mapfile
     */
    public function refreshMapfileAction(Request $request)
    {
        $target = $request->request->get("target", null);
        $project = $request->request->get("project", null);
        $mapset = $request->request->get("mapset", null);
        if ($target === null) {
            return new JsonResponse(array(
                "result" => "ok",
                "error" => "Missing parameter 'target'",
            ), JsonResponse::HTTP_BAD_REQUEST);
        }
        if ($project === null) {
            return new JsonResponse(array(
                "result" => "ok",
                "error" => "Missing parameter 'project'",
            ), JsonResponse::HTTP_BAD_REQUEST);
        }
        try {
            if (defined('PROJECT_MAPFILE') && PROJECT_MAPFILE) {
                \GCAuthor::refreshProjectMapfile($project, ($target === "public"));
            } else {
                $refreshLayerMapfile = defined('ENABLE_OGC_SINGLE_LAYER_WMS') && ENABLE_OGC_SINGLE_LAYER_WMS === true;
                $publish = $target === "public";
                if (empty($mapset)) {
                    \GCAuthor::refreshMapfiles($project, $publish, $refreshLayerMapfile);
                } else {
                    \GCAuthor::refreshMapfile($project, $mapset, $publish, $refreshLayerMapfile);
                }
            }
            $errors = \GCError::get();
            if (!empty($errors)) {
                throw new \Exception("GCErrors:\n" . implode("\n", $errors));
            }
            return new JsonResponse(array(
                "result" => "ok",
            ));
        } catch (\Exception $e) {
            return new JsonResponse(array(
                "result" => "error",
                "error" => $e->getMessage(),
            ), JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
