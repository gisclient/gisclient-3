<?php

namespace GisClient\Author\Api\Dto\Support;

final class DtoPropertyAccessor
{
    /**
     * @var array<string,\ReflectionProperty>
     */
    private static $properties = [];

    /**
     * @param object $dto
     * @param mixed $value
     */
    public static function set($dto, string $property, $value): void
    {
        self::property($dto, $property)->setValue($dto, $value);
    }

    /**
     * @param object $dto
     * @return mixed
     */
    public static function get($dto, string $property)
    {
        return self::property($dto, $property)->getValue($dto);
    }

    /**
     * @param object $dto
     */
    public static function isInitialized($dto, string $property): bool
    {
        return self::property($dto, $property)->isInitialized($dto);
    }

    /**
     * @param object $dto
     */
    private static function property($dto, string $property): \ReflectionProperty
    {
        $class = get_class($dto);
        $key = $class . '::' . $property;
        if (isset(self::$properties[$key])) {
            return self::$properties[$key];
        }

        $reflection = new \ReflectionProperty($class, $property);
        $reflection->setAccessible(true);
        self::$properties[$key] = $reflection;

        return $reflection;
    }
}
