<?php

namespace GisClient\Author\Controller;

use GisClient\MapServer\Writer\MapfileWriterInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RefreshMapfileController implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Refresh Mapfile
     */
    public function refreshMapfileAction(Request $request)
    {
        $target = $request->request->get("target", null);
        $project = $request->request->get("project", null);
        $mapset = $request->request->get("mapset", null);
        if ($target === null) {
            return new JsonResponse([
                "result" => "ok",
                "error" => "Missing parameter 'target'",
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        if ($project === null) {
            return new JsonResponse([
                "result" => "ok",
                "error" => "Missing parameter 'project'",
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        try {
            if (defined('PROJECT_MAPFILE') && PROJECT_MAPFILE) {
                \GCAuthor::refreshProjectMapfile($project, ($target === "public"));
            } else {
                $refreshLayerMapfile = defined('ENABLE_OGC_SINGLE_LAYER_WMS') && ENABLE_OGC_SINGLE_LAYER_WMS === true;
                $publish = $target === "public";
                $writer = $this->container->get(MapfileWriterInterface::class);
                if (empty($mapset)) {
                    if (!\GCAuthor::hasProject($project)) {
                        throw new \Exception("Project '$project' does not exist.");
                    }
                    foreach (\GCAuthor::getMapsets($project) as $mapsetData) {
                        $writer->refreshMapset($project, $mapsetData['mapset_name'], $publish, $refreshLayerMapfile);
                    }
                } else {
                    $writer->refreshMapset($project, $mapset, $publish, $refreshLayerMapfile);
                }
            }
            $errors = \GCError::get();
            if (!empty($errors)) {
                throw new \Exception("GCErrors:\n" . implode("\n", $errors));
            }
            return new JsonResponse([
                "result" => "ok",
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                "result" => "error",
                "error" => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
