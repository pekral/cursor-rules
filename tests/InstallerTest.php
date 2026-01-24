<?php

declare(strict_types = 1);

use Pekral\CursorRules\Installer;
use Pekral\CursorRules\InstallerPath;

test('run shows help when executed without arguments', function (): void {
    ob_start();
    $exitCode = Installer::run(['cursor-rules']);
    $output = (string) ob_get_clean();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('Usage:');
});

test('run returns error code for unknown command', function (): void {
    $exitCode = Installer::run(['cursor-rules', 'unknown']);

    expect($exitCode)->toBe(1);
});

test('package directory points to correct location', function (): void {
    $packageDir = dirname(__DIR__);
    $rulesDir = $packageDir . '/rules';
    $skillsDir = $packageDir . '/skills';

    expect(is_dir($rulesDir))->toBeTrue();
    expect(is_dir($skillsDir))->toBeTrue();
});

test('resolveRulesSource prefers development directory', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/test.mdc', 'content');

    try {
        $source = InstallerPath::resolveRulesSource($root);

        expect($source)->toBe($root . '/rules');
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveRulesSource falls back to package directory', function (): void {
    $root = installerCreateProjectRoot();

    try {
        $source = InstallerPath::resolveRulesSource($root);
        $packageDir = dirname(__DIR__);

        expect($source)->toBe($packageDir . '/rules');
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveSkillsSource prefers development directory', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/skills/test/SKILL.md', 'content');

    try {
        $source = InstallerPath::resolveSkillsSource($root);

        expect($source)->toBe($root . '/skills');
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveSkillsSource falls back to package directory', function (): void {
    $root = installerCreateProjectRoot();

    try {
        $source = InstallerPath::resolveSkillsSource($root);
        $packageDir = dirname(__DIR__);

        expect($source)->toBe($packageDir . '/skills');
    } finally {
        installerRemoveDirectory($root);
    }
});

test('install copies rules from development directory', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/example.mdc', 'dev content');
    $originalCwd = getcwd() ?: '';

    try {
        chdir($root);
        ob_start();
        $exitCode = Installer::run(['cursor-rules', 'install']);
        ob_end_clean();

        $installedFile = $root . '/.cursor/rules/example.mdc';

        expect($exitCode)->toBe(0);
        expect(is_file($installedFile))->toBeTrue();
        expect(file_get_contents($installedFile))->toBe('dev content');
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install copies rules from package when no development directory', function (): void {
    $root = installerCreateProjectRoot();
    $originalCwd = getcwd() ?: '';

    try {
        chdir($root);
        ob_start();
        $exitCode = Installer::run(['cursor-rules', 'install']);
        ob_end_clean();

        expect($exitCode)->toBe(0);

        $installedDir = $root . '/.cursor/rules';
        expect(is_dir($installedDir))->toBeTrue();

        $files = glob($installedDir . '/*.mdc');
        expect(count($files))->toBeGreaterThan(0);
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install respects force flag', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/force.mdc', 'new content');
    $installedFile = $root . '/.cursor/rules/force.mdc';
    installerWriteFile($installedFile, 'existing content');
    $originalCwd = getcwd() ?: '';

    try {
        chdir($root);

        ob_start();
        Installer::run(['cursor-rules', 'install']);
        ob_end_clean();
        expect(file_get_contents($installedFile))->toBe('existing content');

        ob_start();
        Installer::run(['cursor-rules', 'install', '--force']);
        ob_end_clean();
        expect(file_get_contents($installedFile))->toBe('new content');
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install creates symlinks when requested', function (): void {
    if (installerSymlinkUnsupported()) {
        expect(true)->toBeTrue();

        return;
    }

    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/link.mdc', 'link content');
    $originalCwd = getcwd() ?: '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--symlink']);
        ob_end_clean();

        $target = $root . '/.cursor/rules/link.mdc';

        expect(is_link($target))->toBeTrue();
        expect(file_get_contents($target))->toBe('link content');
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install copies nested directories', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/nested/deep/example.mdc', 'nested content');
    $originalCwd = getcwd() ?: '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install']);
        ob_end_clean();

        $installedFile = $root . '/.cursor/rules/nested/deep/example.mdc';

        expect(is_file($installedFile))->toBeTrue();
        expect(file_get_contents($installedFile))->toBe('nested content');
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install copies skills from development directory', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/example.mdc', 'rules');
    installerWriteFile($root . '/skills/test-skill/SKILL.md', 'skill content');
    $originalCwd = getcwd() ?: '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install']);
        ob_end_clean();

        $installedSkill = $root . '/.cursor/skills/test-skill/SKILL.md';

        expect(is_file($installedSkill))->toBeTrue();
        expect(file_get_contents($installedSkill))->toBe('skill content');
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});
