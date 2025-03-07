<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\Php73\Rector\ConstFetch\SensitiveConstantNameRector;
use Rector\Php73\Rector\FuncCall\ArrayKeyFirstLastRector;
use Rector\Php73\Rector\FuncCall\StringifyStrNeedlesRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureVoidReturnTypeWhereNoReturnRector;

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
    ->withSkip([
        LongArrayToShortArrayRector::class, // temporarily to avoid lots of code change just for this - execute late together with ecs (to avoid long single line array)

        AddClosureVoidReturnTypeWhereNoReturnRector::class,
        ArrayKeyFirstLastRector::class,
        ClosureToArrowFunctionRector::class,
        SensitiveConstantNameRector::class,
        StringifyStrNeedlesRector::class,
    ])
    ->withCache(
        __DIR__ . '/var/cache/rector',
        FileCacheStorage::class
    );
