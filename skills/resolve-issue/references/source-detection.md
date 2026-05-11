# Source Detection

Detect the issue tracker automatically from the input:

| Input pattern | Source | Extra rules |
|---|---|---|
| GitHub URL or `#123` | GitHub | Load context via `skills/code-review-github/scripts/load-issue.sh <NUMBER\|URL>`; fall back to GitHub MCP only when the script is unavailable |
| JIRA URL or issue key (e.g. `PROJ-123`) | JIRA | Apply `@rules/jira/general.mdc`; load context via `skills/code-review-jira/scripts/load-issue.sh <KEY\|URL>`; fall back to JIRA MCP only when the script is unavailable |
| Bugsnag URL or ID | Bugsnag | Treat as runtime error, prefer TDD |

If the source cannot be determined, ask the user.
