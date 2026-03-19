<?php

namespace GisClient\Author\Api\Dto;

class CatalogDto extends JsonApiDto
{
    public ?int $id = null;

    public string $catalogName;

    public int $connectionType;

    public ?int $setExtent = null;

    public string $catalogPath;

    public ?string $filesPath = null;

    public ?string $catalogDescription = null;

    public ProjectDto $project;
}
