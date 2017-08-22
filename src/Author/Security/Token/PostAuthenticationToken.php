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
     * @param string $credentials
     */
    public function __construct($user)
    {
        $this->user = $user;
        $this->setAuthenticated(true);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        return $this->user;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        if ($this->user instanceof UserInterface) {
            return $this->user->getUsername();
        }

        return (string) $this->user;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAuthenticated()
    {
        return $this->authenticated;
    }

    /**
     * {@inheritdoc}
     */
    public function setAuthenticated($isAuthenticated)
    {
        $this->authenticated = (bool)$isAuthenticated;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(array(
            is_object($this->user) ? clone $this->user : $this->user,
            $this->authenticated
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($this->user, $this->authenticated) = unserialize($serialized);
    }
}
