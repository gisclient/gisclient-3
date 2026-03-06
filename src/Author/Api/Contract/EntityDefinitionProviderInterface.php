<?php

namespace GisClient\Author\Api\Contract;

use GisClient\Author\Api\Model\EntityDefinition;

interface EntityDefinitionProviderInterface
{
    /**
     * @param string $entity
     * @return EntityDefinition
     */
    public function getEntityDefinition($entity);
}
