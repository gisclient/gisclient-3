<?php

namespace GisClient\Author\Api\Validation;

use GisClient\Author\Api\Contract\AuthorEntityRepositoryInterface;
use GisClient\Author\Api\Contract\EntityDefinitionProviderInterface;
use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\Model\EntityDefinition;

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
     * @var EntityDefinitionProviderInterface|null
     */
    private $definitionProvider;

    /**
     * @var AuthorEntityRepositoryInterface|null
     */
    private $repository;

    public function __construct(
        ?\PDO $db = null,
        ?callable $lookupExistsCallback = null,
        ?EntityDefinitionProviderInterface $definitionProvider = null,
        ?AuthorEntityRepositoryInterface $repository = null
    ) {
        $this->db = $db;
        $this->lookupExistsCallback = $lookupExistsCallback;
        $this->definitionProvider = $definitionProvider;
        $this->repository = $repository;
    }

    /**
     * @param array<string,mixed> $attributes
     * @param string|int|null $resourceId
     * @param bool $isCreate
     * @param bool $isPut
     * @return array<string,mixed>
     */
    public function validateAndNormalize(EntityDefinition $definition, array $attributes, $resourceId, $isCreate, $isPut)
    {
        $errors = [];
        $primaryKey = $definition->getPrimaryKey();

        if ($isCreate && $resourceId !== null) {
            $idValue = $resourceId;
            if ($this->isEmptyValue($idValue)) {
                $this->addError($errors, 'invalid_id', 'Invalid Resource Identifier', 'data.id cannot be empty', [
                    'id' => true,
                ]);
            } else {
                $normalizedId = $this->normalizeResourceId($definition, $idValue, $errors);
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
            if (!in_array($field, $definition->getWritableFields(), true) && $field !== $primaryKey) {
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
                    [
                        'attribute' => $field,
                    ]
                );
            }
        }

        $this->validateAttributeLookups($definition, $attributes, $errors);

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        return $attributes;
    }

    /**
     * @param array<string,mixed> $attributes
     * @param array<string,array{type:?string,id:string|int|null}|null> $relationships
     */
    public function validateReferences(EntityDefinition $definition, array $attributes, array $relationships): void
    {
        $errors = [];
        $this->validateRelationshipReferences($definition, $relationships, $errors);
        $this->validateAttributeLookups($definition, $attributes, $errors);

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @param array<int,array<string,mixed>> $errors
     * @param mixed $idValue
     * @return int|string|null
     */
    private function normalizeResourceId(EntityDefinition $definition, $idValue, array &$errors)
    {
        $idType = strtolower((string) $definition->getIdType());
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
    private function validateAttributeLookups(EntityDefinition $definition, array $attributes, array &$errors)
    {
        foreach ($attributes as $field => $value) {
            if ($value === null) {
                continue;
            }

            $rule = $definition->getAttributeRule($field);
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
    private function validateRelationshipReferences(EntityDefinition $definition, array $relationships, array &$errors): void
    {
        if ($this->definitionProvider === null || $this->repository === null) {
            return;
        }

        foreach ($relationships as $relationshipName => $relationshipData) {
            if (!is_string($relationshipName) || !is_array($relationshipData) || !array_key_exists('id', $relationshipData)) {
                continue;
            }

            if ($relationshipData['id'] === null) {
                continue;
            }

            $relationship = $definition->getRelationships()[$relationshipName] ?? null;
            if (!is_array($relationship)) {
                continue;
            }

            $targetType = $relationship['type'] ?? null;
            if (!is_string($targetType) || trim($targetType) === '') {
                continue;
            }

            $targetDefinition = $this->definitionProvider->getEntityDefinition($targetType);
            if ($this->repository->findById($targetDefinition, $relationshipData['id']) !== null) {
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
