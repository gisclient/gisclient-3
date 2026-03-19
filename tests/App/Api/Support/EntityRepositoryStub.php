<?php

use GisClient\Author\Persistence\Entity;
use GisClient\Author\Persistence\EntityQuery;
use GisClient\Author\Persistence\EntityRef;
use GisClient\Author\Persistence\EntityRepository;
use GisClient\Author\Persistence\EntitySchemaRegistry;
use GisClient\Author\Persistence\PagedResult;

final class EntityRepositoryStub implements EntityRepository
{
    public array $createdAttributes = [];

    public array $updatedAttributes = [];

    public ?Entity $createdEntity = null;

    public ?Entity $updatedEntity = null;

    /**
     * @var array<string,bool>
     */
    private array $existingIds;

    /**
     * @var callable|null
     */
    private $findAllCallback;

    /**
     * @var callable|null
     */
    private $findByIdCallback;

    /**
     * @var callable|null
     */
    private $createCallback;

    /**
     * @var callable|null
     */
    private $updateCallback;

    /**
     * @var callable|null
     */
    private $deleteCallback;

    private ?string $writeEntityContext = null;

    public function __construct(
        array $existingIds = [],
        ?callable $findByIdCallback = null,
        ?callable $findAllCallback = null,
        ?callable $createCallback = null,
        ?callable $updateCallback = null,
        ?callable $deleteCallback = null
    ) {
        $this->existingIds = array_fill_keys($existingIds, true);
        $this->findByIdCallback = $findByIdCallback;
        $this->findAllCallback = $findAllCallback;
        $this->createCallback = $createCallback;
        $this->updateCallback = $updateCallback;
        $this->deleteCallback = $deleteCallback;
    }

    public function findAll(EntityQuery $query)
    {
        if ($this->findAllCallback !== null) {
            return ($this->findAllCallback)($query);
        }

        return new PagedResult([], 0, 50, 0);
    }

    public function findById(EntityRef $ref)
    {
        $schema = EntitySchemaRegistry::schemaForType($ref->getType());

        if ($this->findByIdCallback !== null) {
            $result = ($this->findByIdCallback)($ref);
            return $this->normalizeEntityResult($ref->getType(), $result);
        }

        $key = (string) $ref->getId();
        if (!isset($this->existingIds[$key])) {
            if ($this->writeEntityContext !== null && $ref->getType() !== $this->writeEntityContext) {
                return new Entity(
                    $ref->getType(),
                    Entity::OPERATION_READ,
                    $schema->getIdPhpType() === 'int' ? (int) $ref->getId() : (string) $ref->getId()
                );
            }

            return null;
        }

        return new Entity(
            $ref->getType(),
            Entity::OPERATION_READ,
            $schema->getIdPhpType() === 'int' ? (int) $ref->getId() : (string) $ref->getId(),
            [
                'project_title' => 'Project',
            ]
        );
    }

    public function create(Entity $entity)
    {
        $this->createdEntity = $entity;
        $this->createdAttributes = $entity->getAttributes();

        if ($this->createCallback !== null) {
            return $this->normalizeEntityResult($entity->getType(), ($this->createCallback)($entity));
        }

        $primaryKey = EntitySchemaRegistry::schemaForType($entity->getType())->getPrimaryKey();
        if ($primaryKey !== null && isset($this->createdAttributes[$primaryKey])) {
            $this->existingIds[(string) $this->createdAttributes[$primaryKey]] = true;
        }

        return new Entity(
            $entity->getType(),
            Entity::OPERATION_READ,
            $entity->getId(),
            $entity->getAttributes(),
            $entity->getRelationships()
        );
    }

    public function update(Entity $entity)
    {
        $this->updatedEntity = $entity;
        $this->updatedAttributes = $entity->getAttributes();

        if ($this->updateCallback !== null) {
            return $this->normalizeEntityResult($entity->getType(), ($this->updateCallback)($entity));
        }

        return new Entity(
            $entity->getType(),
            Entity::OPERATION_READ,
            $entity->getId(),
            $entity->getAttributes(),
            $entity->getRelationships()
        );
    }

    public function delete(EntityRef $ref)
    {
        if ($this->deleteCallback !== null) {
            ($this->deleteCallback)($ref);
        }
    }

    public function beginWriteContext(string $entity): void
    {
        $this->writeEntityContext = $entity;
    }

    public function endWriteContext(): void
    {
        $this->writeEntityContext = null;
    }

    /**
     * @param mixed $result
     */
    private function normalizeEntityResult(string $type, $result): ?Entity
    {
        if ($result === null || $result instanceof Entity) {
            return $result;
        }

        if (!is_array($result)) {
            throw new \RuntimeException('Unsupported repository stub result');
        }

        $schema = EntitySchemaRegistry::schemaForType($type);
        $id = $result[$schema->getPrimaryKey()] ?? null;
        $attributes = [];
        foreach ($schema->getReadableAttributeColumns() as $publicName => $column) {
            if (array_key_exists($column, $result)) {
                $attributes[$column] = $result[$column];
            }
        }

        $relationships = [];
        foreach ($schema->getReadableRelationshipColumns() as $relationshipName => $column) {
            if (array_key_exists($column, $result)) {
                $relationships[$relationshipName] = [
                    'type' => null,
                    'id' => $result[$column],
                ];
            }
        }

        return new Entity($type, Entity::OPERATION_READ, $id, $attributes, $relationships);
    }
}
