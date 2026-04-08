# JIRA Comment Formatting

## Universal Shape

Use this output shape for every JIRA update from this skill:

```
h3. <short status title>

h4. Findings
* <finding 1>
* <finding 2>

h4. Testing recommendations
* <recommendation with direct in-app link>
```

## Formatting Rules

- Use `h3.` for the short status title
- Use `h4.` for section headers (`Findings`, `Testing recommendations`)
- Use `*` for bullet lists (JIRA wiki markup, not markdown `-`)
- Inline references with `{{...}}` (ticket id, endpoint, env, status code)
- Use `{code}` / `{code:json}` only for short examples
- Never include markdown fences (```` ``` ````) in JIRA comments
- Keep comments understandable for project managers and testers, not only developers

## Testing Recommendations

- Only include if requirements are met and no critical errors found
- For every testing recommendation item, include a direct in-app link (full URL) so testers can open the exact screen immediately
