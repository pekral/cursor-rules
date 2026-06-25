<?php

declare(strict_types = 1);

test('race-condition-review skill is referenced only by code review skills', function (): void {
    $packageDir = dirname(__DIR__, 2);
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
        $packageDir . '/skills/code-review-bugsnag/SKILL.md',
    ];

    foreach ($expectedFiles as $expectedFile) {
        $content = file_get_contents($expectedFile);
        expect($content)->toContain($needle);
    }

    foreach ($skillFiles as $skillFile) {
        if (in_array($skillFile, $expectedFiles, strict: true)) {
            continue;
        }

        $content = file_get_contents($skillFile);
        expect($content)->not->toContain($needle);
    }
});

test('dry review rule is referenced by process-code-review skill', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = file_get_contents($packageDir . '/skills/process-code-review/SKILL.md');
    expect($content)->toContain('DRY violations');
});

test('unified resolve-issue skill requires code review before PR creation', function (): void {
    $packageDir = dirname(__DIR__, 2);
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

test('draft-PR-until-review-converges policy is wired through the rule and the PR-lifecycle skills', function (): void {
    $packageDir = dirname(__DIR__, 2);

    // Canonical policy lives in the git rule.
    $git = (string) file_get_contents($packageDir . '/rules/git/general.mdc');
    expect($git)->toContain('### Draft pull requests');
    expect($git)->toContain('gh pr create --draft');
    expect($git)->toContain('gh pr ready');
    // A Draft is never merged — the merge skill skips it.
    expect($git)->toContain('isDraft == true');

    // resolve-issue opens the PR as a Draft.
    $resolve = (string) file_get_contents($packageDir . '/skills/resolve-issue/SKILL.md');
    expect($resolve)->toContain('Open the pull request as a Draft');
    expect($resolve)->toContain('gh pr create --draft');

    // process-code-review promotes the PR out of Draft only after convergence.
    $process = (string) file_get_contents($packageDir . '/skills/process-code-review/SKILL.md');
    expect($process)->toContain('Promote the PR out of Draft');
    expect($process)->toContain('gh pr ready');

    // merge-github-pr refuses to merge a Draft and reads isDraft from the loader.
    $merge = (string) file_get_contents($packageDir . '/skills/merge-github-pr/SKILL.md');
    expect($merge)->toContain('Not a Draft');
    expect($merge)->toContain('isDraft == false');
});

test('merge-github-pr post-merge step includes conditional worktree cleanup with opt-in and used-tree guards (issue #699)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $merge = (string) file_get_contents($packageDir . '/skills/merge-github-pr/SKILL.md');

    // The cleanup step must be conditional on the worktree having been explicitly created.
    expect($merge)->toContain('opt-in');
    // The step must reference the git rule's Worktrees / Workspaces section.
    expect($merge)->toContain('Worktrees / Workspaces');
    // The step must prohibit forcing removal of an active or dirty tree.
    expect($merge)->toContain('--force');
    // The step must include the remove command.
    expect($merge)->toContain('git worktree remove');
    // The step must prune leftover metadata.
    expect($merge)->toContain('git worktree prune');
});

test('resolve-random skills are not shipped in source skills directory', function (): void {
    $packageDir = dirname(__DIR__, 2);
    expect(is_dir($packageDir . '/skills/resolve-random-github-issue'))->toBeFalse();
    expect(is_dir($packageDir . '/skills/resolve-random-jira-issue'))->toBeFalse();
});

test('query scopes rule is present in class refactoring skill', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/class-refactoring/SKILL.md');
    expect($content)->toContain('query scopes');
});

