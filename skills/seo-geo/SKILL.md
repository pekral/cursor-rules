---
name: seo-geo
description: "Use when improving SEO and GEO (Generative Engine Optimization): AI search visibility, keywords, JSON-LD, meta tags, content structure, robots/sitemap strategy, or when the user asks about ChatGPT/Perplexity/Gemini/Copilot/Claude citation or traditional Google/Bing ranking."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Read project.mdc file
- First, load all the rules for the cursor editor (rules/.*mdc).
- I want the texts to be in the language in which the assignment was written.
- All messages formatted as markdown for output.
- Do not rely on bundled scripts or external example files; use project code, public URLs, and available tools (e.g. WebSearch, HTTP fetch) only.
- For **implementing** `robots.txt`, `sitemap.xml`, route-level meta, canonical, and OG tags in a Laravel/PHP codebase, follow @skills/seo-fix/SKILL.md. Use this skill for **strategy, audits, GEO content patterns, and schema design** that complements that implementation work.

**Steps:**

**Understand GEO**
- **GEO (Generative Engine Optimization)** — optimizing so AI search systems **cite** your content; citation is the primary success signal, not classic blue-link rank alone.
- Combine traditional SEO (crawl, index, snippets) with GEO (clear answers, citations, structured facts).

**Audit (technical, no custom scripts)**
- Obtain the target URL(s). Check HTML for `<title>`, `<meta name="description">`, Open Graph tags, and `application/ld+json` (e.g. via HTTP fetch or local template output).
- Fetch `/robots.txt` and verify important user-agents are not blocked for public content: e.g. `Googlebot`, `Bingbot`, `Perplexity-User`, `ChatGPT-User`, `GPTBot`, `ClaudeBot` / `anthropic-ai`, as required by product policy.
- Fetch `/sitemap.xml` (or app sitemap route) and confirm important public URLs appear under `<loc>`.
- Note page speed and mobile usability from project context or public signals when relevant.

**Keyword and competitor research**
- Use WebSearch for difficulty, volume hints, and competitor pages (e.g. `site:competitor.com keyword`).
- Capture long-tail variants and locale-specific ambiguity (same acronym, different industries).

**GEO content methods (Princeton-style checklist)**
- Prioritize: **authoritative citations**, **concrete statistics**, **attributed quotations**, confident expert tone, plain-language explanations, domain terminology where appropriate, varied vocabulary, strong readability (fluency). **Avoid keyword stuffing** (hurts visibility).
- **Strong pair:** fluency + statistics.
- Prefer **answer-first** layout: direct answer before detail; clear `H1` > `H2` > `H3`; lists and tables for comparisons; short paragraphs.

**Structured data**
- Recommend JSON-LD types that match the page: `WebPage` / `Article`, `FAQPage`, `Product`, `Organization`, `SoftwareApplication`, etc.
- For FAQ-style GEO lift, suggest `FAQPage` with questions and answers that include citations or numbers where truthful.
- Validate with Google Rich Results Test and Schema.org validator (share URLs; do not assume GUI automation).

**Traditional on-page SEO**
- Title: primary keyword, brand, secondary keyword where natural.
- Meta description ~150–160 characters, compelling, aligned with query intent.
- Align OG and Twitter Card tags with title/description and a 1200×630 image when available.
- Checklist: primary keyword in `H1`; descriptive image `alt`; internal links; external links with `rel="noopener noreferrer"` when appropriate; public pages indexable; reasonable load time.

**Platform-oriented notes (high level)**
- **ChatGPT / OpenAI:** brand and freshness matter; backlinks and clear structure support citation.
- **Perplexity:** allow Perplexity-related crawling per `robots.txt` policy; FAQ schema and semantically tight copy help; PDFs may be cited where applicable.
- **Google (incl. AI Overviews):** E-E-A-T, structured data, topical clusters and internal links, citations where appropriate.
- **Bing / Copilot:** Bing index coverage; fast pages; clear entity definitions.
- **Claude (via search partners):** factual density and clear structure aid extraction.

**Deliverable**
- Produce a concise markdown report: current status (meta, schema, robots, sitemap, AI bot access), prioritized recommendations, GEO tactics applied or proposed, and validation links/tests to run in the project.

**After completing the tasks**
- If code changes to robots, sitemap, or layouts are required, hand off implementation steps to @skills/seo-fix/SKILL.md and keep tests green.
- Summarize what was audited, what to change first, and what to validate after deploy.
