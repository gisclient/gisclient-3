<?php

require_once __DIR__ . '/Support/AuthorEntityRepositoryStub.php';
require_once __DIR__ . '/Support/TestApiCrudService.php';

use GisClient\Author\Api\Definition\DtoEntityDefinitionProvider;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\Model\EntityDefinition;
use PHPUnit\Framework\TestCase;

class ApiCrudServiceTest extends TestCase
{
    public function testCreateMapsDataIdToPrimaryKey(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);

        $service->createResource('project', [
            'data' => [
                'type' => 'project',
                'id' => 'milano',
                'attributes' => [
                    'project_title' => 'Milano',
                    'project_srid' => 3857,
                    'max_extent_scale' => 50000,
                    'charset_encodings_id' => 1,
                    'default_language_id' => 'it',
                ],
            ],
        ]);

        $this->assertSame('milano', $repository->createdAttributes['project_name']);
        $this->assertSame('Milano', $repository->createdAttributes['project_title']);
    }

    public function testCreateRejectsInvalidAttributeTypeWith422(): void
    {
        $service = TestApiCrudService::create();

        try {
            $service->createResource('project', [
                'data' => [
                    'type' => 'project',
                    'id' => 'milano',
                    'attributes' => [
                        'project_title' => 'Milano',
                        'project_srid' => 3857,
                        'max_extent_scale' => 'A50000',
                        'charset_encodings_id' => 1,
                        'default_language_id' => 'it',
                    ],
                ],
            ]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertSame('invalid_attribute_type', $errors[0]['code']);
            $this->assertSame('max_extent_scale', $errors[0]['source']['attribute']);
        }
    }

    public function testCreateReturnsAllValidationErrorsAtOnce(): void
    {
        $service = TestApiCrudService::create();

        try {
            $service->createResource('project', [
                'data' => [
                    'type' => 'project',
                    'id' => 'milano',
                    'attributes' => [
                        'hidden_field' => 'hidden',
                        'project_title' => '',
                        'project_srid' => 3857,
                        'max_extent_scale' => 'A50000',
                        'charset_encodings_id' => 1,
                        'default_language_id' => 'it',
                    ],
                ],
            ]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $codes = array_column($exception->getErrors(), 'code');
            $this->assertContains('invalid_attribute', $codes);
            $this->assertContains('missing_required_attribute', $codes);
            $this->assertContains('invalid_attribute_type', $codes);
        }
    }

    public function testCreateRejectsDuplicatePrimaryKeyWithExplicitConflict(): void
    {
        $repository = new AuthorEntityRepositoryStub(['milano']);
        $service = TestApiCrudService::create($repository);

        $this->assertApiException(function () use ($service): void {
            $service->createResource('project', [
                'data' => [
                    'type' => 'project',
                    'id' => 'milano',
                    'attributes' => [
                        'project_title' => 'Milano',
                        'project_srid' => 3857,
                        'max_extent_scale' => 50000,
                        'charset_encodings_id' => 1,
                        'default_language_id' => 'it',
                    ],
                ],
            ]);
        }, 409, 'duplicate_primary_key', '/data/id');
    }

    public function testCreateRejectsUnknownRelationshipReference(): void
    {
        $repository = new AuthorEntityRepositoryStub([], static function (EntityDefinition $definition, $id, array $scopeFilters) {
            if ($definition->getType() === 'project') {
                return null;
            }

            return null;
        });
        $service = new \GisClient\Author\Api\Service\ApiCrudService(
            new DtoEntityDefinitionProvider(),
            $repository,
            new \GisClient\Author\Api\Validation\PayloadValidator(null, static fn (): bool => true)
        );

        try {
            $service->createResource('theme', [
                'data' => [
                    'type' => 'theme',
                    'id' => '4',
                    'attributes' => [
                        'theme_name' => 'boundaries_places',
                        'theme_title' => 'Boundaries and places',
                        'theme_order' => 10,
                    ],
                    'relationships' => [
                        'project' => [
                            'data' => [
                                'type' => 'project',
                                'id' => 'missing_project',
                            ],
                        ],
                    ],
                ],
            ]);
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
        $service = new \GisClient\Author\Api\Service\ApiCrudService(
            new DtoEntityDefinitionProvider(),
            $repository,
            new \GisClient\Author\Api\Validation\PayloadValidator(null, static function (array $lookupRule, $value): bool {
                if (($lookupRule['table'] ?? null) !== 'seldb_mapset_srid') {
                    return true;
                }

                return (int) $value === 3857
                    && (($lookupRule['resolved_filters']['project_name'] ?? null) === 'milano');
            })
        );

        try {
            $service->createResource('mapset', [
                'data' => [
                    'type' => 'mapset',
                    'id' => 'base',
                    'attributes' => [
                        'mapset_title' => 'Base map',
                        'mapset_srid' => 32632,
                        'displayprojection' => 32632,
                        'maxscale' => 50000,
                        'mapset_extent' => '0 0 10 10',
                    ],
                    'relationships' => [
                        'project' => [
                            'data' => [
                                'type' => 'project',
                                'id' => 'milano',
                            ],
                        ],
                    ],
                ],
            ]);
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
        $service = new \GisClient\Author\Api\Service\ApiCrudService(
            new DtoEntityDefinitionProvider(),
            $repository,
            new \GisClient\Author\Api\Validation\PayloadValidator(null, static function (array $lookupRule, $value): bool {
                if (($lookupRule['table'] ?? null) !== 'seldb_relation') {
                    return true;
                }

                return (int) $value === 0
                    && (($lookupRule['resolved_filters']['layer_id'] ?? null) === '2'
                        || ($lookupRule['resolved_filters']['layer_id'] ?? null) === 2);
            })
        );

        try {
            $service->createResource('field', [
                'data' => [
                    'type' => 'field',
                    'id' => '51',
                    'attributes' => [
                        'relation_id' => 99,
                        'field_name' => 'gid',
                        'field_header' => 'GID',
                        'fieldtype_id' => 1,
                        'datatype_id' => 1,
                        'resultype_id' => 1,
                        'searchtype_id' => 1,
                        'orderby_id' => 0,
                    ],
                    'relationships' => [
                        'layer' => [
                            'data' => [
                                'type' => 'layer',
                                'id' => '2',
                            ],
                        ],
                    ],
                ],
            ]);
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

    public function testScopedCreateInjectsScopeAndRendersParentRelationship(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $payload = $service->createResource('project_srs', [
            'data' => [
                'type' => 'project_srs',
                'id' => '3857',
                'attributes' => [
                    'projparam' => '+proj=merc',
                ],
                'relationships' => [
                    'project' => [
                        'data' => [
                            'type' => 'project',
                            'id' => 'default',
                        ],
                    ],
                ],
            ],
        ], [
            'project_name' => 'default',
        ]);

        $this->assertSame('default', $repository->createdAttributes['project_name']);
        $this->assertSame('3857', (string) $payload['data']['id']);
        $this->assertSame('default', $payload['data']['relationships']['project']['data']['id']);
        $this->assertArrayNotHasKey('project_name', $payload['data']['attributes']);
    }

    public function testScopedCreateRejectsMismatchedScopeAttribute(): void
    {
        $service = TestApiCrudService::create();

        $this->assertApiException(function () use ($service): void {
            $service->createResource('project_srs', [
                'data' => [
                    'type' => 'project_srs',
                    'id' => '3857',
                    'attributes' => [
                        'project_name' => 'other_project',
                        'projparam' => '+proj=merc',
                    ],
                    'relationships' => [
                        'project' => [
                            'data' => [
                                'type' => 'project',
                                'id' => 'default',
                            ],
                        ],
                    ],
                ],
            ], [
                'project_name' => 'default',
            ]);
        }, 422, 'scope_attribute_mismatch', '/data/attributes/project_name');
    }

    public function testScopedGetUsesScopeFilter(): void
    {
        $repository = new AuthorEntityRepositoryStub(['default|3857']);
        $service = TestApiCrudService::create($repository);

        $payload = $service->getResource('project_srs', 3857, [], [
            'project_name' => 'default',
        ]);
        $this->assertSame('3857', (string) $payload['data']['id']);

        $this->assertApiException(function () use ($service): void {
            $service->getResource('project_srs', 3857, [], [
                'project_name' => 'other',
            ]);
        }, 404, 'resource_not_found');
    }

    public function testScopedCreateRequiresProjectRelationship(): void
    {
        $service = TestApiCrudService::create();

        $this->assertApiException(function () use ($service): void {
            $service->createResource('project_srs', [
                'data' => [
                    'type' => 'project_srs',
                    'id' => '3857',
                    'attributes' => [
                        'projparam' => '+proj=merc',
                    ],
                ],
            ], [
                'project_name' => 'default',
            ]);
        }, 422, 'missing_required_relationship', '/data/relationships/project/data');
    }

    public function testScopedMapsetLayergroupCreateInjectsScopeAndRendersRelationships(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $payload = $service->createResource('mapset_layergroup', [
            'data' => [
                'type' => 'mapset_layergroup',
                'id' => '42',
                'attributes' => [
                    'status' => 1,
                    'refmap' => 0,
                    'hide' => 1,
                ],
                'relationships' => [
                    'mapset' => [
                        'data' => [
                            'type' => 'mapset',
                            'id' => 'base',
                        ],
                    ],
                    'layergroup' => [
                        'data' => [
                            'type' => 'layergroup',
                            'id' => '42',
                        ],
                    ],
                ],
            ],
        ], [
            'mapset_name' => 'base',
        ]);

        $this->assertSame('base', $repository->createdAttributes['mapset_name']);
        $this->assertSame('42', (string) $repository->createdAttributes['layergroup_id']);
        $this->assertSame('42', (string) $payload['data']['id']);
        $this->assertSame('base', $payload['data']['relationships']['mapset']['data']['id']);
        $this->assertSame('42', $payload['data']['relationships']['layergroup']['data']['id']);
        $this->assertArrayNotHasKey('mapset_name', $payload['data']['attributes']);
        $this->assertArrayNotHasKey('layergroup_id', $payload['data']['attributes']);
    }

    public function testScopedMapsetLayergroupCreateRequiresMapsetRelationship(): void
    {
        $service = TestApiCrudService::create();

        $this->assertApiException(function () use ($service): void {
            $service->createResource('mapset_layergroup', [
                'data' => [
                    'type' => 'mapset_layergroup',
                    'id' => '42',
                    'attributes' => [
                        'status' => 1,
                    ],
                    'relationships' => [
                        'layergroup' => [
                            'data' => [
                                'type' => 'layergroup',
                                'id' => '42',
                            ],
                        ],
                    ],
                ],
            ], [
                'mapset_name' => 'base',
            ]);
        }, 422, 'missing_required_relationship', '/data/relationships/mapset/data');
    }

    public function testScopedMapsetLayergroupCreateRequiresLayergroupRelationship(): void
    {
        $service = TestApiCrudService::create();

        $this->assertApiException(function () use ($service): void {
            $service->createResource('mapset_layergroup', [
                'data' => [
                    'type' => 'mapset_layergroup',
                    'id' => '42',
                    'attributes' => [
                        'status' => 1,
                    ],
                    'relationships' => [
                        'mapset' => [
                            'data' => [
                                'type' => 'mapset',
                                'id' => 'base',
                            ],
                        ],
                    ],
                ],
            ], [
                'mapset_name' => 'base',
            ]);
        }, 422, 'missing_required_relationship', '/data/relationships/layergroup/data');
    }

    public function testScopedMapsetLayergroupCreateRejectsMismatchedMapsetRelationship(): void
    {
        $service = TestApiCrudService::create();

        $this->assertApiException(function () use ($service): void {
            $service->createResource('mapset_layergroup', [
                'data' => [
                    'type' => 'mapset_layergroup',
                    'id' => '42',
                    'attributes' => [
                        'status' => 1,
                    ],
                    'relationships' => [
                        'mapset' => [
                            'data' => [
                                'type' => 'mapset',
                                'id' => 'other',
                            ],
                        ],
                        'layergroup' => [
                            'data' => [
                                'type' => 'layergroup',
                                'id' => '42',
                            ],
                        ],
                    ],
                ],
            ], [
                'mapset_name' => 'base',
            ]);
        }, 422, 'relationship_scope_mismatch', '/data/relationships/mapset/data/id');
    }

    public function testScopedMapsetLayergroupGetUsesScopeFilter(): void
    {
        $repository = new AuthorEntityRepositoryStub(['base|42']);
        $service = TestApiCrudService::create($repository);

        $payload = $service->getResource('mapset_layergroup', 42, [], [
            'mapset_name' => 'base',
        ]);
        $this->assertSame('42', (string) $payload['data']['id']);

        $this->assertApiException(function () use ($service): void {
            $service->getResource('mapset_layergroup', 42, [], [
                'mapset_name' => 'other',
            ]);
        }, 404, 'resource_not_found');
    }

    public function testScopedMapsetLayergroupPutAllowsRelationshipOnlyMapsetWithoutAttributeMismatch(): void
    {
        $repository = new AuthorEntityRepositoryStub(['base|42']);
        $service = TestApiCrudService::create($repository);

        $payload = $service->updateResource('mapset_layergroup', 42, [
            'data' => [
                'type' => 'mapset_layergroup',
                'id' => '42',
                'attributes' => [
                    'status' => 1,
                    'refmap' => 1,
                    'hide' => 0,
                ],
                'relationships' => [
                    'mapset' => [
                        'data' => [
                            'type' => 'mapset',
                            'id' => 'base',
                        ],
                    ],
                    'layergroup' => [
                        'data' => [
                            'type' => 'layergroup',
                            'id' => '42',
                        ],
                    ],
                ],
            ],
        ], [
            'mapset_name' => 'base',
        ]);

        $this->assertSame('base', $repository->updatedAttributes['mapset_name']);
        $this->assertSame(1, $repository->updatedAttributes['status']);
        $this->assertSame('base', $payload['data']['relationships']['mapset']['data']['id']);
        $this->assertSame('42', $payload['data']['relationships']['layergroup']['data']['id']);
    }

    public function testTopLevelCreateMapsProjectRelationshipToLocalKey(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $payload = $service->createResource('theme', [
            'data' => [
                'type' => 'theme',
                'id' => '4',
                'attributes' => [
                    'theme_name' => 'boundaries_places',
                    'theme_title' => 'Boundaries and places',
                    'theme_order' => 10,
                ],
                'relationships' => [
                    'project' => [
                        'data' => [
                            'type' => 'project',
                            'id' => 'milano',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame('milano', $repository->createdAttributes['project_name']);
        $this->assertSame('milano', $payload['data']['relationships']['project']['data']['id']);
        $this->assertArrayNotHasKey('project_name', $payload['data']['attributes']);
    }

    public function testTopLevelCatalogCreateRequiresProjectRelationship(): void
    {
        $service = TestApiCrudService::create();

        $this->assertApiException(function () use ($service): void {
            $service->createResource('catalog', [
                'data' => [
                    'type' => 'catalog',
                    'id' => '2',
                    'attributes' => [
                        'catalog_name' => 'Main catalog',
                        'connection_type' => 6,
                        'catalog_path' => 'dbname=test',
                    ],
                ],
            ]);
        }, 422, 'missing_required_relationship', '/data/relationships/project/data');
    }

    public function testTopLevelCatalogCreateMapsProjectRelationshipToLocalKey(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $payload = $service->createResource('catalog', [
            'data' => [
                'type' => 'catalog',
                'id' => '2',
                'attributes' => [
                    'catalog_name' => 'Main catalog',
                    'connection_type' => 6,
                    'catalog_path' => 'dbname=test',
                ],
                'relationships' => [
                    'project' => [
                        'data' => [
                            'type' => 'project',
                            'id' => 'milano',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame('milano', $repository->createdAttributes['project_name']);
        $this->assertSame('milano', $payload['data']['relationships']['project']['data']['id']);
        $this->assertArrayNotHasKey('project_name', $payload['data']['attributes']);
    }

    public function testTopLevelLinkCreateRequiresProjectRelationship(): void
    {
        $service = TestApiCrudService::create();

        $this->assertApiException(function () use ($service): void {
            $service->createResource('link', [
                'data' => [
                    'type' => 'link',
                    'id' => '2',
                    'attributes' => [
                        'link_name' => 'Docs',
                        'link_def' => 'https://example.test',
                    ],
                ],
            ]);
        }, 422, 'missing_required_relationship', '/data/relationships/project/data');
    }

    public function testTopLevelLinkCreateMapsProjectRelationshipToLocalKey(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $payload = $service->createResource('link', [
            'data' => [
                'type' => 'link',
                'id' => '2',
                'attributes' => [
                    'link_name' => 'Docs',
                    'link_def' => 'https://example.test',
                ],
                'relationships' => [
                    'project' => [
                        'data' => [
                            'type' => 'project',
                            'id' => 'milano',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame('milano', $repository->createdAttributes['project_name']);
        $this->assertSame('milano', $payload['data']['relationships']['project']['data']['id']);
        $this->assertArrayNotHasKey('project_name', $payload['data']['attributes']);
    }

    public function testTopLevelMapsetCreateRequiresProjectRelationship(): void
    {
        $service = TestApiCrudService::create();

        $this->assertApiException(function () use ($service): void {
            $service->createResource('mapset', [
                'data' => [
                    'type' => 'mapset',
                    'id' => 'base',
                    'attributes' => [
                        'mapset_title' => 'Base map',
                        'mapset_srid' => 3857,
                        'displayprojection' => 4326,
                        'maxscale' => 50000,
                        'mapset_extent' => '0 0 10 10',
                    ],
                ],
            ]);
        }, 422, 'missing_required_relationship', '/data/relationships/project/data');
    }

    public function testTopLevelMapsetCreateMapsProjectRelationshipAndKeepsSridAttributes(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $payload = $service->createResource('mapset', [
            'data' => [
                'type' => 'mapset',
                'id' => 'base',
                'attributes' => [
                    'mapset_title' => 'Base map',
                    'mapset_srid' => 3857,
                    'displayprojection' => 4326,
                    'maxscale' => 50000,
                    'mapset_extent' => '0 0 10 10',
                ],
                'relationships' => [
                    'project' => [
                        'data' => [
                            'type' => 'project',
                            'id' => 'milano',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame('milano', $repository->createdAttributes['project_name']);
        $this->assertSame(3857, $repository->createdAttributes['mapset_srid']);
        $this->assertSame(4326, $repository->createdAttributes['displayprojection']);
        $this->assertSame('milano', $payload['data']['relationships']['project']['data']['id']);
        $this->assertSame(3857, $payload['data']['attributes']['mapset_srid']);
        $this->assertSame(4326, $payload['data']['attributes']['displayprojection']);
    }

    public function testTopLevelMapsetGetKeepsSridFieldsAsAttributes(): void
    {
        $repository = new AuthorEntityRepositoryStub([], static fn (EntityDefinition $definition, $id, array $scopeFilters) => [
            'mapset_name' => (string) $id,
            'project_name' => 'milano',
            'mapset_title' => 'Base map',
            'mapset_srid' => '3857',
            'displayprojection' => '4326',
            'maxscale' => '50000',
            'mapset_extent' => '0 0 10 10',
        ]);
        $service = TestApiCrudService::create($repository);
        $payload = $service->getResource('mapset', 'base');

        $this->assertSame(3857, $payload['data']['attributes']['mapset_srid']);
        $this->assertSame(4326, $payload['data']['attributes']['displayprojection']);
        $this->assertSame('milano', $payload['data']['relationships']['project']['data']['id']);
    }

    public function testTopLevelLayergroupCreateRequiresThemeRelationship(): void
    {
        $service = TestApiCrudService::create();

        $this->assertApiException(function () use ($service): void {
            $service->createResource('layergroup', [
                'data' => [
                    'type' => 'layergroup',
                    'id' => '5',
                    'attributes' => [
                        'layergroup_name' => 'base',
                    ],
                ],
            ]);
        }, 422, 'missing_required_relationship', '/data/relationships/theme/data');
    }

    public function testTopLevelLayergroupCreateMapsThemeRelationshipToLocalKey(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $payload = $service->createResource('layergroup', [
            'data' => [
                'type' => 'layergroup',
                'id' => '5',
                'attributes' => [
                    'layergroup_name' => 'base',
                ],
                'relationships' => [
                    'theme' => [
                        'data' => [
                            'type' => 'theme',
                            'id' => '4',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame('4', (string) $repository->createdAttributes['theme_id']);
        $this->assertSame('4', $payload['data']['relationships']['theme']['data']['id']);
        $this->assertArrayNotHasKey('theme_id', $payload['data']['attributes']);
    }

    public function testTopLevelLayerCreateRequiresLayergroupRelationship(): void
    {
        $service = TestApiCrudService::create();

        $this->assertApiException(function () use ($service): void {
            $service->createResource('layer', [
                'data' => [
                    'type' => 'layer',
                    'id' => '2',
                    'attributes' => [
                        'layer_name' => 'buildings',
                        'layertype_id' => 3,
                    ],
                    'relationships' => [
                        'catalog' => [
                            'data' => [
                                'type' => 'catalog',
                                'id' => '10',
                            ],
                        ],
                    ],
                ],
            ]);
        }, 422, 'missing_required_relationship', '/data/relationships/layergroup/data');
    }

    public function testTopLevelLayerCreateRequiresCatalogRelationship(): void
    {
        $service = TestApiCrudService::create();

        $this->assertApiException(function () use ($service): void {
            $service->createResource('layer', [
                'data' => [
                    'type' => 'layer',
                    'id' => '2',
                    'attributes' => [
                        'layer_name' => 'buildings',
                        'layertype_id' => 3,
                    ],
                    'relationships' => [
                        'layergroup' => [
                            'data' => [
                                'type' => 'layergroup',
                                'id' => '5',
                            ],
                        ],
                    ],
                ],
            ]);
        }, 422, 'missing_required_relationship', '/data/relationships/catalog/data');
    }

    public function testTopLevelLayerCreateMapsParentRelationshipsToLocalKeys(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $payload = $service->createResource('layer', [
            'data' => [
                'type' => 'layer',
                'id' => '2',
                'attributes' => [
                    'layer_name' => 'buildings',
                    'layertype_id' => 3,
                ],
                'relationships' => [
                    'layergroup' => [
                        'data' => [
                            'type' => 'layergroup',
                            'id' => '5',
                        ],
                    ],
                    'catalog' => [
                        'data' => [
                            'type' => 'catalog',
                            'id' => '10',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame('5', (string) $repository->createdAttributes['layergroup_id']);
        $this->assertSame('10', (string) $repository->createdAttributes['catalog_id']);
        $this->assertSame('5', $payload['data']['relationships']['layergroup']['data']['id']);
        $this->assertSame('10', $payload['data']['relationships']['catalog']['data']['id']);
        $this->assertArrayNotHasKey('layergroup_id', $payload['data']['attributes']);
        $this->assertArrayNotHasKey('catalog_id', $payload['data']['attributes']);
    }

    public function testBuildQueryOptionsAcceptsLayerRelationshipFilterAliases(): void
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

    public function testTopLevelClassCreateMapsLayerRelationshipToLocalKey(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $payload = $service->createResource('class', [
            'data' => [
                'type' => 'class',
                'id' => '31',
                'attributes' => [
                    'class_name' => 'buildings',
                    'class_title' => 'Buildings',
                    'class_order' => 1,
                ],
                'relationships' => [
                    'layer' => [
                        'data' => [
                            'type' => 'layer',
                            'id' => '2',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame('2', (string) $repository->createdAttributes['layer_id']);
        $this->assertSame('2', $payload['data']['relationships']['layer']['data']['id']);
        $this->assertArrayNotHasKey('layer_id', $payload['data']['attributes']);
    }

    public function testBuildQueryOptionsAcceptsClassRelationshipFilterAlias(): void
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

    public function testTopLevelStyleCreateMapsClassRelationshipToLocalKey(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $payload = $service->createResource('style', [
            'data' => [
                'type' => 'style',
                'id' => '41',
                'attributes' => [
                    'style_name' => 'default',
                    'style_order' => 1,
                ],
                'relationships' => [
                    'class' => [
                        'data' => [
                            'type' => 'class',
                            'id' => '3',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame('3', (string) $repository->createdAttributes['class_id']);
        $this->assertSame('3', $payload['data']['relationships']['class']['data']['id']);
        $this->assertArrayNotHasKey('class_id', $payload['data']['attributes']);
    }

    public function testBuildQueryOptionsAcceptsStyleRelationshipFilterAlias(): void
    {
        $service = TestApiCrudService::create();
        $provider = new DtoEntityDefinitionProvider();
        $queryOptions = $service->buildQueryOptions($provider->getEntityDefinition('style'), [
            'filter' => [
                'class' => '3',
            ],
        ]);

        $this->assertSame([
            'class_id' => '3',
        ], $queryOptions->getFilters());
    }

    public function testTopLevelFieldCreateMapsLayerRelationshipToLocalKey(): void
    {
        $repository = new AuthorEntityRepositoryStub();
        $service = TestApiCrudService::create($repository);
        $payload = $service->createResource('field', [
            'data' => [
                'type' => 'field',
                'id' => '51',
                'attributes' => [
                    'field_name' => 'gid',
                    'field_header' => 'GID',
                ],
                'relationships' => [
                    'layer' => [
                        'data' => [
                            'type' => 'layer',
                            'id' => '2',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame('2', (string) $repository->createdAttributes['layer_id']);
        $this->assertSame('2', $payload['data']['relationships']['layer']['data']['id']);
        $this->assertArrayNotHasKey('layer_id', $payload['data']['attributes']);
    }

    public function testBuildQueryOptionsAcceptsFieldRelationshipFilterAlias(): void
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

    public function testGetFieldResourceKeepsRelationIdAsAttribute(): void
    {
        $repository = new AuthorEntityRepositoryStub([], static fn (EntityDefinition $definition, $id, array $scopeFilters) => [
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

    public function testGetResourceCastsNumericAttributesFromDatabaseStrings(): void
    {
        $repository = new AuthorEntityRepositoryStub([], static fn (EntityDefinition $definition, $id, array $scopeFilters) => [
            'theme_id' => '3',
            'project_name' => 'milano',
            'theme_name' => 'boundaries_places3',
            'theme_single' => '0',
            'radio' => '1',
        ]);
        $service = TestApiCrudService::create($repository);
        $payload = $service->getResource('theme', '3');

        $this->assertSame('3', $payload['data']['id']);
        $this->assertSame(0, $payload['data']['attributes']['theme_single']);
        $this->assertSame(1, $payload['data']['attributes']['radio']);
        $this->assertSame('milano', $payload['data']['relationships']['project']['data']['id']);
    }

    private function assertApiException(callable $callable, int $status, string $code, ?string $pointer = null): void
    {
        try {
            $callable();
            $this->fail(sprintf('Expected %s ApiException', $code));
        } catch (ApiException $exception) {
            $this->assertSame($status, $exception->getStatus());
            $this->assertSame($code, $exception->getErrorCode());
            if ($pointer !== null) {
                $this->assertSame($pointer, $exception->getSourcePointer());
            }
        }
    }
}
