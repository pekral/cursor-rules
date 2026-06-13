<?php

declare(strict_types = 1);

use Pekral\CursorRules\Installer;
use Pekral\CursorRules\InstallerClaudeSettings;
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
    $agentsSource = $packageDir . '/agents';
    $expectedRulesCount = installerCountFiles($rulesSource);
    $expectedSkillsCount = installerCountFiles($skillsSource);
    $expectedAgentsCount = installerCountFiles($agentsSource);
    $root = installerCreateProjectRoot();
    $homeEnv = getenv('HOME');
    $homeBefore = $homeEnv !== false && $homeEnv !== '' ? $homeEnv : getenv('USERPROFILE');
    putenv('HOME=' . $root);

    if (getenv('USERPROFILE') !== false) {
        putenv('USERPROFILE=' . $root);
    }

    $rulesTargets = InstallerPath::resolveRulesTargetDirectories($root, InstallerPath::EDITOR_ALL);
    $skillTargets = InstallerPath::resolveSkillsTargetDirectories($root, InstallerPath::EDITOR_ALL);
    $agentTargets = InstallerPath::resolveAgentsTargetDirectories($root, InstallerPath::EDITOR_ALL);
    $claudeMdCount = InstallerPath::resolveClaudeMdSource() !== null ? 1 : 0;
    $expectedTotalFiles = $expectedRulesCount * count($rulesTargets)
        + $expectedSkillsCount * count($skillTargets)
        + $expectedAgentsCount * count($agentTargets)
        + $claudeMdCount;
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

        foreach ($agentTargets as $agentsTarget) {
            $actualAgentsCount = installerCountFiles($agentsTarget);
            expect($actualAgentsCount)->toBe($expectedAgentsCount, 'Agents: all source files in ' . $agentsTarget);
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

    $reviewLoopPos = strpos($content, '## Code quality and review loop');
    $testingPos = strpos($content, '## Testing');
    $pullRequestPos = strpos($content, '## Pull request');
    expect($reviewLoopPos)->not->toBeFalse();
    expect($testingPos)->not->toBeFalse();
    expect($pullRequestPos)->not->toBeFalse();

    if (!is_int($reviewLoopPos) || !is_int($testingPos) || !is_int($pullRequestPos)) {
        return;
    }

    expect($reviewLoopPos)->toBeLessThan($pullRequestPos);
    expect($testingPos)->toBeLessThan($pullRequestPos);

    expect($content)->toContain('### Technical report → codebase tracker (GitHub PR)');
    expect($content)->toContain('### Non-technical report → original task tracker');

    $reviewLoopSection = substr($content, $reviewLoopPos, $pullRequestPos - $reviewLoopPos);
    expect($reviewLoopSection)->not->toContain('@skills/process-code-review/SKILL.md to apply');
    expect($reviewLoopSection)->not->toContain('@skills/code-review-github/SKILL.md');
    expect($reviewLoopSection)->not->toContain('@skills/code-review-jira/SKILL.md');
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

test('laravel rules prefer filled()/blank() helpers over strict empty-string comparisons', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/laravel.mdc');

    expect($content)->toContain('## String Emptiness Checks');
    expect($content)->toContain('`filled()`');
    expect($content)->toContain('`blank()`');
    expect($content)->toContain('`!== \'\'`');
    expect($content)->toContain('`=== \'\'`');
});

test('laravel rules extend Database and Eloquent with index and EXPLAIN guidance (issue #525)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/laravel.mdc');

    expect($content)->toContain('verify indexes for every high-cardinality');
    expect($content)->toContain('check `EXPLAIN` before shipping');
    expect($content)->toContain('left-most prefix');
    expect($content)->toContain('Do not add indexes blindly');
});

test('laravel rules forbid dispatching full Eloquent models to queued jobs (issue #525)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/laravel.mdc');

    expect($content)->toContain('Do not dispatch full Eloquent models to queued jobs');
    expect($content)->toContain('Fetch fresh models inside `handle()`');
    expect($content)->toContain('serialize only the explicit fields needed by the job');
    expect($content)->toContain('Queue constructors must only accept lightweight scalar values');
});

test('laravel rules tighten Dependency Injection with hot-path and lazy resolution guidance (issue #525)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/laravel.mdc');

    expect($content)->toContain('Do not call `app()`, `resolve()`, or `$container->make()` inside loops or hot paths');
    expect($content)->toContain('Bind stateless expensive services as singletons');
    expect($content)->toContain('Prefer lazy service resolution');
    expect($content)->toContain('Keep service constructors lightweight');
});

test('laravel rules require selective and lightweight middleware (issue #525)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/laravel.mdc');

    expect($content)->toContain('Apply middleware selectively');
    expect($content)->toContain('Put cheap fast-failing middleware before expensive middleware');
    expect($content)->toContain('Do not perform database queries, service orchestration, or external API calls in middleware');
});

test('laravel rules add Stateless Runtime, Caching, and Long-Running Runtime Safety sections (issue #525)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/laravel.mdc');

    expect($content)->toContain('## Stateless Runtime');
    expect($content)->toContain('Production application servers must be disposable');
    expect($content)->toContain('`onOneServer()` or another explicit distributed mutex');

    expect($content)->toContain('## Caching');
    expect($content)->toContain('Use Redis or another shared cache for sessions, queues, cross-server locks');
    expect($content)->toContain('Always set explicit TTLs for cached values');
    expect($content)->toContain('Do not cache user-specific or permission-sensitive data without including the relevant identity');

    expect($content)->toContain('## Long-Running Runtime Safety');
    expect($content)->toContain('safe for long-running PHP processes');
    expect($content)->toContain('Octane');
    expect($content)->toContain('worker recycling');
});

test('architecture rules enumerate the seven allowed business logic layers including Eloquent models', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/architecture.mdc');

    expect($content)->toContain('## Business Logic Layers');
    expect($content)->toContain('seven class types');
    expect($content)->toContain('**Actions**');
    expect($content)->toContain('**Model Services**');
    expect($content)->toContain('**Repositories**');
    expect($content)->toContain('**ModelManagers**');
    expect($content)->toContain('**Data Validators**');
    expect($content)->toContain('**Data Builders**');
    expect($content)->toContain('**Eloquent models**');
    expect($content)->toContain('simple, self-contained domain methods');
    expect($content)->toContain('@skills/class-refactoring/SKILL.md');
});

test('laravel rules permit simple self-contained logic on Eloquent models', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/laravel.mdc');

    expect($content)->toContain('Simple, self-contained domain logic may live as methods on the model.');
    expect($content)->toContain('$user->isActive()');
    expect($content)->toContain('Forbidden on models');
    expect($content)->toContain('$user->sendWelcomeEmail()');
    expect($content)->toContain('lazy-load relationships count as new database queries');
    expect($content)->not->toContain('Keep business logic out of models.');
    expect($content)->not->toContain('Keep business logic out of controllers, middleware, Blade views, and Eloquent models.');
});

test('architecture bullets remain under the Architecture heading and Business Logic Layers sits before Actions', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/architecture.mdc');

    $architectureHeading = strpos($content, "\n## Architecture\n");
    $multitenancyBullet = strpos($content, 'Multitenancy remains mandatory');
    $customHelpersBullet = strpos($content, '**Custom Helpers:**');
    $businessLogicHeading = strpos($content, "\n## Business Logic Layers\n");
    $actionsHeading = strpos($content, "\n## Actions\n");

    assert($architectureHeading !== false);
    assert($multitenancyBullet !== false);
    assert($customHelpersBullet !== false);
    assert($businessLogicHeading !== false);
    assert($actionsHeading !== false);

    expect($architectureHeading)->toBeLessThan($multitenancyBullet);
    expect($multitenancyBullet)->toBeLessThan($businessLogicHeading);
    expect($customHelpersBullet)->toBeLessThan($businessLogicHeading);
    expect($businessLogicHeading)->toBeLessThan($actionsHeading);
});

test('core standards forbid speculative project-owned interfaces', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/php/core-standards.mdc');

    expect($content)->toContain('Do not introduce PHP `interface` types speculatively');
    expect($content)->toContain('at least two non-test consumers, and/or at least two non-test implementations');
    expect($content)->toContain('test doubles, mocks, and fakes do not count toward either threshold');
});

test('code-review skill flags speculative interfaces in Core Analysis', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md');

    expect($content)->toContain('Speculative interfaces');
    expect($content)->toContain('neither at least two non-test consumers nor at least two non-test implementations');
});

test('assignment-compliance-check skill exists with required sections and writes no files', function (): void {
    $packageDir = dirname(__DIR__);
    $skillPath = $packageDir . '/skills/assignment-compliance-check/SKILL.md';

    expect(file_exists($skillPath))->toBeTrue();

    $content = (string) file_get_contents($skillPath);

    expect($content)->toContain('name: assignment-compliance-check');
    expect($content)->toContain('## Constraints');
    expect($content)->toContain('## Use when');
    expect($content)->toContain('## Required approach');
    expect($content)->toContain('## Output Format');
    expect($content)->toContain('## Done when');
    expect($content)->toContain('Report **only Critical**');
    expect($content)->toContain('must not** write any output to disk');
    expect($content)->toContain('No files were created on disk');
    expect($content)->not->toContain('.cursor-rules-reports');
});

test('assignment-compliance-check returns markdown to the caller without publishing on its own', function (): void {
    $packageDir = dirname(__DIR__);
    $compliance = (string) file_get_contents($packageDir . '/skills/assignment-compliance-check/SKILL.md');
    $canonical = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md');
    $github = (string) file_get_contents($packageDir . '/skills/code-review-github/SKILL.md');
    $jira = (string) file_get_contents($packageDir . '/skills/code-review-jira/SKILL.md');

    expect($compliance)->toContain('### 5. Return the report to the caller');
    expect($compliance)->toContain(
        '**Do not call `gh issue comment`, `acli`, the GitHub MCP server\'s `add_issue_comment`, or any JIRA write endpoint.**',
    );
    expect($compliance)->toContain('no linked issue — assignment compliance skipped');
    expect($compliance)->toContain('single consolidated linked-tracker comment authored by `@skills/pr-summary/SKILL.md`');
    expect($compliance)->toContain('**must not** embed the Assignment Compliance content into the **GitHub PR** comment');
    expect($compliance)->not->toContain('post via `gh issue comment <number> --body ...`');
    expect($compliance)->not->toContain('post via `acli`');
    expect($compliance)->not->toContain('Embed the returned section verbatim');
    expect($compliance)->not->toContain('Where in the code');

    foreach ([$canonical, $github, $jira] as $wrapper) {
        expect($wrapper)->toContain('**Do not embed**');
        expect($wrapper)->not->toContain('Embed the returned section verbatim');
        expect($wrapper)->not->toContain('Embed it verbatim into the GitHub PR comment');
    }

    expect($github)->toContain('no linked issue — assignment compliance skipped');
    expect($github)->toContain('one consolidated comment** per CR run');

    expect($jira)->toContain('one consolidated comment** per CR run');
    expect($jira)->not->toContain('**do not duplicate** its Critical gaps inside the JIRA non-technical summary');
});

