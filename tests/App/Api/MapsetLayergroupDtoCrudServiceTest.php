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

    public function testCreateUsesRootIdAndRendersRelationships(): void
    {
        $repository = new EntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $dto = $this->makeDto(MapsetLayergroupDto::class, 7, [
            'status' => 1,
            'refmap' => 0,
            'hide' => 1,
        ], [
            'mapset' => $this->identifierDto(MapsetDto::class, 'base'),
            'layergroup' => $this->identifierDto(LayergroupDto::class, 42),
        ]);

        $payload = $service->createResource('mapset_layergroup', $dto);

        $this->assertSame('base', $repository->createdAttributes['mapset_name']);
        $this->assertSame('42', (string) $repository->createdAttributes['layergroup_id']);
        $this->assertSame('7', (string) $payload['data']['id']);
        $this->assertSame('base', $payload['data']['relationships']['mapset']['data']['id']);
        $this->assertSame('42', $payload['data']['relationships']['layergroup']['data']['id']);
    }

    public function testCreateRequiresMapsetRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(MapsetLayergroupDto::class, 7, [
            'status' => 1,
        ], [
            'layergroup' => $this->identifierDto(LayergroupDto::class, 42),
        ]);

        $this->assertValidationException(static function () use ($service, $dto): void {
            $service->createResource('mapset_layergroup', $dto);
        }, 422, 'missing_required_relationship', '/data/relationships/mapset/data');
    }

    public function testCreateRequiresLayergroupRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(MapsetLayergroupDto::class, 7, [
            'status' => 1,
        ], [
            'mapset' => $this->identifierDto(MapsetDto::class, 'base'),
        ]);

        $this->assertValidationException(static function () use ($service, $dto): void {
            $service->createResource('mapset_layergroup', $dto);
        }, 422, 'missing_required_relationship', '/data/relationships/layergroup/data');
    }

    public function testGetUsesRootIdWithoutScope(): void
    {
        $repository = new EntityRepositoryStub([], static fn ($ref) => [
            'id' => (int) $ref->getId(),
            'mapset_name' => 'base',
            'layergroup_id' => 42,
            'status' => 1,
            'refmap' => 0,
            'hide' => 1,
        ]);
        $service = TestApiCrudService::create($repository);

        $payload = $service->getResource('mapset_layergroup', 7);
        $this->assertSame('7', (string) $payload['data']['id']);
        $this->assertSame('base', $payload['data']['relationships']['mapset']['data']['id']);
        $this->assertSame('42', $payload['data']['relationships']['layergroup']['data']['id']);
    }

    public function testPutUsesRootIdWithoutScope(): void
    {
        $repository = new EntityRepositoryStub(
            [],
            static fn ($ref) => [
                'id' => (int) $ref->getId(),
                'mapset_name' => 'base',
                'layergroup_id' => 42,
                'status' => 0,
                'refmap' => 0,
                'hide' => 1,
            ],
            null,
            null,
            static fn ($entity) => array_merge([
                'id' => (int) $entity->getId(),
                'mapset_name' => 'base',
                'layergroup_id' => 42,
            ], $entity->getAttributes())
        );
        $service = TestApiCrudService::create($repository);
        $dto = $this->makeDto(MapsetLayergroupDto::class, 7, [
            'status' => 1,
            'refmap' => 1,
            'hide' => 0,
        ], [
            'mapset' => $this->identifierDto(MapsetDto::class, 'base'),
            'layergroup' => $this->identifierDto(LayergroupDto::class, 42),
        ]);

        $payload = $service->updateResource('mapset_layergroup', 7, $dto);

        $this->assertSame(1, $repository->updatedAttributes['status']);
        $this->assertSame('7', (string) $payload['data']['id']);
        $this->assertSame('base', $payload['data']['relationships']['mapset']['data']['id']);
        $this->assertSame('42', $payload['data']['relationships']['layergroup']['data']['id']);
    }
}
