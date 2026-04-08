---
name: seo-geo
description: "Use when improving SEO and GEO (Generative Engine Optimization): AI search visibility, keywords, JSON-LD, meta tags, content structure, robots/sitemap strategy, or when the user asks about ChatGPT/Perplexity/Gemini/Copilot/Claude citation or traditional Google/Bing ranking."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# SEO & GEO

## Purpose

Perform SEO audits, GEO (Generative Engine Optimization) analysis, and content strategy so that AI search systems **cite** your content and traditional search engines rank it well.

GEO optimizes for AI citation — the primary success signal in generative search — while combining traditional SEO (crawl, index, snippets) with clear answers, structured facts, and authoritative content.

---

## Constraint

- Apply @rules/base-constraints.mdc
- All messages formatted as markdown for output.
- Do not rely on bundled scripts or external example files beyond those in this skill; use project code, public URLs, and available tools (e.g. WebSearch, HTTP fetch) only.
- For **implementing** `robots.txt`, `sitemap.xml`, route-level meta, canonical, and OG tags in a Laravel/PHP codebase, follow @skills/seo-fix/SKILL.md. Use this skill for **strategy, audits, GEO content patterns, and schema design** that complements that implementation work.

---

## Scripts

Use the pre-built scripts in `@skills/seo-geo/scripts/` to gather data. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/check-robots.sh <url>` | Fetch robots.txt and check AI bot access policy |
| `scripts/check-sitemap.sh <url>` | Fetch sitemap.xml, count URLs, check robots.txt reference |
| `scripts/check-meta-tags.sh <url>` | Extract title, meta description, OG tags, JSON-LD, canonical |

---

## References

- `references/audit-checklist.md` — technical audit criteria for meta tags, robots.txt, sitemap, and page signals
- `references/geo-content-methods.md` — Princeton-style GEO checklist: citations, statistics, quotations, layout
- `references/structured-data-guidelines.md` — JSON-LD types, FAQ schema for GEO lift, validation methods
- `references/on-page-seo-checklist.md` — traditional on-page SEO: title, meta, OG, content structure, canonical
- `references/platform-notes.md` — platform-specific notes for ChatGPT, Perplexity, Google, Bing/Copilot, Claude

---

## Examples

See `examples/` for expected output format:
- `examples/report-full-audit.md` — complete SEO/GEO audit with status table, recommendations, and validation links
- `examples/report-geo-recommendations.md` — GEO-focused content recommendations with before/after changes

---

## Steps

1. **Obtain target URL(s)** and understand the scope (full site audit, single page, content strategy).
2. **Run audit scripts** to gather technical data:
   - `scripts/check-meta-tags.sh <url>` for HTML meta, OG, JSON-LD, canonical
   - `scripts/check-robots.sh <base-url>` for robots.txt and AI bot access
   - `scripts/check-sitemap.sh <base-url>` for sitemap coverage
3. **Evaluate technical SEO** per `references/audit-checklist.md` and `references/on-page-seo-checklist.md`.
4. **Perform keyword and competitor research** — use WebSearch for difficulty, volume hints, and competitor pages (e.g. `site:competitor.com keyword`). Capture long-tail variants and locale-specific ambiguity.
5. **Assess GEO readiness** per `references/geo-content-methods.md` — check for authoritative citations, statistics, quotations, answer-first layout, and content fluency.
6. **Evaluate structured data** per `references/structured-data-guidelines.md` — recommend JSON-LD types matching the page, suggest FAQ schema for GEO lift where applicable.
7. **Review platform-specific requirements** per `references/platform-notes.md` — ensure bot access, content signals, and schema align with target platforms.
8. **Produce the deliverable report** following the Output contract below.

---

## Output Contract

For each audited URL or site, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Current status table | Yes | Meta tags, JSON-LD, robots.txt, sitemap, AI bot access status |
| Prioritized recommendations | Yes | Ordered list of changes, highest impact first |
| GEO tactics | Yes | Applied or proposed GEO content methods with specifics |
| Structured data recommendations | If applicable | JSON-LD types to add or fix |
| Platform-specific notes | If applicable | Relevant platform optimization notes |
| Validation links/tests | Yes | URLs for Rich Results Test, Schema.org validator, or commands to verify |
| Confidence notes | If applicable | Caveats, assumptions, or items requiring stakeholder input |
| Next action | Yes | What should happen next (implementation handoff, re-audit, etc.) |

---

## After Completing the Tasks

- If code changes to robots, sitemap, or layouts are required, hand off implementation steps to @skills/seo-fix/SKILL.md and keep tests green.
- Summarize what was audited, what to change first, and what to validate after deploy.
