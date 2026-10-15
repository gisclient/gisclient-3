<?php

namespace GisClient\Author\Security\ApiToken;

/**
 * A stored API Token: only the hash of the token is known
 */
final class ApiToken
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $tokenHash;

    /**
     * @var string
     */
    private $createdAt;

    public function __construct(string $id, string $username, string $name, string $tokenHash, string $createdAt)
    {
        $this->id = $id;
        $this->username = $username;
        $this->name = $name;
        $this->tokenHash = $tokenHash;
        $this->createdAt = $createdAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Compares the given plaintext token with the stored hash in constant time
     */
    public function matches(string $token): bool
    {
        return hash_equals($this->tokenHash, ApiTokenFormat::hash($token));
    }
}
