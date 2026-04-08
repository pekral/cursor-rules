# Merge Criteria

## Definition of "resolved review feedback"

A review item is **resolved** when:
- The requested code change is present in the current PR diff, OR
- The reviewer explicitly marked the thread as resolved, OR
- The author replied with a valid justification and no further objection followed

A review item is **NOT resolved** when:
- A `CHANGES_REQUESTED` review exists without a subsequent `APPROVED` review from the same reviewer
- An inline thread contains an unaddressed request with no reply or resolution
- The author acknowledged the issue but the fix is not in the diff

## Handling requested changes vs comments

- **CHANGES_REQUESTED** reviews are blocking — the PR cannot merge until the same reviewer submits an `APPROVED` review or the request is explicitly dismissed
- **COMMENT** reviews are non-blocking but must still be checked for actionable items; unaddressed actionable comments are reported as warnings

## Handling outdated threads

- Threads marked as "outdated" by GitHub (file changed since the comment) should still be verified — the underlying issue may persist in the new code
- If the outdated thread's concern is no longer applicable (code was removed or rewritten), treat it as resolved

## Approval validation

- At least one `APPROVED` review is preferred before merge
- If no reviews exist and no review is required by branch protection, the PR may proceed if CI is green and there are no conflicts
- Stale approvals (approval given before new commits were pushed) should be flagged as a warning but are not blocking unless branch protection requires re-approval

## Dependent PR handling

- If a PR depends on another unmerged PR (shared branch, stacked PRs), do NOT merge out of order
- Check the PR description and comments for dependency signals ("depends on #X", "after #X is merged")

## When diff changes invalidate approvals

- If commits were pushed after the last approval, flag this and recommend re-review
- Do not auto-merge if the diff delta since last approval is significant (new files, changed logic)
