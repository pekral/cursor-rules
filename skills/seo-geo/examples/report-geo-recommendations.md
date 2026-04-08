# Example: GEO-Focused Recommendations

## Site: blog.example.com/guide/widget-setup

### Current Status

| Area | Status |
|---|---|
| **Title** | Present but generic ("Widget Setup Guide") |
| **JSON-LD** | `Article` schema present |
| **AI bot access** | All major bots allowed |
| **GEO readiness** | Low — no citations, no statistics, no FAQ |

### GEO Recommendations

1. **Add authoritative citations** — reference official documentation and industry reports inline (e.g., "According to the 2024 Widget Industry Report...")
2. **Include concrete statistics** — replace vague claims ("many users") with specifics ("78% of enterprise users reported...")
3. **Add FAQ section** — append 3-5 common questions with concise, citation-ready answers; implement `FAQPage` schema
4. **Restructure to answer-first** — move the setup summary to the top before step-by-step details
5. **Improve heading hierarchy** — current structure skips H2; fix to H1 > H2 > H3

### Content Changes Proposed

| Section | Current | Proposed |
|---|---|---|
| Intro | "This guide covers widget setup." | "Widget setup takes 5 minutes and requires an API key. This guide walks through each step with screenshots." |
| Benefits | "Widgets are useful for teams." | "Teams using widgets report 34% faster onboarding (2024 Widget Survey, WidgetCorp)." |

### Confidence Notes

- Statistics are illustrative; actual data must be sourced from verified reports before publishing

### Next Action

- Apply content changes and add FAQ schema
- Validate with Google Rich Results Test after deployment