test('assignment-compliance-check skill exists with required sections and writes no files', function (): void {
    $packageDir = dirname(__DIR__, 2);
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
    $packageDir = dirname(__DIR__, 2);
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
    $packageDir = dirname(__DIR__, 2);
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
    $packageDir = dirname(__DIR__, 2);
    $rule = (string) file_get_contents($packageDir . '/rules/refactoring/general.mdc');
    $classRefactoring = (string) file_get_contents($packageDir . '/skills/class-refactoring/SKILL.md');
    $codeReview = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md') . "\n" . (string) file_get_contents(
        $packageDir . '/rules/code-review/general.mdc',
    );

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

test('readme reports the current skill count and lists tester-cookbook and security-threat-analysis', function (): void {
    $packageDir = dirname(__DIR__, 2);
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
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/class-refactoring/SKILL.md');

    expect($content)->toContain('**Speculative interfaces:**');
    expect($content)->toContain('@rules/php/core-standards.mdc');
});

test('class-refactoring skill enforces the seven business logic layers including Eloquent models', function (): void {
    $packageDir = dirname(__DIR__, 2);
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

test('core standards forbid speculative project-owned interfaces', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/php/core-standards.mdc');

    expect($content)->toContain('Do not introduce PHP `interface` types speculatively');
    expect($content)->toContain('at least two non-test consumers, and/or at least two non-test implementations');
    expect($content)->toContain('test doubles, mocks, and fakes do not count toward either threshold');
});

test('code-review skill flags speculative interfaces in Core Analysis', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md') . "\n" . (string) file_get_contents(
        $packageDir . '/rules/code-review/general.mdc',
    );

    expect($content)->toContain('Speculative interfaces');
    expect($content)->toContain('neither at least two non-test consumers nor at least two non-test implementations');
});

test('github load-issue script is shipped, executable, and documents the same shape as JIRA', function (): void {
    $packageDir = dirname(__DIR__, 2);
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
    $packageDir = dirname(__DIR__, 2);
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

test('new JIRA agent scripts are shipped, executable, and documented', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $scripts = [
        'gather-issue-context.sh' => 'Usage: gather-issue-context.sh <KEY|URL>',
        'parse-comments.sh' => 'Usage: parse-comments.sh <KEY|URL>',
        'transition-to-code-review.sh' => 'Usage: transition-to-code-review.sh <KEY|URL> [<STATUS>]',
        'transition-to-in-progress.sh' => 'Usage: transition-to-in-progress.sh <KEY|URL> [<STATUS>]',
    ];

    foreach ($scripts as $name => $usage) {
        $script = $packageDir . '/skills/code-review-jira/scripts/' . $name;
        expect(file_exists($script))->toBeTrue();
        expect(is_executable($script))->toBeTrue();

        $content = (string) file_get_contents($script);
        expect($content)->toStartWith('#!/usr/bin/env bash');
        expect($content)->toContain($usage);
    }
});

test('gather-issue-context persists linked issues as compact single-line JSON', function (): void {
    // Regression guard: load-issue.sh emits pretty (multi-line) JSON, while the
    // related-issue render / URL passes read the accumulator file line by line.
    // Each record must collapse to a single line via `jq -c`, otherwise every
    // linked issue is silently dropped (the readback parses JSON fragments).
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review-jira/scripts/gather-issue-context.sh');

    expect($content)->toContain('jq -c . >> "$RELATED_JSON_FILE"');
    expect($content)->not->toContain('printf \'%s\n\' "$j" >> "$RELATED_JSON_FILE"');
    expect($content)->toContain('while IFS= read -r line; do');
});

test('transition-to-code-review refuses non-review targets and re-verifies the landed status', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review-jira/scripts/transition-to-code-review.sh');

    // Guard: only a review status is allowed.
    expect($content)->toContain('is not a Code Review status');
    // Post-transition re-read so an acli false-positive "looped transition" is caught.
    expect($content)->toContain('acli jira workitem transition --key "$KEY" --status "$TARGET" --yes');
    expect($content)->toContain('exit 5');
});

test('transition-to-in-progress refuses non-progress targets, is idempotent, and catches false positives (issue #704)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review-jira/scripts/transition-to-in-progress.sh');

    // Guard: only a progress status is allowed.
    expect($content)->toContain('is not an In Progress status');
    // Idempotent no-op when already in the target status.
    expect($content)->toContain('already in progress');
    // Past-In-Progress guard: exit 4 when issue is already claimed/past.
    expect($content)->toContain('exit 4');
    expect($content)->toContain('past In Progress');
    // Post-transition re-read so an acli false-positive "looped transition" is caught.
    expect($content)->toContain('acli jira workitem transition --key "$KEY" --status "$TARGET" --yes');
    expect($content)->toContain('exit 5');
});

