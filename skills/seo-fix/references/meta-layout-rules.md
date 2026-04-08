# Meta Tags and Layout Rules

## Public (Indexed) Pages

Head must include:
- `<meta name="robots" content="index, follow">`
- Canonical URL (`<link rel="canonical" href="...">`)
- `<link rel="sitemap" type="application/xml" href="<sitemap URL>">`
- OG tags: `og:title`, `og:description`, `og:url`, `og:type`, `og:image`
- `<title>` tag with page-specific title
- `<meta name="description" content="...">` with page-specific description

Title and meta description must exist (from i18n, CMS, or config per route/page).

Public pages typically use a shared "guest" or "public" layout.

## Private (Auth/App) Pages

Head must include:
- `<meta name="robots" content="noindex, nofollow">`
- No sitemap link

Private pages typically use a shared "app" or "auth" layout.

## New Public Route/Page

- Use the public layout
- Ensure title and meta description are defined for that page
- Verify canonical URL points to the correct location
- Verify OG tags are populated

## Where SEO Usually Lives (Locate in the Current Project)

| Component | Typical location | Content-Type |
|---|---|---|
| robots.txt | Endpoint or static file at GET `/robots.txt` | `text/plain; charset=UTF-8` |
| sitemap.xml | Endpoint or static file at GET `/sitemap.xml` | `application/xml; charset=UTF-8` |
| Public pages | Shared "guest" or "public" layout/template | N/A |
| Private pages | Shared "app" or "auth" layout/template | N/A |
| Tests | Feature or E2E tests for robots, sitemap, and head meta | N/A |
