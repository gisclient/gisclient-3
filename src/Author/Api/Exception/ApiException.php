<?php

namespace GisClient\Author\Api\Exception;

class ApiException extends \RuntimeException
{
    /**
     * @var int
     */
    private $status;

    /**
     * @var string
     */
    private $errorCode;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string|null
     */
    private $sourcePointer;

    public function __construct($status, $errorCode, $title, $detail, $sourcePointer = null, ?\Throwable $previous = null)
    {
        parent::__construct($detail, 0, $previous);
        $this->status = (int) $status;
        $this->errorCode = $errorCode;
        $this->title = $title;
        $this->sourcePointer = $sourcePointer;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDetail()
    {
        return $this->getMessage();
    }

    public function getSourcePointer()
    {
        return $this->sourcePointer;
    }
}
