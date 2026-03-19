<?php

namespace GisClient\Author\Api\Contract;

use GisClient\Author\Api\Dto\Schema\ResourceSchema;
use GisClient\Author\Api\Model\PagedResult;
use GisClient\Author\Api\Model\QueryOptions;

interface AuthorEntityRepositoryInterface
{
    /**
     * @return PagedResult
     */
    public function findAll(ResourceSchema $schema, QueryOptions $queryOptions);

    /**
     * @param string|int $id
     * @return array|null
     */
    public function findById(ResourceSchema $schema, $id);

    /**
     * @return array
     */
    public function create(ResourceSchema $schema, array $attributes);

    /**
     * @param string|int $id
     * @return array
     */
    public function update(ResourceSchema $schema, $id, array $attributes);

    /**
     * @param string|int $id
     */
    public function delete(ResourceSchema $schema, $id);
}
