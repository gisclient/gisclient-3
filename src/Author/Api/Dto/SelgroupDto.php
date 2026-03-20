<?php

namespace GisClient\Author\Api\Dto;

class SelgroupDto extends JsonApiDto
{
    public ?int $id = null;

    public string $selgroupName;

    public ?string $selgroupTitle = null;

    public ?int $selgroupOrder = null;

    public ProjectDto $project;
}
