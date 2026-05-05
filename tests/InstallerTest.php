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

test('gitignore ignores local cursor and claude directories', function (): void {
    $gitignore = file_get_contents(__DIR__ . '/../.gitignore');

    expect($gitignore)->toContain('/.cursor/');
    expect($gitignore)->toContain('/.claude/');
});

test('resolveRulesSource always uses package directory', function (): void {
    $root = installerCreateProjectRoot();
    $packageDir = dirname(__DIR__);

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
    $packageDir = dirname(__DIR__);

    try {
        $source = InstallerPath::resolveRulesSource($root);

        expect($source)->toBe($packageDir . '/rules');
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveSkillsSource always uses package directory', function (): void {
    $root = installerCreateProjectRoot();
    $packageDir = dirname(__DIR__);

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
    $packageDir = dirname(__DIR__);

    try {
        $source = InstallerPath::resolveSkillsSource();

        expect($source)->toBe($packageDir . '/skills');
    } finally {
        installerRemoveDirectory($root);
    }
});

test('install ignores rules directory in project root and uses package source', function (): void {
    $root = installerCreateProjectRoot();
    installerWriteFile($root . '/rules/example.mdc', 'foreign content');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        $exitCode = Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();

        expect($exitCode)->toBe(0);

        $foreignFile = $root . '/.cursor/rules/example.mdc';
        expect(is_file($foreignFile))->toBeFalse();

        $installedDir = $root . '/.cursor/rules';
        expect(is_dir($installedDir))->toBeTrue();
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
        $exitCode = Installer::run(['cursor-rules', 'install', '--editor=cursor']);
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
    $installedFile = $root . '/.cursor/rules/php/core-standards.mdc';
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);

        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();
        $originalContent = file_get_contents($installedFile);
        expect($originalContent)->toBeString();

        file_put_contents($installedFile, 'modified content');

        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();
        expect(file_get_contents($installedFile))->toBe('modified content');

        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor', '--force']);
        ob_end_clean();
        expect(file_get_contents($installedFile))->toBe($originalContent);
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
        Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();
        expect(file_get_contents($installedFile))->toBe('my project-specific content');

        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor', '--force']);
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
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor', '--symlink']);
        ob_end_clean();

        $target = $root . '/.cursor/rules/php/core-standards.mdc';

        expect(is_link($target))->toBeTrue();
        expect(file_get_contents($target))->not->toBeEmpty();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install copies nested directories', function (): void {
    $root = installerCreateProjectRoot();
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();

        $installedFile = $root . '/.cursor/rules/laravel/architecture.mdc';

        expect(is_file($installedFile))->toBeTrue();
        expect(file_get_contents($installedFile))->not->toBeEmpty();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install with editor=cursor copies rules and skills only to .cursor', function (): void {
    $root = installerCreateProjectRoot();
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();

        expect(is_file($root . '/.cursor/rules/php/core-standards.mdc'))->toBeTrue();
        expect(is_file($root . '/.cursor/skills/code-review/SKILL.md'))->toBeTrue();
        expect(is_dir($root . '/.claude/skills'))->toBeFalse();
        expect(is_dir($root . '/.codex/skills'))->toBeFalse();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install with editor=all copies skills to all target directories', function (): void {
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

        foreach (InstallerPath::resolveSkillsTargetDirectories($root, InstallerPath::EDITOR_ALL) as $targetDir) {
            $installedSkill = $targetDir . '/code-review/SKILL.md';
            expect(is_file($installedSkill))->toBeTrue('Skills should be installed to ' . $targetDir);
            expect(file_get_contents($installedSkill))->not->toBeEmpty();
        }
    } finally {
        installerRestoreEnvAndCleanup($homeBefore, $originalCwd, $root);
    }
});

