<?php

namespace GisClient\Author\Api\Dto;

use GisClient\Author\Api\Dto\Schema\FieldDefinition;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;

class ClassDto extends JsonApiDto
{
    public ?int $id = null;

    public string $className;

    public ?string $classTitle = null;

    public ?int $classOrder = null;

    public ?string $expression = null;

    public ?string $keyimage = null;

    public ?int $legendtypeId = null;

    public ?string $maxscale = null;

    public ?string $minscale = null;

    public ?string $classTemplate = null;

    public ?string $labelFont = null;

    public ?int $labelMaxsize = null;

    public ?int $labelMinsize = null;

    public ?string $labelSize = null;

    public ?string $classText = null;

    public ?string $labelColor = null;

    public ?string $labelOutlinecolor = null;

    public ?string $labelBgcolor = null;

    public ?string $labelPosition = null;

    public ?string $labelAngle = null;

    public ?string $labelDef = null;

    public ?int $labelForce = null;

    public ?int $labelPriority = null;

    public ?int $labelBuffer = null;

    public ?int $labelAntialias = null;

    public ?string $labelWrap = null;

    public ?LayerDto $layer = null;

    public static function schema(): ResourceSchema
    {
        return ResourceSchema::resource('class', self::class, 'class_id', 'int')
            ->requiredOnCreate(['layer_id', 'class_name'])
            ->requiredOnPut(['layer_id', 'class_name'])
            ->addAttribute(FieldDefinition::attribute('class_name', 'className', 'string'))
            ->addAttribute(FieldDefinition::attribute('class_title', 'classTitle', 'string', true))
            ->addAttribute(FieldDefinition::attribute('class_order', 'classOrder', 'int', true))
            ->addAttribute(FieldDefinition::attribute('expression', 'expression', 'string', true))
            ->addAttribute(FieldDefinition::attribute('keyimage', 'keyimage', 'string', true))
            ->addAttribute(FieldDefinition::attribute('legendtype_id', 'legendtypeId', 'int', true))
            ->addAttribute(FieldDefinition::attribute('maxscale', 'maxscale', 'string', true))
            ->addAttribute(FieldDefinition::attribute('minscale', 'minscale', 'string', true))
            ->addAttribute(FieldDefinition::attribute('class_template', 'classTemplate', 'string', true))
            ->addAttribute(FieldDefinition::attribute('label_font', 'labelFont', 'string', true))
            ->addAttribute(FieldDefinition::attribute('label_maxsize', 'labelMaxsize', 'int', true))
            ->addAttribute(FieldDefinition::attribute('label_minsize', 'labelMinsize', 'int', true))
            ->addAttribute(FieldDefinition::attribute('label_size', 'labelSize', 'string', true))
            ->addAttribute(FieldDefinition::attribute('class_text', 'classText', 'string', true))
            ->addAttribute(FieldDefinition::attribute('label_color', 'labelColor', 'string', true))
            ->addAttribute(FieldDefinition::attribute('label_outlinecolor', 'labelOutlinecolor', 'string', true))
            ->addAttribute(FieldDefinition::attribute('label_bgcolor', 'labelBgcolor', 'string', true))
            ->addAttribute(FieldDefinition::attribute('label_position', 'labelPosition', 'string', true))
            ->addAttribute(FieldDefinition::attribute('label_angle', 'labelAngle', 'string', true))
            ->addAttribute(FieldDefinition::attribute('label_def', 'labelDef', 'string', true))
            ->addAttribute(FieldDefinition::attribute('label_force', 'labelForce', 'int', true))
            ->addAttribute(FieldDefinition::attribute('label_priority', 'labelPriority', 'int', true))
            ->addAttribute(FieldDefinition::attribute('label_buffer', 'labelBuffer', 'int', true))
            ->addAttribute(FieldDefinition::attribute('label_antialias', 'labelAntialias', 'int', true))
            ->addAttribute(FieldDefinition::attribute('label_wrap', 'labelWrap', 'string', true))
            ->addRelationship(FieldDefinition::relationship('layer', 'layer', LayerDto::class, 'layer', 'layer_id', true));
    }
}
