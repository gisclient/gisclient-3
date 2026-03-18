<?php

require_once __DIR__ . '/AuthorEntityRepositoryStub.php';

use GisClient\Author\Api\Contract\AuthorEntityRepositoryInterface;
use GisClient\Author\Api\Definition\DtoEntityDefinitionProvider;
use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Serializer\JsonApiSerializer;
use GisClient\Author\Api\Service\ApiCrudService;
use GisClient\Author\Api\Validation\PersistenceWriteValidator;

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
            new PersistenceWriteValidator(null, static fn (...$args): bool => true)
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

    public function listResources($entity, array $query)
    {
        return $this->serializer->serializeCollection(parent::listResources($entity, $query));
    }

    public function getResource($entity, $id)
    {
        return $this->serializer->serializeResource(parent::getResource($entity, $id));
    }

    public function createResource($entity, JsonApiDto $dto)
    {
        $this->beginWriteContext($entity);

        try {
            return $this->serializer->serializeResource(parent::createResource($entity, $dto));
        } finally {
            $this->endWriteContext();
        }
    }

    public function updateResource($entity, $id, JsonApiDto $dto)
    {
        $this->beginWriteContext($entity);

        try {
            return $this->serializer->serializeResource(parent::updateResource($entity, $id, $dto));
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
