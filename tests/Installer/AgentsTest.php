<?php

declare(strict_types = 1);

use Pekral\CursorRules\Installer;
use Pekral\CursorRules\InstallerPath;

test('resolveAgentsSource returns the package agents directory when it exists', function (): void {
    $packageDir = dirname(__DIR__, 2);

    expect(InstallerPath::resolveAgentsSource())->toBe($packageDir . '/agents');
});

test('isAgentsEditor matches only claude and all', function (): void {
    expect(InstallerPath::isAgentsEditor(InstallerPath::EDITOR_CLAUDE))->toBeTrue();
    expect(InstallerPath::isAgentsEditor(InstallerPath::EDITOR_ALL))->toBeTrue();
    expect(InstallerPath::isAgentsEditor(InstallerPath::EDITOR_CURSOR))->toBeFalse();
    expect(InstallerPath::isAgentsEditor(InstallerPath::EDITOR_CODEX))->toBeFalse();
});

test('resolveAgentsTargetDirectories returns .claude/agents for editor=claude', function (): void {
    expect(InstallerPath::resolveAgentsTargetDirectories('/project', InstallerPath::EDITOR_CLAUDE))
        ->toBe(['/project/.claude/agents']);
});

test('resolveAgentsTargetDirectories returns .claude/agents for editor=all', function (): void {
    expect(InstallerPath::resolveAgentsTargetDirectories('/project', InstallerPath::EDITOR_ALL))
        ->toBe(['/project/.claude/agents']);
});

test('resolveAgentsTargetDirectories returns empty list for editor=cursor', function (): void {
    expect(InstallerPath::resolveAgentsTargetDirectories('/project', InstallerPath::EDITOR_CURSOR))->toBe([]);
});

test('resolveAgentsTargetDirectories returns empty list for editor=codex', function (): void {
    expect(InstallerPath::resolveAgentsTargetDirectories('/project', InstallerPath::EDITOR_CODEX))->toBe([]);
});

test('install with editor=claude copies the athena agent to .claude/agents', function (): void {
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
        ob_end_clean();

        expect(is_file($root . '/.claude/agents/athena.md'))->toBeTrue();
        expect(is_file($root . '/.claude/agents/argos.md'))->toBeFalse();
        expect(is_dir($root . '/.cursor/agents'))->toBeFalse();
        expect(is_dir($root . '/.codex/agents'))->toBeFalse();
    } finally {
        installerRestoreEnvAndCleanup($homeBefore, $originalCwd, $root);
    }
});

test('install with editor=cursor does not copy agents', function (): void {
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
        Installer::run(['cursor-rules', 'install', '--editor=cursor']);
        ob_end_clean();

        expect(is_dir($root . '/.cursor/agents'))->toBeFalse();
        expect(is_dir($root . '/.claude/agents'))->toBeFalse();
    } finally {
        installerRestoreEnvAndCleanup($homeBefore, $originalCwd, $root);
    }
});

test('athena is the single code-review agent and the retired argos definition no longer ships (issue #753)', function (): void {
    $packageDir = dirname(__DIR__, 2);

    expect(is_file($packageDir . '/agents/argos.md'))->toBeFalse();
    expect(is_file($packageDir . '/assets/agents/argos.png'))->toBeFalse();

    $content = (string) file_get_contents($packageDir . '/agents/athena.md');

    // Athena absorbed the tracker-wrapper routing the retired reviewer owned.
    expect($content)->toContain('@skills/code-review-github/SKILL.md');
    expect($content)->toContain('@skills/code-review-jira/SKILL.md');
    expect($content)->toContain('@skills/code-review-bugsnag/SKILL.md');
    // No resolvable source falls back to the base read-only code-review skill rather than a tracker wrapper.
    expect($content)->toContain('No resolvable source');
    expect($content)->toContain('fall back to the default `@skills/code-review/SKILL.md`');
    // One agent, one pass — quality, architecture, optimisation and security share a single report.
    expect($content)->toContain('One CR agent, one review pass');
    expect($content)->toContain('Code-review mode (post-implementation)');
    // The retired split-review handoff status is gone; the single CR handoff replaces it.
    expect($content)->not->toContain('Security CR done');
    expect($content)->toContain('`CR done` (review mode)');

    // No agent definition may still reference the retired reviewer.
    $globResult = glob($packageDir . '/agents/*.md');
    $agentFiles = $globResult !== false ? $globResult : [];
    expect($agentFiles)->not->toBeEmpty();

    foreach ($agentFiles as $agentFile) {
        expect((string) file_get_contents($agentFile))->not->toContain('argos');
    }
});

