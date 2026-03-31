<?php

namespace GisClient\Author\Api\Dto;

class FontDto extends JsonApiDto
{
    public ?string $id = null;

    public string $fontName;

    public string $fileName;

    // font_data (bytea) is excluded from CRUD — handled only by export/import CLI commands
}
