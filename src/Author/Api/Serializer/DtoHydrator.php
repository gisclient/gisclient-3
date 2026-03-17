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
        $schema = $dtoClass::schema();

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
                $dto->addExtraAttribute($name, $value);
                continue;
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
                $dto->addExtraRelationship($name, $relationshipData);
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

            $relatedDto = $this->hydrateRelationshipData($relationshipData, (string) $field->getTargetClass());
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
     * @param array<string,mixed> $relationshipData
     */
    private function hydrateRelationshipData(array $relationshipData, string $dtoClass): JsonApiDto
    {
        /** @var JsonApiDto $relatedDto */
        $relatedDto = new $dtoClass();
        $schema = $dtoClass::schema();

        if (($relationshipData['type'] ?? null) !== null && $relationshipData['type'] !== $schema->getType()) {
            throw new ApiException(
                422,
                'invalid_relationship',
                'Invalid Relationship',
                sprintf("Relationship type must be '%s'", $schema->getType()),
                '/data/type'
            );
        }

        if (array_key_exists('id', $relationshipData) && $relationshipData['id'] !== null) {
            DtoPropertyAccessor::set($relatedDto, 'id', $this->normalizeScalarId($relationshipData['id']));
            $relatedDto->markPresent('id');
        }

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
