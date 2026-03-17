<?php

namespace GisClient\Author\Api\Exception;

class ValidationException extends \RuntimeException
{
    /**
     * @var int
     */
    private $status;

    /**
     * @var array<int,array<string,mixed>>
     */
    private $errors;

    /**
     * @param array<int,array<string,mixed>> $errors
     */
    public function __construct(array $errors, int $status = 422)
    {
        parent::__construct('Validation failed');
        $this->errors = $errors;
        $this->status = $status;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getErrors()
    {
        return $this->errors;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
