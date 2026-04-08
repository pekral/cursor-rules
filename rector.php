<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\ClassMethod\NewInInitializerRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/vendor',
        __DIR__ . '/src/ComposerPlugin.php',
        NewInInitializerRector::class,
    ]);

    $rectorConfig->import(__DIR__ . '/vendor/pekral/rector-rules/rector.php');
};
