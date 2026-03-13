<?php

namespace GisClient\Author\Api\Serializer;

use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Model\EntityDefinition;
use GisClient\Author\Api\Model\ResourceCollectionData;
use GisClient\Author\Api\Model\ResourceData;
use GisClient\Author\Api\Model\ResourceIdentifierData;
use GisClient\Author\Api\Model\ResourceWriteData;

class JsonApiSerializer
{
    /**
     * @return ResourceWriteData
     */
    public function deserializeRequestBody($content, $expectedType)
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

        $attributes = $data['attributes'] ?? null;
        if (!is_array($attributes)) {
            throw new ApiException(400, 'invalid_attributes', 'Invalid Attributes', 'Payload must include data.attributes object', '/data/attributes');
        }

        $relationships = [];
        if (isset($data['relationships'])) {
            if (!is_array($data['relationships'])) {
                throw new ApiException(400, 'invalid_relationships', 'Invalid Relationships', 'Payload data.relationships must be an object', '/data/relationships');
            }

            foreach ($data['relationships'] as $name => $relationship) {
                if (!is_string($name) || !is_array($relationship)) {
                    continue;
                }

                $relationshipData = $relationship['data'] ?? null;
                if ($relationshipData === null) {
                    $relationships[$name] = new ResourceIdentifierData();
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

                $relationships[$name] = new ResourceIdentifierData(
                    isset($relationshipData['type']) ? (string) $relationshipData['type'] : null,
                    $relationshipData['id'] ?? null
                );
            }
        }

        return new ResourceWriteData($data['id'] ?? null, $attributes, $relationships);
    }

    /**
     * @return array<string,mixed>
     */
    public function serializeResource(ResourceData $resource)
    {
        return [
            'data' => $this->serializeResourceObject($resource->getDefinition(), $resource->getRow()),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function serializeCollection(ResourceCollectionData $collection)
    {
        $data = [];
        foreach ($collection->getRows() as $row) {
            $data[] = $this->serializeResourceObject($collection->getDefinition(), $row);
        }

        return [
            'data' => $data,
            'meta' => [
                'total' => $collection->getTotal(),
                'limit' => $collection->getLimit(),
                'offset' => $collection->getOffset(),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function serializeResourceObject(EntityDefinition $definition, array $row)
    {
        $primaryKey = $definition->getPrimaryKey();
        if (!array_key_exists($primaryKey, $row)) {
            throw new ApiException(500, 'invalid_resource', 'Invalid Resource', sprintf("Primary key '%s' missing in resource row", $primaryKey));
        }

        $resource = [
            'type' => $definition->getType(),
            'id' => (string) $row[$primaryKey],
            'attributes' => [],
        ];

        $relationshipKeys = [];
        $relationships = $this->buildRelationships($definition, $row);
        if (count($relationships) > 0) {
            $resource['relationships'] = $relationships;
            foreach ($definition->getRelationships() as $relationship) {
                if (isset($relationship['local_key']) && is_string($relationship['local_key'])) {
                    $relationshipKeys[$relationship['local_key']] = true;
                }
            }
        }

        foreach ($definition->getReadableFields() as $field) {
            if ($field === '*' || $field === $primaryKey || isset($relationshipKeys[$field])) {
                continue;
            }
            if (array_key_exists($field, $row)) {
                $resource['attributes'][$field] = $this->castAttributeValue($definition, $field, $row[$field]);
            }
        }

        return $resource;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function castAttributeValue(EntityDefinition $definition, $field, $value)
    {
        if ($value === null) {
            return null;
        }

        $rule = $definition->getAttributeRule($field);
        $type = is_array($rule) ? ($rule['type'] ?? null) : null;
        if (!is_string($type)) {
            return $value;
        }

        if ($type === 'integer') {
            if (is_int($value)) {
                return $value;
            }
            if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
                return (int) $value;
            }
            return $value;
        }

        if ($type === 'boolean') {
            if (is_bool($value)) {
                return $value;
            }
            if (is_int($value)) {
                if ($value === 0) {
                    return false;
                }
                if ($value === 1) {
                    return true;
                }
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
            return $value;
        }

        if ($type === 'numeric') {
            if (is_int($value) || is_float($value)) {
                return $value;
            }
            if (is_string($value)) {
                $normalized = trim($value);
                if ($normalized === '' || !is_numeric($normalized)) {
                    return $value;
                }
                if (preg_match('/^[+-]?\d+$/', $normalized) === 1) {
                    $intValue = filter_var($normalized, FILTER_VALIDATE_INT);
                    return $intValue === false ? $value : $intValue;
                }
                return (float) $normalized;
            }
            return $value;
        }

        return $value;
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,array{data:array<string,mixed>|null}>
     */
    private function buildRelationships(EntityDefinition $definition, array $row)
    {
        $relationships = [];
        foreach ($definition->getRelationships() as $name => $relationship) {
            $localKey = $relationship['local_key'] ?? null;
            $type = $relationship['type'] ?? null;
            if (!is_string($name) || !is_string($localKey) || !is_string($type)) {
                continue;
            }

            $data = null;
            if (array_key_exists($localKey, $row) && $row[$localKey] !== null) {
                $data = [
                    'type' => $type,
                    'id' => (string) $row[$localKey],
                ];
            }

            if ($data === null) {
                continue;
            }

            $relationships[$name] = [
                'data' => $data,
            ];
        }

        return $relationships;
    }
}
