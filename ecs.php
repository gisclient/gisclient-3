<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveUselessDefaultCommentFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/lib',
        __DIR__ . '/public',
        __DIR__ . '/tests',
    ])
    ->withRootFiles()

    ->withPreparedSets(
        true,
        false,
        false,
        false,
        true,
        true,
        true
    )

    // add a single rule
    ->withRules([
        NoUnusedImportsFixer::class,
    ])
    ->withSkip([
        RemoveUselessDefaultCommentFixer::class, // Might remove useful comments
    ])
    ->withCache(
        __DIR__ . '/var/cache/ecs'
    )

    // add sets - group of rules
   // ->withPreparedSets(
        // arrays: true,
        // namespaces: true,
        // spaces: true,
        // docblocks: true,
        // comments: true,
    // )

;
