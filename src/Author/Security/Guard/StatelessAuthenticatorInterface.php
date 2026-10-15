<?php

namespace GisClient\Author\Security\Guard;

use Symfony\Component\HttpFoundation\Request;

/**
 * A guard that authenticates some requests without a session
 */
interface StatelessAuthenticatorInterface extends GuardAuthenticatorInterface
{
    /**
     * Returns true if the request carries credentials that this guard authenticates for the request only
     */
    public function isStateless(Request $request): bool;
}
