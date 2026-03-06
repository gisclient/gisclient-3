<?php

namespace GisClient\Author\Api\Model;

class PagedResult
{
    private $rows;
    private $total;
    private $limit;
    private $offset;

    public function __construct(array $rows, $total, $limit, $offset)
    {
        $this->rows = $rows;
        $this->total = $total;
        $this->limit = $limit;
        $this->offset = $offset;
    }

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
