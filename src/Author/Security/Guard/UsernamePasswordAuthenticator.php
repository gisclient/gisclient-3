<?php

namespace GisClient\Author\Security\Guard;

use Symfony\Component\HttpFoundation\Request;
use GisClient\Author\Security\Token\TokenInterface;
use GisClient\Author\Security\Token\UsernamePasswordToken;
use GisClient\Author\Security\Token\PostAuthenticationToken;
use GisClient\Author\Security\User\UserInterface;
use GisClient\Author\Security\User\UserProviderInterface;

class UsernamePasswordAuthenticator implements GuardAuthenticatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getToken(Request $request)
    {
        // TODO:
        if ($request->getMethod() === 'POST') { // TODO: could be limited to certain areas! pathinfo!!
            $username = $request->request->get('username');
            $password = md5($request->request->get('password'));
            
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
