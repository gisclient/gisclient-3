<?php

declare(strict_types=1);

namespace GisClient\Author\Api\Dto;

class GroupDto extends JsonApiDto
{
    public ?string $id = null;

    public ?string $description = null;
}
