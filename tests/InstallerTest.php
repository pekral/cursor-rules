<?php

declare(strict_types = 1);

use Pekral\CursorRules\Installer;
use Pekral\CursorRules\InstallerFailure;
use Pekral\CursorRules\InstallerPath;
use Pekral\CursorRules\InstallerPruner;

test('run shows help when executed without arguments', function (): void {
    ob_start();
    $exitCode = Installer::run(['cursor-rules']);
    $output = (string) ob_get_clean();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('Usage:');
    expect($output)->toContain('--editor=EDITOR');
});

test('run returns error code for unknown command', function (): void {
    $exitCode = Installer::run(['cursor-rules', 'unknown']);

    expect($exitCode)->toBe(1);
});

test('run returns error code for invalid editor', function (): void {
    $exitCode = Installer::run(['cursor-rules', 'install', '--editor=invalid']);

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

test('install never overwrites existing project.mdc in target', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/project.mdc', 'package default content');
    $installedFile = $root . '/.cursor/rules/project.mdc';
    installerWriteFile($installedFile, 'my project-specific content');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);

        ob_start();
        Installer::run(['cursor-rules', 'install']);
        ob_end_clean();
        expect(file_get_contents($installedFile))->toBe('my project-specific content');

        ob_start();
        Installer::run(['cursor-rules', 'install', '--force']);
        ob_end_clean();
        expect(file_get_contents($installedFile))->toBe('my project-specific content');
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

test('install with default editor copies rules and skills only to .cursor', function (): void {
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

        expect(is_file($root . '/.cursor/rules/example.mdc'))->toBeTrue();
        expect(is_file($root . '/.cursor/skills/test-skill/SKILL.md'))->toBeTrue();
        expect(is_dir($root . '/.claude/skills'))->toBeFalse();
        expect(is_dir($root . '/.codex/skills'))->toBeFalse();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install injects humanizer instruction into installed skills', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/skills/humanize/SKILL.md', "---\nname: humanize\ndescription: test skill\n---\n\n# Body");
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install']);
        ob_end_clean();

        $installedSkill = $root . '/.cursor/skills/humanize/SKILL.md';
        $installedContents = file_get_contents($installedSkill);
        $installedContents = $installedContents !== false ? $installedContents : '';

        expect($installedContents)->toContain('https://github.com/blader/humanizer');
        expect($installedContents)->toContain('## Output Humanization');
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install does not duplicate humanizer instruction in installed skills', function (): void {
    $root = installerCreateProjectRoot();
    $skillWithHumanizer = <<<'MD'
---
name: humanize
description: test skill
---

## Output Humanization
- Use [blader/humanizer](https://github.com/blader/humanizer) for all skill outputs to keep the text natural and human-friendly.
MD;

    installerWriteFile(
        $root . '/skills/humanize/SKILL.md',
        $skillWithHumanizer . PHP_EOL,
    );
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install']);
        ob_end_clean();

        $installedSkill = $root . '/.cursor/skills/humanize/SKILL.md';
        $installedContents = file_get_contents($installedSkill);
        $installedContents = $installedContents !== false ? $installedContents : '';
        preg_match_all('/https:\/\/github\.com\/blader\/humanizer/', $installedContents, $matches);

        expect(count($matches[0]))->toBe(1);
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install with editor=all copies skills to all target directories', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/example.mdc', 'rules');
    installerWriteFile($root . '/skills/test-skill/SKILL.md', 'skill content');
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

        foreach (InstallerPath::resolveSkillsTargetDirectories($root, InstallerPath::EDITOR_ALL) as $targetDir) {
            $installedSkill = $targetDir . '/test-skill/SKILL.md';
            expect(is_file($installedSkill))->toBeTrue('Skills should be installed to ' . $targetDir);
            $installedContents = file_get_contents($installedSkill);
            $installedContents = $installedContents !== false ? $installedContents : '';
            expect($installedContents)->toContain('skill content');
            expect($installedContents)->toContain('https://github.com/blader/humanizer');
        }
    } finally {
        installerRestoreEnvAndCleanup($homeBefore, $originalCwd, $root);
    }
});

