<?php

namespace GisClient\Author\Security\Guard;

use GisClient\Author\Security\ApiToken\ApiTokenFormat;
use GisClient\Author\Security\ApiToken\ApiTokenStoreInterface;
use GisClient\Author\Security\ApiToken\InvalidApiTokenException;
use GisClient\Author\Security\Token\PostAuthenticationToken;
use GisClient\Author\Security\Token\TokenInterface;
use GisClient\Author\Security\Token\UsernamePasswordToken;
use GisClient\Author\Security\User\UserInterface;
use GisClient\Author\Security\User\UserProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Authenticates a request with an API Token sent as `Authorization: Bearer <token>`
 *
 * Requests without an authorization header are left to the session. Requests with one are stateless.
 */
class BearerTokenAuthenticator implements StatelessAuthenticatorInterface
{
    /**
     * @var ApiTokenStoreInterface
     */
    private $store;

    public function __construct(ApiTokenStoreInterface $store)
    {
        $this->store = $store;
    }

    /**
     * Any authorization header makes the request stateless: one that is not a valid Bearer token gets a 401, never the session
     */
    public function isStateless(Request $request): bool
    {
        return $request->headers->has('Authorization');
    }

    /**
     * @throws InvalidApiTokenException if the authorization header is not a Bearer token, or the token is missing, malformed, unknown or doesn't match
     */
    public function getToken(Request $request)
    {
        $token = $this->getBearerToken($request);
        if (null === $token) {
            return null;
        }

        $id = ApiTokenFormat::parseId($token);
        if (null === $id) {
            throw new InvalidApiTokenException('Malformed API token.');
        }

        $apiToken = $this->store->find($id);
        if (null === $apiToken || !$apiToken->matches($token)) {
            throw new InvalidApiTokenException("Invalid API token '$id'.");
        }

        return new UsernamePasswordToken($apiToken->getUsername(), $apiToken->getId());
    }

    public function getUser(TokenInterface $token, UserProviderInterface $userProvider)
    {
        return $userProvider->loadUserByUsername($token->getUsername());
    }

    public function checkCredentials(TokenInterface $token, UserInterface $user)
    {
        // the token was already checked against the store in getToken()
        return true;
    }

    public function createAuthenticatedToken(UserInterface $user)
    {
        return new PostAuthenticationToken($user);
    }

    /**
     * Returns the credentials of the Bearer authorization header, or null if the request has no authorization header
     *
     * @throws InvalidApiTokenException if the authorization header is not a Bearer token
     */
    private function getBearerToken(Request $request): ?string
    {
        if (!$request->headers->has('Authorization')) {
            return null;
        }

        $parts = preg_split('/\s+/', trim((string) $request->headers->get('Authorization')), 2);
        if (strcasecmp($parts[0], 'Bearer') !== 0) {
            throw new InvalidApiTokenException('The authorization header is not a Bearer token.');
        }

        return $parts[1] ?? '';
    }
}
