<?php

use GisClient\Author\Api\Contract\AuthorEntityRepositoryInterface;
use GisClient\Author\Api\Model\EntityDefinition;
use GisClient\Author\Api\Model\PagedResult;
use GisClient\Author\Api\Model\QueryOptions;

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

    public function findAll(EntityDefinition $definition, QueryOptions $queryOptions, array $scopeFilters = [])
    {
        if ($this->findAllCallback !== null) {
            return ($this->findAllCallback)($definition, $queryOptions, $scopeFilters);
        }

        return new PagedResult([], 0, 50, 0);
    }

    public function findById(EntityDefinition $definition, $id, array $scopeFilters = [])
    {
        if ($this->findByIdCallback !== null) {
            return ($this->findByIdCallback)($definition, $id, $scopeFilters);
        }

        $scopeField = $definition->getScopeFields()[0] ?? null;
        $scopeKey = ($scopeField !== null && isset($scopeFilters[$scopeField])) ? (string) $scopeFilters[$scopeField] : null;
        $key = $scopeKey !== null ? ($scopeKey . '|' . (string) $id) : (string) $id;
        if (!isset($this->existingIds[$key])) {
            if ($this->writeEntityContext !== null && $definition->getType() !== $this->writeEntityContext) {
                return [
                    $definition->getPrimaryKey() => $definition->getIdType() === 'int' ? (int) $id : (string) $id,
                ];
            }

            return null;
        }

        return [
            $definition->getPrimaryKey() => $definition->getIdType() === 'int' ? (int) $id : (string) $id,
            $scopeField ?? 'project_name' => $scopeKey ?? (string) $id,
            'project_title' => 'Project',
        ];
    }

    public function create(EntityDefinition $definition, array $attributes)
    {
        $this->createdAttributes = $attributes;

        if ($this->createCallback !== null) {
            return ($this->createCallback)($definition, $attributes);
        }

        $scopeField = $definition->getScopeFields()[0] ?? null;
        $primaryKey = $definition->getPrimaryKey();
        if (isset($attributes[$primaryKey])) {
            if ($scopeField !== null && isset($attributes[$scopeField])) {
                $this->existingIds[(string) $attributes[$scopeField] . '|' . (string) $attributes[$primaryKey]] = true;
            } else {
                $this->existingIds[(string) $attributes[$primaryKey]] = true;
            }
        } elseif ($scopeField !== null && isset($attributes[$scopeField])) {
            $this->existingIds[(string) $attributes[$scopeField]] = true;
        }

        return $attributes;
    }

    public function update(EntityDefinition $definition, $id, array $attributes, array $scopeFilters = [])
    {
        $this->updatedAttributes = $attributes;

        if ($this->updateCallback !== null) {
            return ($this->updateCallback)($definition, $id, $attributes, $scopeFilters);
        }

        $defaults = [
            $definition->getPrimaryKey() => $definition->getIdType() === 'int' ? (int) $id : (string) $id,
        ];
        foreach ($definition->getScopeFields() as $scopeField) {
            if (isset($scopeFilters[$scopeField])) {
                $defaults[$scopeField] = (string) $scopeFilters[$scopeField];
            }
        }

        return array_merge($defaults, $attributes);
    }

    public function delete(EntityDefinition $definition, $id, array $scopeFilters = [])
    {
        if ($this->deleteCallback !== null) {
            ($this->deleteCallback)($definition, $id, $scopeFilters);
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
