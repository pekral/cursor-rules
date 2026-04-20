# Source Detection

Detect the issue tracker automatically from the input:

| Input pattern | Source | Extra rules |
|---|---|---|
| GitHub URL or `#123` | GitHub | Use `gh` CLI |
| JIRA URL or issue key (e.g. `PROJ-123`) | JIRA | Apply `@rules/jira/general.mdc`, use `acli` or JIRA MCP |
| Bugsnag URL or ID | Bugsnag | Treat as runtime error, prefer TDD |

If the source cannot be determined, ask the user.
