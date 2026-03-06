<?php

use GisClient\Author\Api\Contract\AuthorEntityRepositoryInterface;
use GisClient\Author\Api\Contract\EntityDefinitionProviderInterface;
use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Model\EntityDefinition;
use GisClient\Author\Api\Model\PagedResult;
use GisClient\Author\Api\Model\QueryOptions;
use GisClient\Author\Api\Service\ApiCrudService;
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

    private function createService($isAdmin, &$repo = null)
    {
        $definition = new EntityDefinition(
            'project',
            'gisclient_34',
            'project',
            'project_name',
            'string',
            ['project_name', 'project_title'],
            ['project_name', 'project_title'],
            ['project_name', 'project_title'],
            ['project_title'],
            ['project_name'],
            ['project_name'],
            'project_name'
        );

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

        $repo = new class() implements AuthorEntityRepositoryInterface {
            public $createdAttributes = [];

            public function findAll(EntityDefinition $definition, QueryOptions $queryOptions)
            {
                return new PagedResult([], 0, 50, 0);
            }

            public function findById(EntityDefinition $definition, $id)
            {
                return [
                    'project_name' => (string) $id,
                    'project_title' => 'Project',
                ];
            }

            public function create(EntityDefinition $definition, array $attributes)
            {
                $this->createdAttributes = $attributes;
                return $attributes;
            }

            public function update(EntityDefinition $definition, $id, array $attributes)
            {
                return array_merge([
                    'project_name' => (string) $id,
                ], $attributes);
            }

            public function delete(EntityDefinition $definition, $id)
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
                parent::__construct($provider, $repository);
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
