<?php

namespace GisClient\Author\Api\Model;

class ResourceWriteData
{
    /**
     * @var string|int|null
     */
    private $id;

    /**
     * @var array<string,mixed>
     */
    private $attributes;

    /**
     * @var array<string,ResourceIdentifierData>
     */
    private $relationships;

    /**
     * @param string|int|null $id
     * @param array<string,mixed> $attributes
     * @param array<string,ResourceIdentifierData> $relationships
     */
    public function __construct($id = null, array $attributes = [], array $relationships = [])
    {
        $this->id = $id;
        $this->attributes = $attributes;
        $this->relationships = $relationships;
    }

    /**
     * @return string|int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array<string,mixed>
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return array<string,ResourceIdentifierData>
     */
    public function getRelationships()
    {
        return $this->relationships;
    }

    public function hasRelationship($name)
    {
        return array_key_exists($name, $this->relationships);
    }

    /**
     * @return ResourceIdentifierData|null
     */
    public function getRelationship($name)
    {
        return $this->relationships[$name] ?? null;
    }
}
