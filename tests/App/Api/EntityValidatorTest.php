<?php

require_once __DIR__ . '/Support/AuthorEntityRepositoryStub.php';

use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\Validation\EntityValidator;
use GisClient\Author\Persistence\Entity;
use PHPUnit\Framework\TestCase;

class EntityValidatorTest extends TestCase
{
    public function testCollectsMultipleValidationErrors(): void
    {
        $validator = new EntityValidator();

        try {
            $validator->validate(new Entity('project', Entity::OPERATION_CREATE, 'milano', [
                'project_name' => 'milano',
                'non_existing_attribute' => 'hidden',
                'project_title' => '',
                'project_srid' => 3857,
                'max_extent_scale' => 50000,
                'charset_encodings_id' => 1,
                'default_language_id' => 'it',
            ]));
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
        $validator = new EntityValidator(null, static function (array $lookupRule, $value): bool {
            if ($lookupRule['table'] === 'e_language') {
                return in_array((string) $value, ['it', 'en'], true);
            }

            return true;
        });

        try {
            $validator->validate(new Entity('project', Entity::OPERATION_CREATE, 'milano', [
                'project_name' => 'milano',
                'project_title' => 'Milano',
                'project_srid' => 3857,
                'max_extent_scale' => 50000,
                'charset_encodings_id' => 1,
                'default_language_id' => 'zz',
            ]));
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $codes = array_column($errors, 'code');
            $this->assertContains('invalid_reference', $codes);
        }
    }

    public function testAcceptsValidForeignKeyReference(): void
    {
        $validator = new EntityValidator(null, static function (array $lookupRule, $value): bool {
            if ($lookupRule['table'] === 'e_language') {
                return in_array((string) $value, ['it', 'en'], true);
            }

            return true;
        });

        $entity = new Entity('project', Entity::OPERATION_CREATE, 'milano', [
            'project_name' => 'milano',
            'project_title' => 'Milano',
            'project_srid' => 3857,
            'max_extent_scale' => 50000,
            'charset_encodings_id' => 1,
            'default_language_id' => 'it',
        ]);

        $validator->validate($entity);

        $this->assertSame('it', $entity->getAttributes()['default_language_id']);
    }

    public function testRejectsPrimaryKeyMutationOnUpdate(): void
    {
        $validator = new EntityValidator();

        try {
            $validator->validate(new Entity('project', Entity::OPERATION_UPDATE, 'milano', [
                'project_name' => 'other',
                'project_title' => 'Milano',
                'project_srid' => 3857,
                'max_extent_scale' => 50000,
                'charset_encodings_id' => 1,
                'default_language_id' => 'it',
            ]));
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $codes = array_column($exception->getErrors(), 'code');
            $this->assertContains('immutable_primary_key', $codes);
        }
    }

    public function testRejectsUnknownRelationshipReference(): void
    {
        $validator = new EntityValidator(
            null,
            null,
            new AuthorEntityRepositoryStub([], static fn ($ref) => null)
        );

        try {
            $validator->validate(new Entity('theme', Entity::OPERATION_CREATE, 4, [
                'theme_id' => 4,
                'theme_name' => 'boundaries_places',
                'theme_title' => 'Boundaries and places',
                'theme_order' => 10,
                'project_name' => 'missing_project',
            ], [
                'project' => [
                    'type' => 'project',
                    'id' => 'missing_project',
                ],
            ]));
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertSame('invalid_relationship', $exception->getErrors()[0]['code']);
            $this->assertSame('/data/relationships/project/data/id', $exception->getErrors()[0]['source']['pointer']);
        }
    }
}
