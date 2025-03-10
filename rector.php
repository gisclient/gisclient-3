<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/lib',
        __DIR__ . '/public',
        __DIR__ . '/tests',
    ])
    ->withRootFiles()

    // uncomment to reach your current PHP version
    ->withPhpSets()
    ->withTypeCoverageLevel(0)
    ->withSets([
        // PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        // SymfonySetList::SYMFONY_CODE_QUALITY,
    ])
    ->withCache(
        __DIR__ . '/var/cache/rector',
        FileCacheStorage::class
    );
