---
name: code-review
description: Use when senior PHP code review focused on architecture, business
  logic, and risk detection. Read-only.
---

# Code Review

## Purpose
Perform structured code review focused on:
- correctness
- architecture
- regression risks
- security and performance issues

---

## Constraints
- Apply @rules/php/core-standards.mdc
- Apply @rules/api/general.mdc — when the diff adds or modifies an HTTP API surface (routes, controllers / `__invoke` request handlers, API Resources / DTOs serialized into responses, FormRequests, status-code / `response()` / `abort()` calls, `Idempotency-Key` handling), walk it against the API contract pillars. The dedicated walk lives in `@skills/api-review/SKILL.md` (Specialized Reviews → Always run); severities follow that rule's CR Severity Rules section.
- Apply @rules/code-review/general.mdc
- Apply @rules/refactoring/general.mdc — use the shared refactoring definition when assessing refactoring changes or when proposing refactoring; reject big-bang rewrites and prefer incremental migration.
- Apply @rules/php/dependency-selection.mdc — when the PR diff adds a new `require` / `require-dev` entry to `composer.json`, walk the Activity + Compatibility gates from that rule against the PR description / commit body. A missing selection note is a **Critical** finding; an adopted archived / abandoned / branch-pinned package is a **Critical** finding on the spot; a single-maintainer adoption without bus-factor flag is a **Moderate** finding.
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Output findings only (no praise)
- **Read-only skill** — never modify code, never stage / commit / push changes, and never run any git write operation (`git add`, `git commit`, `git push`, `git reset`, `git checkout -- …`, etc.). Checking out the relevant branch and `git pull` to read the latest code are **required** (the mandatory Branch checkout gate below); mutating the working tree or pushing to the remote is not. Output is the review markdown only.
- Apply @rules/reports/general.mdc — the review markdown handed to `code-review-github` / `code-review-jira` for publishing on the **GitHub PR** stays in canonical English per the rule's *Exception — technical CR findings on the GitHub PR* (severity labels, structured field labels, rule references, and code identifiers are all in English). The non-technical mirror that the wrappers delegate to `@skills/pr-summary/SKILL.md` follows the language of the source assignment — that is the wrapper's responsibility, not this skill's.
- Do not duplicate findings the project's fixers already auto-correct (Pint, PHPCS, Rector — pure whitespace, import ordering, unused-use, single-line vs multi-line argument splits). Those are caught by the build. **Do** flag every rule violation a fixer does not cover — architectural breaches, structural rules, missing return types, untyped DTO boundaries, naming bound to a domain rule, testing-pattern violations, etc.

---

## Execution

- **Branch checkout gate (mandatory, always).** Before any analysis step, check out the branch that contains the changes and pull the latest commits — `git fetch`, `git checkout <branch>`, and `git pull` when the branch tracks a remote (skip the pull for a local-only branch that has no upstream, e.g. the read-only fallback review of a branch that maps to no PR) — so the review always runs against the **actual current codebase on disk (the checked-out working tree)**, never against a remote diff in isolation. Confirm local `HEAD` matches the change branch's head commit. If the branch cannot be checked out (missing ref, detached `HEAD`, or local changes that would be overwritten), **stop and report it** instead of reviewing from a diff. Every subsequent step reads the checked-out files so findings reflect the real state of the code.
- Identify changes vs main branch.
- Deduplicate previous findings.

### Cross-run history

The CR wrappers publish the review through an **always-new comment per CR run** (both GitHub and JIRA — see `@skills/code-review-github/SKILL.md` and `@skills/code-review-jira/SKILL.md`). Every run POSTs a fresh comment so the chronological sequence of comments is the audit trail; history never lives in a tracker's edit history. Do not load prior CR findings from PR comments and do not author a `Previous CR Status` section in the output — the always-new-comment convention makes it redundant.

### Issue Context Analysis

Before reviewing code, load and analyze the full issue context:

1. Load the complete issue or task (description, all comments, and attachments) from the linked tracker (GitHub, JIRA, Bugsnag). For JIRA issues, call `skills/code-review-jira/scripts/load-issue.sh <KEY|URL>` and read all fields off the resulting JSON document — never call `acli` directly. Fall back to the JIRA MCP server only when the script is unavailable or for data outside its scope (changelog, available transitions, friendly custom-field names). For Bugsnag errors, call `skills/code-review-bugsnag/scripts/load-issue.sh <URL|TRIPLE>` (requires `BUGSNAG_TOKEN`) and read the error class, message, `context`, `latestEvent.stacktrace`, `comments[]`, and `linkedIssues[]` off the JSON — never call `api.bugsnag.com` directly. Fall back to a Bugsnag MCP server only when the script is unavailable.
2. Extract from the issue:
   - **Requirements and acceptance criteria** — what the code must do
   - **Expected behavior** — how the feature or fix should work
   - **Edge cases and constraints** — mentioned by the reporter or in comments
   - **Test data** — any sample inputs, payloads, or scenarios provided in the issue
