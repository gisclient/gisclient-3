<?php

namespace GisClient\Author\Api\Service;

use GisClient\Author\Api\Contract\AuthorEntityRepositoryInterface;
use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;
use GisClient\Author\Api\Dto\Support\DtoPropertyAccessor;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Model\QueryOptions;
use GisClient\Author\Api\Model\ResourceCollectionData;
use GisClient\Author\Api\Model\ResourceData;
use GisClient\Author\Api\Validation\DtoValidator;
use GisClient\Author\Api\Validation\PersistenceWriteValidator;
use GisClient\Author\Persistence\EntitySchema;
use GisClient\Author\Persistence\EntitySchemaRegistry;

class ApiCrudService
{
    /**
     * @var AuthorEntityRepositoryInterface
     */
    private $repository;

    /**
     * @var PersistenceWriteValidator
     */
    private $persistenceWriteValidator;

    /**
     * @var DtoValidator
     */
    private $dtoValidator;

    public function __construct(
        AuthorEntityRepositoryInterface $repository,
        PersistenceWriteValidator $persistenceWriteValidator,
        ?DtoValidator $dtoValidator = null
    ) {
        $this->repository = $repository;
        $this->persistenceWriteValidator = $persistenceWriteValidator;
        $this->dtoValidator = $dtoValidator ?: new DtoValidator();
    }

    /**
     * @param string $entity
     * @return ResourceCollectionData
     */
    public function listResources($entity, array $query)
    {
        $schema = DtoSchemaRegistry::schemaForType((string) $entity);
        $entitySchema = EntitySchemaRegistry::schemaForType((string) $entity);
        $queryOptions = $this->buildQueryOptions($schema, $query);
        $result = $this->repository->findAll($entitySchema, $queryOptions);

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
        $entitySchema = EntitySchemaRegistry::schemaForType((string) $entity);
        $row = $this->repository->findById($entitySchema, $id);
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
        $entitySchema = EntitySchemaRegistry::schemaForType((string) $entity);
        $this->validateWriteDto($schema, $entitySchema, $dto, true, false);
        $attributes = $this->extractAttributes($schema, $entitySchema, $dto, true, false);
        $this->validateReferences($schema, $entitySchema, $dto, $attributes);
        $this->assertNoDuplicatePrimaryKeyOnCreate($entitySchema, $dto, $attributes);
        $created = $this->repository->create($entitySchema, $attributes);

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
        $entitySchema = EntitySchemaRegistry::schemaForType((string) $entity);
        $this->validateWriteDto($schema, $entitySchema, $dto, false, true);
        $current = $this->repository->findById($entitySchema, $id);
        if ($current === null) {
            throw new ApiException(404, 'resource_not_found', 'Not Found', sprintf("%s '%s' not found", $entity, $id));
        }

        $attributes = $this->extractAttributes($schema, $entitySchema, $dto, false, true);
        $this->validateReferences($schema, $entitySchema, $dto, $attributes);
        $updated = $this->repository->update($entitySchema, $id, $attributes);

        return new ResourceData($schema, $updated);
    }

    /**
     * @param string $entity
     * @param mixed $id
     */
    public function deleteResource($entity, $id)
    {
        $entitySchema = EntitySchemaRegistry::schemaForType((string) $entity);
        $current = $this->repository->findById($entitySchema, $id);
        if ($current === null) {
            throw new ApiException(404, 'resource_not_found', 'Not Found', sprintf("%s '%s' not found", $entity, $id));
        }

        $this->repository->delete($entitySchema, $id);
    }