test('agents directory ships the talos code-writing subagent with required frontmatter', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $agentPath = $packageDir . '/agents/talos.md';

    expect(is_file($agentPath))->toBeTrue();

    $content = (string) file_get_contents($agentPath);
    expect($content)->toContain('name: talos');
    expect($content)->toContain('tools: Read, Write, Edit, Glob, Grep, Bash');
    expect($content)->toContain('@skills/resolve-issue/SKILL.md');
    expect($content)->toContain('@skills/resolve-issue/references/source-detection.md');
});

test('agents directory ships the metis problem-analysis subagent with required frontmatter', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $agentPath = $packageDir . '/agents/metis.md';

    expect(is_file($agentPath))->toBeTrue();

    $content = (string) file_get_contents($agentPath);
    expect($content)->toContain('name: metis');
    expect($content)->toContain('tools: Read, Glob, Grep, Bash');
    expect($content)->toContain('tools: Read, Glob, Grep, Bash, WebSearch, WebFetch');
    expect($content)->toContain('@skills/analyze-problem/SKILL.md');
});

test('agents directory ships the daidalos orchestrator subagent with required frontmatter', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $agentPath = $packageDir . '/agents/daidalos.md';

    expect(is_file($agentPath))->toBeTrue();

    $content = (string) file_get_contents($agentPath);
    expect($content)->toContain('name: daidalos');
    expect($content)->toContain('tools: Task, Read, Glob, Grep, Bash');
    expect($content)->toContain('@skills/resolve-issue/references/source-detection.md');
    expect($content)->toContain('@skills/autoresolve-oldest-github-issue/SKILL.md');
    // Shared task brief: daidalos gathers context into a git-ignored ephemeral brief before dispatching.
    expect($content)->toContain('Shared task brief');
    expect($content)->toContain('.claude/run/');
});

test('agents directory ships the athena security-CR subagent with required frontmatter', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $agentPath = $packageDir . '/agents/athena.md';

    expect(is_file($agentPath))->toBeTrue();

    $content = (string) file_get_contents($agentPath);
    expect($content)->toContain('name: athena');
    expect($content)->toContain('tools: Read, Glob, Grep, Bash');
    expect($content)->toContain('tools: Read, Glob, Grep, Bash, WebSearch, WebFetch');
    expect($content)->toContain('model: opus');
    expect($content)->toContain('@skills/security-review/SKILL.md');
    expect($content)->toContain('@skills/laravel-security/SKILL.md');
    expect($content)->toContain('@skills/security-bounty-hunter/SKILL.md');
    expect($content)->toContain('@skills/security-threat-analysis/SKILL.md');
    expect($content)->toContain('@skills/resolve-issue/references/source-detection.md');
    // Read-only stance: never edits, commits, pushes, or merges.
    expect($content)->toContain('read-only');
});

test('athena also runs a pre-implementation security-analysis mode that feeds talos', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/agents/athena.md');

    // Dual-mode contract: security analysis (pre-implementation) plus the code review (post-implementation).
    expect($content)->toContain('Security analysis mode (pre-implementation)');
    expect($content)->toContain('Code-review mode (post-implementation)');
    // Analysis mode frames the remediation through analyze-problem so talos can implement it.
    expect($content)->toContain('@skills/analyze-problem/SKILL.md');
    // Both handoff statuses exist so the caller can route the result.
    expect($content)->toContain('Security analysis done');
    expect($content)->toContain('CR done');
});

test('athena reviews the diff of the current changes only (issue #753)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/agents/athena.md');

    expect($content)->toContain('## Review scope — the diff of the current changes only');
    expect($content)->toContain('**You review the diff, never the repository.**');
    // A finding must anchor to a changed line, even when neighbouring code was read for understanding.
    expect($content)->toContain('the finding must anchor to a changed line');
    // The whole-app sweeps are explicitly constrained; a full audit is a different, human-triggered job.
    expect($content)->toContain('**Whole-repository sweeps run diff-scoped.**');
    expect($content)->toContain('never entered from a CR pass');
    // The scope is handed to the wrapper, not left to it to guess.
    expect($content)->toContain('and pass it the diff scope from *Review scope* above');

    // The docs mirror the scope so a reader of the roster sees it too.
    $docs = (string) file_get_contents($packageDir . '/docs/agents.md');
    expect($docs)->toContain('**Scope: the current changes only.**');
});

