<?php

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Mapper\JsonApiExceptionMapper;
use GisClient\Author\Api\Serializer\JsonApiSerializer;
use GisClient\Author\Controller\JsonApiController;
use GisClient\Author\Persistence\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JsonApiControllerTest extends TestCase
{
    /**
     * @var mixed
     */
    private $originalAuthenticationHandler;

    protected function setUp(): void
    {
        $this->originalAuthenticationHandler = $this->getAuthenticationHandler();
    }

    protected function tearDown(): void
    {
        $this->setAuthenticationHandler($this->originalAuthenticationHandler);
    }

    public function testIndexActionReturnsAuthenticationRequiredWhenUnauthenticated()
    {
        $service = new class() {
            public $listCalled = false;

            public function listResources($entity, array $query)
            {
                $this->listCalled = true;
                return null;
            }
        };

        $controller = $this->createController($service);
        $this->setAuthenticationHandler($this->createAuthHandler(false, false));

        $response = $controller->indexAction('project', Request::create('/api/project', 'GET'));
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame('authentication_required', $payload['errors'][0]['code']);
        $this->assertFalse($service->listCalled);
    }

    public function testCreateActionReturnsAdminRequiredWhenAuthenticatedNonAdmin()
    {
        $service = new class() {
            public $createCalled = false;

            public function createResource($entity, JsonApiDto $payload)
            {
                $this->createCalled = true;
                return null;
            }
        };

        $controller = $this->createController($service);
        $this->setAuthenticationHandler($this->createAuthHandler(true, false));

        $request = Request::create(
            '/api/project',
            'POST',
            [],
            [],
            [],
            [],
            json_encode([
                'data' => [
                    'type' => 'project',
                    'attributes' => [
                        'project_title' => 'Milano',
                    ],
                ],
            ])
        );

        $response = $controller->createAction('project', $request);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertSame('admin_required', $payload['errors'][0]['code']);
        $this->assertFalse($service->createCalled);
    }

    public function testCreateActionDelegatesToCrudServiceWhenAdmin()
    {
        $service = new class() {
            public $captured = [];

            public function createResource($entity, JsonApiDto $payload)
            {
                $this->captured = [
                    'entity' => $entity,
                    'payload' => $payload,
                ];

                $dto = new \GisClient\Author\Api\Dto\ProjectDto();
                $dto->id = 'milano';
                $dto->projectTitle = 'Milano';
                $dto->markPresent('id');
                $dto->markPresent('project_title');

                return $dto;
            }
        };

        $controller = $this->createController($service);
        $this->setAuthenticationHandler($this->createAuthHandler(true, true));

        $request = Request::create(
            '/api/project',
            'POST',
            [],
            [],
            [],
            [],
            json_encode([
                'data' => [
                    'type' => 'project',
                    'id' => 'milano',
                    'attributes' => [
                        'project_title' => 'Milano',
                    ],
                ],
            ])
        );

        $response = $controller->createAction('project', $request);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame('project', $service->captured['entity']);
        $this->assertSame('milano', $service->captured['payload']->getId());
        $this->assertSame('milano', $payload['data']['id']);
        $this->assertSame('Milano', $payload['data']['attributes']['project_title']);
    }

    public function testCreateActionReturnsBadRequestForWrongAttributeTypes()
    {
        $service = new class() {
            public $createCalled = false;

            public function createResource($entity, JsonApiDto $payload)
            {
                $this->createCalled = true;
                return null;
            }
        };

        $controller = $this->createController($service);
        $this->setAuthenticationHandler($this->createAuthHandler(true, true));

        $request = Request::create(
            '/api/project',
            'POST',
            [],
            [],
            [],
            [],
            json_encode([
                'data' => [
                    'type' => 'project',
                    'id' => 'milano',
                    'attributes' => [
                        'project_title' => 'Milano',
                        'xc' => '501090',
                        'yc' => '5022596',
                        'project_srid' => '32632',
                        'max_extent_scale' => '50000',
                        'charset_encodings_id' => 2,
                        'default_language_id' => 'it',
                    ],
                ],
            ])
        );

        $response = $controller->createAction('project', $request);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertCount(4, $payload['errors']);
        $this->assertSame('invalid_attribute_type', $payload['errors'][0]['code']);
        $this->assertSame('/data/attributes/xc', $payload['errors'][0]['source']['pointer']);
        $this->assertSame('/data/attributes/yc', $payload['errors'][1]['source']['pointer']);
        $this->assertSame('/data/attributes/project_srid', $payload['errors'][2]['source']['pointer']);
        $this->assertSame('/data/attributes/max_extent_scale', $payload['errors'][3]['source']['pointer']);
        $this->assertFalse($service->createCalled);
    }

    public function testCreateActionMapsPersistenceExceptionToJsonApiError()
    {
        $service = new class() {
            public function createResource($entity, JsonApiDto $payload)
            {
                throw new UniqueConstraintViolationException('duplicate');
            }
        };

        $controller = $this->createController($service);
        $this->setAuthenticationHandler($this->createAuthHandler(true, true));

        $request = Request::create(
            '/api/project',
            'POST',
            [],
            [],
            [],
            [],
            json_encode([
                'data' => [
                    'type' => 'project',
                    'attributes' => [
                        'project_title' => 'Milano',
                    ],
                ],
            ])
        );

        $response = $controller->createAction('project', $request);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertSame('unique_constraint_violation', $payload['errors'][0]['code']);
        $this->assertSame('Duplicate value violates unique constraint', $payload['errors'][0]['detail']);
    }

    private function createController($service)
    {
        $container = new Container();
        $container->set(\GisClient\Author\Api\Service\ApiCrudService::class, $service);
        $container->set(JsonApiExceptionMapper::class, new JsonApiExceptionMapper());
        $container->set(JsonApiSerializer::class, new JsonApiSerializer());

        $controller = new JsonApiController();
        $controller->setContainer($container);

        return $controller;
    }

    private function createAuthHandler($isAuthenticated, $isAdmin)
    {
        return new class($isAuthenticated, $isAdmin) {
            private $isAuthenticated;
            private $isAdmin;

            public function __construct($isAuthenticated, $isAdmin)
            {
                $this->isAuthenticated = $isAuthenticated;
                $this->isAdmin = $isAdmin;
            }

            public function isAuthenticated()
            {
                return $this->isAuthenticated;
            }

            public function isAdmin()
            {
                return $this->isAdmin;
            }
        };
    }

    private function getAuthenticationHandler()
    {
        $property = new ReflectionProperty('GCApp', 'authenticationHandler');
        $property->setAccessible(true);

        return $property->getValue();
    }

    private function setAuthenticationHandler($handler)
    {
        $property = new ReflectionProperty('GCApp', 'authenticationHandler');
        $property->setAccessible(true);
        $property->setValue(null, $handler);
    }
}
