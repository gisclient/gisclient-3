<?php

namespace GisClient\Author\Api\Dto\Schema;

class FieldDefinition
{
    public static function attribute(
        string $jsonApiName,
        string $propertyName,
        string $phpType,
        bool $nullable = false,
        bool $readable = true,
        bool $writable = true,
        ?string $localKey = null
    ): self {
        return new self(
            $jsonApiName,
            $propertyName,
            $phpType,
            $nullable,
            $readable,
            $writable,
            $localKey ?? $jsonApiName
        );
    }

    public static function relationship(
        string $jsonApiName,
        string $propertyName,
        string $targetClass,
        string $targetType,
        ?string $localKey,
        bool $nullable = false,
        bool $readable = true,
        bool $writable = true,
        bool $allowIdentifierOnly = true
    ): self {
        return new self(
            $jsonApiName,
            $propertyName,
            $targetClass,
            $nullable,
            $readable,
            $writable,
            $localKey,
            $targetClass,
            $targetType,
            true,
            false,
            $allowIdentifierOnly
        );
    }

    /**
     * @var string
     */
    private $jsonApiName;

    /**
     * @var string
     */
    private $propertyName;

    /**
     * @var string
     */
    private $phpType;

    /**
     * @var bool
     */
    private $nullable;

    /**
     * @var bool
     */
    private $readable;

    /**
     * @var bool
     */
    private $writable;

    /**
     * @var string|null
     */
    private $localKey;

    /**
     * @var string|null
     */
    private $targetClass;

    /**
     * @var string|null
     */
    private $targetType;

    /**
     * @var bool
     */
    private $relationship;

    /**
     * @var bool
     */
    private $collection;

    /**
     * @var bool
     */
    private $allowIdentifierOnly;

    /**
     * @var array<string,mixed>
     */
    private $rules = [];

    public function __construct(
        string $jsonApiName,
        string $propertyName,
        string $phpType,
        bool $nullable,
        bool $readable,
        bool $writable,
        ?string $localKey = null,
        ?string $targetClass = null,
        ?string $targetType = null,
        bool $relationship = false,
        bool $collection = false,
        bool $allowIdentifierOnly = false
    ) {
        $this->jsonApiName = $jsonApiName;
        $this->propertyName = $propertyName;
        $this->phpType = $phpType;
        $this->nullable = $nullable;
        $this->readable = $readable;
        $this->writable = $writable;
        $this->localKey = $localKey;
        $this->targetClass = $targetClass;
        $this->targetType = $targetType;
        $this->relationship = $relationship;
        $this->collection = $collection;
        $this->allowIdentifierOnly = $allowIdentifierOnly;
    }

    public function getJsonApiName(): string
    {
        return $this->jsonApiName;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function getPhpType(): string
    {
        return $this->phpType;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function getLocalKey(): ?string
    {
        return $this->localKey;
    }

    public function getTargetClass(): ?string
    {
        return $this->targetClass;
    }

    public function getTargetType(): ?string
    {
        return $this->targetType;
    }

    public function isRelationship(): bool
    {
        return $this->relationship;
    }

    public function isCollection(): bool
    {
        return $this->collection;
    }

    public function allowIdentifierOnly(): bool
    {
        return $this->allowIdentifierOnly;
    }

    /**
     * @param array<string,mixed> $lookup
     */
    public function withLookup(array $lookup): self
    {
        $this->rules['lookup'] = $lookup;

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function withRule(string $name, $value): self
    {
        $this->rules[$name] = $value;

        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    public function getRules(): array
    {
        return $this->rules;
    }
}
