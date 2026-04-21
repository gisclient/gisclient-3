<?php

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\LocalizationDto;
use GisClient\Author\Api\Dto\ProjectDto;

class LocalizationMetadata extends Metadata
{
    public function __construct()
    {
        parent::__construct('localization', LocalizationDto::class, 'localization_id', 'int');

        $this
            ->requiredOnCreate(['project', 'pkey_id'])
            ->requiredOnPut(['pkey_id'])
            ->filterable(['localization_id', 'project_name', 'i18nf_id', 'pkey_id', 'language_id'])
            ->sortable(['localization_id', 'pkey_id', 'language_id'], 'localization_id')
            ->addAttribute('pkey_id', 'pkeyId', 'string')
            ->addAttribute('language_id', 'languageId', 'string', true, true, true, null, [
                'lookup' => [
                    'table' => 'e_language',
                    'column' => 'language_id',
                ],
            ])
            ->addAttribute('value', 'value', 'string', true)
            ->addAttribute('i18nf_id', 'i18nfId', 'int', true, true, true, null, [
                'lookup' => [
                    'table' => 'i18n_field',
                    'column' => 'i18nf_id',
                ],
            ])
            ->addRelationship('project', 'project', ProjectDto::class, 'project', false, true, true, 'project_name');
    }
}
