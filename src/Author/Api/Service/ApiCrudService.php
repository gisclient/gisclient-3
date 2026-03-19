<?php

namespace GisClient\Author\Api\Service;

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\PagedResultDto;
use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Mapper\DtoToEntityMapper;
use GisClient\Author\Api\Mapper\EntityToDtoMapper;
use GisClient\Author\Api\Mapper\ResourceQueryMapper;
use GisClient\Author\Api\Persistence\EntityRepository;
use GisClient\Author\Api\Validation\DtoValidator;
use GisClient\Author\Api\Validation\EntityValidator;
use GisClient\Author\Persistence\EntityQuery;
use GisClient\Author\Persistence\EntityRef;

class ApiCrudService
{
    /**
     * @var EntityRepository
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

    /**
     * @var EntityToDtoMapper
     */
    private $entityToDtoMapper;

    public function __construct(
        EntityRepository $repository,
        EntityValidator $entityValidator,
        ?DtoValidator $dtoValidator = null,
        ?DtoToEntityMapper $dtoToEntityMapper = null,
        ?ResourceQueryMapper $resourceQueryMapper = null,
        ?EntityToDtoMapper $entityToDtoMapper = null
    ) {
        $this->repository = $repository;
        $this->entityValidator = $entityValidator;
        $this->dtoValidator = $dtoValidator ?: new DtoValidator();
        $this->dtoToEntityMapper = $dtoToEntityMapper ?: new DtoToEntityMapper();
        $this->resourceQueryMapper = $resourceQueryMapper ?: new ResourceQueryMapper();
        $this->entityToDtoMapper = $entityToDtoMapper ?: new EntityToDtoMapper();
    }

    /**
     * @param string $entity
     * @return PagedResultDto
     */
    public function listResources($entity, array $query)
    {
        $schema = DtoSchemaRegistry::schemaForType((string) $entity);
        $entityQuery = $this->buildEntityQuery($schema, $query);
        $result = $this->repository->findAll($entityQuery);
        $items = [];
        foreach ($result->getItems() as $item) {
            $items[] = $this->entityToDtoMapper->map($schema, $item);
        }

        return new PagedResultDto(
            $items,
            $result->getTotal(),
            $result->getLimit(),
            $result->getOffset()
        );
    }

    /**
     * @param string $entity
     * @param mixed $id
     * @return JsonApiDto
     */
    public function getResource($entity, $id)
    {
        $schema = DtoSchemaRegistry::schemaForType((string) $entity);
        $storedEntity = $this->repository->findById(new EntityRef((string) $entity, $id));
        if ($storedEntity === null) {
            throw new ApiException(404, 'resource_not_found', 'Not Found', sprintf("%s '%s' not found", $entity, $id));
        }

        return $this->entityToDtoMapper->map($schema, $storedEntity);
    }

    /**
     * @param string $entity
     * @return JsonApiDto
     */
    public function createResource($entity, JsonApiDto $dto)
    {
        $schema = DtoSchemaRegistry::schemaForType((string) $entity);
        $this->validateWriteDto($schema, $dto, true, false);
        $entityModel = $this->dtoToEntityMapper->mapForCreate((string) $entity, $dto);
        $this->entityValidator->validate($entityModel);
        $created = $this->repository->create($entityModel);

        return $this->entityToDtoMapper->map($schema, $created);
    }

    /**
     * @param string $entity
     * @param mixed $id
     * @return JsonApiDto
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

        return $this->entityToDtoMapper->map($schema, $updated);
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
    public function buildEntityQuery(ResourceSchema $schema, array $query)
    {
        return $this->resourceQueryMapper->map($schema, $query);
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
