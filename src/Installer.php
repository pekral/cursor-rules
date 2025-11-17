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

            return self::installRules($force, $symlink);
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

    private static function installRules(bool $force, bool $symlink): int
    {
        $root = self::resolveProjectRoot();
        $source = self::resolveRulesSource($root);
        $targetDir = self::resolveTargetDirectory($root);

        self::ensureDirectoryExists($targetDir);
        self::replicateDirectories($source, $targetDir);

        $files = self::listFiles($source);
        $copied = self::processFiles($files, $source, $targetDir, $force, $symlink);

        echo sprintf('Cursor rules installed to %s (%d files).%s', $targetDir, $copied, PHP_EOL);

        return 0;
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

        if (file_exists($dst) && !$force) {
            return false;
        }

        return self::installFile($src, $dst, $symlink);
    }

    private static function ensureDirectoryExists(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (is_file($directory)) {
            throw InstallerFailure::directoryCreationFailed($directory);
        }

        set_error_handler(static fn (): bool => true);
        $created = mkdir($directory, 0777, true);
        restore_error_handler();

        if (!$created && !is_dir($directory)) {
            throw InstallerFailure::directoryCreationFailed($directory);
        }
    }

    private static function installFile(string $src, string $dst, bool $symlink): bool
    {
        self::removeExistingTarget($dst);

        if ($symlink && self::canSymlink()) {
            if (self::shouldForceSymlinkFailure() || !symlink($src, $dst)) {
                self::copy($src, $dst);
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

        set_error_handler(static fn (): bool => true);
        $deleted = unlink($destination);
        restore_error_handler();

        if ($deleted === false) {
            throw InstallerFailure::removalFailed($destination);
        }
    }

    private static function findProjectRoot(): string
    {
        $dir = getcwd();

        if ($dir === false) {
            $dir = self::fallbackProjectRoot();
        }

        while ($dir !== '' && !self::isFilesystemRoot($dir) && !file_exists($dir . '/composer.json')) {
            $parentDir = dirname($dir);
            $dir = $parentDir;
        }

        return $dir;
    }

    /**
     * @return array<int, string>
     */
    private static function listFiles(string $base): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
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
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
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
        if (self::shouldForceCopyFailure() || !copy($src, $dst)) {
            throw InstallerFailure::fileCopyFailed($src, $dst);
        }
    }

    private static function canSymlink(): bool
    {
        if (self::shouldDisableSymlinks()) {
            return false;
        }

        if (self::isWindowsEnvironment()) {
            return false;
        }

        return function_exists('symlink');
    }

    private static function shouldDisableSymlinks(): bool
    {
        return self::isTruthyFlag('CURSOR_RULES_DISABLE_SYMLINKS');
    }

    private static function resolveRulesSource(string $root): string
    {
        $developmentSource = $root . '/rules';

        if (is_dir($developmentSource)) {
            return $developmentSource;
        }

        $vendorSource = $root . '/vendor/pekral/cursor-rules/rules';

        if (is_dir($vendorSource)) {
            return $vendorSource;
        }

        throw InstallerFailure::missingSource($developmentSource, $vendorSource);
    }

    private static function resolveTargetDirectory(string $root): string
    {
        $override = getenv('CURSOR_RULES_TARGET_DIR');

        if (is_string($override) && $override !== '') {
            return $override;
        }

        return $root . '/.cursor/rules';
    }

    private static function resolveProjectRoot(): string
    {
        $override = getenv('CURSOR_RULES_PROJECT_ROOT');

        if (is_string($override) && $override !== '') {
            return $override;
        }

        return self::findProjectRoot();
    }

    private static function fallbackProjectRoot(): string
    {
        $override = getenv('CURSOR_RULES_PROJECT_ROOT_FALLBACK');

        if (is_string($override) && $override !== '') {
            return $override;
        }

        return sys_get_temp_dir();
    }

    private static function isFilesystemRoot(string $path): bool
    {
        if ($path === '' || $path === DIRECTORY_SEPARATOR) {
            return true;
        }

        return preg_match('/^[A-Za-z]:\\\\?$/', $path) === 1;
    }

    private static function shouldForceSymlinkFailure(): bool
    {
        return self::isTruthyFlag('CURSOR_RULES_FAIL_SYMLINK');
    }

    private static function shouldForceCopyFailure(): bool
    {
        return self::isTruthyFlag('CURSOR_RULES_FAIL_COPY');
    }

    private static function isWindowsEnvironment(): bool
    {
        if (self::isTruthyFlag('CURSOR_RULES_FORCE_WINDOWS')) {
            return true;
        }

        return stripos(PHP_OS, 'WIN') === 0;
    }

    private static function isTruthyFlag(string $flag): bool
    {
        $value = getenv($flag);

        if ($value === false) {
            return false;
        }

        return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
    }

}
