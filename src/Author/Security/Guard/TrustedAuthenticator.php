<?php

namespace GisClient\Author\Security\Guard;

use Symfony\Component\HttpFoundation\Request;
use GisClient\Author\Security\Token\TokenInterface;
use GisClient\Author\Security\Token\UsernamePasswordToken;
use GisClient\Author\Security\Token\PostAuthenticationToken;
use GisClient\Author\Security\User\UserInterface;
use GisClient\Author\Security\User\UserProviderInterface;

class TrustedAuthenticator implements GuardAuthenticatorInterface
{
    /**
     * Username
     * 
     * @var string 
     */
    private $username;
    
    /**
     * Password
     * 
     * @var string 
     */
    private $password;
    
    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = md5($password);
    }
    /**
     * {@inheritdoc}
     */
    public function getToken(Request $request)
    {
        $token = new UsernamePasswordToken($this->username, $this->password);
        return $token;
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