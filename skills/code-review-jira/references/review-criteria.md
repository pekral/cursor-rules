# Review Criteria

## Severity Levels

List findings using exactly three severity levels:

| Level | Meaning |
|---|---|
| **Critical** | Blocks deployment; causes data loss, security vulnerability, or breaks existing functionality |
| **Moderate** | Significant code quality or correctness issue that should be fixed before merge |
| **Minor** | Style, naming, or low-impact improvement suggestion |

## DRY Violations

Explicitly detect and report **DRY violations** in every CR result. Look for:
- Duplicated logic across files or methods
- Duplicated validation rules
- Repeated branching/condition blocks
- Copy-pasted code paths

## Findings-Only Output

- Do NOT include praise or positive feedback
- Output must contain **only findings** grouped by severity (Critical > Moderate > Minor)
- Each finding must include file/line (or file) and a short, actionable recommendation
- Do not include any summary, "what was checked", or compliments

## Communication Protocol

- If you find significant deviations from the plan or requirements, explicitly flag them and ask for confirmation
- If you identify issues with the original plan or requirements themselves, recommend updates
- For implementation problems, provide clear guidance on fixes needed with code examples

## Simple Fix Examples

For simple fixes, include a minimal example using JIRA `{code}` blocks only when it improves clarity. Do not over-explain obvious changes.
