<?php

namespace GisClient\Author\Persistence;

class EntityQuery
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    private $offset;

    /**
     * @var string|null
     */
    private $sortField;

    /**
     * @var string
     */
    private $sortDirection;

    /**
     * @var array<string,mixed>
     */
    private $filters;

    /**
     * @param array<string,mixed> $filters
     */
    public function __construct(
        string $type,
        int $limit,
        int $offset,
        ?string $sortField,
        string $sortDirection,
        array $filters
    ) {
        $this->type = $type;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->sortField = $sortField;
        $this->sortDirection = $sortDirection;
        $this->filters = $filters;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getSortField(): ?string
    {
        return $this->sortField;
    }

    public function getSortDirection(): string
    {
        return $this->sortDirection;
    }

    /**
     * @return array<string,mixed>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
}
