<?php

namespace GisClient\Author\Api\Model;

class ResourceData
{
    /**
     * @var EntityDefinition
     */
    private $definition;

    /**
     * @var array<string,mixed>
     */
    private $row;

    /**
     * @param array<string,mixed> $row
     */
    public function __construct(EntityDefinition $definition, array $row)
    {
        $this->definition = $definition;
        $this->row = $row;
    }

    /**
     * @return EntityDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @return array<string,mixed>
     */
    public function getRow()
    {
        return $this->row;
    }
}