test('assignment-compliance-check omits the block on clean assignments and removes "what is satisfied" / "open questions" lists', function (): void {
    $packageDir = dirname(__DIR__);
    $compliance = (string) file_get_contents($packageDir . '/skills/assignment-compliance-check/SKILL.md');
    $canonical = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md');
    $github = (string) file_get_contents($packageDir . '/skills/code-review-github/SKILL.md');
    $jira = (string) file_get_contents($packageDir . '/skills/code-review-jira/SKILL.md');

    expect($compliance)->toContain('no critical gaps — assignment compliance block omitted');
    expect($compliance)->toContain('**only when at least one Critical gap exists**');
    expect($compliance)->not->toContain('No critical gaps identified — implementation satisfies every stated requirement');
    expect($compliance)->not->toContain('### What is satisfied');
    expect($compliance)->not->toContain('### Open questions for the reviewer');
    expect($compliance)->not->toContain('one bullet per requirement the PR clearly meets');
    expect($compliance)->not->toContain('No critical gaps>');

    foreach ([$canonical, $github, $jira] as $wrapper) {
        expect($wrapper)->toContain('no critical gaps — assignment compliance block omitted');
        expect($wrapper)->toContain('**only when a block is returned**');
    }
});

test('refactoring requires pre-refactor 100% coverage and unchanged tests in the refactor commit (issue #493)', function (): void {
    $packageDir = dirname(__DIR__);
    $rule = (string) file_get_contents($packageDir . '/rules/refactoring/general.mdc');
    $classRefactoring = (string) file_get_contents($packageDir . '/skills/class-refactoring/SKILL.md');
    $codeReview = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md');

    expect($rule)->toContain('## Test Coverage Contract (mandatory — issue #493)');
    expect($rule)->toContain('Before the refactor commit — verify 100% coverage of the target lines.');
    expect($rule)->toContain('Add missing tests in a dedicated commit before the refactor commit.');
    expect($rule)->toContain('The refactor commit must not modify pre-existing tests.');
    expect($rule)->toContain('`test(scope): cover <area> before refactor`');
    expect($rule)->toContain('**Enforce the Test Coverage Contract above on every refactor PR.**');

    expect($classRefactoring)->toContain('### Test Coverage Gate (mandatory pre-flight — issue #493)');
    expect($classRefactoring)->toContain('**If coverage is below 100% on the target lines, stop and write the missing tests first.**');
    expect($classRefactoring)->toContain('**Test assertion logic must not change during the refactor.**');
    expect($classRefactoring)->toContain('`@rules/refactoring/general.mdc` Test Coverage Contract');

    expect($codeReview)->toContain('**Refactoring test-coverage contract (issue #493)**');
    expect($codeReview)->toContain('Walk the PR commit history and verify the refactor commit is **preceded by a dedicated test commit**');
    expect($codeReview)->toContain('Verify the refactor commit **modifies no pre-existing test file**');
    expect($codeReview)->toContain('Verify the coverage of the refactor commit alone');
});

test('CR run produces one consolidated linked-tracker comment per linked issue (issue #498)', function (): void {
    $packageDir = dirname(__DIR__);
    $prSummary = (string) file_get_contents($packageDir . '/skills/pr-summary/SKILL.md');
    $github = (string) file_get_contents($packageDir . '/skills/code-review-github/SKILL.md');
    $jira = (string) file_get_contents($packageDir . '/skills/code-review-jira/SKILL.md');
    $githubTemplate = (string) file_get_contents($packageDir . '/skills/pr-summary/templates/pr-summary-github.md');
    $jiraTemplate = (string) file_get_contents($packageDir . '/skills/pr-summary/templates/pr-summary-jira.md');

    expect($prSummary)->toContain('Embedded blocks (consolidation contract — issue #498)');
    expect($prSummary)->toContain('append them **verbatim** after `How to test`');
    expect($prSummary)->toContain('published once per linked tracker target');

    expect($github)->toContain('#### Linked-issue consolidated summary (mandatory — single comment per linked issue)');
    expect($github)->toContain('Consolidation contract (issue #498)');
    expect($github)->toContain('exactly one comment per linked issue');

    expect($jira)->toContain('#### JIRA (consolidated non-technical comment — single-comment upsert per actor)');
    expect($jira)->toContain('Consolidation contract (issue #498)');
    expect($jira)->toContain('exactly one JIRA comment per (ticket, acli actor)');

    expect($githubTemplate)->toContain('{embedded_blocks}');
    expect($githubTemplate)->toContain('@skills/assignment-compliance-check/SKILL.md');
    expect($jiraTemplate)->toContain('{embedded_blocks}');
    expect($jiraTemplate)->toContain('@skills/assignment-compliance-check/SKILL.md');
});

test('CR skills publish through the publish helper — GitHub always-new, JIRA single-comment upsert keyed by the current actor', function (): void {
    $packageDir = dirname(__DIR__);

    $githubScript = $packageDir . '/skills/code-review-github/scripts/upsert-comment.sh';
    $jiraScript = $packageDir . '/skills/code-review-jira/scripts/upsert-comment.sh';

    expect(is_file($githubScript))->toBeTrue();
    expect(is_executable($githubScript))->toBeTrue();
    expect(is_file($jiraScript))->toBeTrue();
    expect(is_executable($jiraScript))->toBeTrue();

    $githubScriptBody = (string) file_get_contents($githubScript);
    expect($githubScriptBody)->toContain('MARKER_KEY="${3:-cr-comment}"');
    expect($githubScriptBody)->toContain('<!-- ${MARKER_KEY}:actor=${ACTOR} -->');
    expect($githubScriptBody)->toContain('gh api user --jq .login');
    // Issue #519: a transient `gh api user` failure (rate limit, network blip,
    // token refresh) used to crash with a misleading "is gh authenticated?"
    // message because stderr and exit code were both swallowed. The script
    // now retries up to three times, captures the underlying stderr, and
    // surfaces the real error to the caller.
    expect($githubScriptBody)->toContain('ACTOR_STDERR="$(mktemp)"');
    expect($githubScriptBody)->toContain('trap \'rm -f "$ACTOR_STDERR"\' EXIT');
    expect($githubScriptBody)->toContain('for attempt in 1 2 3; do');
    expect($githubScriptBody)->toContain('gh api user --jq .login 2>"$ACTOR_STDERR"');
    expect($githubScriptBody)->toContain('failed to resolve current GitHub actor after 3 attempts');
    expect($githubScriptBody)->toContain('(run: gh auth status)');
    expect($githubScriptBody)->not->toContain('gh api user --jq .login 2>/dev/null');
    // Always-new comment on GitHub: the PATCH branch was removed by user
    // request — every CR run POSTs a fresh comment so the PR thread keeps a
    // chronological audit trail. The marker stays for per-actor traceability.
    expect($githubScriptBody)->not->toContain('-X PATCH');
    expect($githubScriptBody)->not->toContain('action=updated');
    expect($githubScriptBody)->not->toContain('repos/${NWO}/issues/comments/${EXISTING_ID}');
    expect($githubScriptBody)->toContain('action=created');
    expect($githubScriptBody)->toContain('repos/${NWO}/issues/${NUMBER}/comments');
    // Issue #519: `gh api -f body=@-` published a comment whose body was the
    // literal string `@-` because only the typed `-F/--field` flag expands
    // `@-` to stdin. The script now builds a JSON payload via jq and feeds
    // it through `--input -`, so neither `-f body=@-` nor `-F body=@-`
    // should appear.
    expect($githubScriptBody)->not->toContain('-f body=@-');
    expect($githubScriptBody)->not->toContain('-F body=@-');
    expect($githubScriptBody)->toContain('jq -n --arg body "$BODY" \'{body:$body}\'');
    expect($githubScriptBody)->toContain('--input -');

    $jiraScriptBody = (string) file_get_contents($jiraScript);
    expect($jiraScriptBody)->toContain('MARKER_KEY="${3:-cr-comment}"');
    expect($jiraScriptBody)->toContain('{anchor:${MARKER_KEY}-actor-${ACTOR_SLUG}}');
    expect($jiraScriptBody)->toContain('acli jira me --json');
    expect($jiraScriptBody)->toContain('acli jira workitem comment edit');
    expect($jiraScriptBody)->toContain('acli jira workitem comment add');
    expect($jiraScriptBody)->toMatch('/if ! grep -Fq "\$MARKER" <<<"\$BODY"; then\s+BODY="\$\{BODY\}\s+\$\{MARKER\}"/');

    $github = (string) file_get_contents($packageDir . '/skills/code-review-github/SKILL.md');
    $jira = (string) file_get_contents($packageDir . '/skills/code-review-jira/SKILL.md');
    $prSummary = (string) file_get_contents($packageDir . '/skills/pr-summary/SKILL.md');

    foreach ([$github, $jira, $prSummary] as $skill) {
        expect($skill)->toContain('skills/code-review-github/scripts/upsert-comment.sh');
        expect($skill)->toContain('<!-- cr-comment:actor=<gh-login> -->');
    }

    expect($jira)->toContain('skills/code-review-jira/scripts/upsert-comment.sh');
    expect($jira)->toContain('{anchor:cr-comment-actor-<slug>}');
    expect($prSummary)->toContain('skills/code-review-jira/scripts/upsert-comment.sh');
    expect($prSummary)->toContain('{anchor:cr-comment-actor-<slug>}');

    foreach ([$github, $jira] as $skill) {
        expect(stripos($skill, 'always-new comment'))->not->toBeFalse();
        expect($skill)->toContain('POSTs a new comment');
        expect($skill)->not->toContain('edit the existing comment in place');
        expect($skill)->not->toContain('Replying to code review from');
    }

    $processCodeReview = (string) file_get_contents($packageDir . '/skills/process-code-review/SKILL.md');
    expect($processCodeReview)->toContain('skills/code-review-github/scripts/upsert-comment.sh');
    expect($processCodeReview)->toContain('cr-status');
    expect($processCodeReview)->toContain('<!-- cr-status:actor=<gh-login> -->');
    expect($processCodeReview)->toContain('{anchor:cr-status-actor-<slug>}');
    expect($processCodeReview)->not->toContain('Replying to code review from');
    expect($processCodeReview)->not->toContain('Post resolved items and status updates as a new PR comment');

    foreach ([
        $packageDir . '/skills/code-review-github/templates/pr-comment-output.md',
        $packageDir . '/skills/code-review-jira/templates/github-output.md',
        $packageDir . '/skills/code-review/templates/review-output.md',
    ] as $template) {
        $body = (string) file_get_contents($template);
        expect($body)->toContain('**Last updated:**');
        expect($body)->not->toContain('## Previous CR Status');
    }
});

