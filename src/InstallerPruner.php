<?php

declare(strict_types = 1);

namespace Pekral\CursorRules;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Removes files from the install target that no longer exist in the source directory.
 */
final class InstallerPruner
{

    public static function pruneDirectory(string $source, string $targetDir): int
    {
        if (!is_dir($targetDir)) {
            return 0;
        }

        $sourceFiles = array_flip(self::listFiles($source));
        $targetFiles = self::listFiles($targetDir);
        $pruned = 0;

        foreach ($targetFiles as $relativePath) {
            if (isset($sourceFiles[$relativePath])) {
                continue;
            }

            $target = $targetDir . '/' . $relativePath;

            set_error_handler(static fn (): bool => true);
            $deleted = unlink($target);
            restore_error_handler();

            if (!$deleted) {
                continue;
            }

            $pruned++;
            self::removeEmptyDirectories(dirname($target), $targetDir);
        }

        return $pruned;
    }

    private static function removeEmptyDirectories(string $directory, string $stopAt): void
    {
        while ($directory !== $stopAt && is_dir($directory)) {
            $iterator = new FilesystemIterator($directory);

            if ($iterator->valid()) {
                break;
            }

            set_error_handler(static fn (): bool => true);
            $removed = rmdir($directory);
            restore_error_handler();

            if (!$removed) {
                break;
            }

            $directory = dirname($directory);
        }
    }

    /**
     * @return array<int, string>
     */
    private static function listFiles(string $base): array
    {
        if (!is_dir($base)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );
        $files = [];

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo) {
                // @codeCoverageIgnoreStart
                continue;
                // @codeCoverageIgnoreEnd
            }

            $pathname = $file->getPathname();
            $files[] = ltrim(str_replace($base, '', $pathname), '/');
        }

        sort($files);

        return $files;
    }

}
