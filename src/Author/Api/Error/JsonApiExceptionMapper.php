<?php

namespace GisClient\Author\Api\Error;

use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Api\Serializer\JsonApiSerializer;

class JsonApiExceptionMapper
{
    /**
     * @var JsonApiSerializer
     */
    private $serializer;

    public function __construct(JsonApiSerializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @return array{status:int,payload:array}
     */
    public function map(\Throwable $exception)
    {
        if ($exception instanceof ValidationException) {
            $errors = [];
            foreach ($exception->getErrors() as $error) {
                $errors[] = $this->serializer->serializeValidationError($error);
            }
            return [
                'status' => 422,
                'payload' => [
                    'errors' => $errors,
                ],
            ];
        }

        if ($exception instanceof ApiException) {
            $error = [
                'status' => (string) $exception->getStatus(),
                'code' => $exception->getErrorCode(),
                'title' => $exception->getTitle(),
                'detail' => $exception->getDetail(),
            ];
            if ($exception->getSourcePointer() !== null) {
                $error['source'] = [
                    'pointer' => $exception->getSourcePointer(),
                ];
            }
            return [
                'status' => $exception->getStatus(),
                'payload' => [
                    'errors' => [$error],
                ],
            ];
        }

        return [
            'status' => 500,
            'payload' => [
                'errors' => [[
                    'status' => '500',
                    'code' => 'internal_error',
                    'title' => 'Internal Server Error',
                    'detail' => $exception->getMessage(),
                ]],
            ],
        ];
    }
}