test('process-code-review enforces a convergence loop with quiet iterations and a single final publish', function (): void {
    $packageDir = dirname(__DIR__);
    $process = (string) file_get_contents($packageDir . '/skills/process-code-review/SKILL.md');
    $github = (string) file_get_contents($packageDir . '/skills/code-review-github/SKILL.md');
    $jira = (string) file_get_contents($packageDir . '/skills/code-review-jira/SKILL.md');

    expect($process)->toContain('### Review loop (mandatory — convergence gate)');
    expect($process)->toContain('`maxIterations = 5`');
    expect($process)->toContain('`criticalCount + moderateCount == 0`');
    expect($process)->toContain('do not publish; return findings as in-memory markdown for this loop iteration only');
    expect($process)->toContain('### Finalization (only after Review loop converged)');
    expect($process)->toContain('### PR update (only after Review loop converged)');
    expect($process)->toContain('### Completion (final, single publish)');

    expect($github)->toContain('Quiet mode (loop iterations from `@skills/process-code-review/SKILL.md`)');
    expect($github)->toContain('skip the entire Post Results step');
    expect($jira)->toContain('Quiet mode (loop iterations from `@skills/process-code-review/SKILL.md`)');
    expect($jira)->toContain('skip all publishing');
});

test('JIRA non-technical CR summary delegates to pr-summary Wiki Markup template', function (): void {
    $packageDir = dirname(__DIR__);
    $template = (string) file_get_contents($packageDir . '/skills/pr-summary/templates/pr-summary-jira.md');
    $rule = (string) file_get_contents($packageDir . '/rules/jira/general.mdc');
    $skill = (string) file_get_contents($packageDir . '/skills/code-review-jira/SKILL.md');

    expect($template)->toContain('h2. Summary of changes');
    expect($template)->toContain('h2. How to test');
    expect($template)->not->toContain('## Summary of changes');
    expect($template)->not->toContain('```');

    expect($rule)->toContain('Wiki markup conversion cheatsheet');
    expect($rule)->toContain('`{code:php} ... {code}`');
    expect($rule)->toContain('`[label|https://example.com]`');

    expect($skill)->toContain('Delegate the JIRA comment to `@skills/pr-summary/SKILL.md`');
    expect($skill)->toContain('@skills/pr-summary/templates/pr-summary-jira.md');
    expect(is_file($packageDir . '/skills/code-review-jira/templates/jira-output.md'))->toBeFalse();
});

test('GitHub PR comment templates use a compact AI-parseable header with severity icons', function (): void {
    $packageDir = dirname(__DIR__);

    foreach (['code-review-github/templates/pr-comment-output.md', 'code-review-jira/templates/github-output.md'] as $path) {
        $content = (string) file_get_contents($packageDir . '/skills/' . $path);

        expect($content)->toContain('# Code Review');
        expect($content)->toContain('**Status:** clean / needs-fix');
        expect($content)->toContain('**Counts:** Critical {n} · Moderate {n} · Minor {n} · Refactoring {n}');
        expect($content)->toContain('### 🔴 Critical 1.');
        expect($content)->toContain('### 🟠 Moderate 1.');
        expect($content)->toContain('### 🟡 Minor 1.');
        expect($content)->toContain('- **Location:**');
        expect($content)->toContain('- **Rule:**');
        expect($content)->toContain('- **Faulty Example:**');
        expect($content)->toContain('- **Suggested fix:**');
        expect($content)->toContain('```php');
    }
});

test('code-review skill enforces strict rule compliance and architecture conformance', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md');

    expect($content)->toContain('**Strict rule compliance (mandatory walk-through)**');
    expect($content)->toContain('scan the diff for any pattern that matches a numbered or bulleted rule');
    expect($content)->toContain('raise one finding per matched violation');
    expect($content)->toContain('**Architecture conformance (Laravel)**');
    expect($content)->toContain('section-by-section deep-dive for `@rules/laravel/architecture.mdc`');
    expect($content)->toContain('seven allowed homes including the Eloquent-model carve-out');
    expect($content)->toContain('Default severity for rule violations:');
    expect($content)->toContain('apply the **Strict rule compliance** stratification');
    expect($content)->not->toContain('Do not review formatting, linting, or trivial issues');
});

test('code review skills delegate the non-technical issue-tracker summary to pr-summary', function (): void {
    $packageDir = dirname(__DIR__);
    $github = (string) file_get_contents($packageDir . '/skills/code-review-github/SKILL.md');
    $jira = (string) file_get_contents($packageDir . '/skills/code-review-jira/SKILL.md');
    $canonical = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md');

    expect($github)->toContain('#### Linked-issue consolidated summary (mandatory — single comment per linked issue)');
    expect($github)->toContain('every linked issue');
    expect($github)->toContain('closingIssues[]');
    expect($github)->toContain('skills/code-review-github/scripts/upsert-comment.sh');
    expect($github)->toContain('plus a non-technical summary to every linked issue');
    expect($github)->toContain('issue-tracker summary status');
    expect($github)->toContain('cross-repo issue, lacking write access');
    expect($github)->toContain('@skills/pr-summary/SKILL.md');
    expect($github)->toContain('@skills/pr-summary/templates/pr-summary-github.md');

    expect($jira)->toContain('#### Linked GitHub issues (consolidated mirror — always-new comment per CR run)');
    expect($jira)->toContain('skills/code-review-github/scripts/upsert-comment.sh');
    expect($jira)->toContain('no linked GitHub issue — mirror skipped');
    expect($jira)->toContain('cross-repo issue, lacking write access');
    expect($jira)->toContain('@skills/pr-summary/SKILL.md');
    expect($jira)->toContain('@skills/pr-summary/templates/pr-summary-jira.md');

    expect($canonical)->toContain('must** delegate the **single consolidated comment on every linked issue**');
    expect($canonical)->toContain('every linked issue');
    expect($canonical)->toContain('@skills/pr-summary/SKILL.md');
});

test('every code review skill invokes assignment-compliance-check', function (): void {
    $packageDir = dirname(__DIR__);

    foreach (['code-review', 'code-review-github', 'code-review-jira'] as $skill) {
        $content = (string) file_get_contents($packageDir . '/skills/' . $skill . '/SKILL.md');
        expect($content)->toContain('@skills/assignment-compliance-check/SKILL.md');
    }
});

test('readme reports the current skill count and lists tester-cookbook and security-threat-analysis', function (): void {
    $packageDir = dirname(__DIR__);
    $readme = (string) file_get_contents($packageDir . '/README.md');
    $entries = scandir($packageDir . '/skills');
    assert($entries !== false);
    $skillCount = count(array_filter(
        $entries,
        static fn (string $entry): bool => $entry !== '.' && $entry !== '..' && is_dir($packageDir . '/skills/' . $entry),
    ));

    expect($readme)->toContain($skillCount . ' comprehensive Agent skills');
    expect($readme)->toContain($skillCount . ' skills for issue resolution');
    expect($readme)->toContain('`tester-cookbook`');
    expect($readme)->toContain('`security-threat-analysis`');
});

test('class-refactoring skill surfaces the speculative-interface refactoring', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/class-refactoring/SKILL.md');

    expect($content)->toContain('**Speculative interfaces:**');
    expect($content)->toContain('@rules/php/core-standards.mdc');
});

test('class-refactoring skill enforces the seven business logic layers including Eloquent models', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/class-refactoring/SKILL.md');

    expect($content)->toContain('Business Logic Layers');
    expect($content)->toContain('seven allowed class types');
    expect($content)->toContain('**Actions**');
    expect($content)->toContain('**Model Services**');
    expect($content)->toContain('**Repositories**');
    expect($content)->toContain('**ModelManagers**');
    expect($content)->toContain('**Data Validators**');
    expect($content)->toContain('**Data Builders**');
    expect($content)->toContain('**Eloquent model**');
});

test('code-review skill After Completion section keeps test-like-human on demand', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md');

    expect($content)->not->toMatch('/##\s*After Completion[^#]*Always run @skills\/test-like-human\/SKILL\.md/s');
    expect($content)->toMatch('/##\s*After Completion[^#]*Do \*\*not\*\* auto-invoke `@skills\/test-like-human\/SKILL\.md`/s');
});

test('code-review-jira skill After Completion section keeps test-like-human on demand', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/code-review-jira/SKILL.md');

    expect($content)->not->toMatch('/##\s*After Completion[^#]*Always run @skills\/test-like-human\/SKILL\.md/s');
    expect($content)->toMatch('/##\s*After Completion[^#]*Do \*\*not\*\* auto-invoke `@skills\/test-like-human\/SKILL\.md`/s');
});

test('CR and resolution skills never auto-invoke test-like-human', function (): void {
    $packageDir = dirname(__DIR__);
    $forbiddenSubstrings = [
        'Always run @skills/test-like-human/SKILL.md, regardless of code review findings',
        'Run @skills/test-like-human/SKILL.md if changes are testable',
        '- Run `@skills/test-like-human/SKILL.md`',
        '2. Run `@skills/test-like-human/SKILL.md`',
    ];
    $skills = ['code-review', 'code-review-github', 'code-review-jira', 'process-code-review', 'resolve-issue'];

    foreach ($skills as $skill) {
        $content = (string) file_get_contents($packageDir . '/skills/' . $skill . '/SKILL.md');

        foreach ($forbiddenSubstrings as $needle) {
            expect($content)->not->toContain($needle);
        }
    }
});

test('every code review skill references class-refactoring skill', function (): void {
    $packageDir = dirname(__DIR__);
    $needle = '@skills/class-refactoring/SKILL.md';
    $reviewSkills = [
        $packageDir . '/skills/code-review/SKILL.md',
        $packageDir . '/skills/code-review-github/SKILL.md',
        $packageDir . '/skills/code-review-jira/SKILL.md',
    ];

    foreach ($reviewSkills as $skillFile) {
        $content = (string) file_get_contents($skillFile);
        expect($content)->toContain($needle);
    }
});

test('code review skills constrain refactoring lens to PR diff', function (): void {
    $packageDir = dirname(__DIR__);
    $reviewSkills = [
        $packageDir . '/skills/code-review/SKILL.md',
        $packageDir . '/skills/code-review-github/SKILL.md',
        $packageDir . '/skills/code-review-jira/SKILL.md',
    ];

    foreach ($reviewSkills as $skillFile) {
        $content = (string) file_get_contents($skillFile);
        expect($content)->toContain('Refactoring & Tech Debt (DRY)');
        expect($content)->toContain('untouched code');
    }
});

test('code review templates include refactoring tech debt section', function (): void {
    $packageDir = dirname(__DIR__);
    $templates = [
        $packageDir . '/skills/code-review/templates/review-output.md',
        $packageDir . '/skills/code-review-github/templates/pr-comment-output.md',
        $packageDir . '/skills/code-review-jira/templates/github-output.md',
    ];

    foreach ($templates as $template) {
        $content = (string) file_get_contents($template);
        expect($content)->toContain('## Refactoring (DRY / tech debt)');
        expect($content)->toContain('{n} Refactoring');
    }
});

