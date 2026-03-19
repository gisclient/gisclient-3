<?php

require_once __DIR__ . '/Support/EntityRepositoryStub.php';
require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Dto\MapsetDto;
use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Api\Dto\ThemeDto;
use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\Service\ApiCrudService;
use GisClient\Author\Api\Validation\EntityValidator;
use GisClient\Author\Persistence\EntityQuery;
use GisClient\Author\Persistence\PagedResult;
use PHPUnit\Framework\TestCase;

class ApiCrudServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    public function testCreateMapsDataIdToPrimaryKey(): void
    {
        $repository = new EntityRepositoryStub();
        $service = TestApiCrudService::create($repository);

        $service->createResource('project', $this->makeDto(ProjectDto::class, 'milano', [
            'project_title' => 'Milano',
            'project_srid' => 3857,
            'max_extent_scale' => 50000,
            'charset_encodings_id' => 1,
            'default_language_id' => 'it',
        ]));

        $this->assertSame('milano', $repository->createdAttributes['project_name']);
        $this->assertSame('Milano', $repository->createdAttributes['project_title']);
    }

    public function testCreateRejectsDuplicatePrimaryKeyWithExplicitConflict(): void
    {
        $repository = new EntityRepositoryStub(['milano']);
        $service = TestApiCrudService::create($repository);

        $dto = $this->makeDto(ProjectDto::class, 'milano', [
            'project_title' => 'Milano',
            'project_srid' => 3857,
            'max_extent_scale' => 50000,
            'charset_encodings_id' => 1,
            'default_language_id' => 'it',
        ]);

        $this->assertApiException(static function () use ($service, $dto): void {
            $service->createResource('project', $dto);
        }, 409, 'duplicate_primary_key', '/data/id');
    }

    public function testCreateRejectsUnknownRelationshipReference(): void
    {
        $repository = new EntityRepositoryStub([], static fn ($ref) => null);
        $service = new ApiCrudService(
            $repository,
            new EntityValidator(null, static fn (): bool => true, $repository)
        );

        $dto = $this->makeDto(ThemeDto::class, 4, [
            'theme_name' => 'boundaries_places',
            'theme_title' => 'Boundaries and places',
            'theme_order' => 10,
        ], [
            'project' => $this->identifierDto(ProjectDto::class, 'missing_project'),
        ]);

        try {
            $service->createResource('theme', $dto);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertSame('invalid_relationship', $exception->getErrors()[0]['code']);
            $this->assertSame('/data/relationships/project/data/id', $exception->getErrors()[0]['source']['pointer']);
        }
    }

    public function testCreateRejectsUnknownScopedMapsetSridReference(): void
    {
        $repository = new EntityRepositoryStub([], static function ($ref) {
            if ($ref->getType() === 'project') {
                return [
                    'project_name' => (string) $ref->getId(),
                ];
            }

            return null;
        });
        $service = new ApiCrudService(
            $repository,
            new EntityValidator(null, static function (array $lookupRule, $value): bool {
                if (($lookupRule['table'] ?? null) !== 'seldb_mapset_srid') {
                    return true;
                }

                return (int) $value === 3857
                    && (($lookupRule['resolved_filters']['project_name'] ?? null) === 'milano');
            }, $repository)
        );

        $dto = $this->makeDto(MapsetDto::class, 'base', [
            'mapset_title' => 'Base map',
            'mapset_srid' => 32632,
            'displayprojection' => 32632,
            'maxscale' => 50000,
            'mapset_extent' => '0 0 10 10',
        ], [
            'project' => $this->identifierDto(ProjectDto::class, 'milano'),
        ]);

        try {
            $service->createResource('mapset', $dto);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertSame('invalid_reference', $exception->getErrors()[0]['code']);
            $this->assertSame('mapset_srid', $exception->getErrors()[0]['source']['attribute']);
        }
    }

    public function testBuildQueryOptionsAcceptsRelationshipFilterAlias(): void
    {
        $service = TestApiCrudService::create();
        $query = $service->buildEntityQuery(DtoSchemaRegistry::schemaForType('theme'), [
            'filter' => [
                'project' => 'milano',
            ],
        ]);

        $this->assertSame([
            'project_name' => 'milano',
        ], $query->getFilters());
    }

    public function testBuildQueryOptionsAcceptsMultipleRelationshipFilterAliases(): void
    {
        $service = TestApiCrudService::create();
        $query = $service->buildEntityQuery(DtoSchemaRegistry::schemaForType('layer'), [
            'filter' => [
                'layergroup' => '5',
                'catalog' => '10',
            ],
        ]);

        $this->assertSame([
            'catalog_id' => '10',
            'layergroup_id' => '5',
        ], $query->getFilters());
    }

    public function testListResourcesPassesNormalizedFiltersThroughQueryOptions(): void
    {
        $capturedQueryOptions = null;
        $repository = new EntityRepositoryStub(
            [],
            null,
            static function (EntityQuery $query) use (&$capturedQueryOptions) {
                $capturedQueryOptions = $query;

                return new PagedResult([], 0, $query->getLimit(), $query->getOffset());
            }
        );
        $service = new ApiCrudService(
            $repository,
            new EntityValidator(null, static fn (): bool => true, $repository)
        );

        $service->listResources('theme', [
            'filter' => [
                'project' => 'milano',
            ],
            'limit' => 10,
            'offset' => 5,
        ]);

        $this->assertNotNull($capturedQueryOptions);
        $this->assertSame([
            'project_name' => 'milano',
        ], $capturedQueryOptions->getFilters());
        $this->assertSame(10, $capturedQueryOptions->getLimit());
        $this->assertSame(5, $capturedQueryOptions->getOffset());
    }

    public function testDeleteRemovesExistingResourceWithoutScopeContext(): void
    {
        $deleted = [];
        $repository = new EntityRepositoryStub(
            ['milano'],
            null,
            null,
            null,
            null,
            static function ($ref) use (&$deleted): void {
                $deleted = [
                    'type' => $ref->getType(),
                    'id' => $ref->getId(),
                ];
            }
        );
        $service = TestApiCrudService::create($repository);

        $service->deleteResource('project', 'milano');

        $this->assertSame([
            'type' => 'project',
            'id' => 'milano',
        ], $deleted);
    }
}
