<?php

namespace GisClient\Author\Api\Mapper;

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Api\Dto\Schema\FieldDefinition;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;
use GisClient\Author\Api\Dto\Support\DtoPropertyAccessor;
use GisClient\Author\Persistence\Entity;
use GisClient\Author\Persistence\EntitySchemaRegistry;

class EntityToDtoMapper
{
    public function map(ResourceSchema $schema, Entity $entity): JsonApiDto
    {
        $dtoClass = DtoSchemaRegistry::classFromType($schema->getType());
        /** @var JsonApiDto $dto */
        $dto = new $dtoClass();
        $dtoSchema = DtoSchemaRegistry::schemaForDtoClass($dtoClass);
        $entitySchema = EntitySchemaRegistry::schemaForType($schema->getType());
        $attributes = $entity->getAttributes();

        if ($entity->getId() !== null) {
            DtoPropertyAccessor::set($dto, 'id', $entity->getId());
            $dto->markPresent('id');
        }

        foreach ($dtoSchema->getAttributes() as $field) {
            $jsonApiName = $field->getJsonApiName();
            $column = $entitySchema->getAttributeColumn($jsonApiName) ?? $jsonApiName;
            if (!array_key_exists($column, $attributes)) {
                continue;
            }

            DtoPropertyAccessor::set($dto, $field->getPropertyName(), $this->castAttributeValue($field, $attributes[$column]));
            $dto->markPresent($jsonApiName);
        }

        foreach ($dtoSchema->getRelationships() as $field) {
            if ($field->isCollection()) {
                $collectionData = $entity->getCollectionRelationships()[$field->getJsonApiName()] ?? null;
                if ($collectionData === null) {
                    continue;
                }

                DtoPropertyAccessor::set($dto, $field->getPropertyName(), $collectionData);
                $dto->markPresent($field->getJsonApiName());
                continue;
            }

            $relationshipData = $entity->getRelationships()[$field->getJsonApiName()] ?? null;
            if (!is_array($relationshipData) || !array_key_exists('id', $relationshipData) || $relationshipData['id'] === null) {
                continue;
            }

            $targetClass = (string) $field->getTargetClass();
            /** @var JsonApiDto $relatedDto */
            $relatedDto = new $targetClass();
            DtoPropertyAccessor::set($relatedDto, 'id', $relationshipData['id']);
            $relatedDto->markPresent('id');
            $relatedDto->markAsIdentifierOnly();
            DtoPropertyAccessor::set($dto, $field->getPropertyName(), $relatedDto);
            $dto->markPresent($field->getJsonApiName());
        }

        return $dto;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function castAttributeValue(FieldDefinition $field, $value)
    {
        if ($value === null) {
            return null;
        }

        $type = $field->getPhpType();

        if ($type === 'int' && is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        if ($type === 'bool') {
            if (is_int($value)) {
                return $value === 1;
            }
            if (is_string($value)) {
                $normalized = strtolower(trim($value));
                if (in_array($normalized, ['1', 't', 'true', 'yes', 'on'], true)) {
                    return true;
                }
                if (in_array($normalized, ['0', 'f', 'false', 'no', 'off'], true)) {
                    return false;
                }
            }
        }

        if ($type === 'float' && is_string($value) && is_numeric(trim($value))) {
            return preg_match('/^[+-]?\d+$/', trim($value)) === 1 ? (int) $value : (float) $value;
        }

        return $value;
    }
}
