# robots.txt Rules

## Response Headers

- `Content-Type: text/plain; charset=UTF-8`
- `X-Robots-Tag: noindex`

## Body Format

1. `User-agent: *`
2. `Allow: /`
3. One or more `Disallow: /path` lines **only** for private areas (dashboard, settings, auth, admin, API endpoints that must not be indexed)
4. `Sitemap: <absolute URL of sitemap>`

## Disallow Policy

- Do **not** add `Disallow` for public pages
- Keep `Allow: /` and list only exceptions (private areas)
- When adding a **new private area**: add the corresponding `Disallow: /path` line and add or update a test that the response contains that line

## Common Private Areas to Disallow

- `/dashboard`
- `/settings`
- `/auth`
- `/admin`
- `/api` (if not intended for public indexing)
- `/app`

## Anti-patterns

- Do not use `Disallow: /` (blocks everything)
- Do not list public pages in Disallow
- Do not omit the Sitemap directive
- Do not serve robots.txt with wrong Content-Type
