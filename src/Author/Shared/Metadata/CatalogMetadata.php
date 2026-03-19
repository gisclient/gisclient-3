<?php

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\CatalogDto;
use GisClient\Author\Api\Dto\ProjectDto;

class CatalogMetadata extends Metadata
{
    public function __construct()
    {
        parent::__construct('catalog', CatalogDto::class, 'catalog_id', 'int');

        $this
            ->requiredOnCreate(['project', 'catalog_name', 'connection_type', 'catalog_path'])
            ->requiredOnPut(['catalog_name', 'connection_type', 'catalog_path'])
            ->filterable(['catalog_id', 'project_name', 'catalog_name', 'connection_type'])
            ->sortable(['catalog_id', 'catalog_name', 'connection_type'], 'catalog_name')
            ->addAttribute('catalog_name', 'catalogName', 'string')
            ->addAttribute('connection_type', 'connectionType', 'int', false, true, true, null, [
                'lookup' => [
                    'table' => 'e_conntype',
                    'column' => 'conntype_id',
                ],
            ])
            ->addAttribute('set_extent', 'setExtent', 'int', true)
            ->addAttribute('catalog_path', 'catalogPath', 'string')
            ->addAttribute('files_path', 'filesPath', 'string', true)
            ->addAttribute('catalog_description', 'catalogDescription', 'string', true)
            ->addRelationship('project', 'project', ProjectDto::class, 'project', false, true, true, true, 'project_name');
    }
}
