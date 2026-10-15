<?php

use GisClient\Author\Security\AuthenticationHandler;
use GisClient\Author\Security\Firewall;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Yaml\Yaml;

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

    protected function setUp(): void
    {
        $this->session = new Session(new MockArraySessionStorage());
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

    public function testApiPathUsesDefaultFirewall()
    {
        $this->authenticateSession();

        $this->createFirewall()->authenticate(Request::create('/api/project'));

        $this->assertInstanceOf(UsernamePasswordAuthenticator::class, $this->factoryCalls[0][1]);
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

        $this->createFirewall()->authenticate(Request::create('/api/project'));

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
            $this->createDatabase(),
            $this->createHandlerFactory()
        );
        $firewall->authenticate(Request::create('/api/project'));
    }

    private function createFirewall(): Firewall
    {
        $security = Yaml::parse(file_get_contents(__DIR__ . '/../../../config/security.yml'));

        return new Firewall(
            $security['security'],
            $this->createDatabase(),
            $this->createHandlerFactory()
        );
    }

    private function createDatabase(): \PDO
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is required.');
        }

        return new \PDO('sqlite::memory:');
    }

    private function createHandlerFactory(): callable
    {
        return function (?UserProviderInterface $provider, ?GuardAuthenticatorInterface $guard) {
            $this->factoryCalls[] = [$provider, $guard];

            return new AuthenticationHandler($this->session, $provider, $guard);
        };
    }

    private function authenticateSession(): void
    {
        $this->session->set(
            SessionTokenStorage::TOKEN_ID,
            serialize(new PostAuthenticationToken(new User('admin', 'secret')))
        );
    }
}
