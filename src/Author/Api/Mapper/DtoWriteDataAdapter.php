<?php

namespace GisClient\Author\Api\Mapper;

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\Support\DtoPropertyAccessor;
use GisClient\Author\Api\Model\ResourceIdentifierData;
use GisClient\Author\Api\Model\ResourceWriteData;

class DtoWriteDataAdapter
{
    public function toResourceWriteData(JsonApiDto $dto): ResourceWriteData
    {
        $schema = $dto::schema();
        $attributes = $dto->extraAttributes();
        $relationships = [];
        $id = null;

        if (DtoPropertyAccessor::isInitialized($dto, 'id')) {
            $id = DtoPropertyAccessor::get($dto, 'id');
        }

        foreach ($schema->getAttributes() as $field) {
            if (!$dto->isPresent($field->getJsonApiName()) || !DtoPropertyAccessor::isInitialized($dto, $field->getPropertyName())) {
                continue;
            }

            $attributes[$field->getJsonApiName()] = DtoPropertyAccessor::get($dto, $field->getPropertyName());
        }

        foreach ($dto->extraRelationships() as $name => $relationshipData) {
            if ($relationshipData === null) {
                $relationships[$name] = new ResourceIdentifierData();
                continue;
            }
            if (!is_array($relationshipData)) {
                continue;
            }
            $relationships[$name] = new ResourceIdentifierData(
                isset($relationshipData['type']) ? (string) $relationshipData['type'] : null,
                $relationshipData['id'] ?? null
            );
        }

        foreach ($schema->getRelationships() as $field) {
            if (!$dto->isPresent($field->getJsonApiName())) {
                continue;
            }

            if (!DtoPropertyAccessor::isInitialized($dto, $field->getPropertyName())) {
                $relationships[$field->getJsonApiName()] = new ResourceIdentifierData();
                continue;
            }

            $relatedDto = DtoPropertyAccessor::get($dto, $field->getPropertyName());
            if ($relatedDto === null) {
                $relationships[$field->getJsonApiName()] = new ResourceIdentifierData();
                continue;
            }

            $relationshipId = DtoPropertyAccessor::isInitialized($relatedDto, 'id')
                ? DtoPropertyAccessor::get($relatedDto, 'id')
                : null;

            $relationships[$field->getJsonApiName()] = new ResourceIdentifierData(
                $field->getTargetType(),
                $relationshipId
            );
        }

        return new ResourceWriteData($id, $attributes, $relationships);
    }
}
