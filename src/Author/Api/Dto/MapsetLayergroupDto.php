<?php

namespace GisClient\Author\Api\Dto;

class MapsetLayergroupDto extends JsonApiDto
{
    public ?int $id = null;

    public ?int $status = null;

    public ?int $refmap = null;

    public ?int $hide = null;

    public MapsetDto $mapset;

    public LayergroupDto $layergroup;
}
