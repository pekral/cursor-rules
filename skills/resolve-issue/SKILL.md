---
name: resolve-issue
description: "Use when resolving an issue from any supported tracker (GitHub, JIRA, Bugsnag). Detects the source automatically from the provided link or ID, implements a safe fix or feature, validates with tests, and creates a pull request."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/php/dependency-selection.mdc` — whenever the resolution flow needs to add a new Composer dependency (Packagist or a GitHub-hosted VCS repository), run the Activity gate + Compatibility gate from that rule before recommending a package, and embed the selection note in the PR description. When no candidate passes the gates, stop and surface the disqualification table to the user instead of adopting an inactive library.
- Apply `@rules/git/general.mdc`
- Apply `@rules/reports/general.mdc`. The **final technical report** this skill posts on the GitHub PR (code-review and security-review summary block) stays in canonical English per the rule's *Exception — technical CR findings on the GitHub PR*. The **non-technical report** posted on the original issue / JIRA ticket / Bugsnag-linked GitHub issue follows the language of the source assignment. Code identifiers, file paths, severity labels, and CLI commands stay verbatim regardless of the surrounding prose language; never mix two natural languages inside a single comment.
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Follow project architecture and testing rules
- Do not expose sensitive/internal details in user-facing messages
- Preserve existing behavior unless explicitly required otherwise

## Use when
- You are given an issue link, URL, or ID from any supported tracker
- You need to implement a bugfix or feature based on the issue

## Source detection

See `references/source-detection.md` for the detection table and rules.

## Preparation

Before starting the resolution flow:
- Switch to the `main` branch and pull the latest changes so the working tree reflects the current state of the repository before creating the feature branch.

## Required approach
- Fully analyze the issue (description, comments, attachments)
- Clearly define scope before writing code
- Classify the task:
  - **Bug** — incorrect existing behavior or runtime error
  - **Feature** — new behavior
- Prefer minimal, safe, and readable changes
- Keep scope limited unless related fixes are trivial and safe
- When implementing DB work, prefer batch operations over per-row queries inside loops per `@rules/sql/optimalize.mdc` "Batch over per-row operations" — ModelManager `batchUpdate` / `batchInsert`, `whereIn(...)->delete()`, or a single bulk read keyed in memory. Per-row queries are allowed only when iterations have an unavoidable side-effect dependency that is justified in a code comment.

## Execution

1. Verify the issue belongs to the current project before proceeding:
   - **GitHub:** the issue repository must match the current Git remote origin.
   - **JIRA:** the issue project key must match the configured JIRA project for this repository.
   - **Bugsnag:** the error's linked GitHub issue/PR repository (`linkedIssues[]` in the loaded JSON) must match the current Git remote origin. When the error has no linked GitHub issue, confirm the Bugsnag project corresponds to this repository before proceeding.
   - If the issue does not belong to the current project, refuse to process it and inform the user.
2. Fetch and analyze the issue from the detected source by running the deterministic loader for that tracker — never call `gh`, `acli`, or REST endpoints directly. Read all required fields off the resulting JSON document.
   - **GitHub:** `skills/code-review-github/scripts/load-issue.sh <NUMBER|URL>` for the structured JSON, or `skills/code-review-github/scripts/gather-issue-context.sh <NUMBER|URL>` for a full Markdown context brief in one pass (issue/PR + comments + changed files + commits + reviews + CI checks + recursively-loaded linked issues/PRs + an inventory of external URLs to follow). Read attachment content and the inventoried URLs with your own tools; follow useful links recursively to a sensible depth. If the script is unavailable (missing tool, exit code 2/3), fall back to the GitHub MCP server.
   - **JIRA:** `skills/code-review-jira/scripts/load-issue.sh <KEY|URL>` for the structured JSON, or `skills/code-review-jira/scripts/gather-issue-context.sh <KEY|URL>` for a full Markdown context brief in one pass (issue + comments + attachments + recursively-loaded linked issues + an inventory of external URLs to follow). Read attachment content and the inventoried URLs with your own tools — `acli` cannot fetch them; follow useful links recursively to a sensible depth. If the script is unavailable (missing tool, exit code 2/3), fall back to the JIRA MCP server.
   - **Bugsnag:** `skills/code-review-bugsnag/scripts/load-issue.sh <URL|TRIPLE>` (requires `BUGSNAG_TOKEN`), or `skills/code-review-bugsnag/scripts/gather-issue-context.sh <URL|TRIPLE>` for a full Markdown context brief in one pass (error header + latest event + in-project stacktrace + comments + linked issues + an inventory of external URLs). The JSON carries the error class, message, status, `context`, the in-project `latestEvent.stacktrace` frames (the entry point for the TDD reproduction), `comments[]`, and `linkedIssues[]` (the mirrored GitHub issue/PR). If the script is unavailable (missing tool/token, exit code 2/3), fall back to a Bugsnag MCP server.
3. Define exact requirements and expected behavior.
4. Classify the task (bug or feature).

### Comment analysis

5. Before analyzing the problem, fetch and read **all comments and replies** from the issue tracker (GitHub, JIRA, or Bugsnag). For GitHub, JIRA, and Bugsnag issues, read `comments[]` directly off the JSON loaded in step 2 — do not issue a second listing call:
   - Group comments by conversation thread (e.g., review threads, reply chains).
   - For each thread, determine:
     - **Current requirements** — requests or conditions that are still valid and unfulfilled.
     - **Resolved items** — requirements already addressed by merged PRs or subsequent comments.
     - **Outdated items** — requests superseded by newer comments or decisions.
   - Use only the **current requirements** (combined with the issue description) as input for the next step.

### Context preparation (mandatory pre-flight)

Run `@skills/prepare-issue-context/SKILL.md` with `MODE=resolve-issue` and the same issue reference. It extracts every scenario from the assignment's *Jak otestovat* / acceptance criteria, maps each scenario to a concrete code path, seeds the development database with the records the scenarios depend on, and runs a one-shot reproduction. Stop immediately and surface the gap list to the user when the skill returns `blocked: <count> open gap(s)` — do **not** continue into problem analysis with incomplete context, because an implementing agent forced to guess at missing data is the most common source of hallucinated fixes. The scenario table the skill produces is the canonical input for the next step.

### Problem analysis

6. **Gate — assignment specificity.** The pre-flight in step 5 already guarantees every scenario is mapped to a concrete code path; this gate only decides how clear the *requirements* are. Pick **specific** or **general** based on the scenario table and the current requirements from comment analysis:
   - **Specific** — expected behavior is unambiguous for every scenario, and the root cause (for bugs) or target behavior (for features) is explicitly stated in the assignment or current requirements. **Skip** `@skills/analyze-problem/SKILL.md` and use the scenario table together with the current requirements as the input for step 7.
   - **General** — requirements are vague, acceptance criteria are missing or open-ended, or the root cause is not identified. When in doubt, treat the assignment as general. **Run** `@skills/analyze-problem/SKILL.md` using the issue description, the scenario table, current requirements, and any available context, and use its output as the input for step 7.
7. Review the input from step 6 and split the identified items into three groups:
   - **In scope** — items that directly match the issue requirements. These will be implemented.
   - **Pre-existing issues** — bugs, project-rule violations, or security vulnerabilities already present in the affected files before this task (see *Pre-existing issue handling* below). These will be fixed in **separate commits** inside the same PR.
   - **Out of scope (deferred)** — valid findings that fall outside the current issue **and** do not qualify as pre-existing issues to fix now (e.g. enhancements, refactors, future features). These will be added to the PR summary as a `## TODO` list for future tasks.

