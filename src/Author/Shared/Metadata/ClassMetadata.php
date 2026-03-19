<?php

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\ClassDto;
use GisClient\Author\Api\Dto\LayerDto;

class ClassMetadata extends Metadata
{
    public function __construct()
    {
        parent::__construct('class', ClassDto::class, 'class_id', 'int');

        $this
            ->requiredOnCreate(['layer', 'class_name'])
            ->requiredOnPut(['layer', 'class_name'])
            ->filterable(['class_id', 'layer_id', 'class_name', 'class_title', 'legendtype_id', 'maxscale', 'minscale'])
            ->sortable(['class_id', 'class_order', 'class_name', 'class_title'], 'class_order')
            ->addAttribute('class_name', 'className', 'string')
            ->addAttribute('class_title', 'classTitle', 'string', true)
            ->addAttribute('class_order', 'classOrder', 'int', true)
            ->addAttribute('expression', 'expression', 'string', true)
            ->addAttribute('keyimage', 'keyimage', 'string', true)
            ->addAttribute('legendtype_id', 'legendtypeId', 'int', true)
            ->addAttribute('maxscale', 'maxscale', 'string', true)
            ->addAttribute('minscale', 'minscale', 'string', true)
            ->addAttribute('class_template', 'classTemplate', 'string', true)
            ->addAttribute('label_font', 'labelFont', 'string', true)
            ->addAttribute('label_maxsize', 'labelMaxsize', 'int', true)
            ->addAttribute('label_minsize', 'labelMinsize', 'int', true)
            ->addAttribute('label_size', 'labelSize', 'string', true)
            ->addAttribute('class_text', 'classText', 'string', true)
            ->addAttribute('label_color', 'labelColor', 'string', true)
            ->addAttribute('label_outlinecolor', 'labelOutlinecolor', 'string', true)
            ->addAttribute('label_bgcolor', 'labelBgcolor', 'string', true)
            ->addAttribute('label_position', 'labelPosition', 'string', true)
            ->addAttribute('label_angle', 'labelAngle', 'string', true)
            ->addAttribute('label_def', 'labelDef', 'string', true)
            ->addAttribute('label_force', 'labelForce', 'int', true)
            ->addAttribute('label_priority', 'labelPriority', 'int', true)
            ->addAttribute('label_buffer', 'labelBuffer', 'int', true)
            ->addAttribute('label_antialias', 'labelAntialias', 'int', true)
            ->addAttribute('label_wrap', 'labelWrap', 'string', true)
            ->addRelationship('layer', 'layer', LayerDto::class, 'layer', true, true, true, true, 'layer_id');
    }
}
