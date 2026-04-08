# Completion Criteria

## Definition of done

Resolution of a JIRA issue is complete only when ALL of the following are satisfied:

1. **Code changes** — the fix, refactor, or feature is implemented in a dedicated branch
2. **Tests pass** — all existing tests pass and new tests cover the changes (target 100% coverage for changed code)
3. **Pull request created** — a GitHub PR exists with a clear description linking to the JIRA issue
4. **PR linked to JIRA** — the JIRA issue contains the PR URL (added as a comment or via the remote link field)
5. **JIRA status updated** — the issue is transitioned to the appropriate in-progress or review state

## What does NOT count as done

- Code committed but no PR created
- PR created but not linked to the JIRA issue
- PR linked but tests are failing
- JIRA issue left in its original status without transition

## Delegated resolution

This skill delegates actual resolution to `@skills/resolve-jira-issue/SKILL.md`. The delegated skill is responsible for implementation, testing, and PR creation. This skill is responsible for verifying that the delegated flow completed successfully.
