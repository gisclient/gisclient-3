<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use PhpCsFixer\Fixer\CastNotation\ShortScalarCastFixer;
use PhpCsFixer\Fixer\ClassNotation\NoBlankLinesAfterClassOpeningFixer;
use PhpCsFixer\Fixer\ClassNotation\VisibilityRequiredFixer;
use PhpCsFixer\Fixer\FunctionNotation\MethodArgumentSpaceFixer;
use PhpCsFixer\Fixer\FunctionNotation\NoSpacesAfterFunctionNameFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\LanguageConstruct\SingleSpaceAroundConstructFixer;
use PhpCsFixer\Fixer\NamespaceNotation\BlankLineAfterNamespaceFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Operator\NewWithParenthesesFixer;
use PhpCsFixer\Fixer\Operator\TernaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\PhpTag\BlankLineAfterOpeningTagFixer;
use PhpCsFixer\Fixer\Whitespace\SpacesInsideParenthesesFixer;
use PhpCsFixer\Fixer\Whitespace\StatementIndentationFixer;

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
        ShortScalarCastFixer::class,
        NoBlankLinesAfterClassOpeningFixer::class,
        VisibilityRequiredFixer::class,
        MethodArgumentSpaceFixer::class,
        NoSpacesAfterFunctionNameFixer::class,
        NoUnusedImportsFixer::class,
        OrderedImportsFixer::class,
        SingleSpaceAroundConstructFixer::class,
        BlankLineAfterNamespaceFixer::class,
        BinaryOperatorSpacesFixer::class,
        ConcatSpaceFixer::class,
        NewWithParenthesesFixer::class,
        TernaryOperatorSpacesFixer::class,
        BlankLineAfterOpeningTagFixer::class,
        SpacesInsideParenthesesFixer::class,
        StatementIndentationFixer::class,
        \PhpCsFixer\Fixer\Basic\BracesPositionFixer::class,
        \PhpCsFixer\Fixer\Casing\ConstantCaseFixer::class,
        \PhpCsFixer\Fixer\ClassNotation\ClassDefinitionFixer::class,
        \PhpCsFixer\Fixer\ControlStructure\ElseifFixer::class,
        \PhpCsFixer\Fixer\ControlStructure\NoBreakCommentFixer::class,
        \PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer::class,
        \PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer::class,
        \PhpCsFixer\Fixer\Operator\UnaryOperatorSpacesFixer::class,
        \PhpCsFixer\Fixer\Semicolon\NoSinglelineWhitespaceBeforeSemicolonsFixer::class,
        \PhpCsFixer\Fixer\Whitespace\IndentationTypeFixer::class,
        \PhpCsFixer\Fixer\Whitespace\LineEndingFixer::class,
        \PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer::class,
        \PhpCsFixer\Fixer\Whitespace\SingleBlankLineAtEofFixer::class,
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
