<?php

use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\Model\EntityDefinition;
use GisClient\Author\Api\Validation\PayloadValidator;
use PHPUnit\Framework\TestCase;

class PayloadValidatorTest extends TestCase
{
    public function testCollectsMultipleValidationErrors()
    {
        $validator = new PayloadValidator();
        $definition = new EntityDefinition(
            'project',
            'gisclient_34',
            'project',
            'project_name',
            'string',
            ['project_name', 'project_title', 'max_extent_scale'],
            ['project_title', 'max_extent_scale'],
            ['project_name', 'project_title', 'max_extent_scale'],
            ['project_title', 'max_extent_scale'],
            ['project_name'],
            ['project_name'],
            'project_name',
            [
                'max_extent_scale' => [
                    'type' => 'numeric',
                ],
            ]
        );

        try {
            $validator->validateAndNormalize($definition, [
                'data' => [
                    'type' => 'project',
                    'id' => 'milano',
                    'attributes' => [
                        'project_note' => 'hidden',
                        'project_title' => '',
                        'max_extent_scale' => 'A50000',
                    ],
                ],
            ], true, false);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $codes = array_column($errors, 'code');

            $this->assertContains('invalid_attribute', $codes);
            $this->assertContains('missing_required_attribute', $codes);
            $this->assertContains('invalid_attribute_type', $codes);
        }
    }
}
