# Example: New Public Page Added

## SEO Change Report -- /pricing

| Field | Value |
|---|---|
| **Change type** | New public page |
| **Route** | `/pricing` |
| **Layout** | Public (guest) |
| **Status** | Complete |

### Checklist

- [x] Page uses public layout with `index, follow` meta
- [x] Canonical URL set to `https://example.com/pricing`
- [x] Title: "Pricing -- Example App"
- [x] Meta description: "See our plans and pricing."
- [x] OG tags populated (og:title, og:description, og:url, og:type)
- [x] Sitemap entry added with `<loc>https://example.com/pricing</loc>`, priority `0.8`, changefreq `monthly`
- [x] Test updated: sitemap response contains `/pricing` in `<loc>`
- [x] No `Disallow` added for `/pricing` in robots.txt

### Files changed

- `routes/web.php` -- added `/pricing` route
- `resources/views/pricing.blade.php` -- new page template
- `config/sitemap.php` -- added pricing entry
- `tests/Feature/SitemapTest.php` -- assertion for `/pricing`
