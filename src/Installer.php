<?php

declare(strict_types = 1);

namespace Pekral\CursorRules;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class Installer
{

    public static function run(array $argv): int
    {
        $cmd = $argv[1] ?? 'help';
        $force = in_array('--force', $argv, true);
        $symlink = in_array('--symlink', $argv, true);

        $root = self::findProjectRoot();
        $vendorSource = $root . '/vendor/pekral/cursor-rules/.cursor/rules';
        $targetDir = $root . '/.cursor/rules';

        if (!is_dir($vendorSource)) {
            fwrite(STDERR, sprintf('Source not found: %s%s', $vendorSource, PHP_EOL));

            return 1;
        }

        if ($cmd === 'help') {
            echo "Usage:\n";
            echo "  vendor/bin/cursor-rules install [--force] [--symlink]\n\n";
            echo "Options:\n";
            echo "  --force    Overwrite existing files.\n";
            echo "  --symlink  Create symlinks instead of copying (falls back to copy on Windows).\n";

            return 0;
        }

        if ($cmd !== 'install') {
            fwrite(STDERR, sprintf('Unknown command: %s%s', $cmd, PHP_EOL));

            return 1;
        }

        if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
            fwrite(STDERR, sprintf('Cannot create target directory: %s%s', $targetDir, PHP_EOL));

            return 1;
        }

        $files = self::listFiles($vendorSource);
        $copied = 0;

        foreach ($files as $relPath) {
            $src = $vendorSource . '/' . $relPath;
            $dst = $targetDir . '/' . $relPath;

            if (!is_dir(dirname($dst)) && !mkdir(dirname($dst), 0777, true) && !is_dir(dirname($dst))) {
                fwrite(STDERR, 'Cannot create directory: ' . dirname($dst) . "\n");

                continue;
            }

            if (file_exists($dst) && !$force) {
                // leave existing file intact
                continue;
            }

            if ($symlink && self::canSymlink()) {
                @unlink($dst);

                if (@symlink($src, $dst) === false) {
                    // fallback to copy on failure
                    self::copy($src, $dst);
                }
            } else {
                self::copy($src, $dst);
            }

            $copied++;
        }

        echo "Cursor rules installed to {$targetDir} ({$copied} files).\n";

        return 0;
    }

    private static function findProjectRoot(): string
    {
        // Walk up until composer.json is found (simple heuristic)
        $dir = getcwd();

        while ($dir !== '/' && $dir !== '' && !file_exists($dir . '/composer.json')) {
            $dir = dirname($dir);
        }

        return $dir ?: getcwd();
    }

    private static function listFiles(string $base): array
    {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
        );
        $files = [];

        foreach ($it as $file) {
            if ($file->isFile()) {
                $files[] = ltrim(str_replace($base, '', $file->getPathname()), '/');
            }
        }

        sort($files);

        return $files;
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
