<?php

namespace GisClient\Author\Api\Dto;

class ProjectAdminDto extends JsonApiDto
{
    public string $username;

    public ProjectDto $project;
}
