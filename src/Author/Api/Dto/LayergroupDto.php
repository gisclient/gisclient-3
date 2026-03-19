<?php

namespace GisClient\Author\Api\Dto;

class LayergroupDto extends JsonApiDto
{
    public ?int $id = null;

    public string $layergroupName;

    public ?string $layergroupTitle = null;

    public ?int $layergroupOrder = null;

    public ?int $owstypeId = null;

    public ?int $layergroupMaxscale = null;

    public ?int $layergroupMinscale = null;

    public ?string $opacity = null;

    public ?int $outputformatId = null;

    public ?string $layers = null;

    public ?int $tilesExtentSrid = null;

    public ?string $tilesExtent = null;

    public ?string $url = null;

    public ?int $wmsversionId = null;

    public ?string $tileOrigin = null;

    public ?string $tileResolutions = null;

    public ?string $style = null;

    public ?string $tileMatrixSet = null;

    public ?string $sld = null;

    public ?string $metadataUrl = null;

    public ?int $gutter = null;

    public ?float $buffer = null;

    public ?int $isbaselayer = null;

    public ?float $transition = null;

    public ?float $layergroupSingle = null;

    public ?float $tiletypeId = null;

    public ThemeDto $theme;
}
