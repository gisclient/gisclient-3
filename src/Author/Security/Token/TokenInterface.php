<?php

namespace GisClient\Author\Security\Token;

/**
 * TokenInterface is the interface for the user authentication information.
 */
interface TokenInterface extends \Serializable
{
    /**
     * Returns the user credentials.
     *
     * @return mixed The user credentials
     */
    public function getCredentials();

    /**
     * Returns a user representation.
     *
     * @return mixed Can be a UserInterface instance, an object implementing a __toString method,
     *               or the username as a regular string
     */
    public function getUser();
    
    /**
     * Returns the username.
     *
     * @return string
     */
    public function getUsername();
    
    /**
     * Returns whether the user is authenticated or not.
     *
     * @return bool true
     */
    public function isAuthenticated();

    /**
     * Sets the authenticated flag.
     *
     * @param bool $isAuthenticated
     */
    public function setAuthenticated($isAuthenticated);
}
