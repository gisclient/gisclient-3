<?php

namespace GisClient\Author\Api\Service;

use GisClient\Author\Api\Contract\AuthorEntityRepositoryInterface;
use GisClient\Author\Api\Contract\EntityDefinitionProviderInterface;
use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\Support\DtoPropertyAccessor;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\Model\EntityDefinition;
use GisClient\Author\Api\Model\QueryOptions;
use GisClient\Author\Api\Model\ResourceCollectionData;
use GisClient\Author\Api\Model\ResourceData;
use GisClient\Author\Api\Validation\DtoValidator;
use GisClient\Author\Api\Validation\PersistenceWriteValidator;

class ApiCrudService
{
    /**
     * @var EntityDefinitionProviderInterface
     */
    private $definitionProvider;

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
        EntityDefinitionProviderInterface $definitionProvider,
        AuthorEntityRepositoryInterface $repository,
        PersistenceWriteValidator $persistenceWriteValidator,
        ?DtoValidator $dtoValidator = null
    ) {
        $this->definitionProvider = $definitionProvider;
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
        $definition = $this->definitionProvider->getEntityDefinition($entity);
        $queryOptions = $this->buildQueryOptions($definition, $query);
        $result = $this->repository->findAll($definition, $queryOptions);

        return new ResourceCollectionData(
            $definition,
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
        $definition = $this->definitionProvider->getEntityDefinition($entity);
        $row = $this->repository->findById($definition, $id);
        if ($row === null) {
            throw new ApiException(404, 'resource_not_found', 'Not Found', sprintf("%s '%s' not found", $entity, $id));
        }

        return new ResourceData($definition, $row);
    }

    /**
     * @param string $entity
     * @return ResourceData
     */
    public function createResource($entity, $payload)
    {
        $definition = $this->definitionProvider->getEntityDefinition($entity);
        $dto = $this->normalizeWriteDto($definition, $payload, true, false);
        $attributes = $this->extractAttributes($definition, $dto, true, false);
        $attributes = $this->mergeRelationshipLocalKeysIntoAttributes($definition, $dto, $attributes);
        $this->validateReferences($definition, $dto, $attributes);
        $this->assertNoDuplicatePrimaryKeyOnCreate($definition, $dto, $attributes);
        $created = $this->repository->create($definition, $attributes);

        return new ResourceData($definition, $created);
    }

    /**
     * @param string $entity
     * @param mixed $id
     * @return ResourceData
     */
    public function updateResource($entity, $id, $payload)
    {
        $definition = $this->definitionProvider->getEntityDefinition($entity);
        $dto = $this->normalizeWriteDto($definition, $payload, false, true);
        $current = $this->repository->findById($definition, $id);
        if ($current === null) {
            throw new ApiException(404, 'resource_not_found', 'Not Found', sprintf("%s '%s' not found", $entity, $id));
        }

        $attributes = $this->extractAttributes($definition, $dto, false, true);
        $attributes = $this->mergeRelationshipLocalKeysIntoAttributes($definition, $dto, $attributes);
        $this->validateReferences($definition, $dto, $attributes);
        $updated = $this->repository->update($definition, $id, $attributes);

        return new ResourceData($definition, $updated);
    }

    /**
     * @param string $entity
     * @param mixed $id
     */
    public function deleteResource($entity, $id)
    {
        $definition = $this->definitionProvider->getEntityDefinition($entity);
        $current = $this->repository->findById($definition, $id);
        if ($current === null) {
            throw new ApiException(404, 'resource_not_found', 'Not Found', sprintf("%s '%s' not found", $entity, $id));
        }

        $this->repository->delete($definition, $id);
    }

    /**
     * @return QueryOptions
     */
    public function buildQueryOptions(EntityDefinition $definition, array $query)
    {
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
            if (!in_array($sortField, $definition->getSortableFields(), true)) {
                throw new ApiException(400, 'invalid_sort_field', 'Invalid Sort Field', sprintf("Sort field '%s' is not allowed", $sortField), '/sort');
            }
        }

        $filters = $query['filter'] ?? [];
        if (!is_array($filters)) {
            throw new ApiException(400, 'invalid_filters', 'Invalid Filters', 'filter must be an object of field/value pairs', '/filter');
        }
        $filters = $this->normalizeFilterAliases($definition, $filters);

        foreach ($filters as $field => $value) {
            if (!in_array($field, $definition->getFilterableFields(), true)) {
                throw new ApiException(400, 'invalid_filter_field', 'Invalid Filter Field', sprintf("Filter field '%s' is not allowed", $field), '/filter/' . $field);
            }
        }

        return new QueryOptions($limit, $offset, $sortField, $sortDirection, $filters);
    }

    /**
     * @param bool $isCreate
     * @param bool $isPut
     * @return array
     */
    private function extractAttributes(EntityDefinition $definition, JsonApiDto $dto, $isCreate, $isPut)
    {
        $attributes = $this->extractDtoAttributes($dto);

        return $this->persistenceWriteValidator->validateAndNormalize(
            $definition,
            $attributes,
            $dto->getId(),
            $isCreate,
            $isPut,
            $this->extractRequiredFieldsSatisfiedByRelationships($definition, $dto)
        );
    }

    /**
     * @param array<string,mixed> $attributes
     */
    private function assertNoDuplicatePrimaryKeyOnCreate(EntityDefinition $definition, JsonApiDto $dto, array $attributes)
    {
        if ($dto->getId() === null) {
            return;
        }

        $primaryKey = $definition->getPrimaryKey();
        if (!array_key_exists($primaryKey, $attributes)) {
            return;
        }

        $existing = $this->repository->findById($definition, $attributes[$primaryKey]);
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
    private function mergeRelationshipLocalKeysIntoAttributes(EntityDefinition $definition, JsonApiDto $dto, array $attributes)
    {
        foreach ($definition->getRelationships() as $relationshipName => $relationship) {
            if (!is_string($relationshipName) || !is_array($relationship)) {
                continue;
            }

            $localKey = $relationship['local_key'] ?? null;
            if (!is_string($localKey) || !$dto->isPresent($relationshipName)) {
                continue;
            }

            $relationshipData = $this->getRelationshipIdentifier($definition, $dto, $relationshipName);
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
     * @return array<int,string>
     */
    private function extractRequiredFieldsSatisfiedByRelationships(EntityDefinition $definition, JsonApiDto $dto)
    {
        $fields = [];
        foreach ($definition->getRelationships() as $relationshipName => $relationship) {
            if (!is_string($relationshipName) || !is_array($relationship)) {
                continue;
            }

            $localKey = $relationship['local_key'] ?? null;
            if (!is_string($localKey) || !$dto->isPresent($relationshipName)) {
                continue;
            }

            $relationshipData = $this->getRelationshipIdentifier($definition, $dto, $relationshipName);
            if ($relationshipData === null || $relationshipData['id'] === null) {
                continue;
            }

            if (trim((string) $relationshipData['id']) === '') {
                continue;
            }

            $fields[] = $localKey;
        }

        return array_values(array_unique($fields));
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    private function normalizeFilterAliases(EntityDefinition $definition, array $filters)
    {
        $normalized = $filters;
        foreach ($definition->getRelationships() as $relationshipName => $relationship) {
            if (!is_string($relationshipName) || !is_array($relationship)) {
                continue;
            }
            $localKey = $relationship['local_key'] ?? null;
            if (!is_string($localKey) || !array_key_exists($relationshipName, $normalized)) {
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

    /**
     * @param array<string,mixed> $attributes
     */
    private function validateReferences(EntityDefinition $definition, JsonApiDto $dto, array $attributes): void
    {
        $errors = [];
        $this->validateRelationshipReferences($definition, $dto, $attributes, $errors);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $this->persistenceWriteValidator->validateAttributeReferences(
            $this->definitionWithResolvedLookupFilters($definition, $attributes),
            $attributes
        );
    }

    /**
     * @param array<string,mixed> $attributes
     * @param array<int,array<string,mixed>> $errors
     */
    private function validateRelationshipReferences(EntityDefinition $definition, JsonApiDto $dto, array $attributes, array &$errors): void
    {
        foreach ($definition->getRelationships() as $relationshipName => $relationship) {
            if (!is_string($relationshipName) || !is_array($relationship) || !$dto->isPresent($relationshipName)) {
                continue;
            }

            $relationshipData = $this->getRelationshipIdentifier($definition, $dto, $relationshipName);
            if ($relationshipData === null || $relationshipData['id'] === null) {
                continue;
            }

            $targetType = $relationship['type'] ?? null;
            if (!is_string($targetType) || trim($targetType) === '') {
                continue;
            }

            $targetDefinition = $this->definitionProvider->getEntityDefinition($targetType);

            if ($this->repository->findById($targetDefinition, $relationshipData['id']) !== null) {
                continue;
            }

            $errors[] = [
                'status' => '422',
                'code' => 'invalid_relationship',
                'title' => 'Invalid Relationship',
                'detail' => sprintf("Relationship '%s' references an unknown resource", $relationshipName),
                'source' => [
                    'pointer' => '/data/relationships/' . $relationshipName . '/data/id',
                ],
            ];
        }
    }

    /**
     * @param array<string,mixed> $attributes
     */
    private function definitionWithResolvedLookupFilters(EntityDefinition $definition, array $attributes): EntityDefinition
    {
        $attributeRules = $definition->getAttributeRules();
        foreach ($attributeRules as $field => $rule) {
            if (!is_string($field) || !is_array($rule) || !isset($rule['lookup']) || !is_array($rule['lookup'])) {
                continue;
            }

            $attributeRules[$field]['lookup'] = $this->resolveLookupRuleFilters($rule['lookup'], $attributes);
        }

        return new EntityDefinition(
            $definition->getType(),
            $definition->getSchema(),
            $definition->getTable(),
            $definition->getPrimaryKey(),
            $definition->getIdType(),
            $definition->getReadableFields(),
            $definition->getWritableFields(),
            $definition->getRequiredOnCreate(),
            $definition->getRequiredOnPut(),
            $definition->getFilterableFields(),
            $definition->getSortableFields(),
            $definition->getDefaultSort(),
            $attributeRules,
            $definition->getRelationships(),
            $definition->getRequiredRelationshipsOnWrite()
        );
    }

    /**
     * @param array<string,mixed> $lookupRule
     * @param array<string,mixed> $attributes
     * @return array<string,mixed>
     */
    private function resolveLookupRuleFilters(array $lookupRule, array $attributes): array
    {
        $filters = $lookupRule['filters'] ?? null;
        if (!is_array($filters)) {
            return $lookupRule;
        }

        $resolved = [];
        foreach ($filters as $column => $value) {
            if (!is_string($column)) {
                continue;
            }

            if (is_string($value) && strpos($value, 'from_attribute:') === 0) {
                $attributeName = substr($value, strlen('from_attribute:'));
                if ($attributeName === '' || !array_key_exists($attributeName, $attributes) || $attributes[$attributeName] === null) {
                    $lookupRule['skip_lookup'] = true;
                    continue;
                }
                $resolved[$column] = $attributes[$attributeName];
                continue;
            }

            $resolved[$column] = $value;
        }

        $lookupRule['resolved_filters'] = $resolved;

        return $lookupRule;
    }

    /**
     * @param mixed $payload
     * @return JsonApiDto
     */
    private function normalizeWriteDto(EntityDefinition $definition, $payload, bool $isCreate = false, bool $isPut = false)
    {
        if (!$payload instanceof JsonApiDto) {
            throw new ApiException(
                400,
                'invalid_payload',
                'Invalid Payload',
                'Write operations require a DTO payload'
            );
        }

        if ($payload::schema()->getType() !== $definition->getType()) {
            throw new ApiException(
                422,
                'type_mismatch',
                'Type Mismatch',
                sprintf("Payload data.type must be '%s'", $definition->getType()),
                '/data/type'
            );
        }

        $this->dtoValidator->validate($payload, $isCreate, $isPut);

        return $payload;
    }

    /**
     * @return array<string,mixed>
     */
    private function extractDtoAttributes(JsonApiDto $dto): array
    {
        $schema = $dto::schema();
        $attributes = [];

        foreach ($schema->getAttributes() as $field) {
            if (!$dto->isPresent($field->getJsonApiName()) || !DtoPropertyAccessor::isInitialized($dto, $field->getPropertyName())) {
                continue;
            }

            $attributes[$field->getJsonApiName()] = DtoPropertyAccessor::get($dto, $field->getPropertyName());
        }

        return $attributes;
    }

    /**
     * @return array{type:?string,id:string|int|null}|null
     */
    private function getRelationshipIdentifier(EntityDefinition $definition, JsonApiDto $dto, string $relationshipName): ?array
    {
        $relationship = $definition->getRelationships()[$relationshipName] ?? null;
        $schemaRelationship = $dto::schema()->getRelationship($relationshipName);
        if (!is_array($relationship) || $schemaRelationship === null) {
            return null;
        }

        if (!DtoPropertyAccessor::isInitialized($dto, $schemaRelationship->getPropertyName())) {
            return [
                'type' => $relationship['type'] ?? null,
                'id' => null,
            ];
        }

        $relatedDto = DtoPropertyAccessor::get($dto, $schemaRelationship->getPropertyName());
        if ($relatedDto === null) {
            return [
                'type' => $relationship['type'] ?? null,
                'id' => null,
            ];
        }

        return [
            'type' => $relationship['type'] ?? null,
            'id' => DtoPropertyAccessor::isInitialized($relatedDto, 'id')
                ? DtoPropertyAccessor::get($relatedDto, 'id')
                : null,
        ];
    }
}
