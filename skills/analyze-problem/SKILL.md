---
name: analyze-problem
description: Use when structured problem analysis for debugging, root cause
  identification, and breaking down complex issues before proposing solutions
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Analyze Problem

## Purpose
Perform structured problem analysis before proposing or implementing any changes.

Focus on:
- verified facts
- multiple hypotheses
- root cause identification
- validation strategy

---

## Constraints
- Apply `@rules/php/core-standards.mdc` **only once it is established that the project is a PHP project (PHP stack in `composer.json`) and the analyzed change touches PHP code** — skip it for a non-PHP problem (docs, tooling, infra, markdown, config); do not load the PHP standards for an analysis that does not touch PHP.
- Apply @rules/compound-engineering/general.mdc — the pre-implementation research and the plan artifact below exist so the analysis compounds: it grounds the work in what already exists and leaves a reusable plan behind.
- Never modify code
- Output Markdown only
- Use one language only
- Do not jump directly to solutions
- Do not assume a single cause
- Be explicit about uncertainty

---

## Execution

- Analyze the problem and all available context.
- If relevant, load issue, comments, and attachments using available CLI or MCP tools.
- Prefer issue-tracker-specific tools over generic browsing.
- Walk through the Analysis Framework below in order — do not skip steps.
- Separate facts from assumptions and from hypotheses.
- Identify the most probable root cause and how to validate it.
- Recommend the smallest safe solution and explain rejected alternatives.

---

## Analysis Framework

Apply these 10 steps in order. Each step feeds the next — never jump ahead to a solution before evidence and root cause are settled.

1. **Context extraction** — what we actually know from the assignment, comments, attachments, and surrounding code.
2. **Problem statement** — one precise sentence describing the real problem.
3. **Expected vs actual behavior** — what should happen, and what is happening instead.
4. **Evidence** — logs, screenshots, issue comments, files, reproduction steps. Verified facts only.
5. **Root cause hypothesis** — the most likely cause, clearly separated from facts. State certainty.
6. **Impact / risk** — who and what is affected (users, business, technical, risk areas).
7. **Smallest safe solution** — the smallest, lowest-risk fix that addresses the root cause.
8. **Alternatives rejected** — competing solutions considered and why they were not chosen.
9. **Verification plan** — manual checks, automated tests, edge cases, and regression checks.
10. **Non-technical summary** — plain-language explanation for PM, support, or business stakeholders.

---

## Pre-Implementation Research & Plan

Before proposing or implementing anything, do the research that grounds the analysis in what already exists — then leave a reusable plan behind. This runs after the Analysis Framework settles the root cause and feeds the **Recommended Solution** (step 7) and **Implementation Outline** (step 8).

### Research (do all three before planning)

1. **Codebase** — read the actual files, layers, and conventions the change will touch. Find the existing part of the system the work belongs to; per `@rules/compound-engineering/general.mdc`, reach for an existing home before inventing a new abstraction.
2. **Commit history** — walk `git log` / `git blame` for the affected area to learn how it evolved, which past changes touched it, and which approaches were already tried or reverted. Past decisions are context you must not re-derive blindly.
3. **Internet best practices (when relevant)** — for an unfamiliar pattern, library, protocol, or security-sensitive surface, consult current authoritative references. Cite every source you rely on; skip this step for routine, well-understood changes.

### Plan artifact (the deliverable)

Capture the result as a **written plan** — a text file in the repo (e.g. under `docs/plans/` or alongside the issue) **or** a GitHub issue — not only inline prose. The plan must contain exactly these five parts:

- **Goal** — the outcome in one or two sentences: what will be true when this is done.
- **Architecture** — where the change lives in the existing system (files, layers, the existing part it extends), and why that home over a new abstraction.
- **Implementation steps** — concrete, ordered, independently reviewable steps a following agent can execute without re-deriving the analysis.
- **Sources** — links to the codebase locations, commits, and any external references the plan relies on.
- **Success criteria** — observable, verifiable conditions (tests, behavior, metrics) that prove the work is complete and correct.

State where the plan artifact was written (file path or issue URL) in the analysis output so the next agent can pick it up. A durable plan that the next agent reuses is the compounding payoff — see `@rules/compound-engineering/general.mdc`.

---

## Output Structure

The output uses the template at `templates/analysis-report.md`. The template has 11 sections that map onto the framework above:

