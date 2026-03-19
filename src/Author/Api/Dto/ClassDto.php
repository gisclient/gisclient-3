<?php

namespace GisClient\Author\Api\Dto;

class ClassDto extends JsonApiDto
{
    public ?int $id = null;

    public string $className;

    public ?string $classTitle = null;

    public ?int $classOrder = null;

    public ?string $expression = null;

    public ?string $keyimage = null;

    public ?int $legendtypeId = null;

    public ?string $maxscale = null;

    public ?string $minscale = null;

    public ?string $classTemplate = null;

    public ?string $labelFont = null;

    public ?int $labelMaxsize = null;

    public ?int $labelMinsize = null;

    public ?string $labelSize = null;

    public ?string $classText = null;

    public ?string $labelColor = null;

    public ?string $labelOutlinecolor = null;

    public ?string $labelBgcolor = null;

    public ?string $labelPosition = null;

    public ?string $labelAngle = null;

    public ?string $labelDef = null;

    public ?int $labelForce = null;

    public ?int $labelPriority = null;

    public ?int $labelBuffer = null;

    public ?int $labelAntialias = null;

    public ?string $labelWrap = null;

    public ?LayerDto $layer = null;
}
