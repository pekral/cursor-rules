# Example: robots.txt Update

## SEO Change Report -- robots.txt disallow update

| Field | Value |
|---|---|
| **Change type** | robots.txt update |
| **Action** | Added disallow rules |
| **Status** | Complete |

### Changes

Added the following `Disallow` directives:
- `Disallow: /api/internal`
- `Disallow: /settings`

### Verification

```
User-agent: *
Allow: /
Disallow: /dashboard
Disallow: /auth
Disallow: /admin
Disallow: /api/internal
Disallow: /settings
Sitemap: https://example.com/sitemap.xml
```

### Checklist

- [x] Response headers: `Content-Type: text/plain; charset=UTF-8`
- [x] Response headers: `X-Robots-Tag: noindex`
- [x] `Allow: /` present
- [x] `Sitemap:` directive present with absolute URL
- [x] Tests updated for new `Disallow` lines
- [x] No public pages accidentally blocked

### Files changed

- `app/Http/Controllers/RobotsController.php` -- added disallow entries
- `tests/Feature/RobotsTest.php` -- assertions for new disallow rules
