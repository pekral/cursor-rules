# Changelog

All notable changes to `cursor-rules` will be documented in this file.

## [Unreleased]

- 📝 **Changed**: `code-review` skill tightened to **strict rule compliance**. Two new mandatory Core Analysis steps: (1) **Strict rule compliance** — walk every numbered/bulleted rule from every Apply'd rule file (`@rules/php/core-standards.mdc`, `@rules/code-review/general.mdc`, `@rules/refactoring/general.mdc`, `@rules/code-testing/general.mdc`, plus on Laravel projects all `@rules/laravel/*.mdc`) against every line in the diff and raise one finding per violation with the rule reference; (2) **Architecture conformance (Laravel)** — walk every section of `@rules/laravel/architecture.mdc` (Business Logic Layers, Actions, Model Services, Repositories/ModelManagers, DTOs, Data Validators, Controllers, Livewire, Custom Helpers, …) against the diff. Default severity for any unexcused rule violation on touched lines is **Critical** unless the rule file's CR Severity Rules subsection assigns lower. The blanket "do not review formatting / linting" exclusion was narrowed to fixer-handled output (Pint / PHPCS / Rector) only — every rule violation a fixer does not catch must be flagged
- 📝 **Changed**: code review skills (`code-review`, `code-review-github`, `code-review-jira`) now mandate publishing a non-technical summary (overall status, key risks in plain language, testing recommendations, link back to the PR) to **every linked issue** in the originating tracker — `code-review-github` posts to each `closingIssues[]` GitHub issue via `gh issue comment` after the PR comment, and `code-review-jira` mirrors the existing JIRA non-technical summary onto any GitHub issues the PR also references. The canonical `code-review` rule explicitly states the wrapper skills must propagate the summary
- 📝 **Changed**: Laravel architecture rules and the `class-refactoring` skill now treat Eloquent models as a 7th allowed home for business logic — but **only** for simple, self-contained domain methods (own-attribute predicates like `$user->isActive()`, computed values from already-loaded data, simple state derivations). Anything that needs external services, repositories, model managers, new database queries, persistence side effects, or multi-entity orchestration must move to one of the other six layers (Action / Model Service / Repository / ModelManager / Data Validator / Data Builder). CR severity table flags model methods that cross this boundary as **critical** (#453)
- ✨ **Added**: new Agent skill `assignment-compliance-check` — plain-language verification that the PR implementation satisfies the linked issue's business requirements. Returns an in-memory **Assignment Compliance** markdown section (no file is written) that the invoking CR skill (`code-review`, `code-review-github`, `code-review-jira`) embeds verbatim into the published CR comment, so Critical functional gaps surface alongside the rest of the review (#457)
- ✨ **Added**: new Laravel rule `laravel/dynamodb.mdc` covering DynamoDB query safety — scan prevention for every query-builder change, required key-targeted access patterns (`GetItem` / `Query`), GSI usage, review checklist, and Tinker-based testing/debugging when `laravel/tinker` is available (#447)
- 📝 **Changed**: code review and security-review skills (`code-review`, `code-review-github`, `code-review-jira`, `security-review`) and their output templates now require a **Suggested Fix** code snippet alongside Faulty Example / Expected Behavior / Test Hint for every Critical and Moderate finding (Critical/High for security). The snippet must comply with `@rules/php/core-standards.mdc` and, on Laravel projects, `@rules/laravel/architecture.mdc`. `process-code-review` reads the snippet and applies it directly when resolving the finding
- 📝 **Changed**: code review skills (`code-review`, `code-review-github`, `code-review-jira`) and the shared `code-review/general.mdc` rule now require reviewers to flag newly added or modified logic that duplicates behavior already present elsewhere in the codebase — reuse the existing implementation to keep logic unified across the application (#440)
- 🐛 **Fixed**: CR skills now systematically review all open PRs per issue instead of only one (#227)
- 🐛 **Fixed**: JIRA skills now consistently prefer `acli` console tool via shared `jira-operations.mdc` rule (#228)
- 🐛 **Fixed**: CR no longer flags custom named static constructors (`fromModel()`, `fromRequest()`) on Spatie DTOs as issues (#230)
- 🐛 **Fixed**: CR skills now include mandatory regression analysis to verify changes don't break existing functionality outside ticket scope (#233)
- 🐛 **Fixed**: `code-review` skill now enforces English-only output — Czech Deliver/Communication sections translated (#235)
- 🔧 **Changed**: move shared rule files from `rules/skills/` to `rules/`, update all skill references (#238)
- 🐛 **Fixed**: `create-missing-tests-in-pr` and `test-like-human` skills now use shared `github-operations.mdc` / `jira-operations.mdc` rules instead of inline preferences (#237)
- 🐛 **Fixed**: all mock rules now prefer partial mocks (`makePartial()`) over full mocks (#241)
- ✨ **Added**: new Agent skill `skill-creator` for scaffolding new SKILL.md files that follow project conventions and pass `skill-check` validation (#432)
- ✨ **Added**: new Laravel rule `laravel/queue-debouncing.mdc` covering safe queue debouncing, urgency separation, replaceable-work design, and review/testing requirements (#431)
- 📝 **Changed**: testing rules now explicitly mandate Pest syntax (`it()` / `test()`) for all new test files; PHPUnit class-based tests are no longer allowed for new tests (#430)
- 📝 **Changed**: rules and CR skills now require 100% test coverage for every changed code path, mandate discovering the project's coverage command (Phing → Composer) before running it, and require a `## Coverage` section in every published CR comment — never push a CR report without the coverage result (#429)
- 📝 **Changed**: Laravel queue rules require a `deduplicationId(): string` method directly on the job class when using SQS FIFO / an SQS deduplicator package — the job is the source of truth for its own deduplication identity (#428)

## [0.6.2] - 2026-04-07

- 📝 **Changed**: consolidate CHANGELOG (single [Unreleased] section, remove duplicate blocks)
- 🔧 **Changed**: refresh readme and align Rules Overview with actual rule files
- 🔧 **Changed**: cleanup rules and skills
- 🔧 **Changed**: composer update dependencies
- 🐛 **Fixed**: installer (project root, rules/skills paths)
- 🐛 **Fixed**: install rules to correct folder
- 🐛 **Fixed**: Symfony console v8 compatibility
- ♻️ **Refactored**: optimize commands and prompts
- ✨ **Added**: new Agent skills (resolve-github-issue, merge-github-pr, code-review, seo-fix, package-review, and others)
- ✨ **Added**: new Agent skill `process-code-review` for handling pull request review feedback loops
- 🗑️ **Removed**: obsolete Agent skills (`class-refactoring-plan`, `composer-install`, `interactive-testing`, `merge-github-prs`, `resolve-random-github-issue`) and `skills/ANALYZA-SKILLS.md`
- ✨ **Added**: new Agent skill `smartest-project-addition` for single high-impact innovation prompt outcomes
- ✨ **Added**: new Agent skill `understand-propose-implement-verify` for strict problem-solving workflow
- 📝 **Changed**: Update SKILL.md files and rule files (php standards, sql, git, laravel)
- 📝 **Changed**: Laravel testing rules and test-writing skills require Eloquent rows in tests to be created only via model factories (#147)
- 📝 **Changed**: Laravel rules and test-writing skills clarify database schema defaults as source of truth—avoid duplicating them in PHP/factories (#152)
- 📝 **Changed**: Laravel testing rules and test-writing skills require queueing jobs in tests via `JobClass::dispatch()` only (#153)
- ✨ **Added**: new Agent skill `seo-geo` for SEO and generative-engine optimization strategy (#164)
- ✨ **Added**: new Agent skill `create-jira-issue-from-pr` for creating JIRA-ready issue drafts from GitHub PR review context while preserving original task text (#205)
- ✨ **Added**: new Agent skill `laravel-telescope` for Telescope URL analysis, DB request correlation, and optimization recommendations (#211)
