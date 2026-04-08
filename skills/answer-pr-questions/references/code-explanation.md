# Code Explanation Guidelines

## How to Explain Code Clearly
- Start with what the code does (behavior), not how it does it (implementation).
- Use the reviewer's terminology — match their mental model.
- Reference specific file and line when the explanation depends on code context.

## Referencing Code
- Use `file:line` format for precise references.
- Quote only the minimal relevant snippet (1-3 lines max).
- Do not paste large blocks of code — link to the diff instead.

## Avoiding Vague Explanations
- Bad: "This handles the edge case."
- Good: "This guard returns early when `$user` is null, which happens when the session expires mid-request."

## Explaining Design Decisions
- State the constraint or requirement that drove the decision.
- If alternatives were considered, briefly mention why they were rejected.
- Keep it factual — "we chose X because Y", not "X is better than Z".

## When Not to Explain
- Do not explain obvious code (simple assignments, standard patterns).
- Do not explain framework behavior that any developer on the team would know.
- If the reviewer asks about something obvious, check if they are actually asking something deeper.
