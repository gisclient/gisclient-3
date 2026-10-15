<?php

namespace GisClient\Author\Security\Token;

/**
 * Token storage that holds the token for the current request only, without a session
 */
class InMemoryTokenStorage implements TokenStorageInterface
{
    /**
     * @var TokenInterface|null
     */
    private $token;

    public function getToken()
    {
        if (null === $this->token) {
            throw new \Exception('No token has been set for this request.');
        }

        return $this->token;
    }

    public function setToken(TokenInterface $token = null)
    {
        $this->token = $token;
    }

    public function hasToken()
    {
        return null !== $this->token;
    }

    public function removeToken()
    {
        $token = $this->token;
        $this->token = null;

        return $token;
    }
}
