<?php

namespace GisClient\Author\Security\Token;

class PreAuthenticationToken implements TokenInterface
{
    /**
     * Flag to indicated if the token is authenticated
     *
     * @var boolean
     */
    private $authenticated = false;
    private $user = null;
    
    public function getCredentials()
    {
        return [];
    }

    public function getUser()
    {
        return null;
    }
    
    public function getUsername()
    {
        return null;
    }
    
    public function isAuthenticated()
    {
        return $this->authenticated;
    }

    public function setAuthenticated($isAuthenticated)
    {
        throw new \Exception("The pre-authentication token is never authenticated.");
    }

    public function serialize()
    {
        return serialize([
            is_object($this->user) ? clone $this->user : $this->user,
            $this->authenticated,
        ]);
    }

    public function unserialize($serialized)
    {
        [$this->user, $this->authenticated] = unserialize($serialized);
    }
}
