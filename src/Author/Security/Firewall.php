<?php

namespace GisClient\Author\Security;

use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Mapper\JsonApiExceptionMapper;
use GisClient\Author\Security\ApiToken\ApiTokenStoreInterface;
use GisClient\Author\Security\ApiToken\InvalidApiTokenException;
use GisClient\Author\Security\Guard\BearerTokenAuthenticator;
use GisClient\Author\Security\Guard\GuardAuthenticatorInterface;
use GisClient\Author\Security\Guard\StatelessAuthenticatorInterface;
use GisClient\Author\Security\Token\InMemoryTokenStorage;
use GisClient\Author\Security\Token\TokenStorageInterface;
use GisClient\Author\Security\User\UserProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Authenticates a request against the firewalls and access control rules of the security configuration
 */
class Firewall
{
    /**
     * Firewalls, keyed by name, in matching order
     *
     * @var array<string, array<string, string>>
     */
    private $firewalls;

    /**
     * Access control rules, in matching order
     *
     * @var array<array{path: string, authenticated: bool}>
     */
    private $accessControls;

    /**
     * Database passed to the user providers
     *
     * @var \PDO
     */
    private $db;

    /**
     * API Token store passed to the Bearer token authenticator
     *
     * @var ApiTokenStoreInterface
     */
    private $apiTokenStore;

    /**
     * Creates the authentication handler for the matched provider and guard
     *
     * The token storage is given for stateless requests only: the handler must use it instead of the session.
     *
     * @var callable(UserProviderInterface|null, GuardAuthenticatorInterface|null, TokenStorageInterface|null): AuthenticationHandler
     */
    private $authenticationHandlerFactory;

    /**
     * Request the matched firewall and guard were resolved for
     *
     * @var Request|null
     */
    private $matchedRequest;

    /**
     * Firewall and guard matched for the request, so that both are resolved once per request
     *
     * @var array{0: array<string, string>|null, 1: GuardAuthenticatorInterface|null}
     */
    private $match = [null, null];

    /**
     * @param array $security the `security` section of config/security.yml
     * @param callable(UserProviderInterface|null, GuardAuthenticatorInterface|null, TokenStorageInterface|null): AuthenticationHandler $authenticationHandlerFactory
     */
    public function __construct(
        array $security,
        \PDO $db,
        ApiTokenStoreInterface $apiTokenStore,
        callable $authenticationHandlerFactory
    ) {
        $this->firewalls = $security['firewalls'];
        $this->accessControls = $security['access_control'];
        $this->db = $db;
        $this->apiTokenStore = $apiTokenStore;
        $this->authenticationHandlerFactory = $authenticationHandlerFactory;
    }

    /**
     * Returns true if the first matching firewall authenticates the request without a session
     *
     * The session must not be started for a stateless request.
     */
    public function isStateless(Request $request): bool
    {
        [, $guard] = $this->match($request);

        return null !== $guard && $this->isStatelessGuard($guard, $request);
    }

    /**
     * Log in through the first matching firewall and apply the access control rules
     *
     * @return Response|null a 401 response if the request carries an invalid API Token, null to go on with the request
     * @throws AccessDeniedHttpException if the path requires authentication and the request is not authenticated
     */
    public function authenticate(Request $request): ?Response
    {
        [$firewall, $guard] = $this->match($request);
        if (null === $firewall) {
            return null;
        }

        $provider = null;
        if (isset($firewall['provider'])) {
            $provider = new $firewall['provider']($this->db);
        }

        if ($provider === null && $guard === null) {
            return null;
        }

        $tokenStorage = null;
        if (null !== $guard && $this->isStatelessGuard($guard, $request)) {
            $tokenStorage = new InMemoryTokenStorage();
        }

        $authHandler = ($this->authenticationHandlerFactory)($provider, $guard, $tokenStorage);
        try {
            $authHandler->login($request);
        } catch (InvalidApiTokenException $e) {
            return $this->createInvalidApiTokenResponse();
        }
        if (!$this->checkAccessControl($request, $authHandler)) {
            throw new AccessDeniedHttpException('Authentication required to access this path.');
        }

        return null;
    }

    /**
     * Returns the first matching firewall and its guard
     *
     * @return array{0: array<string, string>|null, 1: GuardAuthenticatorInterface|null}
     */
    private function match(Request $request): array
    {
        if ($this->matchedRequest !== $request) {
            $firewall = $this->getFirewall($request);
            $guard = isset($firewall['guard']) ? $this->createGuard($firewall['guard']) : null;
            $this->match = [$firewall, $guard];
            $this->matchedRequest = $request;
        }

        return $this->match;
    }

    private function createGuard(string $guardClass): GuardAuthenticatorInterface
    {
        if ($guardClass === BearerTokenAuthenticator::class) {
            return new BearerTokenAuthenticator($this->apiTokenStore);
        }

        return new $guardClass();
    }

    private function isStatelessGuard(GuardAuthenticatorInterface $guard, Request $request): bool
    {
        return $guard instanceof StatelessAuthenticatorInterface && $guard->isStateless($request);
    }

    private function createInvalidApiTokenResponse(): Response
    {
        $mapped = (new JsonApiExceptionMapper())->map(new ApiException(
            Response::HTTP_UNAUTHORIZED,
            'invalid_token',
            'Unauthorized',
            'The API token is missing, malformed or invalid.'
        ));

        return new JsonResponse($mapped['payload'], $mapped['status'], [
            'WWW-Authenticate' => 'Bearer error="invalid_token"',
        ]);
    }

    /**
     * @return array<string, string>|null
     */
    private function getFirewall(Request $request): ?array
    {
        foreach ($this->firewalls as $firewallName => $firewall) {
            if (!isset($firewall['pattern'])) {
                throw new \Exception('No pattern defined for this firewall ' . $firewallName);
            }

            $firewallMatcher = new RequestMatcher($firewall['pattern']);
            if ($firewallMatcher->matches($request)) {
                return $firewall;
            }
        }

        return null;
    }

    private function checkAccessControl(Request $request, AuthenticationHandler $authHandler): bool
    {
        foreach ($this->accessControls as $accessControl) {
            $accessControlMatcher = new RequestMatcher($accessControl['path']);
            if ($accessControlMatcher->matches($request)) {
                return !$accessControl['authenticated'] || $authHandler->isAuthenticated();
            }
        }

        return false;
    }
}
