---
name: refresh-claude-md
description: "Use when the project's CLAUDE.md is stale or missing and must be regenerated from the current codebase — re-detect tech stack, commands, conventions, and structure, then update CLAUDE.md while preserving every human-authored customization. Adapted from the ECC codebase-onboarding skill."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/git/general.mdc`
- Trigger only to update or create `CLAUDE.md` — never run this as a general onboarding or audit task
- Reconnaissance uses Glob and Grep; use Read only to resolve an ambiguous signal, never to read every file
- Enhance, never blindly replace: preserve all human-authored sections, custom rules, and project-specific instructions already in `CLAUDE.md`
- Keep `CLAUDE.md` scannable and under ~100 lines; link to `@rules/**` instead of pasting rule content
- Flag unknowns explicitly ("Could not determine X") rather than speculate
- Do not modify other skills, rules, or production code

## Use when
- `CLAUDE.md` does not exist and the project needs one generated from the codebase
- `CLAUDE.md` is stale — the tech stack, build/test commands, directory layout, or conventions have drifted from what the file documents
- The user asks to "refresh", "regenerate", "update", or "rebuild" `CLAUDE.md`
- A dependency, framework, or tooling change has invalidated the documented commands or structure

Do **not** use this skill for ad-hoc codebase questions, code review, or onboarding write-ups — it has one job: keep `CLAUDE.md` correct.

## Execution

### 1. Decide whether a refresh is warranted
- Read the existing `CLAUDE.md` (if any) and record which sections are human-authored vs auto-generated.
- Confirm at least one trigger applies. If `CLAUDE.md` already matches the codebase, stop and report "no refresh needed" instead of rewriting it.

### 2. Reconnaissance (Glob + Grep only)
Gather signals without reading every file:
- **Manifests** — `composer.json`, `package.json`, `go.mod`, `pyproject.toml`, `Cargo.toml`, `Gemfile`, etc.
- **Framework fingerprint** — Laravel (`artisan`, `bootstrap/app.php`), `vite.config.*`, `next.config.*`, and similar config files
- **Entry points** — `public/index.php`, `artisan`, `app.*`, `main.*`, `src/` roots, `routes/`
- **Directory snapshot** — top two levels, excluding `vendor`, `node_modules`, `.git`, `dist`, `build`, `storage`
- **Tooling** — `phpstan.neon`, `pint.json`, `rector.php`, `phpcs.xml`, `.github/workflows/`, `Dockerfile`, `.env.example`
- **Tests** — `tests/`, `*Test.php`, `*.spec.ts`, Pest/PHPUnit config

### 3. Synthesize
- **Tech stack** — languages, framework versions, database, build tools, CI.
- **Commands** — extract real dev/build/test/lint commands from `composer.json` scripts, `package.json` scripts, and `Makefile`; never invent a command.
- **Structure** — map key directories to their purpose.
- **Conventions** — naming, error handling, and Git workflow from recent `git log` (skip and note if history is shallow).
- Use Read only to disambiguate conflicting signals; trust actual code over config when they disagree.

### 4. Merge into CLAUDE.md
- Update the auto-generated sections in place; leave human-authored content untouched.
- When creating a new file, use these sections, omitting any that do not apply: **Tech Stack**, **Code Style**, **Testing**, **Build & Run**, **Project Structure**, **Conventions**.
- Mark each detected fact so the next refresh can tell generated content from custom edits.

### 5. Validate
- Run every command written into the **Build & Run** / **Testing** sections to confirm it exists and is correct.
- Run `composer build` and fix any errors before declaring done.

## Output
- Updated (or newly created) `CLAUDE.md` at the project root.
- A short summary covering: whether a refresh was needed, which sections were updated vs preserved, which commands were verified, and any facts that could not be determined.

## Done when
- `CLAUDE.md` reflects the current tech stack, verified commands, structure, and conventions
- Every pre-existing human-authored section is preserved verbatim
- All documented commands have been run and confirmed to work
- `composer build` finishes without errors
- The summary lists updated vs preserved sections and any unresolved unknowns
