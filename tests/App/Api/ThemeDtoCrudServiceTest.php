<?php

require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Api\Dto\ThemeDto;
use PHPUnit\Framework\TestCase;

class ThemeDtoCrudServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    public function testCreateMapsProjectRelationshipToLocalKey(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $dto = $this->makeDto(ThemeDto::class, 4, [
            'theme_name' => 'boundaries_places',
            'theme_title' => 'Boundaries and places',
            'theme_order' => 10,
        ], [
            'project' => $this->identifierDto(ProjectDto::class, 'milano'),
        ]);

        $payload = $service->createResource('theme', $dto);

        $this->assertSame('milano', $repository->createdAttributes['project_name']);
        $this->assertSame('milano', $payload['data']['relationships']['project']['data']['id']);
        $this->assertArrayNotHasKey('project_name', $payload['data']['attributes']);
    }

    public function testBuildQueryOptionsAcceptsRelationshipFilterAlias(): void
    {
        $service = TestApiCrudService::create();
        $queryOptions = $service->buildQueryOptions(DtoSchemaRegistry::schemaForType('theme'), [
            'filter' => [
                'project' => 'milano',
            ],
        ]);

        $this->assertSame([
            'project_name' => 'milano',
        ], $queryOptions->getFilters());
    }

    public function testGetResourceCastsNumericAttributesFromDatabaseStrings(): void
    {
        $repository = new AuthorEntityRepositoryStub([], static fn ($definition, $id) => [
            'theme_id' => '3',
            'project_name' => 'milano',
            'theme_name' => 'boundaries_places3',
            'theme_single' => '0',
            'radio' => '1',
        ]);
        $service = TestApiCrudService::create($repository);
        $payload = $service->getResource('theme', '3');

        $this->assertSame('3', $payload['data']['id']);
        $this->assertSame(0, $payload['data']['attributes']['theme_single']);
        $this->assertSame(1, $payload['data']['attributes']['radio']);
        $this->assertSame('milano', $payload['data']['relationships']['project']['data']['id']);
    }
}
