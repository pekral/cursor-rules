---
name: create-skill-from-github-fork
description: Use when a user wants a new skill based on an existing GitHub
  project, but explicitly requires a fork-first workflow and does not want to
  load or reuse the source skill directly from the original repository.
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Create Skill From GitHub Fork

Create a brand-new local skill using a forked GitHub repository as reference.

Do not load the skill from the upstream repository path and do not treat
upstream files as final output. The result must be a newly authored skill in the
current project.

------------------------------------------------------------------------

# When To Use

Use this skill when:

-   the user requests "fork only" behavior
-   the user wants a new skill created, not copied from upstream
-   an existing GitHub skill should be used only as inspiration

------------------------------------------------------------------------

# Hard Constraints

Always:

-   create a **new** skill directory in local `skills/`
-   keep the same project skill concept and structure (`SKILL.md` with frontmatter)
-   base behavior on the forked source idea, then rewrite to project standards
-   preserve user intent exactly (especially fork-only requirement)
-   verify output with local `skill-check` rules

Never:

-   load skill files directly from the original GitHub repository as final content
-   copy upstream `SKILL.md` verbatim
-   skip local validation

------------------------------------------------------------------------

# Workflow

1.  Confirm source:
    -   identify GitHub repository URL and target concept.
    -   if missing, request the URL before implementation.

2.  Fork-first setup:
    -   use GitHub CLI to fork the repository (`gh repo fork ...`) when needed.
    -   use the fork only as reference context.

3.  Local skill creation:
    -   create `skills/<new-skill-name>/SKILL.md`.
    -   write complete frontmatter (`name`, `description`, `license`, `metadata.author`).
    -   follow existing section style used in this project.

4.  Content adaptation:
    -   normalize wording to project conventions.
    -   keep instructions deterministic and actionable.
    -   include explicit "When To Use", constraints, and workflow.

5.  Validation:
    -   run `npx skill-check check skills --no-security-scan`.
    -   fix all errors and warnings before final output.

------------------------------------------------------------------------

# Output Requirements

Return:

-   path of the newly created skill
-   short summary of what was created
-   validation result (`skill-check` pass/fail)

If forking fails, return the exact blocker and next required input.
