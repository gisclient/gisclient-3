<?php

require_once __DIR__ . '/AuthorEntityRepositoryStub.php';

use GisClient\Author\Api\Contract\AuthorEntityRepositoryInterface;
use GisClient\Author\Api\Definition\DtoEntityDefinitionProvider;
use GisClient\Author\Api\Serializer\JsonApiSerializer;
use GisClient\Author\Api\Service\ApiCrudService;
use GisClient\Author\Api\Validation\PayloadValidator;

final class TestApiCrudService extends ApiCrudService
{
    private JsonApiSerializer $serializer;

    private AuthorEntityRepositoryInterface $repository;

    public function __construct(
        DtoEntityDefinitionProvider $provider,
        AuthorEntityRepositoryInterface $repository
    ) {
        parent::__construct(
            $provider,
            $repository,
            new PayloadValidator(null, static fn (...$args): bool => true)
        );
        $this->repository = $repository;
        $this->serializer = new JsonApiSerializer();
    }

    public static function create(?AuthorEntityRepositoryInterface $repository = null): self
    {
        return new self(
            new DtoEntityDefinitionProvider(),
            $repository ?? new AuthorEntityRepositoryStub()
        );
    }

    public function listResources($entity, array $query, array $scope = [])
    {
        return $this->serializer->serializeCollection(parent::listResources($entity, $query, $scope));
    }

    public function getResource($entity, $id, array $query = [], array $scope = [])
    {
        return $this->serializer->serializeResource(parent::getResource($entity, $id, $query, $scope));
    }

    public function createResource($entity, $payload, array $scope = [])
    {
        $this->beginWriteContext($entity);

        try {
            return $this->serializer->serializeResource(parent::createResource($entity, $payload, $scope));
        } finally {
            $this->endWriteContext();
        }
    }

    public function updateResource($entity, $id, $payload, array $scope = [])
    {
        $this->beginWriteContext($entity);

        try {
            return $this->serializer->serializeResource(parent::updateResource($entity, $id, $payload, $scope));
        } finally {
            $this->endWriteContext();
        }
    }

    private function beginWriteContext(string $entity): void
    {
        if ($this->repository instanceof AuthorEntityRepositoryStub) {
            $this->repository->beginWriteContext($entity);
        }
    }

    private function endWriteContext(): void
    {
        if ($this->repository instanceof AuthorEntityRepositoryStub) {
            $this->repository->endWriteContext();
        }
    }
}
