<?php

namespace GisClient\Author\Security\Token;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Token storage that uses a Symfony Session object.
 */
class SessionTokenStorage implements TokenStorageInterface
{
    const TOKEN_ID = 'author/user';
    
    /**
     * The user session from which the session ID is returned.
     *
     * @var SessionInterface
     */
    private $session;
    
    /**
     * Initializes the storage with a Session object and a session namespace.
     *
     * @param SessionInterface $session    The user session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function getToken()
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        if (!$this->session->has(self::TOKEN_ID)) {
            throw new \Exception('The token with ID '.self::TOKEN_ID.' does not exist.');
        }

        return unserialize($this->session->get(self::TOKEN_ID));
    }

    /**
     * {@inheritdoc}
     */
    public function setToken(TokenInterface $token = null)
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        $this->session->set(self::TOKEN_ID, serialize($token));
    }

    /**
     * {@inheritdoc}
     */
    public function hasToken()
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        return $this->session->has(self::TOKEN_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function removeToken()
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        return $this->session->remove(self::TOKEN_ID);
    }
}
