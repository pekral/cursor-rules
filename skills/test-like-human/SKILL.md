---
name: test-like-human
description: "Use when testing a pull request from a real user perspective. Follow PR testing instructions, simulate realistic scenarios, and produce a human-readable report."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/git/general.mdc`
- Apply `@rules/jira/general.mdc`
- Output must be human-readable (no technical logs or internal details)
- Focus on user-visible behavior, not implementation

## Use when
- You need to validate a pull request from a real user perspective
- You want structured testing based on PR instructions

## Required approach

### 1. Understand the context
- Load the pull request (prefer `gh`, fallback to MCP tools)
- Read description, comments, and discussions
- Identify the expected final behavior

### 2. Extract testing instructions
- Locate **"Testing Recommendations"**
- Extract all scenarios
- Do not invent new requirements unless needed to verify suspicious behavior
- Every extracted scenario must be covered by an automated test. Map each scenario to an existing test; if no matching test exists, write one before the run is considered complete. Build/CI-level scenarios (e.g. `composer build`, coverage thresholds) are considered covered by the project's CI pipeline and do not require a duplicate unit test.

### 3. Choose testing method per scenario
Use the most appropriate approach:
- UI → browser tools
- API → `curl` or equivalent (prefer API docs if available)
- Backend logic → `php artisan tinker` or equivalent
- CLI → terminal commands

Do not over-test — focus on meaningful validation.

### 4. Execute as a senior tester
For each scenario, think:
- what the user tries to achieve
- where the flow could fail or confuse
- whether behavior feels correct and trustworthy
- for backend changes: whether data ends in the correct state

### 5. Validate results
- Compare expected vs actual behavior
- Identify inconsistencies, confusion, or broken flows
- Do not expose technical details in conclusions

## Report format

Local in-conversation report only — use the template defined in `templates/test-report.md` for the agent's own working notes (raw scenario results, observations, blockers). This template **must not** be posted to any tracker.

## Deliver
- Reference the pull request
- Include all tested scenarios
- Provide overall summary
- Highlight failed / blocked / unclear cases
- Recommend whether the change is ready from a user perspective

## After completion

The tracker-facing output is **always produced by `@skills/pr-summary/SKILL.md`**. This skill does not author its own JIRA / GitHub comment template — that responsibility belongs to `pr-summary`, which already enforces the uniform *Authors / Available behind / Summary of changes / How to test* contract.

1. Hand the raw test-report markdown (from `templates/test-report.md`) and the per-scenario results to `@skills/pr-summary/SKILL.md` as input context for the publishing step.
2. Invoke `pr-summary` with the target tracker matching the PR origin (GitHub for GitHub PRs, JIRA for JIRA-tracked work).
3. The published tracker comment **must**:
   - credit the **real change author(s)** in the `Authors` line — resolved by `pr-summary` from git history and PR metadata, never the agent / tester identity running this skill;
   - include the **Available behind** line whenever the verified change is reachable only behind a test parameter (feature flag, ENV switch, query string, admin toggle, allow-listed account) — pass the gating toggle and required value to `pr-summary` so its first **How to test** step enables it;
   - in the **How to test** section, fold the test scenarios actually executed by this skill (including pass / fail / blocked / unclear status next to each step), so the published comment reflects real verification work rather than restating the PR description.
4. Append a short non-public follow-up message to the dev team (in conversation, not on the tracker) listing failed / blocked / unclear scenarios with enough technical detail to act on them. That message is for the developers — it complements the `pr-summary` tracker comment, it does not replace it.
