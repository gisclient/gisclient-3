<?php

declare(strict_types=1);

namespace GisClient\Author\Api\Dto;

class LayerGroupsDto extends JsonApiDto
{
    public ?int $id = null;

    public ?int $wms = null;

    public ?int $wfs = null;

    public ?int $wfst = null;

    public ?string $layerName = null;

    public LayerDto $layer;

    public GroupDto $group;
}
