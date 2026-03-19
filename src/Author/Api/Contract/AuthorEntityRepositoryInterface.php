<?php

namespace GisClient\Author\Api\Contract;

use GisClient\Author\Api\Model\PagedResult;
use GisClient\Author\Api\Model\QueryOptions;
use GisClient\Author\Persistence\EntitySchema;

interface AuthorEntityRepositoryInterface
{
    /**
     * @return PagedResult
     */
    public function findAll(EntitySchema $schema, QueryOptions $queryOptions);

    /**
     * @param string|int $id
     * @return array|null
     */
    public function findById(EntitySchema $schema, $id);

    /**
     * @return array
     */
    public function create(EntitySchema $schema, array $attributes);

    /**
     * @param string|int $id
     * @return array
     */
    public function update(EntitySchema $schema, $id, array $attributes);

    /**
     * @param string|int $id
     */
    public function delete(EntitySchema $schema, $id);
}
