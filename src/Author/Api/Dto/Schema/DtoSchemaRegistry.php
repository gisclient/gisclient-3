<?php

namespace GisClient\Author\Api\Dto\Schema;

use GisClient\Author\Api\Dto\CatalogDto;
use GisClient\Author\Api\Dto\ClassDto;
use GisClient\Author\Api\Dto\FieldDto;
use GisClient\Author\Api\Dto\JsonApiDto;
use GisClient\Author\Api\Dto\LayerDto;
use GisClient\Author\Api\Dto\LayergroupDto;
use GisClient\Author\Api\Dto\LinkDto;
use GisClient\Author\Api\Dto\MapsetDto;
use GisClient\Author\Api\Dto\MapsetLayergroupDto;
use GisClient\Author\Api\Dto\ProjectDto;
use GisClient\Author\Api\Dto\ProjectSrsDto;
use GisClient\Author\Api\Dto\StyleDto;
use GisClient\Author\Api\Dto\ThemeDto;

class DtoSchemaRegistry
{
    /**
     * @var array<string,string>|null
     */
    private static $typeToClass;

    /**
     * @return array<int,string>
     */
    public static function dtoClasses(): array
    {
        return [
            ProjectDto::class,
            ProjectSrsDto::class,
            ThemeDto::class,
            LayergroupDto::class,
            LayerDto::class,
            ClassDto::class,
            StyleDto::class,
            FieldDto::class,
            CatalogDto::class,
            LinkDto::class,
            MapsetDto::class,
            MapsetLayergroupDto::class,
        ];
    }

    public static function classFromType(string $type): string
    {
        self::initialize();
        if (!isset(self::$typeToClass[$type])) {
            throw new \RuntimeException(sprintf("No DTO registered for resource type '%s'", $type));
        }

        return self::$typeToClass[$type];
    }

    public static function schemaForType(string $type): ResourceSchema
    {
        $dtoClass = self::classFromType($type);

        return $dtoClass::schema();
    }

    private static function initialize(): void
    {
        if (self::$typeToClass !== null) {
            return;
        }

        self::$typeToClass = [];
        foreach (self::dtoClasses() as $dtoClass) {
            /** @var class-string<JsonApiDto> $dtoClass */
            self::$typeToClass[$dtoClass::schema()->getType()] = $dtoClass;
        }
    }
}
