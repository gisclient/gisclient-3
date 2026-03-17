<?php

namespace GisClient\Author\Api\Dto;

use GisClient\Author\Api\Dto\Support\DtoPropertyAccessor;

abstract class JsonApiDto
{
    /**
     * @var array<string,bool>
     */
    private $presentFields = [];

    /**
     * @var bool
     */
    private $identifierOnly = false;

    /**
     * @var array<string,mixed>
     */
    private $extraAttributes = [];

    /**
     * @var array<string,mixed>
     */
    private $extraRelationships = [];

    public function markPresent(string $field): void
    {
        $this->presentFields[$field] = true;
    }

    public function isPresent(string $field): bool
    {
        return isset($this->presentFields[$field]);
    }

    /**
     * @return array<int,string>
     */
    public function presentFields(): array
    {
        return array_keys($this->presentFields);
    }

    public function markAsIdentifierOnly(): void
    {
        $this->identifierOnly = true;
    }

    public function isIdentifierOnly(): bool
    {
        return $this->identifierOnly;
    }

    /**
     * @return string|int|null
     */
    public function getId()
    {
        if (!property_exists($this, 'id') || !DtoPropertyAccessor::isInitialized($this, 'id')) {
            return null;
        }

        return DtoPropertyAccessor::get($this, 'id');
    }

    /**
     * @param mixed $value
     */
    public function addExtraAttribute(string $name, $value): void
    {
        $this->extraAttributes[$name] = $value;
        $this->markPresent($name);
    }

    /**
     * @return array<string,mixed>
     */
    public function extraAttributes(): array
    {
        return $this->extraAttributes;
    }

    /**
     * @param mixed $value
     */
    public function addExtraRelationship(string $name, $value): void
    {
        $this->extraRelationships[$name] = $value;
        $this->markPresent($name);
    }

    /**
     * @return array<string,mixed>
     */
    public function extraRelationships(): array
    {
        return $this->extraRelationships;
    }
}
