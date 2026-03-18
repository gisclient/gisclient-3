<?php

namespace GisClient\Author\Api\Contract;

use GisClient\Author\Api\Model\EntityDefinition;
use GisClient\Author\Api\Model\PagedResult;
use GisClient\Author\Api\Model\QueryOptions;

interface AuthorEntityRepositoryInterface
{
    /**
     * @return PagedResult
     */
    public function findAll(EntityDefinition $definition, QueryOptions $queryOptions);

    /**
     * @param string|int $id
     * @return array|null
     */
    public function findById(EntityDefinition $definition, $id);

    /**
     * @return array
     */
    public function create(EntityDefinition $definition, array $attributes);

    /**
     * @param string|int $id
     * @return array
     */
    public function update(EntityDefinition $definition, $id, array $attributes);

    /**
     * @param string|int $id
     */
    public function delete(EntityDefinition $definition, $id);
}