test('code review output omits empty sections instead of rendering placeholders', function (): void {
    $packageDir = dirname(__DIR__);

    $templates = [
        $packageDir . '/skills/code-review/templates/review-output.md',
        $packageDir . '/skills/code-review-github/templates/pr-comment-output.md',
        $packageDir . '/skills/code-review-jira/templates/github-output.md',
    ];

    foreach ($templates as $template) {
        $content = (string) file_get_contents($template);
        expect($content)->toContain('Section visibility — render only sections that have content.');
        expect($content)->toContain('Render only when at least one Critical, Moderate, or Minor finding exists.');
        expect($content)->toContain('Render only when at least one in-scope refactoring item exists.');
        expect($content)->toContain('Render only when at least one out-of-scope structural improvement is justified by a rule.');
    }

    $skills = [
        $packageDir . '/skills/code-review/SKILL.md',
        $packageDir . '/skills/code-review-github/SKILL.md',
        $packageDir . '/skills/code-review-jira/SKILL.md',
    ];

    foreach ($skills as $skillFile) {
        $content = (string) file_get_contents($skillFile);
        expect($content)->toContain('**Omit empty sections entirely.**');
        // Counts line is the canonical "clean state" signal after the issue #528 follow-up — the Coverage line is no longer always rendered.
        expect($content)->toContain('the Counts line is the clean signal');
    }

    $githubSkill = (string) file_get_contents($packageDir . '/skills/code-review-github/SKILL.md');
    expect($githubSkill)->not->toContain('post: "No findings identified"');
});

