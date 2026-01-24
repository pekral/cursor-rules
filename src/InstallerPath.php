<?php

declare(strict_types = 1);

namespace Pekral\CursorRules;

final class InstallerPath
{

    public static function resolveProjectRoot(): string
    {
        return self::findProjectRoot();
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

        throw InstallerFailure::missingSource($developmentSource, $packageSource);
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

        return null;
    }

    public static function resolveTargetDirectory(string $root): string
    {
        return $root . '/.cursor/rules';
    }

    public static function resolveSkillsTargetDirectory(string $root): string
    {
        return $root . '/.cursor/skills';
    }

    private static function getPackageDirectory(): string
    {
        return dirname(__DIR__);
    }

    private static function findProjectRoot(): string
    {
        $dir = getcwd();

        if ($dir === false) {
            return sys_get_temp_dir();
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
