# Title Generation Rules

## Source

The issue title must be generated from the **first line** of the task description.

## Rules

- Use the **first line exactly** as-is
- Remove surrounding markdown formatting if present (e.g., strip `#`, `**`, etc.)
- Keep the title concise
- Do not rewrite or summarize the first line
- If the first line is too long (over ~120 characters), truncate at a natural word boundary and append `...`

## Examples

| First line | Generated title |
|---|---|
| `# Fix login timeout on mobile` | `Fix login timeout on mobile` |
| `**Upgrade Node.js to v20**` | `Upgrade Node.js to v20` |
| `Add retry logic for failed API calls` | `Add retry logic for failed API calls` |
