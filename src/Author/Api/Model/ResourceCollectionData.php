<?php

namespace GisClient\Author\Api\Model;

class ResourceCollectionData
{
    /**
     * @var EntityDefinition
     */
    private $definition;

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
    public function __construct(EntityDefinition $definition, array $rows, $total, $limit, $offset)
    {
        $this->definition = $definition;
        $this->rows = $rows;
        $this->total = (int) $total;
        $this->limit = (int) $limit;
        $this->offset = (int) $offset;
    }

    /**
     * @return EntityDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
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