1. **Summary** — short summary (covers steps 1–2)
2. **Problem Definition** — problem statement, expected/actual behavior, affected area, problem type (steps 2–3)
3. **Verified Facts** — verified facts only (step 4)
4. **Assumptions and Missing Information** — assumptions and unknowns (supports step 5)
5. **Probable Root Cause** — root cause, certainty, alternative causes (step 5)
6. **Problem Impact** — user/business impact, technical impact, risk areas (step 6)
7. **Recommended Solution** — recommended solution, things to avoid, side effects (steps 7–8)
8. **Implementation Outline** — likely change locations, recommended steps, architecture notes (step 7)
9. **Solution Verification** — manual checks, automated tests, edge cases, regression checks (step 9)
10. **Non-Technical Explanation** — explanation for non-technical stakeholders (step 10)
11. **Final Recommendation** — final recommendation, priority, next step

Fill every section. If a section has nothing to report, write a short explicit note (e.g. `No missing information.`) instead of leaving placeholders.

---

## Principles

- Focus on root cause, not symptoms
- Prefer evidence over assumptions
- Avoid confirmation bias
- Keep analysis structured and concise
- Prefer simple explanations over complex ones

---

## UI Redesign Lens

Apply this lens **only when the analyzed problem is a UI / UX redesign or a new user-facing flow** — detected when the assignment, the loaded issue, or its comments talk about layout, screen, page, dashboard, form, wizard, modal, widget, navigation, look & feel, accessibility, or any other end-user interaction surface. Skip the lens entirely for backend-only, infrastructure, performance, or tooling problems.

When it fires, the lens fixes the design direction of the **Recommended Solution** (step 7 of the framework) and the wording of the **Non-Technical Explanation** (step 10) so the analysis cannot drift into a complex, multi-screen, jargon-heavy design without an explicit reason:

- **Simple** — the screen carries the minimum surface that solves the user's job. Every input, button, copy block, illustration, and toggle on the proposed design must trace to a concrete user need stated in the assignment. Speculative knobs, "in case" filters, and decorative chrome are rejected the same way speculative code is rejected by `@rules/php/core-standards.mdc` *Design Principles*.
- **Intuitive** — the user reaches the goal without reading documentation. Primary action is unambiguous and placed where the user already looks; affordances match platform conventions (web / mobile / desktop) the user has internalised; nothing relies on a hidden gesture or an undocumented shortcut.
- **Readable for humans** — the layout follows a clear visual hierarchy (one primary call-to-action per view, secondary actions visibly demoted, supporting copy in plain language at the user's reading level), respects a comfortable line length and information density, and meets the project's accessibility baseline (WCAG AA contrast, keyboard focus order, screen-reader labels, no colour-only signal) unless the assignment explicitly de-scopes accessibility.
- **Modern** — the design follows current UI conventions of the framework / design system the project already uses (Tailwind UI, Filament, Material, Apple HIG, the project's in-house design tokens). Do not reintroduce patterns the platform has retired (1990s-style modal stacks, full-page reloads on every interaction, dense data tables with no progressive disclosure on mobile widths).
- **One-click default** — for any action the analysis recommends, prefer a single-click / single-tap completion over a multi-step flow. A confirmation step is allowed only when the action is destructive, irreversible, financially material, legally significant, or affects a third party — and the **Recommended Solution** must name which of those reasons justifies the extra click.
- **Wizard fallback when multi-step is unavoidable** — when the underlying job genuinely cannot fit one click (compound input, branching prerequisites, server-side processing between steps), recommend a wizard pattern with these mandatory properties: every step states its purpose and its position in the flow (*Step 2 of 4 — Billing address*); the user can move back without losing entered data; the user can save and resume later when the flow exceeds three steps; each step validates inline and surfaces field-level errors per the rules in `@rules/security/backend.md` / `@rules/security/frontend.md` *Safe Validation & Error Messages*; the final step shows a summary of every choice before commit. Reject wizard variants that hide progress, require the user to backtrack through a different surface to fix an earlier mistake, or block forward navigation behind a hidden prerequisite.

Record the design verdict in the **Recommended Solution** section using these exact subheadings so a reader can scan the lens output deterministically: *Simplicity*, *Intuitiveness*, *Readability*, *Modernity*, *One-click vs wizard decision* (one sentence — *one click* or *N-step wizard*, plus the reason). When the design is *N-step wizard*, also list the wizard's mandatory properties met by the proposal. Do not relax any of the six rules silently — when the assignment forces a deviation (e.g. the brand requires a non-standard interaction), cite the assignment passage that authorizes it.

---

## References

- references/debugging-strategies.md
- references/hypothesis-generation.md
- references/root-cause-analysis.md
- references/analysis-good.md
- references/analysis-missing-context.md
- references/analysis-multiple-hypotheses.md
