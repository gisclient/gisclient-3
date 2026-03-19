<?php

namespace GisClient\Author\Persistence;

use GisClient\Author\Shared\MetadataRegistry;

class EntitySchemaRegistry
{
    /**
     * @var array<string,EntitySchema>|null
     */
    private static $schemas;

    public static function schemaForType(string $type): EntitySchema
    {
        self::initialize();

        if (!isset(self::$schemas[$type])) {
            throw new \RuntimeException(sprintf("No entity schema registered for resource type '%s'", $type));
        }

        return self::$schemas[$type];
    }

    private static function initialize(): void
    {
        if (self::$schemas !== null) {
            return;
        }

        self::$schemas = [];
        foreach (MetadataRegistry::dtoClasses() as $dtoClass) {
            $metadata = MetadataRegistry::metadataForDtoClass($dtoClass);
            self::$schemas[$metadata->getType()] = EntitySchema::fromMetadata($metadata);
        }
    }
}
