---
name: seo-fix
description: "Maintains and extends SEO setup (robots.txt, sitemap.xml, meta tags). Use when adding or changing public routes, disallow rules, sitemap entries, canonical/robots/OG tags, or when the user asks about SEO, sitemap, or robots. Do not use for general front-end or content changes unrelated to indexing or crawlability."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraints**
- Load all rules from `.cursor/rules/**/*.mdc` before starting.
- Use the same language as the assignment.
- Format all output as markdown.
- Adapt to the project’s framework and structure; locate where robots, sitemap, and head meta are implemented.

**Where SEO usually lives (locate in the current project)**
- **robots.txt** — Endpoint or static file serving GET `/robots.txt`. Response: `Content-Type: text/plain; charset=UTF-8`, header `X-Robots-Tag: noindex`. Content: see Steps.
- **sitemap.xml** — Endpoint or static file serving GET `/sitemap.xml`. Response: `Content-Type: application/xml; charset=UTF-8`, header `X-Robots-Tag: noindex`. Content: see Steps.
- **Public (indexed) pages** — Head/template with meta robots index,follow; canonical URL; link to sitemap; OG tags; title and description. Often a shared “guest” or “public” layout.
- **Private (auth/app) pages** — Head/template with meta robots noindex,nofollow. No sitemap link. Often a shared “app” or “auth” layout.
- **Tests** — Feature or E2E tests for robots response, sitemap response, and head meta (e.g. sitemap link, robots meta). Keep them green after changes.

**Steps**

**robots.txt**
1. Set response headers: `Content-Type: text/plain; charset=UTF-8`, `X-Robots-Tag: noindex`.
2. Set body: `User-agent: *`, then `Allow: /`, then one or more `Disallow: /path` lines only for private areas (dashboard, settings, auth, admin, API that must not be indexed). Add one line `Sitemap: <absolute URL of sitemap>`.
3. Do not add `Disallow` for public pages; keep `Allow: /` and list only exceptions.
4. When adding a new private area: add the corresponding `Disallow: /path` and add or update a test that the response contains that line.

**sitemap.xml**
1. Set response headers: `Content-Type: application/xml; charset=UTF-8`, `X-Robots-Tag: noindex`.
2. Use XML root `<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"`. For multi-locale: add `xmlns:xhtml="http://www.w3.org/1999/xhtml"` and per URL `<xhtml:link rel="alternate" hreflang="..." href="...">` for each locale and `hreflang="x-default"`.
3. Per URL: `<loc>` (absolute URL), `<lastmod>` (YYYY-MM-DD), `<changefreq>` (e.g. weekly, monthly, yearly), `<priority>` (0.0–1.0). Static and dynamic entries (e.g. blog, products) come from app config, CMS, or backend; ensure each has lastmod (e.g. file mtime or updated_at), priority, and changefreq.
4. When adding a new public page: register it where the app builds the sitemap (config, DB, CMS); ensure the page uses the public head (canonical, title, description, index,follow); add or update a test that the sitemap body contains the new URL in `<loc>`.
5. Priority guidance: homepage often `1.0`; main sections about `0.8`–`0.9`; blog/articles about `0.8`–`0.9`; legal/low-priority about `0.3`. changefreq: often updated `weekly`, else `monthly`, legal `yearly`.

**Meta and layout**
1. **Public pages:** Ensure head includes: `<meta name="robots" content="index, follow" ...>`, canonical URL, `<link rel="sitemap" ... href="<sitemap URL>">`, OG tags (og:title, og:description, og:url, og:type, og:image). Ensure title and meta description exist (from i18n, CMS, or config per route/page).
2. **Private (auth/app) pages:** Ensure head includes: `<meta name="robots" content="noindex, nofollow">`. Do not add sitemap link.
3. For a new public route/page: use the public layout and ensure title and meta description are defined for that page.

**After SEO changes**
1. Run existing tests that hit robots, sitemap, or assert head meta. Fix any failing assertions.

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
