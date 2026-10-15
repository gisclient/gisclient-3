<?php

namespace GisClient\Author\Security\ApiToken;

/**
 * An API Token can't be registered; the message is meant for the user
 */
class ApiTokenRegistrationException extends \RuntimeException
{
    public static function malformedId(string $id): self
    {
        return new self("Malformed token id '$id': expected 16 lowercase hex characters.");
    }

    public static function malformedHash(): self
    {
        return new self('Malformed token hash: expected 64 lowercase hex characters.');
    }

    public static function unknownUser(string $username): self
    {
        return new self("User '$username' does not exist.");
    }

    public static function duplicateId(string $id): self
    {
        return new self("A token with id '$id' already exists.");
    }

    public static function duplicateName(string $username, string $name): self
    {
        return new self("User '$username' already has a token named '$name'.");
    }
}
