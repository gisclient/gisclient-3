<?php

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\FontDto;

class FontMetadata extends Metadata
{
    public function __construct()
    {
        parent::__construct('font', FontDto::class, 'font_name', 'string');

        $this
            ->requiredOnCreate(['font_name', 'file_name'])
            ->requiredOnPut(['font_name', 'file_name'])
            ->filterable(['font_name', 'file_name'])
            ->sortable(['font_name'], 'font_name')
            ->addAttribute('file_name', 'fileName', 'string');
    }
}
