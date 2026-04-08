# Question Filtering Criteria

## What counts as a "current" question

A question is **current** when:
- It relates to the functionality, scope, or behavior described in the current PR or issue
- It was raised by a stakeholder (PM, client, reviewer, or contributor) and has not been retracted
- The context it refers to still exists in the current state of the code or requirements

A question is **NOT current** when:
- It refers to a previous iteration, branch, or PR that has already been closed or superseded
- The feature or behavior it asks about has been removed from scope
- It was explicitly retracted or marked as no longer relevant by the author

## What counts as "already answered"

A question is **already answered** when:
- A direct reply in the same thread addresses the question clearly and completely
- The answer was given by someone with authority or knowledge (e.g., developer, tech lead)
- No follow-up objection or request for clarification followed the answer

A question is **NOT answered** when:
- The reply is vague, partial, or only acknowledges the question without resolving it
- The answer was given but later invalidated by a code change or scope update
- Multiple conflicting answers exist and no resolution was reached

## Source priority

When gathering questions, collect from all sources in this order:
1. Issue timeline comments
2. PR timeline comments
3. PR review comments and review threads

Questions appearing in multiple sources should be deduplicated — keep the most recent or most specific formulation.

## Edge cases

- If a question is partially answered, treat it as unanswered and note what part remains open
- If a question is ambiguous, restate it clearly before answering
- If a question was answered in a different issue/PR not linked to the current one, treat it as unanswered in this context
