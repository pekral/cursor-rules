---
name: seo-fix
description: "Use when maintaining or extending SEO setup (robots.txt, sitemap.xml, meta tags), adding or changing public routes, disallow rules, sitemap entries, canonical/robots/OG tags, or when the user asks about SEO, sitemap, or robots."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Read project.mdc file
- First, load all the rules for the cursor editor (rules/.*mdc).
- I want the texts to be in the language in which the assignment was written.
- All messages formatted as markdown for output.
- Adapt to the project’s framework and structure; locate where robots, sitemap, and head meta are implemented in the codebase.

**Where SEO usually lives (locate in the current project):**
- **robots.txt** — Endpoint or static file serving GET `/robots.txt`. Response: `Content-Type: text/plain; charset=UTF-8`, header `X-Robots-Tag: noindex`. Content: see Steps.
- **sitemap.xml** — Endpoint or static file serving GET `/sitemap.xml`. Response: `Content-Type: application/xml; charset=UTF-8`, header `X-Robots-Tag: noindex`. Content: see Steps.
- **Public (indexed) pages** — Head/template with meta robots index,follow; canonical URL; link to sitemap; OG tags; title and description. Often a shared “guest” or “public” layout.
- **Private (auth/app) pages** — Head/template with meta robots noindex,nofollow. No sitemap link. Often a shared “app” or “auth” layout.
- **Tests** — Feature or E2E tests for robots response, sitemap response, and head meta (e.g. sitemap link, robots meta). Keep them green after changes.

**Steps:**

**robots.txt**
- Response headers: `Content-Type: text/plain; charset=UTF-8`, `X-Robots-Tag: noindex`.
- Body: `User-agent: *`, then `Allow: /`, then one or more `Disallow: /path` lines only for private areas (dashboard, settings, auth, admin, API that must not be indexed). One line `Sitemap: <absolute URL of sitemap>`.
- Do not add `Disallow` for public pages; keep `Allow: /` and list only exceptions.
- When adding a **new private area**: add corresponding `Disallow: /path` and add or update a test that the response contains that line.

**sitemap.xml**
- Response headers: `Content-Type: application/xml; charset=UTF-8`, `X-Robots-Tag: noindex`.
- XML: root `<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"`. For multi‑locale: add `xmlns:xhtml="http://www.w3.org/1999/xhtml"` and per URL `<xhtml:link rel="alternate" hreflang="..." href="...">` for each locale and `hreflang="x-default"`.
- Per URL: `<loc>` (absolute URL), `<lastmod>` (YYYY-MM-DD), `<changefreq>` (e.g. weekly, monthly, yearly), `<priority>` (0.0–1.0). Static pages and dynamic entries (e.g. blog, products) come from app config, CMS, or backend; ensure each has lastmod (e.g. file mtime or updated_at), priority, and changefreq.
- When adding a **new public page**: register it in the place the app uses to build the sitemap (config, DB, CMS); ensure the page uses the public head (canonical, title, description, index,follow); add or update a test that the sitemap body contains the new URL in `<loc>`.
- Priority guidance: homepage often `1.0`; main sections about `0.8`–`0.9`; blog/articles about `0.8`–`0.9`; legal/low‑priority about `0.3`. changefreq: often updated `weekly`, else `monthly`, legal `yearly`.

**Meta and layout**
- **Public pages:** Head must include: `<meta name="robots" content="index, follow" ...>`, canonical URL, `<link rel="sitemap" ... href="<sitemap URL>">`, OG tags (og:title, og:description, og:url, og:type, og:image). Title and meta description must exist (from i18n, CMS, or config per route/page).
- **Private (auth/app) pages:** Head must include: `<meta name="robots" content="noindex, nofollow">`. No sitemap link.
- New public route/page: use the public layout and ensure title and meta description are defined for that page.

**After SEO changes**
- All new or modified production code must follow @skills/class-refactoring/SKILL.md.
- If new database migrations were created during the changes, run them (`php artisan migrate`) before running tests or creating a PR.
- Run existing tests that hit robots, sitemap, or assert head meta. Fix any failing assertions.

**Checklist — new public page**
- Page/route added; uses public (guest) layout and head with index,follow, canonical, sitemap link, title, description.
- Entry added to sitemap source (config, CMS, or backend that generates sitemap).
- Test added or updated so sitemap response contains the new URL in `<loc>`.
- No new `Disallow` for this path in robots.

**Checklist — new private area**
- New `Disallow: /path` in robots (file or endpoint that serves robots.txt).
- Test added or updated so robots response contains that `Disallow`.
- Area uses private (app/auth) layout with noindex,nofollow.
- Area not included in sitemap.

**Related**
- For GEO (generative engines), AI-search citation strategy, keyword research, and JSON-LD/content patterns beyond robots/sitemap wiring, use @skills/seo-geo/SKILL.md.
