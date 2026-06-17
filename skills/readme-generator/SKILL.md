---
name: readme-generator
description: "Use when a repository needs a maintainer-ready README.md (or sibling root docs like CONTRIBUTING / SECURITY) built from the project's actual code, manifests, scripts, and tests — a zero-hallucination scan that extracts real commands, setup steps, and configuration, with git commit/push only when the user explicitly asks. Adapted from the VoltAgent readme-generator subagent."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/git/general.mdc` — only when the user explicitly asks to commit or push the generated docs
- Output must be in English unless the existing README is written in another language; then match it
- **Zero hallucination** — never invent an install command, CLI flag, environment variable, config key, script name, badge, or setup step. Every concrete claim must be traceable to a file you read or a command whose output you captured
- Do not modify production code, tests, or configuration — this skill writes documentation only
- README-first scope: the repository root docs, not a full documentation site
- Never run git staging, commit, or push without an explicit user instruction

## Use when
- A repository has no README, or its README is stale, inaccurate, or thin
- The user asks to "generate", "write", "update", or "rewrite" the README (or `CONTRIBUTING.md`, `SECURITY.md`, `CHANGELOG.md`)
- Onboarding or maintainer-readiness docs must reflect the current code, not aspirations

## Required approach
- Read the codebase first; write second. Treat the repository as the single source of truth and external research as a last resort
- Extract real values verbatim — commands, flags, env vars, and config keys must be copy-paste correct
- Flag anything the repository cannot prove instead of guessing it
- Keep the README skimmable and ordered for a first-time reader: identity → install → usage → contribute → license

## Execution

1. **Establish identity & audience.** Determine the project's purpose, primary entry points, and target reader. Ask the user when the repository is genuinely ambiguous; otherwise infer from the manifest and entry files and state the inference.
2. **Scan the repository in depth.** Read the package manifest(s) (`composer.json`, `package.json`, `pyproject.toml`, etc.), entry-point files, scripts, type definitions, tests, and existing docs. Map the directory structure, exported API, and available commands. Use `Glob`/`Grep` to find every relevant surface, not just the obvious ones.
3. **Capture real commands.** Run discovered help/version commands (`composer list`, `--help`, `bin/console list`, etc.) to capture exact usage output. Read script definitions to extract the real install, build, and test commands. Never paraphrase a command you have not seen in a file or run.
4. **Extract real examples.** Pull usage snippets from tests, examples, and source. Verify each example reflects the current API surface. Discard obsolete or dead-file examples.
5. **Research only to fill gaps.** Use `WebFetch`/`WebSearch` only for framework or standards context the repository cannot authoritatively provide (e.g. a badge service URL, a license summary). Cite every external source you rely on. If a value is neither in the repo nor authoritatively findable, mark it as a `TODO` for the maintainer rather than inventing it.
6. **Draft the README.** Compose maintainer-ready sections: project identity, status/CI badges (only ones backed by real config), core features, prerequisites, installation, usage examples, configuration / environment variables, contribution notes, and license. Use active voice, clear headings, syntax-highlighted code blocks, and a logical top-to-bottom flow.
7. **Validate before delivery.** Confirm every command, path, badge target, and link resolves to something real in the repository or an authoritative source. Re-read the draft for skimmability and remove filler.
8. **Stage / commit only on request.** Write the file(s). Stage, commit, or push **only** when the user explicitly authorizes it, following `@rules/git/general.mdc` for branch and commit-message conventions; otherwise leave the working tree for the user to review.

## Output
- New or rewritten `README.md` (and any explicitly requested sibling root doc) reflecting verified repository reality
- A short delivery summary listing: files scanned, commands captured, any external sources cited, and every `TODO` left for the maintainer where the repository could not prove a value
- No git actions unless the user explicitly requested them — state in the summary that the changes are unstaged and awaiting review

## Done when
- The README reflects only verified facts from the codebase or cited authoritative sources — no invented commands, flags, env vars, or steps
- Installation, usage, and configuration sections are copy-paste accurate against the current code
- Every link, badge target, and command resolves to something real
- The delivery summary lists scanned files, captured commands, cited sources, and outstanding `TODO`s
- Git staging/commit/push happened only if the user explicitly asked for it
