<?php

namespace GisClient\Author\Api\Mapper;

use GisClient\Author\Api\Exception\ApiException;
use GisClient\Author\Api\Exception\ValidationException;
use GisClient\Author\Persistence\Exception\ForeignKeyConstraintViolationException;
use GisClient\Author\Persistence\Exception\InvalidPersistedDataException;
use GisClient\Author\Persistence\Exception\RepositoryOperationException;
use GisClient\Author\Persistence\Exception\UniqueConstraintViolationException;

class JsonApiExceptionMapper
{
    /**
     * @return array{status:int,payload:array}
     */
    public function map(\Throwable $exception)
    {
        if ($exception instanceof ValidationException) {
            $errors = [];
            foreach ($exception->getErrors() as $error) {
                $errors[] = $this->serializeValidationError($error);
            }
            return [
                'status' => $exception->getStatus(),
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

        if ($exception instanceof UniqueConstraintViolationException) {
            return $this->mapError(409, 'unique_constraint_violation', 'Conflict', 'Duplicate value violates unique constraint');
        }

        if ($exception instanceof ForeignKeyConstraintViolationException) {
            return $this->mapError(409, 'foreign_key_violation', 'Conflict', 'Foreign key constraint violation');
        }

        if ($exception instanceof InvalidPersistedDataException) {
            return $this->mapError(422, 'invalid_attribute_value', 'Invalid Attribute Value', 'One or more attributes have invalid value or format');
        }

        if ($exception instanceof RepositoryOperationException) {
            return $this->mapError(500, 'database_error', 'Database Error', 'An internal error occurred');
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

    /**
     * @return array{status:int,payload:array{errors:array<int,array<string,string>>}}
     */
    private function mapError(int $status, string $code, string $title, string $detail): array
    {
        return [
            'status' => $status,
            'payload' => [
                'errors' => [[
                    'status' => (string) $status,
                    'code' => $code,
                    'title' => $title,
                    'detail' => $detail,
                ]],
            ],
        ];
    }

    /**
     * @param array<string,mixed> $error
     * @return array<string,mixed>
     */
    private function serializeValidationError(array $error)
    {
        if (!isset($error['source']) || !is_array($error['source'])) {
            return $error;
        }

        $pointer = $this->serializeErrorSource($error['source']);
        if ($pointer === null) {
            unset($error['source']);
            return $error;
        }

        $error['source'] = [
            'pointer' => $pointer,
        ];

        return $error;
    }

    /**
     * @param array<string,mixed> $source
     * @return string|null
     */
    private function serializeErrorSource(array $source)
    {
        if (isset($source['pointer']) && is_string($source['pointer'])) {
            return $source['pointer'];
        }
        if (!empty($source['id'])) {
            return '/data/id';
        }
        if (isset($source['attribute']) && is_string($source['attribute'])) {
            return '/data/attributes/' . $source['attribute'];
        }
        if (isset($source['relationship']) && is_string($source['relationship'])) {
            return '/data/relationships/' . $source['relationship'] . '/data';
        }
        if (isset($source['relationship_type']) && is_string($source['relationship_type'])) {
            return '/data/relationships/' . $source['relationship_type'] . '/data/type';
        }
        if (isset($source['relationship_id']) && is_string($source['relationship_id'])) {
            return '/data/relationships/' . $source['relationship_id'] . '/data/id';
        }
        if (isset($source['parameter']) && is_string($source['parameter'])) {
            return '/' . $source['parameter'];
        }

        return null;
    }
}
