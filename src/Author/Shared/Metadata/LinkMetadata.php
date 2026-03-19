<?php

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\LinkDto;
use GisClient\Author\Api\Dto\ProjectDto;

class LinkMetadata extends Metadata
{
    public function __construct()
    {
        parent::__construct('link', LinkDto::class, 'link_id', 'int');

        $this
            ->requiredOnCreate(['project', 'link_name', 'link_def'])
            ->requiredOnPut(['link_name', 'link_def'])
            ->filterable(['link_id', 'project_name', 'link_name', 'link_order'])
            ->sortable(['link_id', 'link_order', 'link_name'], 'link_order')
            ->addAttribute('link_name', 'linkName', 'string')
            ->addAttribute('link_def', 'linkDef', 'string')
            ->addAttribute('winw', 'winw', 'int', true)
            ->addAttribute('winh', 'winh', 'int', true)
            ->addRelationship('project', 'project', ProjectDto::class, 'project', false, true, true, 'project_name');
    }
}
