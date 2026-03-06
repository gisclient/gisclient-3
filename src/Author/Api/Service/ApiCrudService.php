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
        return $this->payloadValidator->validateAndNormalize($definition, $payload, $isCreate, $isPut);
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
}
