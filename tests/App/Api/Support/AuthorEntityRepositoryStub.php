<?php

use GisClient\Author\Api\Contract\AuthorEntityRepositoryInterface;
use GisClient\Author\Api\Model\PagedResult;
use GisClient\Author\Api\Model\QueryOptions;
use GisClient\Author\Persistence\EntitySchema;

final class AuthorEntityRepositoryStub implements AuthorEntityRepositoryInterface
{
    public array $createdAttributes = [];

    public array $updatedAttributes = [];

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

    public function findAll(EntitySchema $schema, QueryOptions $queryOptions)
    {
        if ($this->findAllCallback !== null) {
            return ($this->findAllCallback)($schema, $queryOptions);
        }

        return new PagedResult([], 0, 50, 0);
    }

    public function findById(EntitySchema $schema, $id)
    {
        if ($this->findByIdCallback !== null) {
            return ($this->findByIdCallback)($schema, $id);
        }

        $key = (string) $id;
        if (!isset($this->existingIds[$key])) {
            if ($this->writeEntityContext !== null && $schema->getType() !== $this->writeEntityContext) {
                return [
                    $schema->getPrimaryKey() => $schema->getIdPhpType() === 'int' ? (int) $id : (string) $id,
                ];
            }

            return null;
        }

        return [
            $schema->getPrimaryKey() => $schema->getIdPhpType() === 'int' ? (int) $id : (string) $id,
            'project_title' => 'Project',
        ];
    }

    public function create(EntitySchema $schema, array $attributes)
    {
        $this->createdAttributes = $attributes;

        if ($this->createCallback !== null) {
            return ($this->createCallback)($schema, $attributes);
        }

        $primaryKey = $schema->getPrimaryKey();
        if (isset($attributes[$primaryKey])) {
            $this->existingIds[(string) $attributes[$primaryKey]] = true;
        }

        return $attributes;
    }

    public function update(EntitySchema $schema, $id, array $attributes)
    {
        $this->updatedAttributes = $attributes;

        if ($this->updateCallback !== null) {
            return ($this->updateCallback)($schema, $id, $attributes);
        }

        $defaults = [
            $schema->getPrimaryKey() => $schema->getIdPhpType() === 'int' ? (int) $id : (string) $id,
        ];

        return array_merge($defaults, $attributes);
    }

    public function delete(EntitySchema $schema, $id)
    {
        if ($this->deleteCallback !== null) {
            ($this->deleteCallback)($schema, $id);
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
