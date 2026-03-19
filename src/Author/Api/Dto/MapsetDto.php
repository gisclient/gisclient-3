<?php

namespace GisClient\Author\Api\Dto;

class MapsetDto extends JsonApiDto
{
    public ?string $id = null;

    public ?string $mapsetTitle = null;

    public ?int $maxscale = null;

    public ?int $minscale = null;

    public ?int $mapsetSrid = null;

    public ?int $displayprojection = null;

    public ?int $sizeunitsId = null;

    public ?string $mapsetScales = null;

    public ?string $mapsetExtent = null;

    public ?string $refmapExtent = null;

    public ?string $template = null;

    public int $mapsetScaleType;

    public int $mapsetOrder;

    public ?int $private = null;

    public ?string $mapsetDescription = null;

    public ?string $pageSize = null;

    public ?string $dlImageRes = null;

    public ?string $mapsetDef = null;

    public ?string $metadata = null;

    public ?string $geolocator = null;

    public ?string $bgColor = null;

    public ?int $staticReference = null;

    public ?int $mapsetTiles = null;

    public ProjectDto $project;
}
