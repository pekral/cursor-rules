<?php

declare(strict_types = 1);

namespace Pekral\CursorRules;

use JsonException;
use stdClass;

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
     * Disables AI co-author attribution in Claude Code commits/PRs by writing
     * `includeCoAuthoredBy: false` into the user's settings, but only for the
     * `claude`/`all` editors and when a usable home directory is available.
     * Returns true when the setting was newly written; false in every other case.
     */
    public static function applyCoAuthoredByPreference(string $editor): bool
    {
        if (!InstallerPath::isClaudeMdEditor($editor)) {
            return false;
        }

        $home = InstallerPath::resolveHomeDirectoryOrNull();

        if ($home === null) {
            return false;
        }

        return self::ensureCoAuthoredByDisabled($home);
    }

    /**
     * Sets `includeCoAuthoredBy: false` in `<home>/.claude/settings.json` idempotently.
     * Leaves an existing value untouched so a user who opted back in keeps their choice.
     * Returns true only when the key was absent and is now written.
     */
    public static function ensureCoAuthoredByDisabled(string $home): bool
    {
        $settingsPath = self::resolveSettingsPath($home);
        $existing = self::readSettings($settingsPath);

        if (property_exists($existing, 'includeCoAuthoredBy')) {
            return false;
        }

        $existing->includeCoAuthoredBy = false;

        InstallerPath::ensureDirectory(dirname($settingsPath));
        self::writeSettings($settingsPath, $existing);

        return true;
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
     * Decodes settings into a `stdClass` object (not an associative array) so that
     * empty JSON objects (`{}`) elsewhere in the file survive the read/write round-trip.
     * `json_decode(..., true)` would turn `{}` into `[]`, which `json_encode` then writes
     * back as a JSON array — corrupting object-typed keys such as Claude Code's
     * `attribution` and tripping `/doctor`'s schema validation.
     */
    private static function readSettings(string $path): stdClass
    {
        if (!is_file($path)) {
            return new stdClass();
        }

        $contents = file_get_contents($path);

        if ($contents === false || trim($contents) === '') {
            return new stdClass();
        }

        try {
            $data = json_decode($contents, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw InstallerFailure::settingsJsonInvalid($path, $exception->getMessage());
        }

        if (!$data instanceof stdClass) {
            throw InstallerFailure::settingsJsonInvalid($path, 'top-level value is not an object');
        }

        return $data;
    }

    private static function mergePermissions(stdClass $existing): stdClass
    {
        $permissions = $existing->permissions ?? null;

        if (!$permissions instanceof stdClass) {
            $permissions = new stdClass();
        }

        $allow = $permissions->allow ?? null;

        if (!is_array($allow)) {
            $allow = [];
        }

        $allow = array_values(array_filter($allow, static fn (mixed $entry): bool => is_string($entry)));

        foreach (self::getBundledScriptPermissions() as $pattern) {
            if (!in_array($pattern, $allow, true)) {
                $allow[] = $pattern;
            }
        }

        $permissions->allow = $allow;
        $existing->permissions = $permissions;

        return $existing;
    }

    /**
     * @return array<int, string>
     */
    private static function extractAllow(stdClass $data): array
    {
        $permissions = $data->permissions ?? null;

        if (!$permissions instanceof stdClass) {
            return [];
        }

        $allow = $permissions->allow ?? null;

        if (!is_array($allow)) {
            return [];
        }

        return array_values(array_filter($allow, static fn (mixed $entry): bool => is_string($entry)));
    }

    private static function writeSettings(string $path, stdClass $data): void
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
