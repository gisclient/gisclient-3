<?php

use GisClient\Author\Api\Contract\AuthorEntityRepositoryInterface;
use GisClient\Author\Api\Model\PagedResult;
use GisClient\Author\Persistence\Entity;
use GisClient\Author\Persistence\EntityQuery;
use GisClient\Author\Persistence\EntityRef;
use GisClient\Author\Persistence\EntitySchemaRegistry;

final class AuthorEntityRepositoryStub implements AuthorEntityRepositoryInterface
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
            return ($this->findByIdCallback)($ref);
        }

        $key = (string) $ref->getId();
        if (!isset($this->existingIds[$key])) {
            if ($this->writeEntityContext !== null && $ref->getType() !== $this->writeEntityContext) {
                return [
                    $schema->getPrimaryKey() => $schema->getIdPhpType() === 'int' ? (int) $ref->getId() : (string) $ref->getId(),
                ];
            }

            return null;
        }

        return [
            $schema->getPrimaryKey() => $schema->getIdPhpType() === 'int' ? (int) $ref->getId() : (string) $ref->getId(),
            'project_title' => 'Project',
        ];
    }

    public function create(Entity $entity)
    {
        $this->createdEntity = $entity;
        $this->createdAttributes = $entity->getAttributes();

        if ($this->createCallback !== null) {
            return ($this->createCallback)($entity);
        }

        $primaryKey = EntitySchemaRegistry::schemaForType($entity->getType())->getPrimaryKey();
        if ($primaryKey !== null && isset($this->createdAttributes[$primaryKey])) {
            $this->existingIds[(string) $this->createdAttributes[$primaryKey]] = true;
        }

        return $this->createdAttributes;
    }

    public function update(Entity $entity)
    {
        $this->updatedEntity = $entity;
        $this->updatedAttributes = $entity->getAttributes();

        if ($this->updateCallback !== null) {
            return ($this->updateCallback)($entity);
        }

        $schema = EntitySchemaRegistry::schemaForType($entity->getType());
        $defaults = [
            $schema->getPrimaryKey() => $schema->getIdPhpType() === 'int' ? (int) $entity->getId() : (string) $entity->getId(),
        ];

        return array_merge($defaults, $this->updatedAttributes);
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
}
