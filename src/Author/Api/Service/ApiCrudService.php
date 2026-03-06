<?php

namespace GisClient\Author\Api\Service;

use GisClient\Author\Api\Contract\AuthorEntityRepositoryInterface;
use GisClient\Author\Api\Contract\EntityDefinitionProviderInterface;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Model\EntityDefinition;
use GisClient\Author\Api\Model\QueryOptions;
use GisClient\Author\Api\Validation\PayloadValidator;

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
     * @var PayloadValidator
     */
    private $payloadValidator;

    public function __construct(
        EntityDefinitionProviderInterface $definitionProvider,
        AuthorEntityRepositoryInterface $repository,
        PayloadValidator $payloadValidator
    ) {
        $this->definitionProvider = $definitionProvider;
        $this->repository = $repository;
        $this->payloadValidator = $payloadValidator;
    }

    /**
     * @param string $entity
     * @return array
     */
    public function listResources($entity, array $query, array $scope = [])
    {
        $this->assertAdmin();

        $definition = $this->definitionProvider->getEntityDefinition($entity);
        $scope = $this->normalizeScope($definition, $scope);
        $queryOptions = $this->buildQueryOptions($definition, $query);
        $queryOptions = $this->applyScopeToQueryOptions($queryOptions, $scope);
        $result = $this->repository->findAll($definition, $queryOptions, $scope);

        $data = [];
        foreach ($result->getRows() as $row) {
            $data[] = $this->resourceObject($definition, $row);
        }

        return [
            'data' => $data,
            'meta' => [
                'total' => $result->getTotal(),
                'limit' => $result->getLimit(),
                'offset' => $result->getOffset(),
            ],
        ];
    }

    /**
     * @param string $entity
     * @param mixed $id
     * @return array
     */
    public function getResource($entity, $id, array $query = [], array $scope = [])
    {
        $this->assertAdmin();

        $definition = $this->definitionProvider->getEntityDefinition($entity);
        $scope = $this->normalizeScope($definition, $scope);
        $row = $this->repository->findById($definition, $id, $scope);
        if ($row === null) {
            throw new ApiException(404, 'resource_not_found', 'Not Found', sprintf("%s '%s' not found", $entity, $id));
        }

        return [
            'data' => $this->resourceObject($definition, $row),
        ];
    }

    /**
     * @param string $entity
     * @return array
     */
    public function createResource($entity, array $payload, array $scope = [])
    {
        $this->assertAdmin();

        $definition = $this->definitionProvider->getEntityDefinition($entity);
        $scope = $this->normalizeScope($definition, $scope);
        $payload = $this->applyScopeToPayload($definition, $payload, $scope);
        $attributes = $this->extractAttributes($definition, $payload, true, false);
        $attributes = $this->applyScopeToAttributes($definition, $attributes, $scope);
        $this->assertNoDuplicatePrimaryKeyOnCreate($definition, $payload, $attributes, $scope);
        $created = $this->repository->create($definition, $attributes);

        return [
            'data' => $this->resourceObject($definition, $created),
        ];
    }

    /**
     * @param string $entity
     * @param mixed $id
     * @return array
     */
    public function updateResource($entity, $id, array $payload, array $scope = [])
    {
        $this->assertAdmin();

        $definition = $this->definitionProvider->getEntityDefinition($entity);
        $scope = $this->normalizeScope($definition, $scope);
        $current = $this->repository->findById($definition, $id, $scope);
        if ($current === null) {
            throw new ApiException(404, 'resource_not_found', 'Not Found', sprintf("%s '%s' not found", $entity, $id));
        }

        $payload = $this->applyScopeToPayload($definition, $payload, $scope);
        $attributes = $this->extractAttributes($definition, $payload, false, true);
        $attributes = $this->applyScopeToAttributes($definition, $attributes, $scope);
        $updated = $this->repository->update($definition, $id, $attributes, $scope);

        return [
            'data' => $this->resourceObject($definition, $updated),
        ];
    }

    /**
     * @param string $entity
     * @param mixed $id
     */
    public function deleteResource($entity, $id, array $scope = [])
    {
        $this->assertAdmin();

        $definition = $this->definitionProvider->getEntityDefinition($entity);
        $scope = $this->normalizeScope($definition, $scope);
        $current = $this->repository->findById($definition, $id, $scope);
        if ($current === null) {
            throw new ApiException(404, 'resource_not_found', 'Not Found', sprintf("%s '%s' not found", $entity, $id));
        }

        $this->repository->delete($definition, $id, $scope);
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
    private function extractAttributes(EntityDefinition $definition, array $payload, $isCreate, $isPut)
    {
        return $this->payloadValidator->validateAndNormalize($definition, $payload, $isCreate, $isPut);
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $attributes
     */
    private function assertNoDuplicatePrimaryKeyOnCreate(EntityDefinition $definition, array $payload, array $attributes, array $scope)
    {
        if (!isset($payload['data']) || !is_array($payload['data']) || !array_key_exists('id', $payload['data'])) {
            return;
        }

        $primaryKey = $definition->getPrimaryKey();
        if (!array_key_exists($primaryKey, $attributes)) {
            return;
        }

        $existing = $this->repository->findById($definition, $attributes[$primaryKey], $scope);
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
     * @return array
     */
    private function resourceObject(EntityDefinition $definition, array $row)
    {
        $primaryKey = $definition->getPrimaryKey();
        if (!array_key_exists($primaryKey, $row)) {
            throw new ApiException(500, 'invalid_resource', 'Invalid Resource', sprintf("Primary key '%s' missing in resource row", $primaryKey));
        }

        $resource = [
            'type' => $definition->getType(),
            'id' => (string) $row[$primaryKey],
            'attributes' => [],
        ];

        $relationshipKeys = [];
        $relationships = $this->buildRelationships($definition, $row);
        if (count($relationships) > 0) {
            $resource['relationships'] = $relationships;
            foreach ($definition->getRelationships() as $relationship) {
                if (isset($relationship['local_key']) && is_string($relationship['local_key'])) {
                    $relationshipKeys[$relationship['local_key']] = true;
                }
            }
        }

        foreach ($definition->getReadableFields() as $field) {
            if ($field === '*' || $field === $primaryKey || isset($relationshipKeys[$field])) {
                continue;
            }
            if (array_key_exists($field, $row)) {
                $resource['attributes'][$field] = $row[$field];
            }
        }

        return $resource;
    }

    protected function assertAdmin()
    {
        $auth = \GCApp::getAuthenticationHandler();
        if (!$auth->isAuthenticated()) {
            throw new ApiException(401, 'authentication_required', 'Unauthorized', 'Authentication is required');
        }
        if (!$auth->isAdmin()) {
            throw new ApiException(403, 'admin_required', 'Forbidden', 'Administrator permissions are required');
        }
    }

    /**
     * @param array<string,mixed> $scope
     * @return array<string,mixed>
     */
    private function normalizeScope(EntityDefinition $definition, array $scope)
    {
        $requiredScopeFields = $definition->getScopeFields();
        if (count($requiredScopeFields) === 0) {
            return [];
        }

        $normalized = [];
        foreach ($requiredScopeFields as $scopeField) {
            if (!array_key_exists($scopeField, $scope)) {
                throw new ApiException(400, 'missing_scope', 'Missing Scope', sprintf("Scope field '%s' is required", $scopeField));
            }
            $normalized[$scopeField] = $scope[$scopeField];
        }

        return $normalized;
    }

    /**
     * @param array<string,mixed> $scope
     * @return array<string,mixed>
     */
    private function applyScopeToAttributes(EntityDefinition $definition, array $attributes, array $scope)
    {
        foreach ($definition->getScopeFields() as $scopeField) {
            if (!array_key_exists($scopeField, $scope)) {
                continue;
            }
            if (array_key_exists($scopeField, $attributes) && (string) $attributes[$scopeField] !== (string) $scope[$scopeField]) {
                throw new ApiException(
                    422,
                    'scope_attribute_mismatch',
                    'Scope Mismatch',
                    sprintf("Attribute '%s' must match scoped value", $scopeField),
                    '/data/attributes/' . $scopeField
                );
            }
            $attributes[$scopeField] = $scope[$scopeField];
        }

        return $attributes;
    }

    /**
     * @param array<string,mixed> $scope
     * @return array<string,mixed>
     */
    private function applyScopeToPayload(EntityDefinition $definition, array $payload, array $scope)
    {
        if (!isset($payload['data']) || !is_array($payload['data'])) {
            return $payload;
        }

        $this->validateRequiredRelationships($definition, $payload, $scope);

        if (count($scope) === 0) {
            return $payload;
        }

        $attributes = $payload['data']['attributes'] ?? [];
        if (!is_array($attributes)) {
            return $payload;
        }

        foreach ($definition->getScopeFields() as $scopeField) {
            if (!array_key_exists($scopeField, $scope)) {
                continue;
            }
            if (array_key_exists($scopeField, $attributes) && (string) $attributes[$scopeField] !== (string) $scope[$scopeField]) {
                throw new ApiException(
                    422,
                    'scope_attribute_mismatch',
                    'Scope Mismatch',
                    sprintf("Attribute '%s' must match scoped value", $scopeField),
                    '/data/attributes/' . $scopeField
                );
            }
            $attributes[$scopeField] = $scope[$scopeField];
        }

        $payload['data']['attributes'] = $attributes;
        return $payload;
    }

    /**
     * @param array<string,mixed> $scope
     */
    private function validateRequiredRelationships(EntityDefinition $definition, array $payload, array $scope)
    {
        $required = $definition->getRequiredRelationshipsOnWrite();
        if (count($required) === 0) {
            return;
        }

        $relationshipsPayload = $payload['data']['relationships'] ?? null;
        foreach ($required as $relationshipName) {
            $pointer = '/data/relationships/' . $relationshipName . '/data';
            if (!is_array($relationshipsPayload) || !isset($relationshipsPayload[$relationshipName]) || !is_array($relationshipsPayload[$relationshipName])) {
                throw new ApiException(
                    422,
                    'missing_required_relationship',
                    'Missing Required Relationship',
                    sprintf("Relationship '%s' is required", $relationshipName),
                    $pointer
                );
            }

            $relationshipData = $relationshipsPayload[$relationshipName]['data'] ?? null;
            if (!is_array($relationshipData)) {
                throw new ApiException(
                    422,
                    'missing_required_relationship',
                    'Missing Required Relationship',
                    sprintf("Relationship '%s' data is required", $relationshipName),
                    $pointer
                );
            }

            $relationshipDefinition = $definition->getRelationships()[$relationshipName] ?? null;
            if (is_array($relationshipDefinition)) {
                $expectedType = $relationshipDefinition['type'] ?? null;
                $expectedId = null;
                if (isset($relationshipDefinition['local_key']) && is_string($relationshipDefinition['local_key']) && array_key_exists($relationshipDefinition['local_key'], $scope)) {
                    $expectedId = (string) $scope[$relationshipDefinition['local_key']];
                }

                if ($expectedType !== null && (($relationshipData['type'] ?? null) !== $expectedType)) {
                    throw new ApiException(
                        422,
                        'invalid_relationship',
                        'Invalid Relationship',
                        sprintf("Relationship '%s' type must be '%s'", $relationshipName, $expectedType),
                        $pointer . '/type'
                    );
                }
                if ($expectedId !== null && ((string) ($relationshipData['id'] ?? '')) !== $expectedId) {
                    throw new ApiException(
                        422,
                        'relationship_scope_mismatch',
                        'Relationship Scope Mismatch',
                        sprintf("Relationship '%s' id must match scoped project", $relationshipName),
                        $pointer . '/id'
                    );
                }
            }
        }
    }

    private function applyScopeToQueryOptions(QueryOptions $queryOptions, array $scope)
    {
        if (count($scope) === 0) {
            return $queryOptions;
        }

        return new QueryOptions(
            $queryOptions->getLimit(),
            $queryOptions->getOffset(),
            $queryOptions->getSortField(),
            $queryOptions->getSortDirection(),
            array_merge($queryOptions->getFilters(), $scope)
        );
    }

    /**
     * @return array<string,array{data:array<string,mixed>|null}>
     */
    private function buildRelationships(EntityDefinition $definition, array $row)
    {
        $relationships = [];
        foreach ($definition->getRelationships() as $name => $relationship) {
            $localKey = $relationship['local_key'] ?? null;
            $type = $relationship['type'] ?? null;
            if (!is_string($name) || !is_string($localKey) || !is_string($type)) {
                continue;
            }

            $data = null;
            if (array_key_exists($localKey, $row) && $row[$localKey] !== null) {
                $data = [
                    'type' => $type,
                    'id' => (string) $row[$localKey],
                ];
            }

            $relationships[$name] = [
                'data' => $data,
            ];
        }

        return $relationships;
    }
}
