<?php

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\ClassDto;
use GisClient\Author\Api\Dto\StyleDto;

class StyleMetadata extends Metadata
{
    public function __construct()
    {
        parent::__construct('style', StyleDto::class, 'style_id', 'int');

        $this
            ->requiredOnCreate(['class', 'style_name'])
            ->requiredOnPut(['class', 'style_name'])
            ->filterable(['style_id', 'class_id', 'style_name', 'symbol_name', 'pattern_id', 'color', 'outlinecolor'])
            ->sortable(['style_id', 'style_order', 'style_name'], 'style_order')
            ->addAttribute('style_name', 'styleName', 'string')
            ->addAttribute('style_order', 'styleOrder', 'int', true)
            ->addAttribute('symbol_name', 'symbolName', 'string', true)
            ->addAttribute('pattern_id', 'patternId', 'int', true, true, true, null, [
                'lookup' => [
                    'table' => 'e_pattern',
                    'column' => 'pattern_id',
                ],
            ])
            ->addAttribute('color', 'color', 'string', true)
            ->addAttribute('outlinecolor', 'outlinecolor', 'string', true)
            ->addAttribute('bgcolor', 'bgcolor', 'string', true)
            ->addAttribute('maxsize', 'maxsize', 'string', true)
            ->addAttribute('minsize', 'minsize', 'string', true)
            ->addAttribute('size', 'size', 'string', true)
            ->addAttribute('maxwidth', 'maxwidth', 'string', true)
            ->addAttribute('minwidth', 'minwidth', 'string', true)
            ->addAttribute('width', 'width', 'string', true)
            ->addAttribute('angle', 'angle', 'string', true)
            ->addAttribute('style_def', 'styleDef', 'string', true)
            ->addRelationship('class', 'class', ClassDto::class, 'class', false, true, true, 'class_id');
    }
}
