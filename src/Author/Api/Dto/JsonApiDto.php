<?php

namespace GisClient\Author\Api\Dto;

use GisClient\Author\Api\Dto\Support\DtoPropertyAccessor;

abstract class JsonApiDto
{
    /**
     * Tracks which JSON:API fields were actually present in the input payload.
     *
     * This is intentionally separate from PHP property initialization so the
     * write pipeline can distinguish between:
     * - field omitted from payload
     * - field explicitly sent as null
     * - field set programmatically on the DTO
     *
     * When constructing DTOs manually, assign the property value first and
     * then call markPresent() for the matching JSON:API field name.
     *
     * @var array<string,bool>
     */
    private $presentFields = [];

    /**
     * True when the DTO originated from a JSON:API resource identifier object
     * instead of a fully expanded resource object.
     *
     * @var bool
     */
    private $identifierOnly = false;

    /**
     * Marks a field as present in the JSON:API payload.
     *
     * This should be called after assigning the corresponding DTO property.
     * Example:
     * $dto->projectTitle = 'Milano';
     * $dto->markPresent('project_title');
     */
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

    /**
     * Marks the DTO as originating from a JSON:API resource identifier object.
     *
     * In practice this means the related resource was provided or built with
     * only type/id information and without embedded attributes or
     * relationships.
     */
    public function markAsIdentifierOnly(): void
    {
        $this->identifierOnly = true;
    }

    /**
     * Returns true when the DTO originated from identifier-only relationship
     * data.
     */
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
}
