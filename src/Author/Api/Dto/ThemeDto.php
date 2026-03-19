<?php

namespace GisClient\Author\Api\Dto;

class ThemeDto extends JsonApiDto
{
    public ?int $id = null;

    public string $themeName;

    public ?string $themeTitle = null;

    public ?int $themeOrder = null;

    public ?string $copyrightString = null;

    public ?string $symbolName = null;

    public ?float $themeSingle = null;

    public ?float $radio = null;

    public ?ProjectDto $project = null;
}
