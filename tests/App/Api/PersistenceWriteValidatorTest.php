<?php

require_once __DIR__ . '/Support/AuthorEntityRepositoryStub.php';

use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Api\Dto\Schema\FieldDefinition;
use GisClient\Author\Api\Dto\Schema\ResourceSchema;
use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\Validation\PersistenceWriteValidator;
use PHPUnit\Framework\TestCase;

class PersistenceWriteValidatorTest extends TestCase
{
    public function testCollectsMultipleValidationErrors(): void
    {
        $validator = new PersistenceWriteValidator();
        $schema = ResourceSchema::resource('project', 'ProjectDto', 'project_name', 'string')
            ->requiredOnCreate(['project_name', 'project_title', 'max_extent_scale'])
            ->requiredOnPut(['project_title', 'max_extent_scale'])
            ->filterable(['project_name'])
            ->sortable(['project_name'], 'project_name')
            ->storedAs('project', 'gisclient_34')
            ->addAttribute(FieldDefinition::attribute('project_title', 'projectTitle', 'string'))
            ->addAttribute(FieldDefinition::attribute('max_extent_scale', 'maxExtentScale', 'int'));

        try {
            $validator->validateAndNormalize($schema, [
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

        $schema = ResourceSchema::resource('project', 'ProjectDto', 'project_name', 'string')
            ->requiredOnCreate(['project_name', 'default_language_id'])
            ->requiredOnPut(['default_language_id'])
            ->filterable(['project_name'])
            ->sortable(['project_name'], 'project_name')
            ->storedAs('project', 'gisclient_34')
            ->addAttribute(FieldDefinition::attribute('default_language_id', 'defaultLanguageId', 'string')->withLookup([
                'schema' => 'gisclient_34',
                'table' => 'e_language',
                'column' => 'language_id',
            ]));

        try {
            $validator->validateAndNormalize($schema, [
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

        $schema = ResourceSchema::resource('project', 'ProjectDto', 'project_name', 'string')
            ->requiredOnCreate(['project_name', 'default_language_id'])
            ->requiredOnPut(['default_language_id'])
            ->filterable(['project_name'])
            ->sortable(['project_name'], 'project_name')
            ->storedAs('project', 'gisclient_34')
            ->addAttribute(FieldDefinition::attribute('default_language_id', 'defaultLanguageId', 'string')->withLookup([
                'schema' => 'gisclient_34',
                'table' => 'e_language',
                'column' => 'language_id',
            ]));

        $attributes = $validator->validateAndNormalize($schema, [
            'default_language_id' => 'it',
        ], 'milano', true, false);

        $this->assertSame('it', $attributes['default_language_id']);
    }

    public function testNormalizesIntegerResourceIdFromString(): void
    {
        $validator = new PersistenceWriteValidator();
        $schema = ResourceSchema::resource('project_srs', 'ProjectSrsDto', 'srid', 'int')
            ->requiredOnCreate(['srid', 'project_name'])
            ->requiredOnPut([])
            ->filterable(['srid'])
            ->sortable(['srid'], 'srid')
            ->storedAs('project_srs', 'gisclient_34')
            ->addAttribute(FieldDefinition::attribute('project_name', 'projectName', 'string'))
            ->addAttribute(FieldDefinition::attribute('projparam', 'projparam', 'string', true));

        $attributes = $validator->validateAndNormalize($schema, [
            'project_name' => 'milano',
            'projparam' => null,
        ], '32632', true, false);

        $this->assertSame(32632, $attributes['srid']);
    }

    public function testRejectsNonNumericResourceIdForIntegerPrimaryKey(): void
    {
        $validator = new PersistenceWriteValidator();
        $schema = ResourceSchema::resource('project_srs', 'ProjectSrsDto', 'srid', 'int')
            ->requiredOnCreate(['srid', 'project_name'])
            ->requiredOnPut([])
            ->filterable(['srid'])
            ->sortable(['srid'], 'srid')
            ->storedAs('project_srs', 'gisclient_34')
            ->addAttribute(FieldDefinition::attribute('project_name', 'projectName', 'string'));

        try {
            $validator->validateAndNormalize($schema, [
                'project_name' => 'milano',
            ], '32A', true, false);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $codes = array_column($exception->getErrors(), 'code');
            $this->assertContains('invalid_id', $codes);
        }
    }

    public function testRejectsUnknownRelationshipReference(): void
    {
        $validator = new PersistenceWriteValidator(
            null,
            null,
            new AuthorEntityRepositoryStub([], static fn ($schema, $id) => null)
        );
        $schema = DtoSchemaRegistry::schemaForType('theme');

        try {
            $validator->validateReferences($schema, [], [
                'project' => [
                    'type' => 'project',
                    'id' => 'missing_project',
                ],
            ]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertSame('invalid_relationship', $exception->getErrors()[0]['code']);
            $this->assertSame('/data/relationships/project/data/id', $exception->getErrors()[0]['source']['pointer']);
        }
    }
}
