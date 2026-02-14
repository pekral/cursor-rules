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
    $securityRulesDir = $packageDir . '/rules/security';

    expect(is_dir($rulesDir))->toBeTrue();
    expect(is_dir($skillsDir))->toBeTrue();
    expect(is_dir($securityRulesDir))->toBeTrue();
});

test('resolveRulesSource prefers development directory when root is package', function (): void {
    $packageDir = dirname(__DIR__);
    $source = InstallerPath::resolveRulesSource($packageDir);

    expect($source)->toBe($packageDir . '/rules');
});

test('resolveRulesSource uses package rules when project has rules but is not package', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/wrong.mdc', 'wrong content');

    try {
        $source = InstallerPath::resolveRulesSource($root);
        $packageDir = dirname(__DIR__);

        expect($source)->toBe($packageDir . '/rules');
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

test('resolveRulesSource uses package when root path does not exist', function (): void {
    $nonExistentRoot = '/tmp/cursor-rules-nonexistent-' . bin2hex(random_bytes(4));
    $packageDir = dirname(__DIR__);

    $source = InstallerPath::resolveRulesSource($nonExistentRoot);

    expect($source)->toBe($packageDir . '/rules');
});

test('resolveSkillsSource prefers development directory when root is package', function (): void {
    $packageDir = dirname(__DIR__);
    $source = InstallerPath::resolveSkillsSource($packageDir);

    expect($source)->toBe($packageDir . '/skills');
});

test('resolveSkillsSource uses package skills when project has skills but is not package', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/skills/test/SKILL.md', 'content');

    try {
        $source = InstallerPath::resolveSkillsSource($root);
        $packageDir = dirname(__DIR__);

        expect($source)->toBe($packageDir . '/skills');
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

test('install copies rules from development directory when run from package root', function (): void {
    $packageDir = dirname(__DIR__);
    $devFile = $packageDir . '/rules/installer-dev-test.mdc';
    $installedFile = $packageDir . '/.cursor/rules/installer-dev-test.mdc';
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    installerWriteFile($devFile, 'dev content');

    try {
        chdir($packageDir);
        ob_start();
        $exitCode = Installer::run(['cursor-rules', 'install']);
        ob_end_clean();

        expect($exitCode)->toBe(0);
        expect(is_file($installedFile))->toBeTrue();
        expect(file_get_contents($installedFile))->toBe('dev content');
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        if (is_file($installedFile)) {
            unlink($installedFile);
        }

        if (is_file($devFile)) {
            unlink($devFile);
        }
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

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($installedDir, FilesystemIterator::SKIP_DOTS),
        );
        $files = [];

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->getExtension() === 'mdc') {
                $files[] = $file->getPathname();
            }
        }

        expect(count($files))->toBeGreaterThan(0);
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install respects force flag', function (): void {
    $packageDir = dirname(__DIR__);
    $forceFile = $packageDir . '/rules/installer-force-test.mdc';
    $installedFile = $packageDir . '/.cursor/rules/installer-force-test.mdc';
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    installerWriteFile($forceFile, 'new content');

    try {
        chdir($packageDir);

        ob_start();
        Installer::run(['cursor-rules', 'install']);
        ob_end_clean();
        expect(is_file($installedFile))->toBeTrue();
        expect(file_get_contents($installedFile))->toBe('new content');

        file_put_contents($installedFile, 'existing content');

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

        if (is_file($installedFile)) {
            unlink($installedFile);
        }

        if (is_file($forceFile)) {
            unlink($forceFile);
        }
    }
});

test('install creates symlinks when requested', function (): void {
    if (installerSymlinkUnsupported()) {
        expect(true)->toBeTrue();

        return;
    }

    $packageDir = dirname(__DIR__);
    $linkFile = $packageDir . '/rules/installer-symlink-test.mdc';
    $target = $packageDir . '/.cursor/rules/installer-symlink-test.mdc';
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    installerWriteFile($linkFile, 'link content');

    try {
        chdir($packageDir);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--symlink']);
        ob_end_clean();

        expect(is_link($target))->toBeTrue();
        expect(file_get_contents($target))->toBe('link content');
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        if (file_exists($target)) {
            unlink($target);
        }

        if (is_file($linkFile)) {
            unlink($linkFile);
        }
    }
});

test('install copies nested directories from package', function (): void {
    $packageDir = dirname(__DIR__);
    $root = installerCreateProjectRoot();
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install']);
        ob_end_clean();

        $installedFile = $root . '/.cursor/rules/security/backend.md';
        $expectedContent = file_get_contents($packageDir . '/rules/security/backend.md');

        expect(is_file($installedFile))->toBeTrue();
        expect(file_get_contents($installedFile))->toBe($expectedContent);
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install copies skills from development directory when run from package root', function (): void {
    $packageDir = dirname(__DIR__);
    $skillFile = $packageDir . '/skills/installer-test-skill/SKILL.md';
    $installedSkill = $packageDir . '/.cursor/skills/installer-test-skill/SKILL.md';
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    installerWriteFile($skillFile, 'skill content');

    try {
        chdir($packageDir);
        ob_start();
        Installer::run(['cursor-rules', 'install']);
        ob_end_clean();

        expect(is_file($installedSkill))->toBeTrue();
        expect(file_get_contents($installedSkill))->toBe('skill content');
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        if (is_file($installedSkill)) {
            unlink($installedSkill);
        }

        installerRemoveDirectory($packageDir . '/skills/installer-test-skill');
        installerRemoveDirectory($packageDir . '/.cursor/skills/installer-test-skill');
    }
});

test('install copies all files from rules and skills directories', function (): void {
    $packageDir = dirname(__DIR__);
    $rulesSource = $packageDir . '/rules';
    $skillsSource = $packageDir . '/skills';
    $expectedRulesCount = installerCountFiles($rulesSource);
    $expectedSkillsCount = installerCountFiles($skillsSource);

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
        $actualRulesCount = installerCountFiles($rulesTarget);
        $actualSkillsCount = installerCountFiles($skillsTarget);

        expect($actualRulesCount)->toBe($expectedRulesCount, 'Rules: all source files should be copied');
        expect($actualSkillsCount)->toBe($expectedSkillsCount, 'Skills: all source files should be copied');
        expect($output)->toContain(sprintf('(%d files)', $expectedRulesCount + $expectedSkillsCount));
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
    $targetPath = $root . '/.cursor/rules/security/backend.md';
    installerEnsureDirectory($targetPath);
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

test('resolveProjectRoot returns current working directory', function (): void {
    $result = InstallerPath::resolveProjectRoot();

    expect($result)->toBeString();
    expect(strlen($result))->toBeGreaterThan(0);
});

test('install fails when rules subdirectory path is a file', function (): void {
    $root = installerCreateProjectRoot();
    $targetSubdir = $root . '/.cursor/rules/security';
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

test('install copies security rules from package rules/security directory', function (): void {
    $packageDir = dirname(__DIR__);
    $root = installerCreateProjectRoot();
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install']);
        ob_end_clean();

        $securityDir = $root . '/.cursor/rules/security';

        expect(is_file($securityDir . '/backend.md'))->toBeTrue();
        expect(file_get_contents($securityDir . '/backend.md'))->toBe(file_get_contents($packageDir . '/rules/security/backend.md'));
        expect(is_file($securityDir . '/frontend.md'))->toBeTrue();
        expect(file_get_contents($securityDir . '/frontend.md'))->toBe(file_get_contents($packageDir . '/rules/security/frontend.md'));
        expect(is_file($securityDir . '/mobile.md'))->toBeTrue();
        expect(file_get_contents($securityDir . '/mobile.md'))->toBe(file_get_contents($packageDir . '/rules/security/mobile.md'));
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install always force-copies security rules even without force flag', function (): void {
    $packageDir = dirname(__DIR__);
    $root = installerCreateProjectRoot();
    $securityFile = $root . '/.cursor/rules/security/backend.md';
    installerEnsureDirectory(dirname($securityFile));
    installerWriteFile($securityFile, 'old security content');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);

        ob_start();
        Installer::run(['cursor-rules', 'install']);
        ob_end_clean();

        $expectedContent = file_get_contents($packageDir . '/rules/security/backend.md');
        expect(file_get_contents($securityFile))->toBe($expectedContent);
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
    $targetDir = $root . '/.cursor/rules';
    $targetFile = $targetDir . '/git/conventions.mdc';
    $gitDir = dirname($targetFile);
    installerEnsureDirectory($gitDir);
    installerWriteFile($targetFile, 'old content');
    chmod($gitDir, 0555);
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
        chmod($gitDir, 0755);

        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});
