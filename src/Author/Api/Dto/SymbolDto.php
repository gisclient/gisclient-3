<?php

namespace GisClient\Author\Api\Dto;

class SymbolDto extends JsonApiDto
{
    public ?string $id = null;

    public string $symbolName;

    public ?int $symbolcategoryId = null;

    public ?int $icontype = null;

    public ?string $symbolDef = null;

    public ?string $symbolType = null;

    public ?string $fontName = null;

    public ?int $asciiCode = null;

    public ?int $filled = null;

    public ?string $points = null;

    public ?string $image = null;

    // symbol_image (bytea) is excluded from CRUD — handled only by export/import CLI commands
}