    /**
     * @return QueryOptions
     */
    public function buildQueryOptions(ResourceSchema $schema, array $query)
    {
        $entitySchema = EntitySchemaRegistry::schemaForType($schema->getType());
        $limit = isset($query['limit']) ? (int) $query['limit'] : 50;
        if ($limit < 1) {
            $limit = 1;
        }
        if ($limit > 200) {
            $limit = 200;
        }

        $offset = isset($query['offset']) ? (int) $query['offset'] : 0;
        if ($offset < 0) {
            $offset = 0;
        }

        $sortRaw = $query['sort'] ?? null;
        $sortField = null;
        $sortDirection = 'ASC';
        if (!empty($sortRaw)) {
            if (strpos($sortRaw, '-') === 0) {
                $sortDirection = 'DESC';
                $sortField = substr($sortRaw, 1);
            } else {
                $sortField = $sortRaw;
            }
            if (!in_array($sortField, $schema->getSortableFields(), true)) {
                throw new ApiException(400, 'invalid_sort_field', 'Invalid Sort Field', sprintf("Sort field '%s' is not allowed", $sortField), '/sort');
            }

            $sortField = $entitySchema->translateSortField($sortField);
        }

        $filters = $query['filter'] ?? [];
        if (!is_array($filters)) {
            throw new ApiException(400, 'invalid_filters', 'Invalid Filters', 'filter must be an object of field/value pairs', '/filter');
        }
        $filters = $this->normalizeFilterAliases($entitySchema, $filters);

        foreach ($filters as $field => $value) {
            if (!in_array($field, $schema->getFilterableFields(), true)) {
                throw new ApiException(400, 'invalid_filter_field', 'Invalid Filter Field', sprintf("Filter field '%s' is not allowed", $field), '/filter/' . $field);
            }
        }

        $translatedFilters = [];
        foreach ($filters as $field => $value) {
            $translatedFilters[$entitySchema->translateFilterField($field)] = $value;
        }

        return new QueryOptions($limit, $offset, $sortField, $sortDirection, $translatedFilters);
    }

    /**
     * @param bool $isCreate
     * @param bool $isPut
     * @return array
     */
    private function extractAttributes(ResourceSchema $schema, EntitySchema $entitySchema, JsonApiDto $dto, $isCreate, $isPut)
    {
        $attributes = $this->mergeRelationshipLocalKeysIntoAttributes(
            $entitySchema,
            $dto,
            $this->extractDtoAttributes($schema, $entitySchema, $dto)
        );

        return $this->persistenceWriteValidator->validateAndNormalize(
            $schema,
            $entitySchema,
            $attributes,
            $dto->getId(),
            $isCreate,
            $isPut
        );
    }

    private function assertNoDuplicatePrimaryKeyOnCreate(EntitySchema $schema, JsonApiDto $dto, array $attributes): void
    {
        if ($dto->getId() === null) {
            return;
        }

        $primaryKey = $schema->getPrimaryKey();
        if (!array_key_exists($primaryKey, $attributes)) {
            return;
        }

        $existing = $this->repository->findById($schema, $attributes[$primaryKey]);
        if ($existing !== null) {
            throw new ApiException(
                409,
                'duplicate_primary_key',
                'Conflict',
                sprintf("Resource with primary key '%s' already exists", (string) $attributes[$primaryKey]),
                '/data/id'
            );
        }
    }

