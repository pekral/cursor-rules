# Skill Reuse Policy

## Principle

Always prefer invoking an existing project skill over performing ad-hoc work when a skill already covers the needed functionality.

## When to reuse skills

- **Bug investigation**: Use `analyze-problem` during the Understand phase
- **Issue resolution**: Use `resolve-github-issue` when working from a GitHub issue
- **Testing**: Use `create-test` when adding or updating tests
- **Code review**: Use `code-review` or `process-code-review` during the Verify phase
- **Security checks**: Use `security-review` when changes affect authentication, authorization, or data handling
- **PR merging**: Use `merge-github-pr` when the final step is merging a pull request

## How to identify reuse opportunities

1. During the Propose phase, list all skills that could apply
2. During Implementation, invoke them in the appropriate order
3. During Verify, confirm that skill outputs were integrated correctly

## Rules

- Do NOT duplicate logic that an existing skill already provides
- If a skill partially covers the need, use it for the covered part and handle the rest manually
- Document which skills were invoked and what they contributed
