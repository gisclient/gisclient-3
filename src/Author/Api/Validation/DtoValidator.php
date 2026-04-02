<?php

namespace GisClient\Author\Api\Validation;

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Api\Dto\Support\DtoPropertyAccessor;
use GisClient\Author\Api\Exception\ValidationException;

class DtoValidator
{
    /**
     * @param bool $isCreate
     * @param bool $isPut
     */
    public function validate(JsonApiDto $dto, $isCreate, $isPut): void
    {
        $schema = DtoSchemaRegistry::schemaForDtoClass(get_class($dto));
        $errors = [];

        $requiredFields = $isCreate ? $schema->getRequiredOnCreate() : ($isPut ? $schema->getRequiredOnPut() : []);
        foreach ($requiredFields as $fieldName) {
            if ($fieldName === $schema->getPrimaryKey()) {
                if (!$dto->isPresent('id') || $this->isEmpty($dto, 'id')) {
                    $errors[] = $this->error('missing_required_attribute', 'Missing Required Attribute', sprintf("'%s' is required and must be sent as data.id", $fieldName), [
                        'id' => true,
                    ]);
                }
                continue;
            }

            $relationship = $schema->getRelationship($fieldName);
            if ($relationship !== null) {
                if ($this->isSatisfiedByRelationship($dto, $fieldName)) {
                    continue;
                }
                continue;
            }

            $attribute = $schema->getAttribute($fieldName);
            if ($attribute === null) {
                continue;
            }

            if (!$dto->isPresent($fieldName) || $this->isEmpty($dto, $attribute->getPropertyName())) {
                $errors[] = $this->error('missing_required_attribute', 'Missing Required Attribute', sprintf("Attribute '%s' is required", $fieldName), [
                    'attribute' => $fieldName,
                ]);
            }
        }

        foreach ($this->requiredRelationshipNames($dto, $isCreate, $isPut) as $relationshipName) {
            if (!$dto->isPresent($relationshipName)) {
                $errors[] = $this->error(
                    'missing_required_relationship',
                    'Missing Required Relationship',
                    sprintf("Relationship '%s' is required", $relationshipName),
                    [
                        'relationship' => $relationshipName,
                    ]
                );
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    private function isEmpty(JsonApiDto $dto, string $property): bool
    {
        if (!DtoPropertyAccessor::isInitialized($dto, $property)) {
            return true;
        }

        $value = DtoPropertyAccessor::get($dto, $property);
        if ($value === null) {
            return true;
        }

        return is_string($value) && trim($value) === '';
    }

    private function isSatisfiedByRelationship(JsonApiDto $dto, string $relationshipName): bool
    {
        $field = DtoSchemaRegistry::schemaForDtoClass(get_class($dto))->getRelationship($relationshipName);
        if ($field === null || !$dto->isPresent($relationshipName)) {
            return false;
        }

        if ($field->isCollection()) {
            return true;
        }

        if (!DtoPropertyAccessor::isInitialized($dto, $field->getPropertyName())) {
            return false;
        }

        $relatedDto = DtoPropertyAccessor::get($dto, $field->getPropertyName());
        if (!$relatedDto instanceof JsonApiDto || !DtoPropertyAccessor::isInitialized($relatedDto, 'id')) {
            return false;
        }

        $value = DtoPropertyAccessor::get($relatedDto, 'id');
        return $value !== null && (!is_string($value) || trim($value) !== '');
    }

    /**
     * @return array<int,string>
     */
    private function requiredRelationshipNames(JsonApiDto $dto, bool $isCreate, bool $isPut): array
    {
        $schema = DtoSchemaRegistry::schemaForDtoClass(get_class($dto));
        $requiredFields = $isCreate ? $schema->getRequiredOnCreate() : ($isPut ? $schema->getRequiredOnPut() : []);
        $requiredRelationships = [];

        foreach ($requiredFields as $fieldName) {
            if ($schema->getRelationship($fieldName) === null) {
                continue;
            }

            $requiredRelationships[] = $fieldName;
        }

        return array_values(array_unique($requiredRelationships));
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    private function error(string $code, string $title, string $detail, array $source): array
    {
        return [
            'code' => $code,
            'title' => $title,
            'detail' => $detail,
            'source' => $source,
        ];
    }
}
