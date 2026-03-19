<?php

namespace GisClient\Author\Api\Dto;

class PagedResultDto
{
    /**
     * @var array<int,JsonApiDto>
     */
    private $items;

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
     * @param array<int,JsonApiDto> $items
     */
    public function __construct(array $items, int $total, int $limit, int $offset)
    {
        $this->items = $items;
        $this->total = $total;
        $this->limit = $limit;
        $this->offset = $offset;
    }

    /**
     * @return array<int,JsonApiDto>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }
}
