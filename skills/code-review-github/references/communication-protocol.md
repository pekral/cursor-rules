# Communication Protocol

## Output rules

- Do **not** include praise or positive feedback. Output must contain only findings.
- Never combine multiple languages in the answer (e.g., one part in English, another in Czech).
- All comments or outputs posted to GitHub (issues, PRs, review comments, PR descriptions) must be written in **English**.
- Use readable Markdown with clear section separators.
- Include short code suggestions for simple fixes when helpful.

## PR comment format

The PR comment must contain **only findings** grouped by severity:

1. **Critical** (first)
2. **Moderate** (second)
3. **Minor** (last)

Each finding must include:
- File and line reference (or file only if line is not applicable)
- A short, actionable recommendation

Do **not** include:
- Summary of what was checked
- Praise or "looks good" statements
- General commentary

## When no findings exist

If no issues are found, post a short comment stating that **no findings were identified**.

## Escalation

- If significant deviations from the plan or requirements are found, explicitly flag them and ask for confirmation.
- If issues with the original plan or requirements are identified, recommend updates.
- For implementation problems, provide clear guidance on fixes needed with code examples.
