<?php

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Dto\ProjectSrsDto;

class ProjectSrsMetadata extends Metadata
{
    public function __construct()
    {
        parent::__construct('project_srs', ProjectSrsDto::class, 'id', 'int');

        $this
            ->requiredOnCreate(['project', 'srid'])
            ->requiredOnPut(['project', 'srid'])
            ->filterable(['id', 'project_name', 'srid'])
            ->sortable(['id', 'srid'], 'srid')
            ->addAttribute('srid', 'srid', 'int')
            ->addAttribute('projparam', 'projparam', 'string', true)
            ->addRelationship('project', 'project', ProjectDto::class, 'project', false, true, true, true, 'project_name');
    }
}
