# Review Thread Resolution

## Extracting checklist from review comments

1. Run `scripts/pr-reviews.sh <PR>` — collect all review submissions
2. Run `scripts/review-threads.sh <PR>` — collect all inline code comments
3. For each `CHANGES_REQUESTED` review, extract the body as a checklist item
4. For each inline comment without `in_reply_to_id` (top-level thread), extract as a checklist item
5. Ignore pure praise or acknowledgment comments ("LGTM", "nice", "thanks")
6. Group items by file path for easier verification

## Verifying fixes in diff

For each checklist item:
1. Identify the file and line range referenced in the comment
2. Check the current PR diff (`gh pr diff <PR>`) for changes at that location
3. Verify the change addresses the specific concern raised
4. If the fix is in a different location than the comment referenced, note this but accept it if the concern is resolved

## Detecting unresolved discussions

A discussion is **unresolved** when:
- The thread has no reply from the PR author
- The author replied but did not address the concern (e.g., "will fix later")
- The reviewer's last message in the thread is a follow-up question or objection
- A `CHANGES_REQUESTED` review has no subsequent `APPROVED` from the same user

## Reporting unresolved items

Output format for each unresolved item:
```
- [ ] **[file:line]** — [summary of requested change] (reviewer: @username)
```

Group by severity:
1. Items from `CHANGES_REQUESTED` reviews (blocking)
2. Items from inline threads without resolution (blocking)
3. Items from general comments (warning, non-blocking)
