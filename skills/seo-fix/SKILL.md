---
name: seo-fix
description: "Use when maintaining or extending SEO setup (robots.txt, sitemap.xml, meta tags), adding or changing public routes, disallow rules, sitemap entries, canonical/robots/OG tags, or when the user asks about SEO, sitemap, or robots."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- All messages formatted as markdown for output.
- Adapt to the project's framework and structure; locate where robots, sitemap, and head meta are implemented in the codebase.

**Scripts:** Use the pre-built scripts in `@skills/seo-fix/scripts/` to verify SEO configuration. Do not reinvent these checks — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/check-robots.sh <BASE_URL>` | Fetch robots.txt and validate headers, directives, and sitemap reference |
| `scripts/check-sitemap.sh <BASE_URL>` | Fetch sitemap.xml and validate headers, XML structure, and entry count |
| `scripts/check-meta.sh <PAGE_URL>` | Fetch a page and extract robots meta, canonical, title, description, OG tags |

**References:**
- `references/robots-txt-rules.md` — response headers, body format, disallow policy, anti-patterns
- `references/sitemap-xml-rules.md` — response headers, XML structure, required fields, priority guidance
- `references/meta-layout-rules.md` — public vs private page meta tags, layout requirements, where SEO lives
- `references/checklists.md` — new public page checklist, new private area checklist, post-change checklist

**Examples:** See `examples/` for expected output format:
- `examples/report-new-public-page.md` — adding a new indexed page
- `examples/report-new-private-area.md` — adding a new private/noindex area
- `examples/report-robots-update.md` — updating robots.txt disallow rules

**Steps:**

1. Locate where robots.txt, sitemap.xml, and head meta are implemented in the current project per `references/meta-layout-rules.md`.
2. Determine the type of SEO change (new public page, new private area, robots update, sitemap update, meta fix).
3. Apply the rules from the corresponding reference file:
   - **robots.txt** changes: follow `references/robots-txt-rules.md`
   - **sitemap.xml** changes: follow `references/sitemap-xml-rules.md`
   - **Meta and layout** changes: follow `references/meta-layout-rules.md`
4. After making changes, verify using the appropriate script:
   - `scripts/check-robots.sh` for robots.txt changes
   - `scripts/check-sitemap.sh` for sitemap changes
   - `scripts/check-meta.sh` for meta/layout changes
5. Complete the applicable checklist from `references/checklists.md`.
6. All new or modified production code must follow @skills/class-refactoring/SKILL.md.
7. If new database migrations were created during the changes, run them (`php artisan migrate`) before running tests or creating a PR.
8. Run existing tests that hit robots, sitemap, or assert head meta. Fix any failing assertions.

**Output contract:** For each SEO change, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Change type | Yes | What was changed (new public page, new private area, robots update, etc.) |
| Route / path | Yes | The route or path affected |
| Layout | Yes | Public (guest) or Private (app/auth) |
| Status | Yes | Complete / incomplete / blocked |
| Checklist | Yes | All items from the applicable checklist with pass/fail |
| Files changed | Yes | List of files modified or created |
| Verification result | If scripts were run | Output summary from check scripts |
| Confidence notes | If applicable | Caveats or assumptions (e.g., could not verify live, framework-specific quirks) |

**Related**
- For GEO (generative engines), AI-search citation strategy, keyword research, and JSON-LD/content patterns beyond robots/sitemap wiring, use @skills/seo-geo/SKILL.md.
