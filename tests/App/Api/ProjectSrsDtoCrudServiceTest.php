<?php

require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Dto\ProjectSrsDto;
use PHPUnit\Framework\TestCase;

class ProjectSrsDtoCrudServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    public function testCreateUsesIdAndRendersParentRelationship(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $dto = $this->makeDto(ProjectSrsDto::class, 1, [
            'srid' => 3857,
            'projparam' => '+proj=merc',
        ], [
            'project' => $this->identifierDto(ProjectDto::class, 'default'),
        ]);

        $payload = $service->createResource('project_srs', $dto);

        $this->assertSame('default', $repository->createdAttributes['project_name']);
        $this->assertSame(3857, $repository->createdAttributes['srid']);
        $this->assertSame('1', (string) $payload['data']['id']);
        $this->assertSame('default', $payload['data']['relationships']['project']['data']['id']);
        $this->assertArrayNotHasKey('project_name', $payload['data']['attributes']);
    }

    public function testGetUsesRootIdWithoutScope(): void
    {
        $repository = new AuthorEntityRepositoryStub([], static fn ($definition, $id, array $scopeFilters) => [
            'id' => (int) $id,
            'project_name' => 'default',
            'srid' => 3857,
            'projparam' => '+proj=merc',
        ]);
        $service = TestApiCrudService::create($repository);

        $payload = $service->getResource('project_srs', 1);
        $this->assertSame('1', (string) $payload['data']['id']);
        $this->assertSame(3857, $payload['data']['attributes']['srid']);
        $this->assertSame('default', $payload['data']['relationships']['project']['data']['id']);
    }

    public function testCreateRequiresProjectRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(ProjectSrsDto::class, 1, [
            'srid' => 3857,
            'projparam' => '+proj=merc',
        ]);

        $this->assertApiException(static function () use ($service, $dto): void {
            $service->createResource('project_srs', $dto);
        }, 422, 'missing_required_relationship', '/data/relationships/project/data');
    }
}
