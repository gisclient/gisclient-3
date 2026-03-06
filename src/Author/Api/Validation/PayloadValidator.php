<?php

namespace GisClient\Author\Api\Validation;

use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\Model\EntityDefinition;

class PayloadValidator
{
    /**
     * @param bool $isCreate
     * @param bool $isPut
     * @return array<string,mixed>
     */
    public function validateAndNormalize(EntityDefinition $definition, array $payload, $isCreate, $isPut)
    {
        $errors = [];
        $data = $payload['data'] ?? null;
        if (!is_array($data)) {
            $this->addError($errors, 'invalid_payload', 'Invalid Payload', 'Payload must include a data object', '/data');
            throw new ValidationException($errors);
        }

        if (($data['type'] ?? null) !== $definition->getType()) {
            $this->addError($errors, 'type_mismatch', 'Type Mismatch', sprintf("Payload data.type must be '%s'", $definition->getType()), '/data/type');
        }

        $attributesRaw = $data['attributes'] ?? null;
        if (!is_array($attributesRaw)) {
            $this->addError($errors, 'invalid_attributes', 'Invalid Attributes', 'Payload must include data.attributes object', '/data/attributes');
            throw new ValidationException($errors);
        }

        $attributes = $attributesRaw;
        $primaryKey = $definition->getPrimaryKey();

        if ($isCreate && array_key_exists('id', $data)) {
            $idValue = $data['id'];
            if ($this->isEmptyValue($idValue)) {
                $this->addError($errors, 'invalid_id', 'Invalid Resource Identifier', 'data.id cannot be empty', '/data/id');
            } else {
                if (array_key_exists($primaryKey, $attributes) && (string) $attributes[$primaryKey] !== (string) $idValue) {
                    $this->addError(
                        $errors,
                        'id_attribute_mismatch',
                        'Identifier Mismatch',
                        sprintf("data.id and data.attributes.%s must match", $primaryKey),
                        '/data/id'
                    );
                }
                $attributes[$primaryKey] = $idValue;
            }
        }

        foreach ($attributes as $field => $value) {
            if (!in_array($field, $definition->getWritableFields(), true) && $field !== $primaryKey) {
                $this->addError(
                    $errors,
                    'invalid_attribute',
                    'Invalid Attribute',
                    sprintf("Attribute '%s' is not writable", $field),
                    '/data/attributes/' . $field
                );
            }
        }

        if (isset($attributes[$primaryKey]) && !$isCreate) {
            $this->addError(
                $errors,
                'immutable_primary_key',
                'Immutable Primary Key',
                'Primary key cannot be changed',
                '/data/attributes/' . $primaryKey
            );
        }

        if ($isPut) {
            $complete = [];
            foreach ($definition->getWritableFields() as $field) {
                if ($field === $primaryKey) {
                    continue;
                }
                $complete[$field] = array_key_exists($field, $attributes) ? $attributes[$field] : null;
            }
            $attributes = $complete;
        }

        $requiredFields = $isCreate ? $definition->getRequiredOnCreate() : $definition->getRequiredOnPut();
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $attributes) || $this->isEmptyValue($attributes[$field])) {
                $this->addError(
                    $errors,
                    'missing_required_attribute',
                    'Missing Required Attribute',
                    sprintf("Attribute '%s' is required", $field),
                    '/data/attributes/' . $field
                );
            }
        }

        $this->validateAttributeTypes($definition, $attributes, $errors);

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        return $attributes;
    }

    /**
     * @param array<int,array<string,mixed>> $errors
     */
    private function validateAttributeTypes(EntityDefinition $definition, array $attributes, array &$errors)
    {
        foreach ($attributes as $field => $value) {
            if ($value === null) {
                continue;
            }

            $rule = $definition->getAttributeRule($field);
            if ($rule === null || !isset($rule['type'])) {
                continue;
            }

            $type = $rule['type'];
            $pointer = '/data/attributes/' . $field;
            if ($type === 'integer' && !is_int($value)) {
                $this->addError($errors, 'invalid_attribute_type', 'Invalid Attribute Type', sprintf("Attribute '%s' must be an integer", $field), $pointer);
            }
            if ($type === 'numeric' && !is_int($value) && !is_float($value)) {
                $this->addError($errors, 'invalid_attribute_type', 'Invalid Attribute Type', sprintf("Attribute '%s' must be numeric", $field), $pointer);
            }
            if ($type === 'boolean' && !is_bool($value)) {
                $this->addError($errors, 'invalid_attribute_type', 'Invalid Attribute Type', sprintf("Attribute '%s' must be boolean", $field), $pointer);
            }
            if ($type === 'string' && !is_string($value)) {
                $this->addError($errors, 'invalid_attribute_type', 'Invalid Attribute Type', sprintf("Attribute '%s' must be string", $field), $pointer);
            }
        }
    }

    /**
     * @param array<int,array<string,mixed>> $errors
     */
    private function addError(array &$errors, $code, $title, $detail, $pointer)
    {
        $error = [
            'status' => '422',
            'code' => $code,
            'title' => $title,
            'detail' => $detail,
        ];
        if ($pointer !== null) {
            $error['source'] = [
                'pointer' => $pointer,
            ];
        }
        $errors[] = $error;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private function isEmptyValue($value)
    {
        if ($value === null) {
            return true;
        }
        if (is_string($value)) {
            return trim($value) === '';
        }
        return false;
    }
}
