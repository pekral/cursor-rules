<?php

declare(strict_types = 1);

namespace Pekral\CursorRules;

use JsonException;

final class InstallerClaudeSettings
{

    /**
     * Bundled scripts that are safe to run without per-call confirmation.
     * Patterns match both project-local (`.claude/skills/.../scripts/...`) and
     * home (`~/.claude/skills/.../scripts/...`) install locations.
     *
     * @return array<int, string>
     */
    public static function getBundledScriptPermissions(): array
    {
        return [
            'Bash(*skills/code-review-github/scripts/load-issue.sh:*)',
            'Bash(*skills/code-review-jira/scripts/load-issue.sh:*)',
        ];
    }

    public static function resolveSettingsPath(string $home): string
    {
        return $home . '/.claude/settings.json';
    }

    /**
     * Applies bundled-script permissions only when the caller opted in
     * (`--allow-bundled-scripts`), the editor is `claude` or `all`, and a
     * usable home directory is available. Returns the number of entries newly
     * added; 0 in every other case.
     */
    public static function applyIfRequested(bool $allowBundledScripts, string $editor): int
    {
        if (!$allowBundledScripts || !InstallerPath::isClaudeMdEditor($editor)) {
            return 0;
        }

        $home = InstallerPath::resolveHomeDirectoryOrNull();

        if ($home === null) {
            return 0;
        }

        return self::ensureBundledScriptPermissions($home);
    }

    /**
     * Reads the `permissions.allow` list from `<home>/.claude/settings.json`,
     * sanitised to strings only. Returns an empty list when the file does not
     * exist or the section is missing.
     *
     * @return array<int, string>
     */
    public static function loadAllowList(string $home): array
    {
        $settingsPath = self::resolveSettingsPath($home);
        $data = self::readSettings($settingsPath);

        return self::extractAllow($data);
    }

    /**
     * Adds the bundled-script permission entries to the user's Claude settings file
     * idempotently. Returns the number of entries newly added (0 when nothing changed).
     */
    public static function ensureBundledScriptPermissions(string $home): int
    {
        $settingsPath = self::resolveSettingsPath($home);
        $existing = self::readSettings($settingsPath);
        $existingAllow = self::extractAllow($existing);
        $merged = self::mergePermissions($existing);
        $mergedAllow = self::extractAllow($merged);

        $added = count($mergedAllow) - count($existingAllow);

        if ($added === 0) {
            return 0;
        }

        InstallerPath::ensureDirectory(dirname($settingsPath));
        self::writeSettings($settingsPath, $merged);

        return $added;
    }

    /**
     * @return array<string, mixed>
     */
    private static function readSettings(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $contents = file_get_contents($path);

        if ($contents === false || trim($contents) === '') {
            return [];
        }

        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw InstallerFailure::settingsJsonInvalid($path, $exception->getMessage());
        }

        if (!is_array($data)) {
            throw InstallerFailure::settingsJsonInvalid($path, 'top-level value is not an object');
        }

        $normalized = [];

        foreach ($data as $key => $value) {
            $normalized[(string) $key] = $value;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $existing
     * @return array<string, mixed>
     */
    private static function mergePermissions(array $existing): array
    {
        $permissions = $existing['permissions'] ?? [];

        if (!is_array($permissions)) {
            $permissions = [];
        }

        $allow = $permissions['allow'] ?? [];

        if (!is_array($allow)) {
            $allow = [];
        }

        $allow = array_values(array_filter($allow, static fn (mixed $entry): bool => is_string($entry)));

        foreach (self::getBundledScriptPermissions() as $pattern) {
            if (!in_array($pattern, $allow, true)) {
                $allow[] = $pattern;
            }
        }

        $permissions['allow'] = $allow;
        $existing['permissions'] = $permissions;

        return $existing;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private static function extractAllow(array $data): array
    {
        $permissions = $data['permissions'] ?? [];

        if (!is_array($permissions)) {
            return [];
        }

        $allow = $permissions['allow'] ?? [];

        if (!is_array($allow)) {
            return [];
        }

        return array_values(array_filter($allow, static fn (mixed $entry): bool => is_string($entry)));
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function writeSettings(string $path, array $data): void
    {
        try {
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            // @codeCoverageIgnoreStart
        } catch (JsonException $exception) {
            throw InstallerFailure::settingsJsonWriteFailed($path, $exception->getMessage());
        }

        // @codeCoverageIgnoreEnd

        set_error_handler(static fn (): bool => true);
        $written = file_put_contents($path, $json . "\n");
        restore_error_handler();

        if ($written === false) {
            throw InstallerFailure::settingsJsonWriteFailed($path, 'file_put_contents returned false');
        }
    }

}
