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
        $cmd = $argv[1] ?? 'help';
        $force = in_array('--force', $argv, true);
        $symlink = in_array('--symlink', $argv, true);

        if ($cmd === 'help') {
            return self::showHelp();
        }

        if ($cmd !== 'install') {
            fwrite(STDERR, sprintf('Unknown command: %s%s', $cmd, PHP_EOL));

            return 1;
        }

        return self::installRules($force, $symlink);
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
        $root = self::findProjectRoot();
        
        // Check if we're in development mode (rules/ folder exists in project root)
        $devSource = $root . '/rules';
        $vendorSource = $root . '/vendor/pekral/cursor-rules/.cursor/rules';
        
        // Use development source if it exists, otherwise fall back to vendor source
        $source = is_dir($devSource) ? $devSource : $vendorSource;
        $targetDir = $root . '/.cursor/rules';

        if (!is_dir($source)) {
            fwrite(STDERR, sprintf('Source not found: %s%s', $source, PHP_EOL));
            fwrite(STDERR, 'Make sure you have either a rules/ folder in your project root or the package is installed via Composer.' . PHP_EOL);

            return 1;
        }

        if (!self::ensureTargetDirectory($targetDir)) {
            return 1;
        }

        $files = self::listFiles($source);
        $copied = self::processFiles($files, $source, $targetDir, $force, $symlink);

        echo "Cursor rules installed to {$targetDir} ({$copied} files).\n";

        return 0;
    }

    private static function ensureTargetDirectory(string $targetDir): bool
    {
        if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
            fwrite(STDERR, sprintf('Cannot create target directory: %s%s', $targetDir, PHP_EOL));

            return false;
        }

        return true;
    }

    /**
     * @param array<string> $files
     */
    private static function processFiles(array $files, string $vendorSource, string $targetDir, bool $force, bool $symlink): int
    {
        $copied = 0;

        foreach ($files as $relPath) {
            if (self::shouldProcessFile($relPath, $vendorSource, $targetDir, $force, $symlink)) {
                $copied += 1;
            }
        }

        return $copied;
    }

    private static function shouldProcessFile(string $relPath, string $vendorSource, string $targetDir, bool $force, bool $symlink): bool
    {
        $src = $vendorSource . '/' . $relPath;
        $dst = $targetDir . '/' . $relPath;

        $dirName = dirname($dst);

        if ($dirName === '') {
            return false;
        }

        if (!self::ensureDirectoryExists($dirName)) {
            return false;
        }

        if (file_exists($dst) && !$force) {
            return false;
        }

        return self::installFile($src, $dst, $symlink);
    }

    private static function ensureDirectoryExists(string $dir): bool
    {
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            fwrite(STDERR, 'Cannot create directory: ' . $dir . "\n");

            return false;
        }

        return true;
    }

    private static function installFile(string $src, string $dst, bool $symlink): bool
    {
        if ($symlink && self::canSymlink()) {
            @unlink($dst);

            if (@symlink($src, $dst) === false) {
                self::copy($src, $dst);
            }
        } else {
            self::copy($src, $dst);
        }

        return true;
    }

    private static function findProjectRoot(): string
    {
        // Walk up until composer.json is found (simple heuristic)
        $dir = getcwd();

        if ($dir === false) {
            $dir = sys_get_temp_dir();
        }

        while ($dir !== '/' && $dir !== '' && !file_exists($dir . '/composer.json')) {
            $parentDir = dirname($dir);

            if ($parentDir === $dir) {
                break;
            }

            $dir = $parentDir;
        }

        return $dir;
    }

    /**
     * @return array<string>
     */
    private static function listFiles(string $base): array
    {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
        );
        $files = [];

        foreach ($it as $file) {
            if ($file instanceof SplFileInfo) {
                $filePath = self::processFileItem($file, $base);

                if ($filePath !== null) {
                    $files[] = $filePath;
                }
            }
        }

        sort($files);

        return $files;
    }

    private static function processFileItem(SplFileInfo $file, string $base): ?string
    {
        return self::extractFilePath($file, $base);
    }

    private static function extractFilePath(SplFileInfo $file, string $base): ?string
    {
        if (!$file->isFile()) {
            return null;
        }

        $pathname = $file->getPathname();

        return ltrim(str_replace($base, '', $pathname), '/');
    }

    private static function copy(string $src, string $dst): void
    {
        copy($src, $dst);
    }

    private static function canSymlink(): bool
    {
        if (stripos(PHP_OS, 'WIN') === 0) {
            // keep it simple on Windows
            return false;
        }

        return function_exists('symlink');
    }

}