test('install preserves executable bit on shipped scripts', function (): void {
    $root = installerCreateProjectRoot();
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();

        $installedScripts = [
            $root . '/.cursor/skills/code-review-github/scripts/load-issue.sh',
            $root . '/.cursor/skills/code-review-jira/scripts/load-issue.sh',
        ];

        foreach ($installedScripts as $script) {
            expect(is_file($script))->toBeTrue($script . ' should exist after install');
            expect(is_executable($script))->toBeTrue($script . ' must be executable after install');
        }
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('github code review skills do not describe inline review comment workflow', function (): void {
    $packageDir = dirname(__DIR__);
    $githubFacingSkills = [
        $packageDir . '/skills/code-review-github/SKILL.md',
        $packageDir . '/skills/code-review-github/templates/pr-comment-output.md',
        $packageDir . '/skills/code-review-jira/SKILL.md',
        $packageDir . '/skills/code-review-jira/templates/github-output.md',
    ];

    foreach ($githubFacingSkills as $skillFile) {
        $content = (string) file_get_contents($skillFile);
        expect($content)->not->toContain('/pulls/{pr}/reviews');
        expect($content)->not->toContain('comments[]');
        expect($content)->not->toContain('event=COMMENT');
        expect($content)->not->toContain('event=REQUEST_CHANGES');
        expect($content)->not->toContain('inline review comment');
    }
});

test('github load-issue script is shipped, executable, and documents the same shape as JIRA', function (): void {
    $packageDir = dirname(__DIR__);
    $script = $packageDir . '/skills/code-review-github/scripts/load-issue.sh';

    expect(file_exists($script))->toBeTrue();
    expect(is_executable($script))->toBeTrue();

    $content = (string) file_get_contents($script);
    expect($content)->toStartWith('#!/usr/bin/env bash');
    expect($content)->toContain('Usage: load-issue.sh <NUMBER|URL>');
    expect($content)->toContain('"kind"');
    expect($content)->toContain('"comments"');
    expect($content)->toContain('"closingIssues"');
    expect($content)->toContain('"closingPullRequests"');
    expect($content)->toContain('"statusCheckRollup"');
    expect($content)->toContain('(www\.)?github\.com');
});

test('github-consuming skills route context loading through load-issue.sh', function (): void {
    $packageDir = dirname(__DIR__);
    $skills = [
        $packageDir . '/skills/resolve-issue/SKILL.md',
        $packageDir . '/skills/code-review-github/SKILL.md',
        $packageDir . '/skills/process-code-review/SKILL.md',
        $packageDir . '/skills/merge-github-pr/SKILL.md',
    ];

    foreach ($skills as $skillFile) {
        $content = (string) file_get_contents($skillFile);
        expect($content)->toContain('skills/code-review-github/scripts/load-issue.sh');
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

test('reports/general.mdc rule ships in the package and declares the canonical language statement', function (): void {
    $packageDir = dirname(__DIR__);
    $rulePath = $packageDir . '/rules/reports/general.mdc';

    expect(is_file($rulePath))->toBeTrue();

    $content = (string) file_get_contents($rulePath);

    expect($content)->toContain('Tracker-Published Reports — Language');
    expect($content)->toContain('same language as the source assignment');
    expect($content)->toContain('Czech');
    expect($content)->toContain('Code identifiers stay verbatim');
    expect($content)->toContain('@rules/git/general.mdc');
});

test('every tracker-publishing skill references @rules/reports/general.mdc', function (): void {
    $packageDir = dirname(__DIR__);
    $trackerPublishingSkills = [
        $packageDir . '/skills/pr-summary/SKILL.md',
        $packageDir . '/skills/code-review/SKILL.md',
        $packageDir . '/skills/code-review-github/SKILL.md',
        $packageDir . '/skills/code-review-jira/SKILL.md',
        $packageDir . '/skills/process-code-review/SKILL.md',
        $packageDir . '/skills/security-review/SKILL.md',
        $packageDir . '/skills/security-threat-analysis/SKILL.md',
        $packageDir . '/skills/assignment-compliance-check/SKILL.md',
        $packageDir . '/skills/resolve-issue/SKILL.md',
        $packageDir . '/skills/test-like-human/SKILL.md',
        $packageDir . '/skills/tester-cookbook/SKILL.md',
        $packageDir . '/skills/prepare-issue-context/SKILL.md',
    ];

    foreach ($trackerPublishingSkills as $skillFile) {
        $content = (string) file_get_contents($skillFile);

        $hasReference = str_contains($content, '@rules/reports/general.mdc');

        expect($hasReference)->toBeTrue($skillFile . ' must reference the shared tracker-report language rule (@rules/reports/general.mdc)');
    }
});

test('no tracker-publishing skill still carries the obsolete "must be in English" constraint', function (): void {
    $packageDir = dirname(__DIR__);
    $forbiddenPatterns = [
        '/^[-*]\s*All output must be in English\s*$/m',
        '/^[-*]\s*All output posted to GitHub must be in English\s*$/m',
        '/^[-*]\s*GitHub output must be in English\s*$/m',
        '/^[-*]\s*All CR output must be written in English\s*$/m',
        '/^[-*]\s*Output must be in English\s*$/m',
    ];
    $skills = [
        $packageDir . '/skills/code-review/SKILL.md',
        $packageDir . '/skills/code-review-github/SKILL.md',
        $packageDir . '/skills/code-review-jira/SKILL.md',
        $packageDir . '/skills/process-code-review/SKILL.md',
        $packageDir . '/skills/security-review/SKILL.md',
        $packageDir . '/skills/security-threat-analysis/SKILL.md',
    ];

    foreach ($skills as $skillFile) {
        $content = (string) file_get_contents($skillFile);

        foreach ($forbiddenPatterns as $pattern) {
            expect((bool) preg_match($pattern, $content))->toBeFalse(
                $skillFile . ' still carries an obsolete English-only constraint matching ' . $pattern,
            );
        }
    }
});

test('readme rules overview lists the reports/general.mdc rule', function (): void {
    $packageDir = dirname(__DIR__);
    $readme = (string) file_get_contents($packageDir . '/README.md');

    expect($readme)->toContain('`reports/general.mdc`');
    expect($readme)->toContain('Language rule for reports published to issue trackers');
});

test('reports/general.mdc declares the GitHub-PR technical-CR English exception', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/reports/general.mdc');

    expect($content)->toContain('Exception — technical CR findings on the GitHub PR');
    expect($content)->toContain('canonical English');
    expect($content)->toContain('@skills/code-review-github/SKILL.md');
    expect($content)->toContain('@skills/process-code-review/SKILL.md');
    expect($content)->toContain('exception does **not** extend to');
    expect($content)->toContain('pr-summary');
});

test('reports/general.mdc bans bilingual parentheses and mid-comment language mixing', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/reports/general.mdc');

    expect($content)->toContain('never mix that language with another natural language');
    expect($content)->toContain('No bilingual parentheses');
    expect($content)->toContain('Kritické (Critical)');
    expect((bool) preg_match('/use the Czech equivalents \(e\.g\. \*Kritické\*, \*Závažné\*, \*Drobné\*\)/', $content))->toBeFalse();
});

test('CR wrapper skills carry the GitHub-PR English exception in their constraints', function (): void {
    $packageDir = dirname(__DIR__);
    $crWrapperSkills = [
        $packageDir . '/skills/code-review-github/SKILL.md',
        $packageDir . '/skills/code-review-jira/SKILL.md',
        $packageDir . '/skills/code-review/SKILL.md',
        $packageDir . '/skills/process-code-review/SKILL.md',
        $packageDir . '/skills/security-review/SKILL.md',
        $packageDir . '/skills/security-threat-analysis/SKILL.md',
        $packageDir . '/skills/resolve-issue/SKILL.md',
    ];

    foreach ($crWrapperSkills as $skillFile) {
        $content = (string) file_get_contents($skillFile);

        $namesException = str_contains($content, 'Exception — technical CR findings on the GitHub PR');
        $mentionsCanonicalEnglish = str_contains($content, 'canonical English');

        expect($namesException && $mentionsCanonicalEnglish)->toBeTrue(
            $skillFile . ' must cite the GitHub-PR technical-CR English exception from @rules/reports/general.mdc',
        );
    }
});

test('help text documents the --allow-bundled-scripts flag', function (): void {
    ob_start();
    $exitCode = Installer::run(['cursor-rules']);
    $output = (string) ob_get_clean();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('--allow-bundled-scripts');
    expect($output)->toContain('~/.claude/settings.json');
    expect($output)->toContain('Opt-in');
});

test('normalizeCliArguments splits --allow-bundled-scripts from a concatenated argv blob', function (): void {
    $normalized = InstallerPath::normalizeCliArguments(['cursor-rules', 'install', '--editor=claude--allow-bundled-scripts']);

    expect($normalized)->toContain('--editor=claude');
    expect($normalized)->toContain('--allow-bundled-scripts');
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

test('InstallerClaudeSettings exposes the two bundled-script permission patterns', function (): void {
    $patterns = InstallerClaudeSettings::getBundledScriptPermissions();

    expect($patterns)->toBe([
        'Bash(*skills/code-review-github/scripts/load-issue.sh:*)',
        'Bash(*skills/code-review-jira/scripts/load-issue.sh:*)',
    ]);
});

test('InstallerClaudeSettings resolveSettingsPath joins HOME with /.claude/settings.json', function (): void {
    expect(InstallerClaudeSettings::resolveSettingsPath('/tmp/fakehome'))->toBe('/tmp/fakehome/.claude/settings.json');
});

test('ensureBundledScriptPermissions creates a fresh settings.json with both patterns', function (): void {
    $home = sys_get_temp_dir() . '/claude-settings-' . bin2hex(random_bytes(4));

    try {
        $added = InstallerClaudeSettings::ensureBundledScriptPermissions($home);

        expect($added)->toBe(2);

        $settingsPath = $home . '/.claude/settings.json';
        expect(is_file($settingsPath))->toBeTrue();
        expect(InstallerClaudeSettings::loadAllowList($home))
            ->toBe(InstallerClaudeSettings::getBundledScriptPermissions());
    } finally {
        installerRemoveDirectory($home);
    }
});

test('ensureBundledScriptPermissions merges into existing settings.json without dropping unrelated keys', function (): void {
    $home = sys_get_temp_dir() . '/claude-settings-' . bin2hex(random_bytes(4));
    $settingsPath = $home . '/.claude/settings.json';
    installerWriteFile($settingsPath, (string) json_encode([
        'theme' => 'dark',
        'permissions' => [
            'allow' => ['Bash(git status:*)'],
            'deny' => ['Bash(rm -rf:*)'],
        ],
    ], JSON_PRETTY_PRINT));

    try {
        $added = InstallerClaudeSettings::ensureBundledScriptPermissions($home);

        expect($added)->toBe(2);
        expect(InstallerClaudeSettings::loadAllowList($home))->toBe([
            'Bash(git status:*)',
            'Bash(*skills/code-review-github/scripts/load-issue.sh:*)',
            'Bash(*skills/code-review-jira/scripts/load-issue.sh:*)',
        ]);

        $raw = (string) file_get_contents($settingsPath);
        expect($raw)->toContain('"theme": "dark"');
        expect($raw)->toContain('"Bash(rm -rf:*)"');
    } finally {
        installerRemoveDirectory($home);
    }
});

test('ensureBundledScriptPermissions is idempotent on a settings.json that already has both entries', function (): void {
    $home = sys_get_temp_dir() . '/claude-settings-' . bin2hex(random_bytes(4));

    try {
        $firstAdded = InstallerClaudeSettings::ensureBundledScriptPermissions($home);
        $secondAdded = InstallerClaudeSettings::ensureBundledScriptPermissions($home);

        expect($firstAdded)->toBe(2);
        expect($secondAdded)->toBe(0);
    } finally {
        installerRemoveDirectory($home);
    }
});

test('ensureBundledScriptPermissions recovers when permissions key is the wrong shape', function (): void {
    $home = sys_get_temp_dir() . '/claude-settings-' . bin2hex(random_bytes(4));
    $settingsPath = $home . '/.claude/settings.json';
    installerWriteFile($settingsPath, (string) json_encode([
        'permissions' => 'not-an-array',
    ]));

    try {
        $added = InstallerClaudeSettings::ensureBundledScriptPermissions($home);

        expect($added)->toBe(2);
        expect(InstallerClaudeSettings::loadAllowList($home))
            ->toBe(InstallerClaudeSettings::getBundledScriptPermissions());
    } finally {
        installerRemoveDirectory($home);
    }
});

test('ensureBundledScriptPermissions recovers when permissions.allow is the wrong shape', function (): void {
    $home = sys_get_temp_dir() . '/claude-settings-' . bin2hex(random_bytes(4));
    $settingsPath = $home . '/.claude/settings.json';
    installerWriteFile($settingsPath, (string) json_encode([
        'permissions' => ['allow' => 'string-not-array'],
    ]));

    try {
        $added = InstallerClaudeSettings::ensureBundledScriptPermissions($home);

        expect($added)->toBe(2);
        expect(InstallerClaudeSettings::loadAllowList($home))
            ->toBe(InstallerClaudeSettings::getBundledScriptPermissions());
    } finally {
        installerRemoveDirectory($home);
    }
});

test('ensureBundledScriptPermissions drops non-string entries from allow before merging', function (): void {
    $home = sys_get_temp_dir() . '/claude-settings-' . bin2hex(random_bytes(4));
    $settingsPath = $home . '/.claude/settings.json';
    installerWriteFile($settingsPath, (string) json_encode([
        'permissions' => ['allow' => ['Bash(git status:*)', 42, null]],
    ]));

    try {
        InstallerClaudeSettings::ensureBundledScriptPermissions($home);

        expect(InstallerClaudeSettings::loadAllowList($home))->toBe([
            'Bash(git status:*)',
            'Bash(*skills/code-review-github/scripts/load-issue.sh:*)',
            'Bash(*skills/code-review-jira/scripts/load-issue.sh:*)',
        ]);
    } finally {
        installerRemoveDirectory($home);
    }
});

test('ensureBundledScriptPermissions skips when settings.json content is empty whitespace', function (): void {
    $home = sys_get_temp_dir() . '/claude-settings-' . bin2hex(random_bytes(4));
    $settingsPath = $home . '/.claude/settings.json';
    installerWriteFile($settingsPath, "   \n  \n");

    try {
        $added = InstallerClaudeSettings::ensureBundledScriptPermissions($home);

        expect($added)->toBe(2);
        expect(InstallerClaudeSettings::loadAllowList($home))
            ->toBe(InstallerClaudeSettings::getBundledScriptPermissions());
    } finally {
        installerRemoveDirectory($home);
    }
});

test('ensureBundledScriptPermissions raises InstallerFailure when settings.json is malformed JSON', function (): void {
    $home = sys_get_temp_dir() . '/claude-settings-' . bin2hex(random_bytes(4));
    $settingsPath = $home . '/.claude/settings.json';
    installerWriteFile($settingsPath, '{not-valid-json');

    try {
        expect(static fn (): int => InstallerClaudeSettings::ensureBundledScriptPermissions($home))
            ->toThrow(InstallerFailure::class);
    } finally {
        installerRemoveDirectory($home);
    }
});

test('ensureBundledScriptPermissions raises InstallerFailure when top-level value is not an object', function (): void {
    $home = sys_get_temp_dir() . '/claude-settings-' . bin2hex(random_bytes(4));
    $settingsPath = $home . '/.claude/settings.json';
    installerWriteFile($settingsPath, '"a string"');

    try {
        expect(static fn (): int => InstallerClaudeSettings::ensureBundledScriptPermissions($home))
            ->toThrow(InstallerFailure::class);
    } finally {
        installerRemoveDirectory($home);
    }
});

test('ensureBundledScriptPermissions raises InstallerFailure when settings.json target is a directory (write fails)', function (): void {
    $home = sys_get_temp_dir() . '/claude-settings-' . bin2hex(random_bytes(4));
    $settingsPath = $home . '/.claude/settings.json';
    installerEnsureDirectory($settingsPath);

    try {
        expect(static fn (): int => InstallerClaudeSettings::ensureBundledScriptPermissions($home))
            ->toThrow(InstallerFailure::class);
    } finally {
        installerRemoveDirectory($home);
    }
});

test('ensureBundledScriptPermissions raises InstallerFailure when ~/.claude path is a file (mkdir fails)', function (): void {
    $home = sys_get_temp_dir() . '/claude-settings-' . bin2hex(random_bytes(4));
    installerEnsureDirectory($home);
    file_put_contents($home . '/.claude', 'blocker file');

    try {
        expect(static fn (): int => InstallerClaudeSettings::ensureBundledScriptPermissions($home))
            ->toThrow(InstallerFailure::class);
    } finally {
        installerRemoveDirectory($home);
    }
});

test('install --editor=claude --allow-bundled-scripts writes the permissions and reports them', function (): void {
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
        Installer::run(['cursor-rules', 'install', '--editor=claude', '--allow-bundled-scripts']);
        $output = (string) ob_get_clean();

        expect($output)->toContain('Allowed 2 bundled-script permission(s) in ~/.claude/settings.json.');

        $settingsPath = $root . '/.claude/settings.json';
        expect(is_file($settingsPath))->toBeTrue();
        expect(InstallerClaudeSettings::loadAllowList($root))
            ->toBe(InstallerClaudeSettings::getBundledScriptPermissions());
    } finally {
        installerRestoreEnvAndCleanup($homeBefore, $originalCwd, $root);
    }
});

test('install --editor=all --allow-bundled-scripts writes the permissions to ~/.claude/settings.json', function (): void {
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
        Installer::run(['cursor-rules', 'install', '--editor=all', '--allow-bundled-scripts']);
        ob_end_clean();

        $settingsPath = $root . '/.claude/settings.json';
        expect(is_file($settingsPath))->toBeTrue();
    } finally {
        installerRestoreEnvAndCleanup($homeBefore, $originalCwd, $root);
    }
});

test('install --editor=cursor --allow-bundled-scripts does not write settings.json', function (): void {
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
        Installer::run(['cursor-rules', 'install', '--editor=cursor', '--allow-bundled-scripts']);
        $output = (string) ob_get_clean();

        expect($output)->not->toContain('Allowed');
        expect(is_file($root . '/.claude/settings.json'))->toBeFalse();
    } finally {
        installerRestoreEnvAndCleanup($homeBefore, $originalCwd, $root);
    }
});

test('install --editor=claude without --allow-bundled-scripts leaves settings.json untouched', function (): void {
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
        Installer::run(['cursor-rules', 'install', '--editor=claude']);
        $output = (string) ob_get_clean();

        expect($output)->not->toContain('Allowed');
        expect(is_file($root . '/.claude/settings.json'))->toBeFalse();
    } finally {
        installerRestoreEnvAndCleanup($homeBefore, $originalCwd, $root);
    }
});

test('install --editor=claude --allow-bundled-scripts with HOME unset is a no-op for settings.json', function (): void {
    $root = installerCreateProjectRoot();
    $homeBefore = getenv('HOME');
    $userProfileBefore = getenv('USERPROFILE');
    putenv('HOME');
    putenv('USERPROFILE');

    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=claude', '--allow-bundled-scripts']);
        $output = (string) ob_get_clean();

        expect($output)->not->toContain('Allowed');
    } finally {
        if ($homeBefore !== false && $homeBefore !== '') {
            putenv('HOME=' . $homeBefore);
        }

        if ($userProfileBefore !== false && $userProfileBefore !== '') {
            putenv('USERPROFILE=' . $userProfileBefore);
        }

        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install --editor=claude --allow-bundled-scripts is idempotent across two consecutive runs', function (): void {
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
        Installer::run(['cursor-rules', 'install', '--editor=claude', '--allow-bundled-scripts']);
        ob_end_clean();

        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=claude', '--allow-bundled-scripts']);
        $secondOutput = (string) ob_get_clean();

        expect($secondOutput)->not->toContain('Allowed');
        expect(InstallerClaudeSettings::loadAllowList($root))
            ->toBe(InstallerClaudeSettings::getBundledScriptPermissions());
    } finally {
        installerRestoreEnvAndCleanup($homeBefore, $originalCwd, $root);
    }
});

