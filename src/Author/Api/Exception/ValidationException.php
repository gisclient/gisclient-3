<?php

namespace GisClient\Author\Api\Exception;

class ValidationException extends \RuntimeException
{
    /**
     * @var array<int,array<string,mixed>>
     */
    private $errors;

    /**
     * @param array<int,array<string,mixed>> $errors
     */
    public function __construct(array $errors)
    {
        parent::__construct('Validation failed');
        $this->errors = $errors;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
