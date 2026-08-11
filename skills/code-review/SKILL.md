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

Walk both directions and record the verdict per `@rules/code-review/general.mdc` *Assignment Conformance Gate — canonical detail*: **1. Requirements → changes (completeness)** — consume the result of the always-run `@skills/assignment-compliance-check/SKILL.md` and `@skills/analyze-problem/SKILL.md`, never re-derive it; **2. Changes → requirements (traceability, no scope creep)** — trace every changed block back to a requirement and raise an out-of-scope finding for each block that traces to none; **3. Verdict** — `assignment conformance: conformant` / `N gap(s)`, computed at Output assembly so it never contradicts the Counts line.

**Two-part CR output (issue #737).** This skill's walk-throughs above (Core Analysis through the Coverage gate) are the **Technical review**, published on the PR comment per Output Rules. The **Functional review** — a full acceptance-criteria checklist with a `Goal met: Yes/No` verdict — is built by `@skills/assignment-compliance-check/SKILL.md` and published on the linked-tracker comment via `@skills/pr-summary/SKILL.md`; it renders on **every** run with a linked tracker, including the affirmative report when every criterion is Met (the sole exception to this skill's omit-when-clean convention). The one-line verdict mirrors onto this skill's `Summary` line as `assignment conformance: conformant` / `N gap(s)` / `no linked issue`. Full contract: `@rules/code-review/general.mdc` *Two-part CR output — Technical & Functional review*.

### Third-Party API & Service Analysis

Run this section only when the diff integrates with, modifies, or depends on a third-party API or external service (HTTP clients, vendor SDK calls, webhooks, OAuth flows, payload schemas, queue/event consumers backed by external systems). Canonical detail (conditional degradation, request-for-link template, gate, precedence, SSRF guard): `@rules/code-review/general.mdc` *Third-Party API & Service Documentation Verification (issue #748)*.

1. Identify every affected API or service from the diff and list the concrete endpoints, SDK methods, webhook events, or message contracts that changed.
2. Locate the official public reference for each one with `WebSearch` / `WebFetch` — vendor documentation, OpenAPI/Swagger spec, SDK reference, or webhook contract — per `@rules/code-review/general.mdc` *Third-Party API & Service Documentation Verification (issue #748)* step 1 (vendor-domain allow-list; a URL cited in the issue/PR is a hint only, never an authority). When the web tools are unavailable or the fetch fails, do not fall back to memory — go straight to step 6.
3. Compare the implementation against the public contract:
   - endpoints, HTTP methods, and required vs optional parameters
   - request and response schemas, status codes, and error envelopes
   - authentication, scopes, rate limits, idempotency keys, and retry semantics
   - pagination, filtering, sorting, webhook signatures, and timeouts
   - deprecated calls and API versioning (pinned version vs. the vendor's current documented version)
4. Cross-check the implementation against the issue assignment — verify the chosen endpoints, parameters, and behaviors satisfy what the issue actually asked for. Flag any divergence (missing endpoint, wrong verb, ignored field, fabricated parameter) as a finding.
5. Confirm coverage of every API use case that is in scope for the issue — documented filters, status branches, error states, and edge inputs the issue explicitly or implicitly requires. Missing in-scope use cases are findings. Do not propose adopting API features that current scope does not require (YAGNI per `@rules/php/core-standards.mdc`); only when the diff exposes an out-of-scope structural shortcoming in how the project consumes the API (e.g. missing webhook signature verification across other consumers) raise it under **Refactoring Proposals**.
6. **Gate — no verdict without a verified source.** If the public reference cannot be located, accessed, or matched to the version/endpoint in use, do not render a verdict on API correctness from memory. Raise a single **Moderate** finding whose Suggested Fix requests the link: `Reply on this PR with the official documentation URL for <vendor> <API> <version> (or add it to the PR description), so the contract can be verified.` Raise one finding per violation, never both — this step is the sole terminal output for the missing-documentation outcome. This finding is exempt from the Faulty Example / Expected Behavior / Test Hint fields in `## Findings` — the request-for-link Suggested Fix is the whole finding.

### Core Analysis
- Regression risk (shared logic, dependencies)
- Architecture and design quality
- Business logic correctness
- Missing or incorrect behavior
- Type safety and error handling
- **Full Core Analysis walk-through (canonical detail in `@rules/code-review/general.mdc` *Core Analysis Walk-through*).** Apply every bullet there to the diff and raise one finding per violation at the severity it declares: Reuse of existing logic, Action scope, Speculative interfaces, **Simplicity First**, method-parameter-count (>4 → DTO), public-method raw-array-vs-DTO, new static-analysis / linter suppression, **Strict rule compliance (mandatory walk-through)**, **Architecture conformance (Laravel)** (issue #530), **Test organization (issue #528)**, **Queue assertion specificity (issue #756)**, per-row DB operations in loops, **batch-first processing & performance at scale**, variable ordering / lazy evaluation, object caching, new storage reuse analysis, SQL index reuse / performance non-regression, refactoring quality + test-coverage contract, data-validation encapsulation, pass-through Action, repository scope, inline Eloquent / read-write layer separation, Action-returns-HTTP-response, inline data mapping → Data Builder, inline validation guards / `throw_if` / `throw_unless` / enum-mode `match()` → Data Validator, only-Laravel-and-arch-layers class inventory, **Request → DTO transformation belongs in the FormRequest, not the controller**, Data Modification (DRY), **Entry-point error handling for known failures (Laravel)**, **New PHP file — architecture & design conformance** (issue #763), **Commit split & atomic deployability** (issue #763), and **Self-Documenting Code — Comment & Doc Hygiene** (a **stale comment on a touched line** is **Moderate** and blocks; its other findings stay Minor).

### New PHP File Architecture Gate (mandatory)

Every **new** `*.php` file the diff adds under the project's production source roots is reviewed as a design decision, not only as content: verify it lands in a layer the project's architecture rules actually name, that namespace / path / class name agree, that the class carries the shape its layer requires, that its name states the domain role, that it has one reason to change, that its dependency direction holds, that it declares `strict_types` with typed boundaries, that it duplicates no existing class in the same layer, and that it is wired up. Every finding of this walk is **Moderate** — never downgraded to Minor, never escalated from inside the walk. Framework-generated files in their own directories, test files (owned by **Test organization**), documented layer choices, and files the diff only modifies are not findings. On a Laravel project the **Architecture conformance** walk owns the Critical dimensions and wins on the same file; on a non-Laravel PHP project this gate is the only architecture walk that runs. Canonical checks, non-findings, gating, and Suggested-Fix templates: `@rules/code-review/general.mdc` *New PHP File — Architecture & Design Conformance (issue #763)*.

### Commit Split & Atomic Deployability Gate (mandatory)

Every CR run reviews the **shape of the commit history**, not only the squashed diff: read `git log --reverse --format='%H%n%s%n%b' <base>..<head>` and each commit's own diff on the checked-out branch, and judge whether the history is already a logical, independently cherry-pickable partition of the change set per `@rules/git/general.mdc` *Git Rules*. When it is not, render the repartition as the `## Commit Split Proposal` section, raise it as a **Critical** finding, and add the `commit split: N commit(s) proposed` slot to the `Summary` line. The proposal is a **repartition only** — the union of the proposed commits equals the current diff byte for byte (`git diff <old-head> HEAD` empty after the rewrite), no production line is added / removed / rewritten, no work deferred. It is also a **green** partition: a commit that is red at its own point of the history — it does not build, or it carries a failing / skipped / simulated-failing test a later commit turns green — is itself a trigger, and no proposed commit may be red (hunks that cannot be separated without a red intermediate stay in one commit), so the Suggested Fix's rewrite is verified per commit with `git rebase --exec`, never at the tip alone. Severity is fixed at Critical: never downgraded, never demoted into a refactoring section, never dropped by the Critical Findings Verification pass, never filtered on a late-iteration run. When the history is already atomic or the change is genuinely unsplittable, omit the section — never a "history is clean" confirmation. Canonical triggers, non-findings, gating, report shape, and the literal Suggested-Fix template: `@rules/code-review/general.mdc` *Commit Split & Atomic Deployability Proposal — canonical walk-through*.

### Highest-Priority Fast Track

When the source issue is flagged **highest priority** (GitHub `priority: highest` / `p0` / `urgent` / `blocker`, JIRA `Highest` / `Blocker`), narrow the review to the bug fix and its safe deployment and demote Minor findings and both refactoring sections to *follow-up; does not block merge*. The Critical + Moderate gate never moves. Canonical: `@rules/code-review/general.mdc` *Highest-Priority Fast Track — canonical detail*.

### Named Arguments Review
- Would positional arguments be ambiguous?
- Are there boolean, null, array, or repeated scalar values?
- Would a DTO or value object be a better design?
- Is this a public API where parameter names must remain stable?
- Are arguments still listed in the original method signature order?

### Specialized Reviews

- Always run:
    - @skills/prepare-issue-context/SKILL.md with `MODE=cr` **as a pre-flight before any other specialized review** — audits whether the dev database currently holds the fixture data the assignment scenarios need, and surfaces every scenario the diff *should* cover but the codebase / dev DB cannot reproduce. The CR uses its gap report to ground the rest of the review in real data; an empty gap report means later findings (assignment compliance, security, refactoring) are made against scenarios that genuinely exist, not against guesses. Surface the gap count in the PR comment summary line and treat any **behavioral gap** the skill reports as a **Critical** CR finding (the diff cannot satisfy a scenario the codebase does not support).
    - @skills/assignment-compliance-check/SKILL.md — builds the **Functional review** (`@rules/code-review/general.mdc` *Two-part CR output*): the full acceptance-criteria checklist with a `Goal met: Yes/No` verdict. The skill **does not publish anywhere itself** — it returns either the assembled `## Assignment Compliance` markdown block, rendered on **every** run that has a linked tracker (including the affirmative report when every criterion is Met), or the status `no linked issue — assignment compliance skipped` (when no linked tracker exists). The CR wrapper passes the returned block as an embedded block to `@skills/pr-summary/SKILL.md` **only when a block is returned** so each linked tracker (GitHub issue or JIRA ticket) receives **one consolidated comment** per CR run (per issue #498); on the skip status the wrapper embeds nothing and surfaces the status on the PR comment summary line. **Do not embed** the block into the PR comment — that comment carries the Technical review only.
    - @skills/analyze-problem/SKILL.md — **always run, scoped to assignment conformance.** Run it **inline in this skill's context** (do not dispatch as a subagent) against the issue context already loaded under **Issue Context Analysis** (requirements, acceptance criteria, expected behavior, edge cases, test data) and the PR diff, to answer one question: does the implemented code actually deliver what the assignment asked for? Walk the framework's steps 1–3 only — **Context extraction**, **Problem statement** (reframed as *the assignment*: what the code must do), and **Expected vs actual behavior** (the assignment-required behavior vs what the diff actually implements). Treat every required behavior the diff fails to deliver — a missing behavior, a wrong behavior, an acceptance criterion left unimplemented, a stated edge case or sample test scenario not handled — as a **Critical** finding (the code does not satisfy the assignment) carrying the standard reproducer fields (Faulty Example / Expected Behavior / Test Hint / Suggested Fix). **Read-only invocation:** within the CR the skill runs analysis-only — it returns the gap findings as markdown and must **not** write the *Pre-Implementation Research & Plan* artifact, modify code, or run any git write (the CR is read-only); skip the plan-artifact step entirely. This is **distinct from** the per-Critical-finding **Critical Findings Verification (issue #537)** below, which uses the same skill to *confirm* each Critical finding one by one; here the skill runs **once over the whole diff** to *detect* assignment gaps up front. Do not duplicate a gap that `@skills/assignment-compliance-check/SKILL.md` already raises in its `## Assignment Compliance` block — when both would surface the same gap, keep the technical **Critical** finding here (with reproducer fields, folded into the standard severity buckets) and let the compliance block carry only the non-technical mirror.
    - @skills/security-review/SKILL.md
    - @skills/api-review/SKILL.md — API design contract lens (`@rules/api/general.mdc`). The skill self-scopes: when the diff touches no HTTP API surface (routes, controllers / `__invoke` request handlers, API Resources / DTOs in responses, FormRequests, status-code handling, `Idempotency-Key` logic) it returns no findings. When it does, fold the Critical / Moderate / Minor findings (with their reproducer fields) into the standard severity buckets — do not duplicate a finding the **Strict rule compliance** walk already raised for `@rules/api/general.mdc`.
    - @skills/class-refactoring/SKILL.md **with `MODE=cr`** — read-only refactoring lens scoped to the PR diff, **run on every CR**. The lens walks the skill's **complete guideline set** (its *Refactoring Guidelines*, its *Laravel Context*, and the behavior-preservation clauses of its *Execution* section) against the lines the PR actually touched — DRY duplication and oversized methods are the common case, **not** the boundary of the walk. **Every item the lens returns is rendered in the published report.** The guidelines that are walked, the routing of each returned item (severity bucket vs. **Refactoring (DRY / tech debt)** / **Refactoring proposals**), and the de-duplication + no-drop contract are all owned by `@rules/code-review/general.mdc` *Refactoring & Tech Debt (DRY) Analysis — diff-scoped detail* — follow it there rather than restating it here. `MODE=cr` guarantees the lens never modifies code, writes tests, commits, runs fixers, or chains another review — it returns proposals only. Do not propose changes that fall outside the diff.

- Run conditionally:
    - **Diff is a refactoring (behavior-preserving structural change per `@rules/refactoring/general.mdc`) → run the full refactoring skill set read-only.** Detect this when the PR restructures existing code without adding a feature or changing observable behavior (extracted methods / classes, moved orchestration, renamed-for-clarity, dedup, layer reshuffles). When it fires, additionally invoke `@skills/refactor-entry-point-to-action/SKILL.md` **with `MODE=cr`** (and run the `class-refactoring` lens above at full depth) to surface the entry-point → Action proposals. **Both skills run read-only — no code changes, no commits, no fixers, no review chaining — `MODE=cr` enforces this.** Fold their output into the **Refactoring (DRY / tech debt)** section (in-scope items) and **Refactoring proposals** section (out-of-scope items) of the review; this skill never modifies code.
    - **Database operations detected in the diff → `@skills/mysql-problem-solver/SKILL.md` is mandatory.** Trigger this skill whenever the diff touches any of: raw SQL strings, Eloquent / query-builder calls (`DB::`, `->where(`, `->join(`, `->whereHas(`, `->withCount(`, `->orderBy(`, `->groupBy(`, `->chunk(`, `->cursor(`, `paginate(`, `simplePaginate(`), Eloquent relationship definitions, `with(` / `load(` eager loads, model scopes, ModelManager / Repository methods, database migrations (`Schema::`, `up()` / `down()`), seeders, factories that materialise rows, DynamoDB / NoSQL access. Pass the diff scope to `mysql-problem-solver` and capture its findings — they **must** appear in the published CR review under the dedicated `## Database Analysis` section described in **Output Rules**, never silently absorbed into the generic Critical / Moderate / Minor buckets.
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
- **Omit empty sections entirely.** Only the header block (Status / Counts / Last updated / tracker-status line) and the final `Summary` line are always rendered. The `Summary` line always carries the **assignment conformance verdict** from the **Assignment Conformance Gate** (`assignment conformance: conformant` / `N gap(s)` / `no linked issue`) so the reader sees whether the changes match the assignment without scanning the body. The `Coverage:` header line, the `## Coverage` section, and the `coverage …` slot in the summary line are all conditional — render them **only** when the coverage gate produced something to report (uncovered changed lines or unavailable / non-runnable tooling, both Critical findings). When every changed line is at 100% coverage and the tool ran successfully, drop all three coverage surfaces; the Counts line is the clean signal. Every other section — `Findings` (including each severity sub-heading), `Commit Split Proposal`, `Refactoring (DRY / tech debt)`, `Refactoring proposals`, `Database Analysis`, and any specialized-review sub-section — appears **only when it has at least one item**. Never emit `None.` / `Not applicable.` / `n/a` / `100%` placeholders for empty sections or omitted coverage surfaces; drop the whole heading and body instead. **History across CR runs** is preserved by the tracker's edit history on the upserted comment — never re-create a `Previous CR Status` section in the body.
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
- This skill is read-only and does not publish anywhere itself. The wrapper skills that consume its output (`@skills/code-review-github/SKILL.md`, `@skills/code-review-jira/SKILL.md`) **must** delegate the **single consolidated comment on every linked issue** in the originating tracker (GitHub issue, JIRA ticket, or both) to `@skills/pr-summary/SKILL.md` — the CR wrappers never author their own non-technical template. `pr-summary` produces a uniform *"Summary of changes + How to test"* comment understandable by non-technical project managers, rendered as GitHub Markdown for GitHub issues and JIRA Wiki Markup for JIRA tickets per `@rules/jira/general.mdc`. `@skills/assignment-compliance-check/SKILL.md` returns the **Functional review** markdown block on every run that has a linked tracker — including the affirmative `Goal met: Yes` checklist on a clean run — and the CR wrapper passes it as an embedded block to `pr-summary`, which appends it after `How to test` so the linked-tracker audience reads exactly **one comment per CR run** (per issue #498). Only when the compliance check returns the `no linked issue — assignment compliance skipped` status does the wrapper embed nothing. Technical findings still go directly on the PR comment.
- **`## Commit Split Proposal` section (issue #763).** Render it immediately after `## Findings` whenever the **Commit Split & Atomic Deployability Gate** fires: verdict, one row per proposed commit (subject, contents, source hunks, assignment item, cherry-pick verdict, reversibility), rollback notes, the reconciliation proving the union equals the current diff, and the blockers. The finding is **Critical**, listed in the `## Findings` Critical bucket by title with this section as its body, and exempt from the Faulty Example / Expected Behavior / Test Hint fields. Never dropped by the late-iteration filter; omitted entirely — section and summary slot — when the history is already atomic.
- **Security, translation, and test-isolation output walks** — apply the per-string / per-diff walks defined in `@rules/code-review/general.mdc` *Output Rules — Security & Translation Walks* and raise one finding per match: **Safe validation & error texts (issue #540)**, **Malicious code & supply-chain indicators (issue #549)**, **Malicious file upload content (issue #680)**, **Translation completeness**, **Test isolation — no real HTTP, no real system processes**, and the **Database Analysis section** rendering rule. Severities are declared there.
- **Late-iteration report scope (CR iteration > 2).** When the caller passes `iteration = <n>` (the Review loop iteration from `@skills/process-code-review/SKILL.md`) and `n > 2`, the report carries **Critical and Moderate findings only** — every Minor finding, `Refactoring (DRY / tech debt)`, and `Refactoring proposals` are dropped at Output assembly, `Counts` and `Summary` keep their real unchanged numbers, and the header gains the `Report scope:` line. No walk is skipped and the convergence gate is unchanged. Canonical: `@rules/code-review/general.mdc` *Late-Iteration Report Scope — Critical & Moderate Only (CR iteration > 2)*.
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
