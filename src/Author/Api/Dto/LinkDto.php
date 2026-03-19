<?php

namespace GisClient\Author\Api\Dto;

class LinkDto extends JsonApiDto
{
    public ?int $id = null;

    public string $linkName;

    public string $linkDef;

    public ?int $winw = null;

    public ?int $winh = null;

    public ProjectDto $project;
}
