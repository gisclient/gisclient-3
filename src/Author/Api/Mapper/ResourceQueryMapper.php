<?php

namespace GisClient\Author\Api\Mapper;

use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Persistence\EntityQuery;
use GisClient\Author\Persistence\EntitySchema;
use GisClient\Author\Persistence\EntitySchemaRegistry;

class ResourceQueryMapper
{
    public function map(ResourceSchema $schema, array $query): EntityQuery
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

        return new EntityQuery($schema->getType(), $limit, $offset, $sortField, $sortDirection, $translatedFilters);
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    private function normalizeFilterAliases(EntitySchema $entitySchema, array $filters): array
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
}
