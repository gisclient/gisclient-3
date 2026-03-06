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