3. Use this context to evaluate whether the implementation fully satisfies the issue — not just whether the code is technically correct.
4. If the issue contains test data or test scenarios, verify they are covered by existing or new tests. Flag missing test coverage as a finding.

### Assignment Conformance Gate (mandatory)

Every CR run must explicitly verify **both directions** of the relationship between the diff and the linked assignment, then surface a single conformance verdict. When no tracker is linked (`closingIssues[]` empty for a GitHub PR, no JIRA / Bugsnag reference), skip the gate and state `assignment conformance: no linked issue` on the summary line.

1. **Requirements → changes (completeness).** Every requirement, acceptance criterion, expected behavior, edge case, and sample test scenario extracted under **Issue Context Analysis** must map to a concrete change in the diff that implements it — including the **testing logic**: tests added or modified by the diff must themselves assert the correct, assignment-required behavior and must not assert a stale, incorrect, or reduced version of a requirement. Verify that test assertions (expected values, exception types, event payloads, response shapes) are consistent with the current assignment requirements — not with an older version of the spec. This direction is already executed by the always-run `@skills/assignment-compliance-check/SKILL.md` and `@skills/analyze-problem/SKILL.md` (assignment-conformance scope) in **Specialized Reviews**; **do not re-derive or duplicate their findings here** — consume their result. Any unmet requirement (in production code or in test logic) is already a **Critical** finding raised there.
2. **Changes → requirements (traceability, no scope creep).** This is the direction those two skills do **not** cover and the gate adds: walk **every changed code block in the diff** (added or modified production lines, per file) and trace each one back to a specific requirement, acceptance criterion, or expected behavior from the assignment. Classify each block:
   - **In scope** — directly implements a stated requirement. Cite the requirement.
   - **Allowed support** — does not implement a requirement on its own but is necessary to deliver one: tests for the changed behavior, a refactor/extraction the requirement forces, a migration/config the requirement needs, a fixup of code the change touches. Cite the requirement it supports.
   - **Out of scope (finding)** — traces to **no** requirement and is not allowed support: an unrequested feature, an opportunistic refactor of untouched concerns, a drive-by behavior change, a config/dependency change the assignment never asked for. Raise one finding per out-of-scope block: `file:line`, the change in one sentence, and "no assignment requirement traces to this change". Severity: **Moderate** by default; escalate to **Critical** when the untraceable change alters observable behavior, touches a security / payment / auth surface, or adds a dependency. The **Suggested Fix** is to remove the change from this PR and, when it has independent value, move it to its own issue / PR. Do not duplicate a finding **Simplicity First** already raised for the same block — keep it here as the assignment-traceability finding and cite Simplicity First instead of emitting a second entry. The two lenses are not the same: Simplicity First owns *unrequested complexity*, while this gate owns *traceability* — a change can be perfectly simple yet still trace to no requirement (e.g. a one-line drive-by rename), and that case is this gate's to raise, not Simplicity First's.
