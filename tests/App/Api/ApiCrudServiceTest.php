<?php

use GisClient\Author\Api\Contract\AuthorEntityRepositoryInterface;
use GisClient\Author\Api\Contract\EntityDefinitionProviderInterface;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\Model\EntityDefinition;
use GisClient\Author\Api\Model\PagedResult;
use GisClient\Author\Api\Model\QueryOptions;
use GisClient\Author\Api\Service\ApiCrudService;
use GisClient\Author\Api\Validation\PayloadValidator;
use PHPUnit\Framework\TestCase;

class ApiCrudServiceTest extends TestCase
{
    public function testListResourcesRequiresAdmin()
    {
        $service = $this->createService(false);

        $this->assertAdminRequired(function () use ($service): void {
            $service->listResources('project', []);
        });
    }

    public function testGetResourceRequiresAdmin()
    {
        $service = $this->createService(false);

        $this->assertAdminRequired(function () use ($service): void {
            $service->getResource('project', 'default');
        });
    }

    public function testCreateResourceRequiresAdmin()
    {
        $service = $this->createService(false);

        $this->assertAdminRequired(function () use ($service): void {
            $service->createResource('project', [
                'data' => [
                    'type' => 'project',
                    'id' => 'default',
                    'attributes' => [
                        'project_title' => 'Default',
                    ],
                ],
            ]);
        });
    }

    public function testUpdateResourceRequiresAdmin()
    {
        $service = $this->createService(false);

        $this->assertAdminRequired(function () use ($service): void {
            $service->updateResource('project', 'default', [
                'data' => [
                    'type' => 'project',
                    'attributes' => [
                        'project_title' => 'Updated',
                    ],
                ],
            ]);
        });
    }

    public function testDeleteResourceRequiresAdmin()
    {
        $service = $this->createService(false);

        $this->assertAdminRequired(function () use ($service): void {
            $service->deleteResource('project', 'default');
        });
    }

    public function testCreateMapsDataIdToPrimaryKey()
    {
        $repo = null;
        $service = $this->createService(true, $repo);

        $service->createResource('project', [
            'data' => [
                'type' => 'project',
                'id' => 'milano',
                'attributes' => [
                    'project_title' => 'Milano',
                ],
            ],
        ]);

        $this->assertSame('milano', $repo->createdAttributes['project_name']);
        $this->assertSame('Milano', $repo->createdAttributes['project_title']);
    }

