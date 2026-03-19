<?php

namespace GisClient\Author\Api\Validation;

use GisClient\Author\Api\Contract\AuthorEntityRepositoryInterface;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;
use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Persistence\EntitySchema;
use GisClient\Author\Persistence\EntitySchemaRegistry;

class PersistenceWriteValidator
{
    /**
     * @var \PDO|null
     */
    private $db;

    /**
     * @var callable|null
     */
    private $lookupExistsCallback;

    /**
     * @var AuthorEntityRepositoryInterface|null
     */
    private $repository;

    public function __construct(
        ?\PDO $db = null,
        ?callable $lookupExistsCallback = null,
        ?AuthorEntityRepositoryInterface $repository = null
    ) {
        $this->db = $db;
        $this->lookupExistsCallback = $lookupExistsCallback;
        $this->repository = $repository;
    }

    /**
     * @param bool $isCreate
     * @param bool $isPut
     */
    public function validateAndNormalize(ResourceSchema $schema, EntitySchema $entitySchema, array $attributes, $resourceId, $isCreate, $isPut)
    {
        $errors = [];
        $primaryKey = $schema->getPrimaryKey();

        if ($isCreate && $resourceId !== null) {
            $idValue = $resourceId;
            if ($this->isEmptyValue($idValue)) {
                $this->addError($errors, 'invalid_id', 'Invalid Resource Identifier', 'data.id cannot be empty', [
                    'id' => true,
                ]);
            } else {
                $normalizedId = $this->normalizeResourceId($schema, $idValue, $errors);
                if ($normalizedId === null) {
                    // keep collecting all errors in payload
                } elseif (array_key_exists($primaryKey, $attributes) && (string) $attributes[$primaryKey] !== (string) $normalizedId) {
                    $this->addError(
                        $errors,
                        'id_attribute_mismatch',
                        'Identifier Mismatch',
                        sprintf("data.id and data.attributes.%s must match", $primaryKey),
                        [
                            'id' => true,
                        ]
                    );
                } else {
                    $attributes[$primaryKey] = $normalizedId;
                }
            }
        }

        foreach ($attributes as $field => $value) {
            if (!in_array($field, $entitySchema->getWritableDbFields(), true) && $field !== $primaryKey) {
                $this->addError(
                    $errors,
                    'invalid_attribute',
                    'Invalid Attribute',
                    sprintf("Attribute '%s' is not writable", $field),
                    [
                        'attribute' => $field,
                    ]
                );
            }
        }

        if (isset($attributes[$primaryKey]) && !$isCreate) {
            $this->addError(
                $errors,
                'immutable_primary_key',
                'Immutable Primary Key',
                'Primary key cannot be changed',
                [
                    'attribute' => $primaryKey,
                ]
            );
        }

        if ($isPut) {
            $complete = [];
            foreach ($entitySchema->getWritableDbFields() as $field) {
                if ($field === $primaryKey) {
                    continue;
                }
                $complete[$field] = array_key_exists($field, $attributes) ? $attributes[$field] : null;
            }
            $attributes = $complete;
        }

        $requiredFields = $isCreate ? $schema->getRequiredOnCreate() : $schema->getRequiredOnPut();
        foreach ($requiredFields as $field) {
            $requiredColumn = $this->resolveRequiredFieldColumn($schema, $entitySchema, $field);
            if ($requiredColumn === null || !array_key_exists($requiredColumn, $attributes) || $this->isEmptyValue($attributes[$requiredColumn])) {
                $this->addError(
                    $errors,
                    'missing_required_attribute',
                    'Missing Required Attribute',
                    sprintf("Attribute '%s' is required", $field),
                    [
                        'attribute' => $field,
                    ]
                );
            }
        }

        $this->validateAttributeLookups($entitySchema, $attributes, $errors);

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        return $attributes;
    }

