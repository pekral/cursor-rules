<?php

declare(strict_types = 1);

use Pekral\CursorRules\Installer;
use Pest\PendingCalls\TestCall;

dataset('installer-run-commands', [
    'help fallback' => [
        [
            'args' => ['cursor-rules'],
            'expectedExitCode' => 0,
            'expectedOutputFragment' => 'Usage:',
        ],
    ],
    'unknown command' => [
        [
            'args' => ['cursor-rules', 'unknown'],
            'expectedExitCode' => 1,
            'expectedOutputFragment' => null,
        ],
    ],
]);

function installerRegisterRunCommandTest(): TestCall
{
    $testCall = test('run command handling responds according to provided arguments', function (array $scenario): void {
        /** @var array{
         *     args: array<int, string>,
         *     expectedExitCode: int,
         *     expectedOutputFragment: string|null
         * } $scenario
         */
        ob_start();
        $exitCode = Installer::run($scenario['args']);
        $output = (string) ob_get_clean();

        expect($exitCode)->toBe($scenario['expectedExitCode']);

        if ($scenario['expectedOutputFragment'] !== null) {
            expect($output)->toContain($scenario['expectedOutputFragment']);
        }
    });

    if (!$testCall instanceof TestCall) {
        throw new LogicException('Failed to register installer run command dataset test.');
    }

    return $testCall;
}

$runCommandTestCall = installerRegisterRunCommandTest();
$runCommandTestCall->with('installer-run-commands');

test('run shows help when executed without arguments', function (): void {
    ob_start();
    $exitCode = Installer::run(['cursor-rules']);
    $output = (string) ob_get_clean();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('Usage:');
});

test('run returns error code for unknown command input', function (): void {
    $exitCode = Installer::run(['cursor-rules', 'unknown']);

    expect($exitCode)->toBe(1);
});

test('install copies rules from development directory', function (): void {
    $root = installerCreateProjectRoot();
    $targetDir = installerTargetDirectoryFor($root);
    putenv('CURSOR_RULES_TARGET_DIR=' . $targetDir);
    installerWriteFile($root . '/rules/example.mdc', 'dev content');
    $workingDirectory = installerCreateWorkingDirectory($root);

    try {
        installerRunInstallerFrom($workingDirectory, ['cursor-rules', 'install']);
        $installedFile = $targetDir . '/example.mdc';

        expect($installedFile)->toBeFile();
        expect(file_get_contents($installedFile))->toBe('dev content');
    } finally {
        putenv('CURSOR_RULES_TARGET_DIR');
        installerRemoveDirectory($root);
    }
});

test('install falls back to vendor directory', function (): void {
    $root = installerCreateProjectRoot();
    $targetDir = installerTargetDirectoryFor($root);
    putenv('CURSOR_RULES_TARGET_DIR=' . $targetDir);
    $vendorRules = $root . '/vendor/pekral/cursor-rules/rules';
    installerWriteFile($vendorRules . '/vendor-only.mdc', 'vendor content');

    try {
        installerRunInstallerFrom($root, ['cursor-rules', 'install']);
        $installedFile = $targetDir . '/vendor-only.mdc';

        expect($installedFile)->toBeFile();
        expect(file_get_contents($installedFile))->toBe('vendor content');
    } finally {
        putenv('CURSOR_RULES_TARGET_DIR');
        installerRemoveDirectory($root);
    }
});

test('project rules take precedence when vendor rules also exist', function (): void {
    $root = installerCreateProjectRoot();
    $targetDir = installerTargetDirectoryFor($root);
    putenv('CURSOR_RULES_TARGET_DIR=' . $targetDir);
    $vendorRules = $root . '/vendor/pekral/cursor-rules/rules';
    installerWriteFile($root . '/rules/priority.mdc', 'project content');
    installerWriteFile($vendorRules . '/priority.mdc', 'vendor content');

    try {
        installerRunInstallerFrom($root, ['cursor-rules', 'install']);
        $installedFile = $targetDir . '/priority.mdc';

        expect($installedFile)->toBeFile();
        expect(file_get_contents($installedFile))->toBe('project content');
    } finally {
        putenv('CURSOR_RULES_TARGET_DIR');
        installerRemoveDirectory($root);
    }
});

