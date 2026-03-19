<?php

namespace GisClient\Author\Api\Mapper;

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;
use GisClient\Author\Api\Dto\Support\DtoPropertyAccessor;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Persistence\Entity;
use GisClient\Author\Persistence\EntitySchema;
use GisClient\Author\Persistence\EntitySchemaRegistry;

class DtoToEntityMapper
{
    public function mapForCreate(string $type, JsonApiDto $dto): Entity
    {
        $schema = DtoSchemaRegistry::schemaForDtoClass(get_class($dto));
        $entitySchema = EntitySchemaRegistry::schemaForType($type);
        $attributes = $this->mergeRelationshipLocalKeysIntoAttributes(
            $entitySchema,
            $dto,
            $this->extractDtoAttributes($schema, $entitySchema, $dto)
        );
        $id = $this->normalizeId($schema, $dto->getId());

        if ($id !== null) {
            $attributes[$schema->getPrimaryKey()] = $id;
        }

        return new Entity(
            $type,
            Entity::OPERATION_CREATE,
            $id,
            $attributes,
            $this->extractRelationshipIdentifiers($schema, $dto)
        );
    }

    /**
     * @param int|string $id
     */
    public function mapForUpdate(string $type, $id, JsonApiDto $dto, bool $isPut = true): Entity
    {
        $schema = DtoSchemaRegistry::schemaForDtoClass(get_class($dto));
        $entitySchema = EntitySchemaRegistry::schemaForType($type);
        $attributes = $this->mergeRelationshipLocalKeysIntoAttributes(
            $entitySchema,
            $dto,
            $this->extractDtoAttributes($schema, $entitySchema, $dto)
        );

        if ($isPut) {
            $complete = [];
            foreach ($entitySchema->getWritableDbFields() as $field) {
                if ($field === $entitySchema->getPrimaryKey()) {
                    continue;
                }

                $complete[$field] = array_key_exists($field, $attributes) ? $attributes[$field] : null;
            }
            $attributes = $complete;
        }

        return new Entity(
            $type,
            Entity::OPERATION_UPDATE,
            $id,
            $attributes,
            $this->extractRelationshipIdentifiers($schema, $dto)
        );
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
     * @param array<string,mixed> $attributes
     * @return array<string,mixed>
     */
    private function mergeRelationshipLocalKeysIntoAttributes(EntitySchema $entitySchema, JsonApiDto $dto, array $attributes): array
    {
        $schema = DtoSchemaRegistry::schemaForDtoClass(get_class($dto));
        foreach ($schema->getRelationships() as $relationshipName => $relationship) {
            $localKey = $entitySchema->getRelationshipColumn($relationshipName);
            if ($localKey === null || !$dto->isPresent($relationshipName)) {
                continue;
            }

            $relationshipData = $this->getRelationshipIdentifier($schema, $dto, $relationshipName);
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

    /**
     * @param mixed $idValue
     * @return int|string|null
     */
    private function normalizeId(ResourceSchema $schema, $idValue)
    {
        if ($idValue === null) {
            return null;
        }

        $idType = strtolower((string) $schema->getIdPhpType());
        if (in_array($idType, ['int', 'integer'], true)) {
            if (is_int($idValue)) {
                return $idValue;
            }
            if (is_string($idValue) && preg_match('/^-?\d+$/', $idValue) === 1) {
                return (int) $idValue;
            }

            throw new ValidationException([[
                'status' => '422',
                'code' => 'invalid_id',
                'title' => 'Invalid Resource Identifier',
                'detail' => 'data.id must be an integer identifier for this resource type',
                'source' => [
                    'id' => true,
                ],
            ]]);
        }

        if (is_string($idValue)) {
            return $idValue;
        }
        if (is_scalar($idValue)) {
            return (string) $idValue;
        }

        throw new ValidationException([[
            'status' => '422',
            'code' => 'invalid_id',
            'title' => 'Invalid Resource Identifier',
            'detail' => 'data.id must be a string identifier',
            'source' => [
                'id' => true,
            ],
        ]]);
    }
}
