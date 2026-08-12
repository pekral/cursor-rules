# Code Review

> **Technical review.** This comment is the **Technical review** half of the two-part CR output (`@rules/code-review/general.mdc` *Two-part CR output — Technical & Functional review*) — strict rule compliance only. The **Functional review** (full acceptance-criteria checklist, `Goal met: Yes/No`) is published separately on the linked-tracker comment via `@skills/pr-summary/SKILL.md`; its one-line verdict mirrors onto this comment's `Summary` line as `assignment conformance: …`.

> **Section visibility — render only sections that have content.** Always render the header block (Status / Counts / Last updated) and the final `Summary` line. The `Coverage:` header line, the `## Coverage` section, and the `coverage …` slot in the summary line are conditional — render them **only** when the coverage gate produced something to report (uncovered changed lines or unavailable / non-runnable tooling, both Critical findings per `@skills/code-review/SKILL.md` Coverage gate). When every changed line is at 100% coverage and the tool ran successfully, drop all three coverage surfaces; the Counts line is the clean signal. The `## Architecture` section follows the same conditional rule (issue #530): on Laravel projects the architecture walk runs on every CR run, but the heading is rendered **only when the walk produces at least one finding** — when the walk is clean, omit the heading entirely (no "walked, 0 findings" line, no "clean" placeholder, no confirmation that the check ran). On non-Laravel projects (`laravel/framework` not in `composer.json` `require`), omit the `## Architecture` section entirely. Every section is conditional: omit its heading and body entirely when it has no items. Never emit `None.` / `Not applicable.` / `n/a` / `100%` / `walked, 0 findings` placeholders for empty sections or omitted coverage surfaces — drop them entirely. The Counts line in the header is the single source of "zero" signal; the goal is a clean, scannable PR comment a human can read at a glance — only items that still need action remain in the body.

> **Late-iteration report scope (`iteration > 2`).** When the CR wrapper is invoked with `iteration = <n>` from the Review loop of `@skills/process-code-review/SKILL.md` and `n > 2`, render **Critical and Moderate findings only**: drop every Minor finding (in `## Findings`, `## Architecture`, `## Database Analysis`, and every specialized-review sub-section), drop the whole `## Refactoring (DRY / tech debt)` section, and drop the whole `## Refactoring proposals` section. The `## Commit Split Proposal` section is **Critical** and is **never** dropped — it renders on every iteration together with its `commit split: …` slot on the `Summary` line. The `Counts` and `Summary` lines stay **unchanged in shape and content** — every slot, carrying the real number for every severity, including the suppressed Minor and refactoring items — and the header block gains the line `**Report scope:** critical+moderate only (iteration {n}) — Minor findings and refactoring sections not rendered` directly under `Counts`; never drop a slot, never emit a placeholder for a suppressed item, and never report it as a zero count. A run with no `iteration` input is `iteration = 1` and renders the full report. Canonical: `@rules/code-review/general.mdc` *Late-Iteration Report Scope — Critical & Moderate Only (CR iteration > 2)*.

**Status:** clean / needs-fix
**Counts:** Critical {n} · Moderate {n} · Minor {n} · Refactoring {n}
**Report scope:** critical+moderate only (iteration {n}) — Minor findings and refactoring sections not rendered  *(render this line only on a late-iteration run, i.e. the caller passed `iteration > 2`; the `Counts` line above keeps its real, unchanged numbers)*
**Coverage:** {result} (tool: {name or "not available — <reason>"})  *(render this line only when the `## Coverage` section is rendered — i.e. uncovered changed lines or unavailable tooling)*
**Last updated:** {ISO-8601 timestamp of this CR run}

> **Always-new comment:** the CR wrapper (`code-review-github` / `code-review-jira`) publishes this output as a **new comment on every run** — it never edits a prior comment in place. GitHub comments carry an actor marker (`<!-- cr-comment:actor=<gh-login> -->`); JIRA comments carry no marker. The chronological sequence of comments is the audit trail — never re-create a `Previous CR Status` section in the body.

---

## Findings

> Render only when at least one Critical, Moderate, or Minor finding exists. Within this section, render only the severity sub-headings that have items — omit the others entirely. When all three severities are empty, omit the entire `## Findings` parent heading.

### 🔴 Critical 1. <short title>

(the commit-split finding per `@rules/code-review/general.mdc` *Commit Split & Atomic Deployability Proposal — canonical walk-through* is listed here by title only and is exempt from Faulty Example / Expected behavior / Test hint — its body is the `## Commit Split Proposal` section below)

- **Location:** `path/to/file.php:42`
- **Rule:** `@rules/<area>/<file>.mdc#<section>`
- **Impact:** one sentence — what breaks or what risk this introduces.
- **Faulty Example:**
  ```php
  // minimal code or input that reproduces the issue (no secrets / PII)
  ```
- **Expected behavior:** single assertable statement (return value, thrown exception, persisted state, emitted event).
- **Test hint:** test layer (unit / integration / feature) + entry point, in one sentence.
- **Suggested fix:**
  ```php
  // minimal corrected snippet — must comply with @rules/php/core-standards.mdc (and @rules/laravel/architecture.mdc on Laravel projects). Use `n/a — <reason>` only when a snippet adds no value.
  ```

### 🟠 Moderate 1. <short title>

(same six fields as Critical; a request-for-link finding per `@rules/code-review/general.mdc` *Third-Party API & Service Documentation Verification (issue #748)* step 3 is exempt from Faulty Example / Expected behavior / Test hint — the Suggested fix, the literal request-for-link template, is the whole finding)

### 🟡 Minor 1. <short title>

- **Location:** `path/to/file.php:42`
- **Note:** one sentence. Faulty Example / Expected behavior / Test hint / Suggested fix may be omitted when no behavior change is implied.

---

## Commit Split Proposal

> **Render only when the Commit Split & Atomic Deployability Gate fires** (`@rules/code-review/general.mdc` *Commit Split & Atomic Deployability Proposal — canonical walk-through*). The gate runs on every CR run, but this section appears only when the history under review is **not** already a logical, independently cherry-pickable partition of the change set. When it is — or when the change is genuinely atomic — omit the heading and body entirely; never render a "history is clean" line.
>
> The finding is **Critical**, always: it is listed by title in the `## Findings` Critical bucket with this section as its body, it counts toward the Counts line, it may never be downgraded, and it is **never** dropped by the late-iteration filter. It is exempt from the Faulty Example / Expected behavior / Test hint fields — the proposal below plus the Suggested fix is the whole finding.
>
> **Behavior-preservation invariant:** this is a repartition of the existing change set and nothing else. The union of the proposed commits equals the current diff byte for byte; no line of production code is added, removed, or rewritten, and no work is deferred.
>
> **Green- and live-commit invariants:** every proposed commit passes the project's gate at its own point of the history, and everything it adds is already reached there — the callee travels with its call site and its coverage. A commit that ships dead code (a symbol, route, config key, or asset whose first use lands in a later commit) is itself a trigger; hunks that cannot be separated without leaving an intermediate commit red or dead stay in one commit. The `Green` column always carries `yes`, and the `Live` column carries `yes` or `inert prerequisite — consumed by #k` for the single declared exception (config key, off-by-default flag, additive expand migration) — a proposed commit is never rendered with `green: no` or `live: no`.

- **Verdict:** {splittable into N commits | blocked — <reason>}

| # | Proposed subject | Contains | Assembled from | Assignment item | Cherry-pick | Green | Live | Reversible |
|---|------------------|----------|----------------|-----------------|-------------|-------|------|------------|
| 1 | `type(scope): short description` | one sentence | `<sha>` hunks in `path/to/file.php` | item ID / `pre-existing` / `support` / `noise` | independent | yes | yes | yes |
| 2 | `type(scope): short description` | one sentence | `<sha>`, `<sha>` | item ID | depends on #1 | yes | yes | no |

- **Rollback notes:** one line per commit marked `reversible: no` — what must be done to undo it. Omit when every commit is reversible.
- **Reconciliation:** the union of the proposed commits equals `<base>..<head>`; verify with `git diff <old-head> HEAD` (must be empty after the rewrite), replay the range with `git rebase --exec '<gate command>' <base>` (no commit is red), and check reachability per commit with `git grep -n '<symbol>' <sha>` for every symbol a commit adds (no commit is dead).
- **Blockers:** commits that cannot be split under any ordering, files two proposed commits both touch (the split will conflict on cherry-pick — name the file), destructive migrations — or `none`.
- **Suggested fix:** ``Repartition `<base>..<head>` into <N> commits — <1. `type(scope): subject`; 2. `type(scope): subject`; …> — with `git rebase -i <base>` (split a bundled commit via `git reset HEAD^` and re-commit the hunks separately; fold a repair commit into its target with `fixup`), then verify the repartition changed nothing: `git diff <old-head> HEAD` must be empty, verify every commit of the new range is green: `git rebase --exec '<gate command>' <base>` must replay the whole range without stopping, and verify every commit is live: for each symbol a commit adds, `git grep -n '<symbol>' <sha>` must return a use outside its own declaration. Publish with `git push --force-with-lease`.``

---

## Refactoring (DRY / tech debt)

> Render only when at least one in-scope refactoring item exists. Only items on lines touched by this PR (added or modified). Each item must reduce tech debt — no stylistic preferences. Omit the entire section when there are no items.

1. **Location:** `path/to/file.php:42`
   **Problem:** one sentence.
   **Refactor:** concrete consolidation step (Data Builder / DTO / Service / Action / Repository / ModelManager).
   **Why:** the rule reference the item satisfies (`@rules/laravel/architecture.mdc#<section>`, `@rules/code-review/general.mdc#<section>`, …); for a lens-produced item, also the matched `@skills/class-refactoring/SKILL.md` guideline.

---

## Refactoring proposals

> Render only when at least one out-of-scope structural improvement is justified by a rule. Omit the entire section when there are no items.

1. **Title:** short, actionable issue title
   **Scope:** affected file(s) or area
   **Reason:** rule violated + why it matters
   **Approach:** brief description

---

## Database Analysis

> Render only when the diff touches database operations (raw SQL, Eloquent / query-builder calls, eager loads, model scopes, ModelManager / Repository methods, migrations, seeders, DynamoDB / NoSQL access) **and** at least one finding is produced by `@skills/mysql-problem-solver/SKILL.md`. Omit the entire section when no DB operations are present in the diff, or when DB ops are present but no findings result — never leave a placeholder or fold it into Coverage.
>
> Report only findings (errors) and their fix recommendations. Never include the trigger decision, an inspected `file:line` list, or an EXPLAIN / static-analysis summary — those belong to the internal investigation, not the published review.
>
> Every finding must carry the **concrete optimization artifact** in a fenced code block — the rewritten query in full, the exact index DDL, or the rewritten batching code. A prose description of the fix ("add an index on `user_id`", "rewrite it to be SARGable") does not satisfy this section; see `@rules/code-review/general.mdc` *Database Analysis section*. Each **DB-performance** defect is rendered here exactly once and is never duplicated into `## Findings` — but a **security** finding on the same `file:line` is a different defect and always keeps its own `## Findings` entry with the full finding shape.

- **Findings:**
  1. **{Critical / Moderate / Minor}** — `file:line` — one-sentence problem
     **Suggested Fix:** {one sentence naming the fix category per `@rules/sql/optimalize.mdc` — existing-index reuse, query rewrite, pagination change, batching, new index, or the documented justification for a slower query}
     ```sql
     -- Mandatory: the concrete artifact itself, never a prose description of it.
     -- user-supplied values stay bound (?/:named) — never inlined or concatenated
     -- query rewrite / index reuse / pagination  → the rewritten query in full
     -- new index                                 → the exact DDL, e.g.
     --   ALTER TABLE orders ADD INDEX idx_user_status_created (user_id, status, created_at);
     -- application-level fix (Eloquent chain, batchUpdate / batchInsert,
     --   whereIn(...)->delete(), keyed bulk read) → render a php-fenced block instead of this one
     -- slower-but-justified query                → the three-part documentation block replaces this snippet
     ```

---

## Architecture

> **Laravel-only, conditional on findings (issue #530).** On every Laravel project (`laravel/framework` is in `composer.json` `require`), the architecture walk per `@skills/code-review/SKILL.md` Core Analysis "Architecture conformance (Laravel) — mandatory standalone walk-through" runs on every CR run, but this section is rendered **only when the walk produces at least one finding**.
>
> - **Walk produced findings →** render the `## Architecture` heading and list the findings below under Critical / Moderate / Minor severity sub-headings (same six reproducer fields as `## Findings`), each citing the offending `file:line` and the specific subsection of `@rules/laravel/architecture.mdc` (`Business Logic Layers`, `Actions`, `Action Rules`, `Model Services`, `Repositories and ModelManagers`, `DTOs`, `Data Modification (DRY)`, `Data Builders`, `Validation Rules (Traits)`, `Data Validators`, `Controllers and Other Entry Points`, `Resource Controllers`, `Single-Action Controllers`, `Livewire`, `Custom Helpers`).
> - **Walk produced zero findings →** omit the entire `## Architecture` heading and body. Do not render a `walked, 0 findings` status line, a `clean` placeholder, or any other confirmation that the check ran. The absence of the section is the clean signal — only items that still need action are reported.
> - **Non-Laravel projects →** omit the entire `## Architecture` section. Do not emit a "skipped" placeholder.

### 🔴 Critical 1. <short title>

(same six fields as `## Findings` — Location / Rule / Impact / Faulty Example / Expected behavior / Test hint / Suggested fix)

### 🟠 Moderate 1. <short title>

(same six fields as Critical)

### 🟡 Minor 1. <short title>

- **Location:** `path/to/file.php:42`
- **Rule:** `@rules/laravel/architecture.mdc#<subsection>`
- **Note:** one sentence. Faulty Example / Expected behavior / Test hint / Suggested fix may be omitted when no behavior change is implied.

---

## Coverage

> Render this section **only** when the coverage gate produced something to report — uncovered changed lines (Critical findings) or unavailable / non-runnable coverage tooling (Critical finding). When every changed line is at 100% coverage and the tool ran successfully, omit the entire `## Coverage` section, the `Coverage:` header line, and the `coverage …` slot in the summary line — the Counts line is the clean signal.

- **Tool:** {project's available coverage tooling used to verify the changed files (Phing coverage target, Composer `test:coverage` / `coverage`, or `vendor/bin/pest --coverage-clover` / PHPUnit `--coverage-clover`) — or "coverage tooling unavailable — <reason>". Assess the changed files only; do not gate on a project-wide coverage percentage.}
- **Command:** `<exact command run — e.g. `vendor/bin/pest --coverage-clover=coverage.xml`>`
- **Result:** {list of uncovered added/changed lines — which must also appear as Critical findings — or "coverage tooling unavailable — <reason>"}

---

**Summary:** {n} Critical · {n} Moderate · {n} Minor · {n} Refactoring · assignment conformance: {conformant | N gap(s) | no linked issue}{` · commit split: {n} commit(s) proposed` — appended only when the `## Commit Split Proposal` section is rendered; omitted when the history is already an atomic, cherry-pickable partition}{` · coverage {result}` — appended only when the `## Coverage` section is rendered; omitted on a clean 100% pass}
