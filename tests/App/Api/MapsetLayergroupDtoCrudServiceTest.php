<?php

require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Dto\LayergroupDto;
use GisClient\Author\Api\Dto\MapsetDto;
use GisClient\Author\Api\Dto\MapsetLayergroupDto;
use PHPUnit\Framework\TestCase;

class MapsetLayergroupDtoCrudServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    public function testScopedCreateInjectsScopeAndRendersRelationships(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $dto = $this->makeDto(MapsetLayergroupDto::class, 42, [
            'status' => 1,
            'refmap' => 0,
            'hide' => 1,
        ], [
            'mapset' => $this->identifierDto(MapsetDto::class, 'base'),
            'layergroup' => $this->identifierDto(LayergroupDto::class, 42),
        ]);

        $payload = $service->createResource('mapset_layergroup', $dto, [
            'mapset_name' => 'base',
        ]);

        $this->assertSame('base', $repository->createdAttributes['mapset_name']);
        $this->assertSame('42', (string) $repository->createdAttributes['layergroup_id']);
        $this->assertSame('base', $payload['data']['relationships']['mapset']['data']['id']);
        $this->assertSame('42', $payload['data']['relationships']['layergroup']['data']['id']);
    }

    public function testScopedCreateRequiresMapsetRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(MapsetLayergroupDto::class, 42, [
            'status' => 1,
        ], [
            'layergroup' => $this->identifierDto(LayergroupDto::class, 42),
        ]);

        $this->assertApiException(static function () use ($service, $dto): void {
            $service->createResource('mapset_layergroup', $dto, [
                'mapset_name' => 'base',
            ]);
        }, 422, 'missing_required_relationship', '/data/relationships/mapset/data');
    }

    public function testScopedCreateRequiresLayergroupRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(MapsetLayergroupDto::class, 42, [
            'status' => 1,
        ], [
            'mapset' => $this->identifierDto(MapsetDto::class, 'base'),
        ]);

        $this->assertApiException(static function () use ($service, $dto): void {
            $service->createResource('mapset_layergroup', $dto, [
                'mapset_name' => 'base',
            ]);
        }, 422, 'missing_required_relationship', '/data/relationships/layergroup/data');
    }

    public function testScopedCreateRejectsMismatchedMapsetRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(MapsetLayergroupDto::class, 42, [
            'status' => 1,
        ], [
            'mapset' => $this->identifierDto(MapsetDto::class, 'other'),
            'layergroup' => $this->identifierDto(LayergroupDto::class, 42),
        ]);

        $this->assertApiException(static function () use ($service, $dto): void {
            $service->createResource('mapset_layergroup', $dto, [
                'mapset_name' => 'base',
            ]);
        }, 422, 'relationship_scope_mismatch', '/data/relationships/mapset/data/id');
    }

    public function testScopedGetUsesScopeFilter(): void
    {
        $repository = new AuthorEntityRepositoryStub(['base|42']);
        $service = TestApiCrudService::create($repository);

        $payload = $service->getResource('mapset_layergroup', 42, [], [
            'mapset_name' => 'base',
        ]);
        $this->assertSame('42', (string) $payload['data']['id']);

        $this->assertApiException(static function () use ($service): void {
            $service->getResource('mapset_layergroup', 42, [], [
                'mapset_name' => 'other',
            ]);
        }, 404, 'resource_not_found');
    }

    public function testScopedPutAllowsRelationshipOnlyMapsetWithoutAttributeMismatch(): void
    {
        $repository = new AuthorEntityRepositoryStub(['base|42']);
        $service = TestApiCrudService::create($repository);
        $dto = $this->makeDto(MapsetLayergroupDto::class, 42, [
            'status' => 1,
            'refmap' => 1,
            'hide' => 0,
        ], [
            'mapset' => $this->identifierDto(MapsetDto::class, 'base'),
            'layergroup' => $this->identifierDto(LayergroupDto::class, 42),
        ]);

        $payload = $service->updateResource('mapset_layergroup', 42, $dto, [
            'mapset_name' => 'base',
        ]);

        $this->assertSame('base', $repository->updatedAttributes['mapset_name']);
        $this->assertSame(1, $repository->updatedAttributes['status']);
        $this->assertSame('base', $payload['data']['relationships']['mapset']['data']['id']);
        $this->assertSame('42', $payload['data']['relationships']['layergroup']['data']['id']);
    }
}
