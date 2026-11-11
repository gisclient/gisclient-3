<?php

require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Dto\GroupDto;
use GisClient\Author\Api\Dto\LayerDto;
use GisClient\Author\Api\Dto\LayerGroupsDto;
use GisClient\Author\Persistence\EntitySchemaRegistry;
use PHPUnit\Framework\TestCase;

class LayerGroupsDtoCrudServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    public function testSingularTypeMapsToLayerGroupsTable(): void
    {
        $schema = EntitySchemaRegistry::schemaForType('layer_group');

        $this->assertSame('layer_groups', $schema->getResolvedTable());
        $this->assertSame('layer_groups_id', $schema->getPrimaryKey());
        $this->assertSame('layer_id', $schema->getRelationshipColumn('layer'));
        $this->assertSame('groupname', $schema->getRelationshipColumn('group'));
    }

    public function testCreateWritesColumnsAndRendersRelationships(): void
    {
        $repository = new EntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $dto = $this->makeDto(LayerGroupsDto::class, 7, [
            'wms' => 1,
            'wfs' => 1,
            'wfst' => 0,
        ], [
            'layer' => $this->identifierDto(LayerDto::class, 42),
            'group' => $this->identifierDto(GroupDto::class, 'editors'),
        ]);

        $payload = $service->createResource('layer_group', $dto);

        $this->assertSame('42', (string) $repository->createdAttributes['layer_id']);
        $this->assertSame('editors', $repository->createdAttributes['groupname']);
        $this->assertSame(1, $repository->createdAttributes['wms']);
        $this->assertSame(1, $repository->createdAttributes['wfs']);
        $this->assertSame(0, $repository->createdAttributes['wfst']);
        $this->assertSame('7', (string) $payload['data']['id']);
        $this->assertSame('42', (string) $payload['data']['relationships']['layer']['data']['id']);
        $this->assertSame('editors', $payload['data']['relationships']['group']['data']['id']);
    }

    public function testCreateRequiresLayerRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(LayerGroupsDto::class, 7, [
            'wms' => 1,
        ], [
            'group' => $this->identifierDto(GroupDto::class, 'editors'),
        ]);

        $this->assertValidationException(static function () use ($service, $dto): void {
            $service->createResource('layer_group', $dto);
        }, 422, 'missing_required_relationship', '/data/relationships/layer/data');
    }

    public function testCreateRequiresGroupRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(LayerGroupsDto::class, 7, [
            'wms' => 1,
        ], [
            'layer' => $this->identifierDto(LayerDto::class, 42),
        ]);

        $this->assertValidationException(static function () use ($service, $dto): void {
            $service->createResource('layer_group', $dto);
        }, 422, 'missing_required_relationship', '/data/relationships/group/data');
    }

    public function testGetRendersFlagsAndRelationships(): void
    {
        $repository = new EntityRepositoryStub([], static fn ($ref) => [
            'layer_groups_id' => (int) $ref->getId(),
            'layer_id' => 42,
            'groupname' => 'editors',
            'wms' => 1,
            'wfs' => 0,
            'wfst' => 1,
            'layer_name' => 'roads',
        ]);
        $service = TestApiCrudService::create($repository);

        $payload = $service->getResource('layer_group', 7);

        $this->assertSame('7', (string) $payload['data']['id']);
        $this->assertSame(1, $payload['data']['attributes']['wms']);
        $this->assertSame(0, $payload['data']['attributes']['wfs']);
        $this->assertSame(1, $payload['data']['attributes']['wfst']);
        $this->assertSame('roads', $payload['data']['attributes']['layer_name']);
        $this->assertSame('42', (string) $payload['data']['relationships']['layer']['data']['id']);
        $this->assertSame('editors', $payload['data']['relationships']['group']['data']['id']);
    }

    public function testPutUpdatesFlags(): void
    {
        $repository = new EntityRepositoryStub(
            [],
            static fn ($ref) => [
                'layer_groups_id' => (int) $ref->getId(),
                'layer_id' => 42,
                'groupname' => 'editors',
                'wms' => 0,
                'wfs' => 0,
                'wfst' => 0,
            ],
            null,
            null,
            static fn ($entity) => array_merge([
                'layer_groups_id' => (int) $entity->getId(),
                'layer_id' => 42,
                'groupname' => 'editors',
            ], $entity->getAttributes())
        );
        $service = TestApiCrudService::create($repository);
        $dto = $this->makeDto(LayerGroupsDto::class, 7, [
            'wms' => 1,
            'wfs' => 1,
            'wfst' => 1,
        ], [
            'layer' => $this->identifierDto(LayerDto::class, 42),
            'group' => $this->identifierDto(GroupDto::class, 'editors'),
        ]);

        $payload = $service->updateResource('layer_group', 7, $dto);

        $this->assertSame(1, $repository->updatedAttributes['wms']);
        $this->assertSame(1, $repository->updatedAttributes['wfs']);
        $this->assertSame(1, $repository->updatedAttributes['wfst']);
        $this->assertSame('7', (string) $payload['data']['id']);
        $this->assertSame(1, $payload['data']['attributes']['wfst']);
        $this->assertSame('42', (string) $payload['data']['relationships']['layer']['data']['id']);
        $this->assertSame('editors', $payload['data']['relationships']['group']['data']['id']);
    }
}
