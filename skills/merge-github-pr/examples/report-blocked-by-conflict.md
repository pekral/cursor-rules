# Example: PR Blocked by Conflict

## PR #165 — refactor(models): rename User columns

| Field | Value |
|---|---|
| **Decision** | `do not merge` |
| **CI** | Not evaluated (conflict detected) |
| **Conflicts** | Yes — merge conflict with base branch |
| **Reviews** | Not evaluated |
| **Unresolved threads** | Not evaluated |

### Blocking reason

The PR has merge conflicts with the `master` branch. CI and review status are not evaluated until conflicts are resolved.

### Next action

Rebase the branch onto `master`, resolve conflicts, and push.