### Read, Map & Verify before implementing (mandatory pre-flight)

Reading, mapping, and verifying come first; implementing comes last. This pre-flight is **blocking** — do not add or modify a single line of production code until all three steps pass, and never act on an assumption you have not confirmed by reading the code. (The context preparation above maps scenarios to code paths; this gate grounds the actual implementation in the real files you are about to change.)

1. **Read** — open and read the actual files you will change and the code they depend on (callers, called methods, related tests, configuration, migrations). Confirm what the code does by reading it, not by guessing from names or the issue description.
2. **Map** — map the change's blast radius: every call site, caller, data-flow path, and existing test that the in-scope change touches, plus the conventions, helpers, Services, and Actions already in the codebase to reuse instead of reinventing.
3. **Verify** — check your assumptions against the real code and its observed behavior (for bugs, reproduce the failure; for features, confirm the integration points exist as assumed). If reading and mapping contradict the issue framing or the scenario table, stop and surface the discrepancy instead of implementing on a wrong premise.

Only after Read, Map, and Verify are complete may phase planning and implementation begin.

### Phase planning (commit plan)

Before writing any code, decide how the in-scope work will be split into commits within the PR.

1. **Detect existing phases** in the issue description and the kept comments. Phase markers include explicit headings such as `Phase 1`, numbered milestones, ordered acceptance-criteria blocks, or a step-by-step plan written by the reporter.
2. **If phases exist:** treat each phase as exactly **one commit**. Keep the original phase order as commit order. Do not merge, reorder, or re-scope phases.
3. **If no phases exist but the assignment is long or covers multiple distinct concerns:** propose a phased breakdown — each phase must be independently reviewable and yield a working state — then map **one phase per commit**.
4. **If the assignment is small and atomic:** keep it as a single commit. Do not invent artificial phases.
5. Record the planned phases as a numbered list (one line per commit, with the intended commit message in `type(scope): description` form per `@rules/git/general.mdc`) **before** starting implementation. This list is the commit plan for step 11.
6. During implementation, commit at the end of each phase. Run pre-push fixers and tests on the changes belonging to that phase before moving on.