test('dependency-selection rule gates every new Composer package on activity and compatibility', function (): void {
    $packageDir = dirname(__DIR__);
    $rulePath = $packageDir . '/rules/php/dependency-selection.mdc';

    expect(is_file($rulePath))->toBeTrue();

    $rule = (string) file_get_contents($rulePath);

    expect($rule)->toContain('## Activity gate (mandatory');
    expect($rule)->toContain('Recent activity (≤ 12 months).');
    expect($rule)->toContain('Not archived.');
    expect($rule)->toContain('Not abandoned on Packagist.');
    expect($rule)->toContain('Tagged release exists.');
    expect($rule)->toContain('Issue tracker is responsive.');

    expect($rule)->toContain('## Compatibility gate (mandatory');
    expect($rule)->toContain('Match the project\'s PHP constraint.');
    expect($rule)->toContain('OSI-approved license');
    expect($rule)->toContain('no CI configured at all');
    expect($rule)->toContain('quality risk under the *Test surface* scoring signal');

    expect($rule)->toContain('## Selection process (mandatory');
    expect($rule)->toContain('Enumerate 2–3 realistic candidates.');
    expect($rule)->toContain('Alternatives considered:');
    expect($rule)->toContain('### Proposed dependency:');
    expect($rule)->toContain('### Proposed dependency: spatie/laravel-data');
    expect($rule)->toContain('Concrete rendered example');

    expect($rule)->toContain('do **not** silently relax the rule');
    expect($rule)->toContain('Stop, report a blocker to the user');

    expect($rule)->toContain('## Code Review Application');
    expect($rule)->toContain('**Critical** finding');

    $callers = [
        $packageDir . '/skills/resolve-issue/SKILL.md',
        $packageDir . '/skills/class-refactoring/SKILL.md',
        $packageDir . '/skills/composer-update/SKILL.md',
        $packageDir . '/skills/security-threat-analysis/SKILL.md',
        $packageDir . '/skills/code-review/SKILL.md',
        $packageDir . '/skills/security-review/SKILL.md',
    ];

    foreach ($callers as $caller) {
        $body = (string) file_get_contents($caller);
        expect($body)->toContain('@rules/php/dependency-selection.mdc');
    }
});

test('code-testing rules add Test Organization clause for namespace mirroring and description match (issue #528)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/code-testing/general.mdc');

    expect($content)->toContain('## Test Organization');
    expect($content)->toContain('mirrors the namespace of the production class');
    expect($content)->toContain('{ClassName}Test.php');
    expect($content)->toContain('{ClassName}{Scenario}Test.php');
    expect($content)->toContain('tests/Feature/<flow>');
    expect($content)->toContain('tests/Contract/<vendor>');
    expect($content)->toContain('tests/Integration/<area>');
    expect($content)->toContain('matches what the body actually asserts');
    expect($content)->toContain('test(\'test1\')');
    expect($content)->toContain('it(\'it works\')');
    expect($content)->toContain('test(\'happy path\')');

    expect($content)->toContain('tests/InstallerPathTest.php');
    expect($content)->not->toContain('`tests/InstallerPath.php`');
});

test('code-testing rules register the Test Organization Review Hook pointing at the code-review skill (issue #528)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/code-testing/general.mdc');

    expect($content)->toContain('## Test Organization Review Hook');
    expect($content)->toContain('@skills/code-review/SKILL.md');
});

test('code-review rule references Test Organization gate (issue #528)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/code-review/general.mdc');

    expect($content)->toContain('## Test Organization');
    expect($content)->toContain('mirrors the namespace of the production class');
    expect($content)->toContain('{ClassName}Test.php');
    expect($content)->toContain('matches what the body asserts');
    expect($content)->toContain('@rules/code-testing/general.mdc');
    expect($content)->toContain('@skills/code-review/SKILL.md');
});

test('code-review skill enforces Test Organization gate on every diff (issue #528)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md');

    expect($content)->toContain('**Test organization (issue #528)**');
    expect($content)->toContain('Placement mirrors the SUT namespace');
    expect($content)->toContain('File name matches the SUT');
    expect($content)->toContain('`it()` / `test()` description matches the asserted scenario');
    expect($content)->toContain('Severity: **Moderate** by default');
    expect($content)->toContain('Escalate to **Critical**');
    expect($content)->toContain('@rules/code-testing/general.mdc');

    // Suggested Fix templates must be concrete so process-code-review can extract them.
    expect($content)->toContain('**Placement / file name fix**');
    expect($content)->toContain('**Description fix**');
    expect($content)->toContain('@skills/process-code-review/SKILL.md');
    expect($content)->toContain('degrade to checking that the file sits under an intent-named directory');
});

test('create-test skill instructs creators to follow Test Organization conventions (issue #528)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/create-test/SKILL.md');

    expect($content)->toContain('Place new test files per `@rules/code-testing/general.mdc` *Test Organization*');
    expect($content)->toContain('{ClassName}Test.php');
    expect($content)->toContain('Name every `it()` / `test()` block to match the scenario the body asserts');
    expect($content)->toContain('test(\'test1\')');
});

test('create-missing-tests-in-pr skill instructs creators to follow Test Organization conventions (issue #528)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/create-missing-tests-in-pr/SKILL.md');

    expect($content)->toContain('Place new test files per `@rules/code-testing/general.mdc` *Test Organization*');
    expect($content)->toContain('{ClassName}Test.php');
    expect($content)->toContain('Name every `it()` / `test()` block to match the scenario the body asserts');
});

test('code-testing rule short-circuits coverage reporting when changed files are at 100% (issue #528 follow-up)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/code-testing/general.mdc');

    expect($content)->toContain('Coverage reporting is short by default');
    expect($content)->toContain('uncovered changed lines');
    expect($content)->toContain('coverage tooling unavailable');
    expect($content)->toContain(
        'omit the `## Coverage` section entirely, omit the `Coverage:` header line, and omit the `coverage …` slot from the final summary line',
    );
    expect($content)->toContain('The coverage check itself still runs unconditionally');
    expect($content)->not->toContain('Always report the coverage result (tool used, command, % covered for changed lines).');
});

test('core-standards Testing bullet short-circuits coverage reporting when 100% (issue #528 follow-up)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/php/core-standards.mdc');

    expect($content)->toContain('Report the coverage result short by default');
    expect($content)->toContain('omit the `## Coverage` section, the `Coverage:` header line, and the `coverage …` slot from the summary line');
    expect($content)->toContain('The check itself still runs unconditionally');
    expect($content)->not->toContain('Always report the coverage result; never push or finalize a change without it.');
});

test('code-review skill short-circuits coverage section in Output Rules + Coverage gate (issue #528 follow-up)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md');

    // Coverage gate text mandates short-by-default reporting.
    expect($content)->toContain('**Coverage reporting is short by default.**');
    expect($content)->toContain(
        'omit the `## Coverage` section entirely, omit the `Coverage:` header line, and omit the `coverage …` slot from the final summary line',
    );

    // Output Rules opening clause no longer claims `## Coverage` is always rendered.
    expect($content)->toContain(
        'Only the header block (Status / Counts / Last updated / tracker-status line) and the final `Summary` line are always rendered.',
    );
    expect($content)->toContain('all conditional');
    // The old "always render Coverage" sentence must be gone — verify by checking a distinctive fragment that only existed in the legacy sentence.
    expect($content)->not->toContain('Counts / Coverage / Last updated / tracker-status line');
});

test('code-review-github skill + template short-circuit coverage section (issue #528 follow-up)', function (): void {
    $packageDir = dirname(__DIR__);
    $skill = (string) file_get_contents($packageDir . '/skills/code-review-github/SKILL.md');
    $template = (string) file_get_contents($packageDir . '/skills/code-review-github/templates/pr-comment-output.md');

    expect($skill)->toContain('Only the header block (Status / Counts / Last updated / Issue tracker summary)');
    expect($skill)->toContain('the final `Summary` line are always rendered in the PR comment.');
    expect($skill)->toContain('all conditional');
    expect($skill)->toContain('includes a `## Coverage` section before the summary line **only** when the coverage gate has something to report');
    expect($skill)->not->toContain('Counts / Coverage / Issue tracker summary');

    expect($template)->toContain('are conditional');
    expect($template)->toContain('Render this section **only** when the coverage gate produced something to report');
    expect($template)->toContain('omitted on a clean 100% pass');
});

test('code-review-jira skill + template short-circuit coverage section (issue #528 follow-up)', function (): void {
    $packageDir = dirname(__DIR__);
    $skill = (string) file_get_contents($packageDir . '/skills/code-review-jira/SKILL.md');
    $template = (string) file_get_contents($packageDir . '/skills/code-review-jira/templates/github-output.md');

    expect($skill)->toContain('Only the header block (Status / Counts / Last updated / Linked-tracker mirror)');
    expect($skill)->toContain('the final `Summary` line are always rendered in the GitHub PR comment.');
    expect($skill)->toContain('all conditional');
    expect($skill)->toContain('includes a `## Coverage` section before the summary line **only** when the coverage gate has something to report');

    expect($template)->toContain('are conditional');
    expect($template)->toContain('Render this section **only** when the coverage gate produced something to report');
    expect($template)->toContain('omitted on a clean 100% pass');
});

test('CR base review-output template short-circuits coverage section (issue #528 follow-up)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/templates/review-output.md');

    expect($content)->toContain('are conditional');
    expect($content)->toContain('Render this section **only** when the coverage gate produced something to report');
    expect($content)->toContain('omitted on a clean 100% pass');
});

test('code-review skill mandates a standalone Laravel architecture walk on every CR run (issue #530)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md');

    expect($content)->toContain('**Architecture conformance (Laravel)** — mandatory standalone walk-through (issue #530)');
    expect($content)->toContain('independent of Strict rule compliance');
    expect($content)->toContain('section-by-section deep-dive for `@rules/laravel/architecture.mdc`');
    expect($content)->toContain('Walk every section of that file against the current diff **regardless of which files the diff touches**');
    expect($content)->toContain('helpers, routes, configs, migrations, seeders, tests, or even a docs-only commit');
    expect($content)->toContain('seven allowed homes including the Eloquent-model carve-out');
    expect($content)->toContain('Actions / Model Services / Repositories / ModelManagers / Data Validators / Data Builders / Eloquent models');
    expect($content)->toContain('arch-app-services examples (when installed)');
    expect($content)->toContain('https://github.com/pekral/arch-app-services/blob/master/README.md');
    expect($content)->toContain('When the package is **not** installed, ignore this README cross-check');
    expect($content)->toContain('published CR comment carries a `## Architecture` section **only when the walk produces at least one finding**');
    expect($content)->toContain('omit the `## Architecture` heading entirely — never render a "walked, 0 findings" status line');
    expect($content)->toContain(
        'On **non-Laravel projects** (no `laravel/framework` in `composer.json` `require`), skip the walk entirely and omit the `## Architecture` section',
    );
});