test('resolve-issue claims the GitHub issue before implementation and releases on Blocked (issue #704)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/resolve-issue/SKILL.md');

    // Claim happens immediately after the open-state gate.
    expect($content)->toContain('Claim the issue immediately');
    expect($content)->toContain('Resolve_by_AI:in-progress');
    // Abort when already claimed by another run.
    expect($content)->toContain('already claimed');
    // Apply-and-verify: external writes can be silently blocked.
    expect($content)->toContain('re-read and verify');
    // JIRA claim via the new helper.
    expect($content)->toContain('skills/code-review-jira/scripts/transition-to-in-progress.sh');
    // Release on Blocked/abort before PR.
    expect($content)->toContain('Release on Blocked');
    expect($content)->toContain('--remove-label');
    // Bugsnag: no claim.
    expect($content)->toContain('no claim step');
});

test('autoresolve QUERY excludes already-claimed issues via label negation (issue #704)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/autoresolve-oldest-github-issue/SKILL.md');

    // QUERY must include the -label: negation to skip already-claimed issues.
    expect($content)->toContain('-label:');
    expect($content)->toContain('Resolve_by_AI:in-progress');
    expect($content)->toContain('CLAIM_LABEL');
    // Line-17 amendment: claim label is a sanctioned write owned by the delegated skill.
    expect($content)->toContain('sanctioned write owned by the delegated skill');
});

test('JIRA context-consuming skills offer gather-issue-context.sh', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $skills = [
        $packageDir . '/skills/resolve-issue/SKILL.md',
        $packageDir . '/skills/prepare-issue-context/SKILL.md',
        $packageDir . '/skills/tester-cookbook/SKILL.md',
        $packageDir . '/skills/code-review-jira/SKILL.md',
    ];

    foreach ($skills as $skillFile) {
        $content = (string) file_get_contents($skillFile);
        expect($content)->toContain('skills/code-review-jira/scripts/gather-issue-context.sh');
    }
});

test('jira rule permits the single code-review transition via the helper only', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/jira/general.mdc');

    expect($content)->toContain('transition-to-code-review.sh');
    expect($content)->toContain('human-only');
    expect($content)->toContain('Never change JIRA issue status');
});

test('jira rule permits two sanctioned transitions and names both helpers (issue #704)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/jira/general.mdc');

    expect($content)->toContain('transition-to-in-progress.sh');
    expect($content)->toContain('transition-to-code-review.sh');
    // Both helpers must be mentioned as sanctioned exceptions.
    expect($content)->toContain('two exceptions');
});

test('resolve-issue moves the issue to code review via the transition helper after the PR is open', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/resolve-issue/SKILL.md');

    expect($content)->toContain('skills/code-review-jira/scripts/transition-to-code-review.sh');
});

test('new GitHub agent scripts are shipped, executable, and documented', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $scripts = [
        'gather-issue-context.sh' => 'Usage: gather-issue-context.sh <NUMBER|URL>',
        'parse-comments.sh' => 'Usage: parse-comments.sh <NUMBER|URL>',
    ];

    foreach ($scripts as $name => $usage) {
        $script = $packageDir . '/skills/code-review-github/scripts/' . $name;
        expect(file_exists($script))->toBeTrue();
        expect(is_executable($script))->toBeTrue();

        $content = (string) file_get_contents($script);
        expect($content)->toStartWith('#!/usr/bin/env bash');
        expect($content)->toContain($usage);
    }
});

test('new Bugsnag agent scripts are shipped, executable, and documented', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $scripts = [
        'gather-issue-context.sh' => 'Usage: gather-issue-context.sh <URL|ORG_SLUG/PROJECT_SLUG/ERROR_ID>',
        'parse-comments.sh' => 'Usage: parse-comments.sh <URL|ORG_SLUG/PROJECT_SLUG/ERROR_ID>',
    ];

    foreach ($scripts as $name => $usage) {
        $script = $packageDir . '/skills/code-review-bugsnag/scripts/' . $name;
        expect(file_exists($script))->toBeTrue();
        expect(is_executable($script))->toBeTrue();

        $content = (string) file_get_contents($script);
        expect($content)->toStartWith('#!/usr/bin/env bash');
        expect($content)->toContain($usage);
    }
});

