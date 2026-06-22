<?php

declare(strict_types = 1);

use Pekral\CursorRules\Installer;
use Pekral\CursorRules\InstallerPruner;

test('install with prune removes files from target that no longer exist in source', function (): void {
    $root = installerCreateProjectRoot();
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);

        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();

        installerWriteFile($root . '/.cursor/skills/orphaned-skill/SKILL.md', 'orphaned content');

        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor', '--prune']);
        ob_end_clean();

        expect(is_file($root . '/.cursor/skills/code-review/SKILL.md'))->toBeTrue();
        expect(is_file($root . '/.cursor/skills/orphaned-skill/SKILL.md'))->toBeFalse();
        expect(is_dir($root . '/.cursor/skills/orphaned-skill'))->toBeFalse();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install without prune keeps orphaned files in target', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/.cursor/skills/orphaned-skill/SKILL.md', 'orphaned content');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();

        expect(is_file($root . '/.cursor/skills/orphaned-skill/SKILL.md'))->toBeTrue();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install with prune also removes rules that no longer exist in source', function (): void {
    $root = installerCreateProjectRoot();
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);

        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();

        installerWriteFile($root . '/.cursor/rules/removed.mdc', 'removed rule');

        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor', '--prune']);
        ob_end_clean();

        expect(is_file($root . '/.cursor/rules/php/core-standards.mdc'))->toBeTrue();
        expect(is_file($root . '/.cursor/rules/removed.mdc'))->toBeFalse();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install with prune reports pruned file count in output', function (): void {
    $root = installerCreateProjectRoot();
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);

        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();

        installerWriteFile($root . '/.cursor/skills/drop-skill/SKILL.md', 'drop');

        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor', '--prune']);
        $output = (string) ob_get_clean();

        expect($output)->toContain('1 pruned');
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('InstallerPruner returns 0 when target directory does not exist', function (): void {
    $result = InstallerPruner::pruneDirectory('/some/source', '/nonexistent/target');

    expect($result)->toBe(0);
});

test('InstallerPruner prunes all files when source directory does not exist', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/target/some-skill/SKILL.md', 'content');

    try {
        $pruned = InstallerPruner::pruneDirectory(
            $root . '/nonexistent-source',
            $root . '/target',
        );

        expect($pruned)->toBe(1);
        expect(is_file($root . '/target/some-skill/SKILL.md'))->toBeFalse();
    } finally {
        installerRemoveDirectory($root);
    }
});

test('InstallerPruner prune keeps non-orphaned files in nested directory', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/source/skill-a/SKILL.md', 'source content');
    installerWriteFile($root . '/target/skill-a/SKILL.md', 'target content');
    installerWriteFile($root . '/target/skill-a/extra.md', 'extra content');

    try {
        $pruned = InstallerPruner::pruneDirectory(
            $root . '/source',
            $root . '/target',
        );

        expect($pruned)->toBe(1);
        expect(is_file($root . '/target/skill-a/SKILL.md'))->toBeTrue();
        expect(is_file($root . '/target/skill-a/extra.md'))->toBeFalse();
        expect(is_dir($root . '/target/skill-a'))->toBeTrue();
    } finally {
        installerRemoveDirectory($root);
    }
});

test('InstallerPruner removes empty parent directories after pruning', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/source/keep/SKILL.md', 'keep');
    installerWriteFile($root . '/target/keep/SKILL.md', 'keep');
    installerWriteFile($root . '/target/orphaned/SKILL.md', 'orphaned');

    try {
        $pruned = InstallerPruner::pruneDirectory(
            $root . '/source',
            $root . '/target',
        );

        expect($pruned)->toBe(1);
        expect(is_dir($root . '/target/orphaned'))->toBeFalse();
        expect(is_dir($root . '/target/keep'))->toBeTrue();
    } finally {
        installerRemoveDirectory($root);
    }
});

test('InstallerPruner handles unwritable file gracefully when pruning', function (): void {
    if (posix_getuid() === 0) {
        expect(value: true)->toBeTrue();

        return;
    }

    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/source/keep/SKILL.md', 'keep');
    installerWriteFile($root . '/target/keep/SKILL.md', 'keep');
    installerWriteFile($root . '/target/locked/SKILL.md', 'locked');
    chmod($root . '/target/locked', 0555);

    try {
        set_error_handler(static fn (): bool => true);
        $pruned = InstallerPruner::pruneDirectory(
            $root . '/source',
            $root . '/target',
        );
        restore_error_handler();

        expect($pruned)->toBe(0);
    } finally {
        chmod($root . '/target/locked', 0755);
        installerRemoveDirectory($root);
    }
});

test('InstallerPruner handles unwritable parent directory when removing empty dirs', function (): void {
    if (posix_getuid() === 0) {
        expect(value: true)->toBeTrue();

        return;
    }

    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/source/keep/SKILL.md', 'keep');
    installerWriteFile($root . '/target/keep/SKILL.md', 'keep');
    installerWriteFile($root . '/target/orphaned/SKILL.md', 'orphaned');
    chmod($root . '/target', 0555);

    try {
        set_error_handler(static fn (): bool => true);
        InstallerPruner::pruneDirectory(
            $root . '/source',
            $root . '/target',
        );
        restore_error_handler();

        expect(value: true)->toBeTrue();
    } finally {
        chmod($root . '/target', 0755);
        installerRemoveDirectory($root);
    }
});
