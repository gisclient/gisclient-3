<?php

namespace GisClient\Author\Api\Dto;

class StyleDto extends JsonApiDto
{
    public ?int $id = null;

    public string $styleName;

    public ?int $styleOrder = null;

    public ?string $symbolName = null;

    public ?int $patternId = null;

    public ?string $color = null;

    public ?string $outlinecolor = null;

    public ?string $bgcolor = null;

    public ?string $maxsize = null;

    public ?string $minsize = null;

    public ?string $size = null;

    public ?string $maxwidth = null;

    public ?string $minwidth = null;

    public ?string $width = null;

    public ?string $angle = null;

    public ?string $styleDef = null;

    public ClassDto $class;
}