test('code-review Output Rules carry the Architecture section conditional rendering rule (issue #530)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md');

    expect($content)->toContain('`## Architecture` section (issue #530)');
    expect($content)->toContain('the `## Architecture` heading is rendered **only when the walk produces at least one finding**');
    expect($content)->toContain('omit the heading entirely — never render a `walked, 0 findings` status line');
    expect($content)->toContain('the `## Architecture` section is omitted entirely');
});

test('code-review canonical template renders the Laravel Architecture section conditionally (issue #530)', function (): void {
    $packageDir = dirname(__DIR__);
    $template = (string) file_get_contents($packageDir . '/skills/code-review/templates/review-output.md');

    expect($template)->toContain('## Architecture');
    expect($template)->toContain('**Laravel-only, conditional on findings (issue #530)');
    expect($template)->toContain('only when the walk produces at least one finding');
    expect($template)->toContain('omit the entire `## Architecture` heading and body');
    expect($template)->toContain('Architecture conformance (Laravel) — mandatory standalone walk-through');
    expect($template)->not->toContain('Status: walked, 0 findings');

    $architectureHeading = strpos($template, "\n## Architecture\n");
    $coverageHeading = strpos($template, "\n## Coverage\n");

    expect($architectureHeading)->not->toBeFalse();
    expect($coverageHeading)->not->toBeFalse();
    assert($architectureHeading !== false);
    assert($coverageHeading !== false);
    expect($architectureHeading)->toBeLessThan($coverageHeading);
});

test('code-review-github Output Rules and template carry the Architecture conditional rendering rule (issue #530)', function (): void {
    $packageDir = dirname(__DIR__);
    $skill = (string) file_get_contents($packageDir . '/skills/code-review-github/SKILL.md');
    $template = (string) file_get_contents($packageDir . '/skills/code-review-github/templates/pr-comment-output.md');

    expect($skill)->toContain('`## Architecture` section (issue #530)');
    expect($skill)->toContain('only when the walk produces at least one finding');
    expect($skill)->toContain('never render a `walked, 0 findings` status line');
    expect($skill)->toContain('On non-Laravel projects, omit the `## Architecture` section entirely');

    expect($template)->toContain('## Architecture');
    expect($template)->toContain('**Laravel-only, conditional on findings (issue #530)');
    expect($template)->toContain('only when the walk produces at least one finding');
    expect($template)->not->toContain('Status: walked, 0 findings');

    $architectureHeading = strpos($template, "\n## Architecture\n");
    $coverageHeading = strpos($template, "\n## Coverage\n");

    expect($architectureHeading)->not->toBeFalse();
    expect($coverageHeading)->not->toBeFalse();
    assert($architectureHeading !== false);
    assert($coverageHeading !== false);
    expect($architectureHeading)->toBeLessThan($coverageHeading);
});

test('code-review-jira Output Rules and GitHub template carry the Architecture conditional rendering rule (issue #530)', function (): void {
    $packageDir = dirname(__DIR__);
    $skill = (string) file_get_contents($packageDir . '/skills/code-review-jira/SKILL.md');
    $template = (string) file_get_contents($packageDir . '/skills/code-review-jira/templates/github-output.md');

    expect($skill)->toContain('`## Architecture` section (issue #530)');
    expect($skill)->toContain('only when the walk produces at least one finding');
    expect($skill)->toContain('never render a `walked, 0 findings` status line');
    expect($skill)->toContain('The JIRA non-technical comment (produced by `pr-summary`) never includes this section');

    expect($template)->toContain('## Architecture');
    expect($template)->toContain('**Laravel-only, conditional on findings (issue #530)');
    expect($template)->toContain('only when the walk produces at least one finding');
    expect($template)->not->toContain('Status: walked, 0 findings');

    $architectureHeading = strpos($template, "\n## Architecture\n");
    $coverageHeading = strpos($template, "\n## Coverage\n");

    expect($architectureHeading)->not->toBeFalse();
    expect($coverageHeading)->not->toBeFalse();
    assert($architectureHeading !== false);
    assert($coverageHeading !== false);
    expect($architectureHeading)->toBeLessThan($coverageHeading);
});

test('architecture rules carry the Shared Concerns (Traits) section scoped to globally reusable, domain-agnostic logic (issue #531)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/architecture.mdc');

    expect($content)->toContain('## Shared Concerns (Traits)');
    expect($content)->toContain('`app/Concerns/` is the **canonical home for all globally shared and reusable logic**');
    expect($content)->toContain('**Globally applicable**');
    expect($content)->toContain('**Domain-agnostic**');
    expect($content)->toContain('**Reusable as-is**');
    expect($content)->toContain('**Forbidden in `app/Concerns/`:**');
    expect($content)->toContain('Domain-specific logic');
    expect($content)->toContain('Single-use traits or helpers consumed by exactly one class');
    expect($content)->toContain('Orchestration, persistence, query, or HTTP/queue dispatching logic');
    expect($content)->toContain('The **Validation Rules (Traits)** section below is one specific instance of this broader rule');
    expect($content)->toContain('This is the canonical worked example of the **Shared Concerns (Traits)** rule above.');
});

test('architecture Shared Concerns (Traits) section sits immediately before Validation Rules (Traits) (issue #531)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/architecture.mdc');

    $sharedConcernsHeading = strpos($content, "\n## Shared Concerns (Traits)\n");
    $validationRulesHeading = strpos($content, "\n## Validation Rules (Traits)\n");
    $dataValidatorsHeading = strpos($content, "\n## Data Validators\n");

    expect($sharedConcernsHeading)->not->toBeFalse();
    expect($validationRulesHeading)->not->toBeFalse();
    expect($dataValidatorsHeading)->not->toBeFalse();
    assert($sharedConcernsHeading !== false);
    assert($validationRulesHeading !== false);
    assert($dataValidatorsHeading !== false);

    expect($sharedConcernsHeading)->toBeLessThan($validationRulesHeading);
    expect($validationRulesHeading)->toBeLessThan($dataValidatorsHeading);
});

test('architecture CR Severity Rules cover app/Concerns misuse in both directions (issue #531)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/architecture.mdc');

    expect($content)->toContain('domain-specific code placed under `app/Concerns/`');
    expect($content)->toContain('shared, reusable trait or helper logic placed outside `app/Concerns/`');
    expect($content)->toContain('single-use trait parked in `app/Concerns/`');
    expect($content)->toContain('per **Shared Concerns (Traits)**');
});

test('laravel rules carry the parallel Shared Concerns section and Layer Responsibilities bullet (issue #531)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/laravel.mdc');

    expect($content)->toContain('## Shared Concerns');
    expect($content)->toContain('Shared Concerns (`app/Concerns/`): globally shared and reusable logic');
    expect($content)->toContain('canonical home for all globally shared and reusable logic in the application');
    expect($content)->toContain('**globally applicable**');
    expect($content)->toContain('**domain-agnostic**');
    expect($content)->toContain('**reusable as-is**');
    expect($content)->toContain('Never put domain-specific logic in `app/Concerns/`');
    expect($content)->toContain('Validation rule traits (see the **Validation** section below) are one specific worked example');
});

test('code-review skill adds Shared Concerns (Traits) to the mandatory architecture walk (issue #531)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md');

    expect($content)->toContain('**Shared Concerns (Traits)** (globally shared, domain-agnostic, reusable-as-is logic only');
    expect($content)->toContain('flag domain-specific code parked under `app/Concerns/`');
    expect($content)->toContain('reusable trait logic scattered outside `app/Concerns/`');
});

test('code-review skill verifies every Critical finding via analyze-problem before publishing (issue #537)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md');

    expect($content)->toContain('### Critical Findings Verification (issue #537)');
    expect($content)->toContain('Walk every **Critical** finding aggregated within this skill\'s run through `@skills/analyze-problem/SKILL.md`');
    expect($content)->toContain('invoke `@skills/analyze-problem/SKILL.md` **inline in this skill\'s context** (do not dispatch as a subagent)');
    expect($content)->toContain(
        '**Confirmed** — Verified Facts and Probable Root Cause back the finding → keep the Critical finding verbatim in the report',
    );
    expect($content)->toContain('**Refuted** — Verified Facts contradict the finding');
    expect($content)->toContain('**Never silently downgrade** a Critical to Moderate or Minor on the basis of this verification');
    expect($content)->toContain('**Moderate and Minor findings are not subject to this verification**');
});

test('security/backend.md carries the Safe Validation & Error Messages section (issue #540)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/security/backend.md');

    expect($content)->toContain('## Safe Validation & Error Messages (issue #540)');
    expect($content)->toContain('**No identity / account enumeration.**');
    expect($content)->toContain('Invalid credentials.');
    expect($content)->toContain('If the account exists, we sent the reset link.');
    expect($content)->toContain('**No authorization granularity leaks.**');
    expect($content)->toContain('**No internal implementation detail.**');
    expect($content)->toContain('**No verbatim echo of attacker input.**');
    expect($content)->toContain('**No password / token policy leak beyond the stated rule.**');
    expect($content)->toContain('**No timing or shape side channels.**');
    expect($content)->toContain('**Translations carry the same contract.**');
    expect($content)->toContain('**Specificity stays on the safe surfaces.**');
});

test('security/frontend.md carries the Safe Validation & Error Messages section (issue #540)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/security/frontend.md');

    expect($content)->toContain('## Safe Validation & Error Messages (issue #540)');
    expect($content)->toContain('@rules/security/backend.md');
    expect($content)->toContain('**Mirror the backend wording.**');
    expect($content)->toContain('**Do not pre-flight existence on the client.**');
    expect($content)->toContain('**Never inject attacker input into the message DOM unescaped.**');
    expect($content)->toContain('**Strip stack traces and SDK errors before display.**');
    expect($content)->toContain('**Translation parity.**');
});

test('security/mobile.md carries the Safe Validation & Error Messages section (issue #540)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/security/mobile.md');

    expect($content)->toContain('## Safe Validation & Error Messages (issue #540)');
    expect($content)->toContain('@rules/security/backend.md');
    expect($content)->toContain('**No native crash dialogs surfaced to the user.**');
    expect($content)->toContain('**WebView error pages must stay generic.**');
    expect($content)->toContain('**Logs / debug overlays are not user-facing channels.**');
    expect($content)->toContain('**Translation parity.**');
});

