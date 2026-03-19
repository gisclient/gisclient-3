<?php

require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Dto\ClassDto;
use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Api\Dto\StyleDto;
use PHPUnit\Framework\TestCase;

class StyleDtoCrudServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    public function testCreateRequiresClassRelationship(): void
    {
        $service = TestApiCrudService::create();
        $dto = $this->makeDto(StyleDto::class, 3, [
            'style_name' => 'default',
            'style_order' => 1,
        ]);

        $this->assertValidationException(static function () use ($service, $dto): void {
            $service->createResource('style', $dto);
        }, 422, 'missing_required_relationship', '/data/relationships/class/data');
    }

    public function testCreateMapsClassRelationshipToLocalKey(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $dto = $this->makeDto(StyleDto::class, 3, [
            'style_name' => 'default',
            'style_order' => 1,
        ], [
            'class' => $this->identifierDto(ClassDto::class, 3),
        ]);

        $payload = $service->createResource('style', $dto);

        $this->assertSame('3', (string) $repository->createdAttributes['class_id']);
        $this->assertSame('3', $payload['data']['relationships']['class']['data']['id']);
    }

    public function testBuildQueryOptionsAcceptsRelationshipFilterAlias(): void
    {
        $service = TestApiCrudService::create();
        $queryOptions = $service->buildQueryOptions(DtoSchemaRegistry::schemaForType('style'), [
            'filter' => [
                'class' => '3',
            ],
        ]);

        $this->assertSame([
            'class_id' => '3',
        ], $queryOptions->getFilters());
    }
}
