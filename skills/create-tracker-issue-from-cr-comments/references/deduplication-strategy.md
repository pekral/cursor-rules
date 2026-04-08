# Deduplication Strategy

## When to check for duplicates

Before creating any issue, search the tracker for existing open issues that cover the same problem. This prevents clutter and duplicate work.

## Search strategy

1. Search by file path and keyword from the finding summary
2. Search by the `from-code-review` label combined with the affected component
3. If the finding references a specific function or class, search for that identifier

## Match criteria

An existing issue is considered a **match** (skip creation) when:

- It references the same file and the same concern (even if the line numbers differ due to code changes)
- It describes the same root cause, even if discovered in a different PR
- It was created from a previous code review and covers a superset of the current finding

An existing issue is **NOT a match** when:

- It references the same file but a completely different concern
- It is closed (the problem may have regressed)
- It covers only a subset of the current finding (in this case, consider updating the existing issue instead of creating a new one)

## Handling partial matches

When an existing issue partially overlaps with the new finding:

- If the existing issue can be extended, add a comment to it referencing the new PR and the additional context
- If the overlap is minor and the concerns are distinct enough, create a new issue and cross-reference the existing one

## Reporting skipped duplicates

For every finding skipped due to duplication, report:

```
- **Skipped:** <finding summary> — duplicate of #<existing issue number> (<existing issue title>)
```
