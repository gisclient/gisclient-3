<?php

require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Dto\CatalogDto;
use GisClient\Author\Api\Dto\ProjectDto;
use PHPUnit\Framework\TestCase;

class CatalogDtoCrudServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    public function testCreateRequiresProjectRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(CatalogDto::class, 2, [
            'catalog_name' => 'Main catalog',
            'connection_type' => 6,
            'catalog_path' => 'dbname=test',
        ]);

        $this->assertValidationException(static function () use ($service, $dto): void {
            $service->createResource('catalog', $dto);
        }, 422, 'missing_required_relationship', '/data/relationships/project/data');
    }

    public function testCreateMapsProjectRelationshipToLocalKey(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $dto = $this->makeDto(CatalogDto::class, 2, [
            'catalog_name' => 'Main catalog',
            'connection_type' => 6,
            'catalog_path' => 'dbname=test',
        ], [
            'project' => $this->identifierDto(ProjectDto::class, 'milano'),
        ]);

        $payload = $service->createResource('catalog', $dto);

        $this->assertSame('milano', $repository->createdAttributes['project_name']);
        $this->assertSame('milano', $payload['data']['relationships']['project']['data']['id']);
        $this->assertArrayNotHasKey('project_name', $payload['data']['attributes']);
    }
}
