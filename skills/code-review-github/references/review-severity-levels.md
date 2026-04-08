# Review Severity Levels

Use exactly three severity levels for all findings. Do not invent additional levels.

## Critical

Issues that **must** be fixed before merge:
- Security vulnerabilities (injection, auth bypass, data exposure)
- Data loss or corruption risk
- Breaking existing functionality outside the ticket scope (regression)
- Race conditions on shared state that can cause incorrect results
- Merge conflicts with the base branch (blocks the entire review)

## Moderate

Issues that **should** be fixed before merge:
- Logic errors that affect correctness within the ticket scope
- DRY violations (duplicated logic, copy-pasted code paths, repeated validation rules)
- Missing or incorrect error handling
- Deviations from the planned approach without justification
- Performance problems (I/O bottlenecks, N+1 queries, unbounded memory)
- Violations of project conventions defined in `rules/**/*.mdc`

## Minor

Issues that are **recommended** but not blocking:
- Style or naming inconsistencies not caught by linters
- Missing or unclear documentation/comments
- Simplification opportunities that do not affect correctness
- Non-blocking suggestions for improved readability
