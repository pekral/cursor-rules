#!/usr/bin/env php
<?php

declare(strict_types = 1);

// Thin CLI wrapper around Pekral\CursorRules\CoverageDiffCheck — exits 0 when every changed line is covered,
// 1 when a gap is detected, 2 for environment / input problems.

$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
    __DIR__ . '/../../../../autoload.php',
];

$autoloadLoaded = false;

foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require $autoloadPath;
        $autoloadLoaded = true;

        break;
    }
}

if (!$autoloadLoaded) {
    fwrite(STDERR, "coverage-diff-check: Composer autoloader not found.\n");
    exit(2);
}

use Pekral\CursorRules\CoverageDiffCheck;

$cloverPath = $argv[1] ?? null;
$baseRef = $argv[2] ?? null;

if (!is_string($cloverPath) || $cloverPath === '' || !is_file($cloverPath)) {
    fwrite(STDERR, "coverage-diff-check: usage: coverage-diff-check.php <clover.xml> <base-ref>\n");
    exit(2);
}

if (!is_string($baseRef) || $baseRef === '') {
    fwrite(STDERR, "coverage-diff-check: usage: coverage-diff-check.php <clover.xml> <base-ref>\n");
    exit(2);
}

$projectRoot = rtrim((string) shell_exec('git rev-parse --show-toplevel'));

if ($projectRoot === '') {
    fwrite(STDERR, "coverage-diff-check: not inside a git repository\n");
    exit(2);
}

$changedLines = CoverageDiffCheck::discoverChangedLines($baseRef);

if ($changedLines === []) {
    echo "📊 No changed PHP lines detected — diff-scoped coverage clean.\n";
    exit(0);
}

$cloverXml = file_get_contents($cloverPath);

if ($cloverXml === false) {
    fwrite(STDERR, sprintf('coverage-diff-check: failed to read clover XML: %s%s', $cloverPath, PHP_EOL));
    exit(2);
}

try {
    $gaps = CoverageDiffCheck::findGapsInClover($cloverXml, $changedLines, $projectRoot);
} catch (Throwable $exception) {
    fwrite(STDERR, 'coverage-diff-check: ' . $exception->getMessage() . "\n");
    exit(2);
}

if ($gaps === []) {
    echo '✅ Diff-scoped coverage: 100% for every changed PHP line (' . count($changedLines) . " file(s) inspected).\n";
    exit(0);
}

echo "❌ Diff-scoped coverage gap — the following changed lines are NOT covered:\n";

foreach ($gaps as $file => $lines) {
    echo sprintf('  · %s: ', $file) . implode(', ', $lines) . "\n";
}

exit(1);
