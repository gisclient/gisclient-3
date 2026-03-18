<?php

require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Definition\DtoEntityDefinitionProvider;
use GisClient\Author\Api\Dto\ClassDto;
use GisClient\Author\Api\Dto\LayerDto;
use PHPUnit\Framework\TestCase;

class ClassDtoCrudServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    public function testCreateRequiresLayerRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(ClassDto::class, 3, [
            'class_name' => 'default',
            'class_order' => 1,
        ]);

        $this->assertValidationException(static function () use ($service, $dto): void {
            $service->createResource('class', $dto);
        }, 422, 'missing_required_relationship', '/data/relationships/layer/data');
    }

    public function testCreateMapsLayerRelationshipToLocalKey(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $dto = $this->makeDto(ClassDto::class, 3, [
            'class_name' => 'default',
            'class_order' => 1,
        ], [
            'layer' => $this->identifierDto(LayerDto::class, 2),
        ]);

        $payload = $service->createResource('class', $dto);

        $this->assertSame('2', (string) $repository->createdAttributes['layer_id']);
        $this->assertSame('2', $payload['data']['relationships']['layer']['data']['id']);
    }

    public function testBuildQueryOptionsAcceptsRelationshipFilterAlias(): void
    {
        $service = TestApiCrudService::create();
        $provider = new DtoEntityDefinitionProvider();
        $queryOptions = $service->buildQueryOptions($provider->getEntityDefinition('class'), [
            'filter' => [
                'layer' => '2',
            ],
        ]);

        $this->assertSame([
            'layer_id' => '2',
        ], $queryOptions->getFilters());
    }
}
