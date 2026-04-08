# Structured Data Guidelines

## Recommended JSON-LD Types

Match the JSON-LD type to the page purpose:

| Page Type | Recommended Schema |
|---|---|
| Generic page | `WebPage` |
| Blog post / news | `Article` |
| FAQ section | `FAQPage` |
| Product page | `Product` |
| Company info | `Organization` |
| Software / app | `SoftwareApplication` |
| How-to content | `HowTo` |
| Local business | `LocalBusiness` |

## FAQ Schema for GEO Lift

- Use `FAQPage` schema with question-answer pairs
- Include citations or statistics in answers where truthful
- Keep answers concise but complete enough to be cited standalone

## Validation

- Use **Google Rich Results Test** to verify structured data renders correctly
- Use **Schema.org Validator** to check schema syntax
- Share validation URLs in the report; do not assume GUI automation is available

## Best Practices

- One primary schema type per page (additional types may supplement)
- Ensure schema data matches visible page content (no hidden or misleading data)
- Keep `@context` set to `https://schema.org`
- Include `name`, `description`, and `url` at minimum for any entity
