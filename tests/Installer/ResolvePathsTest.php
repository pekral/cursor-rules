<?php

declare(strict_types = 1);

use Pekral\CursorRules\InstallerPath;

test('resolveRulesSource always uses package directory', function (): void {
    $root = installerCreateProjectRoot();
    $packageDir = dirname(__DIR__, 2);

    try {
        $source = InstallerPath::resolveRulesSource($root);

        expect($source)->toBe($packageDir . '/rules');
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveRulesSource ignores rules directory in project root', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/test.mdc', 'foreign content');
    $packageDir = dirname(__DIR__, 2);

    try {
        $source = InstallerPath::resolveRulesSource($root);

        expect($source)->toBe($packageDir . '/rules');
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveSkillsSource always uses package directory', function (): void {
    $root = installerCreateProjectRoot();
    $packageDir = dirname(__DIR__, 2);

    try {
        $source = InstallerPath::resolveSkillsSource();

        expect($source)->toBe($packageDir . '/skills');
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveSkillsSource ignores skills directory in project root', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/skills/test/SKILL.md', 'foreign content');
    $packageDir = dirname(__DIR__, 2);

    try {
        $source = InstallerPath::resolveSkillsSource();

        expect($source)->toBe($packageDir . '/skills');
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveSkillsSource falls back to package when development directory does not exist', function (): void {
    $root = sys_get_temp_dir() . '/no-skills-' . bin2hex(random_bytes(4));
    installerEnsureDirectory($root);

    try {
        $result = InstallerPath::resolveSkillsSource();
        $packageDir = dirname(__DIR__, 2);

        expect($result)->toBe($packageDir . '/skills');
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveProjectRoot returns current working directory', function (): void {
    $result = InstallerPath::resolveProjectRoot();

    expect($result)->toBeString();
    expect(strlen($result))->toBeGreaterThan(0);
});

test('resolveTargetDirectory returns correct path', function (): void {
    $result = InstallerPath::resolveTargetDirectory('/test/root');

    expect($result)->toBe('/test/root/.cursor/rules');
});

test('resolveSkillsTargetDirectory returns correct path', function (): void {
    $result = InstallerPath::resolveSkillsTargetDirectory('/test/root');

    expect($result)->toBe('/test/root/.cursor/skills');
});

test('resolveAllSkillsTargetDirectories returns project and home skill directories', function (): void {
    $root = '/test/root';
    $targets = InstallerPath::resolveAllSkillsTargetDirectories($root);

    expect($targets)->toContain('/test/root/.cursor/skills');
    expect($targets)->toContain('/test/root/.claude/skills');
    expect($targets)->toContain('/test/root/.codex/skills');
    expect(count($targets))->toBeGreaterThanOrEqual(3);

    $homeEnv = getenv('HOME');
    $home = $homeEnv !== false && $homeEnv !== '' ? $homeEnv : getenv('USERPROFILE');

    if ($home === false || $home === '') {
        return;
    }

    expect($targets)->toContain($home . '/.claude/skills');
    expect($targets)->toContain($home . '/.codex/skills');
    expect(count($targets))->toBe(5);
});

test('resolveRulesTargetDirectories returns single path for cursor editor', function (): void {
    $targets = InstallerPath::resolveRulesTargetDirectories('/project', InstallerPath::EDITOR_CURSOR);

    expect($targets)->toBe(['/project/.cursor/rules']);
});

test('resolveRulesTargetDirectories returns all paths for all editor', function (): void {
    $targets = InstallerPath::resolveRulesTargetDirectories('/project', InstallerPath::EDITOR_ALL);

    expect($targets)->toBe([
        '/project/.cursor/rules',
        '/project/.claude/rules',
        '/project/.codex/rules',
    ]);
});

test('resolveRulesTargetDirectories returns default path for unknown editor', function (): void {
    $targets = InstallerPath::resolveRulesTargetDirectories('/project', 'unknown');

    expect($targets)->toBe(['/project/.cursor/rules']);
});

test('resolveSkillsTargetDirectories returns single path for cursor editor', function (): void {
    $targets = InstallerPath::resolveSkillsTargetDirectories('/project', InstallerPath::EDITOR_CURSOR);

    expect($targets)->toBe(['/project/.cursor/skills']);
});

test('resolveSkillsTargetDirectories with editor=cursor and HOME set does not add home paths', function (): void {
    $homeBefore = getenv('HOME');
    $userProfileBefore = getenv('USERPROFILE');
    putenv('HOME=/fake/home');
    putenv('USERPROFILE=/fake/home');

    try {
        $targets = InstallerPath::resolveSkillsTargetDirectories('/project', InstallerPath::EDITOR_CURSOR);

        expect($targets)->toBe(['/project/.cursor/skills']);
    } finally {
        if ($homeBefore !== false) {
            putenv('HOME=' . $homeBefore);
        } else {
            putenv('HOME');
        }

        if ($userProfileBefore !== false) {
            putenv('USERPROFILE=' . $userProfileBefore);
        } else {
            putenv('USERPROFILE');
        }
    }
});

test('resolveSkillsTargetDirectories returns default path for unknown editor', function (): void {
    $targets = InstallerPath::resolveSkillsTargetDirectories('/project', 'unknown');

    expect($targets)->toBe(['/project/.cursor/skills']);
});

test('resolveSkillsTargetDirectories with editor=all and no HOME returns only project paths', function (): void {
    $homeBefore = getenv('HOME');
    $userProfileBefore = getenv('USERPROFILE');
    putenv('HOME');
    putenv('USERPROFILE');

    try {
        $targets = InstallerPath::resolveSkillsTargetDirectories('/project', InstallerPath::EDITOR_ALL);

        expect($targets)->toBe([
            '/project/.cursor/skills',
            '/project/.claude/skills',
            '/project/.codex/skills',
        ]);
    } finally {
        if ($homeBefore !== false) {
            putenv('HOME=' . $homeBefore);
        }

        if ($userProfileBefore !== false) {
            putenv('USERPROFILE=' . $userProfileBefore);
        }
    }
});

test('resolveSkillsTargetDirectories with editor=claude and no HOME returns only project path', function (): void {
    $homeBefore = getenv('HOME');
    $userProfileBefore = getenv('USERPROFILE');
    putenv('HOME');
    putenv('USERPROFILE');

    try {
        $targets = InstallerPath::resolveSkillsTargetDirectories('/project', InstallerPath::EDITOR_CLAUDE);

        expect($targets)->toBe(['/project/.claude/skills']);
    } finally {
        if ($homeBefore !== false) {
            putenv('HOME=' . $homeBefore);
        }

        if ($userProfileBefore !== false) {
            putenv('USERPROFILE=' . $userProfileBefore);
        }
    }
});

test('isFilesystemRoot returns true for root paths', function (): void {
    $reflection = new ReflectionClass(InstallerPath::class);
    $method = $reflection->getMethod('isFilesystemRoot');

    expect($method->invoke(null, ''))->toBeTrue();
    expect($method->invoke(null, DIRECTORY_SEPARATOR))->toBeTrue();
    expect($method->invoke(null, 'C:'))->toBeTrue();
    expect($method->invoke(null, 'D:\\'))->toBeTrue();
    expect($method->invoke(null, '/home/user'))->toBeFalse();
});

test('findProjectRoot traverses directories up', function (): void {
    $root = installerCreateProjectRoot();
    $subdir = $root . '/deep/nested/path';
    installerEnsureDirectory($subdir);
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($subdir);

        $reflection = new ReflectionClass(InstallerPath::class);
        $method = $reflection->getMethod('findProjectRoot');

        $result = $method->invoke(null);
        $expectedRoot = realpath($root);
        $expectedRoot = $expectedRoot !== false ? $expectedRoot : $root;

        expect($result)->toBe($expectedRoot);
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('resolveHomeDirectoryOrNull returns the HOME value when set', function (): void {
    $homeBefore = getenv('HOME');
    $userProfileBefore = getenv('USERPROFILE');
    putenv('HOME=/tmp/fake-home-' . bin2hex(random_bytes(2)));

    try {
        $home = InstallerPath::resolveHomeDirectoryOrNull();

        expect($home)->toBeString();
        expect($home)->toStartWith('/tmp/fake-home-');
    } finally {
        if ($homeBefore !== false && $homeBefore !== '') {
            putenv('HOME=' . $homeBefore);
        } else {
            putenv('HOME');
        }

        if ($userProfileBefore !== false && $userProfileBefore !== '') {
            putenv('USERPROFILE=' . $userProfileBefore);
        }
    }
});

test('resolveHomeDirectoryOrNull returns null when neither HOME nor USERPROFILE is set', function (): void {
    $homeBefore = getenv('HOME');
    $userProfileBefore = getenv('USERPROFILE');
    putenv('HOME');
    putenv('USERPROFILE');

    try {
        $home = InstallerPath::resolveHomeDirectoryOrNull();

        expect($home)->toBeNull();
    } finally {
        if ($homeBefore !== false && $homeBefore !== '') {
            putenv('HOME=' . $homeBefore);
        }

        if ($userProfileBefore !== false && $userProfileBefore !== '') {
            putenv('USERPROFILE=' . $userProfileBefore);
        }
    }
});
