<?php

namespace GisClient\Author\Controller;

use GisClient\Author\Api\Error\JsonApiExceptionMapper;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Serializer\JsonApiSerializer;
use GisClient\Author\Api\Service\ApiCrudService;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JsonApiController implements ContainerAwareInterface
{
    /**
     * @var ApiCrudService
     */
    private $apiCrudService;

    /**
     * @var JsonApiExceptionMapper
     */
    private $exceptionMapper;

    /**
     * @var JsonApiSerializer
     */
    private $serializer;

    public function setContainer(?ContainerInterface $container = null)
    {
        $this->apiCrudService = $container->get(ApiCrudService::class);
        $this->exceptionMapper = $container->get(JsonApiExceptionMapper::class);
        $this->serializer = $container->get(JsonApiSerializer::class);
    }

    public function indexAction($entity, Request $request)
    {
        return $this->execute(function () use ($entity, $request) {
            $this->assertAdmin();
            $payload = $this->serializer->serializeCollection(
                $this->apiCrudService->listResources($entity, $request->query->all())
            );
            return new JsonResponse($payload, Response::HTTP_OK);
        });
    }

    public function showAction($entity, $id, Request $request)
    {
        return $this->execute(function () use ($entity, $id, $request) {
            $this->assertAdmin();
            $payload = $this->serializer->serializeResource(
                $this->apiCrudService->getResource($entity, $id)
            );
            return new JsonResponse($payload, Response::HTTP_OK);
        });
    }

    public function createAction($entity, Request $request)
    {
        return $this->execute(function () use ($entity, $request) {
            $this->assertAdmin();
            $payload = $this->serializer->serializeResource(
                $this->apiCrudService->createResource(
                    $entity,
                    $this->serializer->deserializeRequestBody($request->getContent(), $entity)
                )
            );
            return new JsonResponse($payload, Response::HTTP_CREATED);
        });
    }

    public function updateAction($entity, $id, Request $request)
    {
        return $this->execute(function () use ($entity, $id, $request) {
            $this->assertAdmin();
            $payload = $this->serializer->serializeResource(
                $this->apiCrudService->updateResource(
                    $entity,
                    $id,
                    $this->serializer->deserializeRequestBody($request->getContent(), $entity)
                )
            );
            return new JsonResponse($payload, Response::HTTP_OK);
        });
    }

    public function deleteAction($entity, $id, Request $request)
    {
        return $this->execute(function () use ($entity, $id, $request) {
            $this->assertAdmin();
            $this->apiCrudService->deleteResource($entity, $id);
            return new Response('', Response::HTTP_NO_CONTENT);
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
