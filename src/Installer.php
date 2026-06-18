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
        $normalizedArgv = InstallerPath::normalizeCliArguments($argv);
        $command = $normalizedArgv[1] ?? 'help';
        $options = InstallOptions::fromArgv($normalizedArgv);

        try {
            if ($command === 'help') {
                return self::showHelp();
            }

            if ($command !== 'install') {
                fwrite(STDERR, sprintf('Unknown command: %s%s', $command, PHP_EOL));

                return 1;
            }

            $editor = self::parseEditor($normalizedArgv);

            if ($editor === null) {
                fwrite(STDERR, 'Missing or invalid --editor value. Allowed: cursor, claude, codex, all.' . PHP_EOL);

                return 1;
            }

            return self::install($editor, $options);
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

        return null;
    }

    private static function showHelp(): int
    {
        echo "Usage:\n";
        echo "  vendor/bin/cursor-rules install --editor=EDITOR [--force] [--symlink] [--prune] [--allow-bundled-scripts] [--allow-subagent-writes]\n\n";
        echo "Options:\n  --editor=EDITOR         Target editor (required): cursor, claude, codex, all.\n";
        echo "  --force                 Overwrite existing files.\n";
        echo "  --symlink               Create symlinks instead of copying (falls back to copy on Windows).\n";
        echo "  --prune                 Remove files in target that no longer exist in source.\n";
        echo "  --allow-bundled-scripts Whitelist bundled scripts (load-issue.sh) in ~/.claude/settings.json. Opt-in; --editor=claude/all only.\n";
        echo "  --allow-subagent-writes Allow dispatched-subagent file writes by adding scoped Edit/Write entries for the project\n";
        echo "                          tree to permissions.allow in .claude/settings.local.json. Opt-in; --editor=claude/all only.\n";

        return 0;
    }

    private static function install(string $editor, InstallOptions $options): int
    {
        $root = InstallerPath::resolveProjectRoot();
        [$copied, $pruned] = self::runAllSyncs(self::collectSyncPayloads($root, $editor), $options->force, $options->symlink, $options->prune);

        $claudeMdSource = InstallerPath::isClaudeMdEditor($editor) ? InstallerPath::resolveClaudeMdSource() : null;
        $copied += self::installSingleFile($claudeMdSource, InstallerPath::resolveClaudeMdTarget($root));
        $permissionsAdded = InstallerClaudeSettings::applyIfRequested($options->allowBundledScripts, $editor);
        $coAuthoredByDisabled = InstallerClaudeSettings::applyCoAuthoredByPreference($editor);
        $subagentWritesEnabled = InstallerClaudeSettings::applySubagentWritesIfRequested($options->allowSubagentWrites, $editor, $root);

        self::reportInstallSummary($copied, $pruned, $permissionsAdded, $coAuthoredByDisabled);

        if ($subagentWritesEnabled) {
            echo sprintf('Allowed subagent file writes (Edit/Write on the working tree) in .claude/settings.local.json.%s', PHP_EOL);
        }

        return 0;
    }

    /**
     * @return array<int, array{0: string, 1: array<int, string>}>
     */
    private static function collectSyncPayloads(string $root, string $editor): array
    {
        $payloads = [
            [InstallerPath::resolveRulesSource($root), InstallerPath::resolveRulesTargetDirectories($root, $editor)],
        ];

        $skillsSource = InstallerPath::resolveSkillsSource();

        if ($skillsSource !== null) {
            $payloads[] = [$skillsSource, InstallerPath::resolveSkillsTargetDirectories($root, $editor)];
        }

        $agentsSource = InstallerPath::resolveAgentsSource();
        $agentsTargets = InstallerPath::resolveAgentsTargetDirectories($root, $editor);

        if ($agentsSource !== null && $agentsTargets !== []) {
            $payloads[] = [$agentsSource, $agentsTargets];
        }

        return $payloads;
    }

    /**
     * @param array<int, array{0: string, 1: array<int, string>}> $payloads
     * @return array{int, int}
     */
    private static function runAllSyncs(array $payloads, bool $force, bool $symlink, bool $prune): array
    {
        $totalCopied = 0;
        $totalPruned = 0;

        foreach ($payloads as [$source, $targets]) {
            [$copied, $prunedCount] = self::syncDirectories($source, $targets, $force, $symlink, $prune);
            $totalCopied += $copied;
            $totalPruned += $prunedCount;
        }

        return [$totalCopied, $totalPruned];
    }

    private static function reportInstallSummary(int $copied, int $pruned, int $permissionsAdded, bool $coAuthoredByDisabled): void
    {
        echo sprintf('Cursor rules installed (%d files, %d pruned).%s', $copied, $pruned, PHP_EOL);

        if ($permissionsAdded > 0) {
            echo sprintf('Allowed %d bundled-script permission(s) in ~/.claude/settings.json.%s', $permissionsAdded, PHP_EOL);
        }

        if ($coAuthoredByDisabled) {
            echo sprintf('Disabled AI co-author attribution (includeCoAuthoredBy: false) in ~/.claude/settings.json.%s', PHP_EOL);
        }
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
        InstallerPath::ensureDirectory($targetDir);
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

        InstallerPath::ensureDirectory($dirName);

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
            self::preserveExecutableBit($src, $dst);
        }

        InstallerHumanizer::appendIfNeeded($dst);

        return true;
    }

    private static function preserveExecutableBit(string $src, string $dst): void
    {
        $mode = fileperms($src);

        if ($mode === false || ($mode & 0111) === 0) {
            return;
        }

        set_error_handler(static fn (): bool => true);
        chmod($dst, ($mode & 0777) | 0111);
        restore_error_handler();
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

        // @codeCoverageIgnoreStart
        if ($deleted === false) {
            throw InstallerFailure::removalFailed($destination);
        }
        // @codeCoverageIgnoreEnd
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

            InstallerPath::ensureDirectory($targetDir . '/' . $relativePath);
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

    private static function installSingleFile(?string $source, string $target): int
    {
        if ($source === null || file_exists($target)) {
            return 0;
        }

        return self::installFile($source, $target, false) ? 1 : 0;
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
