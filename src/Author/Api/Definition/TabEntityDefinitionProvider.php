<?php

namespace GisClient\Author\Api\Definition;

use GisClient\Author\Api\Contract\EntityDefinitionProviderInterface;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Model\EntityDefinition;

class TabEntityDefinitionProvider implements EntityDefinitionProviderInterface
{
    /**
     * @var array
     */
    private $registry;

    /**
     * @var TabFieldExtractor
     */
    private $tabFieldExtractor;

    public function __construct(?array $registry = null, ?TabFieldExtractor $tabFieldExtractor = null)
    {
        $this->registry = $registry ?: EntityRegistry::getDefinitions();
        $this->tabFieldExtractor = $tabFieldExtractor ?: new TabFieldExtractor();
    }

    public function getEntityDefinition($entity)
    {
        if (!isset($this->registry[$entity])) {
            throw new ApiException(404, 'unknown_entity', 'Unknown Entity', sprintf("Entity '%s' is not managed by this API", $entity));
        }

        $config = $this->registry[$entity];
        $tabFields = $this->tabFieldExtractor->extractFields($config['tab_file']);

        $writableFields = array_merge($tabFields, $config['writable_extra'] ?? []);
        $readableFields = array_merge($tabFields, $config['readable_extra'] ?? []);

        $primaryKey = $config['primary_key'];
        if (!in_array($primaryKey, $readableFields, true)) {
            $readableFields[] = $primaryKey;
        }

        return new EntityDefinition(
            $entity,
            $config['schema'],
            $config['table'],
            $primaryKey,
            $config['id_type'] ?? 'string',
            $readableFields,
            $writableFields,
            $config['required_on_create'] ?? [],
            $config['required_on_put'] ?? [],
            $config['filterable_fields'] ?? [],
            $config['sortable_fields'] ?? [],
            $config['default_sort'] ?? $primaryKey
        );
    }
}
