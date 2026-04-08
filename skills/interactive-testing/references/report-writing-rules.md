# Report Writing Rules

## Language and Tone

- Write for humans: no technical notes, terminal logs, stack details, or developer commentary in the final report.
- Use clear, concise language that a product manager or non-technical stakeholder can understand.
- Never combine multiple languages in the report (e.g., one part in English and another in Czech).
- The comment posted to the issue tracker must be written in the language of the task assignment.

## What to Include

- Pull request reference (number and title)
- Each tested scenario with its result
- Overall summary of testing outcomes
- List of failed, blocked, or unclear behaviors
- Recommendation whether the change appears ready from a user perspective

## What to Exclude

- Raw terminal output or log snippets
- Implementation details, internal architecture, or framework behavior
- Stack traces or error codes (summarize in plain language instead)
- Developer commentary or code-level observations

## Scenario Result Format

Each scenario must follow this structure:

- **What was tested** — short description of the user goal
- **Expected result** — what a normal user would expect
- **Observed result** — what actually happened
- **Status** — Passed / Failed / Blocked / Unclear
- **Comment** — human-readable note focused on user experience
