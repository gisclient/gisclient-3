<?php

namespace GisClient\Author\Api\Model;

class QueryOptions
{
    private $limit;
    private $offset;
    private $sortField;
    private $sortDirection;
    private $filters;

    public function __construct($limit = 50, $offset = 0, $sortField = null, $sortDirection = 'ASC', array $filters = [])
    {
        $this->limit = $limit;
        $this->offset = $offset;
        $this->sortField = $sortField;
        $this->sortDirection = strtoupper($sortDirection) === 'DESC' ? 'DESC' : 'ASC';
        $this->filters = $filters;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function getSortField()
    {
        return $this->sortField;
    }

    public function getSortDirection()
    {
        return $this->sortDirection;
    }

    public function getFilters()
    {
        return $this->filters;
    }
}
