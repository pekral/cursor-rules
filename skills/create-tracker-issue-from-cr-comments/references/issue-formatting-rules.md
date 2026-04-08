# Issue Formatting Rules

## Title generation

- Generate the title from the finding summary, not the reviewer's raw comment
- Keep titles concise (under 80 characters)
- Use a conventional prefix matching the finding type: `fix:`, `refactor:`, `test:`, `security:`, `perf:`
- Include the affected component or file area (e.g., `fix(auth): validate token expiry edge case`)
- Do not include PR numbers or reviewer names in the title

## Issue body structure

Format the issue body as follows:

```markdown
## Context

This issue was identified during code review of PR #<number> (<PR title>).

**Reviewer:** @<username>
**Severity:** <Critical | Moderate | Minor>
**File:** `<file path>:<line range>`

## Description

<Original reviewer comment, preserved exactly. Only formatting improvements allowed.>

## Relevant code

<Link to the specific file/line in the PR, or a short code snippet if the link would be ambiguous.>

---

_Created from code review finding. Original PR: #<number>._
```

## Labels

Assign labels based on the finding:

| Finding category | Labels |
|---|---|
| Bug or logic error | `bug`, `from-code-review` |
| Security concern | `security`, `from-code-review` |
| Performance issue | `performance`, `from-code-review` |
| Missing tests | `test`, `from-code-review` |
| Technical debt / refactor | `tech-debt`, `from-code-review` |
| Architecture concern | `architecture`, `from-code-review` |

Always include the `from-code-review` label so these issues can be filtered later.

If the label does not exist in the tracker, create it or fall back to the closest available label.

## Assignee

- Assign the issue to the current user (the person running the skill)
- Do not assign to the reviewer or PR author unless explicitly instructed

## Linking

- Reference the source PR in the issue body
- If the issue tracker supports PR linking (e.g., GitHub "linked PRs"), add the reference
