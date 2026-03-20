<?php

namespace GisClient\Author\Security\Token;

use GisClient\Author\Security\User\UserInterface;

class PostAuthenticationToken implements TokenInterface
{
    /**
     * User
     *
     * @var mixed
     */
    private $user;
    
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
     */
    public function __construct($user)
    {
        $this->user = $user;
        $this->setAuthenticated(true);
    }
    
    public function getCredentials()
    {
        return [];
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
        $this->authenticated = (bool)$isAuthenticated;
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
