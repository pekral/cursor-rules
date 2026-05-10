---
name: skill-creator
description: "Use when creating a new Agent skill in this repository. Generates a SKILL.md that follows project conventions, passes skill-check validation, and updates the changelog and readme."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Skill Creator

## Purpose
Author a new Agent skill that fits this repository's conventions and ships ready for review.

Focus on:
- consistent frontmatter and section layout
- behavior aligned with `skill-check.config.json` limits
- clear Use when, Execution, and Done when sections
- no duplication with existing skills

---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/git/general.mdc`
- Output must be in English
- Do not modify other skills, rules, or production code
- Do not duplicate an existing skill — extend or refactor instead
- Never add behavior beyond what the requested skill needs

---

## Use when
- A new Agent skill must be added to `skills/` for an AI agent workflow
- An existing workflow that lives only in chat history should be promoted to a reusable skill
- The user asks to "create a skill", "add a skill", or "scaffold a skill"

---

## Inputs the agent must collect
Before generating any file, confirm with the user:
- **Skill name** — kebab-case, ≤ 64 chars, unique under `skills/`
- **Purpose** — one sentence describing what the skill does
- **Trigger phrase** — the "Use when …" wording for the description
- **Scope** — read-only review, code change, refactor, delivery, or other
- **Required rules** — which `rules/**/*.md*` files the skill must apply
- **Integrations** — issue tracker, GitHub, MySQL, Telescope, etc. (or none)
- **Output expectations** — markdown report, code change, PR comment, etc.

If the user already provided enough context, restate the assumptions and continue without re-asking.

---

## Execution

### 1. Discover existing skills
- List `skills/` and read any skill whose name overlaps semantically with the request.
- If an existing skill already covers the workflow, stop and propose an update to that skill instead of creating a new one.

### 2. Choose the slug and location
- Slug must be kebab-case, ≤ 64 chars, and not collide with an existing folder under `skills/`.
- Create `skills/<slug>/SKILL.md`. Add subfolders (`templates/`, `references/`) only when the skill genuinely needs them.

### 3. Write the frontmatter
Required keys:

```yaml
---
name: <slug>
description: "Use when <trigger phrase>. <one sentence on scope>."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---
```

Rules from `skill-check.config.json`:
- `description`: 50–1024 chars; should start with "Use when"
- `name`: ≤ 64 chars
- Body: ≤ 500 lines and ≤ 5000 tokens

### 4. Compose the body
Use the following section order. Omit a section only when it does not apply.

1. `# <Title Case Name>`
2. `## Purpose` — one short paragraph plus a bullet list of focus areas
3. `## Constraints` — applied rules and hard limits (one bullet per item)
4. `## Use when` — concrete triggers
5. `## Execution` — numbered steps the agent must follow
6. `## Output` or `## Output Format` — structure of what the skill returns
7. `## Principles` — short guiding rules (optional)
8. `## Done when` — verifiable completion criteria

### 5. Reference rules and other skills
- Reference rule files as `@rules/<area>/<file>.mdc` or `.md` exactly as they exist on disk.
- Reference other skills as `@skills/<slug>/SKILL.md`.
- Never invent paths. Verify every reference points to a real file before saving.

### 6. Keep behavior minimal
- One skill, one workflow. Split into separate skills if the scope grows.
- Do not paste rule content into the skill — link to it.
- Do not add humanizer, marketing, or third-party links unless the user requests them.

---

## Quality Gates
Run before declaring the skill done:
- `composer skill-check-fix` — auto-fix `SKILL.md` formatting
- `composer skill-check` — must report `PASS` with no warnings
- `composer build` — full project build (must finish without errors)

If `skill-check` flags warnings on the new file, fix the SKILL.md until the report is clean. Do not silence checks.

---

## Repository updates
After the new SKILL.md passes validation:
- Add a `CHANGELOG.md` entry under `[Unreleased]` describing the new skill and referencing the issue (e.g. `(#432)`).
- Update `README.md`:
  - bump the skill count in the "Skills Overview" header and the "Why This Package" bullet
  - add the new skill to the appropriate table (Issue Resolution, Code Review, Testing, Platform & Data, etc.)

Skip the README update only when the skill is intentionally internal and not part of the public catalog — state this explicitly in the PR description.

---

## Output

- New file: `skills/<slug>/SKILL.md`
- Updated `CHANGELOG.md` and `README.md`
- Short summary covering:
  - chosen slug and scope
  - rules and skills referenced
  - skill-check result
  - follow-up tasks (e.g. tests for code that the skill orchestrates) if any

---

## Principles

- Reuse existing skills before adding new ones
- Keep each skill narrow and composable
- Prefer explicit steps over vague guidance
- Verify every `@rules/*` and `@skills/*` reference exists
- Let `skill-check` be the source of truth for SKILL.md quality

---

## Done when
- `skills/<slug>/SKILL.md` exists with valid frontmatter and required sections
- `composer skill-check` passes with no warnings on the new file
- `composer build` finishes without errors
- `CHANGELOG.md` and `README.md` reflect the new skill (or the omission is justified)
- The summary lists the slug, references, and validation result
