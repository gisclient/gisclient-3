<?php

namespace GisClient\Author\Security;

use GisClient\Author\Security\Guard\GuardAuthenticatorInterface;
use GisClient\Author\Security\User\UserProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
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
     * Creates the authentication handler for the matched provider and guard
     *
     * @var callable(UserProviderInterface|null, GuardAuthenticatorInterface|null): AuthenticationHandler
     */
    private $authenticationHandlerFactory;

    /**
     * @param array $security the `security` section of config/security.yml
     * @param callable(UserProviderInterface|null, GuardAuthenticatorInterface|null): AuthenticationHandler $authenticationHandlerFactory
     */
    public function __construct(array $security, \PDO $db, callable $authenticationHandlerFactory)
    {
        $this->firewalls = $security['firewalls'];
        $this->accessControls = $security['access_control'];
        $this->db = $db;
        $this->authenticationHandlerFactory = $authenticationHandlerFactory;
    }

    /**
     * Log in through the first matching firewall and apply the access control rules
     *
     * @throws AccessDeniedHttpException if the path requires authentication and the request is not authenticated
     */
    public function authenticate(Request $request): void
    {
        $firewall = $this->getFirewall($request);
        if (null === $firewall) {
            return;
        }

        $provider = null;
        if (isset($firewall['provider'])) {
            $provider = new $firewall['provider']($this->db);
        }

        $guard = null;
        if (isset($firewall['guard'])) {
            $guard = new $firewall['guard']();
        }

        if ($provider === null && $guard === null) {
            return;
        }

        $authHandler = ($this->authenticationHandlerFactory)($provider, $guard);
        $authHandler->login($request);
        if (!$this->checkAccessControl($request, $authHandler)) {
            throw new AccessDeniedHttpException('Authentication required to access this path.');
        }
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
