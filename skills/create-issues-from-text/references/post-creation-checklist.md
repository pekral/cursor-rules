# Post-Creation Checklist

After all issues are created, complete these steps:

## Required

1. Return a summary table with: step number, issue title, issue URL, and dependencies.
2. Ensure all issues are assigned to the current user.

## Conditional

3. If the original assignment came from an existing issue, post a comment on that issue with the breakdown summary and links to all created issues.
4. If the assignment references an existing issue or PR, link each created issue back to the source.

## Summary Table Format

The summary must be a markdown table:

```markdown
| Step | Title | URL | Dependencies |
|------|-------|-----|--------------|
| 1 | ... | ... | None |
| 2 | ... | ... | #1 |
```
