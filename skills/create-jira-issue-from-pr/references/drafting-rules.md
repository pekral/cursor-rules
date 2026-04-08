# Drafting Rules

## Original Assignment Handling

- The original assignment text must be preserved verbatim in the "Původní zadání (beze změn)" section
- Only formatting improvements (line breaks, list markers) are allowed — content must not change
- If the assignment is in Czech, keep it in Czech; do not translate

## Goal Summary ("Cíl")

- Must be understandable by non-technical stakeholders (product managers, business analysts)
- Keep it to 1-3 sentences maximum
- Focus on the business value or user-facing outcome, not technical details

## Technical Context ("Technický kontext z PR")

- Summarize relevant findings from PR diff, commits, and review threads
- Include file paths and component names when useful for developers
- Reference specific review comments that led to implementation requirements

## Implementation Requirements ("Požadavky pro implementaci")

- Derive from unresolved PR review comments and assignment context
- Each requirement must be a concrete, actionable task
- Remove duplicates and already-resolved items
- Use checkbox format for tracking

## Acceptance Criteria ("Akceptační kritéria")

- Must be measurable and testable
- Should cover both functional and non-functional aspects when relevant
- Each criterion should be independently verifiable

## Notes Section ("Poznámky")

- Always include the source PR URL
- Add a note that the output is formatted for JIRA and the original assignment is unchanged
- Include any caveats or assumptions made during drafting