test('github gather-issue-context persists linked items as compact single-line JSON', function (): void {
    // Regression guard (same class of bug fixed for the JIRA gatherer):
    // load-issue.sh emits pretty multi-line JSON while the linked-item render /
    // URL passes read the accumulator file line by line, so each record must
    // collapse to a single line via `jq -c` or every linked item is dropped.
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review-github/scripts/gather-issue-context.sh');

    expect($content)->toContain('jq -c . >> "$RELATED_JSON_FILE"');
    expect($content)->toContain('while IFS= read -r line; do');
});

test('GitHub context-consuming skills offer gather-issue-context.sh', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $skills = [
        $packageDir . '/skills/code-review-github/SKILL.md',
        $packageDir . '/skills/resolve-issue/SKILL.md',
        $packageDir . '/skills/prepare-issue-context/SKILL.md',
    ];

    foreach ($skills as $skillFile) {
        $content = (string) file_get_contents($skillFile);
        expect($content)->toContain('skills/code-review-github/scripts/gather-issue-context.sh');
    }
});

test('Bugsnag context-consuming skills offer gather-issue-context.sh', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $skills = [
        $packageDir . '/skills/code-review-bugsnag/SKILL.md',
        $packageDir . '/skills/resolve-issue/SKILL.md',
    ];

    foreach ($skills as $skillFile) {
        $content = (string) file_get_contents($skillFile);
        expect($content)->toContain('skills/code-review-bugsnag/scripts/gather-issue-context.sh');
    }
});

