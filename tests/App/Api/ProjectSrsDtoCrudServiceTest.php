<?php

require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Dto\ProjectSrsDto;
use PHPUnit\Framework\TestCase;

class ProjectSrsDtoCrudServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    public function testScopedCreateInjectsScopeAndRendersParentRelationship(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $dto = $this->makeDto(ProjectSrsDto::class, 3857, [
            'projparam' => '+proj=merc',
        ], [
            'project' => $this->identifierDto(ProjectDto::class, 'default'),
        ]);

        $payload = $service->createResource('project_srs', $dto, [
            'project_name' => 'default',
        ]);

        $this->assertSame('default', $repository->createdAttributes['project_name']);
        $this->assertSame('3857', (string) $payload['data']['id']);
        $this->assertSame('default', $payload['data']['relationships']['project']['data']['id']);
        $this->assertArrayNotHasKey('project_name', $payload['data']['attributes']);
    }

    public function testScopedCreateRejectsMismatchedScopeAttribute(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(ProjectSrsDto::class, 3857, [
            'project_name' => 'other_project',
            'projparam' => '+proj=merc',
        ], [
            'project' => $this->identifierDto(ProjectDto::class, 'default'),
        ]);

        $this->assertApiException(static function () use ($service, $dto): void {
            $service->createResource('project_srs', $dto, [
                'project_name' => 'default',
            ]);
        }, 422, 'scope_attribute_mismatch', '/data/attributes/project_name');
    }

    public function testScopedGetUsesScopeFilter(): void
    {
        $repository = new AuthorEntityRepositoryStub(['default|3857']);
        $service = TestApiCrudService::create($repository);

        $payload = $service->getResource('project_srs', 3857, [], [
            'project_name' => 'default',
        ]);
        $this->assertSame('3857', (string) $payload['data']['id']);

        $this->assertApiException(static function () use ($service): void {
            $service->getResource('project_srs', 3857, [], [
                'project_name' => 'other',
            ]);
        }, 404, 'resource_not_found');
    }

    public function testScopedCreateRequiresProjectRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(ProjectSrsDto::class, 3857, [
            'projparam' => '+proj=merc',
        ]);

        $this->assertApiException(static function () use ($service, $dto): void {
            $service->createResource('project_srs', $dto, [
                'project_name' => 'default',
            ]);
        }, 422, 'missing_required_relationship', '/data/relationships/project/data');
    }
}
