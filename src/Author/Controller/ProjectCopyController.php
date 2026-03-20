<?php

namespace GisClient\Author\Controller;

use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Mapper\JsonApiExceptionMapper;
use GisClient\Author\Api\ProjectCopy\ProjectCopyRequestParser;
use GisClient\Author\Api\Service\ProjectCopyService;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProjectCopyController implements ContainerAwareInterface
{
    /**
     * @var JsonApiExceptionMapper
     */
    private $exceptionMapper;

    /**
     * @var ProjectCopyService
     */
    private $projectCopyService;

    public function setContainer(?ContainerInterface $container = null)
    {
        $this->exceptionMapper = $container->get(JsonApiExceptionMapper::class);
        $this->projectCopyService = $container->get(ProjectCopyService::class);
    }

    public function copyAction(Request $request)
    {
        return $this->execute(function () use ($request) {
            $this->assertAdmin();

            $result = $this->projectCopyService->execute(
                (new ProjectCopyRequestParser())->parse($request->getContent())
            );

            return new JsonResponse($result->toArray(), Response::HTTP_CREATED);
        });
    }

    /**
     * @return Response
     */
    private function execute(callable $callable)
    {
        try {
            return $callable();
        } catch (\Throwable $exception) {
            $mapped = $this->exceptionMapper->map($exception);
            return new JsonResponse($mapped['payload'], $mapped['status']);
        }
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
