<?php

require_once __DIR__ . '/Support/AuthorEntityRepositoryStub.php';
require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Definition\DtoEntityDefinitionProvider;
use GisClient\Author\Api\Dto\MapsetDto;
use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Dto\ThemeDto;
use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\Model\EntityDefinition;
use GisClient\Author\Api\Model\PagedResult;
use GisClient\Author\Api\Model\QueryOptions;
use GisClient\Author\Api\Service\ApiCrudService;
use GisClient\Author\Api\Validation\PersistenceWriteValidator;
use PHPUnit\Framework\TestCase;

class ApiCrudServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    public function testCreateMapsDataIdToPrimaryKey(): void
    {
        $repository = new AuthorEntityRepositoryStub();
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
        $repository = new AuthorEntityRepositoryStub(['milano']);
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
        $provider = new DtoEntityDefinitionProvider();
        $repository = new AuthorEntityRepositoryStub([], static fn (EntityDefinition $definition, $id) => null);
        $service = new ApiCrudService(
            $provider,
            $repository,
            new PersistenceWriteValidator(null, static fn (): bool => true, $provider, $repository)
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
        $provider = new DtoEntityDefinitionProvider();
        $repository = new AuthorEntityRepositoryStub([], static function (EntityDefinition $definition, $id) {
            if ($definition->getType() === 'project') {
                return [
                    'project_name' => (string) $id,
                ];
            }

            return null;
        });
        $service = new ApiCrudService(
            $provider,
            $repository,
            new PersistenceWriteValidator(null, static function (array $lookupRule, $value): bool {
                if (($lookupRule['table'] ?? null) !== 'seldb_mapset_srid') {
                    return true;
                }

                return (int) $value === 3857
                    && (($lookupRule['resolved_filters']['project_name'] ?? null) === 'milano');
            }, $provider, $repository)
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
        $provider = new DtoEntityDefinitionProvider();
        $queryOptions = $service->buildQueryOptions($provider->getEntityDefinition('theme'), [
            'filter' => [
                'project' => 'milano',
            ],
        ]);

        $this->assertSame([
            'project_name' => 'milano',
        ], $queryOptions->getFilters());
    }

    public function testBuildQueryOptionsAcceptsMultipleRelationshipFilterAliases(): void
    {
        $service = TestApiCrudService::create();
        $provider = new DtoEntityDefinitionProvider();
        $queryOptions = $service->buildQueryOptions($provider->getEntityDefinition('layer'), [
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

    public function testListResourcesPassesNormalizedFiltersThroughQueryOptions(): void
    {
        $provider = new DtoEntityDefinitionProvider();
        $capturedQueryOptions = null;
        $repository = new AuthorEntityRepositoryStub(
            [],
            null,
            static function (EntityDefinition $definition, QueryOptions $queryOptions) use (&$capturedQueryOptions) {
                $capturedQueryOptions = $queryOptions;

                return new PagedResult([], 0, $queryOptions->getLimit(), $queryOptions->getOffset());
            }
        );
        $service = new ApiCrudService(
            $provider,
            $repository,
            new PersistenceWriteValidator(null, static fn (): bool => true, $provider, $repository)
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
        $repository = new AuthorEntityRepositoryStub(
            ['milano'],
            null,
            null,
            null,
            null,
            static function (EntityDefinition $definition, $id) use (&$deleted): void {
                $deleted = [
                    'type' => $definition->getType(),
                    'id' => $id,
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
