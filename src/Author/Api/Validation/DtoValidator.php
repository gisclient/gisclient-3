<?php

namespace GisClient\Author\Api\Validation;

use GisClient\Author\Api\Dto\JsonApiDto;
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
        $schema = $dto::schema();
        $errors = [];

        $requiredFields = $isCreate ? $schema->getRequiredOnCreate() : ($isPut ? $schema->getRequiredOnPut() : []);
        foreach ($requiredFields as $fieldName) {
            if ($fieldName === $schema->getPrimaryKey()) {
                if (!$dto->isPresent('id') || $this->isEmpty($dto, 'id')) {
                    $errors[] = $this->error('missing_required_attribute', 'Missing Required Attribute', sprintf("Attribute '%s' is required", $fieldName), [
                        'id' => true,
                    ]);
                }
                continue;
            }

            $attribute = $schema->getAttribute($fieldName);
            if ($attribute === null) {
                if ($this->isSatisfiedByRelationship($dto, $fieldName)) {
                    continue;
                }
                continue;
            }
            if (!$dto->isPresent($fieldName) || $this->isEmpty($dto, $attribute->getPropertyName())) {
                if ($this->isSatisfiedByRelationship($dto, $fieldName)) {
                    continue;
                }
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

    private function isSatisfiedByRelationship(JsonApiDto $dto, string $localKey): bool
    {
        foreach ($dto::schema()->getRelationships() as $field) {
            if ($field->getLocalKey() !== $localKey || !$dto->isPresent($field->getJsonApiName())) {
                continue;
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

        return false;
    }

    /**
     * @return array<int,string>
     */
    private function requiredRelationshipNames(JsonApiDto $dto, bool $isCreate, bool $isPut): array
    {
        $schema = $dto::schema();
        $requiredFields = $isCreate ? $schema->getRequiredOnCreate() : ($isPut ? $schema->getRequiredOnPut() : []);
        $requiredFieldsSet = array_fill_keys($requiredFields, true);
        $requiredRelationships = [];

        foreach ($schema->getRelationships() as $field) {
            $localKey = $field->getLocalKey();
            if ($localKey === null || !isset($requiredFieldsSet[$localKey])) {
                continue;
            }

            $requiredRelationships[] = $field->getJsonApiName();
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