test('install with editor=all copies all files to all rule and skill directories', function (): void {
    $packageDir = dirname(__DIR__);
    $rulesSource = $packageDir . '/rules';
    $skillsSource = $packageDir . '/skills';
    $expectedRulesCount = installerCountFiles($rulesSource);
    $expectedSkillsCount = installerCountFiles($skillsSource);
    $root = installerCreateProjectRoot();
    $homeEnv = getenv('HOME');
    $homeBefore = $homeEnv !== false && $homeEnv !== '' ? $homeEnv : getenv('USERPROFILE');
    putenv('HOME=' . $root);

    if (getenv('USERPROFILE') !== false) {
        putenv('USERPROFILE=' . $root);
    }

    $rulesTargets = InstallerPath::resolveRulesTargetDirectories($root, InstallerPath::EDITOR_ALL);
    $skillTargets = InstallerPath::resolveSkillsTargetDirectories($root, InstallerPath::EDITOR_ALL);
    $expectedTotalFiles = $expectedRulesCount * count($rulesTargets) + $expectedSkillsCount * count($skillTargets);
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        $exitCode = Installer::run(['cursor-rules', 'install', '--editor=all']);
        $output = (string) ob_get_clean();

        expect($exitCode)->toBe(0);

        foreach ($rulesTargets as $rulesTarget) {
            $actualRulesCount = installerCountFiles($rulesTarget);
            expect($actualRulesCount)->toBe($expectedRulesCount, 'Rules: all source files in ' . $rulesTarget);
        }

        foreach ($skillTargets as $skillsTarget) {
            $actualSkillsCount = installerCountFiles($skillsTarget);
            expect($actualSkillsCount)->toBe($expectedSkillsCount, 'Skills: all source files in ' . $skillsTarget);
        }

        expect($output)->toContain(sprintf('(%d files,', $expectedTotalFiles));
    } finally {
        installerRestoreEnvAndCleanup($homeBefore, $originalCwd, $root);
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

test('install copies security rules from rules/security directory', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/example.mdc', 'rules');
    installerWriteFile($root . '/rules/security/backend.md', 'backend security');
    installerWriteFile($root . '/rules/security/frontend.md', 'frontend security');
    installerWriteFile($root . '/rules/security/mobile.md', 'mobile security');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install']);
        ob_end_clean();

        $securityDir = $root . '/.cursor/rules/security';

        expect(is_file($securityDir . '/backend.md'))->toBeTrue();
        expect(file_get_contents($securityDir . '/backend.md'))->toBe('backend security');
        expect(is_file($securityDir . '/frontend.md'))->toBeTrue();
        expect(file_get_contents($securityDir . '/frontend.md'))->toBe('frontend security');
        expect(is_file($securityDir . '/mobile.md'))->toBeTrue();
        expect(file_get_contents($securityDir . '/mobile.md'))->toBe('mobile security');
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install always force-copies security rules even without force flag', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/example.mdc', 'rules');
    installerWriteFile($root . '/rules/security/backend.md', 'new security content');
    $securityFile = $root . '/.cursor/rules/security/backend.md';
    installerWriteFile($securityFile, 'old security content');
    $regularFile = $root . '/.cursor/rules/example.mdc';
    installerWriteFile($regularFile, 'old rules content');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);

        ob_start();
        Installer::run(['cursor-rules', 'install']);
        ob_end_clean();

        expect(file_get_contents($securityFile))->toBe('new security content');
        expect(file_get_contents($regularFile))->toBe('old rules content');
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

test('install with editor=claude copies to .claude only', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/example.mdc', 'rules');
    installerWriteFile($root . '/skills/test-skill/SKILL.md', 'skill content');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=claude']);
        ob_end_clean();

        expect(is_file($root . '/.claude/rules/example.mdc'))->toBeTrue();
        expect(is_file($root . '/.claude/skills/test-skill/SKILL.md'))->toBeTrue();
        expect(is_dir($root . '/.cursor/rules'))->toBeFalse();
        expect(is_dir($root . '/.codex/rules'))->toBeFalse();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install with editor=codex copies to .codex only', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/example.mdc', 'rules');
    installerWriteFile($root . '/skills/test-skill/SKILL.md', 'skill content');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=codex']);
        ob_end_clean();

        expect(is_file($root . '/.codex/rules/example.mdc'))->toBeTrue();
        expect(is_file($root . '/.codex/skills/test-skill/SKILL.md'))->toBeTrue();
        expect(is_dir($root . '/.cursor/rules'))->toBeFalse();
        expect(is_dir($root . '/.claude/rules'))->toBeFalse();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install from package root installs rules and skills into .cursor by default', function (): void {
    $packageRoot = dirname(__DIR__);

    if (!file_exists($packageRoot . '/composer.json') || !is_dir($packageRoot . '/rules')) {
        expect(true)->toBeTrue();

        return;
    }

    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($packageRoot);
        ob_start();
        $exitCode = Installer::run(['cursor-rules', 'install']);
        $output = (string) ob_get_clean();

        expect($exitCode)->toBe(0);
        expect($output)->toContain('installed');
        expect(is_dir($packageRoot . '/.cursor/rules'))->toBeTrue();
        expect(is_dir($packageRoot . '/.cursor/skills'))->toBeTrue();
        expect(installerCountFiles($packageRoot . '/.cursor/rules'))->toBeGreaterThan(0);
        expect(installerCountFiles($packageRoot . '/.cursor/skills'))->toBeGreaterThan(0);
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }
    }
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

