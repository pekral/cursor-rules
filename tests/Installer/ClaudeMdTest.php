<?php

declare(strict_types = 1);

use Pekral\CursorRules\Installer;
use Pekral\CursorRules\InstallerPath;

test('resolveClaudeMdSource returns path to CLAUDE.md in package', function (): void {
    $source = InstallerPath::resolveClaudeMdSource();

    expect($source)->not->toBeNull();
    expect($source)->toBeString();
    expect($source)->toEndWith('/CLAUDE.md');
    expect(is_file((string) $source))->toBeTrue();
});

test('resolveClaudeMdTarget returns CLAUDE.md path in project root', function (): void {
    $target = InstallerPath::resolveClaudeMdTarget('/project');

    expect($target)->toBe('/project/CLAUDE.md');
});

test('isClaudeMdEditor returns true for claude and all editors', function (): void {
    expect(InstallerPath::isClaudeMdEditor(InstallerPath::EDITOR_CLAUDE))->toBeTrue();
    expect(InstallerPath::isClaudeMdEditor(InstallerPath::EDITOR_ALL))->toBeTrue();
    expect(InstallerPath::isClaudeMdEditor(InstallerPath::EDITOR_CURSOR))->toBeFalse();
    expect(InstallerPath::isClaudeMdEditor(InstallerPath::EDITOR_CODEX))->toBeFalse();
});

test('install with editor=claude copies CLAUDE.md to project root', function (): void {
    $root = installerCreateProjectRoot();
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=claude']);
        ob_end_clean();

        $claudeMd = $root . '/CLAUDE.md';
        expect(is_file($claudeMd))->toBeTrue();
        expect(file_get_contents($claudeMd))->toContain('Behavioral guidelines');
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install with editor=cursor does not copy CLAUDE.md', function (): void {
    $root = installerCreateProjectRoot();
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();

        expect(is_file($root . '/CLAUDE.md'))->toBeFalse();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install with editor=all copies CLAUDE.md to project root', function (): void {
    $root = installerCreateProjectRoot();
    $homeEnv = getenv('HOME');
    $homeBefore = $homeEnv !== false && $homeEnv !== '' ? $homeEnv : getenv('USERPROFILE');
    putenv('HOME=' . $root);

    if (getenv('USERPROFILE') !== false) {
        putenv('USERPROFILE=' . $root);
    }

    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=all']);
        ob_end_clean();

        $claudeMd = $root . '/CLAUDE.md';
        expect(is_file($claudeMd))->toBeTrue();
        expect(file_get_contents($claudeMd))->toContain('Behavioral guidelines');
    } finally {
        installerRestoreEnvAndCleanup($homeBefore, $originalCwd, $root);
    }
});

test('install does not overwrite existing CLAUDE.md without force flag', function (): void {
    $root = installerCreateProjectRoot();
    $claudeMd = $root . '/CLAUDE.md';
    file_put_contents($claudeMd, 'my custom CLAUDE.md');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=claude']);
        ob_end_clean();

        expect(file_get_contents($claudeMd))->toBe('my custom CLAUDE.md');
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install never overwrites existing CLAUDE.md even with force flag', function (): void {
    $root = installerCreateProjectRoot();
    $claudeMd = $root . '/CLAUDE.md';
    file_put_contents($claudeMd, 'my custom CLAUDE.md');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=claude', '--force']);
        ob_end_clean();

        expect(file_get_contents($claudeMd))->toBe('my custom CLAUDE.md');
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('CLAUDE.md source file exists in package', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $claudeMd = $packageDir . '/CLAUDE.md';

    expect(is_file($claudeMd))->toBeTrue();
    expect(file_get_contents($claudeMd))->toContain('Behavioral guidelines');
    expect(file_get_contents($claudeMd))->toContain('Think Before Coding');
    expect(file_get_contents($claudeMd))->toContain('Simplicity First');
    expect(file_get_contents($claudeMd))->toContain('Surgical Changes');
    expect(file_get_contents($claudeMd))->toContain('Goal-Driven Execution');
});