### Pre-existing issue handling

While reading and modifying the files required for the in-scope work, you may encounter problems that are **unrelated to the current assignment** but were already present in those files. The following categories qualify as pre-existing issues that must be fixed in this PR:

- **Bugs** — incorrect logic, broken edge cases, null-dereference risks, race conditions, or runtime errors that exist before this task.
- **Project-rule violations** — code that contradicts any rule listed in this skill's *Constraints* block (`@rules/php/core-standards.mdc`, `@rules/laravel/*`, `@rules/sql/optimalize.mdc`, etc.) or any other rule under `.claude/rules/`.
- **Security vulnerabilities** — anything `@rules/security/backend.md`, `@rules/security/frontend.md`, or `@rules/security/mobile.md` would flag (injection, missing authn/authz, unsafe deserialization, sensitive-data exposure, …).

Rules:

1. **Do not silently ignore** a pre-existing issue you encountered in a file you had to read for the in-scope work — fix it in this PR.
2. **Do not expand scope** by actively scanning unrelated files for additional pre-existing issues. Limit attention to files already touched by the in-scope changes (or their direct dependencies you must read to understand the change).
3. Land each pre-existing fix in its **own separate commit** inside the same PR:
   - Use a Conventional Commits subject per `@rules/git/general.mdc`: `fix(<scope>): pre-existing — <description>` for bugs and security, `refactor(<scope>): pre-existing — <description>` for rule violations without behavior change.
   - The `pre-existing — ` prefix is mandatory so reviewers can identify these commits at a glance (e.g. `fix(user): pre-existing — null check before dispatching welcome mail`).
   - **Test coverage workflow depends on the commit type:**
     - `fix(<scope>): pre-existing — …` (bug, security) — add the regression test in the **same commit** as the fix; the test must fail before the fix lands and pass after.
     - `refactor(<scope>): pre-existing — …` (project-rule violation, behavior-preserving) — apply `@rules/refactoring/general.mdc` *Test Coverage Contract*: when the target lines are below 100% coverage, author a dedicated `test(<scope>): cover <area> before pre-existing refactor` commit **before** the refactor commit, and do **not** modify pre-existing tests inside the refactor commit (mechanical renames forced by the refactor itself stay exempt and must be flagged in the commit body).
   - Either way, pre-existing fixes follow the same 100% coverage rule on changed lines as in-scope changes (step 16).
4. Order pre-existing fix commits **before** the in-scope commits in the commit plan from the previous section, so they form an independently revertable base. Update the recorded commit plan to include them before starting implementation.
5. If a pre-existing issue is **non-trivial** (would significantly expand the PR, requires architectural decisions, or affects shared infrastructure beyond the touched files), do **not** fix it inline. Move it to the *Out of scope (deferred)* group from step 7 and surface it under the PR's `## TODO` section with a one-line reason for deferral.

