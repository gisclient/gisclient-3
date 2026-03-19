<?php

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\ProjectDto;

class ProjectMetadata extends Metadata
{
    public function __construct()
    {
        parent::__construct('project', ProjectDto::class, 'project_name', 'string');

        $this
            ->requiredOnCreate(['project_name', 'project_title', 'project_srid', 'max_extent_scale', 'charset_encodings_id', 'default_language_id'])
            ->requiredOnPut(['project_title', 'project_srid', 'max_extent_scale', 'charset_encodings_id', 'default_language_id'])
            ->filterable(['project_name', 'project_title', 'default_language_id'])
            ->sortable(['project_name', 'project_title'], 'project_name')
            ->addAttribute('project_title', 'projectTitle', 'string', true)
            ->addAttribute('xc', 'xc', 'float', true)
            ->addAttribute('yc', 'yc', 'float', true)
            ->addAttribute('project_srid', 'projectSrid', 'int')
            ->addAttribute('max_extent_scale', 'maxExtentScale', 'float', true)
            ->addAttribute('charset_encodings_id', 'charsetEncodingsId', 'int', true, true, true, null, [
                'lookup' => [
                    'table' => 'e_charset_encodings',
                    'column' => 'charset_encodings_id',
                ],
            ])
            ->addAttribute('default_language_id', 'defaultLanguageId', 'string', false, true, true, null, [
                'lookup' => [
                    'table' => 'e_language',
                    'column' => 'language_id',
                ],
            ])
            ->addAttribute('base_path', 'basePath', 'string', true)
            ->addAttribute('base_url', 'baseUrl', 'string', true)
            ->addAttribute('imagelabel_text', 'imagelabelText', 'string', true)
            ->addAttribute('imagelabel_position', 'imagelabelPosition', 'string', true)
            ->addAttribute('imagelabel_offset_x', 'imagelabelOffsetX', 'int', true)
            ->addAttribute('imagelabel_offset_y', 'imagelabelOffsetY', 'int', true)
            ->addAttribute('imagelabel_font', 'imagelabelFont', 'string', true)
            ->addAttribute('imagelabel_size', 'imagelabelSize', 'int', true)
            ->addAttribute('imagelabel_color', 'imagelabelColor', 'string', true)
            ->addAttribute('icon_h', 'iconH', 'int', true)
            ->addAttribute('icon_w', 'iconW', 'int', true)
            ->addAttribute('project_note', 'projectNote', 'string', true);
    }
}
