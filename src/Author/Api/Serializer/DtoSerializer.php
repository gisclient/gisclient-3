<?php

namespace GisClient\Author\Api\Serializer;

use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\Schema\DtoSchemaRegistry;
use GisClient\Author\Api\Dto\Support\DtoPropertyAccessor;

class DtoSerializer
{
    /**
     * @return array<string,mixed>
     */
    public function serialize(JsonApiDto $dto): array
    {
        $schema = DtoSchemaRegistry::schemaForDtoClass(get_class($dto));
        $resource = [
            'type' => $schema->getType(),
            'id' => DtoPropertyAccessor::isInitialized($dto, 'id') ? (string) DtoPropertyAccessor::get($dto, 'id') : null,
            'attributes' => [],
        ];

        foreach ($schema->getAttributes() as $field) {
            if (!$field->isReadable()) {
                continue;
            }

            if (!DtoPropertyAccessor::isInitialized($dto, $field->getPropertyName())) {
                continue;
            }

            $resource['attributes'][$field->getJsonApiName()] = $this->normalizeValue(
                DtoPropertyAccessor::get($dto, $field->getPropertyName())
            );
        }

        $relationships = [];
        foreach ($schema->getRelationships() as $field) {
            if (!$field->isReadable()) {
                continue;
            }

            if (!DtoPropertyAccessor::isInitialized($dto, $field->getPropertyName())) {
                continue;
            }

            if ($field->isCollection()) {
                $items = DtoPropertyAccessor::get($dto, $field->getPropertyName());
                $relationships[$field->getJsonApiName()] = [
                    'data' => is_array($items) ? array_map(
                        static fn ($id) => [
                            'type' => $field->getTargetType(),
                            'id' => (string) $id,
                        ],
                        $items
                    ) : [],
                ];
                continue;
            }

            $relatedDto = DtoPropertyAccessor::get($dto, $field->getPropertyName());
            if ($relatedDto === null) {
                continue;
            }
            $relationships[$field->getJsonApiName()] = [
                'data' => [
                    'type' => $field->getTargetType(),
                    'id' => DtoPropertyAccessor::isInitialized($relatedDto, 'id') ? (string) DtoPropertyAccessor::get($relatedDto, 'id') : null,
                ],
            ];
        }

        if ($relationships !== []) {
            $resource['relationships'] = $relationships;
        }

        return $resource;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function normalizeValue($value)
    {
        if (is_float($value) && floor($value) === $value) {
            return (int) $value;
        }

        return $value;
    }
}
