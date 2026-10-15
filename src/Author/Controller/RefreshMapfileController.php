<?php

namespace GisClient\Author\Controller;

use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Mapper\JsonApiExceptionMapper;
use GisClient\MapServer\Writer\MapfileRefresherInterface;
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
            $this->container->get(MapfileRefresherInterface::class)->refresh(
                $project,
                empty($mapset) ? null : $mapset,
                $target === "public"
            );
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

    /**
     * Refresh Mapfile for client applications (API Token or Super User session):
     * same parameters and responses as refreshMapfileAction, restricted to the Super User
     */
    public function apiRefreshMapfileAction(Request $request)
    {
        try {
            $this->assertAdmin();
        } catch (ApiException $exception) {
            $mapped = $this->container->get(JsonApiExceptionMapper::class)->map($exception);
            return new JsonResponse($mapped['payload'], $mapped['status']);
        }

        return $this->refreshMapfileAction($request);
    }

    private function assertAdmin()
    {
        $auth = \GCApp::getAuthenticationHandler();
        if (!$auth->isAuthenticated()) {
            throw new ApiException(401, 'authentication_required', 'Unauthorized', 'Authentication is required');
        }
        if (!$auth->isAdmin()) {
            throw new ApiException(403, 'admin_required', 'Forbidden', 'Administrator permissions are required');
        }
    }
}
