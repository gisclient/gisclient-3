<?php

use GisClient\Author\Api\Mapper\JsonApiExceptionMapper;
use GisClient\Author\Api\Serializer\JsonApiSerializer;
use GisClient\Author\Api\Service\ApiCrudService;
use GisClient\Author\Controller\JsonApiController;
use GisClient\Author\Security\ApiToken\ApiTokenGenerator;
use GisClient\Author\Security\ApiToken\PdoApiTokenStore;
use GisClient\Author\Security\AuthenticationHandler;
use GisClient\Author\Security\Firewall;
use GisClient\Author\Security\Guard\BearerTokenAuthenticator;
use GisClient\Author\Security\Guard\GuardAuthenticatorInterface;
use GisClient\Author\Security\Guard\TokenAuthenticator;
use GisClient\Author\Security\Guard\UsernamePasswordAuthenticator;
use GisClient\Author\Security\Token\PostAuthenticationToken;
use GisClient\Author\Security\Token\SessionTokenStorage;
use GisClient\Author\Security\User\R3UserProvider;
use GisClient\Author\Security\User\User;
use GisClient\Author\Security\User\UserProvider;
use GisClient\Author\Security\User\UserProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__ . '/Support/SecurityTestDatabase.php';

class FirewallTest extends TestCase
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var array<array{UserProviderInterface|null, GuardAuthenticatorInterface|null}>
     */
    private $factoryCalls = [];

    /**
     * @var \PDO|null
     */
    private $db;

    /**
     * @var mixed
     */
    private $originalAuthenticationHandler;

    protected function setUp(): void
    {
        $this->session = new Session(new MockArraySessionStorage());
        $this->originalAuthenticationHandler = $this->getGlobalAuthenticationHandler();
    }

    protected function tearDown(): void
    {
        $this->setGlobalAuthenticationHandler($this->originalAuthenticationHandler);
    }

    public function testR3gisAppsPathUsesR3gisAppsFirewall()
    {
        $this->createFirewall()->authenticate(Request::create('/r3gis_apps/login'));

        $this->assertCount(1, $this->factoryCalls);
        $this->assertInstanceOf(R3UserProvider::class, $this->factoryCalls[0][0]);
        $this->assertInstanceOf(TokenAuthenticator::class, $this->factoryCalls[0][1]);
    }

    public function testOtherPathUsesDefaultFirewall()
    {
        $this->createFirewall()->authenticate(Request::create('/login'));

        $this->assertCount(1, $this->factoryCalls);
        $this->assertInstanceOf(UserProvider::class, $this->factoryCalls[0][0]);
        $this->assertInstanceOf(UsernamePasswordAuthenticator::class, $this->factoryCalls[0][1]);
    }

    public function testApiPathUsesApiFirewall()
    {
        $this->authenticateSession();

        $this->createFirewall()->authenticate(Request::create('/api/project'));

        $this->assertInstanceOf(UserProvider::class, $this->factoryCalls[0][0]);
        $this->assertInstanceOf(BearerTokenAuthenticator::class, $this->factoryCalls[0][1]);
    }

    public function testValidApiTokenAuthenticatesAsItsUser()
    {
        $token = $this->createApiToken('admin', 'client-app');

        $response = $this->createFirewall()->authenticate($this->createBearerRequest('/api/project', $token));

        $this->assertNull($response);
        $this->assertSame('admin', $this->getAuthenticatedUsername());
    }

    public function testApiTokenWithUnderscoreAndDashInSecretAuthenticates()
    {
        $this->addUser('admin');
        $token = 'gca_0123456789abcdef_a_b-c_-d' . str_repeat('x', 36);
        $this->createStore()->register('0123456789abcdef', 'admin', 'client-app', hash('sha256', $token));

        $response = $this->createFirewall()->authenticate($this->createBearerRequest('/api/project', $token));

        $this->assertNull($response);
        $this->assertSame('admin', $this->getAuthenticatedUsername());
    }

    /**
     * @dataProvider invalidAuthorizationProvider
     */
    public function testInvalidApiTokenIsRejectedWithoutSessionFallback(callable $authorization)
    {
        $this->authenticateSession();
        $validToken = $this->createApiToken('admin', 'client-app');

        $request = Request::create('/api/project', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => $authorization($validToken),
        ]);
        $response = $this->createFirewall()->authenticate($request);

        $this->assertInvalidTokenResponse($response);
    }

    public function invalidAuthorizationProvider(): array
    {
        return [
            'unknown id' => [
                fn (string $token) => 'Bearer gca_ffffffffffffffff' . substr($token, 20),
            ],
            'wrong secret' => [
                fn (string $token) => 'Bearer ' . substr($token, 0, -1) . (substr($token, -1) === 'A' ? 'B' : 'A'),
            ],
            'malformed id' => [
                fn (string $token) => 'Bearer gca_XYZ_' . substr($token, 21),
            ],
            'no secret' => [
                fn (string $token) => 'Bearer ' . substr($token, 0, 20),
            ],
            'wrong prefix' => [
                fn (string $token) => 'Bearer abc' . substr($token, 3),
            ],
            'empty value' => [
                fn (string $token) => 'Bearer ',
            ],
            'scheme only' => [
                fn (string $token) => 'Bearer',
            ],
            'missing space after scheme' => [
                fn (string $token) => 'Bearer' . $token,
            ],
            'other scheme' => [
                fn (string $token) => 'Basic ' . base64_encode('admin:secret'),
            ],
            'empty header' => [
                fn (string $token) => '',
            ],
        ];
    }

    public function testRevokedApiTokenIsRejected()
    {
        $this->addUser('admin');
        $generated = (new ApiTokenGenerator())->generate();
        $store = $this->createStore();
        $store->register($generated->getId(), 'admin', 'client-app', $generated->getHash());
        $store->delete($generated->getId());

        $response = $this->createFirewall()->authenticate(
            $this->createBearerRequest('/api/project', $generated->getToken())
        );

        $this->assertInvalidTokenResponse($response);
    }

    public function testApiPathWithoutAuthorizationHeaderUsesSession()
    {
        $this->authenticateSession();

        $response = $this->createFirewall()->authenticate(Request::create('/api/project'));

        $this->assertNull($response);
        $this->assertSame('admin', $this->getAuthenticatedUsername());
    }

    public function testApiPathWithoutAuthorizationHeaderOrSessionIsRejected()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->createFirewall()->authenticate(Request::create('/api/project'));
    }

    public function testApiTokenOnNonApiPathIsIgnored()
    {
        $token = $this->createApiToken('admin', 'client-app');

        $this->expectException(AccessDeniedHttpException::class);

        $this->createFirewall()->authenticate($this->createBearerRequest('/logout', $token));
    }

    public function testApiTokenOfUserWhoIsNotSuperUserIsForbiddenByController()
    {
        $token = $this->createApiToken('editor', 'client-app');

        $response = $this->createFirewall()->authenticate($this->createBearerRequest('/api/project', $token));
        $this->assertNull($response);

        $this->setGlobalAuthenticationHandler($this->factoryCalls[0][2]);
        $response = $this->createJsonApiController()->indexAction('project', Request::create('/api/project'));
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertSame('admin_required', $payload['errors'][0]['code']);
    }

    /**
     * @dataProvider publicPathProvider
     */
    public function testPublicPathPassesWithoutAuthentication(string $path)
    {
        $this->createFirewall()->authenticate(Request::create($path));

        $this->assertFalse($this->session->has(SessionTokenStorage::TOKEN_ID));
    }

    public function publicPathProvider(): array
    {
        return [
            'login' => ['/login'],
            'refresh' => ['/refresh_mapfile'],
            'gcmap' => ['/gcmap.php'],
            'download' => ['/download.php'],
            'r3gis_apps login' => ['/r3gis_apps/login'],
            'r3gis_apps gcmapconfig' => ['/r3gis_apps/gcmapconfig.php'],
        ];
    }

    /**
     * @dataProvider protectedPathProvider
     */
    public function testProtectedPathWithoutAuthenticationIsRejected(string $path)
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Authentication required to access this path.');

        $this->createFirewall()->authenticate(Request::create($path));
    }

    public function protectedPathProvider(): array
    {
        return [
            'api' => ['/api/project'],
            'logout' => ['/logout'],
            'r3gis_apps print' => ['/r3gis_apps/print.php'],
        ];
    }

    public function testProtectedPathPassesWithAuthenticatedSession()
    {
        $this->authenticateSession();

        $response = $this->createFirewall()->authenticate(Request::create('/api/project'));

        $this->assertNull($response);
        $this->assertCount(1, $this->factoryCalls);
    }

    public function testFirewallWithoutPatternIsAnError()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No pattern defined for this firewall broken');

        $firewall = new Firewall(
            [
                'firewalls' => [
                    'broken' => [],
                ],
                'access_control' => [],
            ],
            $this->getDatabase(),
            $this->createStore(),
            $this->createHandlerFactory()
        );
        $firewall->authenticate(Request::create('/api/project'));
    }

    private function createFirewall(): Firewall
    {
        $security = Yaml::parse(file_get_contents(__DIR__ . '/../../../config/security.yml'));

        return new Firewall(
            $security['security'],
            $this->getDatabase(),
            $this->createStore(),
            $this->createHandlerFactory()
        );
    }

    private function getDatabase(): \PDO
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is required.');
        }

        if (null === $this->db) {
            $this->db = SecurityTestDatabase::create();
        }

        return $this->db;
    }

    private function createStore(): PdoApiTokenStore
    {
        return new PdoApiTokenStore($this->getDatabase());
    }

    private function addUser(string $username): void
    {
        SecurityTestDatabase::addUser($this->getDatabase(), $username);
    }

    private function createApiToken(string $username, string $name): string
    {
        $this->addUser($username);
        $generated = (new ApiTokenGenerator())->generate();
        $this->createStore()->register($generated->getId(), $username, $name, $generated->getHash());

        return $generated->getToken();
    }

    private function createBearerRequest(string $path, string $token): Request
    {
        return Request::create($path, 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
    }

    private function createHandlerFactory(): callable
    {
        return function (?UserProviderInterface $provider, ?GuardAuthenticatorInterface $guard) {
            $handler = new AuthenticationHandler($this->session, $provider, $guard);
            $this->factoryCalls[] = [$provider, $guard, $handler];

            return $handler;
        };
    }

    private function getAuthenticatedUsername(): ?string
    {
        $handler = $this->factoryCalls[0][2];
        if (!$handler->isAuthenticated()) {
            return null;
        }

        return $handler->getToken()->getUsername();
    }

    private function assertInvalidTokenResponse(?Response $response): void
    {
        $this->assertNotNull($response);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame('Bearer error="invalid_token"', $response->headers->get('WWW-Authenticate'));
        $payload = json_decode($response->getContent(), true);
        $this->assertSame('401', $payload['errors'][0]['status']);
        $this->assertSame('invalid_token', $payload['errors'][0]['code']);
    }

    private function createJsonApiController(): JsonApiController
    {
        $container = new Container();
        $container->set(ApiCrudService::class, new class() {
            public function listResources($entity, array $query)
            {
                throw new \LogicException('The controller must not reach the service.');
            }
        });
        $container->set(JsonApiExceptionMapper::class, new JsonApiExceptionMapper());
        $container->set(JsonApiSerializer::class, new JsonApiSerializer());

        $controller = new JsonApiController();
        $controller->setContainer($container);

        return $controller;
    }

    /**
     * @return mixed
     */
    private function getGlobalAuthenticationHandler()
    {
        $property = new ReflectionProperty('GCApp', 'authenticationHandler');
        $property->setAccessible(true);

        return $property->getValue();
    }

    /**
     * @param mixed $handler
     */
    private function setGlobalAuthenticationHandler($handler): void
    {
        $property = new ReflectionProperty('GCApp', 'authenticationHandler');
        $property->setAccessible(true);
        $property->setValue(null, $handler);
    }

    private function authenticateSession(): void
    {
        $this->session->set(
            SessionTokenStorage::TOKEN_ID,
            serialize(new PostAuthenticationToken(new User('admin', 'secret')))
        );
    }
}
