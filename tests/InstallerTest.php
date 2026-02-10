<?php

declare(strict_types = 1);

use Pekral\CursorRules\Installer;
use Pekral\CursorRules\InstallerFailure;
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
    $agentsDir = $packageDir . '/agents';

    expect(is_dir($rulesDir))->toBeTrue();
    expect(is_dir($skillsDir))->toBeTrue();
    expect(is_dir($agentsDir))->toBeTrue();
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

test('resolveAgentsSource prefers development directory', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/agents/test.md', 'content');

    try {
        $source = InstallerPath::resolveAgentsSource($root);

        expect($source)->toBe($root . '/agents');
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveAgentsSource falls back to package directory', function (): void {
    $root = installerCreateProjectRoot();

    try {
        $source = InstallerPath::resolveAgentsSource($root);
        $packageDir = dirname(__DIR__);

        expect($source)->toBe($packageDir . '/agents');
    } finally {
        installerRemoveDirectory($root);
    }
});

test('install copies rules from development directory', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/example.mdc', 'dev content');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

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
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        $exitCode = Installer::run(['cursor-rules', 'install']);
        ob_end_clean();

        expect($exitCode)->toBe(0);

        $installedDir = $root . '/.cursor/rules';
        expect(is_dir($installedDir))->toBeTrue();

        $files = glob($installedDir . '/*.mdc');
        $files = $files !== false ? $files : [];
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
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

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
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

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
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

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
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

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

test('install copies agents from development directory', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/example.mdc', 'rules');
    installerWriteFile($root . '/agents/resolve-issue.md', 'agent content');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install']);
        ob_end_clean();

        $installedAgent = $root . '/.cursor/agents/resolve-issue.md';

        expect(is_file($installedAgent))->toBeTrue();
        expect(file_get_contents($installedAgent))->toBe('agent content');
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install copies all files from rules, skills and agents directories', function (): void {
    $packageDir = dirname(__DIR__);
    $rulesSource = $packageDir . '/rules';
    $skillsSource = $packageDir . '/skills';
    $agentsSource = $packageDir . '/agents';
    $expectedRulesCount = installerCountFiles($rulesSource);
    $expectedSkillsCount = installerCountFiles($skillsSource);
    $expectedAgentsCount = installerCountFiles($agentsSource);

    $root = installerCreateProjectRoot();
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        $exitCode = Installer::run(['cursor-rules', 'install']);
        $output = (string) ob_get_clean();

        expect($exitCode)->toBe(0);

        $rulesTarget = $root . '/.cursor/rules';
        $skillsTarget = $root . '/.cursor/skills';
        $agentsTarget = $root . '/.cursor/agents';
        $actualRulesCount = installerCountFiles($rulesTarget);
        $actualSkillsCount = installerCountFiles($skillsTarget);
        $actualAgentsCount = installerCountFiles($agentsTarget);

        expect($actualRulesCount)->toBe($expectedRulesCount, 'Rules: all source files should be copied');
        expect($actualSkillsCount)->toBe($expectedSkillsCount, 'Skills: all source files should be copied');
        expect($actualAgentsCount)->toBe($expectedAgentsCount, 'Agents: all source files should be copied');
        expect($output)->toContain(sprintf('(%d files)', $expectedRulesCount + $expectedSkillsCount + $expectedAgentsCount));
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('InstallerFailure missingSource creates exception with correct message', function (): void {
    $exception = InstallerFailure::missingSource('/dev/path', '/vendor/path');

    expect($exception)->toBeInstanceOf(InstallerFailure::class);
    expect($exception->getMessage())->toBe('Source not found. Checked /dev/path and /vendor/path.');
});

test('InstallerFailure directoryCreationFailed creates exception with correct message', function (): void {
    $exception = InstallerFailure::directoryCreationFailed('/some/directory');

    expect($exception)->toBeInstanceOf(InstallerFailure::class);
    expect($exception->getMessage())->toBe('Cannot create directory: /some/directory');
});

test('InstallerFailure fileCopyFailed creates exception with correct message', function (): void {
    $exception = InstallerFailure::fileCopyFailed('/source/file', '/dest/file');

    expect($exception)->toBeInstanceOf(InstallerFailure::class);
    expect($exception->getMessage())->toBe('Unable to copy /source/file to /dest/file.');
});

test('InstallerFailure removalFailed creates exception with correct message', function (): void {
    $exception = InstallerFailure::removalFailed('/some/path');

    expect($exception)->toBeInstanceOf(InstallerFailure::class);
    expect($exception->getMessage())->toBe('Cannot remove: /some/path');
});

test('install fails when target path is a file instead of directory', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/test.mdc', 'content');
    file_put_contents($root . '/.cursor', 'blocking file');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        $exitCode = Installer::run(['cursor-rules', 'install']);
        ob_get_clean();

        expect($exitCode)->toBe(1);
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install fails when destination is directory that cannot be removed', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/test.mdc', 'content');
    $targetDir = $root . '/.cursor/rules/test.mdc';
    installerEnsureDirectory($targetDir);
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        $exitCode = Installer::run(['cursor-rules', 'install', '--force']);
        ob_get_clean();

        expect($exitCode)->toBe(1);
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('resolveSkillsSource falls back to package when development directory does not exist', function (): void {
    $root = sys_get_temp_dir() . '/no-skills-' . bin2hex(random_bytes(4));
    installerEnsureDirectory($root);

    try {
        $result = InstallerPath::resolveSkillsSource($root);
        $packageDir = dirname(__DIR__);

        expect($result)->toBe($packageDir . '/skills');
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveAgentsSource falls back to package when development directory does not exist', function (): void {
    $root = sys_get_temp_dir() . '/no-agents-' . bin2hex(random_bytes(4));
    installerEnsureDirectory($root);

    try {
        $result = InstallerPath::resolveAgentsSource($root);
        $packageDir = dirname(__DIR__);

        expect($result)->toBe($packageDir . '/agents');
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveProjectRoot returns current working directory', function (): void {
    $result = InstallerPath::resolveProjectRoot();

    expect($result)->toBeString();
    expect(strlen($result))->toBeGreaterThan(0);
});

test('install fails when rules subdirectory path is a file', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/subdir/test.mdc', 'content');
    $targetSubdir = $root . '/.cursor/rules/subdir';
    installerEnsureDirectory(dirname($targetSubdir));
    file_put_contents($targetSubdir, 'blocking file');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        $exitCode = Installer::run(['cursor-rules', 'install']);
        ob_get_clean();

        expect($exitCode)->toBe(1);
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('resolveTargetDirectory returns correct path', function (): void {
    $result = InstallerPath::resolveTargetDirectory('/test/root');

    expect($result)->toBe('/test/root/.cursor/rules');
});

test('resolveSkillsTargetDirectory returns correct path', function (): void {
    $result = InstallerPath::resolveSkillsTargetDirectory('/test/root');

    expect($result)->toBe('/test/root/.cursor/skills');
});

test('resolveAgentsTargetDirectory returns correct path', function (): void {
    $result = InstallerPath::resolveAgentsTargetDirectory('/test/root');

    expect($result)->toBe('/test/root/.cursor/agents');
});

test('isFilesystemRoot returns true for root paths', function (): void {
    $reflection = new ReflectionClass(InstallerPath::class);
    $method = $reflection->getMethod('isFilesystemRoot');
    $method->setAccessible(true);

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
        $method->setAccessible(true);

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

test('install fails when copy fails due to unwritable destination', function (): void {
    if (posix_getuid() === 0) {
        expect(true)->toBeTrue();

        return;
    }

    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/test.mdc', 'content');
    $targetDir = $root . '/.cursor/rules';
    installerEnsureDirectory($targetDir);
    chmod($targetDir, 0444);
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        set_error_handler(static fn (): bool => true);
        $exitCode = Installer::run(['cursor-rules', 'install']);
        restore_error_handler();
        ob_get_clean();

        expect($exitCode)->toBe(1);
    } finally {
        chmod($targetDir, 0755);

        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install fails when existing file cannot be removed', function (): void {
    if (posix_getuid() === 0) {
        expect(true)->toBeTrue();

        return;
    }

    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/test.mdc', 'new content');
    $targetDir = $root . '/.cursor/rules';
    $targetFile = $targetDir . '/test.mdc';
    installerWriteFile($targetFile, 'old content');
    chmod($targetDir, 0555);
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        set_error_handler(static fn (): bool => true);
        $exitCode = Installer::run(['cursor-rules', 'install', '--force']);
        restore_error_handler();
        ob_get_clean();

        expect($exitCode)->toBe(1);
    } finally {
        chmod($targetDir, 0755);

        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});
