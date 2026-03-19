<?php

namespace GisClient\Author\Api\Dto\Schema;

use GisClient\Author\Shared\MetadataRegistry;

class DtoSchemaRegistry
{
    /**
     * @return array<int,string>
     */
    public static function dtoClasses(): array
    {
        return MetadataRegistry::dtoClasses();
    }

    public static function classFromType(string $type): string
    {
        return MetadataRegistry::metadataForType($type)->getDtoClass();
    }

    public static function schemaForType(string $type): ResourceSchema
    {
        return ResourceSchema::fromMetadata(MetadataRegistry::metadataForType($type));
    }

    public static function schemaForDtoClass(string $dtoClass): ResourceSchema
    {
        return ResourceSchema::fromMetadata(MetadataRegistry::metadataForDtoClass($dtoClass));
    }
}
