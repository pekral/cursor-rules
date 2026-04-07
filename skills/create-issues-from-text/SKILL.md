---
name: create-issues-from-text
description: "Use when breaking down a text assignment into multiple issue tracker
  issues. Analyzes the assignment, splits it into logical steps, and creates a
  separate issue for each step — written like a senior project manager would.
  Each issue contains a product summary, technical solution, and testing
  scenarios."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/github-operations.mdc
- Never rewrite or remove the original assignment text — preserve it verbatim in the parent issue or first issue.
- Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
- All issue titles and bodies must be written in the language of the original assignment.
- Do not implement any code — this skill only creates issues.
- Each created issue must be assigned to the current user.

**Steps:**
- Read and fully understand the provided assignment text.
- Analyze the assignment and break it down into logical, sequential implementation steps. Each step should represent a single deliverable unit of work (one feature, one integration, one migration, etc.).
- For each step, prepare a complete issue draft structured as described in the **Issue Structure** section below.
- Before creating issues, present the proposed breakdown (step titles and brief summaries) to the user for confirmation. Wait for approval before proceeding.
- After confirmation, create all issues in the configured issue tracker using installed CLI tools (`gh`, `acli`, or equivalent).
- If the assignment references an existing issue or PR, link each created issue back to the source.
- After all issues are created, return a numbered list of created issues with direct URLs.

------------------------------------------------------------------------

## Issue Structure

Each issue must follow this template:

```markdown
## Goal

<Clear, concise summary of what this step achieves — written for product managers
and non-technical stakeholders. Focus on the business value and user impact.>

## Original Assignment (context)

<Reference to the original assignment or a relevant excerpt that explains why
this step exists. Keep it brief — link to the parent issue when possible.>

## Technical Solution

<Detailed technical approach for developers and AI agents:>
- Architecture decisions and patterns to follow
- Key files, classes, or modules to create/modify
- Integration points and dependencies on other steps
- Edge cases and constraints to consider

## Acceptance Criteria

- [ ] <Specific, measurable criterion 1>
- [ ] <Specific, measurable criterion 2>
- [ ] <...>

## Testing Scenarios

<Concrete test cases a QA tester can execute:>

### Happy Path
- <Scenario description and expected result>

### Edge Cases
- <Scenario description and expected result>

### Regression
- <What existing functionality must remain unaffected>

## Dependencies

- <List any steps/issues that must be completed before this one>
- <Or state "None" if independent>

## Notes

- Source: <HTTP link to the original assignment or parent issue>
- This issue was created by the `create-issues-from-text` skill.
```

------------------------------------------------------------------------

## Step Decomposition Guidelines

When splitting the assignment into steps:

- Each step must be independently deliverable and testable.
- Order steps by dependency — earlier steps should not depend on later ones.
- Group related changes together (e.g., migration + model + factory = one step).
- Separate infrastructure/setup steps from business logic steps.
- Separate frontend and backend work when they can be developed in parallel.
- Keep each step small enough to be reviewed in a single PR.
- If a step is too large, split it further.

------------------------------------------------------------------------

## Title Generation

Issue titles must follow the pattern:

`[Step N/Total] <Concise action-oriented title>`

Examples:
- `[Step 1/5] Create database migration for user preferences`
- `[Step 2/5] Implement UserPreference model and repository`
- `[Step 3/5] Add API endpoints for preference management`

------------------------------------------------------------------------

## After Creating Issues

After all issues are created:

1. Return a summary table with: step number, issue title, issue URL, and dependencies.
2. If the original assignment came from an existing issue, post a comment on that issue with the breakdown summary and links to all created issues.
3. Ensure all issues are assigned to the current user.
