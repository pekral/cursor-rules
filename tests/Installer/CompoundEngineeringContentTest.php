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
