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
        $packageDir = self::getPackageDirectory();
        $packageSource = $packageDir . '/rules';

        if (self::isPackageRoot($root)) {
            $developmentSource = $root . '/rules';

            if (is_dir($developmentSource)) {
                return $developmentSource;
            }
        }

        if (is_dir($packageSource)) {
            return $packageSource;
        }

        // @codeCoverageIgnoreStart
        throw InstallerFailure::missingSource($root . '/rules', $packageSource);
        // @codeCoverageIgnoreEnd
    }

    public static function resolveSkillsSource(string $root): ?string
    {
        $packageDir = self::getPackageDirectory();
        $packageSource = $packageDir . '/skills';

        if (self::isPackageRoot($root)) {
            $developmentSource = $root . '/skills';

            if (is_dir($developmentSource)) {
                return $developmentSource;
            }
        }

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

    private static function getPackageDirectory(): string
    {
        return dirname(__DIR__);
    }

    private static function isPackageRoot(string $root): bool
    {
        $packageDir = self::getPackageDirectory();
        $resolvedRoot = realpath($root);
        $resolvedPackage = realpath($packageDir);

        if ($resolvedRoot === false || $resolvedPackage === false) {
            return $root === $packageDir;
        }

        return $resolvedRoot === $resolvedPackage;
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
