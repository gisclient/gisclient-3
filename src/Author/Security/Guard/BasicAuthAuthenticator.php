<?php

namespace GisClient\Author\Security\Guard;

use Symfony\Component\HttpFoundation\Request;
use GisClient\Author\Security\Token\TokenInterface;
use GisClient\Author\Security\Token\UsernamePasswordToken;
use GisClient\Author\Security\Token\PostAuthenticationToken;
use GisClient\Author\Security\User\UserInterface;
use GisClient\Author\Security\User\UserProviderInterface;

class BasicAuthAuthenticator implements GuardAuthenticatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getToken(Request $request)
    {
        if (!empty($request->server->get('PHP_AUTH_USER')) && !empty($request->server->get('PHP_AUTH_PW'))) {
            $username = $request->server->get('PHP_AUTH_USER');
            $password = md5($request->server->get('PHP_AUTH_PW'));
            
            $token = new UsernamePasswordToken($username, $password);
            
            return $token;
        }
        
        return null;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getUser(TokenInterface $token, UserProviderInterface $userProvider)
    {
        return $userProvider->loadUserByUsername($token->getUsername());
    }
    
    /**
     * {@inheritdoc}
     */
    public function checkCredentials(TokenInterface $token, UserInterface $user)
    {
        return $user->getPassword() === $token->getCredentials();
    }
    
    /**
     * {@inheritdoc}
     */
    public function createAuthenticatedToken(UserInterface $user)
    {
        return new PostAuthenticationToken($user);
    }
}