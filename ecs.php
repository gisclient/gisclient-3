<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
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
        true
    )

    // add a single rule
    ->withRules([
        NoUnusedImportsFixer::class,
    ])
    ->withCache(
        __DIR__ . '/var/cache/ecs'
    )
    ->withSkip([
        \PhpCsFixer\Fixer\Basic\BracesPositionFixer::class,
        \PhpCsFixer\Fixer\Casing\ConstantCaseFixer::class,
        \PhpCsFixer\Fixer\ClassNotation\ClassDefinitionFixer::class,
        \PhpCsFixer\Fixer\ControlStructure\ElseifFixer::class,
        \PhpCsFixer\Fixer\ControlStructure\NoBreakCommentFixer::class,
        \PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer::class,
        \PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer::class,
    ])

    // add sets - group of rules
   // ->withPreparedSets(
        // arrays: true,
        // namespaces: true,
        // spaces: true,
        // docblocks: true,
        // comments: true,
    // )

;
