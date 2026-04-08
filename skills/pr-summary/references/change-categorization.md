# Change Categorization

## Standard Categories

Use the following categories when grouping changes. Not all categories will apply to every PR — only include categories that have at least one change.

| Category | Description |
|---|---|
| New features | Wholly new functionality visible to users or other systems |
| Bug fixes | Corrections to existing behavior |
| Refactoring | Internal restructuring with no user-visible change |
| Configuration | Changes to config files, environment variables, CI/CD pipelines |
| Tests | New or updated tests |
| Documentation | README, inline docs, API docs, changelogs |
| Dependencies | Added, removed, or updated third-party packages |
| Performance | Optimizations without functional change |

## Categorization Rules

- A single commit may touch multiple categories — list the change under the **primary** category.
- If a commit is ambiguous, prefer the category that best describes the **intent** (check the commit message).
- Merge-related or auto-generated commits (e.g., merge commits, bot updates) should be noted briefly or omitted if they carry no meaningful change.
