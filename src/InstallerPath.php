<?php

declare(strict_types = 1);

namespace Pekral\CursorRules;

final class InstallerPath
{

    public const string EDITOR_CURSOR = 'cursor';

    public const string EDITOR_CLAUDE = 'claude';

    public const string EDITOR_CODEX = 'codex';

    public const string EDITOR_ALL = 'all';

    /**
     * @return array<int, string>
     */
    public static function getAllowedEditors(): array
    {
        return [self::EDITOR_CURSOR, self::EDITOR_CLAUDE, self::EDITOR_CODEX, self::EDITOR_ALL];
    }

    public static function resolveProjectRoot(): string
    {
        return self::findProjectRoot();
    }

    /**
     * Splits combined CLI flags (e.g. --force--editor=claude) into separate arguments.
     *
     * @param array<int, string> $argv
     * @return array<int, string>
     */
    public static function normalizeCliArguments(array $argv): array
    {
        $rawArguments = implode(' ', $argv);
        $parts = preg_split('/\s+|(?=--(?:force|symlink|prune|editor=))/', trim($rawArguments), -1, PREG_SPLIT_NO_EMPTY);

        return is_array($parts) && $parts !== [] ? $parts : $argv;
    }

    public static function resolveRulesSource(string $root): string
    {
        $developmentSource = $root . '/rules';

        if (is_dir($developmentSource)) {
            return $developmentSource;
        }

        $packageSource = self::getPackageDirectory() . '/rules';

        if (is_dir($packageSource)) {
            return $packageSource;
        }

        // @codeCoverageIgnoreStart
        throw InstallerFailure::missingSource($developmentSource, $packageSource);
        // @codeCoverageIgnoreEnd
    }

    public static function resolveSkillsSource(string $root): ?string
    {
        $developmentSource = $root . '/skills';

        if (is_dir($developmentSource)) {
            return $developmentSource;
        }

        $packageSource = self::getPackageDirectory() . '/skills';

        if (is_dir($packageSource)) {
            return $packageSource;
        }

        // @codeCoverageIgnoreStart
        return null;
        // @codeCoverageIgnoreEnd
    }

    public static function resolveTargetDirectory(string $root): string
    {
        return $root . '/.cursor/rules';
    }

    public static function resolveSkillsTargetDirectory(string $root): string
    {
        return $root . '/.cursor/skills';
    }

    /**
     * Rules target directories for the given editor.
     *
     * @return array<int, string>
     */
    public static function resolveRulesTargetDirectories(string $root, string $editor): array
    {
        $editor = strtolower($editor);

        if ($editor === self::EDITOR_ALL) {
            return [
                $root . '/.cursor/rules',
                $root . '/.claude/rules',
                $root . '/.codex/rules',
            ];
        }

        $baseDir = match ($editor) {
            self::EDITOR_CURSOR => '.cursor',
            self::EDITOR_CLAUDE => '.claude',
            self::EDITOR_CODEX => '.codex',
            default => '.cursor',
        };

        return [$root . '/' . $baseDir . '/rules'];
    }

    /**
     * Skill target directories for the given editor.
     * Includes user home paths for claude/codex when HOME or USERPROFILE is set.
     *
     * @return array<int, string>
     */
    public static function resolveSkillsTargetDirectories(string $root, string $editor): array
    {
        $editor = strtolower($editor);
        $home = self::resolveHomeDirectory();

        if ($editor === self::EDITOR_ALL) {
            $targets = [
                $root . '/.cursor/skills',
                $root . '/.claude/skills',
                $root . '/.codex/skills',
            ];
            $targets = self::appendHomeSkillPaths($targets, $home);

            return array_values(array_unique($targets));
        }

        $baseDir = match ($editor) {
            self::EDITOR_CURSOR => '.cursor',
            self::EDITOR_CLAUDE => '.claude',
            self::EDITOR_CODEX => '.codex',
            default => '.cursor',
        };

        $targets = [$root . '/' . $baseDir . '/skills'];
        $targets = self::appendHomeSkillPathForEditor($targets, $home, $baseDir, $editor);

        return array_values(array_unique($targets));
    }

    /**
     * All skill target directories for Cursor/Claude/Codex compatibility (editor=all).
     *
     * @return array<int, string>
     */
    public static function resolveAllSkillsTargetDirectories(string $root): array
    {
        return self::resolveSkillsTargetDirectories($root, self::EDITOR_ALL);
    }

    private static function resolveHomeDirectory(): string|false
    {
        $homeEnv = getenv('HOME');

        return $homeEnv !== false && $homeEnv !== '' ? $homeEnv : getenv('USERPROFILE');
    }

    /**
     * @param array<int, string> $targets
     * @return array<int, string>
     */
    private static function appendHomeSkillPaths(array $targets, string|false $home): array
    {
        if ($home === false || $home === '') {
            return $targets;
        }

        $targets[] = $home . '/.claude/skills';
        $targets[] = $home . '/.codex/skills';

        return $targets;
    }

    /**
     * @param array<int, string> $targets
     * @return array<int, string>
     */
    private static function appendHomeSkillPathForEditor(array $targets, string|false $home, string $baseDir, string $editor): array
    {
        if ($home === false || $home === '') {
            return $targets;
        }

        if ($editor !== self::EDITOR_CLAUDE && $editor !== self::EDITOR_CODEX) {
            return $targets;
        }

        $targets[] = $home . '/' . $baseDir . '/skills';

        return $targets;
    }

    private static function getPackageDirectory(): string
    {
        return dirname(__DIR__);
    }

    private static function findProjectRoot(): string
    {
        $dir = getcwd();

        if ($dir === false) {
            // @codeCoverageIgnoreStart
            return sys_get_temp_dir();
            // @codeCoverageIgnoreEnd
        }

        while ($dir !== '' && !self::isFilesystemRoot($dir) && !file_exists($dir . '/composer.json')) {
            $dir = dirname($dir);
        }

        return $dir;
    }

    private static function isFilesystemRoot(string $path): bool
    {
        if ($path === '' || $path === DIRECTORY_SEPARATOR) {
            return true;
        }

        return preg_match('/^[A-Za-z]:\\\\?$/', $path) === 1;
    }

}
