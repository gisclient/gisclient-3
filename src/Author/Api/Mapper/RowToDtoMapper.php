<?php

namespace GisClient\Author\Api\Mapper;

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Api\Dto\Schema\FieldDefinition;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;
use GisClient\Author\Api\Dto\Support\DtoPropertyAccessor;
use GisClient\Author\Persistence\EntitySchemaRegistry;

class RowToDtoMapper
{
    /**
     * @param array<string,mixed> $row
     */
    public function map(ResourceSchema $schema, array $row): JsonApiDto
    {
        $dtoClass = DtoSchemaRegistry::classFromType($schema->getType());
        /** @var JsonApiDto $dto */
        $dto = new $dtoClass();
        $dtoSchema = $dtoClass::schema();
        $entitySchema = EntitySchemaRegistry::schemaForType($schema->getType());

        if (array_key_exists($schema->getPrimaryKey(), $row)) {
            DtoPropertyAccessor::set($dto, 'id', $row[$schema->getPrimaryKey()]);
            $dto->markPresent('id');
        }

        foreach ($dtoSchema->getAttributes() as $field) {
            $jsonApiName = $field->getJsonApiName();
            if (!array_key_exists($jsonApiName, $row)) {
                continue;
            }

            DtoPropertyAccessor::set($dto, $field->getPropertyName(), $this->castAttributeValue($field, $row[$jsonApiName]));
            $dto->markPresent($jsonApiName);
        }

        foreach ($dtoSchema->getRelationships() as $field) {
            $localKey = $entitySchema->getRelationshipColumn($field->getJsonApiName());
            if ($localKey === null || !array_key_exists($localKey, $row)) {
                continue;
            }

            if ($row[$localKey] === null) {
                continue;
            }

            $targetClass = (string) $field->getTargetClass();
            /** @var JsonApiDto $relatedDto */
            $relatedDto = new $targetClass();
            DtoPropertyAccessor::set($relatedDto, 'id', $row[$localKey]);
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
