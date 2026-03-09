---
description: Plan phased refactoring for current changes using
  class-refactoring context. Use when the user wants a refactoring
  proposal only. Output markdown plan with one phase per commit.
  Read-only --- never modify code.
license: MIT
metadata:
  author: Petr Král (pekral.cz)
name: refactoring-plan
---

# Refactoring Plan

Prepare a **structured refactoring plan** for the current changes.

This skill is **read-only**: - never modify code - never generate
patches - never rewrite files

Output **markdown only**.

------------------------------------------------------------------------

# When To Use

Use this skill when:

-   a refactoring strategy is needed before implementing changes
-   large changes should be split into safe commits
-   architectural or class changes require planning
-   an AI agent should execute refactoring step-by-step

------------------------------------------------------------------------

# Planning Rules

Always:

-   produce **a plan only**
-   work from **current changes or described context**
-   keep phases **small and safe**
-   avoid full architecture redesign unless requested

Prefer:

1.  readability improvements
2.  class responsibility cleanup
3.  dependency cleanup
4.  architecture improvements
5.  follow‑up cleanup

------------------------------------------------------------------------

# Phase Requirements

Each phase must:

-   represent **exactly one commit**
-   be **independently reviewable**
-   be **safe to apply alone**
-   contain **clear implementation steps**

------------------------------------------------------------------------

# Phase Template

Use this structure for every phase.

``` markdown
## Phase X — Title

**Reason**

Why the refactor is needed.

**Steps**

- Step 1
- Step 2
- Step 3

**Impact**

Expected effects on:

- readability
- maintainability
- architecture
- performance
- testability

**Commit message**

type(scope): short description
```

------------------------------------------------------------------------

# Output Rules

The result must:

-   be **markdown**
-   contain **only the plan**
-   contain **multiple phases**
-   contain **no code changes**
-   be easy for humans to edit

------------------------------------------------------------------------

# Delivery

Do not leave the result in a generated file.

Ask where to send the plan:

-   GitHub Issue format
-   Slack message (markdown)
