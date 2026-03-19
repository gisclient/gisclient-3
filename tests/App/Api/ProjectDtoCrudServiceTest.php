<?php

require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\Validation\EntityValidator;
use PHPUnit\Framework\TestCase;

class ProjectDtoCrudServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    public function testCreateMapsDtoIdToPrimaryKey(): void
    {
        $repository = new EntityRepositoryStub();
        $service = TestApiCrudService::create($repository);

        $service->createResource('project', $this->makeDto(ProjectDto::class, 'milano', [
            'project_title' => 'Milano',
            'project_srid' => 3857,
            'max_extent_scale' => 50000,
            'charset_encodings_id' => 1,
            'default_language_id' => 'it',
        ]));

        $this->assertSame('milano', $repository->createdAttributes['project_name']);
        $this->assertSame('Milano', $repository->createdAttributes['project_title']);
    }

    public function testDtoTypedPropertyRejectsInvalidAssignmentBeforeServiceCall(): void
    {
        $dto = new ProjectDto();

        $this->expectException(TypeError::class);
        $dto->maxExtentScale = 'A50000';
    }

    public function testCreateRejectsDuplicatePrimaryKeyWithExplicitConflict(): void
    {
        $repository = new EntityRepositoryStub(['milano']);
        $service = TestApiCrudService::create($repository);
        $dto = $this->makeDto(ProjectDto::class, 'milano', [
            'project_title' => 'Milano',
            'project_srid' => 3857,
            'max_extent_scale' => 50000,
            'charset_encodings_id' => 1,
            'default_language_id' => 'it',
        ]);

        $this->assertApiException(static function () use ($service, $dto): void {
            $service->createResource('project', $dto);
        }, 409, 'duplicate_primary_key', '/data/id');
    }

    public function testCreateRejectsUnknownCharsetEncodingReference(): void
    {
        $repository = new EntityRepositoryStub();
        $service = TestApiCrudService::create(
            $repository,
            new EntityValidator(null, static function (array $lookupRule, $value): bool {
                if ($lookupRule['table'] !== 'e_charset_encodings') {
                    return true;
                }

                return in_array((int) $value, [1, 2], true);
            }, $repository)
        );

        $dto = $this->makeDto(ProjectDto::class, 'milano', [
            'project_title' => 'Milano',
            'project_srid' => 3857,
            'max_extent_scale' => 50000,
            'charset_encodings_id' => 3,
            'default_language_id' => 'it',
        ]);

        $this->expectException(ValidationException::class);

        try {
            $service->createResource('project', $dto);
        } catch (ValidationException $exception) {
            $this->assertSame('invalid_reference', $exception->getErrors()[0]['code']);
            $this->assertSame('charset_encodings_id', $exception->getErrors()[0]['source']['attribute']);

            throw $exception;
        }
    }
}
