<?php

namespace GisClient\Author\Security\Guard;

use GisClient\Author\Security\Token\PostAuthenticationToken;
use GisClient\Author\Security\Token\TokenInterface;
use GisClient\Author\Security\Token\UsernamePasswordToken;
use GisClient\Author\Security\User\UserInterface;
use GisClient\Author\Security\User\UserProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class TokenAuthenticator implements GuardAuthenticatorInterface
{
    public const SALT = 'r3gis';

    public function getToken(Request $request)
    {
        $username = urldecode($request->headers->get('x-username'));
        if ($request->headers->has('x-domain')) {
            $username .= '@' . $request->headers->get('x-domain');
        }
        $usertoken = $request->headers->get('x-token');
        if (!empty($username) && !empty($usertoken)) {
            return new UsernamePasswordToken($username, $usertoken);
        }

        return null;
    }

    public function getUser(TokenInterface $token, UserProviderInterface $userProvider)
    {
        return $userProvider->loadUserByUsername($token->getUsername());
    }

    public function checkCredentials(TokenInterface $token, UserInterface $user)
    {
        return md5($user->getUsername() . self::SALT . $user->getPassword()) === $token->getCredentials();
    }

    public function createAuthenticatedToken(UserInterface $user)
    {
        return new PostAuthenticationToken($user);
    }
}
