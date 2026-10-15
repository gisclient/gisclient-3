<?php

namespace GisClient\Author\Security\ApiToken;

/**
 * Generates API Tokens from a cryptographic random source
 */
class ApiTokenGenerator
{
    private const ID_BYTES = 8;
    private const SECRET_BYTES = 32;

    public function generate(): GeneratedApiToken
    {
        $id = bin2hex(random_bytes(self::ID_BYTES));
        $secret = rtrim(strtr(base64_encode(random_bytes(self::SECRET_BYTES)), '+/', '-_'), '=');

        return new GeneratedApiToken($id, ApiTokenFormat::PREFIX . '_' . $id . '_' . $secret);
    }
}
