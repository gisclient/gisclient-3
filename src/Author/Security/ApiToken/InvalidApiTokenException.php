<?php

namespace GisClient\Author\Security\ApiToken;

/**
 * A request carries a Bearer token that is missing, malformed, unknown or doesn't match the stored hash
 */
class InvalidApiTokenException extends \RuntimeException
{
}
