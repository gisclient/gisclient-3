<?php

namespace GisClient\Author\Api\Model;

use GisClient\Author\Api\Dto\Schema\ResourceSchema;

class ResourceData
{
    /**
     * @var ResourceSchema
     */
    private $schema;

    /**
     * @var array<string,mixed>
     */
    private $row;

    /**
     * @param array<string,mixed> $row
     */
    public function __construct(ResourceSchema $schema, array $row)
    {
        $this->schema = $schema;
        $this->row = $row;
    }

    /**
     * @return ResourceSchema
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @return array<string,mixed>
     */
    public function getRow()
    {
        return $this->row;
    }
}
