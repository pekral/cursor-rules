<?php

declare(strict_types = 1);

test('compound-engineering rule codifies easier-future-work and per-project compound memory (issue #564)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $rulePath = $packageDir . '/rules/compound-engineering/general.mdc';

    expect(is_file($rulePath))->toBeTrue();

    $content = (string) file_get_contents($rulePath);

    // Frontmatter: always-applied cross-cutting rule.
    expect($content)->toContain('alwaysApply: true');

    // Pillar 1 — every change must make future work easier, and lessons are recorded.
    expect($content)->toContain('## Compound Engineering');
    expect($content)->toContain('make future work easier');

    // Pillar 2 — per-project compound memory, stored in the project, not this package.
    expect($content)->toContain('## Compound Memory (per project)');
    expect($content)->toContain('in the project being worked on, never in this shared rules package');
    expect($content)->toContain('existing part of the system rather than in a new abstraction');
    expect($content)->toContain('collective memory');

    // The rule is listed in the README Rules Overview table.
    $readme = (string) file_get_contents($packageDir . '/README.md');
    expect($readme)->toContain('`compound-engineering/general.mdc`');
});

test('analyze-problem skill requires pre-implementation research and a plan artifact (issue #564)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/analyze-problem/SKILL.md');

    expect($content)->toContain('@rules/compound-engineering/general.mdc');
    expect($content)->toContain('## Pre-Implementation Research & Plan');

    // The three research inputs.
    expect($content)->toContain('**Codebase**');
    expect($content)->toContain('**Commit history**');
    expect($content)->toContain('**Internet best practices');

    // The plan artifact is a text file or a GitHub issue.
    expect($content)->toContain('text file in the repo');
    expect($content)->toContain('GitHub issue');

    // The five mandatory parts of the plan.
    expect($content)->toContain('**Goal**');
    expect($content)->toContain('**Architecture**');
    expect($content)->toContain('**Implementation steps**');
    expect($content)->toContain('**Sources**');
    expect($content)->toContain('**Success criteria**');
});

test('git/general.mdc mandates English branch names regardless of assignment language', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/git/general.mdc');

    expect($content)->toContain('always written in English regardless of the assignment language');
});

test('resolve-issue skill requires the created branch name to be in English', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/resolve-issue/SKILL.md');

    expect($content)->toContain('name always in English, regardless of the assignment language');
});

test('git/general.mdc mandates one commit per phase for phased issues', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/git/general.mdc');

    expect($content)->toContain('One phase = one commit.');
    expect($content)->toContain('exactly one commit');
});

test('git/general.mdc requires the finished history to be a logical partition and to be reshaped when it is not', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/git/general.mdc');

    // The rule is about the history handed over, not the order the work happened in.
    expect($content)->toContain('The finished history is a logical partition of the change set — reshape it until it is.');
    expect($content)->toContain('git log --oneline <default-branch>..HEAD');

    // All three reshape moves plus the missing-commit case are named.
    expect($content)->toContain('**split it**');
    expect($content)->toContain('**squash or fixup**');
    expect($content)->toContain('**amend** the message');
    expect($content)->toContain('**create the commit it deserves**');

    // The PR description may never be used to excuse a bundled commit, and mechanics live in the git skill.
    expect($content)->toContain('the description documents the history, it does not excuse it');
    expect($content)->toContain('@skills/git-workflow/SKILL.md');
    expect($content)->toContain('git push --force-with-lease');
});

test('git/general.mdc requires every commit in the branch to be green on its own', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/git/general.mdc');

    // The invariant is per-commit, not per-branch-tip, and it is what makes cherry-pick safe.
    expect($content)->toContain('Every commit is green — a red commit never reaches the pull request.');
    expect($content)->toContain('**on its own**, not merely at the branch tip');
    expect($content)->toContain('cherry-picked onto the default branch and deployed without dragging the rest of the branch along');

    // A red or simulated-red test may never be committed.
    expect($content)->toContain('Never commit a failing test, and never simulate a failure in one.');
    expect($content)->toContain('the TDD RED step landed as a commit of its own');
    expect($content)->toContain('`->skip()` / `->todo()` / `markTestIncomplete()`');
    expect($content)->toContain('belong to the **same** commit');

    // A required rebase is verified commit by commit, and a failing commit is repaired in place.
    expect($content)->toContain('A rebase or reshape is finished only when every commit has been verified green, one at a time.');
    expect($content)->toContain('git rebase --exec \'<gate command>\' <base>');
    expect($content)->toContain('never append a repair commit at the tip');

    // No commit may stay red with an explanation in the PR description.
    expect($content)->toContain('A commit that cannot be made green alone is not a commit.');
});