test('default target directory is used when no override is provided', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/default.mdc', 'default content');
    $workingDirectory = installerCreateWorkingDirectory($root);
    $supportsHiddenDirectories = installerSupportsCursorDirectoryCreation();

    try {
        $exitCode = installerRunInstallerFrom($workingDirectory, ['cursor-rules', 'install']);

        $installedFile = $root . '/.cursor/rules/default.mdc';

        if ($supportsHiddenDirectories) {
            expect($exitCode)->toBe(0);
            expect($installedFile)->toBeFile();
            expect(file_get_contents($installedFile))->toBe('default content');
        } else {
            expect($exitCode)->toBe(1);
            expect(is_file($installedFile))->toBeFalse();
        }
    } finally {
        installerRemoveDirectory($root);
    }
});

test('empty target directory override falls back to default location', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/empty-override.mdc', 'empty override content');
    putenv('CURSOR_RULES_TARGET_DIR=');
    $supportsHiddenDirectories = installerSupportsCursorDirectoryCreation();

    try {
        $exitCode = installerRunInstallerFrom($root, ['cursor-rules', 'install']);
        $installedFile = $root . '/.cursor/rules/empty-override.mdc';

        if ($supportsHiddenDirectories) {
            expect($exitCode)->toBe(0);
            expect($installedFile)->toBeFile();
            expect(file_get_contents($installedFile))->toBe('empty override content');
        } else {
            expect($exitCode)->toBe(1);
            expect(is_file($installedFile))->toBeFalse();
        }
    } finally {
        putenv('CURSOR_RULES_TARGET_DIR');
        installerRemoveDirectory($root);
    }
});

test('install copies nested directories', function (): void {
    $root = installerCreateProjectRoot();
    $targetDir = installerTargetDirectoryFor($root);
    putenv('CURSOR_RULES_TARGET_DIR=' . $targetDir);
    installerWriteFile($root . '/rules/nested/example.mdc', 'nested content');

    try {
        installerRunInstallerFrom($root, ['cursor-rules', 'install']);
        $installedFile = $targetDir . '/nested/example.mdc';

        expect($installedFile)->toBeFile();
        expect(file_get_contents($installedFile))->toBe('nested content');
    } finally {
        putenv('CURSOR_RULES_TARGET_DIR');
        installerRemoveDirectory($root);
    }
});

test('install creates empty nested directories from source', function (): void {
    $root = installerCreateProjectRoot();
    $targetDir = installerTargetDirectoryFor($root);
    putenv('CURSOR_RULES_TARGET_DIR=' . $targetDir);
    $emptyDirectory = $root . '/rules/templates/snippets/deep';
    installerEnsureDirectory($emptyDirectory);

    try {
        installerRunInstallerFrom($root, ['cursor-rules', 'install']);

        expect(is_dir($targetDir . '/templates/snippets/deep'))->toBeTrue();
    } finally {
        putenv('CURSOR_RULES_TARGET_DIR');
        installerRemoveDirectory($root);
    }
});

test('install fails when target directory cannot be created', function (): void {
    if (stripos(PHP_OS, 'WIN') === 0) {
        expect(true)->toBeTrue();

        return;
    }

    $root = installerCreateProjectRoot();
    $targetDir = $root . '/restricted/rules';
    $restrictedParent = dirname($targetDir);
    installerEnsureDirectory($restrictedParent);
    installerWriteFile($root . '/rules/restricted.mdc', 'content');
    expect(chmod($restrictedParent, 0555))->toBeTrue();
    putenv('CURSOR_RULES_TARGET_DIR=' . $targetDir);

    try {
        $exitCode = installerRunInstallerFrom($root, ['cursor-rules', 'install']);
        expect($exitCode)->toBe(1);
    } finally {
        chmod($restrictedParent, 0755);
        putenv('CURSOR_RULES_TARGET_DIR');
        installerRemoveDirectory($root);
    }
});

