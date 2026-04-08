---
name: code-review
description: Senior PHP code reviewer. Use when reviewing pull requests, examining code changes vs master branch, or when the user asks for a code review. Read-only review — never modifies code.
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/review-only.mdc
- Always apply @skills/smartest-project-addition/SKILL.md internally to identify one highest-impact, low-risk addition candidate; include it only if it maps to a real finding and keep the final output in the required findings-only format.
- All CR output (findings, recommendations, comments) must be written in English.
- Identify changes vs main branch (list commits).
- Understand context before reviewing.
- Every CR must use @skills/security-review/SKILL.md for the current changes.
- Check for any points where the current changes could break the logic. If it is shared functionality, make sure to check these parts of the application as well!
- All changes must comply with `rules/**/*.mdc`.
- Apply @rules/architecture-patterns.mdc

**Scripts:** Use the pre-built scripts in `@skills/code-review/scripts/` to gather data. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/pr-diff.sh [<PR>]` | Show PR diff or current branch diff vs main |
| `scripts/pr-conflicts.sh <PR>` | Check if PR has merge conflicts |
| `scripts/list-commits.sh [<PR>]` | List commits in PR or current branch |
| `scripts/pr-previous-reviews.sh <PR>` | Fetch previous review comments for dedup |

**References:**
- `references/review-checklist.md` — pre-review gates, plan alignment, regression analysis, conditional reviews, focus areas, skip list
- `references/severity-levels.md` — definition and examples for Critical, Moderate, Minor levels
- `references/php-antipatterns.md` — `?array` ban, array key type safety, invokeable syntax, DTO attribute rules
- `references/laravel-patterns.md` — layer responsibilities, SOLID, large data processing, N+1, performance, testing
- `references/database-review-rules.md` — schema standards, index management, query analysis, transactions
- `references/io-bottleneck-checklist.md` — I/O trigger signals and streaming/async patterns
- `references/test-coverage-rules.md` — 100% coverage target, test quality, missing test identification

**Examples:** See `examples/` for expected output format:
- `examples/findings-critical.md` — output with critical findings
- `examples/findings-clean.md` — output when no issues found
- `examples/findings-mixed.md` — output with all severity levels
- `examples/findings-skipped-conflicts.md` — output when CR is skipped due to conflicts

**Steps:**
1. Run `scripts/pr-conflicts.sh <PR>` to check for merge conflicts. **Cancel CR if PR has conflicts!** per `references/review-checklist.md`.
2. Run `scripts/list-commits.sh <PR>` to identify all changes vs main branch.
3. Run `scripts/pr-previous-reviews.sh <PR>` to collect previous CR reports and build a dedup list per `references/review-checklist.md`.
4. Run `scripts/pr-diff.sh <PR>` to load the full diff.
5. **Plan Alignment Analysis** per `references/review-checklist.md`: compare implementation against the original issue description and requirements. Identify deviations, missing, or partially met items.
6. **Simplification analysis** per `references/review-checklist.md`: evaluate whether the solution can be written more simply, leveraging `rules/**/*.mdc`.
7. **Regression analysis** per `references/review-checklist.md`: trace callers and dependents of changed methods/classes. Flag regression risks as Critical.
8. **Security review (every CR):** Apply @skills/security-review/SKILL.md for the current changes.
9. **SQL analysis (only when changes touch the database):** Apply @skills/mysql-problem-solver/SKILL.md per `references/review-checklist.md` and `references/database-review-rules.md`.
10. **Race condition review (when shared state is modified):** Apply @skills/race-condition-review/SKILL.md per `references/review-checklist.md`.
11. **I/O bottleneck review (when changes touch file, storage, or external I/O):** Apply per `references/io-bottleneck-checklist.md`.
12. Review code against `references/laravel-patterns.md` — layer responsibilities, SOLID, large data processing, N+1, performance.
13. Review code against `references/php-antipatterns.md` — `?array`, array key safety, invokeable syntax, DTO attributes.
14. Verify test coverage per `references/test-coverage-rules.md` — 100% for changed files, test quality, missing variations.
15. Verify acceptance criteria: when the task has stated requirements, check each item against the changes; list unaddressed items as findings.
16. Assign severity per `references/severity-levels.md` and produce output per the Output contract below.

**Output contract:** Produce **only findings** (bugs/issues/risks) with a brief suggested fix. No summary, no "what was checked", no praise.

| Field | Required | Description |
|---|---|---|
| Severity group | Yes | Group findings by severity: Critical, then Moderate, then Minor |
| Finding title | Yes | Short description of the issue |
| Location | Yes | File + line (or at least file) |
| Impact/risk | Yes | What breaks or degrades |
| Fix recommendation | Yes | Concrete fix with short snippet for simple fixes |
| Confidence notes | If applicable | Caveats or assumptions about the finding |

If there are no findings, simply state that no issues were found.

**Communication protocol:**
- Do not include positive feedback or "well done" passages; output must contain only findings.
- If you find significant deviations from the requirements/specification, list them as findings with severity and recommendation.
- For implementation problems, provide clear steps to fix (and a short code example when it speeds up the fix).
- Findings are recommendations; final decisions remain with the human reviewer.

**After completing the tasks:**
- If all **Critical** and **Moderate** findings from the current CR cycle are resolved, then (and only then) run @skills/test-like-human/SKILL.md when the changes can be tested. The test-like-human skill must post its unified test report as a comment to the related issue in the issue tracker.
