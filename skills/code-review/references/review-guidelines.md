# Review Guidelines

## Writing Good Review Comments
- Lead with the problem, not the solution.
- Explain *why* something is wrong — the reviewer should understand the risk.
- Be specific: reference the exact file, line, and variable.
- Keep comments concise — one finding per comment.

## Prioritization Rules
- Critical issues first — these block the merge.
- Major issues next — these should be fixed but are not blockers.
- Minor issues and nitpicks last — optional improvements.
- If a change has 10+ findings, focus on the top 5 most impactful.

## Avoiding Noise
- Do not comment on formatting, imports, or style — automated tools handle these.
- Do not repeat what the code already says.
- Do not nitpick naming unless it actively confuses or misleads.
- If a pattern is used consistently in the project, do not flag it as wrong.

## Focusing on Impact
- Ask: "What breaks if this code ships as-is?"
- Ask: "What is the worst-case scenario for this bug?"
- Ask: "Does this change affect other parts of the system?"
- If the answer to all three is "nothing significant", it is at most Minor.

## When Not to Comment
- The code is correct and clear.
- The suggestion is purely personal preference.
- The issue is already covered by static analysis tools.
- The improvement would require a significant rewrite for marginal benefit.
