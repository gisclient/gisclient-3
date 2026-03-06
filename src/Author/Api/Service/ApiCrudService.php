<?php

namespace GisClient\Author\Api\Service;

use GisClient\Author\Api\Contract\AuthorEntityRepositoryInterface;
use GisClient\Author\Api\Contract\EntityDefinitionProviderInterface;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Model\EntityDefinition;
use GisClient\Author\Api\Model\QueryOptions;

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

    public function __construct(
        EntityDefinitionProviderInterface $definitionProvider,
        AuthorEntityRepositoryInterface $repository
    ) {
        $this->definitionProvider = $definitionProvider;
        $this->repository = $repository;
    }

    /**
     * @param string $entity
     * @return array
     */
    public function listResources($entity, array $query)
    {
        $this->assertAdmin();

        $definition = $this->definitionProvider->getEntityDefinition($entity);
        $queryOptions = $this->buildQueryOptions($definition, $query);
        $result = $this->repository->findAll($definition, $queryOptions);

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
    public function getResource($entity, $id, array $query = [])
    {
        $this->assertAdmin();

        $definition = $this->definitionProvider->getEntityDefinition($entity);
        $row = $this->repository->findById($definition, $id);
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
    public function createResource($entity, array $payload)
    {
        $this->assertAdmin();

        $definition = $this->definitionProvider->getEntityDefinition($entity);
        $attributes = $this->extractAttributes($definition, $payload, true, false);
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
    public function updateResource($entity, $id, array $payload)
    {
        $this->assertAdmin();

        $definition = $this->definitionProvider->getEntityDefinition($entity);
        $current = $this->repository->findById($definition, $id);
        if ($current === null) {
            throw new ApiException(404, 'resource_not_found', 'Not Found', sprintf("%s '%s' not found", $entity, $id));
        }

        $attributes = $this->extractAttributes($definition, $payload, false, true);
        $updated = $this->repository->update($definition, $id, $attributes);

        return [
            'data' => $this->resourceObject($definition, $updated),
        ];
    }

    /**
     * @param string $entity
     * @param mixed $id
     */
    public function deleteResource($entity, $id)
    {
        $this->assertAdmin();

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
        if (!isset($payload['data']) || !is_array($payload['data'])) {
            throw new ApiException(400, 'invalid_payload', 'Invalid Payload', 'Payload must include a data object', '/data');
        }
        if (($payload['data']['type'] ?? null) !== $definition->getType()) {
            throw new ApiException(409, 'type_mismatch', 'Type Mismatch', sprintf("Payload data.type must be '%s'", $definition->getType()), '/data/type');
        }
        if (!isset($payload['data']['attributes']) || !is_array($payload['data']['attributes'])) {
            throw new ApiException(400, 'invalid_attributes', 'Invalid Attributes', 'Payload must include data.attributes object', '/data/attributes');
        }

        $attributes = $payload['data']['attributes'];
        $primaryKey = $definition->getPrimaryKey();

        if ($isCreate && array_key_exists('id', $payload['data'])) {
            $idValue = $payload['data']['id'];
            if ($this->isEmptyValue($idValue)) {
                throw new ApiException(400, 'invalid_id', 'Invalid Resource Identifier', 'data.id cannot be empty', '/data/id');
            }
            if (array_key_exists($primaryKey, $attributes) && (string) $attributes[$primaryKey] !== (string) $idValue) {
                throw new ApiException(409, 'id_attribute_mismatch', 'Identifier Mismatch', sprintf("data.id and data.attributes.%s must match", $primaryKey), '/data/id');
            }
            $attributes[$primaryKey] = $idValue;
        }

        foreach ($attributes as $field => $value) {
            if (!in_array($field, $definition->getWritableFields(), true) && $field !== $primaryKey) {
                throw new ApiException(400, 'invalid_attribute', 'Invalid Attribute', sprintf("Attribute '%s' is not writable", $field), '/data/attributes/' . $field);
            }
        }

        if (isset($attributes[$primaryKey]) && !$isCreate) {
            throw new ApiException(400, 'immutable_primary_key', 'Immutable Primary Key', 'Primary key cannot be changed', '/data/attributes/' . $primaryKey);
        }

        if ($isPut) {
            $complete = [];
            foreach ($definition->getWritableFields() as $field) {
                if ($field === $primaryKey) {
                    continue;
                }
                $complete[$field] = array_key_exists($field, $attributes) ? $attributes[$field] : null;
            }
            $attributes = $complete;
        }

        $requiredFields = $isCreate ? $definition->getRequiredOnCreate() : $definition->getRequiredOnPut();
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $attributes) || $this->isEmptyValue($attributes[$field])) {
                throw new ApiException(400, 'missing_required_attribute', 'Missing Required Attribute', sprintf("Attribute '%s' is required", $field), '/data/attributes/' . $field);
            }
        }

        $this->validateAttributeTypes($definition, $attributes);

        return $attributes;
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

        foreach ($definition->getReadableFields() as $field) {
            if ($field === '*' || $field === $primaryKey) {
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
     * @param mixed $value
     * @return bool
     */
    private function isEmptyValue($value)
    {
        if ($value === null) {
            return true;
        }
        if (is_string($value)) {
            return trim($value) === '';
        }
        return false;
    }

    private function validateAttributeTypes(EntityDefinition $definition, array $attributes)
    {
        foreach ($attributes as $field => $value) {
            if ($value === null) {
                continue;
            }

            $rule = $definition->getAttributeRule($field);
            if ($rule === null || !isset($rule['type'])) {
                continue;
            }

            $type = $rule['type'];
            $pointer = '/data/attributes/' . $field;
            if ($type === 'integer' && !is_int($value)) {
                throw new ApiException(422, 'invalid_attribute_type', 'Invalid Attribute Type', sprintf("Attribute '%s' must be an integer", $field), $pointer);
            }
            if ($type === 'numeric' && !is_int($value) && !is_float($value)) {
                throw new ApiException(422, 'invalid_attribute_type', 'Invalid Attribute Type', sprintf("Attribute '%s' must be numeric", $field), $pointer);
            }
            if ($type === 'boolean' && !is_bool($value)) {
                throw new ApiException(422, 'invalid_attribute_type', 'Invalid Attribute Type', sprintf("Attribute '%s' must be boolean", $field), $pointer);
            }
            if ($type === 'string' && !is_string($value)) {
                throw new ApiException(422, 'invalid_attribute_type', 'Invalid Attribute Type', sprintf("Attribute '%s' must be string", $field), $pointer);
            }
        }
    }
}
