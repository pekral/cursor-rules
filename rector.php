<?php

declare(strict_types=1);

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
        // Both rules are deprecated upstream (rector/rector 2.5.9) but still registered by
        // pekral/rector-rules dev-master, and rector-core now hard-errors instead of warning.
        AddParamArrayDocblockFromDataProviderRector::class,
        AddReturnDocblockDataProviderRector::class,
    ]);

    $rectorConfig->import(__DIR__ . '/vendor/pekral/rector-rules/rector.php');
};
