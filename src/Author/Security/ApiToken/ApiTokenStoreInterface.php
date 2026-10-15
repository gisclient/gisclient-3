<?php

namespace GisClient\Author\Security\ApiToken;

/**
 * Persistence of API Tokens: only their hash is stored
 */
interface ApiTokenStoreInterface
{
    /**
     * @throws ApiTokenRegistrationException if the id or hash is malformed, the user doesn't exist, or the id or the name for the user is taken
     */
    public function register(string $id, string $username, string $name, string $tokenHash): void;

    public function find(string $id): ?ApiToken;

    /**
     * @param string|null $username only the tokens of this user, or all tokens if null
     *
     * @return ApiToken[] ordered by username and name
     */
    public function list(?string $username = null): array;

    /**
     * @return bool false if no token has the given id
     */
    public function delete(string $id): bool;
}
