<?php

namespace GisClient\Author\Persistence;

class Entity
{
    public const OPERATION_READ = 'read';
    public const OPERATION_CREATE = 'create';
    public const OPERATION_UPDATE = 'update';

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $operation;

    /**
     * @var int|string|null
     */
    private $id;

    /**
     * @var array<string,mixed>
     */
    private $attributes;

    /**
     * @var array<string,array{type:?string,id:string|int|null}|null>
     */
    private $relationships;

    /**
     * @param int|string|null $id
     * @param array<string,mixed> $attributes
     * @param array<string,array{type:?string,id:string|int|null}|null> $relationships
     */
    public function __construct(string $type, string $operation, $id = null, array $attributes = [], array $relationships = [])
    {
        $this->type = $type;
        $this->operation = $operation;
        $this->id = $id;
        $this->attributes = $attributes;
        $this->relationships = $relationships;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function isCreate(): bool
    {
        return $this->operation === self::OPERATION_CREATE;
    }

    public function isRead(): bool
    {
        return $this->operation === self::OPERATION_READ;
    }

    public function isUpdate(): bool
    {
        return $this->operation === self::OPERATION_UPDATE;
    }

    /**
     * @return int|string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array<string,mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<string,array{type:?string,id:string|int|null}|null>
     */
    public function getRelationships(): array
    {
        return $this->relationships;
    }
}
