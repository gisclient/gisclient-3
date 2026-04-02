<?php

declare(strict_types=1);

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\GroupDto;

class GroupMetadata extends Metadata
{
    public function __construct()
    {
        parent::__construct('group', GroupDto::class, 'groupname', 'string', 'groups');

        $this
            ->requiredOnCreate(['groupname'])
            ->requiredOnPut(['groupname'])
            ->filterable(['groupname'])
            ->sortable(['groupname'], 'groupname')
            ->addAttribute('description', 'description', 'string', true);
    }
}
