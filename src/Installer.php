<?php

declare(strict_types = 1);

namespace Pekral\CursorRules;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class Installer
{

    /**
     * @param array<int, string> $argv
     */
    public static function run(array $argv): int
    {
        $command = $argv[1] ?? 'help';
        $force = in_array('--force', $argv, true);
        $symlink = in_array('--symlink', $argv, true);

        try {
            if ($command === 'help') {
                return self::showHelp();
            }

            if ($command !== 'install') {
                fwrite(STDERR, sprintf('Unknown command: %s%s', $command, PHP_EOL));

                return 1;
            }

            return self::install($force, $symlink);
        } catch (InstallerFailure $exception) {
            fwrite(STDERR, $exception->getMessage() . PHP_EOL);

            return 1;
        }
    }

    private static function showHelp(): int
    {
        echo "Usage:\n";
        echo "  vendor/bin/cursor-rules install [--force] [--symlink]\n\n";
        echo "Options:\n";
        echo "  --force    Overwrite existing files.\n";
        echo "  --symlink  Create symlinks instead of copying (falls back to copy on Windows).\n";

        return 0;
    }

    private static function install(bool $force, bool $symlink): int
    {
        $root = InstallerPath::resolveProjectRoot();
        $totalCopied = 0;

        $rulesSource = InstallerPath::resolveRulesSource($root);
        $rulesTarget = InstallerPath::resolveTargetDirectory($root);
        $totalCopied += self::installDirectory($rulesSource, $rulesTarget, $force, $symlink);

        $skillsSource = InstallerPath::resolveSkillsSource($root);

        if ($skillsSource !== null) {
            $skillsTarget = InstallerPath::resolveSkillsTargetDirectory($root);
            $totalCopied += self::installDirectory($skillsSource, $skillsTarget, $force, $symlink);
        }

        echo sprintf('Cursor rules installed (%d files).%s', $totalCopied, PHP_EOL);

        return 0;
    }

    private static function installDirectory(string $source, string $targetDir, bool $force, bool $symlink): int
    {
        self::ensureDirectoryExists($targetDir);
        self::replicateDirectories($source, $targetDir);

        $files = self::listFiles($source);

        return self::processFiles($files, $source, $targetDir, $force, $symlink);
    }

    /**
     * @param array<int, string> $files
     */
    private static function processFiles(array $files, string $source, string $targetDir, bool $force, bool $symlink): int
    {
        return array_reduce(
            $files,
            static fn (int $copied, string $relativePath): int => $copied + (self::shouldProcessFile(
                $relativePath,
                $source,
                $targetDir,
                $force,
                $symlink,
            ) ? 1 : 0),
            0,
        );
    }

    private static function shouldProcessFile(string $relativePath, string $source, string $targetDir, bool $force, bool $symlink): bool
    {
        $src = $source . '/' . $relativePath;
        $dst = $targetDir . '/' . $relativePath;
        $dirName = dirname($dst);

        self::ensureDirectoryExists($dirName);

        $effectiveForce = $force || self::isSecurityRule($relativePath);

        if (file_exists($dst) && !$effectiveForce) {
            return false;
        }

        return self::installFile($src, $dst, $symlink);
    }

    private static function isSecurityRule(string $relativePath): bool
    {
        return str_starts_with($relativePath, 'security/') || str_starts_with($relativePath, 'security\\');
    }

    private static function ensureDirectoryExists(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (is_file($directory)) {
            throw InstallerFailure::directoryCreationFailed($directory);
        }

        $created = self::runWithErrorSuppression(static fn (): bool => mkdir($directory, 0777, true));

        if (!$created && !is_dir($directory)) {
            throw InstallerFailure::directoryCreationFailed($directory);
        }
    }

    private static function installFile(string $src, string $dst, bool $symlink): bool
    {
        self::removeExistingTarget($dst);

        if ($symlink && self::canSymlink()) {
            if (!symlink($src, $dst)) {
                // @codeCoverageIgnoreStart
                self::copy($src, $dst);
                // @codeCoverageIgnoreEnd
            }
        } else {
            self::copy($src, $dst);
        }

        return true;
    }

    private static function removeExistingTarget(string $destination): void
    {
        if (!file_exists($destination)) {
            return;
        }

        if (is_dir($destination)) {
            throw InstallerFailure::removalFailed($destination);
        }

        $deleted = self::runWithErrorSuppression(static fn (): bool => unlink($destination));

        if ($deleted === false) {
            throw InstallerFailure::removalFailed($destination);
        }
    }

    /**
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    private static function runWithErrorSuppression(callable $fn): mixed
    {
        set_error_handler(static fn (): bool => true);

        try {
            return $fn();
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @return array<int, string>
     */
    private static function listFiles(string $base): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $base,
                FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS,
            ),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );
        $files = [];

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $files[] = self::extractFilePath($file, $base);
        }

        sort($files);

        return $files;
    }

    private static function replicateDirectories(string $source, string $targetDir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $source,
                FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS,
            ),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $directory) {
            if (!$directory instanceof SplFileInfo || !$directory->isDir()) {
                continue;
            }

            $relativePath = self::extractFilePath($directory, $source);

            self::ensureDirectoryExists($targetDir . '/' . $relativePath);
        }
    }

    private static function extractFilePath(SplFileInfo $file, string $base): string
    {
        $pathname = $file->getPathname();

        return ltrim(str_replace($base, '', $pathname), '/');
    }

    private static function copy(string $src, string $dst): void
    {
        if (!copy($src, $dst)) {
            throw InstallerFailure::fileCopyFailed($src, $dst);
        }
    }

    private static function canSymlink(): bool
    {
        if (stripos(PHP_OS, 'WIN') === 0) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }

        return function_exists('symlink');
    }

}
