<?php

namespace GisClient\Author\Security\Guard;

use Symfony\Component\HttpFoundation\Request;
use GisClient\Author\Security\Token\TokenInterface;
use GisClient\Author\Security\User\UserInterface;
use GisClient\Author\Security\User\UserProviderInterface;

interface GuardAuthenticatorInterface
{
    /**
     * Get the authentication credentials from the request
     * 
     * @param Request $request
     * @return TokenInterface|null
     */
    public function getToken(Request $request);
    
    /**
     * Get user based on authentication credentials
     * 
     * @param mixed $token
     * @param UserProviderInterface $userProvider
     * @return UserInterface
     */
    public function getUser(TokenInterface $token, UserProviderInterface $userProvider);
    
    /**
     * Return true if the authentication credentials are valid
     * 
     * @param mixed $token
     * @param UserInterface $user
     * @return boolean
     */
    public function checkCredentials(TokenInterface $token, UserInterface $user);
    
    /**
     * Create an authenticated token for the given user
     * 
     * @param UserInterface $user
     * @return TokenInterface
     */
    public function createAuthenticatedToken(UserInterface $user);
}