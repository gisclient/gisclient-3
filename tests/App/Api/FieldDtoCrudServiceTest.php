<?php

require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Definition\DtoEntityDefinitionProvider;
use GisClient\Author\Api\Dto\FieldDto;
use GisClient\Author\Api\Dto\LayerDto;
use PHPUnit\Framework\TestCase;

class FieldDtoCrudServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    public function testCreateRequiresLayerRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(FieldDto::class, 51, [
            'field_name' => 'gid',
            'field_header' => 'GID',
        ]);

        $this->assertApiException(static function () use ($service, $dto): void {
            $service->createResource('field', $dto);
        }, 422, 'missing_required_relationship', '/data/relationships/layer/data');
    }

    public function testCreateMapsLayerRelationshipToLocalKey(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $dto = $this->makeDto(FieldDto::class, 51, [
            'field_name' => 'gid',
            'field_header' => 'GID',
        ], [
            'layer' => $this->identifierDto(LayerDto::class, 2),
        ]);

        $payload = $service->createResource('field', $dto);

        $this->assertSame('2', (string) $repository->createdAttributes['layer_id']);
        $this->assertSame('2', $payload['data']['relationships']['layer']['data']['id']);
    }

    public function testBuildQueryOptionsAcceptsRelationshipFilterAlias(): void
    {
        $service = TestApiCrudService::create();
        $provider = new DtoEntityDefinitionProvider();
        $queryOptions = $service->buildQueryOptions($provider->getEntityDefinition('field'), [
            'filter' => [
                'layer' => '2',
            ],
        ]);

        $this->assertSame([
            'layer_id' => '2',
        ], $queryOptions->getFilters());
    }

    public function testGetResourceKeepsRelationIdAsAttribute(): void
    {
        $repository = new AuthorEntityRepositoryStub([], static fn ($definition, $id, array $scopeFilters) => [
            'field_id' => (int) $id,
            'layer_id' => 2,
            'relation_id' => 0,
            'field_name' => 'gid',
            'field_header' => 'GID',
        ]);
        $service = TestApiCrudService::create($repository);
        $payload = $service->getResource('field', 51);

        $this->assertSame('2', $payload['data']['relationships']['layer']['data']['id']);
        $this->assertSame(0, $payload['data']['attributes']['relation_id']);
    }
}