test('git/general.mdc pull policy verifies the rebased range commit by commit', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/git/general.mdc');

    expect($content)->toContain('Verify the rebased range commit by commit');
    expect($content)->toContain('git rebase --exec \'composer check\' "origin/$DEFAULT_BRANCH"');
    expect($content)->toContain('leave an intermediate commit red while the tip stays green');
});

test('git/general.mdc requires the per-commit gate command to be non-mutating', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/git/general.mdc');

    // A fixer inside --exec dirties the tree and halts the rebase instead of reporting a verdict.
    expect($content)->toContain('must be the project\'s **non-mutating** checking half');
    expect($content)->toContain('never the fix-and-check `composer build`');
    expect($content)->toContain('stops the rebase with a dirty-tree error instead of a verdict');
});

test('the code-writing skills that create commits anchor on the logical-partition git rule', function (): void {
    $packageDir = dirname(__DIR__, 2);

    $refactoring = (string) file_get_contents($packageDir . '/skills/class-refactoring/SKILL.md');
    expect($refactoring)->toContain('Hand over a logically partitioned history.');
    expect($refactoring)->toContain('@rules/git/general.mdc` *Git Rules*');

    $processCr = (string) file_get_contents($packageDir . '/skills/process-code-review/SKILL.md');
    expect($processCr)->toContain('the finished history must be a logical partition of the change set');
    expect($processCr)->toContain('reshape the history before pushing');

    // resolve-issue's reconciliation step is the assignment-item form of the same rule.
    $planning = (string) file_get_contents($packageDir . '/skills/resolve-issue/references/commit-planning.md');
    expect($planning)->toContain('the assignment-item form of the partition rule in `@rules/git/general.mdc` *Git Rules*');
});

test('resolve-issue skill anchors phase planning on the one-phase-one-commit git rule', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/resolve-issue/SKILL.md');

    expect($content)->toContain('one phase = one commit');
    expect($content)->toContain('@rules/git/general.mdc');
});

test('resolve-issue skill refuses to resolve a closed / inactive task', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/resolve-issue/SKILL.md');

    expect($content)->toContain('The issue must be open / active.');
    expect($content)->toContain('do not resolve it');
});

test('compound-engineering rule defines the per-project memory file convention (issue #626)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/compound-engineering/general.mdc');

    expect($content)->toContain('docs/memory/PROJECT_MEMORY.md');
    expect($content)->toContain('### Promotion bar');
    expect($content)->toContain('### Curation pass');
    expect($content)->toContain('### Read protocol');
    expect($content)->toContain('Do not record secrets, credentials, tokens, or PII in the memory file');
});

test('compound-engineering rule provides the Blocked delegation hard-stop section referenced by agents (issue #626)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $rule = (string) file_get_contents($packageDir . '/rules/compound-engineering/general.mdc');
    $daidalos = (string) file_get_contents($packageDir . '/agents/daidalos.md');
    $talos = (string) file_get_contents($packageDir . '/agents/talos.md');

    expect($rule)->toContain('## Blocked delegation is a hard stop');
    expect(substr_count($rule, '## Blocked delegation is a hard stop'))->toBe(1);
    expect($daidalos)->toContain('*Blocked delegation is a hard stop*');
    expect($talos)->toContain('*Blocked delegation is a hard stop*');
});

test('record-project-memory skill exists and is write-only to the memory file (issue #626)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $skill = $packageDir . '/skills/record-project-memory/SKILL.md';

    expect(is_file($skill))->toBeTrue();

    $content = (string) file_get_contents($skill);
    expect($content)->toContain('name: record-project-memory');
    expect($content)->toContain('docs/memory/PROJECT_MEMORY.md');
    expect($content)->toContain('promotion bar');
    expect($content)->toContain('Curation pass');
    expect($content)->toContain('Never record secrets, credentials, tokens, or PII');
});

