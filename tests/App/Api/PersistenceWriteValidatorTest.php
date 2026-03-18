<?php

use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\Model\EntityDefinition;
use GisClient\Author\Api\Validation\PersistenceWriteValidator;
use PHPUnit\Framework\TestCase;

class PersistenceWriteValidatorTest extends TestCase
{
    public function testCollectsMultipleValidationErrors(): void
    {
        $validator = new PersistenceWriteValidator();
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
            'project_name'
        );

        try {
            $validator->validateAndNormalize($definition, [
                'project_note' => 'hidden',
                'project_title' => '',
            ], 'milano', true, false);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $codes = array_column($errors, 'code');

            $this->assertContains('invalid_attribute', $codes);
            $this->assertContains('missing_required_attribute', $codes);
        }
    }

    public function testRejectsInvalidForeignKeyReference(): void
    {
        $validator = new PersistenceWriteValidator(null, static function (array $lookupRule, $value): bool {
            if ($lookupRule['table'] === 'e_language') {
                return in_array((string) $value, ['it', 'en'], true);
            }

            return true;
        });

        $definition = new EntityDefinition(
            'project',
            'gisclient_34',
            'project',
            'project_name',
            'string',
            ['project_name', 'default_language_id'],
            ['default_language_id'],
            ['project_name', 'default_language_id'],
            ['default_language_id'],
            ['project_name'],
            ['project_name'],
            'project_name',
            [
                'default_language_id' => [
                    'lookup' => [
                        'schema' => 'gisclient_34',
                        'table' => 'e_language',
                        'column' => 'language_id',
                    ],
                ],
            ]
        );

        try {
            $validator->validateAndNormalize($definition, [
                'default_language_id' => 'zz',
            ], 'milano', true, false);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $codes = array_column($errors, 'code');
            $this->assertContains('invalid_reference', $codes);
        }
    }

    public function testAcceptsValidForeignKeyReference(): void
    {
        $validator = new PersistenceWriteValidator(null, static function (array $lookupRule, $value): bool {
            if ($lookupRule['table'] === 'e_language') {
                return in_array((string) $value, ['it', 'en'], true);
            }

            return true;
        });

        $definition = new EntityDefinition(
            'project',
            'gisclient_34',
            'project',
            'project_name',
            'string',
            ['project_name', 'default_language_id'],
            ['default_language_id'],
            ['project_name', 'default_language_id'],
            ['default_language_id'],
            ['project_name'],
            ['project_name'],
            'project_name',
            [
                'default_language_id' => [
                    'lookup' => [
                        'schema' => 'gisclient_34',
                        'table' => 'e_language',
                        'column' => 'language_id',
                    ],
                ],
            ]
        );

        $attributes = $validator->validateAndNormalize($definition, [
            'default_language_id' => 'it',
        ], 'milano', true, false);

        $this->assertSame('it', $attributes['default_language_id']);
    }

    public function testNormalizesIntegerResourceIdFromString(): void
    {
        $validator = new PersistenceWriteValidator();
        $definition = new EntityDefinition(
            'project_srs',
            'gisclient_34',
            'project_srs',
            'srid',
            'int',
            ['srid', 'project_name', 'projparam'],
            ['srid', 'project_name', 'projparam'],
            ['srid', 'project_name'],
            [],
            ['srid'],
            ['srid'],
            'srid'
        );

        $attributes = $validator->validateAndNormalize($definition, [
            'project_name' => 'milano',
            'projparam' => null,
        ], '32632', true, false);

        $this->assertSame(32632, $attributes['srid']);
    }

    public function testRejectsNonNumericResourceIdForIntegerPrimaryKey(): void
    {
        $validator = new PersistenceWriteValidator();
        $definition = new EntityDefinition(
            'project_srs',
            'gisclient_34',
            'project_srs',
            'srid',
            'int',
            ['srid', 'project_name'],
            ['srid', 'project_name'],
            ['srid', 'project_name'],
            [],
            ['srid'],
            ['srid'],
            'srid'
        );

        try {
            $validator->validateAndNormalize($definition, [
                'project_name' => 'milano',
            ], '32A', true, false);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $codes = array_column($exception->getErrors(), 'code');
            $this->assertContains('invalid_id', $codes);
        }
    }
}
