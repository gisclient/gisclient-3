<?php

namespace GisClient\Author\Api\Contract;

use GisClient\Author\Api\Model\PagedResult;
use GisClient\Author\Persistence\Entity;
use GisClient\Author\Persistence\EntityQuery;
use GisClient\Author\Persistence\EntityRef;

interface AuthorEntityRepositoryInterface
{
    /**
     * @return PagedResult
     */
    public function findAll(EntityQuery $query);

    /**
     * @return Entity|null
     */
    public function findById(EntityRef $ref);

    /**
     * @return Entity
     */
    public function create(Entity $entity);

    /**
     * @return Entity
     */
    public function update(Entity $entity);

    public function delete(EntityRef $ref);
}
