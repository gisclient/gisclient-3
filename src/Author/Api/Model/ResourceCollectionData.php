<?php

namespace GisClient\Author\Api\Model;

use GisClient\Author\Api\Dto\Schema\ResourceSchema;

class ResourceCollectionData
{
    /**
     * @var ResourceSchema
     */
    private $schema;

    /**
     * @var array<int,array<string,mixed>>
     */
    private $rows;

    /**
     * @var int
     */
    private $total;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    private $offset;

    /**
     * @param array<int,array<string,mixed>> $rows
     */
    public function __construct(ResourceSchema $schema, array $rows, $total, $limit, $offset)
    {
        $this->schema = $schema;
        $this->rows = $rows;
        $this->total = (int) $total;
        $this->limit = (int) $limit;
        $this->offset = (int) $offset;
    }

    /**
     * @return ResourceSchema
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getRows()
    {
        return $this->rows;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getOffset()
    {
        return $this->offset;
    }
}
