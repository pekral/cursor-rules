# Example: Full SEO/GEO Audit Report

## Site: example.com/product

### Current Status

| Area | Status | Details |
|---|---|---|
| **Title** | Present | "Example Product — Best Widget for Teams" (52 chars) |
| **Meta description** | Present | "Discover the best widget for teams..." (148 chars) |
| **Open Graph** | Partial | `og:title` and `og:description` set; `og:image` missing |
| **JSON-LD** | Missing | No structured data found |
| **robots.txt** | OK | Googlebot, Bingbot allowed; GPTBot blocked |
| **Sitemap** | OK | Page listed in `/sitemap.xml` |
| **AI bot access** | Partial | GPTBot and ClaudeBot disallowed |

### Prioritized Recommendations

1. **Add JSON-LD `Product` schema** — enables rich results and improves AI citation; include `name`, `description`, `offers`, `aggregateRating`
2. **Unblock GPTBot and ClaudeBot** — required for ChatGPT and Claude search citation; update `robots.txt` Disallow rules
3. **Add `og:image`** — use a 1200x630 product image for social sharing and link previews
4. **Add FAQ section with `FAQPage` schema** — addresses common queries; strong GEO lift from citation-ready Q&A pairs with statistics

### GEO Tactics Applied

- Rewrote intro paragraph to answer-first format with a concrete statistic ("used by 12,000+ teams")
- Added attributed quotation from CTO in the features section
- Structured comparison table for competitor feature matrix

### Validation

- Google Rich Results Test: `https://search.google.com/test/rich-results?url=example.com/product`
- Schema.org Validator: `https://validator.schema.org/`
- robots.txt check: `curl -s https://example.com/robots.txt`

### Confidence Notes

- Page speed not measured directly; inferred as acceptable from project context
- AI bot policy change (unblocking GPTBot) requires stakeholder approval

### Next Action

- Implement JSON-LD and robots.txt changes via @skills/seo-fix/SKILL.md
- Re-audit after deployment to verify structured data and bot access