### If bug
8. Reproduce the issue if possible.
9. Write or update a test capturing the failure.
10. Confirm the failure before applying the fix.

### If feature
8. Design a minimal implementation aligned with project architecture.

### Continue
11. Implement the solution for all **in-scope** items identified in step 7.
12. Ensure no sensitive data is exposed in error/validation messages. Apply `@rules/security/backend.md` *Safe Validation & Error Messages* (and `@rules/security/frontend.md` / `@rules/security/mobile.md` for the equivalent client surfaces) to every user-facing string the change touches, **including every locale shipped by the project** — auth, password-reset, sign-up, and account-lookup flows must return one generic message with one response shape so the wording cannot be used for identity enumeration, authorization-denied responses must not confirm the resource exists, and no stack traces / file paths / framework versions / DB or queue / cache identifiers / verbatim attacker input reach the response body.
    Apply `@rules/security/backend.md` *Malicious Code & Supply-Chain Indicators* (issue #549) to every line the change adds in application code, shell / deploy / CI scripts, and installer hooks — never introduce a silent `curl -s … | sh`, disabled TLS validation (`curl -k`, `CURLOPT_SSL_VERIFYPEER => false`, `NODE_TLS_REJECT_UNAUTHORIZED=0`), suppressed error output on a security-relevant command, or a hidden `/tmp` file paired with a detached background process; route downloads through allow-listed checksum-verified HTTPS and background work through the project's queue / scheduler.
13. If the implementation introduced new database migrations, run them (`php artisan migrate` for Laravel projects, or the project-specific equivalent) before executing the affected tests or creating the pull request.
14. Run tests for affected areas and confirm correctness.
15. Add or update tests to cover the new or fixed behavior.
16. Verify 100% code coverage for all changed or added code paths — if coverage tooling exists, run it and confirm the result before proceeding.

## Pre-push quality gates

Follow the workflow defined in `references/quality-gates.md`.

## Code quality and review loop

After implementation and pre-push quality gates pass, and **before creating the pull request**, run the review loop on the local changes:

1. **Run the review inline.** Invoke `@skills/code-review/SKILL.md` directly in this skill's context, passing the current branch / diff context plus the instruction "run `@skills/code-review/SKILL.md` on the local changes and return the Critical / Moderate / Minor findings with their reproducer fields (Faulty Example, Expected Behavior, Test Hint, Suggested Fix)". Do not dispatch the review as a subagent — run it sequentially in the current context.
2. If **Critical** or **Moderate** findings exist:
   - Apply the **Suggested Fix** snippet from each finding directly to the working tree
   - Add or update a reproducer test for each finding using its **Faulty Example**, **Expected Behavior**, and **Test Hint**
   - Re-run the pre-push quality gates on touched files
   - Repeat from step 1
3. Iterate until no **Critical** or **Moderate** findings remain

PR-comment processing via `@skills/process-code-review/SKILL.md` remains the path used **after** a PR exists; it is not part of this pre-PR loop because it requires an open PR to operate on.

## Testing

After the code review loop passes clean, and **still before creating the pull request**, validate the change:

1. **Run the security review inline.** Invoke `@skills/security-review/SKILL.md` directly in this skill's context, passing the current diff context plus the instruction "run `@skills/security-review/SKILL.md` on the local changes and return the Critical / Moderate / Minor findings". Do not dispatch the review as a subagent — run it sequentially in the current context.

Resolve any **Critical** or **Moderate** finding from the security review before continuing. If a finding requires code changes, re-run the **Code quality and review loop** to re-validate.

`@skills/test-like-human/SKILL.md` is **not** part of this gate. It runs **on demand only** (via `/test-like-human` or an explicit follow-up after the PR is open); resolve-issue must never auto-chain into it.

## Pull request

Once review and testing are clean:

- Create a branch and commit changes following `@rules/git/general.mdc`
- Create a pull request with:
  - clear description of the change
  - reference to the original issue
  - testing instructions
  - **Summary** — concise overview of what changed and why
  - **Pre-existing fixes** — if any pre-existing issues were fixed per *Pre-existing issue handling*, list each fix commit under a `## Pre-existing fixes` section with a one-line rationale so reviewers can review them independently of the assignment
  - **TODO list** — if any **out-of-scope (deferred)** items were identified in step 7 (or non-trivial pre-existing issues were deferred), include them under a `## TODO` section as a checklist of potential follow-up tasks

## Final report

Reporting is split by audience and destination:

### Technical report → codebase tracker (GitHub PR)

Post the technical report as a comment on the GitHub PR, since that is where the codebase and testing state live. It must contain:

- **Code review summary** — outcome of `@skills/code-review/SKILL.md` (findings addressed during the loop and the final clean state)
- **Security review summary** — outcome of `@skills/security-review/SKILL.md`

### Non-technical report → original task tracker

Post the non-technical report on the issue tracker where the task with the assignment was created (the original tracker, regardless of where the PR lives):

- **GitHub** (task filed as a GitHub issue): post as a comment on the original issue
- **JIRA** (task filed in JIRA): post as a JIRA comment formatted with JIRA Wiki Markup per `@rules/jira/general.mdc` (no Markdown headings, fenced code blocks, or tables)
- **Bugsnag** (task originated from a Bugsnag error): post the non-technical report as a comment directly on the Bugsnag error via `skills/code-review-bugsnag/scripts/upsert-comment.sh <URL|TRIPLE> -` (requires `BUGSNAG_TOKEN`; falls back to a Bugsnag MCP server when the script is unavailable). Also mirror it as a comment on the linked GitHub issue from `linkedIssues[]` when one exists.

The non-technical report must be understandable by non-technical testers and product managers and contain:

- **What changed:** a brief, plain-language summary of the fix or feature
- **How to test:** step-by-step instructions a tester can follow to verify the change works correctly
- **Risk areas and edge cases:** specific scenarios the tester should focus on to catch potential regressions or unexpected behavior
- **Pre-existing fixes also covered by this PR (when any):** plain-language one-line summary per pre-existing fix commit produced by *Pre-existing issue handling*, plus a one-line "what to re-verify" hint per fix so the tester knows the additional regression surface to validate. Omit the bullet entirely when no pre-existing fix landed.

### GitHub-specific follow-up
- If the original repository uses a `ready for review` (or equivalent) label, apply it to the source issue once the PR is open to signal it is ready for reviewers. Skip this step when the project does not use such labels.

### JIRA-specific follow-up
- Link the created PR back to the JIRA issue.
- Once the PR is open, move the issue to the project's Code Review status by running `skills/code-review-jira/scripts/transition-to-code-review.sh <KEY|URL>`. This is the single sanctioned status transition (per `@rules/jira/general.mdc`); the helper refuses any non-review target and only reports success after confirming the issue actually reached the review column. When it exits 5 — the review-status name differs for this project and could not be auto-resolved — discover the real name via the JIRA MCP server's available-transitions and re-run with it as the `STATUS` argument, or ask a human. Perform no other status transition; all others remain human-only.

### Bugsnag-specific follow-up
- The created PR is linked through the Bugsnag error's existing GitHub integration (`linkedIssues[]`); do not invent a second link.
- Do not change the Bugsnag error status (fixed / ignored / snoozed) automatically — like JIRA transitions, marking an error fixed is left to a human after the fix is verified in production.

## References

- references/source-detection.md
- references/quality-gates.md

## Done when
- The issue is fully addressed
- Behavior is correct and stable
- Tests cover affected logic with 100% coverage and pass
- Pre-push fixers and checkers ran clean on all changed files
- No sensitive data is exposed
- Code review loop passed with no Critical or Moderate findings **before the PR was created**
- Security review completed **before the PR was created**
- A clean pull request is created with a summary
- Technical report posted on the GitHub PR
- Non-technical report posted on the original issue tracker
- For JIRA issues: PR is linked back and a summary comment is posted

## Output Humanization
- Use [blader/humanizer](https://github.com/blader/humanizer) for all skill outputs to keep the text natural and human-friendly.
