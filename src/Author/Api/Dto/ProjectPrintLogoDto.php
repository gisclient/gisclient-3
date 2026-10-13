<?php

namespace GisClient\Author\Api\Dto;

class ProjectPrintLogoDto extends JsonApiDto
{
    public ?int $id = null;

    public string $logoPosition;

    // Immagine PNG o JPEG codificata in base64 (senza prefisso data:)
    public string $logoData;

    public ProjectDto $project;
}
