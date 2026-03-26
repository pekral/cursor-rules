<?php

declare(strict_types = 1);

namespace Pekral\CursorRules;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class Installer
{

    private const string HUMANIZER_URL = 'https://github.com/blader/humanizer';

    private const string HUMANIZER_INSTRUCTION = '- Use [blader/humanizer](https://github.com/blader/humanizer) '
        . 'for all skill outputs to keep the text natural and human-friendly.';

    /**
     * @param array<int, string> $argv
     */
    public static function run(array $argv): int
    {
        $command = $argv[1] ?? 'help';
        $force = in_array('--force', $argv, true);
        $symlink = in_array('--symlink', $argv, true);
        $prune = in_array('--prune', $argv, true);

        try {
            if ($command === 'help') {
                return self::showHelp();
            }

            if ($command !== 'install') {
                fwrite(STDERR, sprintf('Unknown command: %s%s', $command, PHP_EOL));

                return 1;
            }

            $editor = self::parseEditor($argv);

            if ($editor === null) {
                fwrite(STDERR, 'Invalid --editor value. Allowed: cursor, claude, codex, all.' . PHP_EOL);

                return 1;
            }

            return self::install($force, $symlink, $prune, $editor);
        } catch (InstallerFailure $exception) {
            fwrite(STDERR, $exception->getMessage() . PHP_EOL);

            return 1;
        }
    }

    /**
     * @param array<int, string> $argv
     */
    private static function parseEditor(array $argv): ?string
    {
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--editor=')) {
                $value = trim(substr($arg, strlen('--editor=')));
                $value = strtolower($value);

                return in_array($value, InstallerPath::getAllowedEditors(), true) ? $value : null;
            }
        }

        return InstallerPath::EDITOR_CURSOR;
    }

    private static function showHelp(): int
    {
        echo "Usage:\n";
        echo "  vendor/bin/cursor-rules install [--force] [--symlink] [--prune] [--editor=EDITOR]\n\n";
        echo "Options:\n";
        echo "  --force         Overwrite existing files.\n";
        echo "  --symlink       Create symlinks instead of copying (falls back to copy on Windows).\n";
        echo "  --prune         Remove files in target that no longer exist in source.\n";
        echo "  --editor=EDITOR Target editor: cursor (default), claude, codex, all.\n";

        return 0;
    }

    private static function install(bool $force, bool $symlink, bool $prune, string $editor): int
    {
        $root = InstallerPath::resolveProjectRoot();

        $rulesSource = InstallerPath::resolveRulesSource($root);
        [$rulesCopied, $rulesPruned] = self::syncDirectories(
            $rulesSource,
            InstallerPath::resolveRulesTargetDirectories($root, $editor),
            $force,
            $symlink,
            $prune,
        );

        $skillsSource = InstallerPath::resolveSkillsSource($root);
        [$skillsCopied, $skillsPruned] = $skillsSource !== null
            ? self::syncDirectories(
                $skillsSource,
                InstallerPath::resolveSkillsTargetDirectories($root, $editor),
                $force,
                $symlink,
                $prune,
            )
            : [0, 0];

        echo sprintf('Cursor rules installed (%d files, %d pruned).%s', $rulesCopied + $skillsCopied, $rulesPruned + $skillsPruned, PHP_EOL);

        return 0;
    }

    /**
     * @param array<int, string> $targets
     * @return array{int, int}
     */
    private static function syncDirectories(string $source, array $targets, bool $force, bool $symlink, bool $prune): array
    {
        $copied = 0;
        $pruned = 0;

        foreach ($targets as $target) {
            $copied += self::installDirectory($source, $target, $force, $symlink);

            if ($prune) {
                $pruned += InstallerPruner::pruneDirectory($source, $target);
            }
        }

        return [$copied, $pruned];
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

        if (file_exists($dst) && self::isProjectRule($relativePath)) {
            return false;
        }

        $effectiveForce = $force || self::isSecurityRule($relativePath);

        if (file_exists($dst) && !$effectiveForce) {
            return false;
        }

        return self::installFile($src, $dst, $symlink);
    }

    private static function isProjectRule(string $relativePath): bool
    {
        $normalized = str_replace('\\', '/', $relativePath);

        return $normalized === 'project.mdc';
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

        if (self::isSkillDefinitionFile($dst)) {
            self::copySkillDefinitionWithHumanizer($src, $dst);

            return true;
        }

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

    private static function isSkillDefinitionFile(string $path): bool
    {
        return basename($path) === 'SKILL.md';
    }

    private static function copySkillDefinitionWithHumanizer(string $src, string $dst): void
    {
        $sourceContents = file_get_contents($src);

        if ($sourceContents === false) {
            throw InstallerFailure::fileCopyFailed($src, $dst);
        }

        $updatedContents = self::ensureHumanizerInstruction($sourceContents);

        if (file_put_contents($dst, $updatedContents) === false) {
            throw InstallerFailure::fileCopyFailed($src, $dst);
        }
    }

    private static function ensureHumanizerInstruction(string $contents): string
    {
        if (str_contains($contents, self::HUMANIZER_URL)) {
            return $contents;
        }

        return rtrim($contents) . PHP_EOL . PHP_EOL . '## Output Humanization' . PHP_EOL . self::HUMANIZER_INSTRUCTION . PHP_EOL;
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