test('install respects the force flag', function (): void {
    $root = installerCreateProjectRoot();
    $targetDir = installerTargetDirectoryFor($root);
    putenv('CURSOR_RULES_TARGET_DIR=' . $targetDir);
    installerWriteFile($root . '/rules/force.mdc', 'new content');
    $installedFile = $targetDir . '/force.mdc';
    installerWriteFile($installedFile, 'existing content');

    try {
        installerRunInstallerFrom($root, ['cursor-rules', 'install']);
        expect(file_get_contents($installedFile))->toBe('existing content');

        installerRunInstallerFrom($root, ['cursor-rules', 'install', '--force']);
        expect(file_get_contents($installedFile))->toBe('new content');
    } finally {
        putenv('CURSOR_RULES_TARGET_DIR');
        installerRemoveDirectory($root);
    }
});

test('installer output reports only processed files', function (): void {
    $root = installerCreateProjectRoot();
    $targetDir = installerTargetDirectoryFor($root);
    putenv('CURSOR_RULES_TARGET_DIR=' . $targetDir);
    installerWriteFile($root . '/rules/fresh.mdc', 'fresh');
    installerWriteFile($root . '/rules/skip.mdc', 'new');
    installerWriteFile($targetDir . '/skip.mdc', 'existing');
    $originalCwd = getcwd();
    $originalCwd = $originalCwd === false ? '' : $originalCwd;

    try {
        chdir($root);
        ob_start();
        $exitCode = Installer::run(['cursor-rules', 'install']);
        $output = (string) ob_get_clean();

        expect($exitCode)->toBe(0);
        expect($targetDir . '/fresh.mdc')->toBeFile();
        expect($targetDir . '/skip.mdc')->toBeFile();

        preg_match('/\((\d+) files\)/', $output, $matches);
        expect($matches[1] ?? null)->toBe('1');
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        putenv('CURSOR_RULES_TARGET_DIR');
        installerRemoveDirectory($root);
    }
});

test('install fails when target directory path is a file', function (): void {
    $root = installerCreateProjectRoot();
    $targetDir = installerTargetDirectoryFor($root);
    installerWriteFile($targetDir, 'conflict');
    putenv('CURSOR_RULES_TARGET_DIR=' . $targetDir);
    installerWriteFile($root . '/rules/file.mdc', 'content');

    try {
        $exitCode = installerRunInstallerFrom($root, ['cursor-rules', 'install']);

        expect($exitCode)->toBe(1);
        expect(is_file($targetDir))->toBeTrue();
    } finally {
        putenv('CURSOR_RULES_TARGET_DIR');
        installerRemoveDirectory($targetDir);
        installerRemoveDirectory($root);
    }
});

test('install fails when existing target cannot be removed', function (): void {
    $root = installerCreateProjectRoot();
    $targetDir = installerTargetDirectoryFor($root);
    putenv('CURSOR_RULES_TARGET_DIR=' . $targetDir);
    installerWriteFile($root . '/rules/block.mdc', 'content');
    installerEnsureDirectory($targetDir . '/block.mdc');

    try {
        $exitCode = installerRunInstallerFrom($root, ['cursor-rules', 'install', '--force']);

        expect($exitCode)->toBe(1);
    } finally {
        putenv('CURSOR_RULES_TARGET_DIR');
        installerRemoveDirectory($root);
    }
});

test('install fails when existing target cannot be removed because of permissions', function (): void {
    if (stripos(PHP_OS, 'WIN') === 0) {
        expect(true)->toBeTrue();

        return;
    }

    $root = installerCreateProjectRoot();
    $targetDir = installerTargetDirectoryFor($root);
    installerEnsureDirectory($targetDir);
    installerWriteFile($root . '/rules/locked.mdc', 'fresh content');
    $blockedFile = $targetDir . '/locked.mdc';
    installerWriteFile($blockedFile, 'stale content');
    expect(chmod($targetDir, 0555))->toBeTrue();
    putenv('CURSOR_RULES_TARGET_DIR=' . $targetDir);

    try {
        $exitCode = installerRunInstallerFrom($root, ['cursor-rules', 'install', '--force']);
        expect($exitCode)->toBe(1);
    } finally {
        chmod($targetDir, 0755);
        putenv('CURSOR_RULES_TARGET_DIR');
        installerRemoveDirectory($root);
    }
});

