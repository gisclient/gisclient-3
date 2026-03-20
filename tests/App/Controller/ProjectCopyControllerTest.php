<?php

use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Mapper\JsonApiExceptionMapper;
use GisClient\Author\Api\ProjectCopy\ProjectCopyRequest;
use GisClient\Author\Api\ProjectCopy\ProjectCopyRequestParser;
use GisClient\Author\Api\ProjectCopy\ProjectCopyResponse;
use GisClient\Author\Controller\ProjectCopyController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProjectCopyControllerTest extends TestCase
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

    public function testCopyActionReturnsAuthenticationRequiredWhenUnauthenticated()
    {
        $service = new class() {
            public $called = false;

            public function execute(ProjectCopyRequest $request)
            {
                $this->called = true;
                return null;
            }
        };

        $controller = $this->createController($service);
        $this->setAuthenticationHandler($this->createAuthHandler(false, false));

        $response = $controller->copyAction(Request::create('/api/project-copy', 'POST', [], [], [], [], '{}'));
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame('authentication_required', $payload['errors'][0]['code']);
        $this->assertFalse($service->called);
    }

    public function testCopyActionReturnsAdminRequiredWhenAuthenticatedNonAdmin()
    {
        $service = new class() {
            public $called = false;

            public function execute(ProjectCopyRequest $request)
            {
                $this->called = true;
                return null;
            }
        };

        $controller = $this->createController($service);
        $this->setAuthenticationHandler($this->createAuthHandler(true, false));

        $response = $controller->copyAction(Request::create('/api/project-copy', 'POST', [], [], [], [], '{}'));
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertSame('admin_required', $payload['errors'][0]['code']);
        $this->assertFalse($service->called);
    }

    public function testCopyActionDelegatesToServiceWhenAdmin()
    {
        $service = new class() {
            public $capturedRequest;

            public function execute(ProjectCopyRequest $request)
            {
                $this->capturedRequest = $request;

                return new ProjectCopyResponse(
                    'source_project',
                    'target_project',
                    [
                        'project' => 1,
                        'mapset' => 2,
                    ]
                );
            }
        };

        $controller = $this->createController($service);
        $this->setAuthenticationHandler($this->createAuthHandler(true, true));

        $response = $controller->copyAction(Request::create(
            '/api/project-copy',
            'POST',
            [],
            [],
            [],
            [],
            json_encode([
                'source_project' => 'source_project',
                'target_project' => 'target_project',
                'mapset_naming_mode' => ProjectCopyRequestParser::MAPSET_NAMING_PREFIX_WITH_TARGET_PROJECT,
                'refresh' => [
                    'private_mapfiles' => true,
                ],
            ])
        ));
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame('source_project', $service->capturedRequest->getSourceProject());
        $this->assertSame('target_project', $service->capturedRequest->getTargetProject());
        $this->assertSame(ProjectCopyRequestParser::MAPSET_NAMING_PREFIX_WITH_TARGET_PROJECT, $service->capturedRequest->getMapsetNamingMode());
        $this->assertTrue($service->capturedRequest->shouldRefreshPrivateMapfiles());
        $this->assertSame('ok', $payload['status']);
        $this->assertSame(1, $payload['created']['project']);
        $this->assertSame(2, $payload['created']['mapset']);
    }

    public function testCopyActionMapsRequestValidationErrors()
    {
        $service = new class() {
            public $called = false;

            public function execute(ProjectCopyRequest $request)
            {
                $this->called = true;
                return null;
            }
        };

        $controller = $this->createController($service);
        $this->setAuthenticationHandler($this->createAuthHandler(true, true));

        $response = $controller->copyAction(Request::create(
            '/api/project-copy',
            'POST',
            [],
            [],
            [],
            [],
            json_encode([
                'target_project' => 'target_project',
            ])
        ));
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('missing_required_attribute', $payload['errors'][0]['code']);
        $this->assertFalse($service->called);
    }

    public function testCopyActionMapsServiceExceptions()
    {
        $service = new class() {
            public function execute(ProjectCopyRequest $request)
            {
                throw new ApiException(409, 'target_project_exists', 'Conflict', 'Target project already exists', '/target_project');
            }
        };

        $controller = $this->createController($service);
        $this->setAuthenticationHandler($this->createAuthHandler(true, true));

        $response = $controller->copyAction(Request::create(
            '/api/project-copy',
            'POST',
            [],
            [],
            [],
            [],
            json_encode([
                'source_project' => 'source_project',
                'target_project' => 'target_project',
            ])
        ));
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertSame('target_project_exists', $payload['errors'][0]['code']);
        $this->assertSame('/target_project', $payload['errors'][0]['source']['pointer']);
    }

    private function createController($service)
    {
        $container = new Container();
        $container->set(JsonApiExceptionMapper::class, new JsonApiExceptionMapper());
        $container->set(ProjectCopyRequestParser::class, new ProjectCopyRequestParser());
        $container->set(\GisClient\Author\Api\Service\ProjectCopyService::class, $service);

        $controller = new ProjectCopyController();
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
