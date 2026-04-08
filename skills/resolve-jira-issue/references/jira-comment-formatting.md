# JIRA Comment Formatting

## Universal structure

Use this structure for every JIRA comment type (status update, testing recommendation, CR summary, implementation summary):

| Element | Syntax | Notes |
|---|---|---|
| Title | `h3. <short title>` | Always present |
| Context paragraph | Plain text, 1-3 lines | Immediately after title |
| Subsections | `h4. <section title>` | Optional, for grouping |
| Bullet lists | `* item` | For listing items |
| Testing recommendations | Full URLs as direct in-app links | Include in each bullet item |
| Inline code/paths/endpoints | `{{...}}` | Monospace formatting |
| Code examples | `{code[:language]}...{code}` | Only via code blocks |
| Tables | `||` header row, `|` data row | JIRA table syntax only |
| Spacing | One empty line between blocks | For readability |

## Rules

- Never use Markdown syntax in JIRA comments — always use JIRA wiki markup.
- Keep comments concise — focus on what changed, what to test, and actionable next steps.
- For testing recommendations, include direct in-app links as full URLs in each bullet item so testers can click through immediately.
