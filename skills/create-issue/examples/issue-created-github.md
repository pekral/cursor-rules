# Example: Issue Created on GitHub

## Input

User provided the following task:

```
Fix login timeout on mobile
When users try to log in on mobile devices the request times out after 5 seconds.
We need to increase the timeout to 30 seconds and add a retry mechanism.
```

## Output

| Field | Value |
|---|---|
| **Tracker** | GitHub |
| **Issue number** | #87 |
| **Title** | Fix login timeout on mobile |
| **Assigned to** | Current user |
| **Link** | `https://github.com/org/repo/issues/87` |

### Created issue body

```markdown
# Task

Fix login timeout on mobile

When users try to log in on mobile devices the request times out after 5 seconds.
We need to:

- Increase the timeout to 30 seconds
- Add a retry mechanism

---

# Notes

This issue was automatically formatted for readability.
Original task content was preserved exactly.
```

### CLI command used

```bash
gh issue create --title "Fix login timeout on mobile" --body "..." --assignee @me
```
