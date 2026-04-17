---
name: code-review
description: Use when senior PHP code review focused on architecture, business
  logic, and risk detection. Read-only.
---

# Code Review

## Purpose
Perform structured code review focused on:
- correctness
- architecture
- regression risks
- security and performance issues

---

## Constraints
- Apply @rules/php/core-standards.mdc
- Apply @rules/code-review/general.mdc
- Apply @rules/laravel/architecture.mdc
- Output findings only (no praise)
- Never modify code
- All output must be in English
- Do not review formatting, linting, or trivial issues

---

## Execution

- Before starting, ensure you are on the branch that contains the changes to review. If not, switch to it.
- Identify changes vs main branch.
- Deduplicate previous findings.

### Issue Context Analysis

Before reviewing code, load and analyze the full issue context:

1. Load the complete issue or task (description, all comments, and attachments) from the linked tracker (GitHub, JIRA, Bugsnag).
2. Extract from the issue:
   - **Requirements and acceptance criteria** — what the code must do
   - **Expected behavior** — how the feature or fix should work
   - **Edge cases and constraints** — mentioned by the reporter or in comments
   - **Test data** — any sample inputs, payloads, or scenarios provided in the issue
3. Use this context to evaluate whether the implementation fully satisfies the issue — not just whether the code is technically correct.
4. If the issue contains test data or test scenarios, verify they are covered by existing or new tests. Flag missing test coverage as a finding.

### Core Analysis
- Regression risk (shared logic, dependencies)
- Architecture and design quality
- Business logic correctness
- Missing or incorrect behavior
- Type safety and error handling
- Data validation encapsulation — verify that all validation logic is in dedicated Data Validator classes or FormRequests, not inline in Actions, controllers, jobs, commands, listeners, or Livewire components (see `@rules/laravel/architecture.mdc` Data Validators section)

### Specialized Reviews (when relevant)

- Always run:
    - @skills/security-review/SKILL.md

- Run conditionally:
    - SQL changes → @skills/mysql-problem-solver/SKILL.md
    - Shared state / concurrency → @skills/race-condition-review/SKILL.md
    - I/O or external calls → I/O review

### Validation
- Verify acceptance criteria
- Check test coverage for changed files (must be 100%)
- Identify missing test scenarios

---

## Output Rules

- Output only findings
- No praise, no summaries of what was checked
- Use severity levels:
    - Critical
    - Moderate
    - Minor
- Group findings by severity
- Each finding must include:
    - location
    - risk/impact
    - concrete fix

---

## Output Format

```markdown
## Critical

1. [file:line] Description  
   Impact: ...  
   Fix: ...

## Moderate

1. ...

## Minor

1. ...

**Summary: X Critical, Y Moderate, Z Minor**
```
---

## Principles

- Focus on risks, not style
- Prefer impact over quantity
- Avoid duplication of findings
- Prioritize regression detection
- Be precise and actionable

---

## After Completion

- If no **Critical** or **Moderate** findings remain:
    - run @skills/test-like-human/SKILL.md