test('run shows prune option in help output', function (): void {
    ob_start();
    $exitCode = Installer::run(['cursor-rules']);
    $output = (string) ob_get_clean();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('--prune');
});

test('install with prune removes files from target that no longer exist in source', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/skills/current-skill/SKILL.md', 'current skill');
    installerWriteFile($root . '/.cursor/skills/current-skill/SKILL.md', 'current skill');
    installerWriteFile($root . '/.cursor/skills/orphaned-skill/SKILL.md', 'orphaned content');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--prune']);
        ob_end_clean();

        expect(is_file($root . '/.cursor/skills/current-skill/SKILL.md'))->toBeTrue();
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
    installerWriteFile($root . '/skills/current-skill/SKILL.md', 'current skill');
    installerWriteFile($root . '/.cursor/skills/orphaned-skill/SKILL.md', 'orphaned content');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install']);
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
    installerWriteFile($root . '/rules/active.mdc', 'active rule');
    installerWriteFile($root . '/.cursor/rules/active.mdc', 'active rule');
    installerWriteFile($root . '/.cursor/rules/removed.mdc', 'removed rule');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--prune']);
        ob_end_clean();

        expect(is_file($root . '/.cursor/rules/active.mdc'))->toBeTrue();
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
    installerWriteFile($root . '/skills/keep-skill/SKILL.md', 'keep');
    installerWriteFile($root . '/.cursor/skills/keep-skill/SKILL.md', 'keep');
    installerWriteFile($root . '/.cursor/skills/drop-skill/SKILL.md', 'drop');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--prune']);
        $output = (string) ob_get_clean();

        expect($output)->toContain('1 pruned');
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install with prune on non-existent target directory does nothing', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/skills/some-skill/SKILL.md', 'content');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        $exitCode = Installer::run(['cursor-rules', 'install', '--prune']);
        ob_end_clean();

        expect($exitCode)->toBe(0);
    } finally {
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
        expect(true)->toBeTrue();

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
        expect(true)->toBeTrue();

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

        expect(true)->toBeTrue();
    } finally {
        chmod($root . '/target', 0755);
        installerRemoveDirectory($root);
    }
});

test('copySkillDefinitionWithHumanizer throws when source file is unreadable', function (): void {
    $reflection = new ReflectionClass(Installer::class);
    $method = $reflection->getMethod('copySkillDefinitionWithHumanizer');
    $method->setAccessible(true);

    set_error_handler(static fn (): bool => true);

    try {
        expect(
            fn (): mixed => $method->invoke(
                null,
                '/non-existent/SKILL.md',
                '/tmp/target-skill.md',
            ),
        )->toThrow(InstallerFailure::class);
    } finally {
        restore_error_handler();
    }
});

test('copySkillDefinitionWithHumanizer throws when destination write fails', function (): void {
    $root = installerCreateProjectRoot();
    $sourceFile = $root . '/source/SKILL.md';
    installerWriteFile($sourceFile, "---\nname: test\ndescription: test\n---");
    $destinationFile = $root . '/missing/target/SKILL.md';

    $reflection = new ReflectionClass(Installer::class);
    $method = $reflection->getMethod('copySkillDefinitionWithHumanizer');
    $method->setAccessible(true);

    try {
        set_error_handler(static fn (): bool => true);

        expect(
            fn (): mixed => $method->invoke(
                null,
                $sourceFile,
                $destinationFile,
            ),
        )->toThrow(InstallerFailure::class);
    } finally {
        restore_error_handler();
        installerRemoveDirectory($root);
    }
});
