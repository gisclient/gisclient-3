<?php

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Dto\ThemeDto;

class ThemeMetadata extends Metadata
{
    public function __construct()
    {
        parent::__construct('theme', ThemeDto::class, 'theme_id', 'int');

        $this
            ->requiredOnCreate(['project', 'theme_name', 'theme_title', 'theme_order'])
            ->requiredOnPut(['theme_name', 'theme_title', 'theme_order'])
            ->filterable(['theme_id', 'project_name', 'theme_name', 'theme_title'])
            ->sortable(['theme_id', 'theme_order', 'theme_title', 'theme_name'], 'theme_order')
            ->addAttribute('theme_name', 'themeName', 'string')
            ->addAttribute('theme_title', 'themeTitle', 'string', true)
            ->addAttribute('theme_order', 'themeOrder', 'int', true)
            ->addAttribute('copyright_string', 'copyrightString', 'string', true)
            ->addAttribute('symbol_name', 'symbolName', 'string', true, true, true, null, [
                'lookup' => [
                    'table' => 'symbol',
                    'column' => 'symbol_name',
                ],
            ])
            ->addAttribute('theme_single', 'themeSingle', 'float', true)
            ->addAttribute('radio', 'radio', 'float', true)
            ->addRelationship('project', 'project', ProjectDto::class, 'project', true, true, true, true, 'project_name');
    }
}
