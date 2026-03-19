<?php

namespace GisClient\Author\Api\Dto;

class LayerDto extends JsonApiDto
{
    public ?int $id = null;

    public string $layerName;

    public ?string $layerTitle = null;

    public ?int $layerOrder = null;

    public ?string $opacity = null;

    public int $layertypeId;

    public ?string $dataType = null;

    public CatalogDto $catalog;

    public ?string $data = null;

    public ?string $dataGeom = null;

    public ?string $dataUnique = null;

    public ?int $dataSrid = null;

    public ?string $maxscale = null;

    public ?string $minscale = null;

    public ?int $symbolscale = null;

    public float $sizeunitsId;

    public ?string $dataExtent = null;

    public ?string $dataFilter = null;

    public ?string $layerDef = null;

    public ?string $metadata = null;

    public ?string $labelitem = null;

    public ?string $labelsizeitem = null;

    public ?string $labelmaxscale = null;

    public ?string $labelminscale = null;

    public ?float $postlabelcache = null;

    public ?string $classitem = null;

    public ?float $private = null;

    public ?float $queryable = null;

    public ?float $hideVectorGeom = null;

    public ?float $hidden = null;

    public ?float $searchableId = null;

    public ?string $template = null;

    public ?string $header = null;

    public ?string $footer = null;

    public ?int $tolerance = null;

    public ?float $toleranceunitsId = null;

    public ?float $selectionWidth = null;

    public ?string $selectionColor = null;

    public ?int $maxfeatures = null;

    public ?int $maxvectfeatures = null;

    public ?float $zoomBuffer = null;

    public ?string $lastUpdate = null;

    public LayergroupDto $layergroup;
}
