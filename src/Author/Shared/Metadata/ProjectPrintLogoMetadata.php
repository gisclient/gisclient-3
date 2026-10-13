<?php

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Dto\ProjectPrintLogoDto;

class ProjectPrintLogoMetadata extends Metadata
{
    public const MAX_LOGO_BYTES = 1048576;

    public function __construct()
    {
        parent::__construct('project_print_logo', ProjectPrintLogoDto::class, 'project_print_logo_id', 'int');

        $this
            ->requiredOnCreate(['project', 'logo_position', 'logo_data'])
            ->requiredOnPut(['project', 'logo_position', 'logo_data'])
            ->filterable(['project_print_logo_id', 'project_name', 'logo_position'])
            ->sortable(['project_print_logo_id', 'logo_position'], 'logo_position')
            ->addAttribute('logo_position', 'logoPosition', 'string')
            ->addAttribute('logo_data', 'logoData', 'string', false, true, true, null, [
                'image' => [
                    'mime_types' => ['image/png', 'image/jpeg'],
                    'max_bytes' => self::MAX_LOGO_BYTES,
                ],
            ])
            ->addRelationship('project', 'project', ProjectDto::class, 'project', false, true, true, 'project_name');
    }
}
