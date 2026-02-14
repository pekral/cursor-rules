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
        $source = self::resolveSource($root, 'rules');

        // @codeCoverageIgnoreStart
        if ($source === null) {
            $packageDir = self::getPackageDirectory();

            throw InstallerFailure::missingSource($root . '/rules', $packageDir . '/rules');
        }

        // @codeCoverageIgnoreEnd

        return $source;
    }

    public static function resolveSkillsSource(string $root): ?string
    {
        return self::resolveSource($root, 'skills');
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
     * @return string|null path to source directory, or null if not found (skills only)
     */
    private static function resolveSource(string $root, string $subdir): ?string
    {
        $packageDir = self::getPackageDirectory();
        $packageSource = $packageDir . '/' . $subdir;

        if (self::isPackageRoot($root)) {
            $developmentSource = $root . '/' . $subdir;

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
