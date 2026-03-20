<?php

namespace GisClient\Author\Security\Token;

use GisClient\Author\Security\User\UserInterface;

class UsernamePasswordToken implements TokenInterface
{
    /**
     * User
     *
     * @var mixed
     */
    private $user;
    
    /**
     * Credentials
     *
     * @var mixed
     */
    private $credentials;
    
    /**
     * Flag to indicated if the token is authenticated
     *
     * @var boolean
     */
    private $authenticated = false;
    
    /**
     * Constructor
     *
     * @param string $user
     * @param string $credentials
     */
    public function __construct($user, $credentials)
    {
        $this->user = $user;
        $this->credentials = $credentials;
    }
    
    public function getCredentials()
    {
        return $this->credentials;
    }

    public function getUser()
    {
        return $this->user;
    }
    
    public function getUsername()
    {
        if ($this->user instanceof UserInterface) {
            return $this->user->getUsername();
        }

        return (string) $this->user;
    }
    
    public function isAuthenticated()
    {
        return $this->authenticated;
    }

    public function setAuthenticated($isAuthenticated)
    {
        throw new \Exception("Can't authenticate a username/password token.");
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
