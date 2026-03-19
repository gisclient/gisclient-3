<?php

namespace GisClient\Author\Api\Dto;

class ProjectSrsDto extends JsonApiDto
{
    public ?int $id = null;

    public int $srid;

    public ?string $projparam = null;

    public ProjectDto $project;
}
