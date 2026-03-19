<?php

namespace GisClient\Author\Api\Dto;

class ProjectDto extends JsonApiDto
{
    public ?string $id = null;

    public ?string $projectTitle = null;

    public ?float $xc = null;

    public ?float $yc = null;

    public int $projectSrid;

    public ?float $maxExtentScale = null;

    public ?int $charsetEncodingsId = null;

    public string $defaultLanguageId;

    public ?string $basePath = null;

    public ?string $baseUrl = null;

    public ?string $imagelabelText = null;

    public ?string $imagelabelPosition = null;

    public ?int $imagelabelOffsetX = null;

    public ?int $imagelabelOffsetY = null;

    public ?string $imagelabelFont = null;

    public ?int $imagelabelSize = null;

    public ?string $imagelabelColor = null;

    public ?int $iconH = null;

    public ?int $iconW = null;

    public ?string $projectNote = null;
}
