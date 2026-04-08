# Finding Selection Criteria

## Findings that qualify for issue creation

A code review finding should become a tracked issue when:

- The PR author explicitly deferred it (e.g., "will fix later", "out of scope for this PR", "follow-up needed")
- The finding was acknowledged but not addressed in the current PR diff
- The finding relates to a pre-existing problem exposed by the review (technical debt, missing tests, architectural concern) that is outside the PR scope
- The reviewer flagged a non-blocking concern that requires future work (e.g., "consider refactoring this in a separate PR")
- The finding has severity **Moderate** or **Critical** and was not resolved

## Findings that do NOT qualify

Do not create issues for:

- Findings already resolved in the current PR diff
- Pure stylistic or formatting concerns (covered by linting tools)
- Praise or acknowledgment comments ("LGTM", "nice", "good approach")
- Trivial nitpicks that have no functional or security impact
- Findings the reviewer explicitly withdrew or marked as optional
- Duplicate findings within the same review (same root cause, different locations) — consolidate into one issue

## Severity mapping

When the code review uses severity levels, map them to issue priority:

| CR Severity | Issue Priority | Action |
|---|---|---|
| Critical | High | Always create issue |
| Moderate | Medium | Create issue unless trivially fixable in a follow-up commit |
| Minor | Low | Create issue only if explicitly deferred by the author |

## Ambiguous cases

When it is unclear whether a finding qualifies:

- Check if the PR author replied — a reply with "acknowledged" or "good point" without a fix indicates deferral
- Check if the reviewer added a follow-up — continued discussion suggests the concern is still open
- When in doubt, create the issue and note the ambiguity in the confidence notes
