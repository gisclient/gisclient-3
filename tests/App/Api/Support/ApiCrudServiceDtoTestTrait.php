<?php

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Exception\ApiException;

trait ApiCrudServiceDtoTestTrait
{
    private function makeDto(string $class, $id = null, array $attributes = [], array $relationships = []): JsonApiDto
    {
        /** @var JsonApiDto $dto */
        $dto = new $class();

        if ($id !== null) {
            $dto->id = $id;
            $dto->markPresent('id');
        }

        foreach ($attributes as $field => $value) {
            $property = $this->fieldToProperty($field);
            if (property_exists($dto, $property)) {
                $dto->{$property} = $value;
                $dto->markPresent($field);
                continue;
            }

            throw new \InvalidArgumentException(sprintf(
                "Unknown attribute '%s' for DTO '%s'",
                $field,
                $class
            ));
        }

        foreach ($relationships as $field => $value) {
            $property = $this->fieldToProperty($field);
            if (property_exists($dto, $property)) {
                $dto->{$property} = $value;
                $dto->markPresent($field);
                continue;
            }

            throw new \InvalidArgumentException(sprintf(
                "Unknown relationship '%s' for DTO '%s'",
                $field,
                $class
            ));
        }

        return $dto;
    }

    private function identifierDto(string $class, $id): JsonApiDto
    {
        /** @var JsonApiDto $dto */
        $dto = new $class();
        $dto->id = $id;
        $dto->markPresent('id');
        $dto->markAsIdentifierOnly();

        return $dto;
    }

    private function assertApiException(callable $callable, int $status, string $code, ?string $pointer = null): void
    {
        try {
            $callable();
            $this->fail(sprintf('Expected %s ApiException', $code));
        } catch (ApiException $exception) {
            $this->assertSame($status, $exception->getStatus());
            $this->assertSame($code, $exception->getErrorCode());
            if ($pointer !== null) {
                $this->assertSame($pointer, $exception->getSourcePointer());
            }
        }
    }

    private function fieldToProperty(string $field): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $field))));
    }
}
