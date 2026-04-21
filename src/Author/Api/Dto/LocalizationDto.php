<?php

namespace GisClient\Author\Api\Dto;

class LocalizationDto extends JsonApiDto
{
    public ?int $id = null;

    public string $pkeyId;

    public ?string $languageId = null;

    public ?string $value = null;

    public ?int $i18nfId = null;

    public ProjectDto $project;
}
