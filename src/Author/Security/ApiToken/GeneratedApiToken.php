<?php

namespace GisClient\Author\Security\ApiToken;

/**
 * A freshly generated API Token: the plaintext exists only here and is never stored
 */
final class GeneratedApiToken
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $token;

    public function __construct(string $id, string $token)
    {
        $this->id = $id;
        $this->token = $token;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * The plaintext token, to be shown to the user exactly once
     */
    public function getToken(): string
    {
        return $this->token;
    }

    public function getHash(): string
    {
        return ApiTokenFormat::hash($this->token);
    }
}
