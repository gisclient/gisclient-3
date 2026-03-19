<?php

require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Dto\CatalogDto;
use GisClient\Author\Api\Dto\LayerDto;
use GisClient\Author\Api\Dto\LayergroupDto;
use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use PHPUnit\Framework\TestCase;

class LayerDtoCrudServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    public function testCreateRequiresLayergroupRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->baseLayerDto();
        $dto->catalog = $this->identifierDto(CatalogDto::class, 10);
        $dto->markPresent('catalog');

        $this->assertValidationException(static function () use ($service, $dto): void {
            $service->createResource('layer', $dto);
        }, 422, 'missing_required_relationship', '/data/relationships/layergroup/data');
    }

    public function testCreateRequiresCatalogRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->baseLayerDto();
        $dto->layergroup = $this->identifierDto(LayergroupDto::class, 5);
        $dto->markPresent('layergroup');

        $this->assertValidationException(static function () use ($service, $dto): void {
            $service->createResource('layer', $dto);
        }, 422, 'missing_required_relationship', '/data/relationships/catalog/data');
    }

    public function testCreateMapsParentRelationshipsToLocalKeys(): void
    {
        $repository = new EntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $dto = $this->baseLayerDto();
        $dto->layergroup = $this->identifierDto(LayergroupDto::class, 5);
        $dto->markPresent('layergroup');
        $dto->catalog = $this->identifierDto(CatalogDto::class, 10);
        $dto->markPresent('catalog');

        $payload = $service->createResource('layer', $dto);

        $this->assertSame('5', (string) $repository->createdAttributes['layergroup_id']);
        $this->assertSame('10', (string) $repository->createdAttributes['catalog_id']);
        $this->assertSame('5', $payload['data']['relationships']['layergroup']['data']['id']);
        $this->assertSame('10', $payload['data']['relationships']['catalog']['data']['id']);
    }

    public function testBuildQueryOptionsAcceptsRelationshipFilterAliases(): void
    {
        $service = TestApiCrudService::create();
        $queryOptions = $service->buildQueryOptions(DtoSchemaRegistry::schemaForType('layer'), [
            'filter' => [
                'layergroup' => '5',
                'catalog' => '10',
            ],
        ]);

        $this->assertSame([
            'catalog_id' => '10',
            'layergroup_id' => '5',
        ], $queryOptions->getFilters());
    }

    public function testUpdateAcceptsRelationshipIdsWhenAttributesAreNullOnPut(): void
    {
        $repository = new EntityRepositoryStub(
            [],
            static fn ($ref) => [
                'layer_id' => (int) $ref->getId(),
                'layergroup_id' => 1,
                'catalog_id' => 1,
                'layer_name' => 'buildings',
                'layertype_id' => 3,
                'sizeunits_id' => 1.0,
                'layer_title' => 'Buildings',
            ],
            null,
            null,
            static fn ($entity) => array_merge([
                'layer_id' => (int) $entity->getId(),
                'layergroup_id' => 1,
                'catalog_id' => 1,
                'layer_name' => 'buildings',
                'layertype_id' => 3,
                'sizeunits_id' => 1.0,
            ], $entity->getAttributes())
        );
        $service = TestApiCrudService::create($repository);
        $dto = $this->makeDto(LayerDto::class, null, [
            'layer_name' => 'buildings',
            'layer_title' => 'buildings UPDATE',
            'layertype_id' => 3,
            'sizeunits_id' => 1.0,
        ], [
            'layergroup' => $this->identifierDto(LayergroupDto::class, 2),
            'catalog' => $this->identifierDto(CatalogDto::class, 2),
        ]);

        $payload = $service->updateResource('layer', 2, $dto);

        $this->assertSame('2', $payload['data']['id']);
        $this->assertSame('2', $payload['data']['relationships']['layergroup']['data']['id']);
        $this->assertSame('2', $payload['data']['relationships']['catalog']['data']['id']);
    }

    private function baseLayerDto(): LayerDto
    {
        /** @var LayerDto $dto */
        $dto = $this->makeDto(LayerDto::class, 2, [
            'layer_name' => 'buildings',
            'layertype_id' => 3,
        ]);

        return $dto;
    }
}
