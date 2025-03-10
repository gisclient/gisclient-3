<?php

namespace GisClient\Author\Security\Guard;

use GisClient\Author\Security\Token\PostAuthenticationToken;
use GisClient\Author\Security\Token\TokenInterface;
use GisClient\Author\Security\Token\UsernamePasswordToken;
use GisClient\Author\Security\User\UserInterface;
use GisClient\Author\Security\User\UserProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class UsernamePasswordAuthenticator implements GuardAuthenticatorInterface
{
    public function getToken(Request $request)
    {
        if ($request->getMethod() === 'POST' &&
            $request->getPathInfo() === '/login'
        ) {
            $username = $request->request->get('username');
            $password = md5($request->request->get('password'));
            
            $token = new UsernamePasswordToken($username, $password);
            
            return $token;
        }
        
        return null;
    }
    
    public function getUser(TokenInterface $token, UserProviderInterface $userProvider)
    {
        return $userProvider->loadUserByUsername($token->getUsername());
    }
    
    public function checkCredentials(TokenInterface $token, UserInterface $user)
    {
        return $user->getPassword() === $token->getCredentials();
    }
    
    public function createAuthenticatedToken(UserInterface $user)
    {
        return new PostAuthenticationToken($user);
    }
}
