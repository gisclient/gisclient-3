<?php

namespace GisClient\Author\Api\Definition;

use GisClient\Author\Api\Contract\EntityDefinitionProviderInterface;
use GisClient\Author\Api\Model\EntityDefinition;

class TabVisibilityEntityDefinitionProvider implements EntityDefinitionProviderInterface
{
    /**
     * @var EntityDefinitionProviderInterface
     */
    private $provider;

    /**
     * @var array
     */
    private $registry;

    /**
     * @var TabFieldExtractor
     */
    private $tabFieldExtractor;

    public function __construct(
        EntityDefinitionProviderInterface $provider,
        TabFieldExtractor $tabFieldExtractor,
        ?array $registry = null
    ) {
        $this->provider = $provider;
        $this->tabFieldExtractor = $tabFieldExtractor;
        $this->registry = $registry ?: EntityRegistry::getDefinitions();
    }

    public function getEntityDefinition($entity)
    {
        $definition = $this->provider->getEntityDefinition($entity);
        $config = $this->registry[$entity] ?? [];
        $tabFields = $this->tabFieldExtractor->extractFields($config['tab_file'] ?? '');

        $pk = $definition->getPrimaryKey();
        $scopeFields = $definition->getScopeFields();
        $relationships = $definition->getRelationships();
        $requiredRelationshipsOnWrite = $definition->getRequiredRelationshipsOnWrite();
        $relationshipKeys = [];
        foreach ($relationships as $relationship) {
            if (isset($relationship['local_key']) && is_string($relationship['local_key'])) {
                $relationshipKeys[] = $relationship['local_key'];
            }
        }
        $forcedReadableSet = array_fill_keys(array_merge($scopeFields, $relationshipKeys), true);
        $forcedWritableSet = array_fill_keys($scopeFields, true);
        $visibleSet = array_fill_keys($tabFields, true);

        $readableSet = array_fill_keys(
            array_values(array_filter($definition->getReadableFields(), static fn ($field) => isset($visibleSet[$field]) || isset($forcedReadableSet[$field]))),
            true
        );
        $readableFields = $this->orderByTabFields($tabFields, $readableSet);
        foreach (array_keys($forcedReadableSet) as $field) {
            if (!in_array($field, $readableFields, true) && in_array($field, $definition->getReadableFields(), true)) {
                $readableFields[] = $field;
            }
        }
        if (!in_array($pk, $readableFields, true)) {
            $readableFields[] = $pk;
        }

        $writableSet = array_fill_keys(
            array_values(array_filter($definition->getWritableFields(), static fn ($field) => isset($visibleSet[$field]) || isset($forcedWritableSet[$field]))),
            true
        );
        $writableFields = $this->orderByTabFields($tabFields, $writableSet);
        foreach (array_keys($forcedWritableSet) as $field) {
            if (!in_array($field, $writableFields, true) && in_array($field, $definition->getWritableFields(), true)) {
                $writableFields[] = $field;
            }
        }

        $requiredOnCreate = array_values(array_filter($definition->getRequiredOnCreate(), static fn ($field) => in_array($field, $writableFields, true) || $field === $pk));
        $requiredOnPut = array_values(array_filter($definition->getRequiredOnPut(), static fn ($field) => in_array($field, $writableFields, true)));

        $filterableFields = array_values(array_filter($definition->getFilterableFields(), static fn ($field) => isset($visibleSet[$field]) || $field === $pk || isset($forcedReadableSet[$field])));
        $sortableFields = array_values(array_filter($definition->getSortableFields(), static fn ($field) => isset($visibleSet[$field]) || $field === $pk || isset($forcedReadableSet[$field])));

        $defaultSort = $definition->getDefaultSort();
        if (!in_array($defaultSort, $sortableFields, true)) {
            $defaultSort = $pk;
            if (!in_array($defaultSort, $sortableFields, true)) {
                $sortableFields[] = $defaultSort;
            }
        }

        $attributeRules = [];
        foreach ($definition->getAttributeRules() as $field => $rule) {
            if (isset($visibleSet[$field]) || $field === $pk || isset($forcedReadableSet[$field])) {
                $attributeRules[$field] = $rule;
            }
        }

        return new EntityDefinition(
            $definition->getType(),
            $definition->getSchema(),
            $definition->getTable(),
            $pk,
            $definition->getIdType(),
            $readableFields,
            $writableFields,
            $requiredOnCreate,
            $requiredOnPut,
            $filterableFields,
            $sortableFields,
            $defaultSort,
            $attributeRules,
            $scopeFields,
            $relationships,
            $requiredRelationshipsOnWrite
        );
    }

    /**
     * @param string[] $tabFields
     * @param array<string,bool> $fieldSet
     * @return string[]
     */
    private function orderByTabFields(array $tabFields, array $fieldSet)
    {
        $ordered = [];
        foreach ($tabFields as $field) {
            if (isset($fieldSet[$field])) {
                $ordered[] = $field;
            }
        }

        return $ordered;
    }
}