test('code-review skill enforces Safe validation & error texts on every diff (issue #540)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md');

    expect($content)->toContain('**Safe validation & error texts (issue #540):**');
    expect($content)->toContain('@rules/security/backend.md');
    expect($content)->toContain('Identity / account enumeration on auth, password-reset, sign-up, change-email, or account-lookup flows');
    expect($content)->toContain('Authorization granularity leak');
    expect($content)->toContain('Internal implementation detail in the response body');
    expect($content)->toContain('Verbatim echo of attacker input');
    expect($content)->toContain('Password / token policy leak beyond the stated rule');
    expect($content)->toContain('Translation drift');
    expect($content)->toContain('Severity: **Critical** when the unsafe wording sits on an auth / password-reset / sign-up / authorization surface');
});

test('security-review skill audits safe validation & error texts across locales (issue #540)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/security-review/SKILL.md');

    expect($content)->toContain('**safe validation & error texts (issue #540)**');
    expect($content)->toContain('@rules/security/backend.md');
    expect($content)->toContain('across every locale shipped by the project');
    expect($content)->toContain('directly exploitable for enumeration');
});

test('resolve-issue skill references Safe Validation & Error Messages rule (issue #540)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/resolve-issue/SKILL.md');

    expect($content)->toContain('@rules/security/backend.md');
    expect($content)->toContain('Safe Validation & Error Messages');
    expect($content)->toContain('including every locale shipped by the project');
});

test('analyze-problem skill carries the UI Redesign Lens with one-click default and wizard fallback', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/analyze-problem/SKILL.md');

    expect($content)->toContain('## UI Redesign Lens');
    expect($content)->toContain('only when the analyzed problem is a UI / UX redesign or a new user-facing flow');
    expect($content)->toContain('**Simple**');
    expect($content)->toContain('**Intuitive**');
    expect($content)->toContain('**Readable for humans**');
    expect($content)->toContain('**Modern**');
    expect($content)->toContain('**One-click default**');
    expect($content)->toContain('**Wizard fallback when multi-step is unavoidable**');
    expect($content)->toContain('A confirmation step is allowed only when the action is destructive');
    expect($content)->toContain('irreversible, financially material, legally significant, or affects a third party');
    expect($content)->toContain('every step states its purpose and its position in the flow');
    expect($content)->toContain('the user can move back without losing entered data');
    expect($content)->toContain('the user can save and resume later when the flow exceeds three steps');
    expect($content)->toContain('the final step shows a summary of every choice before commit');
    expect($content)->toContain('*One-click vs wizard decision*');
});

test('agents/ directory ships in the package with all seven Claude Code subagents', function (): void {
    $packageDir = dirname(__DIR__);
    $agentsDir = $packageDir . '/agents';

    expect(is_dir($agentsDir))->toBeTrue();

    foreach (
        [
            'php-code-reviewer.md',
            'issue-resolver.md',
            'test-engineer.md',
            'security-reviewer.md',
            'laravel-architect.md',
            'mysql-performance-reviewer.md',
            'refactoring-specialist.md',
        ] as $agentFile
    ) {
        $path = $agentsDir . '/' . $agentFile;
        expect(is_file($path))->toBeTrue();

        $content = (string) file_get_contents($path);
        expect($content)->toStartWith('---');
        expect($content)->toContain('name:');
        expect($content)->toContain('description:');
        expect($content)->toContain('tools:');
        expect($content)->toContain('model:');
    }
});

test('resolveAgentsSource returns the package agents directory when it exists', function (): void {
    $packageDir = dirname(__DIR__);

    expect(InstallerPath::resolveAgentsSource())->toBe($packageDir . '/agents');
});

test('isAgentsEditor matches only claude and all', function (): void {
    expect(InstallerPath::isAgentsEditor(InstallerPath::EDITOR_CLAUDE))->toBeTrue();
    expect(InstallerPath::isAgentsEditor(InstallerPath::EDITOR_ALL))->toBeTrue();
    expect(InstallerPath::isAgentsEditor(InstallerPath::EDITOR_CURSOR))->toBeFalse();
    expect(InstallerPath::isAgentsEditor(InstallerPath::EDITOR_CODEX))->toBeFalse();
});

test('resolveAgentsTargetDirectories returns .claude/agents for editor=claude', function (): void {
    $targets = InstallerPath::resolveAgentsTargetDirectories('/project', InstallerPath::EDITOR_CLAUDE);

    expect($targets)->toBe(['/project/.claude/agents']);
});

test('resolveAgentsTargetDirectories returns .claude/agents for editor=all', function (): void {
    $targets = InstallerPath::resolveAgentsTargetDirectories('/project', InstallerPath::EDITOR_ALL);

    expect($targets)->toBe(['/project/.claude/agents']);
});

test('resolveAgentsTargetDirectories returns empty list for editor=cursor', function (): void {
    expect(InstallerPath::resolveAgentsTargetDirectories('/project', InstallerPath::EDITOR_CURSOR))->toBe([]);
});

test('resolveAgentsTargetDirectories returns empty list for editor=codex', function (): void {
    expect(InstallerPath::resolveAgentsTargetDirectories('/project', InstallerPath::EDITOR_CODEX))->toBe([]);
});

test('install with editor=claude copies agents to .claude/agents', function (): void {
    $root = installerCreateProjectRoot();
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=claude']);
        ob_end_clean();

        foreach (
            [
                'php-code-reviewer.md',
                'issue-resolver.md',
                'test-engineer.md',
                'security-reviewer.md',
                'laravel-architect.md',
                'mysql-performance-reviewer.md',
                'refactoring-specialist.md',
            ] as $agentFile
        ) {
            expect(is_file($root . '/.claude/agents/' . $agentFile))->toBeTrue('Missing installed agent: ' . $agentFile);
        }

        expect(is_dir($root . '/.cursor/agents'))->toBeFalse();
        expect(is_dir($root . '/.codex/agents'))->toBeFalse();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install with editor=all copies agents to .claude/agents only', function (): void {
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

        $packageDir = dirname(__DIR__);
        $expectedAgentsCount = installerCountFiles($packageDir . '/agents');

        expect(installerCountFiles($root . '/.claude/agents'))->toBe($expectedAgentsCount);
        expect(is_dir($root . '/.cursor/agents'))->toBeFalse();
        expect(is_dir($root . '/.codex/agents'))->toBeFalse();
    } finally {
        installerRestoreEnvAndCleanup($homeBefore, $originalCwd, $root);
    }
});

test('install with editor=cursor does not copy agents', function (): void {
    $root = installerCreateProjectRoot();
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();

        expect(is_dir($root . '/.claude/agents'))->toBeFalse();
        expect(is_dir($root . '/.cursor/agents'))->toBeFalse();
        expect(is_dir($root . '/.codex/agents'))->toBeFalse();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install with editor=codex does not copy agents', function (): void {
    $root = installerCreateProjectRoot();
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';

    try {
        chdir($root);
        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=codex']);
        ob_end_clean();

        expect(is_dir($root . '/.claude/agents'))->toBeFalse();
        expect(is_dir($root . '/.cursor/agents'))->toBeFalse();
        expect(is_dir($root . '/.codex/agents'))->toBeFalse();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('install with --prune removes orphan agents from .claude/agents', function (): void {
    $root = installerCreateProjectRoot();
    $cwd = getcwd();
    $originalCwd = $cwd !== false ? $cwd : '';
    $orphanAgent = $root . '/.claude/agents/legacy-agent.md';

    try {
        chdir($root);
        installerWriteFile($orphanAgent, '---' . PHP_EOL . 'name: legacy-agent' . PHP_EOL . '---' . PHP_EOL);

        ob_start();
        Installer::run(['cursor-rules', 'install', '--editor=claude', '--prune']);
        ob_end_clean();

        expect(is_file($orphanAgent))->toBeFalse();
        expect(is_file($root . '/.claude/agents/php-code-reviewer.md'))->toBeTrue();
    } finally {
        if ($originalCwd !== '') {
            chdir($originalCwd);
        }

        installerRemoveDirectory($root);
    }
});

test('README documents the Claude Code subagents section with usage examples', function (): void {
    $packageDir = dirname(__DIR__);
    $readme = (string) file_get_contents($packageDir . '/README.md');

    expect($readme)->toContain('## Claude Code Subagents');
    expect($readme)->toContain('.claude/agents/');
    expect($readme)->toContain('@agent-php-code-reviewer');
    expect($readme)->toContain('@agent-issue-resolver');
    expect($readme)->toContain('@agent-test-engineer');
    expect($readme)->toContain('@agent-security-reviewer');
    expect($readme)->toContain('@agent-laravel-architect');
    expect($readme)->toContain('@agent-mysql-performance-reviewer');
    expect($readme)->toContain('@agent-refactoring-specialist');
});

test('issue-resolver agent does not claim to bypass resolve-issue specificity gate via upfront analyze-problem', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/agents/issue-resolver.md');

    expect($content)->not->toContain('trivially decides "specific"');
    expect($content)->not->toContain('skips a redundant second analysis');
    expect($content)->toContain('resolve-issue');
    expect($content)->toContain('analyze-problem');
});

test('api rule codifies the API-as-contract design standard (issue #552)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/rules/api/general.mdc');

    expect($content)->toContain('## API as a Contract');
    expect($content)->toContain('## Resource-Oriented REST');
    expect($content)->toContain('`/getUser`');
    expect($content)->toContain('## Correct HTTP Methods & Idempotence');
    expect($content)->toContain('## Idempotency Keys for Critical Operations');
    expect($content)->toContain('`Idempotency-Key`');
    expect($content)->toContain('## Precise HTTP Status Codes');
    expect($content)->toContain('## Validation at the Trust Boundary');
    expect($content)->toContain('## CR Severity Rules');
});

test('api-review skill is the read-only contract lens for the API rule (issue #552)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/api-review/SKILL.md');

    expect($content)->toContain('name: api-review');
    expect($content)->toContain('@rules/api/general.mdc');
    expect($content)->toContain('**Read-only skill**');
    expect($content)->toContain('templates/review-output.md');
});

test('code-review wires the API rule and api-review skill into every CR run (issue #552)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md');

    expect($content)->toContain('- Apply @rules/api/general.mdc');
    expect($content)->toContain('@skills/api-review/SKILL.md');
    expect($content)->toContain('`@rules/php/core-standards.mdc`, `@rules/api/general.mdc`, `@rules/code-review/general.mdc`');
});

test('cleanup-local-branches skill prunes gone and stale local branches safely (issue #550)', function (): void {
    $packageDir = dirname(__DIR__);
    $content = (string) file_get_contents($packageDir . '/skills/cleanup-local-branches/SKILL.md');

    expect($content)->toContain('name: cleanup-local-branches');
    expect($content)->toContain('@rules/git/general.mdc');
    expect($content)->toContain('git fetch --prune origin');
    expect($content)->toContain('%(upstream:track)');
    expect($content)->toContain('[gone]');
    expect($content)->toContain('six months');
    expect($content)->toContain('Never delete the currently checked-out branch');
    // Merge detection must be content-based (git cherry) so squash/rebase-merged gone branches are recognized as integrated.
    expect($content)->toContain('git cherry');
    expect($content)->toContain('squash');
    expect($content)->toContain('rebase');
});
