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
        bool $writable = true
    ): self {
        return new self(
            $jsonApiName,
            $propertyName,
            $phpType,
            $nullable,
            $readable,
            $writable
        );
    }

    public static function relationship(
        string $jsonApiName,
        string $propertyName,
        string $targetClass,
        string $targetType,
        bool $nullable = false,
        bool $readable = true,
        bool $writable = true,
        bool $collection = false
    ): self {
        return new self(
            $jsonApiName,
            $propertyName,
            $targetClass,
            $nullable,
            $readable,
            $writable,
            $targetClass,
            $targetType,
            true,
            $collection
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

    public function __construct(
        string $jsonApiName,
        string $propertyName,
        string $phpType,
        bool $nullable,
        bool $readable,
        bool $writable,
        ?string $targetClass = null,
        ?string $targetType = null,
        bool $relationship = false,
        bool $collection = false
    ) {
        $this->jsonApiName = $jsonApiName;
        $this->propertyName = $propertyName;
        $this->phpType = $phpType;
        $this->nullable = $nullable;
        $this->readable = $readable;
        $this->writable = $writable;
        $this->targetClass = $targetClass;
        $this->targetType = $targetType;
        $this->relationship = $relationship;
        $this->collection = $collection;
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
}
