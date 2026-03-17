<?php

namespace GisClient\Author\Api\Definition;

use GisClient\Author\Api\Contract\EntityDefinitionProviderInterface;
use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Api\Dto\Schema\FieldDefinition;
use GisClient\Author\Api\Model\EntityDefinition;

class DtoEntityDefinitionProvider implements EntityDefinitionProviderInterface
{
    private const DEFAULT_DB_SCHEMA = 'gisclient_34';

    public function getEntityDefinition($entity)
    {
        $schema = DtoSchemaRegistry::schemaForType((string) $entity);

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
                if (
                    isset($rule['lookup']) &&
                    is_array($rule['lookup']) &&
                    !isset($rule['lookup']['schema'])
                ) {
                    $rule['lookup']['schema'] = $schema->getDbSchema() ?? self::DEFAULT_DB_SCHEMA;
                }
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
            $schema->getDbSchema() ?? self::DEFAULT_DB_SCHEMA,
            $schema->getTable() ?? $schema->getType(),
            $schema->getPrimaryKey(),
            $schema->getIdPhpType(),
            array_values(array_unique($readableFields)),
            array_values(array_unique(array_merge($writableFields, $schema->getScopeFields()))),
            $schema->getRequiredOnCreate(),
            $schema->getRequiredOnPut(),
            $schema->getFilterableFields() !== [] ? $schema->getFilterableFields() : array_values(array_unique($readableFields)),
            $schema->getSortableFields() !== [] ? $schema->getSortableFields() : [$schema->getPrimaryKey()],
            $schema->getDefaultSort() ?? $schema->getPrimaryKey(),
            $attributeRules,
            $schema->getScopeFields(),
            $relationships,
            array_values(array_unique($requiredRelationshipsOnWrite))
        );
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
            return $field->getRules() !== [] ? $field->getRules() : null;
        }

        return array_merge([
            'type' => $type,
        ], $field->getRules());
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
