<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\FuncCall\SimplifyRegexPatternRector;
use Rector\CodingStyle\Rector\ClassMethod\BinaryOpStandaloneAssignsToDirectRector;
use Rector\CodingStyle\Rector\Assign\NestedTernaryToMatchRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\TypeDeclarationDocblocks\Rector\ClassMethod\AddReturnDocblockFromMethodCallDocblockRector;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\ClassMethod\NewInInitializerRector;
use Rector\TypeDeclarationDocblocks\Rector\ClassMethod\AddParamArrayDocblockFromDataProviderRector;
use Rector\TypeDeclarationDocblocks\Rector\Class_\AddReturnDocblockDataProviderRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/bin',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/vendor',
        __DIR__ . '/src/ComposerPlugin.php',
        NewInInitializerRector::class,
        // Every rule below is deprecated upstream but still registered by
        // pekral/rector-rules dev-master. rector-core hard-errors on a deprecated
        // registered rule, and one unusable rule fails the whole run, so the set is
        // skipped wholesale rather than one release at a time.
        AddParamArrayDocblockFromDataProviderRector::class,
        AddReturnDocblockDataProviderRector::class,
        SimplifyRegexPatternRector::class,
        BinaryOpStandaloneAssignsToDirectRector::class,
        AddReturnDocblockFromMethodCallDocblockRector::class,
        EncapsedStringsToSprintfRector::class,
        NestedTernaryToMatchRector::class,
    ]);

    $rectorConfig->import(__DIR__ . '/vendor/pekral/rector-rules/rector.php');
};