3. **Verdict.** Record an explicit one-line verdict on the review summary line: `assignment conformance: conformant` (every requirement implemented **and** every change traces to the assignment), or `assignment conformance: N gap(s)`. The verdict is **computed at Output assembly** — after the Specialized Reviews have produced their results and after the **Critical Findings Verification (issue #537)** step has dropped any refuted Criticals — so it counts only gaps that survive into the published review and never contradicts the Counts line. `N` is the count of Critical assignment gaps (unmet requirements from step 1) plus out-of-scope findings (step 2), regardless of which surface publishes each one — step-1 gaps may appear on the linked-tracker compliance block while step-2 findings sit in the PR comment's severity buckets, but both count toward `N`. The verdict is always rendered on the summary line so a reader sees the conformance result without scanning the body; the individual findings live in their normal severity buckets.

### Third-Party API & Service Analysis

Run this section only when the diff integrates with, modifies, or depends on a third-party API or external service (HTTP clients, vendor SDK calls, webhooks, OAuth flows, payload schemas, queue/event consumers backed by external systems).

1. Identify every affected API or service from the diff and list the concrete endpoints, SDK methods, webhook events, or message contracts that changed.
2. Locate the official public reference for each one — vendor documentation, OpenAPI/Swagger spec, SDK reference, or webhook contract. Prefer URLs cited in the issue or PR; otherwise look up the vendor's current published documentation for the version in use.
3. Compare the implementation against the public contract:
   - endpoints, HTTP methods, and required vs optional parameters
   - request and response schemas, status codes, and error envelopes
   - authentication, scopes, rate limits, idempotency keys, and retry semantics
   - pagination, filtering, sorting, webhook signatures, and timeouts
4. Cross-check the implementation against the issue assignment — verify the chosen endpoints, parameters, and behaviors satisfy what the issue actually asked for. Flag any divergence (missing endpoint, wrong verb, ignored field, fabricated parameter) as a finding.
5. Confirm coverage of every API use case that is in scope for the issue — documented filters, status branches, error states, and edge inputs the issue explicitly or implicitly requires. Missing in-scope use cases are findings. Do not propose adopting API features that current scope does not require (YAGNI per `@rules/php/core-standards.mdc`); only when the diff exposes an out-of-scope structural shortcoming in how the project consumes the API (e.g. missing webhook signature verification across other consumers) raise it under **Refactoring Proposals**.
6. If the public reference cannot be located, accessed, or matched to the version in use, raise this as a **Moderate** finding instead of silently assuming the contract.

### Core Analysis
- Regression risk (shared logic, dependencies)
- Architecture and design quality
- Business logic correctness
- Missing or incorrect behavior
- Type safety and error handling
- **Full Core Analysis walk-through (canonical detail in `@rules/code-review/general.mdc` *Core Analysis Walk-through*).** Apply every bullet there to the diff and raise one finding per violation at the severity it declares: Reuse of existing logic, Action scope, Speculative interfaces, **Simplicity First**, method-parameter-count (>4 → DTO), public-method raw-array-vs-DTO, new static-analysis / linter suppression, **Strict rule compliance (mandatory walk-through)**, **Architecture conformance (Laravel)** (issue #530), **Test organization (issue #528)**, per-row DB operations in loops, variable ordering / lazy evaluation, object caching, new storage reuse analysis, SQL index reuse / performance non-regression, refactoring quality + test-coverage contract, data-validation encapsulation, pass-through Action, repository scope, inline Eloquent / read-write layer separation, Action-returns-HTTP-response, inline data mapping → Data Builder, inline validation guards / `throw_if` / `throw_unless` / enum-mode `match()` → Data Validator, only-Laravel-and-arch-layers class inventory, **Request → DTO transformation belongs in the FormRequest, not the controller**, Data Modification (DRY), and **Entry-point error handling for known failures (Laravel)**.
### Highest-Priority Fast Track

Apply this subsection only when the source issue is flagged as **highest priority**, so the bug fix can deploy as fast as possible without sacrificing the Critical / Moderate gate.

1. **Detect highest priority** from the issue context already loaded under **Issue Context Analysis**:
   - **GitHub:** any label whose name matches (case-insensitively) `priority: highest`, `priority/highest`, `priority-highest`, `p0`, `urgent`, or `blocker`.
   - **JIRA:** the native `priority` field equals `Highest` or `Blocker`.
   - **Bugsnag:** the linked GitHub issue carries one of the GitHub labels above.
   If no signal matches, skip the rest of this subsection and run the review normally.
2. **Narrow the review scope** to whatever directly affects the bug fix and its safe deployment. Out-of-scope improvements that the diff merely happens to sit near must be moved to **Refactoring Proposals** as follow-up items, never blockers.
3. **Keep the resolution gate at Critical and Moderate.** No widening, no narrowing — those two severities still block the merge, exactly as in the default flow. State this explicitly in the review header so the caller does not have to infer it.
4. **Demote non-blocking sections to follow-up only.** Still emit them so nothing is lost, but mark each entry as *follow-up; does not block merge*:
   - **Minor** findings (naming, dead code, wording nits without a binding rule).
   - **Refactoring & Tech Debt (DRY) Analysis** entries that propose changes beyond the literal bug fix.
   - **Refactoring Proposals** drafted for separate issues.
   Critical and Moderate findings, the **Strict rule compliance** walk-through, the **Coverage gate**, the **Database Analysis** section, and every **Specialized Review** that the diff triggers stay mandatory and blocking — fast-track never skips them.
5. **Record the fast-track decision** in the review output: the matched signal (label name or JIRA priority value), the deferred sections, and a one-line reminder that the gate remained Critical + Moderate.

### Named Arguments Review
- Would positional arguments be ambiguous?
- Are there boolean, null, array, or repeated scalar values?
- Would a DTO or value object be a better design?
- Is this a public API where parameter names must remain stable?
- Are arguments still listed in the original method signature order?

### Specialized Reviews

- Always run:
    - @skills/prepare-issue-context/SKILL.md with `MODE=cr` **as a pre-flight before any other specialized review** — audits whether the dev database currently holds the fixture data the assignment scenarios need, and surfaces every scenario the diff *should* cover but the codebase / dev DB cannot reproduce. The CR uses its gap report to ground the rest of the review in real data; an empty gap report means later findings (assignment compliance, security, refactoring) are made against scenarios that genuinely exist, not against guesses. Surface the gap count in the PR comment summary line and treat any **behavioral gap** the skill reports as a **Critical** CR finding (the diff cannot satisfy a scenario the codebase does not support).
    - @skills/assignment-compliance-check/SKILL.md — verifies the PR implementation satisfies the business requirements stated in the linked issue / task. The skill **does not publish anywhere itself** — it returns either the assembled `## Assignment Compliance` markdown block (only when at least one Critical gap exists), the status `no critical gaps — assignment compliance block omitted` (when the implementation satisfies every stated requirement), or the status `no linked issue — assignment compliance skipped` (when no linked tracker exists). The CR wrapper passes the returned block as an embedded block to `@skills/pr-summary/SKILL.md` **only when a block is returned** so each linked tracker (GitHub issue or JIRA ticket) receives **one consolidated comment** per CR run (per issue #498); on either skip status the wrapper embeds nothing and surfaces the status on the PR comment summary line. **Do not embed** the block into the PR comment — that comment carries technical findings only.
    - @skills/analyze-problem/SKILL.md — **always run, scoped to assignment conformance.** Run it **inline in this skill's context** (do not dispatch as a subagent) against the issue context already loaded under **Issue Context Analysis** (requirements, acceptance criteria, expected behavior, edge cases, test data) and the PR diff, to answer one question: does the implemented code actually deliver what the assignment asked for? Walk the framework's steps 1–3 only — **Context extraction**, **Problem statement** (reframed as *the assignment*: what the code must do), and **Expected vs actual behavior** (the assignment-required behavior vs what the diff actually implements). Treat every required behavior the diff fails to deliver — a missing behavior, a wrong behavior, an acceptance criterion left unimplemented, a stated edge case or sample test scenario not handled — as a **Critical** finding (the code does not satisfy the assignment) carrying the standard reproducer fields (Faulty Example / Expected Behavior / Test Hint / Suggested Fix). **Read-only invocation:** within the CR the skill runs analysis-only — it returns the gap findings as markdown and must **not** write the *Pre-Implementation Research & Plan* artifact, modify code, or run any git write (the CR is read-only); skip the plan-artifact step entirely. This is **distinct from** the per-Critical-finding **Critical Findings Verification (issue #537)** below, which uses the same skill to *confirm* each Critical finding one by one; here the skill runs **once over the whole diff** to *detect* assignment gaps up front. Do not duplicate a gap that `@skills/assignment-compliance-check/SKILL.md` already raises in its `## Assignment Compliance` block — when both would surface the same gap, keep the technical **Critical** finding here (with reproducer fields, folded into the standard severity buckets) and let the compliance block carry only the non-technical mirror.
    - @skills/security-review/SKILL.md
    - @skills/api-review/SKILL.md — API design contract lens (`@rules/api/general.mdc`). The skill self-scopes: when the diff touches no HTTP API surface (routes, controllers / `__invoke` request handlers, API Resources / DTOs in responses, FormRequests, status-code handling, `Idempotency-Key` logic) it returns no findings. When it does, fold the Critical / Moderate / Minor findings (with their reproducer fields) into the standard severity buckets — do not duplicate a finding the **Strict rule compliance** walk already raised for `@rules/api/general.mdc`.
    - @skills/class-refactoring/SKILL.md **with `MODE=cr`** — read-only refactoring lens scoped to the PR diff. Use it to surface concrete tech-debt-reducing changes (DRY duplication, single-responsibility breaches, oversized methods) that apply to lines actually touched by the PR. `MODE=cr` guarantees the lens never modifies code, writes tests, commits, runs fixers, or chains another review — it returns proposals only. Do not propose changes that fall outside the diff.

- Run conditionally:
    - **Diff is a refactoring (behavior-preserving structural change per `@rules/refactoring/general.mdc`) → run the full refactoring skill set read-only.** Detect this when the PR restructures existing code without adding a feature or changing observable behavior (extracted methods / classes, moved orchestration, renamed-for-clarity, dedup, layer reshuffles). When it fires, additionally invoke `@skills/refactor-entry-point-to-action/SKILL.md` **with `MODE=cr`** (and run the `class-refactoring` lens above at full depth) to surface the entry-point → Action proposals. **Both skills run read-only — no code changes, no commits, no fixers, no review chaining — `MODE=cr` enforces this.** Fold their output into the **Refactoring (DRY / tech debt)** section (in-scope items) and **Refactoring proposals** section (out-of-scope items) of the review; this skill never modifies code.
    - **Database operations detected in the diff → `@skills/mysql-problem-solver/SKILL.md` is mandatory.** Trigger this skill whenever the diff touches any of: raw SQL strings, Eloquent / query-builder calls (`DB::`, `->where(`, `->join(`, `->whereHas(`, `->withCount(`, `->orderBy(`, `->groupBy(`, `->chunk(`, `->cursor(`, `paginate(`, `simplePaginate(`), Eloquent relationship definitions, `with(` / `load(` eager loads, model scopes, ModelManager / Repository methods, database migrations (`Schema::`, `up()` / `down()`), seeders, factories that materialise rows, DynamoDB / NoSQL access. Pass the diff scope to `mysql-problem-solver` and capture its findings — they **must** appear in the published CR review under the dedicated `## Database Analysis` section described in **Output Rules**, never silently absorbed into the generic Critical / Moderate / Minor buckets.
    - Shared state / concurrency → @skills/race-condition-review/SKILL.md
    - I/O or external calls → I/O review

### Refactoring & Tech Debt (DRY) Analysis

Run this section over the PR diff only — never over untouched code.

The diff-scoped steps (per-block evaluation against `@skills/class-refactoring/SKILL.md` with `MODE=cr`, Livewire / Blade layout splitting, `->when()` conditional query composition with the byte-for-byte semantics-preserving templates, and the in-scope vs. **Refactoring Proposals** routing) live in `@rules/code-review/general.mdc` *Refactoring & Tech Debt (DRY) Analysis — diff-scoped detail*. Apply them to the changed lines only — never over untouched code.

### Validation
- Verify acceptance criteria
- **Acceptance-criteria use-case coverage** and the full **Coverage gate** (changed-files-only scope, CI-result reuse with the staleness guard, coverage-tooling discovery, short-by-default coverage reporting, and the missing-test-scenario walk) live in `@rules/code-review/general.mdc` *Validation & Coverage Gate*. Every acceptance criterion without a dedicated use-case test, and every uncovered changed line, is a **Critical** finding.

### Critical Findings Verification (issue #537)

Run this step **after every preceding analysis step has produced its findings** and **before** the Output assembly. Walk every **Critical** finding through `@skills/analyze-problem/SKILL.md` to confirm it reflects a real problem before it blocks the PR; the binary keep / drop procedure (Confirmed → keep verbatim, Refuted → drop entirely, never silently downgrade, Moderate / Minor exempt) is defined in `@rules/code-review/general.mdc` *Critical Findings Verification (issue #537) — procedure*.

---

## Output Rules

- Output only findings
- No praise, no summaries of what was checked
- **Omit empty sections entirely.** Only the header block (Status / Counts / Last updated / tracker-status line) and the final `Summary` line are always rendered. The `Summary` line always carries the **assignment conformance verdict** from the **Assignment Conformance Gate** (`assignment conformance: conformant` / `N gap(s)` / `no linked issue`) so the reader sees whether the changes match the assignment without scanning the body. The `Coverage:` header line, the `## Coverage` section, and the `coverage …` slot in the summary line are all conditional — render them **only** when the coverage gate produced something to report (uncovered changed lines or unavailable / non-runnable tooling, both Critical findings). When every changed line is at 100% coverage and the tool ran successfully, drop all three coverage surfaces; the Counts line is the clean signal. Every other section — `Findings` (including each severity sub-heading), `Refactoring (DRY / tech debt)`, `Refactoring proposals`, `Database Analysis`, and any specialized-review sub-section — appears **only when it has at least one item**. Never emit `None.` / `Not applicable.` / `n/a` / `100%` placeholders for empty sections or omitted coverage surfaces; drop the whole heading and body instead. **History across CR runs** is preserved by the tracker's edit history on the upserted comment — never re-create a `Previous CR Status` section in the body.
- **`## Architecture` section (issue #530).** On Laravel projects the architecture walk-through described in Core Analysis runs on every CR run, but the `## Architecture` heading is rendered **only when the walk produces at least one finding**. When findings exist, render them under the heading with the standard Critical / Moderate / Minor severity grouping and the reproducer fields. When the walk produces zero findings, omit the heading entirely — never render a `walked, 0 findings` status line, a `clean` placeholder, or any other "the check ran" confirmation. The principle is the same as for every other section: report only items that still need action; an empty section is dropped. On non-Laravel projects (`laravel/framework` not in `composer.json` `require`), the `## Architecture` section is omitted entirely — the section is Laravel-only by design.
- Use severity levels:
    - Critical
    - Moderate
    - Minor
- Group findings by severity
- Each finding must include:
    - location
    - risk/impact
    - concrete fix
- Each **Critical** and **Moderate** finding must additionally include:
    - **Faulty Example** — minimal code snippet or input payload that reproduces the issue (redact secrets/PII)
    - **Expected Behavior** — single assertable statement (return value, exception, persisted state, emitted event)
    - **Test Hint** — one sentence pointing at the test layer (unit, integration, feature) and entry point
    - **Suggested Fix** — minimal corrected code snippet that resolves the finding. Must comply with `@rules/php/core-standards.mdc` and, for Laravel projects, `@rules/laravel/architecture.mdc`. Use `n/a — <reason>` only when a snippet adds no value over the one-line Fix description (e.g. naming-only changes, dead-code removal, pointers to an existing helper whose name already says enough).
- These four fields exist so `@skills/process-code-review/SKILL.md` can convert each finding into a reproducer test and apply the fix without re-deriving context.
- Minor findings may omit these fields when no behavior change is implied (naming, dead code, etc.).
- This skill is read-only and does not publish anywhere itself. The wrapper skills that consume its output (`@skills/code-review-github/SKILL.md`, `@skills/code-review-jira/SKILL.md`) **must** delegate the **single consolidated comment on every linked issue** in the originating tracker (GitHub issue, JIRA ticket, or both) to `@skills/pr-summary/SKILL.md` — the CR wrappers never author their own non-technical template. `pr-summary` produces a uniform *"Summary of changes + How to test"* comment understandable by non-technical project managers, rendered as GitHub Markdown for GitHub issues and JIRA Wiki Markup for JIRA tickets per `@rules/jira/general.mdc`. When `@skills/assignment-compliance-check/SKILL.md` returns a markdown block (i.e. at least one Critical gap was detected), the CR wrapper passes it as an embedded block to `pr-summary`, which appends it after `How to test` so the linked-tracker audience reads exactly **one comment per CR run** (per issue #498). When the compliance check returns a skip status (clean or no linked tracker), the wrapper embeds nothing and the consolidated comment carries only the change summary — clean compliance is reported by the absence of the block. Technical findings still go directly on the PR comment.
- **Security, translation, and test-isolation output walks** — apply the per-string / per-diff walks defined in `@rules/code-review/general.mdc` *Output Rules — Security & Translation Walks* and raise one finding per match: **Safe validation & error texts (issue #540)**, **Malicious code & supply-chain indicators (issue #549)**, **Malicious file upload content (issue #680)**, **Translation completeness**, **Test isolation — no real HTTP, no real system processes**, and the **Database Analysis section** rendering rule. Severities are declared there.
- **Default severity for rule violations:** every unexcused violation of an Apply'd rule on a line touched by the diff defaults to the severity declared in that rule file's CR Severity Rules subsection if present. Otherwise apply the **Strict rule compliance** stratification from Core Analysis: architectural / structural / required-pattern violations are **Critical**; PHP-practice violations a fixer doesn't catch are **Moderate**; naming / wording nits without a binding rule are **Minor**. Reviewers may not silently downgrade further than that stratification; if a rule's spirit is satisfied by an alternative the diff documents in code or PR description, cite that exemption explicitly in the finding instead of suppressing it.

---

## Output Format

Use the template defined in `templates/review-output.md`.
---

## Principles

- Focus on risks, not style
- Prefer impact over quantity
- Avoid duplication of findings
- Prioritize regression detection
- Be precise and actionable

---

## After Completion

- Do **not** auto-invoke `@skills/test-like-human/SKILL.md`. The user-perspective testing skill runs **on demand only** (via `/test-like-human` or an explicit follow-up); CR-track skills must never chain into it.
