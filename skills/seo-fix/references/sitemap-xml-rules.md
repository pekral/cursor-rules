# sitemap.xml Rules

## Response Headers

- `Content-Type: application/xml; charset=UTF-8`
- `X-Robots-Tag: noindex`

## XML Structure

Root element:
```xml
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
```

For multi-locale sites, add:
```xml
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
```

Per URL entry, include `<xhtml:link rel="alternate" hreflang="..." href="...">` for each locale plus `hreflang="x-default"`.

## Required Fields per URL

| Field | Format | Description |
|---|---|---|
| `<loc>` | Absolute URL | Full URL of the page |
| `<lastmod>` | YYYY-MM-DD | Last modification date (file mtime or `updated_at`) |
| `<changefreq>` | weekly, monthly, yearly | How often the page changes |
| `<priority>` | 0.0 -- 1.0 | Relative importance |

## Priority Guidance

| Page type | Priority | changefreq |
|---|---|---|
| Homepage | `1.0` | `weekly` |
| Main sections | `0.8` -- `0.9` | `weekly` or `monthly` |
| Blog / articles | `0.8` -- `0.9` | `weekly` |
| Legal / low-priority | `0.3` | `yearly` |

## Data Sources

Static pages and dynamic entries (blog, products) come from app config, CMS, or backend. Ensure each entry has `lastmod`, `priority`, and `changefreq`.

## Adding a New Public Page

- Register the page in the place the app uses to build the sitemap (config, DB, CMS)
- Ensure the page uses the public head (canonical, title, description, `index,follow`)
- Add or update a test that the sitemap body contains the new URL in `<loc>`
