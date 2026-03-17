<?php

namespace GisClient\Author\Api\Definition;

use GisClient\Author\Api\Contract\EntityDefinitionProviderInterface;
use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Api\Dto\Schema\FieldDefinition;
use GisClient\Author\Api\Model\EntityDefinition;

class DtoEntityDefinitionProvider implements EntityDefinitionProviderInterface
{
    private const DEFAULT_DB_SCHEMA = 'gisclient_34';

    /**
     * @var array<string,mixed>
     */
    private $registry;

    public function __construct()
    {
        $this->registry = EntityRegistry::getDefinitions();
    }

    public function getEntityDefinition($entity)
    {
        $schema = DtoSchemaRegistry::schemaForType((string) $entity);
        $registryEntry = $this->registry[(string) $entity] ?? [];

        $relationships = [];
        $readableFields = [$schema->getPrimaryKey()];
        $writableFields = [];
        $attributeRules = [];

        foreach ($schema->getAttributes() as $field) {
            $localKey = $field->getLocalKey() ?? $field->getJsonApiName();
            if ($field->isReadable()) {
                $readableFields[] = $localKey;
            }
            if ($field->isWritable()) {
                $writableFields[] = $localKey;
            }

            $rule = $this->attributeRuleFromField($field);
            if ($rule !== null) {
                $attributeRules[$localKey] = $rule;
            }
        }

        $requiredRelationshipsOnWrite = [];
        foreach ($schema->getRelationships() as $field) {
            $relationships[$field->getJsonApiName()] = [
                'type' => $field->getTargetType(),
                'local_key' => $field->getLocalKey(),
            ];

            if ($field->getLocalKey() !== null) {
                $readableFields[] = $field->getLocalKey();
            }

            if (
                $field->getLocalKey() !== null &&
                (in_array($field->getLocalKey(), $schema->getRequiredOnCreate(), true) || in_array($field->getLocalKey(), $schema->getRequiredOnPut(), true))
            ) {
                $requiredRelationshipsOnWrite[] = $field->getJsonApiName();
            }
        }

        return new EntityDefinition(
            $schema->getType(),
            $registryEntry['schema'] ?? self::DEFAULT_DB_SCHEMA,
            $registryEntry['table'] ?? $schema->getType(),
            $schema->getPrimaryKey(),
            $schema->getIdPhpType(),
            array_values(array_unique($readableFields)),
            array_values(array_unique(array_merge($writableFields, $registryEntry['scope_fields'] ?? []))),
            $schema->getRequiredOnCreate(),
            $schema->getRequiredOnPut(),
            $registryEntry['filterable_fields'] ?? array_values(array_unique($readableFields)),
            $registryEntry['sortable_fields'] ?? [$schema->getPrimaryKey()],
            $registryEntry['default_sort'] ?? $schema->getPrimaryKey(),
            $this->mergeAttributeRules($attributeRules, $registryEntry['attribute_rules'] ?? []),
            $registryEntry['scope_fields'] ?? [],
            $relationships,
            array_values(array_unique($requiredRelationshipsOnWrite))
        );
    }

    /**
     * @param array<string,array<string,mixed>> $baseRules
     * @param array<string,array<string,mixed>> $overrideRules
     * @return array<string,array<string,mixed>>
     */
    private function mergeAttributeRules(array $baseRules, array $overrideRules): array
    {
        foreach ($overrideRules as $field => $rule) {
            if (!is_string($field) || !is_array($rule)) {
                continue;
            }

            $baseRules[$field] = array_merge($baseRules[$field] ?? [], $rule);
        }

        return $baseRules;
    }

    /**
     * @return array<string,string>|null
     */
    private function attributeRuleFromField(FieldDefinition $field): ?array
    {
        if ($field->isRelationship()) {
            return null;
        }

        $type = $this->normalizeRuleType($field->getPhpType());
        if ($type === null) {
            return null;
        }

        return [
            'type' => $type,
        ];
    }

    private function normalizeRuleType(string $phpType): ?string
    {
        if ($phpType === 'int') {
            return 'integer';
        }

        if ($phpType === 'float') {
            return 'numeric';
        }

        if ($phpType === 'bool') {
            return 'boolean';
        }

        if ($phpType === 'string') {
            return 'string';
        }

        return null;
    }
}