test('install appends output humanization directive to installed skill', function (): void {
    $root = installerCreateProjectRoot();
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();

        $installedSkill = $root . '/.cursor/skills/code-review/SKILL.md';
        $contents = file_get_contents($installedSkill);

        expect($contents)->toContain('## Output Humanization');
        expect($contents)->toContain(
            '- Use [blader/humanizer](https://github.com/blader/humanizer) for all skill outputs to keep the text natural and human-friendly.',
        );
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install does not duplicate output humanization directive in installed skill', function (): void {
    $root = installerCreateProjectRoot();
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();

        $installedSkill = $root . '/.cursor/skills/code-review/SKILL.md';
        $contents = file_get_contents($installedSkill);

        expect(substr_count((string) $contents, '## Output Humanization'))->toBe(1);
        expect(substr_count((string) $contents, '[blader/humanizer](https://github.com/blader/humanizer)'))->toBe(1);
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
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
    $claudeMdCount = InstallerPath::resolveClaudeMdSource() !== null ? 1 : 0;
    $expectedTotalFiles = $expectedRulesCount * count($rulesTargets) + $expectedSkillsCount * count($skillTargets) + $claudeMdCount;
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
        $exitCode = Installer::run(['cursor-rules', 'install', '--editor=cursor']);
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
    $targetDir = $root . '/.cursor/rules/php/core-standards.mdc';
    installerEnsureDirectory($targetDir);
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        $exitCode = Installer::run(['cursor-rules', 'install', '--editor=cursor', '--force']);
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
        $result = InstallerPath::resolveSkillsSource();
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
    $targetSubdir = $root . '/.cursor/rules/php';
    installerEnsureDirectory(dirname($targetSubdir));
    file_put_contents($targetSubdir, 'blocking file');
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        $exitCode = Installer::run(['cursor-rules', 'install', '--editor=cursor']);
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
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();

        $securityDir = $root . '/.cursor/rules/security';

        expect(is_file($securityDir . '/backend.md'))->toBeTrue();
        expect(file_get_contents($securityDir . '/backend.md'))->not->toBeEmpty();
        expect(is_file($securityDir . '/frontend.md'))->toBeTrue();
        expect(file_get_contents($securityDir . '/frontend.md'))->not->toBeEmpty();
        expect(is_file($securityDir . '/mobile.md'))->toBeTrue();
        expect(file_get_contents($securityDir . '/mobile.md'))->not->toBeEmpty();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install always force-copies security rules even without force flag', function (): void {
    $root = installerCreateProjectRoot();
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';
    $packageDir = dirname(__DIR__);
    $originalSecurityContent = file_get_contents($packageDir . '/rules/security/backend.md');

    try {
        chdir($root);

        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();

        $securityFile = $root . '/.cursor/rules/security/backend.md';
        $regularFile = $root . '/.cursor/rules/php/core-standards.mdc';

        file_put_contents($securityFile, 'old security content');
        file_put_contents($regularFile, 'old rules content');

        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();

        expect(file_get_contents($securityFile))->toBe($originalSecurityContent);
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
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=claude']);
        ob_end_clean();

        expect(is_file($root . '/.claude/rules/php/core-standards.mdc'))->toBeTrue();
        expect(is_file($root . '/.claude/skills/code-review/SKILL.md'))->toBeTrue();
        expect(is_dir($root . '/.cursor/rules'))->toBeFalse();
        expect(is_dir($root . '/.codex/rules'))->toBeFalse();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install supports combined force and editor flags for claude', function (): void {
    $root = installerCreateProjectRoot();
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';
    $packageDir = dirname(__DIR__);
    $originalContent = file_get_contents($packageDir . '/rules/php/core-standards.mdc');

    try {
        chdir($root);

        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=claude']);
        ob_end_clean();

        $ruleFile = $root . '/.claude/rules/php/core-standards.mdc';
        file_put_contents($ruleFile, 'old rules');

        ob_start();
        Installer::run(['cursor-rules', 'install', '--force--editor=claude']);
        ob_end_clean();

        expect(file_get_contents($ruleFile))->toBe($originalContent);
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
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=codex']);
        ob_end_clean();

        expect(is_file($root . '/.codex/rules/php/core-standards.mdc'))->toBeTrue();
        expect(is_file($root . '/.codex/skills/code-review/SKILL.md'))->toBeTrue();
        expect(is_dir($root . '/.cursor/rules'))->toBeFalse();
        expect(is_dir($root . '/.claude/rules'))->toBeFalse();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install from package root installs rules and skills into .cursor', function (): void {
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
        $exitCode = Installer::run(['cursor-rules', 'install', '--editor=cursor']);
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
    $targetDir = $root . '/.cursor/rules/php';
    installerEnsureDirectory($targetDir . '/examples');
    chmod($targetDir, 0555);
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        set_error_handler(static fn (): bool => true);
        $exitCode = Installer::run(['cursor-rules', 'install', '--editor=cursor']);
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

test('race-condition-review skill is referenced only by code review skills', function (): void {
    $packageDir = dirname(__DIR__);
    $needle = '@skills/race-condition-review/SKILL.md';
    $skillFiles = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($packageDir . '/skills', FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && $file->getFilename() === 'SKILL.md') {
            $skillFiles[] = $file->getPathname();
        }
    }

    $expectedFiles = [
        $packageDir . '/skills/code-review/SKILL.md',
        $packageDir . '/skills/code-review-github/SKILL.md',
        $packageDir . '/skills/code-review-jira/SKILL.md',
    ];

    foreach ($expectedFiles as $expectedFile) {
        $content = file_get_contents($expectedFile);
        expect($content)->toContain($needle);
    }

    foreach ($skillFiles as $skillFile) {
        if (in_array($skillFile, $expectedFiles, true)) {
            continue;
        }

        $content = file_get_contents($skillFile);
        expect($content)->not->toContain($needle);
    }
});

test('dry review rule is referenced by process-code-review skill', function (): void {
    $packageDir = dirname(__DIR__);
    $content = file_get_contents($packageDir . '/skills/process-code-review/SKILL.md');
    expect($content)->toContain('DRY violations');
});

test('unified resolve-issue skill requires code review before PR creation', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/resolve-issue/SKILL.md');
    expect($content)->toContain('Code review loop passed with no Critical or Moderate findings');
    expect($content)->toContain('Security review completed');
    expect($content)->not->toContain('After checks pass, automatically push');
});

test('resolve-random skills are not shipped in source skills directory', function (): void {
    $packageDir = dirname(__DIR__);
    expect(is_dir($packageDir . '/skills/resolve-random-github-issue'))->toBeFalse();
    expect(is_dir($packageDir . '/skills/resolve-random-jira-issue'))->toBeFalse();
});

test('query scopes rule is present in class refactoring skill', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/class-refactoring/SKILL.md');
    expect($content)->toContain('query scopes');
});

test('test-like-human always runs after code review skills regardless of findings', function (): void {
    $packageDir = dirname(__DIR__);
    $skillFiles = [
        $packageDir . '/skills/code-review/SKILL.md',
        $packageDir . '/skills/code-review-jira/SKILL.md',
    ];

    foreach ($skillFiles as $skillFile) {
        $content = (string) file_get_contents($skillFile);
        expect($content)->not->toContain('If no **Critical** or **Moderate**');
        expect($content)->toMatch('/##\s*After Completion[^#]*Always run @skills\/test-like-human\/SKILL\.md, regardless of code review findings\./s');
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
        $exitCode = Installer::run(['cursor-rules', 'install', '--editor=cursor', '--prune']);
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
    $targetDir = $root . '/.cursor/rules/php';
    $targetFile = $targetDir . '/core.mdc';
    installerWriteFile($targetFile, 'old content');
    chmod($targetDir, 0555);
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        set_error_handler(static fn (): bool => true);
        $exitCode = Installer::run(['cursor-rules', 'install', '--editor=cursor', '--force']);
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
    $packageDir = dirname(__DIR__);
    $claudeMd = $packageDir . '/CLAUDE.md';

    expect(is_file($claudeMd))->toBeTrue();
    expect(file_get_contents($claudeMd))->toContain('Behavioral guidelines');
    expect(file_get_contents($claudeMd))->toContain('Think Before Coding');
    expect(file_get_contents($claudeMd))->toContain('Simplicity First');
    expect(file_get_contents($claudeMd))->toContain('Surgical Changes');
    expect(file_get_contents($claudeMd))->toContain('Goal-Driven Execution');
});

test('resolveEditorFromComposerJson returns editor when configured', function (): void {
    $root = installerCreateProjectRoot();
    file_put_contents($root . '/composer.json', json_encode([
        'extra' => [
            'cursor-rules' => [
                'editor' => 'claude',
            ],
        ],
    ]));

    try {
        $editor = InstallerPath::resolveEditorFromComposerJson($root);

        expect($editor)->toBe('claude');
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveEditorFromComposerJson returns null when no editor configured', function (): void {
    $root = installerCreateProjectRoot();

    try {
        $editor = InstallerPath::resolveEditorFromComposerJson($root);

        expect($editor)->toBeNull();
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveEditorFromComposerJson returns null for invalid editor value', function (): void {
    $root = installerCreateProjectRoot();
    file_put_contents($root . '/composer.json', json_encode([
        'extra' => [
            'cursor-rules' => [
                'editor' => 'invalid',
            ],
        ],
    ]));

    try {
        $editor = InstallerPath::resolveEditorFromComposerJson($root);

        expect($editor)->toBeNull();
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveEditorFromComposerJson returns null when composer.json does not exist', function (): void {
    $root = sys_get_temp_dir() . '/no-composer-' . bin2hex(random_bytes(4));
    mkdir($root, 0777, true);

    try {
        $editor = InstallerPath::resolveEditorFromComposerJson($root);

        expect($editor)->toBeNull();
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveEditorFromComposerJson is case-insensitive for editor key', function (): void {
    $root = installerCreateProjectRoot();
    file_put_contents($root . '/composer.json', json_encode([
        'extra' => [
            'cursor-rules' => [
                'Editor' => 'Claude',
            ],
        ],
    ]));

    try {
        $editor = InstallerPath::resolveEditorFromComposerJson($root);

        expect($editor)->toBe('claude');
    } finally {
        installerRemoveDirectory($root);
    }
});

test('install without --editor returns error', function (): void {
    $exitCode = Installer::run(['cursor-rules', 'install']);

    expect($exitCode)->toBe(1);
});

test('install with --editor flag overrides composer.json editor', function (): void {
    $root = installerCreateProjectRoot();
    file_put_contents($root . '/composer.json', json_encode([
        'extra' => [
            'cursor-rules' => [
                'editor' => 'claude',
            ],
        ],
    ]));
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();

        expect(is_file($root . '/.cursor/rules/php/core-standards.mdc'))->toBeTrue();
        expect(is_dir($root . '/.claude/rules'))->toBeFalse();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('resolveEditorFromComposerJson returns null when extra is not an array', function (): void {
    $root = installerCreateProjectRoot();
    file_put_contents($root . '/composer.json', json_encode([
        'extra' => 'not-an-array',
    ]));

    try {
        $editor = InstallerPath::resolveEditorFromComposerJson($root);

        expect($editor)->toBeNull();
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveEditorFromComposerJson returns null when cursor-rules config is not an array', function (): void {
    $root = installerCreateProjectRoot();
    file_put_contents($root . '/composer.json', json_encode([
        'extra' => [
            'cursor-rules' => 'not-an-array',
        ],
    ]));

    try {
        $editor = InstallerPath::resolveEditorFromComposerJson($root);

        expect($editor)->toBeNull();
    } finally {
        installerRemoveDirectory($root);
    }
});

test('resolveEditorFromComposerJson returns null for invalid JSON', function (): void {
    $root = installerCreateProjectRoot();
    file_put_contents($root . '/composer.json', 'not valid json');

    try {
        $editor = InstallerPath::resolveEditorFromComposerJson($root);

        expect($editor)->toBeNull();
    } finally {
        installerRemoveDirectory($root);
    }
});
