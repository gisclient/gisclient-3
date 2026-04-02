<?php

namespace GisClient\Author\Api\Serializer;

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Api\Dto\Schema\FieldDefinition;
use GisClient\Author\Api\Dto\Support\DtoPropertyAccessor;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Exception\ValidationException;

class DtoHydrator
{
    public function hydrateDocument(string $content, string $expectedType): JsonApiDto
    {
        if ($content === null || trim((string) $content) === '') {
            throw new ApiException(400, 'invalid_json', 'Invalid JSON', 'Request body must be valid JSON');
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new ApiException(400, 'invalid_json', 'Invalid JSON', 'Request body must be valid JSON');
        }

        $data = $decoded['data'] ?? null;
        if (!is_array($data)) {
            throw new ApiException(400, 'invalid_payload', 'Invalid Payload', 'Payload must include a data object', '/data');
        }

        if (($data['type'] ?? null) !== $expectedType) {
            throw new ApiException(422, 'type_mismatch', 'Type Mismatch', sprintf("Payload data.type must be '%s'", $expectedType), '/data/type');
        }

        $dtoClass = DtoSchemaRegistry::classFromType($expectedType);
        if (!class_exists($dtoClass)) {
            throw new ApiException(500, 'missing_dto', 'Missing DTO', sprintf("DTO class '%s' does not exist", $dtoClass));
        }

        return $this->hydrateResourceObject($data, $dtoClass);
    }

    /**
     * @param array<string,mixed> $data
     */
    public function hydrateResourceObject(array $data, string $dtoClass): JsonApiDto
    {
        /** @var JsonApiDto $dto */
        $dto = new $dtoClass();
        $schema = DtoSchemaRegistry::schemaForDtoClass($dtoClass);

        if (array_key_exists('id', $data) && $data['id'] !== null) {
            DtoPropertyAccessor::set($dto, 'id', $this->normalizeScalarId($data['id']));
            $dto->markPresent('id');
        }

        $attributes = $data['attributes'] ?? null;
        if (!is_array($attributes)) {
            throw new ApiException(400, 'invalid_attributes', 'Invalid Attributes', 'Payload must include data.attributes object', '/data/attributes');
        }

        $errors = [];
        foreach ($attributes as $name => $value) {
            if (!is_string($name)) {
                continue;
            }

            $field = $schema->getAttribute($name);
            if ($field === null) {
                $detail = $name === $schema->getPrimaryKey()
                    ? sprintf("'%s' is the resource ID and must be sent as data.id, not data.attributes", $name)
                    : sprintf("Attribute '%s' is not allowed for resource type '%s'", $name, $schema->getType());
                throw new ApiException(
                    400,
                    'invalid_attribute',
                    'Invalid Attribute',
                    $detail,
                    '/data/attributes/' . $name
                );
            }

            if (!$field->isWritable()) {
                throw new ApiException(
                    400,
                    'readonly_attribute',
                    'Read-only Attribute',
                    sprintf("Attribute '%s' is read-only", $name),
                    '/data/attributes/' . $name
                );
            }

            if (!$this->tryAssignAttributeValue($dto, $field, $name, $value, $errors)) {
                continue;
            }
            $dto->markPresent($name);
        }

        if ($errors !== []) {
            throw new ValidationException($errors, 400);
        }

        $relationships = $data['relationships'] ?? [];
        if ($relationships !== [] && !is_array($relationships)) {
            throw new ApiException(400, 'invalid_relationships', 'Invalid Relationships', 'Payload data.relationships must be an object', '/data/relationships');
        }

        foreach ($relationships as $name => $relationship) {
            if (!is_string($name) || !is_array($relationship)) {
                continue;
            }

            $field = $schema->getRelationship($name);
            $relationshipData = $relationship['data'] ?? null;
            if ($field === null) {
                throw new ApiException(
                    400,
                    'invalid_relationship',
                    'Invalid Relationship',
                    sprintf("Relationship '%s' is not allowed for resource type '%s'", $name, $schema->getType()),
                    '/data/relationships/' . $name
                );
            }

            if ($field->isCollection()) {
                $ids = $this->hydrateCollectionRelationshipData($relationshipData, $field, $name);
                $relationshipErrors = [];
                $this->assignValue($dto, $field->getPropertyName(), $ids, '/data/relationships/' . $name . '/data', $name, $relationshipErrors);
                if ($relationshipErrors !== []) {
                    throw new ValidationException($relationshipErrors, 400);
                }
                $dto->markPresent($name);
                continue;
            }

            if ($relationshipData === null) {
                $relationshipErrors = [];
                $this->assignValue($dto, $field->getPropertyName(), null, '/data/relationships/' . $name . '/data', $name, $relationshipErrors);
                if ($relationshipErrors !== []) {
                    throw new ValidationException($relationshipErrors, 400);
                }
                $dto->markPresent($name);
                continue;
            }

            if (!is_array($relationshipData)) {
                throw new ApiException(
                    400,
                    'invalid_relationship',
                    'Invalid Relationship',
                    sprintf("Relationship '%s' data must be an object or null", $name),
                    '/data/relationships/' . $name . '/data'
                );
            }

            $relatedDto = $this->hydrateRelationshipData($relationshipData, (string) $field->getTargetClass(), $name);
            $relationshipErrors = [];
            $this->assignValue($dto, $field->getPropertyName(), $relatedDto, '/data/relationships/' . $name . '/data', $name, $relationshipErrors);
            if ($relationshipErrors !== []) {
                throw new ValidationException($relationshipErrors, 400);
            }
            $dto->markPresent($name);
        }

        return $dto;
    }