test('compound memory reads are hooked into the context phases (issue #626)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $daidalos = (string) file_get_contents($packageDir . '/agents/daidalos.md');
    $analyze = (string) file_get_contents($packageDir . '/skills/analyze-problem/SKILL.md');
    $prepare = (string) file_get_contents($packageDir . '/skills/prepare-issue-context/SKILL.md');

    expect($daidalos)->toContain('## Project memory');
    expect($daidalos)->toContain('docs/memory/PROJECT_MEMORY.md');
    expect($analyze)->toContain('docs/memory/PROJECT_MEMORY.md');
    expect($prepare)->toContain('docs/memory/PROJECT_MEMORY.md');
});

test('compound memory writes are hooked into convergence steps (issue #626)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $resolve = (string) file_get_contents($packageDir . '/skills/resolve-issue/SKILL.md');
    $process = (string) file_get_contents($packageDir . '/skills/process-code-review/SKILL.md');
    $daidalos = (string) file_get_contents($packageDir . '/agents/daidalos.md');

    expect($resolve)->toContain('@skills/record-project-memory/SKILL.md');
    expect($process)->toContain('@skills/record-project-memory/SKILL.md');
    expect($daidalos)->toContain('record-project-memory');
});

test('compound-engineering rule mandates early idempotent claim before work starts (issue #704)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/compound-engineering/general.mdc');

    // The section heading must exist.
    expect($content)->toContain('## Claim a tracker issue before working on it');

    // Core principle: claim early, idempotently, abort-on-conflict.
    expect($content)->toContain('Claim early and idempotently');
    expect($content)->toContain('Abort-on-conflict is the real collision guard');
    expect($content)->toContain('Exclude claimed issues from selection');

    // Release-on-Blocked semantics.
    expect($content)->toContain('Release on Blocked');

    // Bugsnag no-claim documented as known limitation.
    expect($content)->toContain('Bugsnag has no auto-claim');

    // Reference back to the skills that own the execution.
    expect($content)->toContain('@skills/resolve-issue/SKILL.md');
    expect($content)->toContain('@skills/autoresolve-oldest-github-issue/SKILL.md');
});

test('compound-engineering rule mandates assigning the most relevant existing label on issue creation (issue #734)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/compound-engineering/general.mdc');

    // The section heading must exist.
    expect($content)->toContain('## Assign the most relevant existing label when creating a tracker issue');
    expect($content)->toContain('most relevant existing label');

    // GitHub mechanics: enumerate existing labels before assigning.
    expect($content)->toContain('gh label list --json name,description');

    // No-random-label behaviour: never invent, never fall back to a random label, unlabelled is fine.
    expect($content)->toContain('never invent');
    expect($content)->toContain('never fall back to a random or first-available label');
    expect($content)->toContain('create the issue unlabelled rather than force a bad fit');

    // The EPIC label stays the sole sanctioned exception.
    expect($content)->toContain('`EPIC` is the sole sanctioned exception');

    // Reference the issue-creating skills that inherit this rule.
    expect($content)->toContain('@skills/create-issue/SKILL.md');
    expect($content)->toContain('@skills/create-issues-from-text/SKILL.md');
    expect($content)->toContain('@skills/blueprint/SKILL.md');
    expect($content)->toContain('@skills/product-capability/SKILL.md');

    $createIssue = (string) file_get_contents($packageDir . '/skills/create-issue/SKILL.md');
    $createIssuesFromText = (string) file_get_contents($packageDir . '/skills/create-issues-from-text/SKILL.md');
    $blueprint = (string) file_get_contents($packageDir . '/skills/blueprint/SKILL.md');
    $productCapability = (string) file_get_contents($packageDir . '/skills/product-capability/SKILL.md');

    foreach ([$createIssue, $createIssuesFromText, $blueprint, $productCapability] as $skillContent) {
        expect($skillContent)->toContain('Assign the most relevant existing label');
        expect($skillContent)->toContain('@rules/compound-engineering/general.mdc');
    }
});

test('compound-engineering rule mandates temporary-file hygiene with a hard memory-files exception (issue #694)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/compound-engineering/general.mdc');

    // The section heading must exist.
    expect($content)->toContain('## Temporary-file hygiene');

    // The memory-files exception must name the canonical project memory path verbatim.
    expect($content)->toContain('docs/memory/PROJECT_MEMORY.md');

    // The exception must state that memory files are never deleted.
    expect($content)->toContain('NEVER deleted');

    // The rule must reference daidalos step 7 as the reference implementation.
    expect($content)->toContain('daidalos');
});
