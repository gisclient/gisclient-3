<?php

namespace GisClient\Author\Api\Dto;

use GisClient\Author\Api\Dto\Schema\FieldDefinition;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;

class StyleDto extends JsonApiDto
{
    public ?int $id = null;

    public string $styleName;

    public ?int $styleOrder = null;

    public ?string $symbolName = null;

    public ?int $patternId = null;

    public ?string $color = null;

    public ?string $outlinecolor = null;

    public ?string $bgcolor = null;

    public ?string $maxsize = null;

    public ?string $minsize = null;

    public ?string $size = null;

    public ?string $maxwidth = null;

    public ?string $minwidth = null;

    public ?string $width = null;

    public ?string $angle = null;

    public ?string $styleDef = null;

    public ClassDto $class;

    public static function schema(): ResourceSchema
    {
        return ResourceSchema::resource('style', self::class, 'style_id', 'int')
            ->requiredOnCreate(['class', 'style_name'])
            ->requiredOnPut(['class', 'style_name'])
            ->filterable(['style_id', 'class_id', 'style_name', 'symbol_name', 'pattern_id', 'color', 'outlinecolor'])
            ->sortable(['style_id', 'style_order', 'style_name'], 'style_order')
            ->addAttribute(FieldDefinition::attribute('style_name', 'styleName', 'string'))
            ->addAttribute(FieldDefinition::attribute('style_order', 'styleOrder', 'int', true))
            ->addAttribute(FieldDefinition::attribute('symbol_name', 'symbolName', 'string', true))
            ->addAttribute(FieldDefinition::attribute('pattern_id', 'patternId', 'int', true))
            ->addAttribute(FieldDefinition::attribute('color', 'color', 'string', true))
            ->addAttribute(FieldDefinition::attribute('outlinecolor', 'outlinecolor', 'string', true))
            ->addAttribute(FieldDefinition::attribute('bgcolor', 'bgcolor', 'string', true))
            ->addAttribute(FieldDefinition::attribute('maxsize', 'maxsize', 'string', true))
            ->addAttribute(FieldDefinition::attribute('minsize', 'minsize', 'string', true))
            ->addAttribute(FieldDefinition::attribute('size', 'size', 'string', true))
            ->addAttribute(FieldDefinition::attribute('maxwidth', 'maxwidth', 'string', true))
            ->addAttribute(FieldDefinition::attribute('minwidth', 'minwidth', 'string', true))
            ->addAttribute(FieldDefinition::attribute('width', 'width', 'string', true))
            ->addAttribute(FieldDefinition::attribute('angle', 'angle', 'string', true))
            ->addAttribute(FieldDefinition::attribute('style_def', 'styleDef', 'string', true))
            ->addRelationship(FieldDefinition::relationship('class', 'class', ClassDto::class, 'class'));
    }
}
