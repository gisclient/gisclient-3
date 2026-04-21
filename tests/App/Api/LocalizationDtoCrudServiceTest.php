<?php

require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Dto\LocalizationDto;
use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use PHPUnit\Framework\TestCase;

class LocalizationDtoCrudServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    public function testCreateRequiresProjectRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(LocalizationDto::class, 1, [
            'pkey_id' => 'layer.1',
        ]);

        $this->assertValidationException(static function () use ($service, $dto): void {
            $service->createResource('localization', $dto);
        }, 422, 'missing_required_relationship', '/data/relationships/project/data');
    }

    public function testCreateRequiresPkeyId(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(LocalizationDto::class, 1, [], [
            'project' => $this->identifierDto(ProjectDto::class, 'milano'),
        ]);

        $this->assertValidationException(static function () use ($service, $dto): void {
            $service->createResource('localization', $dto);
        }, 422, 'missing_required_attribute', '/data/attributes/pkey_id');
    }

    public function testCreateMapsProjectRelationshipToLocalKey(): void
    {
        $repository = new EntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $dto = $this->makeDto(LocalizationDto::class, 1, [
            'pkey_id' => 'layer.1',
        ], [
            'project' => $this->identifierDto(ProjectDto::class, 'milano'),
        ]);

        $payload = $service->createResource('localization', $dto);

        $this->assertSame('milano', $repository->createdAttributes['project_name']);
        $this->assertSame('milano', $payload['data']['relationships']['project']['data']['id']);
        $this->assertArrayNotHasKey('project_name', $payload['data']['attributes']);
    }

    public function testBuildQueryOptionsAcceptsRelationshipFilterAlias(): void
    {
        $service = TestApiCrudService::create();
        $queryOptions = $service->buildEntityQuery(DtoSchemaRegistry::schemaForType('localization'), [
            'filter' => [
                'project' => 'milano',
            ],
        ]);

        $this->assertSame([
            'project_name' => 'milano',
        ], $queryOptions->getFilters());
    }

    public function testGetResourceReturnsAttributes(): void
    {
        $repository = new EntityRepositoryStub([], static fn ($ref) => [
            'localization_id' => (int) $ref->getId(),
            'project_name' => 'milano',
            'pkey_id' => 'layer.1',
            'language_id' => 'it',
            'value' => 'Strati',
            'i18nf_id' => 3,
        ]);
        $service = TestApiCrudService::create($repository);
        $payload = $service->getResource('localization', 7);

        $this->assertSame('layer.1', $payload['data']['attributes']['pkey_id']);
        $this->assertSame('it', $payload['data']['attributes']['language_id']);
        $this->assertSame('Strati', $payload['data']['attributes']['value']);
        $this->assertSame(3, $payload['data']['attributes']['i18nf_id']);
        $this->assertSame('milano', $payload['data']['relationships']['project']['data']['id']);
        $this->assertArrayNotHasKey('project_name', $payload['data']['attributes']);
    }
}