test('athena files out-of-scope findings as tracker issues instead of blocking the change (issue #753)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/agents/athena.md');

    expect($content)->toContain('## Findings outside the diff — file them in the tracker');
    // The section must disambiguate itself from the CR's blocking scope-creep category.
    expect($content)->toContain('Not to be confused with the CR\'s scope-creep category');
    expect($content)->toContain('it **blocks the merge gate**. It is never filed away as an issue');
    // Filing goes through the canonical skill, one issue per finding.
    expect($content)->toContain('@skills/create-issue/SKILL.md');
    expect($content)->toContain('one issue per finding, never a bundle');
    // The tracker is the one the reviewed source resolves to — never a hardcoded default.
    expect($content)->toContain('the URL / reference the caller handed you decides it, never a default');
    expect($content)->toContain('the GitHub repository from the error\'s `linkedIssues[]`');
    // A finding on a changed line stays an in-scope CR finding — filing is not an escape hatch.
    expect($content)->toContain('never file that as an issue to avoid raising it');
    // Loop safety and dedup: filed once per PR, and never as a duplicate of an open issue.
    expect($content)->toContain('**Do not duplicate.**');
    expect($content)->toContain('once per pull request');
    // Filed issues are reported but never counted toward the convergence gate.
    expect($content)->toContain('they never block convergence');
    expect($content)->toContain('**Out of scope filed:**');
    // The new outbound surface must not carry secret values out of the diff.
    expect($content)->toContain('**Never leak a secret into the issue body.**');
    expect($content)->toContain('the secret itself is rotated out of band, never restated in the tracker');

    $docs = (string) file_get_contents($packageDir . '/docs/agents.md');
    expect($docs)->toContain('**Findings outside the diff become tracker issues.**');
    expect($docs)->toContain('an unrequested change *inside* the diff stays a blocking finding');
});

test('athena runs every code-review skill the project defines (issue #753)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/agents/athena.md');

    // The inventory is a named, verifiable contract, not a loose prose list.
    expect($content)->toContain('Run every code-review skill the project defines — the complete inventory');
    expect($content)->toContain('**no CR skill may be left unrun**');
    expect($content)->toContain('Never skip a lens because another one might catch the same defect');

    $crSkills = [
        'prepare-issue-context',
        'assignment-compliance-check',
        'code-review',
        'analyze-problem',
        'security-review',
        'api-review',
        'class-refactoring',
        'laravel-security',
        'security-bounty-hunter',
        'security-threat-analysis',
        'laravel-authorization-review',
        'refactor-entry-point-to-action',
        'mysql-problem-solver',
        'pr-summary',
    ];

    foreach ($crSkills as $skill) {
        expect(is_dir($packageDir . '/skills/' . $skill))->toBeTrue('CR skill ships: ' . $skill);
        expect($content)->toContain('@skills/' . $skill . '/SKILL.md');
    }

    // Deliberate exclusions: an offensive skill needing human authorisation, and the write-capable test authoring.
    expect($content)->toContain('`@skills/penetration-tester/SKILL.md` runs only on an explicit human request');
    expect($content)->toContain('belong to `apollon`');

    // The handoff has to account for every inventory row.
    expect($content)->toContain('every row of the *complete inventory* in step 4');
});

test('athena references the laravel security audit workflow for existing-app audits', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/agents/athena.md');

    // The 7-area audit workflow lives in a references file; athena links to it, not re-implements it.
    expect($content)->toContain('@skills/laravel-security/references/audit-workflow.md');
});

