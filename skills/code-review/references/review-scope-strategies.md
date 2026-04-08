# Review Scope Strategies

## Reviewing Different Scopes

### Unstaged Changes (`git diff`)
- Focus on work-in-progress — be lenient on naming and structure.
- Look for accidental debug code, incomplete logic, or forgotten TODOs.
- Point out issues early to save time before commit.

### Staged Changes (`git diff --cached`)
- Treat as "ready for commit" — apply full review rigor.
- Check that the staged set is logically complete (no half-finished features).
- Verify nothing sensitive is staged (credentials, debug output).

### Branch Diff (`git diff main...HEAD`)
- Review the full scope of the feature or fix.
- Check for consistency across all commits in the branch.
- Look for regressions against the main branch behavior.
- Verify all acceptance criteria from the issue are met.

### Specific Files
- Focus on the provided files only.
- Check interactions with callers and dependents if the file is shared.
- Note if the review scope is too narrow to assess full impact.

### Commit Range
- Understand the evolution of the code across commits.
- Check if earlier commits introduced issues fixed in later ones (messy history).
- Evaluate whether the final state is correct, not just individual commits.

## Handling Missing Context
- If the intent of a change is unclear, state the assumption explicitly.
- If surrounding code is needed to assess correctness, read it before flagging.
- If the review scope is too narrow to reach a conclusion, say so.
- Never flag something as a bug based solely on the diff without checking the full file.

## Avoiding Overconfidence
- If you are not sure whether something is a bug, label it as an open question.
- Distinguish between "this is wrong" and "this looks suspicious".
- Acknowledge when the change is outside your expertise or project knowledge.
- Prefer "consider checking X" over "X is broken" when confidence is low.