test('dependency-selection rule gates every new Composer package on activity and compatibility', function (): void {
    $packageDir = dirname(__DIR__, 2);
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

test('code-generation skills enforce a Read, Map & Verify pre-flight before implementing', function (): void {
    $packageDir = dirname(__DIR__, 2);

    $skills = [
        'resolve-issue',
        'test-driven-development',
        'create-test',
        'create-missing-tests-in-pr',
        'class-refactoring',
        'refactor-entry-point-to-action',
        'rewrite-tests-pest',
    ];

    foreach ($skills as $skill) {
        $content = (string) file_get_contents($packageDir . '/skills/' . $skill . '/SKILL.md');

        // The shared blocking pre-flight heading: "Read, Map & Verify before <something>".
        expect($content)->toContain('Read, Map & Verify before');

        // All three ordered steps must be present and bolded.
        expect($content)->toContain('**Read**');
        expect($content)->toContain('**Map**');
        expect($content)->toContain('**Verify**');

        // The pre-flight must be blocking and defer implementation until it passes.
        expect($content)->toContain('**blocking**');
        expect($content)->toContain('Only after Read, Map, and Verify are complete');
    }
});

test('analyze-problem skill carries the UI Redesign Lens with one-click default and wizard fallback', function (): void {
    $packageDir = dirname(__DIR__, 2);
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

test('api rule codifies the API-as-contract design standard (issue #552)', function (): void {
    $packageDir = dirname(__DIR__, 2);
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
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/api-review/SKILL.md');

    expect($content)->toContain('name: api-review');
    expect($content)->toContain('@rules/api/general.mdc');
    expect($content)->toContain('**Read-only skill**');
    expect($content)->toContain('templates/review-output.md');
});

test('cleanup-local-branches skill prunes gone and stale local branches safely (issue #550)', function (): void {
    $packageDir = dirname(__DIR__, 2);
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

test('refresh-claude-md skill regenerates CLAUDE.md only when stale or missing', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/refresh-claude-md/SKILL.md');

    expect($content)->toContain('name: refresh-claude-md');
    expect($content)->toContain('@rules/php/core-standards.mdc');
    expect($content)->toContain('@rules/git/general.mdc');
    // Narrow trigger: the skill runs only to keep CLAUDE.md correct, never as a general onboarding task.
    expect($content)->toContain('Trigger only to update or create `CLAUDE.md`');
    expect($content)->toContain('no refresh needed');
    // Four-phase ECC shape with selective reconnaissance.
    expect($content)->toContain('Reconnaissance');
    expect($content)->toContain('Glob and Grep');
    // Human-authored content must be preserved, never blindly replaced.
    expect($content)->toContain('preserve all human-authored sections');
    // Build validation must use the detected command, not a hard-coded PHP toolchain.
    expect($content)->toContain('detected build / quality command');
});

test('every ECC-ported skill ships with valid frontmatter conventions', function (): void {
    $packageDir = dirname(__DIR__, 2);

    $slugs = [
        'design-system', 'docker-patterns', 'e2e-testing', 'frontend-a11y',
        'frontend-design-direction', 'frontend-patterns', 'frontend-slides',
        'git-workflow', 'laravel-security', 'latency-critical-systems',
        'mysql-patterns', 'redis-patterns', 'security-bounty-hunter', 'seo',
        'vite-patterns',
    ];

    foreach ($slugs as $slug) {
        $content = (string) file_get_contents($packageDir . '/skills/' . $slug . '/SKILL.md');

        expect($content)->toContain('name: ' . $slug);
        expect($content)->toContain('license: MIT');
        expect($content)->toContain('author: "Petr Král (pekral.cz)"');
        expect($content)->toContain('description: "Use when');
    }
});

test('mysql-patterns and git-workflow defer to existing rules and skills instead of duplicating them', function (): void {
    $packageDir = dirname(__DIR__, 2);

    $mysql = (string) file_get_contents($packageDir . '/skills/mysql-patterns/SKILL.md');
    // Complementary-only: query tuning stays in the SQL rule, slow-query diagnosis in mysql-problem-solver.
    expect($mysql)->toContain('@rules/sql/optimalize.mdc');
    expect($mysql)->toContain('@skills/mysql-problem-solver/SKILL.md');

    $git = (string) file_get_contents($packageDir . '/skills/git-workflow/SKILL.md');
    // Conventions live in the git rule; branch cleanup and PR merging stay in their own skills.
    expect($git)->toContain('@rules/git/general.mdc');
    expect($git)->toContain('@skills/cleanup-local-branches/SKILL.md');
    expect($git)->toContain('@skills/merge-github-pr/SKILL.md');
    expect($git)->toContain('Defer to');
});

test('e2e-testing skill is gated on Playwright already being present', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/e2e-testing/SKILL.md');

    expect($content)->toContain('Preconditions');
    expect($content)->toContain('playwright.config');
    expect($content)->toContain('@playwright/test');
    // When Playwright is absent the skill must not install it; it defers to manual / Pest-Dusk testing.
    expect($content)->toContain('Do not install Playwright');
    expect($content)->toContain('@skills/test-like-human/SKILL.md');
});

test('frontend and vite skills target the Blade/Livewire/Alpine/Vite stack, not React', function (): void {
    $packageDir = dirname(__DIR__, 2);

    $vite = (string) file_get_contents($packageDir . '/skills/vite-patterns/SKILL.md');
    expect($vite)->toContain('laravel-vite-plugin');
    expect($vite)->toContain('@vite');

    $patterns = (string) file_get_contents($packageDir . '/skills/frontend-patterns/SKILL.md');
    expect($patterns)->toContain('@rules/laravel/livewire.mdc');

    $a11y = (string) file_get_contents($packageDir . '/skills/frontend-a11y/SKILL.md');
    expect($a11y)->toContain('wire:loading');
    expect($a11y)->toContain('aria-live');
});

test('duplicate and unsupported ECC skills were intentionally not ported', function (): void {
    $packageDir = dirname(__DIR__, 2);

    // tdd-workflow duplicates test-driven-development; security-scan depends on an external tool the package does not bundle.
    expect(is_dir($packageDir . '/skills/tdd-workflow'))->toBeFalse();
    expect(is_dir($packageDir . '/skills/security-scan'))->toBeFalse();
    // The retained equivalent still ships.
    expect(is_dir($packageDir . '/skills/test-driven-development'))->toBeTrue();
});
