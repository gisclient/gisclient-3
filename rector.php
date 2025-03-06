<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withRootFiles()

    // uncomment to reach your current PHP version
    ->withPhpSets()
    ->withTypeCoverageLevel(0)
    ->withSets([
    ])
    ->withCache(
        __DIR__ . '/var/cache/rector',
        FileCacheStorage::class
    );
