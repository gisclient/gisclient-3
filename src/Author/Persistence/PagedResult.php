<?php

namespace GisClient\Author\Persistence;

class PagedResult
{
    private $items;
    private $total;
    private $limit;
    private $offset;

    public function __construct(array $items, $total, $limit, $offset)
    {
        $this->items = $items;
        $this->total = $total;
        $this->limit = $limit;
        $this->offset = $offset;
    }

    public function getItems()
    {
        return $this->items;
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
