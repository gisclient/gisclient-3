<?php

require_once __DIR__ . '/EntityRepositoryStub.php';

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Serializer\JsonApiSerializer;
use GisClient\Author\Api\Service\ApiCrudService;
use GisClient\Author\Api\Validation\EntityValidator;
use GisClient\Author\Persistence\EntityRepository;

final class TestApiCrudService extends ApiCrudService
{
    private JsonApiSerializer $serializer;

    private EntityRepository $repository;

    public function __construct(
        EntityRepository $repository,
        EntityValidator $validator
    ) {
        parent::__construct(
            $repository,
            $validator
        );
        $this->repository = $repository;
        $this->serializer = new JsonApiSerializer();
    }

    public static function create(
        ?EntityRepository $repository = null,
        ?EntityValidator $validator = null
    ): self {
        $repository ??= new EntityRepositoryStub();
        $validator ??= new EntityValidator(
            null,
            static fn (...$args): bool => true,
            $repository
        );

        return new self(
            $repository,
            $validator
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
        if ($this->repository instanceof EntityRepositoryStub) {
            $this->repository->beginWriteContext($entity);
        }
    }

    private function endWriteContext(): void
    {
        if ($this->repository instanceof EntityRepositoryStub) {
            $this->repository->endWriteContext();
        }
    }
}
