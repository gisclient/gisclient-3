<?php

use GisClient\Author\Api\Mapper\JsonApiExceptionMapper;
use GisClient\Author\Controller\RefreshMapfileController;
use GisClient\MapServer\Writer\MapfileRefresherInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RefreshMapfileControllerTest extends TestCase
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

    public function testApiActionReturnsAuthenticationRequiredWhenUnauthenticated()
    {
        $refresher = $this->createRefresher();
        $this->setAuthenticationHandler($this->createAuthHandler(false, false));

        $response = $this->createController($refresher)->apiRefreshMapfileAction($this->createRequest('/api/refresh-mapfile', [
            'target' => 'public',
            'project' => 'default',
        ]));
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame('authentication_required', $payload['errors'][0]['code']);
        $this->assertSame([], $refresher->calls);
    }

    public function testApiActionReturnsAdminRequiredWhenAuthenticatedNonAdmin()
    {
        $refresher = $this->createRefresher();
        $this->setAuthenticationHandler($this->createAuthHandler(true, false));

        $response = $this->createController($refresher)->apiRefreshMapfileAction($this->createRequest('/api/refresh-mapfile', [
            'target' => 'public',
            'project' => 'default',
        ]));
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertSame('admin_required', $payload['errors'][0]['code']);
        $this->assertSame([], $refresher->calls);
    }

    /**
     * @dataProvider missingParameterProvider
     */
    public function testApiActionValidatesParametersLikeTheLegacyAction(array $parameters, string $expectedError)
    {
        $refresher = $this->createRefresher();
        $this->setAuthenticationHandler($this->createAuthHandler(true, true));
        $controller = $this->createController($refresher);

        $apiResponse = $controller->apiRefreshMapfileAction($this->createRequest('/api/refresh-mapfile', $parameters));
        $legacyResponse = $controller->refreshMapfileAction($this->createRequest('/refresh_mapfile', $parameters));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $apiResponse->getStatusCode());
        $this->assertSame([
            'result' => 'ok',
            'error' => $expectedError,
        ], json_decode($apiResponse->getContent(), true));
        $this->assertSame($legacyResponse->getStatusCode(), $apiResponse->getStatusCode());
        $this->assertSame($legacyResponse->getContent(), $apiResponse->getContent());
        $this->assertSame([], $refresher->calls);
    }

    public function missingParameterProvider(): array
    {
        return [
            'missing target' => [[
                'project' => 'default',
            ], "Missing parameter 'target'"],
            'missing project' => [[
                'target' => 'public',
            ], "Missing parameter 'project'"],
            'missing both' => [[], "Missing parameter 'target'"],
        ];
    }

    /**
     * @dataProvider refreshParameterProvider
     */
    public function testApiActionRefreshesMapfilesWhenAdmin(array $parameters, array $expectedCall)
    {
        $refresher = $this->createRefresher();
        $this->setAuthenticationHandler($this->createAuthHandler(true, true));

        $response = $this->createController($refresher)->apiRefreshMapfileAction($this->createRequest('/api/refresh-mapfile', $parameters));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame([
            'result' => 'ok',
        ], json_decode($response->getContent(), true));
        $this->assertSame([$expectedCall], $refresher->calls);
    }

    public function refreshParameterProvider(): array
    {
        return [
            'public mapfiles of a project' => [[
                'target' => 'public',
                'project' => 'default',
            ], ['default', null, true]],
            'temporary mapfile of one mapset' => [[
                'target' => 'tmp',
                'project' => 'default',
                'mapset' => 'my_mapset',
            ], ['default', 'my_mapset', false]],
            'empty mapset means all mapsets' => [[
                'target' => 'public',
                'project' => 'default',
                'mapset' => '',
            ], ['default', null, true]],
        ];
    }

    public function testApiActionReturnsTheLegacyErrorWhenTheRefreshFails()
    {
        $refresher = $this->createRefresher(new \Exception("Project 'unknown' does not exist."));
        $this->setAuthenticationHandler($this->createAuthHandler(true, true));

        $response = $this->createController($refresher)->apiRefreshMapfileAction($this->createRequest('/api/refresh-mapfile', [
            'target' => 'public',
            'project' => 'unknown',
        ]));

        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertSame([
            'result' => 'error',
            'error' => "Project 'unknown' does not exist.",
        ], json_decode($response->getContent(), true));
    }

    public function testLegacyActionStillRefreshesForAnyAuthenticatedUser()
    {
        $refresher = $this->createRefresher();
        $this->setAuthenticationHandler($this->createAuthHandler(true, false));

        $response = $this->createController($refresher)->refreshMapfileAction($this->createRequest('/refresh_mapfile', [
            'target' => 'public',
            'project' => 'default',
            'mapset' => 'my_mapset',
        ]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame([['default', 'my_mapset', true]], $refresher->calls);
    }

    private function createRequest(string $path, array $parameters): Request
    {
        return Request::create($path, 'POST', $parameters);
    }

    private function createController(MapfileRefresherInterface $refresher): RefreshMapfileController
    {
        $container = new Container();
        $container->set(JsonApiExceptionMapper::class, new JsonApiExceptionMapper());
        $container->set(MapfileRefresherInterface::class, $refresher);

        $controller = new RefreshMapfileController();
        $controller->setContainer($container);

        return $controller;
    }

    private function createRefresher(?\Exception $failure = null)
    {
        return new class($failure) implements MapfileRefresherInterface {
            /**
             * @var array<int, array{0: string, 1: string|null, 2: bool}>
             */
            public $calls = [];

            private $failure;

            public function __construct(?\Exception $failure)
            {
                $this->failure = $failure;
            }

            public function refresh(string $project, ?string $mapset, bool $publish): void
            {
                $this->calls[] = [$project, $mapset, $publish];
                if (null !== $this->failure) {
                    throw $this->failure;
                }
            }
        };
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
