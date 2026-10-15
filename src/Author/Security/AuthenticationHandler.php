<?php

namespace GisClient\Author\Security;

use GisClient\Author\Security\Guard\GuardAuthenticatorInterface;
use GisClient\Author\Security\Token\PreAuthenticationToken;
use GisClient\Author\Security\Token\TokenInterface;
use GisClient\Author\Security\Token\TokenStorageInterface;
use GisClient\Author\Security\User\UserInterface;
use GisClient\Author\Security\User\UserProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class AuthenticationHandler
{
    /**
     * Token storage
     *
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    
    /**
     * User provider
     *
     * @var UserProviderInterface
     */
    private $userProvider;
    
    /**
     * Guard
     *
     * @var GuardAuthenticatorInterface
     */
    private $guard;
    
    /**
     * Constructor
     *
     * @param TokenStorageInterface $tokenStorage holds the authenticated token, usually a SessionTokenStorage
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        UserProviderInterface $userProvider,
        GuardAuthenticatorInterface $guard
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->userProvider = $userProvider;
        $this->guard = $guard;
    }
    
    /**
     * Execute login
     */
    public function login(Request $request)
    {
        $token = $this->guard->getToken($request);
        
        if (null !== $token) {
            $user = $this->guard->getUser($token, $this->userProvider);
            
            // check credentials
            if (!$this->guard->checkCredentials($token, $user)) {
                throw new \Exception('Invalid user username/password');
            }
            
            $authToken = $this->guard->createAuthenticatedToken($user);
            $this->tokenStorage->setToken($authToken);
        }
    }
    
    /**
     * Get token
     *
     * @return TokenInterface
     */
    public function getToken()
    {
        try {
            return $this->tokenStorage->getToken();
        } catch (\Exception $ex) {
            return new PreAuthenticationToken();
        }
    }
    
    /**
     * Check if the user is authenticated
     *
     * @return boolean
     */
    public function isAuthenticated()
    {
        return $this->getToken()->isAuthenticated();
    }
    
    /**
     * Check if user is a administrator or optional the administrator of the project
     *
     * @param string $project
     * @return boolean
     */
    public function isAdmin($project = null)
    {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $user = $this->getToken()->getUser();
        if (!($user instanceof UserInterface)) {
            return false;
        }
        
        return $user->isAdmin($project);
    }
    
    /**
     * Execute logout
     */
    public function logout()
    {
        $this->tokenStorage->removeToken();
        \GCService::instance()->getSession()->invalidate();
    }
}