test('athena standalone publishing routes to the tracker-matching CR channel, not always GitHub (issue #691)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/agents/athena.md');

    // Publishing must route to the tracker-specific channel, not always GitHub.
    expect($content)->toContain('skills/code-review-github/scripts/upsert-comment.sh');
    // The wrapper owns publishing; athena self-publishes only under a stated, checkable condition.
    expect($content)->toContain('the wrapper owns it; you publish only when it did not');
    expect($content)->toContain('one CR run produces exactly **one** CR comment');
    expect($content)->toContain('You publish yourself in exactly two cases, both checkable before you act');
    expect($content)->toContain('skills/code-review-jira/scripts/upsert-comment.sh');
    expect($content)->toContain('@skills/code-review-bugsnag/SKILL.md');
    // Must not hardcode GitHub as the only standalone publish channel.
    expect($content)->not->toContain('a GitHub PR URL is available does it publish directly');
    // The tracker-matching routing must be explicit.
    expect($content)->toContain('tracker-matching');
});

test('laravel-security audit-workflow ships with all 7 areas, severity mapping, and regression-test requirement', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/laravel-security/references/audit-workflow.md');

    // Severity mapping: 5-level audit scale maps to 3-level CR scale.
    expect($content)->toContain('Critical');
    expect($content)->toContain('Moderate');
    expect($content)->toContain('Minor');

    // All 7 audit areas must be present.
    expect($content)->toContain('Authorization');
    expect($content)->toContain('Authentication');
    expect($content)->toContain('Validation');
    expect($content)->toContain('XSS');
    expect($content)->toContain('File upload');
    expect($content)->toContain('Secrets');
    expect($content)->toContain('Dependencies');

    // Every confirmed finding must carry a regression-test sketch.
    expect($content)->toContain('regresní test');
    // Defensive framing: audit, not attack.
    expect($content)->toContain('autorizovaném prostředí');
});

test('every dispatched agent reads and appends to the shared task brief', function (): void {
    $packageDir = dirname(__DIR__, 2);

    foreach (['metis', 'talos', 'apollon', 'athena', 'hermes'] as $agent) {
        $content = (string) file_get_contents($packageDir . '/agents/' . $agent . '.md');
        expect($content)->toContain('Shared task brief');
        expect($content)->toContain('.claude/run/');
    }
});

test('every agent definition declares a model in frontmatter', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $globResult = glob($packageDir . '/agents/*.md');
    $agentFiles = $globResult !== false ? $globResult : [];

    expect($agentFiles)->not->toBeEmpty();

    foreach ($agentFiles as $agentFile) {
        $content = (string) file_get_contents($agentFile);
        // Anchor to a frontmatter line starting with `model:` so a stray substring
        // (e.g. the prose "## Delegation model") cannot satisfy the assertion.
        expect($content)->toMatch('/^model:\s*\S+/m');
    }
});