    public function testCreateRejectsInvalidAttributeTypeWith422()
    {
        $definition = new EntityDefinition(
            'project',
            'gisclient_34',
            'project',
            'project_name',
            'string',
            ['project_name', 'project_title', 'max_extent_scale'],
            ['project_name', 'project_title', 'max_extent_scale'],
            ['project_name', 'project_title', 'max_extent_scale'],
            ['project_title', 'max_extent_scale'],
            ['project_name'],
            ['project_name'],
            'project_name',
            [
                'max_extent_scale' => [
                    'type' => 'numeric',
                ],
            ]
        );

        $service = $this->createServiceFromDefinition($definition, true);

        try {
            $service->createResource('project', [
                'data' => [
                    'type' => 'project',
                    'id' => 'milano',
                    'attributes' => [
                        'project_title' => 'Milano',
                        'max_extent_scale' => 'A50000',
                    ],
                ],
            ]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertSame('invalid_attribute_type', $errors[0]['code']);
            $this->assertSame('/data/attributes/max_extent_scale', $errors[0]['source']['pointer']);
        }
    }

    public function testCreateRejectsHiddenAttribute()
    {
        $definition = new EntityDefinition(
            'project',
            'gisclient_34',
            'project',
            'project_name',
            'string',
            ['project_name', 'project_title'],
            ['project_title'],
            ['project_name', 'project_title'],
            ['project_title'],
            ['project_name'],
            ['project_name'],
            'project_name'
        );

        $service = $this->createServiceFromDefinition($definition, true);

        try {
            $service->createResource('project', [
                'data' => [
                    'type' => 'project',
                    'id' => 'milano',
                    'attributes' => [
                        'project_title' => 'Milano',
                        'project_note' => 'hidden',
                    ],
                ],
            ]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertSame('invalid_attribute', $errors[0]['code']);
            $this->assertSame('/data/attributes/project_note', $errors[0]['source']['pointer']);
        }
    }

    public function testCreateReturnsAllValidationErrorsAtOnce()
    {
        $definition = new EntityDefinition(
            'project',
            'gisclient_34',
            'project',
            'project_name',
            'string',
            ['project_name', 'project_title', 'max_extent_scale'],
            ['project_title', 'max_extent_scale'],
            ['project_name', 'project_title', 'max_extent_scale'],
            ['project_title', 'max_extent_scale'],
            ['project_name'],
            ['project_name'],
            'project_name',
            [
                'max_extent_scale' => [
                    'type' => 'numeric',
                ],
            ]
        );

        $service = $this->createServiceFromDefinition($definition, true);

        try {
            $service->createResource('project', [
                'data' => [
                    'type' => 'project',
                    'id' => 'milano',
                    'attributes' => [
                        'project_note' => 'hidden',
                        'project_title' => '',
                        'max_extent_scale' => 'A50000',
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

    public function testCreateRejectsDuplicatePrimaryKeyWithExplicitConflict()
    {
        $service = $this->createService(true, $repo, ['milano']);

        try {
            $service->createResource('project', [
                'data' => [
                    'type' => 'project',
                    'id' => 'milano',
                    'attributes' => [
                        'project_title' => 'Milano',
                    ],
                ],
            ]);
            $this->fail('Expected duplicate_primary_key ApiException');
        } catch (ApiException $exception) {
            $this->assertSame(409, $exception->getStatus());
            $this->assertSame('duplicate_primary_key', $exception->getErrorCode());
            $this->assertSame('/data/id', $exception->getSourcePointer());
        }
    }

    public function testBuildQueryOptionsAcceptsRelationshipFilterAlias()
    {
        $definition = new EntityDefinition(
            'theme',
            'gisclient_34',
            'theme',
            'theme_id',
            'int',
            ['theme_id', 'project_name', 'theme_name'],
            ['project_name', 'theme_name'],
            ['project_name', 'theme_name'],
            ['theme_name'],
            ['theme_id', 'project_name', 'theme_name'],
            ['theme_id', 'theme_name'],
            'theme_id',
            [],
            [],
            [
                'project' => [
                    'type' => 'project',
                    'local_key' => 'project_name',
                ],
            ]
        );

        $service = $this->createServiceFromDefinition($definition, true);
        $queryOptions = $service->buildQueryOptions($definition, [
            'filter' => [
                'project' => 'milano',
            ],
        ]);

        $this->assertSame([
            'project_name' => 'milano',
        ], $queryOptions->getFilters());
    }

    public function testScopedCreateInjectsScopeAndRendersParentRelationship()
    {
        $definition = new EntityDefinition(
            'project_srs',
            'gisclient_34',
            'project_srs',
            'srid',
            'int',
            ['srid', 'projparam', 'project_name'],
            ['srid', 'projparam', 'project_name'],
            ['srid', 'project_name'],
            [],
            ['srid', 'project_name'],
            ['srid'],
            'srid',
            [],
            ['project_name'],
            [
                'project' => [
                    'type' => 'project',
                    'local_key' => 'project_name',
                ],
            ],
            ['project']
        );

        $service = $this->createServiceFromDefinition($definition, true, $repo);
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

        $this->assertSame('default', $repo->createdAttributes['project_name']);
        $this->assertSame('3857', (string) $payload['data']['id']);
        $this->assertSame('default', $payload['data']['relationships']['project']['data']['id']);
        $this->assertArrayNotHasKey('project_name', $payload['data']['attributes']);
    }

    public function testScopedCreateRejectsMismatchedScopeAttribute()
    {
        $definition = new EntityDefinition(
            'project_srs',
            'gisclient_34',
            'project_srs',
            'srid',
            'int',
            ['srid', 'projparam', 'project_name'],
            ['srid', 'projparam', 'project_name'],
            ['srid', 'project_name'],
            [],
            ['srid', 'project_name'],
            ['srid'],
            'srid',
            [],
            ['project_name'],
            [
                'project' => [
                    'type' => 'project',
                    'local_key' => 'project_name',
                ],
            ],
            ['project']
        );

        $service = $this->createServiceFromDefinition($definition, true);

        try {
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
            $this->fail('Expected scope_attribute_mismatch ApiException');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->getStatus());
            $this->assertSame('scope_attribute_mismatch', $exception->getErrorCode());
            $this->assertSame('/data/attributes/project_name', $exception->getSourcePointer());
        }
    }

    public function testScopedGetUsesScopeFilter()
    {
        $definition = new EntityDefinition(
            'project_srs',
            'gisclient_34',
            'project_srs',
            'srid',
            'int',
            ['srid', 'projparam', 'project_name'],
            ['srid', 'projparam', 'project_name'],
            ['srid', 'project_name'],
            [],
            ['srid', 'project_name'],
            ['srid'],
            'srid',
            [],
            ['project_name']
        );

        $service = $this->createServiceFromDefinition($definition, true, $repo, ['default|3857']);

        $payload = $service->getResource('project_srs', 3857, [], [
            'project_name' => 'default',
        ]);
        $this->assertSame('3857', (string) $payload['data']['id']);

        try {
            $service->getResource('project_srs', 3857, [], [
                'project_name' => 'other',
            ]);
            $this->fail('Expected resource_not_found ApiException');
        } catch (ApiException $exception) {
            $this->assertSame(404, $exception->getStatus());
            $this->assertSame('resource_not_found', $exception->getErrorCode());
        }
    }

    public function testScopedCreateRequiresProjectRelationship()
    {
        $definition = new EntityDefinition(
            'project_srs',
            'gisclient_34',
            'project_srs',
            'srid',
            'int',
            ['srid', 'projparam', 'project_name'],
            ['srid', 'projparam', 'project_name'],
            ['srid', 'project_name'],
            [],
            ['srid', 'project_name'],
            ['srid'],
            'srid',
            [],
            ['project_name'],
            [
                'project' => [
                    'type' => 'project',
                    'local_key' => 'project_name',
                ],
            ],
            ['project']
        );

        $service = $this->createServiceFromDefinition($definition, true);

        try {
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
            $this->fail('Expected missing_required_relationship ApiException');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->getStatus());
            $this->assertSame('missing_required_relationship', $exception->getErrorCode());
            $this->assertSame('/data/relationships/project/data', $exception->getSourcePointer());
        }
    }

    public function testTopLevelCreateMapsProjectRelationshipToLocalKey()
    {
        $definition = new EntityDefinition(
            'theme',
            'gisclient_34',
            'theme',
            'theme_id',
            'int',
            ['theme_id', 'project_name', 'theme_name', 'theme_title', 'theme_order'],
            ['theme_name', 'theme_title', 'theme_order'],
            ['project_name', 'theme_name', 'theme_title', 'theme_order'],
            ['project_name', 'theme_name', 'theme_title', 'theme_order'],
            ['theme_id', 'project_name', 'theme_name', 'theme_title', 'theme_order'],
            ['theme_id', 'theme_order', 'theme_name'],
            'theme_order',
            [],
            [],
            [
                'project' => [
                    'type' => 'project',
                    'local_key' => 'project_name',
                ],
            ],
            ['project']
        );

        $service = $this->createServiceFromDefinition($definition, true, $repo);

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

        $this->assertSame('milano', $repo->createdAttributes['project_name']);
        $this->assertSame('milano', $payload['data']['relationships']['project']['data']['id']);
        $this->assertArrayNotHasKey('project_name', $payload['data']['attributes']);
    }

    public function testTopLevelCatalogCreateRequiresProjectRelationship()
    {
        $definition = new EntityDefinition(
            'catalog',
            'gisclient_34',
            'catalog',
            'catalog_id',
            'int',
            ['catalog_id', 'project_name', 'catalog_name', 'connection_type', 'catalog_path'],
            ['catalog_name', 'connection_type', 'catalog_path'],
            ['project_name', 'catalog_name', 'connection_type', 'catalog_path'],
            ['catalog_name', 'connection_type', 'catalog_path'],
            ['catalog_id', 'project_name', 'catalog_name'],
            ['catalog_id', 'catalog_name'],
            'catalog_name',
            [],
            [],
            [
                'project' => [
                    'type' => 'project',
                    'local_key' => 'project_name',
                ],
            ],
            ['project']
        );

        $service = $this->createServiceFromDefinition($definition, true);

        try {
            $service->createResource('catalog', [
                'data' => [
                    'type' => 'catalog',
                    'id' => '1',
                    'attributes' => [
                        'catalog_name' => 'osm',
                        'connection_type' => 1,
                        'catalog_path' => 'dbname=test',
                    ],
                ],
            ]);
            $this->fail('Expected missing_required_relationship ApiException');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->getStatus());
            $this->assertSame('missing_required_relationship', $exception->getErrorCode());
            $this->assertSame('/data/relationships/project/data', $exception->getSourcePointer());
        }
    }

    public function testTopLevelCatalogCreateMapsProjectRelationshipToLocalKey()
    {
        $definition = new EntityDefinition(
            'catalog',
            'gisclient_34',
            'catalog',
            'catalog_id',
            'int',
            ['catalog_id', 'project_name', 'catalog_name', 'connection_type', 'catalog_path'],
            ['catalog_name', 'connection_type', 'catalog_path'],
            ['project_name', 'catalog_name', 'connection_type', 'catalog_path'],
            ['catalog_name', 'connection_type', 'catalog_path'],
            ['catalog_id', 'project_name', 'catalog_name'],
            ['catalog_id', 'catalog_name'],
            'catalog_name',
            [],
            [],
            [
                'project' => [
                    'type' => 'project',
                    'local_key' => 'project_name',
                ],
            ],
            ['project']
        );

        $service = $this->createServiceFromDefinition($definition, true, $repo);
        $payload = $service->createResource('catalog', [
            'data' => [
                'type' => 'catalog',
                'id' => '1',
                'attributes' => [
                    'catalog_name' => 'osm',
                    'connection_type' => 1,
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

        $this->assertSame('milano', $repo->createdAttributes['project_name']);
        $this->assertSame('milano', $payload['data']['relationships']['project']['data']['id']);
        $this->assertArrayNotHasKey('project_name', $payload['data']['attributes']);
    }

    public function testTopLevelLayergroupCreateRequiresThemeRelationship()
    {
        $definition = new EntityDefinition(
            'layergroup',
            'gisclient_34',
            'layergroup',
            'layergroup_id',
            'int',
            ['layergroup_id', 'theme_id', 'layergroup_name', 'layergroup_title', 'layergroup_order', 'owstype_id', 'layers'],
            ['layergroup_name', 'layergroup_title', 'layergroup_order', 'owstype_id', 'layers'],
            ['theme_id', 'layergroup_name', 'layergroup_title', 'layergroup_order', 'owstype_id', 'layers'],
            ['layergroup_name', 'layergroup_title', 'layergroup_order', 'owstype_id', 'layers'],
            ['layergroup_id', 'theme_id', 'layergroup_name'],
            ['layergroup_id', 'layergroup_order', 'layergroup_name'],
            'layergroup_order',
            [],
            [],
            [
                'theme' => [
                    'type' => 'theme',
                    'local_key' => 'theme_id',
                ],
            ],
            ['theme']
        );

        $service = $this->createServiceFromDefinition($definition, true);

        try {
            $service->createResource('layergroup', [
                'data' => [
                    'type' => 'layergroup',
                    'id' => '11',
                    'attributes' => [
                        'layergroup_name' => 'base',
                        'layergroup_title' => 'Base',
                        'layergroup_order' => 1,
                        'owstype_id' => 1,
                        'layers' => 'osm',
                    ],
                ],
            ]);
            $this->fail('Expected missing_required_relationship ApiException');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->getStatus());
            $this->assertSame('missing_required_relationship', $exception->getErrorCode());
            $this->assertSame('/data/relationships/theme/data', $exception->getSourcePointer());
        }
    }

    public function testTopLevelLayergroupCreateMapsThemeRelationshipToLocalKey()
    {
        $definition = new EntityDefinition(
            'layergroup',
            'gisclient_34',
            'layergroup',
            'layergroup_id',
            'int',
            ['layergroup_id', 'theme_id', 'layergroup_name', 'layergroup_title', 'layergroup_order', 'owstype_id', 'layers'],
            ['layergroup_name', 'layergroup_title', 'layergroup_order', 'owstype_id', 'layers'],
            ['theme_id', 'layergroup_name', 'layergroup_title', 'layergroup_order', 'owstype_id', 'layers'],
            ['layergroup_name', 'layergroup_title', 'layergroup_order', 'owstype_id', 'layers'],
            ['layergroup_id', 'theme_id', 'layergroup_name'],
            ['layergroup_id', 'layergroup_order', 'layergroup_name'],
            'layergroup_order',
            [],
            [],
            [
                'theme' => [
                    'type' => 'theme',
                    'local_key' => 'theme_id',
                ],
            ],
            ['theme']
        );

        $service = $this->createServiceFromDefinition($definition, true, $repo);
        $payload = $service->createResource('layergroup', [
            'data' => [
                'type' => 'layergroup',
                'id' => '11',
                'attributes' => [
                    'layergroup_name' => 'base',
                    'layergroup_title' => 'Base',
                    'layergroup_order' => 1,
                    'owstype_id' => 1,
                    'layers' => 'osm',
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

        $this->assertSame('4', (string) $repo->createdAttributes['theme_id']);
        $this->assertSame('4', $payload['data']['relationships']['theme']['data']['id']);
        $this->assertArrayNotHasKey('theme_id', $payload['data']['attributes']);
    }

    public function testTopLevelLayerCreateRequiresLayergroupRelationship()
    {
        $definition = new EntityDefinition(
            'layer',
            'gisclient_34',
            'layer',
            'layer_id',
            'int',
            ['layer_id', 'layergroup_id', 'catalog_id', 'layer_name', 'layertype_id', 'layer_order', 'layer_title'],
            ['layer_name', 'layertype_id', 'layer_order', 'layer_title'],
            ['layergroup_id', 'catalog_id', 'layer_name', 'layertype_id'],
            ['layergroup_id', 'catalog_id', 'layer_name', 'layertype_id'],
            ['layer_id', 'layergroup_id', 'catalog_id', 'layer_name'],
            ['layer_id', 'layer_order', 'layer_name'],
            'layer_order',
            [],
            [],
            [
                'layergroup' => [
                    'type' => 'layergroup',
                    'local_key' => 'layergroup_id',
                ],
                'catalog' => [
                    'type' => 'catalog',
                    'local_key' => 'catalog_id',
                ],
            ],
            ['layergroup', 'catalog']
        );

        $service = $this->createServiceFromDefinition($definition, true);

        try {
            $service->createResource('layer', [
                'data' => [
                    'type' => 'layer',
                    'id' => '21',
                    'attributes' => [
                        'layer_name' => 'roads',
                        'layertype_id' => 2,
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
            $this->fail('Expected missing_required_relationship ApiException');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->getStatus());
            $this->assertSame('missing_required_relationship', $exception->getErrorCode());
            $this->assertSame('/data/relationships/layergroup/data', $exception->getSourcePointer());
        }
    }

    public function testTopLevelLayerCreateRequiresCatalogRelationship()
    {
        $definition = new EntityDefinition(
            'layer',
            'gisclient_34',
            'layer',
            'layer_id',
            'int',
            ['layer_id', 'layergroup_id', 'catalog_id', 'layer_name', 'layertype_id', 'layer_order', 'layer_title'],
            ['layer_name', 'layertype_id', 'layer_order', 'layer_title'],
            ['layergroup_id', 'catalog_id', 'layer_name', 'layertype_id'],
            ['layergroup_id', 'catalog_id', 'layer_name', 'layertype_id'],
            ['layer_id', 'layergroup_id', 'catalog_id', 'layer_name'],
            ['layer_id', 'layer_order', 'layer_name'],
            'layer_order',
            [],
            [],
            [
                'layergroup' => [
                    'type' => 'layergroup',
                    'local_key' => 'layergroup_id',
                ],
                'catalog' => [
                    'type' => 'catalog',
                    'local_key' => 'catalog_id',
                ],
            ],
            ['layergroup', 'catalog']
        );

        $service = $this->createServiceFromDefinition($definition, true);

        try {
            $service->createResource('layer', [
                'data' => [
                    'type' => 'layer',
                    'id' => '21',
                    'attributes' => [
                        'layer_name' => 'roads',
                        'layertype_id' => 2,
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
            $this->fail('Expected missing_required_relationship ApiException');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->getStatus());
            $this->assertSame('missing_required_relationship', $exception->getErrorCode());
            $this->assertSame('/data/relationships/catalog/data', $exception->getSourcePointer());
        }
    }

    public function testTopLevelLayerCreateMapsParentRelationshipsToLocalKeys()
    {
        $definition = new EntityDefinition(
            'layer',
            'gisclient_34',
            'layer',
            'layer_id',
            'int',
            ['layer_id', 'layergroup_id', 'catalog_id', 'layer_name', 'layertype_id', 'layer_order', 'layer_title'],
            ['layer_name', 'layertype_id', 'layer_order', 'layer_title'],
            ['layergroup_id', 'catalog_id', 'layer_name', 'layertype_id'],
            ['layergroup_id', 'catalog_id', 'layer_name', 'layertype_id'],
            ['layer_id', 'layergroup_id', 'catalog_id', 'layer_name'],
            ['layer_id', 'layer_order', 'layer_name'],
            'layer_order',
            [],
            [],
            [
                'layergroup' => [
                    'type' => 'layergroup',
                    'local_key' => 'layergroup_id',
                ],
                'catalog' => [
                    'type' => 'catalog',
                    'local_key' => 'catalog_id',
                ],
            ],
            ['layergroup', 'catalog']
        );

        $service = $this->createServiceFromDefinition($definition, true, $repo);
        $payload = $service->createResource('layer', [
            'data' => [
                'type' => 'layer',
                'id' => '21',
                'attributes' => [
                    'layer_name' => 'roads',
                    'layertype_id' => 2,
                    'layer_order' => 1,
                    'layer_title' => 'Roads',
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

        $this->assertSame('5', (string) $repo->createdAttributes['layergroup_id']);
        $this->assertSame('10', (string) $repo->createdAttributes['catalog_id']);
        $this->assertSame('5', $payload['data']['relationships']['layergroup']['data']['id']);
        $this->assertSame('10', $payload['data']['relationships']['catalog']['data']['id']);
        $this->assertArrayNotHasKey('layergroup_id', $payload['data']['attributes']);
        $this->assertArrayNotHasKey('catalog_id', $payload['data']['attributes']);
    }

    public function testBuildQueryOptionsAcceptsLayerRelationshipFilterAliases()
    {
        $definition = new EntityDefinition(
            'layer',
            'gisclient_34',
            'layer',
            'layer_id',
            'int',
            ['layer_id', 'layergroup_id', 'catalog_id', 'layer_name'],
            ['layer_name'],
            ['layergroup_id', 'catalog_id', 'layer_name'],
            ['layergroup_id', 'catalog_id', 'layer_name'],
            ['layer_id', 'layergroup_id', 'catalog_id', 'layer_name'],
            ['layer_id', 'layer_name'],
            'layer_id',
            [],
            [],
            [
                'layergroup' => [
                    'type' => 'layergroup',
                    'local_key' => 'layergroup_id',
                ],
                'catalog' => [
                    'type' => 'catalog',
                    'local_key' => 'catalog_id',
                ],
            ]
        );

        $service = $this->createServiceFromDefinition($definition, true);
        $queryOptions = $service->buildQueryOptions($definition, [
            'filter' => [
                'layergroup' => '5',
                'catalog' => '10',
            ],
        ]);

        $this->assertSame([
            'layergroup_id' => '5',
            'catalog_id' => '10',
        ], $queryOptions->getFilters());
    }

    public function testTopLevelLayerUpdateAcceptsRelationshipIdsWhenAttributesAreNullOnPut()
    {
        $definition = new EntityDefinition(
            'layer',
            'gisclient_34',
            'layer',
            'layer_id',
            'int',
            ['layer_id', 'layergroup_id', 'catalog_id', 'layer_name', 'layertype_id', 'layer_title'],
            ['catalog_id', 'layer_name', 'layertype_id', 'layer_title'],
            ['catalog_id', 'layer_name', 'layertype_id'],
            ['catalog_id', 'layer_name', 'layertype_id'],
            ['layer_id', 'catalog_id', 'layer_name'],
            ['layer_id', 'layer_name'],
            'layer_id',
            [],
            [],
            [
                'catalog' => [
                    'type' => 'catalog',
                    'local_key' => 'catalog_id',
                ],
                'layergroup' => [
                    'type' => 'layergroup',
                    'local_key' => 'layergroup_id',
                ],
            ],
            ['catalog', 'layergroup']
        );

        $repo = new class() implements AuthorEntityRepositoryInterface {
            public function findAll(EntityDefinition $definition, QueryOptions $queryOptions, array $scopeFilters = [])
            {
                return new PagedResult([], 0, 50, 0);
            }

            public function findById(EntityDefinition $definition, $id, array $scopeFilters = [])
            {
                return [
                    'layer_id' => (int) $id,
                    'layergroup_id' => 1,
                    'catalog_id' => 1,
                    'layer_name' => 'buildings',
                    'layertype_id' => 3,
                    'layer_title' => 'Buildings',
                ];
            }

            public function create(EntityDefinition $definition, array $attributes)
            {
                return $attributes;
            }

            public function update(EntityDefinition $definition, $id, array $attributes, array $scopeFilters = [])
            {
                return array_merge([
                    'layer_id' => (int) $id,
                ], $attributes);
            }

            public function delete(EntityDefinition $definition, $id, array $scopeFilters = [])
            {
            }
        };

        $service = $this->createServiceWithRepository($definition, $repo, true);
        $payload = $service->updateResource('layer', 2, [
            'data' => [
                'type' => 'layer',
                'attributes' => [
                    'layer_name' => 'buildings',
                    'layer_title' => 'buildings UPDATE',
                    'layertype_id' => 3,
                ],
                'relationships' => [
                    'layergroup' => [
                        'data' => [
                            'type' => 'layergroup',
                            'id' => '2',
                        ],
                    ],
                    'catalog' => [
                        'data' => [
                            'type' => 'catalog',
                            'id' => '2',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame('2', $payload['data']['id']);
        $this->assertSame('2', $payload['data']['relationships']['layergroup']['data']['id']);
        $this->assertSame('2', $payload['data']['relationships']['catalog']['data']['id']);
    }

    public function testTopLevelClassCreateRequiresLayerRelationship()
    {
        $definition = new EntityDefinition(
            'class',
            'gisclient_34',
            'class',
            'class_id',
            'int',
            ['class_id', 'layer_id', 'class_name', 'class_title', 'class_order'],
            ['class_name', 'class_title', 'class_order'],
            ['class_name'],
            ['class_name'],
            ['class_id', 'layer_id', 'class_name', 'class_title'],
            ['class_id', 'class_order', 'class_name'],
            'class_order',
            [],
            [],
            [
                'layer' => [
                    'type' => 'layer',
                    'local_key' => 'layer_id',
                ],
            ],
            ['layer']
        );

        $service = $this->createServiceFromDefinition($definition, true);

        try {
            $service->createResource('class', [
                'data' => [
                    'type' => 'class',
                    'id' => '31',
                    'attributes' => [
                        'class_name' => 'buildings',
                        'class_title' => 'Buildings',
                    ],
                ],
            ]);
            $this->fail('Expected missing_required_relationship ApiException');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->getStatus());
            $this->assertSame('missing_required_relationship', $exception->getErrorCode());
            $this->assertSame('/data/relationships/layer/data', $exception->getSourcePointer());
        }
    }

    public function testTopLevelClassCreateMapsLayerRelationshipToLocalKey()
    {
        $definition = new EntityDefinition(
            'class',
            'gisclient_34',
            'class',
            'class_id',
            'int',
            ['class_id', 'layer_id', 'class_name', 'class_title', 'class_order'],
            ['class_name', 'class_title', 'class_order'],
            ['class_name'],
            ['class_name'],
            ['class_id', 'layer_id', 'class_name', 'class_title'],
            ['class_id', 'class_order', 'class_name'],
            'class_order',
            [],
            [],
            [
                'layer' => [
                    'type' => 'layer',
                    'local_key' => 'layer_id',
                ],
            ],
            ['layer']
        );

        $service = $this->createServiceFromDefinition($definition, true, $repo);
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

        $this->assertSame('2', (string) $repo->createdAttributes['layer_id']);
        $this->assertSame('2', $payload['data']['relationships']['layer']['data']['id']);
        $this->assertArrayNotHasKey('layer_id', $payload['data']['attributes']);
    }

    public function testBuildQueryOptionsAcceptsClassRelationshipFilterAlias()
    {
        $definition = new EntityDefinition(
            'class',
            'gisclient_34',
            'class',
            'class_id',
            'int',
            ['class_id', 'layer_id', 'class_name'],
            ['class_name'],
            ['class_name'],
            ['class_name'],
            ['class_id', 'layer_id', 'class_name'],
            ['class_id', 'class_name'],
            'class_id',
            [],
            [],
            [
                'layer' => [
                    'type' => 'layer',
                    'local_key' => 'layer_id',
                ],
            ]
        );

        $service = $this->createServiceFromDefinition($definition, true);
        $queryOptions = $service->buildQueryOptions($definition, [
            'filter' => [
                'layer' => '2',
            ],
        ]);

        $this->assertSame([
            'layer_id' => '2',
        ], $queryOptions->getFilters());
    }

    public function testTopLevelStyleCreateRequiresClassRelationship()
    {
        $definition = new EntityDefinition(
            'style',
            'gisclient_34',
            'style',
            'style_id',
            'int',
            ['style_id', 'class_id', 'style_name', 'style_order'],
            ['style_name', 'style_order'],
            ['class_id', 'style_name'],
            ['class_id', 'style_name'],
            ['style_id', 'class_id', 'style_name'],
            ['style_id', 'style_order', 'style_name'],
            'style_order',
            [],
            [],
            [
                'class' => [
                    'type' => 'class',
                    'local_key' => 'class_id',
                ],
            ],
            ['class']
        );

        $service = $this->createServiceFromDefinition($definition, true);

        try {
            $service->createResource('style', [
                'data' => [
                    'type' => 'style',
                    'id' => '41',
                    'attributes' => [
                        'style_name' => 'default',
                        'style_order' => 1,
                    ],
                ],
            ]);
            $this->fail('Expected missing_required_relationship ApiException');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->getStatus());
            $this->assertSame('missing_required_relationship', $exception->getErrorCode());
            $this->assertSame('/data/relationships/class/data', $exception->getSourcePointer());
        }
    }

    public function testTopLevelStyleCreateMapsClassRelationshipToLocalKey()
    {
        $definition = new EntityDefinition(
            'style',
            'gisclient_34',
            'style',
            'style_id',
            'int',
            ['style_id', 'class_id', 'style_name', 'style_order'],
            ['style_name', 'style_order'],
            ['class_id', 'style_name'],
            ['class_id', 'style_name'],
            ['style_id', 'class_id', 'style_name'],
            ['style_id', 'style_order', 'style_name'],
            'style_order',
            [],
            [],
            [
                'class' => [
                    'type' => 'class',
                    'local_key' => 'class_id',
                ],
            ],
            ['class']
        );

        $service = $this->createServiceFromDefinition($definition, true, $repo);
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

        $this->assertSame('3', (string) $repo->createdAttributes['class_id']);
        $this->assertSame('3', $payload['data']['relationships']['class']['data']['id']);
        $this->assertArrayNotHasKey('class_id', $payload['data']['attributes']);
    }

    public function testBuildQueryOptionsAcceptsStyleRelationshipFilterAlias()
    {
        $definition = new EntityDefinition(
            'style',
            'gisclient_34',
            'style',
            'style_id',
            'int',
            ['style_id', 'class_id', 'style_name'],
            ['style_name'],
            ['class_id', 'style_name'],
            ['class_id', 'style_name'],
            ['style_id', 'class_id', 'style_name'],
            ['style_id', 'style_name'],
            'style_id',
            [],
            [],
            [
                'class' => [
                    'type' => 'class',
                    'local_key' => 'class_id',
                ],
            ]
        );

        $service = $this->createServiceFromDefinition($definition, true);
        $queryOptions = $service->buildQueryOptions($definition, [
            'filter' => [
                'class' => '3',
            ],
        ]);

        $this->assertSame([
            'class_id' => '3',
        ], $queryOptions->getFilters());
    }

    public function testTopLevelFieldCreateRequiresLayerRelationship()
    {
        $definition = new EntityDefinition(
            'field',
            'gisclient_34',
            'field',
            'field_id',
            'int',
            ['field_id', 'layer_id', 'relation_id', 'field_name', 'field_header', 'field_order'],
            ['field_name', 'field_header', 'field_order'],
            ['field_name', 'field_header'],
            ['field_name', 'field_header'],
            ['field_id', 'layer_id', 'relation_id', 'field_name', 'field_header'],
            ['field_id', 'field_order', 'field_name'],
            'field_order',
            [],
            [],
            [
                'layer' => [
                    'type' => 'layer',
                    'local_key' => 'layer_id',
                ],
            ],
            ['layer']
        );

        $service = $this->createServiceFromDefinition($definition, true);

        try {
            $service->createResource('field', [
                'data' => [
                    'type' => 'field',
                    'id' => '51',
                    'attributes' => [
                        'field_name' => 'gid',
                        'field_header' => 'GID',
                        'field_order' => 1,
                    ],
                ],
            ]);
            $this->fail('Expected missing_required_relationship ApiException');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->getStatus());
            $this->assertSame('missing_required_relationship', $exception->getErrorCode());
            $this->assertSame('/data/relationships/layer/data', $exception->getSourcePointer());
        }
    }

    public function testTopLevelFieldCreateMapsLayerRelationshipToLocalKey()
    {
        $definition = new EntityDefinition(
            'field',
            'gisclient_34',
            'field',
            'field_id',
            'int',
            ['field_id', 'layer_id', 'relation_id', 'field_name', 'field_header', 'field_order'],
            ['field_name', 'field_header', 'field_order'],
            ['field_name', 'field_header'],
            ['field_name', 'field_header'],
            ['field_id', 'layer_id', 'relation_id', 'field_name', 'field_header'],
            ['field_id', 'field_order', 'field_name'],
            'field_order',
            [],
            [],
            [
                'layer' => [
                    'type' => 'layer',
                    'local_key' => 'layer_id',
                ],
            ],
            ['layer']
        );

        $service = $this->createServiceFromDefinition($definition, true, $repo);
        $payload = $service->createResource('field', [
            'data' => [
                'type' => 'field',
                'id' => '51',
                'attributes' => [
                    'field_name' => 'gid',
                    'field_header' => 'GID',
                    'field_order' => 1,
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

        $this->assertSame('2', (string) $repo->createdAttributes['layer_id']);
        $this->assertSame('2', $payload['data']['relationships']['layer']['data']['id']);
        $this->assertArrayNotHasKey('layer_id', $payload['data']['attributes']);
    }

    public function testBuildQueryOptionsAcceptsFieldRelationshipFilterAlias()
    {
        $definition = new EntityDefinition(
            'field',
            'gisclient_34',
            'field',
            'field_id',
            'int',
            ['field_id', 'layer_id', 'relation_id', 'field_name'],
            ['field_name'],
            ['field_name'],
            ['field_name'],
            ['field_id', 'layer_id', 'relation_id', 'field_name'],
            ['field_id', 'field_name'],
            'field_id',
            [],
            [],
            [
                'layer' => [
                    'type' => 'layer',
                    'local_key' => 'layer_id',
                ],
            ]
        );

        $service = $this->createServiceFromDefinition($definition, true);
        $queryOptions = $service->buildQueryOptions($definition, [
            'filter' => [
                'layer' => '2',
            ],
        ]);

        $this->assertSame([
            'layer_id' => '2',
        ], $queryOptions->getFilters());
    }

    public function testGetFieldResourceKeepsRelationIdAsAttribute()
    {
        $definition = new EntityDefinition(
            'field',
            'gisclient_34',
            'field',
            'field_id',
            'int',
            ['field_id', 'layer_id', 'relation_id', 'field_name', 'field_header'],
            ['field_name', 'field_header'],
            ['field_name', 'field_header'],
            ['field_name', 'field_header'],
            ['field_id', 'layer_id', 'relation_id', 'field_name', 'field_header'],
            ['field_id', 'field_name'],
            'field_id',
            [],
            [],
            [
                'layer' => [
                    'type' => 'layer',
                    'local_key' => 'layer_id',
                ],
            ]
        );

        $repo = new class() implements AuthorEntityRepositoryInterface {
            public function findAll(EntityDefinition $definition, QueryOptions $queryOptions, array $scopeFilters = [])
            {
                return new PagedResult([], 0, 50, 0);
            }

            public function findById(EntityDefinition $definition, $id, array $scopeFilters = [])
            {
                return [
                    'field_id' => (int) $id,
                    'layer_id' => 2,
                    'relation_id' => 0,
                    'field_name' => 'gid',
                    'field_header' => 'GID',
                ];
            }

            public function create(EntityDefinition $definition, array $attributes)
            {
                return $attributes;
            }

            public function update(EntityDefinition $definition, $id, array $attributes, array $scopeFilters = [])
            {
                return $attributes;
            }

            public function delete(EntityDefinition $definition, $id, array $scopeFilters = [])
            {
            }
        };

        $service = $this->createServiceWithRepository($definition, $repo, true);
        $payload = $service->getResource('field', 51);

        $this->assertSame('2', $payload['data']['relationships']['layer']['data']['id']);
        $this->assertSame(0, $payload['data']['attributes']['relation_id']);
    }

    public function testGetResourceCastsNumericAttributesFromDatabaseStrings()
    {
        $definition = new EntityDefinition(
            'theme',
            'gisclient_34',
            'theme',
            'theme_id',
            'int',
            ['theme_id', 'project_name', 'theme_name', 'theme_single', 'radio'],
            ['theme_name', 'theme_single', 'radio'],
            ['project_name', 'theme_name'],
            ['theme_name'],
            ['theme_id', 'project_name', 'theme_name'],
            ['theme_id', 'theme_name'],
            'theme_id',
            [
                'theme_id' => [
                    'type' => 'integer',
                ],
                'theme_single' => [
                    'type' => 'numeric',
                ],
                'radio' => [
                    'type' => 'numeric',
                ],
            ],
            [],
            [
                'project' => [
                    'type' => 'project',
                    'local_key' => 'project_name',
                ],
            ]
        );

        $repo = new class() implements AuthorEntityRepositoryInterface {
            public function findAll(EntityDefinition $definition, QueryOptions $queryOptions, array $scopeFilters = [])
            {
                return new PagedResult([], 0, 50, 0);
            }

            public function findById(EntityDefinition $definition, $id, array $scopeFilters = [])
            {
                return [
                    'theme_id' => '3',
                    'project_name' => 'milano',
                    'theme_name' => 'boundaries_places3',
                    'theme_single' => '0',
                    'radio' => '1',
                ];
            }

            public function create(EntityDefinition $definition, array $attributes)
            {
                return $attributes;
            }

            public function update(EntityDefinition $definition, $id, array $attributes, array $scopeFilters = [])
            {
                return $attributes;
            }

            public function delete(EntityDefinition $definition, $id, array $scopeFilters = [])
            {
            }
        };

        $service = $this->createServiceWithRepository($definition, $repo, true);
        $payload = $service->getResource('theme', '3');

        $this->assertSame('3', $payload['data']['id']);
        $this->assertSame(0, $payload['data']['attributes']['theme_single']);
        $this->assertSame(1, $payload['data']['attributes']['radio']);
        $this->assertSame('milano', $payload['data']['relationships']['project']['data']['id']);
    }

    private function createService($isAdmin, &$repo = null, array $existingIds = [])
    {
        $definition = new EntityDefinition(
            'project',
            'gisclient_34',
            'project',
            'project_name',
            'string',
            ['project_name', 'project_title', 'project_note'],
            ['project_name', 'project_title', 'project_note'],
            ['project_name', 'project_title'],
            ['project_title'],
            ['project_name'],
            ['project_name'],
            'project_name'
        );

        return $this->createServiceFromDefinition($definition, $isAdmin, $repo, $existingIds);
    }

    private function createServiceFromDefinition(EntityDefinition $definition, $isAdmin, &$repo = null, array $existingIds = [])
    {
        $provider = new class($definition) implements EntityDefinitionProviderInterface {
            private $definition;

            public function __construct(EntityDefinition $definition)
            {
                $this->definition = $definition;
            }
            public function getEntityDefinition($entity)
            {
                return $this->definition;
            }
        };

        $repo = new class($existingIds) implements AuthorEntityRepositoryInterface {
            public $createdAttributes = [];
            private $existingIds = [];

            public function __construct(array $existingIds)
            {
                $this->existingIds = array_fill_keys($existingIds, true);
            }

            public function findAll(EntityDefinition $definition, QueryOptions $queryOptions, array $scopeFilters = [])
            {
                return new PagedResult([], 0, 50, 0);
            }

            public function findById(EntityDefinition $definition, $id, array $scopeFilters = [])
            {
                $scopeKey = isset($scopeFilters['project_name']) ? (string) $scopeFilters['project_name'] : null;
                $key = $scopeKey !== null ? ($scopeKey . '|' . (string) $id) : (string) $id;
                if (!isset($this->existingIds[$key])) {
                    return null;
                }
                return [
                    'srid' => (int) $id,
                    'project_name' => $scopeKey ?? (string) $id,
                    'project_title' => 'Project',
                ];
            }

            public function create(EntityDefinition $definition, array $attributes)
            {
                $this->createdAttributes = $attributes;
                if (isset($attributes['srid'])) {
                    if (isset($attributes['project_name'])) {
                        $this->existingIds[(string) $attributes['project_name'] . '|' . (string) $attributes['srid']] = true;
                    } else {
                        $this->existingIds[(string) $attributes['srid']] = true;
                    }
                } elseif (isset($attributes['project_name'])) {
                    $this->existingIds[(string) $attributes['project_name']] = true;
                }
                return $attributes;
            }

            public function update(EntityDefinition $definition, $id, array $attributes, array $scopeFilters = [])
            {
                return array_merge([
                    'project_name' => isset($scopeFilters['project_name']) ? (string) $scopeFilters['project_name'] : (string) $id,
                    'srid' => (int) $id,
                ], $attributes);
            }

            public function delete(EntityDefinition $definition, $id, array $scopeFilters = [])
            {
            }
        };

        return new class($provider, $repo, $isAdmin) extends ApiCrudService {
            private $isAdmin;

            public function __construct(
                EntityDefinitionProviderInterface $provider,
                AuthorEntityRepositoryInterface $repository,
                $isAdmin
            ) {
                parent::__construct($provider, $repository, new PayloadValidator());
                $this->isAdmin = $isAdmin;
            }

            protected function assertAdmin()
            {
                if ($this->isAdmin) {
                    return;
                }

                throw new ApiException(403, 'admin_required', 'Forbidden', 'Administrator permissions are required');
            }
        };
    }

    private function createServiceWithRepository(EntityDefinition $definition, AuthorEntityRepositoryInterface $repository, $isAdmin)
    {
        $provider = new class($definition) implements EntityDefinitionProviderInterface {
            private $definition;

            public function __construct(EntityDefinition $definition)
            {
                $this->definition = $definition;
            }
            public function getEntityDefinition($entity)
            {
                return $this->definition;
            }
        };

        return new class($provider, $repository, $isAdmin) extends ApiCrudService {
            private $isAdmin;

            public function __construct(
                EntityDefinitionProviderInterface $provider,
                AuthorEntityRepositoryInterface $repository,
                $isAdmin
            ) {
                parent::__construct($provider, $repository, new PayloadValidator());
                $this->isAdmin = $isAdmin;
            }

            protected function assertAdmin()
            {
                if ($this->isAdmin) {
                    return;
                }

                throw new ApiException(403, 'admin_required', 'Forbidden', 'Administrator permissions are required');
            }
        };
    }

    private function assertAdminRequired(callable $callable)
    {
        try {
            $callable();
            $this->fail('Expected admin_required ApiException');
        } catch (ApiException $exception) {
            $this->assertSame(403, $exception->getStatus());
            $this->assertSame('admin_required', $exception->getErrorCode());
        }
    }
}
