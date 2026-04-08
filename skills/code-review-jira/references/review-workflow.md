# Review Workflow

## Finding Linked PRs

1. Find all open pull requests automatically linked to JIRA via the branch name
2. If no PR found by branch name, review all comments in the issue and locate relevant PRs
3. Always check only **open** pull requests and ignore the rest

## Multiple PRs per Issue

If the issue has more than one open pull request:

1. Perform a separate code review for each open PR sequentially
2. Review each PR independently on its own branch
3. Post findings to the corresponding PR
4. Produce a per-PR summary
5. After all PRs are reviewed, provide a consolidated overview listing each PR with its result (clean / has findings)

## Conflict Policy

If the PR has merge conflicts with the base branch:
- **Do not perform the code review** for that PR
- Cancel and report that the CR was skipped due to conflicts
- Continue with the next PR

## Deduplication of Findings

Before writing findings:
1. Collect prior review comments/reports from the PR timeline and JIRA discussion
2. Build a dedup list by problem signature (file/scope + root cause + risk)
3. Skip findings already reported unless severity/impact changed

## Conditional Skill Application

Always apply:
- `@skills/code-review/SKILL.md`
- `@skills/security-review/SKILL.md`

Conditionally apply:
- `@skills/mysql-problem-solver/SKILL.md` — only if changes include database-related modifications (migrations, schema changes, repositories, raw SQL, query builder, or Eloquent/queries in changed code)
- `@skills/race-condition-review/SKILL.md` — only if changes contain shared-state signals (see `references/io-and-race-condition-signals.md`)
- I/O bottleneck review — only if changes touch file, storage, or external I/O (see `references/io-and-race-condition-signals.md`)

## Post-Review Steps

1. Post findings as a PR comment grouped by severity (Critical > Moderate > Minor)
2. If no findings, post a short comment stating **no findings were identified**
3. Run tests and verify requirements are met
4. If requirements are met, add a JIRA comment with testing recommendations per `references/jira-comment-formatting.md`
5. If all **Critical** and **Moderate** findings are resolved and changes are testable, run `@skills/test-like-human/SKILL.md`
6. The test-like-human skill must post its unified test report as a comment to the related issue in the issue tracker
7. Analyze the assignment discussion and provide a conclusion on whether the proposed solution is safe and effective
