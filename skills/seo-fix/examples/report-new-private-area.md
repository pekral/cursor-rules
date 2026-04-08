# Example: New Private Area Added

## SEO Change Report -- /admin

| Field | Value |
|---|---|
| **Change type** | New private area |
| **Route prefix** | `/admin` |
| **Layout** | Private (app) |
| **Status** | Complete |

### Checklist

- [x] `Disallow: /admin` added to robots.txt
- [x] Test updated: robots response contains `Disallow: /admin`
- [x] Area uses private layout with `noindex, nofollow` meta
- [x] `/admin` not present in sitemap.xml

### Files changed

- `app/Http/Controllers/RobotsController.php` -- added `/admin` to disallow list
- `tests/Feature/RobotsTest.php` -- assertion for `Disallow: /admin`
