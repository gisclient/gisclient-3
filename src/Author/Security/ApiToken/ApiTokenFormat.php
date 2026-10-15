<?php

namespace GisClient\Author\Security\ApiToken;

/**
 * Format of an API Token: `gca_<id>_<secret>`
 *
 * The id is 16 lowercase hex characters, the secret is base64url without padding and may itself contain `_` and `-`.
 */
final class ApiTokenFormat
{
    public const PREFIX = 'gca';

    /**
     * Returns the id of a well-formed token, or null if the token is malformed
     */
    public static function parseId(string $token): ?string
    {
        // at most three parts: the secret may contain "_"
        $parts = explode('_', $token, 3);
        if (count($parts) !== 3) {
            return null;
        }

        [$prefix, $id, $secret] = $parts;
        if ($prefix !== self::PREFIX || !self::isValidId($id) || $secret === '') {
            return null;
        }

        return $id;
    }

    /**
     * The value stored for a token: SHA-256 (hex) of the full token string
     */
    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function isValidId(string $id): bool
    {
        return preg_match('/^[0-9a-f]{16}$/D', $id) === 1;
    }

    public static function isValidHash(string $hash): bool
    {
        return preg_match('/^[0-9a-f]{64}$/D', $hash) === 1;
    }
}