    /**
     * @param array<string,mixed> $attributes
     * @return array<string,mixed>
     */
    private function mergeRelationshipLocalKeysIntoAttributes(EntitySchema $entitySchema, JsonApiDto $dto, array $attributes)
    {
        foreach ($dto::schema()->getRelationships() as $relationshipName => $relationship) {
            $localKey = $entitySchema->getRelationshipColumn($relationshipName);
            if ($localKey === null || !$dto->isPresent($relationshipName)) {
                continue;
            }

            $relationshipData = $this->getRelationshipIdentifier($dto::schema(), $dto, $relationshipName);
            if ($relationshipData === null || $relationshipData['id'] === null) {
                continue;
            }

            $relationshipId = (string) $relationshipData['id'];
            if ($relationshipId === '') {
                continue;
            }

            if (
                array_key_exists($localKey, $attributes) &&
                $attributes[$localKey] !== null &&
                (string) $attributes[$localKey] !== $relationshipId
            ) {
                throw new ApiException(
                    422,
                    'relationship_attribute_mismatch',
                    'Relationship Attribute Mismatch',
                    sprintf("Attribute '%s' must match relationship '%s' id", $localKey, $relationshipName),
                    '/data/attributes/' . $localKey
                );
            }

            $attributes[$localKey] = $relationshipId;
        }

        return $attributes;
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    private function normalizeFilterAliases(EntitySchema $entitySchema, array $filters)
    {
        $normalized = $filters;
        $resourceSchema = DtoSchemaRegistry::schemaForType($entitySchema->getType());
        foreach ($resourceSchema->getRelationships() as $relationshipName => $relationship) {
            $localKey = $entitySchema->getRelationshipColumn($relationshipName);
            if ($localKey === null || !array_key_exists($relationshipName, $normalized)) {
                continue;
            }

            $aliasValue = $normalized[$relationshipName];
            if (array_key_exists($localKey, $normalized) && (string) $normalized[$localKey] !== (string) $aliasValue) {
                throw new ApiException(
                    400,
                    'ambiguous_filter_alias',
                    'Invalid Filters',
                    sprintf("Filter '%s' conflicts with filter '%s'", $relationshipName, $localKey),
                    '/filter/' . $relationshipName
                );
            }

            $normalized[$localKey] = $aliasValue;
            unset($normalized[$relationshipName]);
        }

        return $normalized;
    }

    private function validateReferences(ResourceSchema $schema, EntitySchema $entitySchema, JsonApiDto $dto, array $attributes): void
    {
        $this->persistenceWriteValidator->validateReferences(
            $schema,
            $entitySchema,
            $attributes,
            $this->extractRelationshipIdentifiers($schema, $dto)
        );
    }

    private function validateWriteDto(ResourceSchema $schema, EntitySchema $entitySchema, JsonApiDto $dto, bool $isCreate = false, bool $isPut = false): void
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

    /**
     * @return array<string,mixed>
     */
    private function extractDtoAttributes(ResourceSchema $schema, EntitySchema $entitySchema, JsonApiDto $dto): array
    {
        $attributes = [];

        foreach ($schema->getAttributes() as $field) {
            if (!$dto->isPresent($field->getJsonApiName()) || !DtoPropertyAccessor::isInitialized($dto, $field->getPropertyName())) {
                continue;
            }

            $column = $entitySchema->getAttributeColumn($field->getJsonApiName()) ?? $field->getJsonApiName();
            $attributes[$column] = DtoPropertyAccessor::get($dto, $field->getPropertyName());
        }

        return $attributes;
    }

    /**
     * @return array<string,array{type:?string,id:string|int|null}|null>
     */
    private function extractRelationshipIdentifiers(ResourceSchema $schema, JsonApiDto $dto): array
    {
        $relationships = [];

        foreach ($schema->getRelationships() as $relationshipName => $relationship) {
            if (!$dto->isPresent($relationshipName)) {
                continue;
            }

            $relationships[$relationshipName] = $this->getRelationshipIdentifier($schema, $dto, $relationshipName);
        }

        return $relationships;
    }

    /**
     * @return array{type:?string,id:string|int|null}|null
     */
    private function getRelationshipIdentifier(ResourceSchema $schema, JsonApiDto $dto, string $relationshipName): ?array
    {
        $relationship = $schema->getRelationship($relationshipName);
        if ($relationship === null) {
            return null;
        }

        if (!DtoPropertyAccessor::isInitialized($dto, $relationship->getPropertyName())) {
            return [
                'type' => $relationship->getTargetType(),
                'id' => null,
            ];
        }

        $relatedDto = DtoPropertyAccessor::get($dto, $relationship->getPropertyName());
        if ($relatedDto === null) {
            return [
                'type' => $relationship->getTargetType(),
                'id' => null,
            ];
        }

        return [
            'type' => $relationship->getTargetType(),
            'id' => DtoPropertyAccessor::isInitialized($relatedDto, 'id')
                ? DtoPropertyAccessor::get($relatedDto, 'id')
                : null,
        ];
    }
}
