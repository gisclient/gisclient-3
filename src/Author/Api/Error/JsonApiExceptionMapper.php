<?php

namespace GisClient\Author\Api\Error;

use GisClient\Author\Api\Exception\ApiException;

class JsonApiExceptionMapper
{
    /**
     * @return array{status:int,payload:array}
     */
    public function map(\Throwable $exception)
    {
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
