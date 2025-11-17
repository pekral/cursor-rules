<?php

declare(strict_types = 1);

use Pekral\CursorRules\Installer;

function installerEnsureDirectory(string $directory): void
{
    if (is_dir($directory)) {
        return;
    }

    mkdir($directory, 0777, true);
}

function installerCreateProjectRoot(?string $baseDir = null): string
{
    $base = $baseDir ?? sys_get_temp_dir();
    $root = rtrim($base, DIRECTORY_SEPARATOR) . '/cursor-rules-' . bin2hex(random_bytes(4));
    installerEnsureDirectory($root);
    file_put_contents($root . '/composer.json', '{}');

    return $root;
}

function installerCreateWorkingDirectory(string $root): string
{
    $workingDirectory = $root . '/nested/workdir';
    installerEnsureDirectory($workingDirectory);

    return $workingDirectory;
}

function installerWriteFile(string $path, string $content): void
{
    $directory = dirname($path);
    installerEnsureDirectory($directory);
    file_put_contents($path, $content);
}

function installerTargetDirectoryFor(string $root): string
{
    return $root . '/cursor-target';
}

/**
 * @param array<int, string> $arguments
 */
function installerRunInstallerFrom(string $directory, array $arguments): int
{
    $original = getcwd();
    $original = $original === false ? '' : $original;
    chdir($directory);
    ob_start();
    $exitCode = Installer::run($arguments);
    ob_end_clean();

    if ($original !== '') {
        chdir($original);
    }

    return $exitCode;
}

function installerRemoveDirectory(string $directory): void
{
    if (is_file($directory)) {
        unlink($directory);

        return;
    }

    if (!is_dir($directory)) {
        return;
    }

    /** @var \RecursiveIteratorIterator<\RecursiveDirectoryIterator> $iterator */
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo instanceof SplFileInfo) {
            continue;
        }

        if ($fileInfo->isDir()) {
            rmdir($fileInfo->getPathname());

            continue;
        }

        unlink($fileInfo->getPathname());
    }

    rmdir($directory);
}

function installerSymlinkUnsupported(): bool
{
    return !function_exists('symlink') || stripos(PHP_OS, 'WIN') === 0;
}

function installerSupportsCursorDirectoryCreation(): bool
{
    $temporaryRoot = sys_get_temp_dir() . '/cursor-hidden-check-' . bin2hex(random_bytes(4));
    installerEnsureDirectory($temporaryRoot);
    $cursorRulesDir = $temporaryRoot . '/.cursor/rules';
    set_error_handler(static fn (): bool => true);
    $created = mkdir($cursorRulesDir, 0777, true);
    restore_error_handler();
    installerRemoveDirectory($temporaryRoot);

    return $created === true;
}
