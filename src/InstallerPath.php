<?php

declare(strict_types = 1);

namespace Pekral\CursorRules;

final class InstallerPath
{

    public static function resolveProjectRoot(): string
    {
        $override = getenv('CURSOR_RULES_PROJECT_ROOT');

        if (is_string($override) && $override !== '') {
            return $override;
        }

        return self::findProjectRoot();
    }

    public static function resolveRulesSource(string $root): string
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

    public static function resolveSkillsSource(string $root): ?string
    {
        $developmentSource = $root . '/skills';

        if (is_dir($developmentSource)) {
            return $developmentSource;
        }

        $vendorSource = $root . '/vendor/pekral/cursor-rules/skills';

        if (is_dir($vendorSource)) {
            return $vendorSource;
        }

        return null;
    }

    public static function resolveTargetDirectory(string $root): string
    {
        $override = getenv('CURSOR_RULES_TARGET_DIR');

        if (is_string($override) && $override !== '') {
            return $override;
        }

        return $root . '/.cursor/rules';
    }

    public static function resolveSkillsTargetDirectory(string $root): string
    {
        $override = getenv('CURSOR_RULES_SKILLS_TARGET_DIR');

        if (is_string($override) && $override !== '') {
            return $override;
        }

        return $root . '/.cursor/skills';
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

}
