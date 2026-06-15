# Source Detection

Detect the issue tracker automatically from the input:

| Input pattern | Source | Extra rules |
|---|---|---|
| GitHub URL or `#123` | GitHub | Load context via `skills/code-review-github/scripts/load-issue.sh <NUMBER\|URL>`; fall back to GitHub MCP only when the script is unavailable |
| JIRA URL or issue key (e.g. `PROJ-123`) | JIRA | Apply `@rules/jira/general.mdc`; load context via `skills/code-review-jira/scripts/load-issue.sh <KEY\|URL>`; fall back to JIRA MCP only when the script is unavailable |
| Bugsnag URL (`app.bugsnag.com/<org>/<project>/errors/<id>`) or `<org>/<project>/<error-id>` triple | Bugsnag | Treat as runtime error, prefer TDD. Load context via `skills/code-review-bugsnag/scripts/load-issue.sh <URL\|TRIPLE>` (needs `BUGSNAG_TOKEN`); fall back to a Bugsnag MCP server only when the script is unavailable |

If the source cannot be determined, ask the user.
