<?php

namespace GisClient\Author\Controller;

use GisClient\Author\Api\Error\JsonApiExceptionMapper;
use GisClient\Author\Api\Exception\ApiException;
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

    public function setContainer(ContainerInterface $container = null)
    {
        $this->apiCrudService = $container->get(ApiCrudService::class);
        $this->exceptionMapper = $container->get(JsonApiExceptionMapper::class);
    }

    public function indexAction($entity, Request $request)
    {
        return $this->execute(function () use ($entity, $request) {
            $payload = $this->apiCrudService->listResources($entity, $request->query->all());
            return new JsonResponse($payload, Response::HTTP_OK);
        });
    }

    public function showAction($entity, $id, Request $request)
    {
        return $this->execute(function () use ($entity, $id, $request) {
            $payload = $this->apiCrudService->getResource($entity, $id, $request->query->all());
            return new JsonResponse($payload, Response::HTTP_OK);
        });
    }

    public function createAction($entity, Request $request)
    {
        return $this->execute(function () use ($entity, $request) {
            $payload = $this->apiCrudService->createResource($entity, $this->decodeJsonBody($request));
            return new JsonResponse($payload, Response::HTTP_CREATED);
        });
    }

    public function updateAction($entity, $id, Request $request)
    {
        return $this->execute(function () use ($entity, $id, $request) {
            $payload = $this->apiCrudService->updateResource($entity, $id, $this->decodeJsonBody($request));
            return new JsonResponse($payload, Response::HTTP_OK);
        });
    }

    public function deleteAction($entity, $id)
    {
        return $this->execute(function () use ($entity, $id) {
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

    /**
     * @return array
     */
    private function decodeJsonBody(Request $request)
    {
        $content = $request->getContent();
        if ($content === null || trim($content) === '') {
            throw new ApiException(400, 'invalid_json', 'Invalid JSON', 'Request body must be valid JSON');
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new ApiException(400, 'invalid_json', 'Invalid JSON', 'Request body must be valid JSON');
        }

        return $decoded;
    }
}
