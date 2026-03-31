<?php

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\SymbolDto;

class SymbolMetadata extends Metadata
{
    public function __construct()
    {
        parent::__construct('symbol', SymbolDto::class, 'symbol_name', 'string');

        $this
            ->requiredOnCreate(['symbol_name'])
            ->requiredOnPut(['symbol_name'])
            ->filterable(['symbol_name', 'symbolcategory_id', 'symbol_type', 'font_name'])
            ->sortable(['symbol_name', 'symbolcategory_id'], 'symbol_name')
            ->addAttribute('symbolcategory_id', 'symbolcategoryId', 'int', true)
            ->addAttribute('icontype', 'icontype', 'int', true)
            ->addAttribute('symbol_def', 'symbolDef', 'string', true)
            ->addAttribute('symbol_type', 'symbolType', 'string', true)
            ->addAttribute('font_name', 'fontName', 'string', true, true, true, null, [
                'lookup' => [
                    'table' => 'font',
                    'column' => 'font_name',
                ],
            ])
            ->addAttribute('ascii_code', 'asciiCode', 'int', true)
            ->addAttribute('filled', 'filled', 'int', true)
            ->addAttribute('points', 'points', 'string', true)
            ->addAttribute('image', 'image', 'string', true);
    }
}
