<?php

namespace GisClient\Author\Shared;

use GisClient\Author\Shared\Metadata\CatalogMetadata;
use GisClient\Author\Shared\Metadata\ClassMetadata;
use GisClient\Author\Shared\Metadata\FieldMetadata;
use GisClient\Author\Shared\Metadata\FontMetadata;
use GisClient\Author\Shared\Metadata\LayergroupMetadata;
use GisClient\Author\Shared\Metadata\LayerMetadata;
use GisClient\Author\Shared\Metadata\LinkMetadata;
use GisClient\Author\Shared\Metadata\MapsetLayergroupMetadata;
use GisClient\Author\Shared\Metadata\MapsetMetadata;
use GisClient\Author\Shared\Metadata\Metadata;
use GisClient\Author\Shared\Metadata\ProjectMetadata;
use GisClient\Author\Shared\Metadata\ProjectSrsMetadata;
use GisClient\Author\Shared\Metadata\RelationMetadata;
use GisClient\Author\Shared\Metadata\StyleMetadata;
use GisClient\Author\Shared\Metadata\SymbolMetadata;
use GisClient\Author\Shared\Metadata\ThemeMetadata;

class MetadataRegistry
{
    /**
     * @var array<string,Metadata>|null
     */
    private static $metadataByType;

    /**
     * @var array<string,Metadata>|null
     */
    private static $metadataByDtoClass;

    public static function metadataForType(string $type): Metadata
    {
        self::initialize();

        if (!isset(self::$metadataByType[$type])) {
            throw new \RuntimeException(sprintf("No metadata registered for resource type '%s'", $type));
        }

        return self::$metadataByType[$type];
    }

    public static function metadataForDtoClass(string $dtoClass): Metadata
    {
        self::initialize();

        if (!isset(self::$metadataByDtoClass[$dtoClass])) {
            throw new \RuntimeException(sprintf("No metadata registered for DTO class '%s'", $dtoClass));
        }

        return self::$metadataByDtoClass[$dtoClass];
    }

    /**
     * @return array<int,string>
     */
    public static function dtoClasses(): array
    {
        self::initialize();

        return array_keys(self::$metadataByDtoClass);
    }

    private static function initialize(): void
    {
        if (self::$metadataByType !== null) {
            return;
        }

        self::$metadataByType = [];
        self::$metadataByDtoClass = [];

        foreach (self::metadataClasses() as $metadataClass) {
            /** @var Metadata $metadata */
            $metadata = new $metadataClass();
            self::$metadataByType[$metadata->getType()] = $metadata;
            self::$metadataByDtoClass[$metadata->getDtoClass()] = $metadata;
        }
    }

    /**
     * @return array<int,string>
     */
    private static function metadataClasses(): array
    {
        return [
            ProjectMetadata::class,
            ProjectSrsMetadata::class,
            ThemeMetadata::class,
            SymbolMetadata::class,
            FontMetadata::class,
            LayergroupMetadata::class,
            LayerMetadata::class,
            ClassMetadata::class,
            StyleMetadata::class,
            RelationMetadata::class,
            FieldMetadata::class,
            CatalogMetadata::class,
            LinkMetadata::class,
            MapsetMetadata::class,
            MapsetLayergroupMetadata::class,
        ];
    }
}