test('install creates symlink when possible', function (): void {
    if (installerSymlinkUnsupported()) {
        expect(true)->toBeTrue();

        return;
    }

    $root = installerCreateProjectRoot();
    $targetDir = installerTargetDirectoryFor($root);
    putenv('CURSOR_RULES_TARGET_DIR=' . $targetDir);
    putenv('CURSOR_RULES_DISABLE_SYMLINKS');
    installerWriteFile($root . '/rules/link.mdc', 'link content');

    try {
        installerRunInstallerFrom($root, ['cursor-rules', 'install', '--symlink']);
        $target = $targetDir . '/link.mdc';

        expect(is_link($target))->toBeTrue();
        expect(file_get_contents($target))->toBe('link content');
    } finally {
        putenv('CURSOR_RULES_TARGET_DIR');
        putenv('CURSOR_RULES_DISABLE_SYMLINKS');
        installerRemoveDirectory($root);
    }
});

test('install falls back to copy when symlink fails', function (): void {
    if (installerSymlinkUnsupported()) {
        expect(true)->toBeTrue();

        return;
    }

    $root = installerCreateProjectRoot();
    $targetDir = installerTargetDirectoryFor($root);
    putenv('CURSOR_RULES_TARGET_DIR=' . $targetDir);
    putenv('CURSOR_RULES_FAIL_SYMLINK=1');
    installerWriteFile($root . '/rules/link-fallback.mdc', 'copy content');

    try {
        installerRunInstallerFrom($root, ['cursor-rules', 'install', '--symlink']);
        $target = $targetDir . '/link-fallback.mdc';

        expect($target)->toBeFile();
        expect(is_link($target))->toBeFalse();
    } finally {
        putenv('CURSOR_RULES_TARGET_DIR');
        putenv('CURSOR_RULES_FAIL_SYMLINK');
        installerRemoveDirectory($root);
    }
});

test('windows environments disable symlinks automatically', function (): void {
    $root = installerCreateProjectRoot();
    $targetDir = installerTargetDirectoryFor($root);
    putenv('CURSOR_RULES_TARGET_DIR=' . $targetDir);
    putenv('CURSOR_RULES_FORCE_WINDOWS=1');
    installerWriteFile($root . '/rules/windows.mdc', 'windows content');

    try {
        installerRunInstallerFrom($root, ['cursor-rules', 'install', '--symlink']);
        $installedFile = $targetDir . '/windows.mdc';

        expect($installedFile)->toBeFile();
        expect(is_link($installedFile))->toBeFalse();
    } finally {
        putenv('CURSOR_RULES_FORCE_WINDOWS');
        putenv('CURSOR_RULES_TARGET_DIR');
        installerRemoveDirectory($root);
    }
});

test('project root override is honoured', function (): void {
    $root = installerCreateProjectRoot();
    $targetDir = installerTargetDirectoryFor($root);
    $originalCwd = getcwd();
    $originalCwd = $originalCwd === false ? '' : $originalCwd;
    putenv('CURSOR_RULES_PROJECT_ROOT=' . $root);
    putenv('CURSOR_RULES_TARGET_DIR=' . $targetDir);
    installerWriteFile($root . '/rules/override.mdc', 'override content');

    try {
        chdir(__DIR__);
        ob_start();
        $exitCode = Installer::run(['cursor-rules', 'install']);
        ob_end_clean();

        expect($exitCode)->toBe(0);
        expect($targetDir . '/override.mdc')->toBeFile();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        putenv('CURSOR_RULES_PROJECT_ROOT');
        putenv('CURSOR_RULES_TARGET_DIR');
        installerRemoveDirectory($root);
    }
});

