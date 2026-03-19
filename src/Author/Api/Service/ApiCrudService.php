<?php

namespace GisClient\Author\Api\Service;

use GisClient\Author\Api\Contract\AuthorEntityRepositoryInterface;
use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Mapper\DtoToEntityMapper;
use GisClient\Author\Api\Mapper\ResourceQueryMapper;
use GisClient\Author\Api\Model\ResourceCollectionData;
use GisClient\Author\Api\Model\ResourceData;
use GisClient\Author\Api\Validation\DtoValidator;
use GisClient\Author\Api\Validation\EntityValidator;
use GisClient\Author\Persistence\Entity;
use GisClient\Author\Persistence\EntityQuery;
use GisClient\Author\Persistence\EntityRef;

class ApiCrudService
{
    /**
     * @var AuthorEntityRepositoryInterface
     */
    private $repository;

    /**
     * @var EntityValidator
     */
    private $entityValidator;

    /**
     * @var DtoValidator
     */
    private $dtoValidator;

    /**
     * @var DtoToEntityMapper
     */
    private $dtoToEntityMapper;

    /**
     * @var ResourceQueryMapper
     */
    private $resourceQueryMapper;

    public function __construct(
        AuthorEntityRepositoryInterface $repository,
        EntityValidator $entityValidator,
        ?DtoValidator $dtoValidator = null,
        ?DtoToEntityMapper $dtoToEntityMapper = null,
        ?ResourceQueryMapper $resourceQueryMapper = null
    ) {
        $this->repository = $repository;
        $this->entityValidator = $entityValidator;
        $this->dtoValidator = $dtoValidator ?: new DtoValidator();
        $this->dtoToEntityMapper = $dtoToEntityMapper ?: new DtoToEntityMapper();
        $this->resourceQueryMapper = $resourceQueryMapper ?: new ResourceQueryMapper();
    }

    /**
     * @param string $entity
     * @return ResourceCollectionData
     */
    public function listResources($entity, array $query)
    {
        $schema = DtoSchemaRegistry::schemaForType((string) $entity);
        $queryOptions = $this->buildQueryOptions($schema, $query);
        $result = $this->repository->findAll($queryOptions);

        return new ResourceCollectionData(
            $schema,
            $result->getRows(),
            $result->getTotal(),
            $result->getLimit(),
            $result->getOffset()
        );
    }

    /**
     * @param string $entity
     * @param mixed $id
     * @return ResourceData
     */
    public function getResource($entity, $id)
    {
        $schema = DtoSchemaRegistry::schemaForType((string) $entity);
        $row = $this->repository->findById(new EntityRef((string) $entity, $id));
        if ($row === null) {
            throw new ApiException(404, 'resource_not_found', 'Not Found', sprintf("%s '%s' not found", $entity, $id));
        }

        return new ResourceData($schema, $row);
    }

    /**
     * @param string $entity
     * @return ResourceData
     */
    public function createResource($entity, JsonApiDto $dto)
    {
        $schema = DtoSchemaRegistry::schemaForType((string) $entity);
        $this->validateWriteDto($schema, $dto, true, false);
        $entityModel = $this->dtoToEntityMapper->mapForCreate((string) $entity, $dto);
        $this->entityValidator->validate($entityModel);
        $this->assertNoDuplicatePrimaryKeyOnCreate($entityModel);
        $created = $this->repository->create($entityModel);

        return new ResourceData($schema, $created);
    }

    /**
     * @param string $entity
     * @param mixed $id
     * @return ResourceData
     */
    public function updateResource($entity, $id, JsonApiDto $dto)
    {
        $schema = DtoSchemaRegistry::schemaForType((string) $entity);
        $this->validateWriteDto($schema, $dto, false, true);
        $current = $this->repository->findById(new EntityRef((string) $entity, $id));
        if ($current === null) {
            throw new ApiException(404, 'resource_not_found', 'Not Found', sprintf("%s '%s' not found", $entity, $id));
        }

        $entityModel = $this->dtoToEntityMapper->mapForUpdate((string) $entity, $id, $dto, true);
        $this->entityValidator->validate($entityModel);
        $updated = $this->repository->update($entityModel);

        return new ResourceData($schema, $updated);
    }

    /**
     * @param string $entity
     * @param mixed $id
     */
    public function deleteResource($entity, $id)
    {
        $ref = new EntityRef((string) $entity, $id);
        $current = $this->repository->findById($ref);
        if ($current === null) {
            throw new ApiException(404, 'resource_not_found', 'Not Found', sprintf("%s '%s' not found", $entity, $id));
        }

        $this->repository->delete($ref);
    }

    /**
     * @return EntityQuery
     */
    public function buildQueryOptions(ResourceSchema $schema, array $query)
    {
        return $this->resourceQueryMapper->map($schema, $query);
    }

    private function assertNoDuplicatePrimaryKeyOnCreate(Entity $entity): void
    {
        if ($entity->getId() === null) {
            return;
        }

        $existing = $this->repository->findById(new EntityRef($entity->getType(), $entity->getId()));
        if ($existing !== null) {
            throw new ApiException(
                409,
                'duplicate_primary_key',
                'Conflict',
                sprintf("Resource with primary key '%s' already exists", (string) $entity->getId()),
                '/data/id'
            );
        }
    }

    private function validateWriteDto(ResourceSchema $schema, JsonApiDto $dto, bool $isCreate = false, bool $isPut = false): void
    {
        if ($dto::schema()->getType() !== $schema->getType()) {
            throw new ApiException(
                422,
                'type_mismatch',
                'Type Mismatch',
                sprintf("Payload data.type must be '%s'", $schema->getType()),
                '/data/type'
            );
        }

        $this->dtoValidator->validate($dto, $isCreate, $isPut);
    }
}