    /**
     * Parses a to-many (collection) relationship data array into a flat list of IDs.
     *
     * @param mixed $data
     * @return array<int,string|int>
     */
    private function hydrateCollectionRelationshipData($data, FieldDefinition $field, string $name): array
    {
        if ($data === null || $data === []) {
            return [];
        }

        if (!is_array($data)) {
            throw new ApiException(
                400,
                'invalid_relationship',
                'Invalid Relationship',
                sprintf("Relationship '%s' data must be an array for to-many relationships", $name),
                '/data/relationships/' . $name . '/data'
            );
        }

        $ids = [];
        foreach ($data as $index => $item) {
            if (!is_array($item)) {
                throw new ApiException(
                    422,
                    'invalid_relationship',
                    'Invalid Relationship',
                    "Each item in a to-many relationship must be a resource identifier object",
                    '/data/relationships/' . $name . '/data/' . $index
                );
            }

            if (($item['type'] ?? null) !== $field->getTargetType()) {
                throw new ApiException(
                    422,
                    'invalid_relationship_type',
                    'Invalid Relationship Type',
                    sprintf("Relationship type must be '%s'", $field->getTargetType()),
                    '/data/relationships/' . $name . '/data/' . $index . '/type'
                );
            }

            $itemId = $item['id'] ?? null;
            if (!is_string($itemId) || trim($itemId) === '') {
                throw new ApiException(
                    422,
                    'invalid_relationship_id',
                    'Invalid Relationship Id',
                    "Collection relationship item id must be a non-empty string",
                    '/data/relationships/' . $name . '/data/' . $index . '/id'
                );
            }

            $ids[] = $itemId;
        }

        return $ids;
    }

    /**
     * @param array<string,mixed> $relationshipData
     */
    private function hydrateRelationshipData(array $relationshipData, string $dtoClass, string $relationshipName): JsonApiDto
    {
        /** @var JsonApiDto $relatedDto */
        $relatedDto = new $dtoClass();
        $schema = DtoSchemaRegistry::schemaForDtoClass($dtoClass);

        if (($relationshipData['type'] ?? null) !== null && $relationshipData['type'] !== $schema->getType()) {
            throw new ApiException(
                422,
                'invalid_relationship_type',
                'Invalid Relationship Type',
                sprintf("Relationship type must be '%s'", $schema->getType()),
                '/data/relationships/' . $relationshipName . '/data/type'
            );
        }

        if (!array_key_exists('id', $relationshipData) || $relationshipData['id'] === null) {
            throw new ApiException(
                422,
                'invalid_relationship_id',
                'Invalid Relationship Id',
                sprintf("Relationship '%s' id is required", $relationshipName),
                '/data/relationships/' . $relationshipName . '/data/id'
            );
        }

        DtoPropertyAccessor::set($relatedDto, 'id', $this->normalizeRelationshipId($relationshipData['id'], $relationshipName));
        $relatedDto->markPresent('id');

        $hasExpandedContent = isset($relationshipData['attributes']) || isset($relationshipData['relationships']);
        if (!$hasExpandedContent) {
            $relatedDto->markAsIdentifierOnly();
            return $relatedDto;
        }

        $resourceObject = [
            'type' => $schema->getType(),
            'id' => $relationshipData['id'] ?? null,
            'attributes' => $relationshipData['attributes'] ?? [],
            'relationships' => $relationshipData['relationships'] ?? [],
        ];

        return $this->hydrateResourceObject($resourceObject, $dtoClass);
    }

