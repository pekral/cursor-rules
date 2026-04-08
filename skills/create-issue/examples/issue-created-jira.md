# Example: Issue Created on Jira

## Input

User provided the following task:

```
Upgrade database driver to v3.2
The current database driver v2.8 has a known memory leak under high concurrency.
Upgrade to v3.2 which includes the fix. Run the integration test suite after upgrade.
```

## Output

| Field | Value |
|---|---|
| **Tracker** | Jira |
| **Issue key** | PROJ-451 |
| **Title** | Upgrade database driver to v3.2 |
| **Assigned to** | Current user |
| **Link** | `https://company.atlassian.net/browse/PROJ-451` |

### Created issue body

```markdown
# Task

Upgrade database driver to v3.2

The current database driver v2.8 has a known memory leak under high concurrency.

- Upgrade to v3.2 which includes the fix
- Run the integration test suite after upgrade

---

# Notes

This issue was automatically formatted for readability.
Original task content was preserved exactly.
```

### CLI command used

```bash
jira issue create --type Task --summary "Upgrade database driver to v3.2" --description "..." --assignee "current-user"
```
