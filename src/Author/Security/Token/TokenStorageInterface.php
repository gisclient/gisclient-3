<?php

namespace GisClient\Author\Security\Token;

/**
 * Token storage interface
 */
interface TokenStorageInterface
{
    /**
     * Returns the current security token.
     *
     * @return TokenInterface|null
     */
    public function getToken();

    /**
     * Sets the authentication token.
     *
     * @param TokenInterface $token
     */
    public function setToken(TokenInterface $token = null);
    
    /**
     * Removes the authentication token.
     *
     * @return TokenInterface|null
     */
    public function removeToken();

    /**
     * Checks whether a token with the given token ID exists.
     *
     * @return bool
     */
    public function hasToken();
}
