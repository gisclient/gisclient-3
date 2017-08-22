<?php

namespace GisClient\Author\Security\User;

interface UserProviderInterface
{
    /**
     * Load user by username
     * 
     * @param string $username
     * @return UserInterface
     * @throws \Exception when user is not found
     */
    public function loadUserByUsername($username);
}