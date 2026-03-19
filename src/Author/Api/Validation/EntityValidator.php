<?php

namespace GisClient\Author\Api\Validation;

use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\Persistence\EntityRepository;
use GisClient\Author\Persistence\Entity;
use GisClient\Author\Persistence\EntityRef;
use GisClient\Author\Persistence\EntitySchema;
use GisClient\Author\Persistence\EntitySchemaRegistry;

class EntityValidator
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
     * @var EntityRepository|null
     */
    private $repository;

    public function __construct(
        ?\PDO $db = null,
        ?callable $lookupExistsCallback = null,
        ?EntityRepository $repository = null
    ) {
        $this->db = $db;
        $this->lookupExistsCallback = $lookupExistsCallback;
        $this->repository = $repository;
    }

    public function validate(Entity $entity): void
    {
        $resourceSchema = DtoSchemaRegistry::schemaForType($entity->getType());
        $entitySchema = EntitySchemaRegistry::schemaForType($entity->getType());
        $attributes = $entity->getAttributes();
        $errors = [];
        $primaryKey = $resourceSchema->getPrimaryKey();

        $this->assertNoDuplicatePrimaryKeyOnCreate($entity);

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

        if (isset($attributes[$primaryKey]) && !$entity->isCreate()) {
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

        $requiredFields = $entity->isCreate() ? $resourceSchema->getRequiredOnCreate() : $resourceSchema->getRequiredOnPut();
        foreach ($requiredFields as $field) {
            $requiredColumn = $this->resolveRequiredFieldColumn($resourceSchema, $entitySchema, $field);
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

        $this->validateRelationshipReferences($resourceSchema, $entity->getRelationships(), $errors);
        $this->validateAttributeLookups($entitySchema, $attributes, $errors);

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }

    private function assertNoDuplicatePrimaryKeyOnCreate(Entity $entity): void
    {
        if (!$entity->isCreate() || $entity->getId() === null || $this->repository === null) {
            return;
        }

        $existing = $this->repository->findById(new EntityRef($entity->getType(), $entity->getId()));
        if ($existing === null) {
            return;
        }

        throw new ApiException(
            409,
            'duplicate_primary_key',
            'Conflict',
            sprintf("Resource with primary key '%s' already exists", (string) $entity->getId()),
            '/data/id'
        );
    }

    /**
     * @param array<int,array<string,mixed>> $errors
     */
    private function validateAttributeLookups(EntitySchema $schema, array $attributes, array &$errors): void
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

            if ($this->repository->findById(new EntityRef($targetType, $relationshipData['id'])) !== null) {
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
     */
    private function lookupValueExists(array $lookupRule, $value): bool
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

        $resolvedFilters = $lookupRule['resolved_filters'] ?? [];
        foreach ($resolvedFilters as $filterColumn => $filterValue) {
            if (!is_string($filterColumn) || !$this->isSafeIdentifier($filterColumn)) {
                continue;
            }

            $placeholder = ':filter_' . count($params);
            $sql .= sprintf(' AND %s = %s', $filterColumn, $placeholder);
            $params[$placeholder] = $filterValue;
        }

        $sql .= ' LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    private function getDb(): ?\PDO
    {
        if ($this->db !== null) {
            return $this->db;
        }

        if (!\class_exists('GCApp', false)) {
            return null;
        }

        try {
            return \GCApp::getDB();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function isSafeIdentifier(string $value): bool
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value) === 1;
    }

    /**
     * @param mixed $value
     */
    private function isEmptyValue($value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    /**
     * @param array<int,array<string,mixed>> $errors
     * @param array<string,mixed> $source
     */
    private function addError(array &$errors, string $code, string $title, string $detail, array $source): void
    {
        $errors[] = [
            'status' => '422',
            'code' => $code,
            'title' => $title,
            'detail' => $detail,
            'source' => $source,
        ];
    }
}