test('install stops when copy fails', function (): void {
    $root = installerCreateProjectRoot();
    $targetDir = installerTargetDirectoryFor($root);
    putenv('CURSOR_RULES_TARGET_DIR=' . $targetDir);
    putenv('CURSOR_RULES_FAIL_COPY=1');
    installerWriteFile($root . '/rules/unreadable.mdc', 'content');

    try {
        $exitCode = installerRunInstallerFrom($root, ['cursor-rules', 'install']);
        expect($exitCode)->toBe(1);
    } finally {
        putenv('CURSOR_RULES_TARGET_DIR');
        putenv('CURSOR_RULES_FAIL_COPY');
        installerRemoveDirectory($root);
    }
});

test('symlinks can be disabled via environment flag', function (): void {
    if (installerSymlinkUnsupported()) {
        expect(true)->toBeTrue();

        return;
    }

    $root = installerCreateProjectRoot();
    $targetDir = installerTargetDirectoryFor($root);
    putenv('CURSOR_RULES_TARGET_DIR=' . $targetDir);
    putenv('CURSOR_RULES_DISABLE_SYMLINKS=1');
    installerWriteFile($root . '/rules/link-disabled.mdc', 'flag content');

    try {
        installerRunInstallerFrom($root, ['cursor-rules', 'install', '--symlink', '--force']);
        $target = $targetDir . '/link-disabled.mdc';

        expect($target)->toBeFile();
        expect(is_link($target))->toBeFalse();
    } finally {
        putenv('CURSOR_RULES_TARGET_DIR');
        putenv('CURSOR_RULES_DISABLE_SYMLINKS');
        installerRemoveDirectory($root);
    }
});

test('run fails when no source is available', function (): void {
    $root = installerCreateProjectRoot();
    $targetDir = installerTargetDirectoryFor($root);
    putenv('CURSOR_RULES_TARGET_DIR=' . $targetDir);

    try {
        $exitCode = installerRunInstallerFrom($root, ['cursor-rules', 'install']);
        expect($exitCode)->toBe(1);
    } finally {
        putenv('CURSOR_RULES_TARGET_DIR');
        installerRemoveDirectory($root);
    }
});

test('project root search falls back to configured directory', function (): void {
    $root = installerCreateProjectRoot();
    $targetDir = installerTargetDirectoryFor($root);
    $originalCwd = getcwd();
    $originalCwd = $originalCwd === false ? '' : $originalCwd;
    $workingDir = $root . '/fallback/workdir';
    installerEnsureDirectory($workingDir);
    putenv('CURSOR_RULES_TARGET_DIR=' . $targetDir);
    putenv('CURSOR_RULES_PROJECT_ROOT_FALLBACK=' . $root);
    installerWriteFile($root . '/rules/temp.mdc', 'temp');

    try {
        chdir($workingDir);
        rmdir($workingDir);
        ob_start();
        $exitCode = Installer::run(['cursor-rules', 'install']);
        ob_end_clean();

        expect($exitCode)->toBe(0);
        expect($targetDir . '/temp.mdc')->toBeFile();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        putenv('CURSOR_RULES_PROJECT_ROOT_FALLBACK');
        putenv('CURSOR_RULES_TARGET_DIR');
        installerRemoveDirectory($root);
    }
});

test('project root search falls back to system temp when nothing is found', function (): void {
    $originalCwd = getcwd();
    $originalCwd = $originalCwd === false ? '' : $originalCwd;
    $missingDir = sys_get_temp_dir() . '/cwd-missing-' . bin2hex(random_bytes(4));
    installerEnsureDirectory($missingDir);
    chdir($missingDir);
    rmdir($missingDir);

    try {
        ob_start();
        $exitCode = Installer::run(['cursor-rules', 'install']);
        ob_end_clean();

        expect($exitCode)->toBe(1);
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }
    }
});
