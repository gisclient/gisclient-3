<?php

require_once __DIR__ . '/Support/AuthorEntityRepositoryStub.php';
require_once __DIR__ . '/Support/ApiCrudServiceDtoTestTrait.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Definition\DtoEntityDefinitionProvider;
use GisClient\Author\Api\Dto\FieldDto;
use GisClient\Author\Api\Dto\LayerDto;
use GisClient\Author\Api\Dto\MapsetDto;
use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Dto\ThemeDto;
use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\Model\EntityDefinition;
use GisClient\Author\Api\Service\ApiCrudService;
use GisClient\Author\Api\Validation\PayloadValidator;
use PHPUnit\Framework\TestCase;

class ApiCrudServiceTest extends TestCase
{
    use ApiCrudServiceDtoTestTrait;

    public function testCreateRejectsLegacyArrayPayloads(): void
    {
        $service = TestApiCrudService::create();

        $this->assertApiException(static function () use ($service): void {
            $service->createResource('project', [
                'data' => [
                    'type' => 'project',
                    'id' => 'milano',
                    'attributes' => [
                        'project_title' => 'Milano',
                    ],
                ],
            ]);
        }, 400, 'invalid_payload');
    }

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
        $repository = new AuthorEntityRepositoryStub([], static fn (EntityDefinition $definition, $id, array $scopeFilters) => null);
        $service = new ApiCrudService(
            new DtoEntityDefinitionProvider(),
            $repository,
            new PayloadValidator(null, static fn (): bool => true)
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
        $repository = new AuthorEntityRepositoryStub([], static function (EntityDefinition $definition, $id, array $scopeFilters) {
            if ($definition->getType() === 'project') {
                return [
                    'project_name' => (string) $id,
                ];
            }

            return null;
        });
        $service = new ApiCrudService(
            new DtoEntityDefinitionProvider(),
            $repository,
            new PayloadValidator(null, static function (array $lookupRule, $value): bool {
                if (($lookupRule['table'] ?? null) !== 'seldb_mapset_srid') {
                    return true;
                }

                return (int) $value === 3857
                    && (($lookupRule['resolved_filters']['project_name'] ?? null) === 'milano');
            })
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

    public function testCreateRejectsUnknownScopedFieldRelationReference(): void
    {
        $repository = new AuthorEntityRepositoryStub([], static function (EntityDefinition $definition, $id, array $scopeFilters) {
            if ($definition->getType() === 'layer') {
                return [
                    'layer_id' => (int) $id,
                ];
            }

            return null;
        });
        $service = new ApiCrudService(
            new DtoEntityDefinitionProvider(),
            $repository,
            new PayloadValidator(null, static function (array $lookupRule, $value): bool {
                if (($lookupRule['table'] ?? null) !== 'seldb_relation') {
                    return true;
                }

                return (int) $value === 0
                    && (($lookupRule['resolved_filters']['layer_id'] ?? null) === '2'
                        || ($lookupRule['resolved_filters']['layer_id'] ?? null) === 2);
            })
        );

        $dto = $this->makeDto(FieldDto::class, 51, [
            'relation_id' => 99,
            'field_name' => 'gid',
            'field_header' => 'GID',
            'fieldtype_id' => 1,
            'datatype_id' => 1,
            'resultype_id' => 1,
            'searchtype_id' => 1,
            'orderby_id' => 0,
        ], [
            'layer' => $this->identifierDto(LayerDto::class, 2),
        ]);

        try {
            $service->createResource('field', $dto);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertSame('invalid_reference', $exception->getErrors()[0]['code']);
            $this->assertSame('relation_id', $exception->getErrors()[0]['source']['attribute']);
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
}
