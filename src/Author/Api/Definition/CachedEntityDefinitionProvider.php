<?php

namespace GisClient\Author\Api\Definition;

use GisClient\Author\Api\Contract\EntityDefinitionProviderInterface;

class CachedEntityDefinitionProvider implements EntityDefinitionProviderInterface
{
    /**
     * @var EntityDefinitionProviderInterface
     */
    private $provider;

    /**
     * @var array
     */
    private $cache = [];

    public function __construct(EntityDefinitionProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    public function getEntityDefinition($entity)
    {
        if (!isset($this->cache[$entity])) {
            $this->cache[$entity] = $this->provider->getEntityDefinition($entity);
        }

        return $this->cache[$entity];
    }
}