    /**
     * @param array<string,mixed> $attributes
     * @param array<string,array{type:?string,id:string|int|null}|null> $relationships
     */
    public function validateReferences(ResourceSchema $schema, EntitySchema $entitySchema, array $attributes, array $relationships): void
    {
        $errors = [];
        $this->validateRelationshipReferences($schema, $relationships, $errors);
        $this->validateAttributeLookups($entitySchema, $attributes, $errors);

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @param array<int,array<string,mixed>> $errors
     * @param mixed $idValue
     * @return int|string|null
     */
    private function normalizeResourceId(ResourceSchema $schema, $idValue, array &$errors)
    {
        $idType = strtolower((string) $schema->getIdPhpType());
        if (in_array($idType, ['int', 'integer'], true)) {
            if (is_int($idValue)) {
                return $idValue;
            }
            if (is_string($idValue) && preg_match('/^-?\d+$/', $idValue) === 1) {
                return (int) $idValue;
            }

            $this->addError($errors, 'invalid_id', 'Invalid Resource Identifier', 'data.id must be an integer identifier for this resource type', [
                'id' => true,
            ]);
            return null;
        }

        if (is_string($idValue)) {
            return $idValue;
        }
        if (is_scalar($idValue)) {
            return (string) $idValue;
        }

        $this->addError($errors, 'invalid_id', 'Invalid Resource Identifier', 'data.id must be a string identifier', [
            'id' => true,
        ]);
        return null;
    }

    /**
     * @param array<int,array<string,mixed>> $errors
     */
    private function validateAttributeLookups(EntitySchema $schema, array $attributes, array &$errors)
    {
        foreach ($attributes as $field => $value) {
            if ($value === null) {
                continue;
            }

            $rule = $this->resolveAttributeRule($schema, $field, $attributes);
            if ($rule === null || !isset($rule['lookup']) || !is_array($rule['lookup'])) {
                continue;
            }

            if (!$this->lookupValueExists($rule['lookup'], $value)) {
                $this->addError(
                    $errors,
                    'invalid_reference',
                    'Invalid Reference',
                    sprintf("Attribute '%s' references an unknown value", $field),
                    [
                        'attribute' => $field,
                    ]
                );
            }
        }
    }

    /**
     * @param array<string,array{type:?string,id:string|int|null}|null> $relationships
     * @param array<int,array<string,mixed>> $errors
     */
    private function validateRelationshipReferences(ResourceSchema $schema, array $relationships, array &$errors): void
    {
        if ($this->repository === null) {
            return;
        }

        foreach ($relationships as $relationshipName => $relationshipData) {
            if (!is_string($relationshipName) || !is_array($relationshipData) || !array_key_exists('id', $relationshipData)) {
                continue;
            }

            if ($relationshipData['id'] === null) {
                continue;
            }

            $relationship = $schema->getRelationship($relationshipName);
            if ($relationship === null) {
                continue;
            }

            $targetType = $relationship->getTargetType();
            if (!is_string($targetType) || trim($targetType) === '') {
                continue;
            }

            $targetSchema = EntitySchemaRegistry::schemaForType($targetType);
            if ($this->repository->findById($targetSchema, $relationshipData['id']) !== null) {
                continue;
            }

            $errors[] = [
                'status' => '422',
                'code' => 'invalid_relationship',
                'title' => 'Invalid Relationship',
                'detail' => sprintf("Relationship '%s' references an unknown resource", $relationshipName),
                'source' => [
                    'pointer' => '/data/relationships/' . $relationshipName . '/data/id',
                ],
            ];
        }
    }

    /**
     * @param array<string,mixed> $attributes
     * @return array<string,mixed>|null
     */
    private function resolveAttributeRule(EntitySchema $schema, string $field, array $attributes): ?array
    {
        $rule = $schema->getAttributeRule($field);
        if ($rule === null || !isset($rule['lookup']) || !is_array($rule['lookup'])) {
            return $rule;
        }

        $rule['lookup'] = $this->resolveLookupRuleFilters($rule['lookup'], $attributes);

        return $rule;
    }

    private function resolveRequiredFieldColumn(ResourceSchema $resourceSchema, EntitySchema $entitySchema, string $field): ?string
    {
        if ($field === $resourceSchema->getPrimaryKey()) {
            return $field;
        }

        if ($resourceSchema->getAttribute($field) !== null) {
            return $entitySchema->getAttributeColumn($field) ?? $field;
        }

        if ($resourceSchema->getRelationship($field) !== null) {
            return $entitySchema->getRelationshipColumn($field);
        }

        return $field;
    }

    /**
     * @param array<string,mixed> $lookupRule
     * @param array<string,mixed> $attributes
     * @return array<string,mixed>
     */
    private function resolveLookupRuleFilters(array $lookupRule, array $attributes): array
    {
        $filters = $lookupRule['filters'] ?? null;
        if (!is_array($filters)) {
            return $lookupRule;
        }

        $resolved = [];
        foreach ($filters as $column => $value) {
            if (!is_string($column)) {
                continue;
            }

            if (is_string($value) && strpos($value, 'from_attribute:') === 0) {
                $attributeName = substr($value, strlen('from_attribute:'));
                if ($attributeName === '' || !array_key_exists($attributeName, $attributes) || $attributes[$attributeName] === null) {
                    $lookupRule['skip_lookup'] = true;
                    continue;
                }
                $resolved[$column] = $attributes[$attributeName];
                continue;
            }

            $resolved[$column] = $value;
        }

        $lookupRule['resolved_filters'] = $resolved;

        return $lookupRule;
    }

    /**
     * @param array<string,mixed> $lookupRule
     * @param mixed $value
     * @return bool
     */
    private function lookupValueExists(array $lookupRule, $value)
    {
        if ($this->lookupExistsCallback !== null) {
            return (bool) call_user_func($this->lookupExistsCallback, $lookupRule, $value);
        }

        if (!empty($lookupRule['skip_lookup'])) {
            return true;
        }

        $db = $this->getDb();
        if ($db === null) {
            return true;
        }

        $schema = $lookupRule['schema'] ?? null;
        $table = $lookupRule['table'] ?? null;
        $column = $lookupRule['column'] ?? null;
        if (!is_string($schema) || !is_string($table) || !is_string($column)) {
            return true;
        }
        if (!$this->isSafeIdentifier($schema) || !$this->isSafeIdentifier($table) || !$this->isSafeIdentifier($column)) {
            return true;
        }

        $sql = sprintf('SELECT 1 FROM %s.%s WHERE %s = :value', $schema, $table, $column);
        $params = [
            ':value' => $value,
        ];

        $filters = $this->resolveLookupFilters($lookupRule, $params);
        if ($filters === null) {
            return true;
        }
        if ($filters !== []) {
            $sql .= ' AND ' . implode(' AND ', $filters);
        }
        $sql .= ' LIMIT 1';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * @param array<string,mixed> $lookupRule
     * @param array<string,mixed> $params
     * @return array<int,string>|null
     */
    private function resolveLookupFilters(array $lookupRule, array &$params): ?array
    {
        $filters = $lookupRule['resolved_filters'] ?? [];
        if (!is_array($filters)) {
            return [];
        }

        $clauses = [];
        $index = 0;
        foreach ($filters as $column => $value) {
            if (!is_string($column) || !$this->isSafeIdentifier($column)) {
                continue;
            }

            $placeholder = ':filter_' . $index;
            $clauses[] = sprintf('%s = %s', $column, $placeholder);
            $params[$placeholder] = $value;
            $index++;
        }

        return $clauses;
    }

    /**
     * @param string $identifier
     * @return bool
     */
    private function isSafeIdentifier($identifier)
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier) === 1;
    }

    /**
     * @return \PDO|null
     */
    private function getDb()
    {
        if ($this->db !== null) {
            return $this->db;
        }
        if (!class_exists('\GCApp')) {
            return null;
        }

        $this->db = \GCApp::getDB();
        return $this->db;
    }

    /**
     * @param array<int,array<string,mixed>> $errors
     */
    private function addError(array &$errors, $code, $title, $detail, array $source = [])
    {
        $error = [
            'status' => '422',
            'code' => $code,
            'title' => $title,
            'detail' => $detail,
        ];

        if ($source !== []) {
            $error['source'] = $source;
        }

        $errors[] = $error;
    }

    /**
     * @param mixed $value
     */
    private function isEmptyValue($value): bool
    {
        if ($value === null) {
            return true;
        }

        return is_string($value) && trim($value) === '';
    }
}
