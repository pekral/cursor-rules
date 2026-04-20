---
name: test-like-human
description: "Use when testing a pull request from a real user perspective. Follow PR testing instructions, simulate realistic scenarios, and produce a human-readable report."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/git/general.mdc`
- Apply `@rules/jira/general.mdc`
- Output must be human-readable (no technical logs or internal details)
- Focus on user-visible behavior, not implementation

## Use when
- You need to validate a pull request from a real user perspective
- You want structured testing based on PR instructions

## Required approach

### 1. Understand the context
- Load the pull request (prefer `gh`, fallback to MCP tools)
- Read description, comments, and discussions
- Identify the expected final behavior

### 2. Extract testing instructions
- Locate **"Testing Recommendations" / "Doporučení k testování"**
- Extract all scenarios
- Do not invent new requirements unless needed to verify suspicious behavior

### 3. Choose testing method per scenario
Use the most appropriate approach:
- UI → browser tools
- API → `curl` or equivalent (prefer API docs if available)
- Backend logic → `php artisan tinker` or equivalent
- CLI → terminal commands

Do not over-test — focus on meaningful validation.

### 4. Execute as a senior tester
For each scenario, think:
- what the user tries to achieve
- where the flow could fail or confuse
- whether behavior feels correct and trustworthy
- for backend changes: whether data ends in the correct state

### 5. Validate results
- Compare expected vs actual behavior
- Identify inconsistencies, confusion, or broken flows
- Do not expose technical details in conclusions

## Report format

Use the template defined in `templates/test-report.md`.

## Deliver
- Reference the pull request
- Include all tested scenarios
- Provide overall summary
- Highlight failed / blocked / unclear cases
- Recommend whether the change is ready from a user perspective

## After completion
- Post the report to the related issue (GitHub/JIRA) using project rules
- Include a short summary of failed or unclear scenarios for developers