test('every agent definition declares effort: high, never max (issue #753)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $globResult = glob($packageDir . '/agents/*.md');
    $agentFiles = $globResult !== false ? $globResult : [];

    expect($agentFiles)->not->toBeEmpty();

    foreach ($agentFiles as $agentFile) {
        $content = (string) file_get_contents($agentFile);
        // Anchor to a frontmatter line so prose mentioning effort cannot satisfy the assertion.
        expect($content)->toMatch('/^effort: high$/m');
        // max is the level this roster deliberately does not use.
        expect($content)->not->toMatch('/^effort: (max|xhigh)$/m');
    }

    // The anatomy section documents the field and the roster-wide level.
    $docs = (string) file_get_contents($packageDir . '/docs/agents.md');
    expect($docs)->toContain('**Every agent in this roster declares `effort: high`**');
    expect($docs)->toContain('Do not raise an agent to `xhigh` / `max`');
    expect($docs)->toContain('effort: high' . "\n" . '---');
});

test('daidalos delegates the end-to-end run by dispatching metis, talos and athena to convergence', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/agents/daidalos.md');

    // True delegation: each step is dispatched as the matching specialist agent through the Task tool.
    expect($content)->toContain('dispatch `metis` through the Task tool');
    expect($content)->toContain('Dispatch `talos` through the Task tool');
    expect($content)->toContain('Dispatch `athena` through the Task tool');
    // The review step dispatches exactly one CR agent — no second reviewer alongside her.
    expect($content)->toContain('**exactly once, the single CR agent**');
    expect($content)->toContain('Do **not** dispatch a second reviewer alongside her');
    // The implementation step still routes through resolve-issue (owned by talos), and the convergence gate is named.
    expect($content)->toContain('@skills/resolve-issue');
    expect($content)->toContain('0 Critical');
});

test('daidalos dispatches athena for a pre-implementation security-risk analysis that feeds talos', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/agents/daidalos.md');

    // Security-focused tasks are analysed by athena before talos implements them.
    expect($content)->toContain('dispatch `athena` through the Task tool');
    expect($content)->toContain('security analysis mode');
    expect($content)->toContain('Security analysis done');
});

test('daidalos marks a cross-cutting mix of requirements as an EPIC with linked sub-issues', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $daidalos = (string) file_get_contents($packageDir . '/agents/daidalos.md');

    // daidalos detects the cross-cutting mix and dispatches metis to build the EPIC parent + sub-issues.
    expect($daidalos)->toContain('EPIC parent');
    expect($daidalos)->toContain('one sub-issue per application area');
    expect($daidalos)->toContain('linked back to the parent');

    // The EPIC variant wins over the plain decomposition bullet when both could apply.
    expect($daidalos)->toContain('this EPIC variant takes precedence');

    // EPIC run-mode parity: the handoff contract omits PR / feedback for an EPIC run too.
    expect($daidalos)->toContain('or an EPIC run, which have no PR');

    // The how lives in the create-issues-from-text skill, which daidalos / metis defer to.
    $skill = (string) file_get_contents($packageDir . '/skills/create-issues-from-text/SKILL.md');
    expect($skill)->toContain('EPIC parent & sub-issues');
    expect($skill)->toContain('gh label create EPIC');
    expect($skill)->toContain('Part of #<parent>');
});

test('daidalos processes multiple resolved sources sequentially and never fans them out in parallel', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/agents/daidalos.md');

    // The concurrency section processes a single request's multiple sources strictly one at a time.
    expect($content)->toContain('Sequential processing of multiple sources');
    expect($content)->toContain('one at a time, strictly sequentially — never in parallel');
    // The analysis-only branch dispatches metis sequentially, not as a parallel fan-out.
    expect($content)->toContain('dispatch their `metis` runs one after another — strictly sequentially, never in parallel');
    // No fan-out across sources in one message.
    expect($content)->toContain('Do **not** fan work out across sources');
    // Each source still gets its own per-source brief.
    expect($content)->toContain('own** shared brief');
    // Step 3 classifies each resolved source independently when several were resolved.
    expect($content)->toContain('classify **each one independently**');
});

test('daidalos auto-remediates a missing code review at merge time and then continues the merge flow', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/agents/daidalos.md');

    // A merge that finds no (or a stale / non-converged) code review runs the CR + fix loop instead of stopping.
    expect($content)->toContain('Missing code review is auto-remediated, not a dead end');
    expect($content)->toContain('When it converges (0 Critical + 0 Moderate), continue the merge flow');
    // The remediation is bounded and the other merge gates stay hard stops.
    expect($content)->toContain('One remediation cycle per merge attempt');
    expect($content)->toContain('The auto-remediation covers the code-review gate only');

    // The merge skill points the orchestrating caller at the same remediation path without waiving the gate.
    $skill = (string) file_get_contents($packageDir . '/skills/merge-github-pr/SKILL.md');
    expect($skill)->toContain('may treat an unmet gate as a trigger to run that review to convergence and then re-enter this skill');
});

test('daidalos keeps the writing path on the shared tree but lets read-only CR agents isolate in a worktree, and cleans them up', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/agents/daidalos.md');

    // The writing path (talos) still never uses worktrees — concurrent writers serialise on the shared tree.
    expect($content)->toContain('The writing path never uses git worktrees');
    expect($content)->toContain('single shared git working tree');
    expect($content)->toContain('there is no isolated-worktree escape for the writing path');
    // Read-only CR agents may isolate in a worktree for parallel review.
    expect($content)->toContain('read-only code-review agent `athena` may use a git worktree');
    // Daidalos owns worktree cleanup so the repo stays clean after the run / merge.
    expect($content)->toContain('git worktree remove');
    expect($content)->toContain('git worktree prune');
});

test('the read-only CR agent documents an optional review worktree it hands back for daidalos cleanup', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/agents/athena.md');

    // The CR agent may isolate its review in a read-only worktree when needed.
    expect($content)->toContain('Review worktree (optional)');
    expect($content)->toContain('git worktree add');
    // It hands the path back so daidalos removes it during cleanup.
    expect($content)->toContain('Record the worktree path in your handoff');
    // Standalone runs clean up after themselves.
    expect($content)->toContain('git worktree remove');
});

test('agents directory ships the apollon test-engineer subagent with required frontmatter', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $agentPath = $packageDir . '/agents/apollon.md';

    expect(is_file($agentPath))->toBeTrue();

    $content = (string) file_get_contents($agentPath);
    expect($content)->toContain('name: apollon');
    // Write-capable test engineer: authors PHPUnit/Pest tests, so the tools line grants Write and Edit.
    expect($content)->toContain('tools: Read, Write, Edit, Glob, Grep, Bash');
    expect($content)->toContain('model: sonnet');
    expect($content)->toContain('@skills/create-test/SKILL.md');
    expect($content)->toContain('@skills/test-like-human/SKILL.md');
    expect($content)->toContain('@skills/e2e-testing/SKILL.md');
    expect($content)->toContain('@skills/resolve-issue/references/source-detection.md');
});

test('agents directory ships the hermes release-announcer subagent with required frontmatter', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $agentPath = $packageDir . '/agents/hermes.md';

    expect(is_file($agentPath))->toBeTrue();

    $content = (string) file_get_contents($agentPath);
    expect($content)->toContain('name: hermes');
    expect($content)->toContain('tools: Read, Glob, Grep, Bash');
    expect($content)->toContain('model: sonnet');
    expect($content)->toContain('@skills/article-writing/SKILL.md');
    expect($content)->toContain('@skills/resolve-issue/references/source-detection.md');
    // Read-only stance: never edits, commits, pushes, or merges.
    expect($content)->toContain('read-only');
    // Publishes only via the canonical wrapper, never raw gh commands.
    expect($content)->toContain('upsert-comment');
});

test('parallel agents share their split output through the brief under an append lock with a barrier before consolidation', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $daidalos = (string) file_get_contents($packageDir . '/agents/daidalos.md');

    // The brief is the rendezvous where parallel agents' split output becomes available to peers.
    expect($daidalos)->toContain('Parallel handoff sharing');
    // Concurrency-safe append: a per-brief append lock guards every `cat >>` so parallel writes never interleave.
    expect($daidalos)->toContain('Concurrency-safe append');
    expect($daidalos)->toContain('$BRIEF.lock');
    // Barrier: a peer's parallel output is only consolidated after every parallel handoff has landed in the brief.
    expect($daidalos)->toContain('Barrier before consolidation');

    // The CR agent references the append lock so a handoff never clobbers a parallel sibling's.
    $athena = (string) file_get_contents($packageDir . '/agents/athena.md');
    expect($athena)->toContain('$BRIEF.lock');
});

test('every agent keeps commit messages and PR titles in English regardless of the assignment language', function (): void {
    $packageDir = dirname(__DIR__, 2);

    foreach (['daidalos', 'talos', 'athena', 'metis', 'apollon', 'hermes'] as $agent) {
        $content = (string) file_get_contents($packageDir . '/agents/' . $agent . '.md');
        expect($content)->toContain('commit messages and PR titles are always English');
    }
});

test('docs/agents.md describes WebFetch as an egress risk, not a working-tree write risk (issue #748 CR fix)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/docs/agents.md');

    $toolsLine = '- **`tools`** — restrict to what the agent needs. A read-only reviewer needs `Read, Glob, Grep,'
        . ' Bash` only; `athena` and `metis` additionally carry `WebSearch, WebFetch` to verify'
        . ' third-party API documentation and research authoritative sources — read-only with respect to the'
        . ' working tree; egress is subject to the host allow-list each agent carries in its own'
        . ' `## Web egress safety (issue #748)` section (`agents/athena.md`, `agents/metis.md`), not the'
        . ' CR-diff-scoped guard in `rules/code-review/general.mdc`'
        . ' *Third-Party API & Service Documentation Verification*.';
    expect($content)->toContain($toolsLine);
});

test(
    'the CR agent ships the Web egress safety section reaching its unconditional URL-read path (issue #748 CR fix, iteration 3)',
    function (): void {
        $packageDir = dirname(__DIR__, 2);
        $content = (string) file_get_contents($packageDir . '/agents/athena.md');

        expect($content)->toContain('## Web egress safety (issue #748)');
        expect($content)->toContain(
            'through `@skills/code-review-github/SKILL.md` / `@skills/code-review-jira/SKILL.md` reading an'
                . ' inventoried external URL "with your own tools"',
        );
    },
);