    /**
     * @param mixed $id
     * @return string|int
     */
    private function normalizeRelationshipId($id, string $relationshipName)
    {
        if (is_string($id)) {
            if (trim($id) === '') {
                throw new ApiException(
                    422,
                    'invalid_relationship_id',
                    'Invalid Relationship Id',
                    sprintf("Relationship '%s' id must not be empty", $relationshipName),
                    '/data/relationships/' . $relationshipName . '/data/id'
                );
            }

            return $id;
        }

        if (is_int($id)) {
            return $id;
        }

        if (is_scalar($id)) {
            $normalized = (string) $id;
            if (trim($normalized) === '') {
                throw new ApiException(
                    422,
                    'invalid_relationship_id',
                    'Invalid Relationship Id',
                    sprintf("Relationship '%s' id must not be empty", $relationshipName),
                    '/data/relationships/' . $relationshipName . '/data/id'
                );
            }

            return $normalized;
        }

        throw new ApiException(
            422,
            'invalid_relationship_id',
            'Invalid Relationship Id',
            sprintf("Relationship '%s' id must be a scalar value", $relationshipName),
            '/data/relationships/' . $relationshipName . '/data/id'
        );
    }

    /**
     * @param mixed $id
     * @return string|int|null
     */
    private function normalizeScalarId($id)
    {
        if ($id === null || is_string($id) || is_int($id)) {
            return $id;
        }
        if (is_scalar($id)) {
            return (string) $id;
        }

        throw new ApiException(422, 'invalid_id', 'Invalid Resource Identifier', 'data.id must be a scalar value', '/data/id');
    }

    /**
     * @param mixed $value
     */
    private function tryAssignAttributeValue(JsonApiDto $dto, FieldDefinition $field, string $name, $value, array &$errors): bool
    {
        if ($value === null) {
            if ($field->isNullable()) {
                $this->assignValue($dto, $field->getPropertyName(), null, '/data/attributes/' . $name, $name, $errors);
                return true;
            }

            $errors[] = $this->attributeTypeError($name, sprintf("Attribute '%s' must not be null", $name));
            return false;
        }

        $expectedType = $field->getPhpType();
        if ($expectedType === 'string' && is_string($value)) {
            $this->assignValue($dto, $field->getPropertyName(), $value, '/data/attributes/' . $name, $name, $errors);
            return true;
        }

        if ($expectedType === 'int' && is_int($value)) {
            $this->assignValue($dto, $field->getPropertyName(), $value, '/data/attributes/' . $name, $name, $errors);
            return true;
        }

        if ($expectedType === 'float' && (is_int($value) || is_float($value))) {
            $this->assignValue($dto, $field->getPropertyName(), $value, '/data/attributes/' . $name, $name, $errors);
            return true;
        }

        $errors[] = $this->attributeTypeError(
            $name,
            sprintf(
                "Attribute '%s' must be %s, %s given",
                $name,
                $this->describeExpectedJsonType($expectedType),
                $this->describeJsonValueType($value)
            )
        );

        return false;
    }

    /**
     * @param mixed $value
     */
    private function assignValue(JsonApiDto $dto, string $propertyName, $value, string $pointer, string $fieldName, array &$errors): void
    {
        try {
            DtoPropertyAccessor::set($dto, $propertyName, $value);
        } catch (\TypeError $exception) {
            $errors[] = [
                'code' => 'invalid_attribute_type',
                'title' => 'Bad Request',
                'detail' => sprintf("Attribute '%s' has an invalid type", $fieldName),
                'source' => [
                    'pointer' => $pointer,
                ],
            ];
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function attributeTypeError(string $name, string $detail): array
    {
        return [
            'code' => 'invalid_attribute_type',
            'title' => 'Bad Request',
            'detail' => $detail,
            'source' => [
                'pointer' => '/data/attributes/' . $name,
            ],
        ];
    }

    private function describeExpectedJsonType(string $phpType): string
    {
        if ($phpType === 'float' || $phpType === 'int') {
            return 'a number';
        }

        if ($phpType === 'string') {
            return 'a string';
        }

        return $phpType;
    }

    /**
     * @param mixed $value
     */
    private function describeJsonValueType($value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_string($value)) {
            return 'string';
        }

        if (is_int($value)) {
            return 'integer';
        }

        if (is_float($value)) {
            return 'number';
        }

        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_array($value)) {
            return 'array';
        }

        if (is_object($value)) {
            return 'object';
        }

        return gettype($value);
    }
}
