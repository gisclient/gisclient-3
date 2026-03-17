<?php

namespace GisClient\Author\Api\Validation;

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\Schema\FieldDefinition;
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

        foreach ($schema->getAttributes() as $field) {
            if (!$dto->isPresent($field->getJsonApiName())) {
                continue;
            }

            $value = DtoPropertyAccessor::isInitialized($dto, $field->getPropertyName())
                ? DtoPropertyAccessor::get($dto, $field->getPropertyName())
                : null;

            if ($value === null) {
                if (!$field->isNullable()) {
                    $errors[] = $this->error('invalid_attribute_type', 'Invalid Attribute Type', sprintf("Attribute '%s' cannot be null", $field->getJsonApiName()), [
                        'attribute' => $field->getJsonApiName(),
                    ]);
                }
                continue;
            }

            if (!$this->matchesType($field, $value)) {
                $errors[] = $this->error('invalid_attribute_type', 'Invalid Attribute Type', sprintf("Attribute '%s' must be %s", $field->getJsonApiName(), $field->getPhpType()), [
                    'attribute' => $field->getJsonApiName(),
                ]);
            }
        }

        foreach ($schema->getRelationships() as $field) {
            if (!$dto->isPresent($field->getJsonApiName())) {
                continue;
            }

            $value = DtoPropertyAccessor::isInitialized($dto, $field->getPropertyName())
                ? DtoPropertyAccessor::get($dto, $field->getPropertyName())
                : null;

            if ($value === null) {
                if (!$field->isNullable()) {
                    $errors[] = $this->error('invalid_relationship', 'Invalid Relationship', sprintf("Relationship '%s' cannot be null", $field->getJsonApiName()), [
                        'pointer' => '/data/relationships/' . $field->getJsonApiName() . '/data',
                    ]);
                }
                continue;
            }

            if (!$value instanceof JsonApiDto) {
                $errors[] = $this->error('invalid_relationship', 'Invalid Relationship', sprintf("Relationship '%s' must be a DTO", $field->getJsonApiName()), [
                    'pointer' => '/data/relationships/' . $field->getJsonApiName() . '/data',
                ]);
                continue;
            }

            $relatedSchema = $value::schema();
            if ($field->getTargetType() !== null && $relatedSchema->getType() !== $field->getTargetType()) {
                $errors[] = $this->error('invalid_relationship', 'Invalid Relationship', sprintf("Relationship '%s' type must be '%s'", $field->getJsonApiName(), $field->getTargetType()), [
                    'pointer' => '/data/relationships/' . $field->getJsonApiName() . '/data/type',
                ]);
            }

            if (!DtoPropertyAccessor::isInitialized($value, 'id') || $this->isEmpty($value, 'id')) {
                $errors[] = $this->error('invalid_relationship', 'Invalid Relationship', sprintf("Relationship '%s' id is required", $field->getJsonApiName()), [
                    'pointer' => '/data/relationships/' . $field->getJsonApiName() . '/data/id',
                ]);
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    private function matchesType(FieldDefinition $field, $value): bool
    {
        $type = $field->getPhpType();
        if ($type === 'int') {
            return is_int($value);
        }
        if ($type === 'float') {
            return is_float($value) || is_int($value);
        }
        if ($type === 'bool') {
            return is_bool($value);
        }
        if ($type === 'string') {
            return is_string($value);
        }
        if ($type === 'array') {
            return is_array($value);
        }

        return true;
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
