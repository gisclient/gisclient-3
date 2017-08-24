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
        return null;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return null;
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
        throw new \Exception("The pre-authentication token is never authenticated.");
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
