<?php

namespace GisClient\Author\Persistence;

interface EntityRepository
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
