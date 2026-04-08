# SEO Checklists

## New Public Page

- [ ] Page/route added; uses public (guest) layout and head with `index,follow`, canonical, sitemap link, title, description
- [ ] Entry added to sitemap source (config, CMS, or backend that generates sitemap)
- [ ] Test added or updated so sitemap response contains the new URL in `<loc>`
- [ ] No new `Disallow` for this path in robots

## New Private Area

- [ ] New `Disallow: /path` in robots (file or endpoint that serves robots.txt)
- [ ] Test added or updated so robots response contains that `Disallow`
- [ ] Area uses private (app/auth) layout with `noindex,nofollow`
- [ ] Area not included in sitemap

## After Any SEO Change

- [ ] All new or modified production code follows @skills/class-refactoring/SKILL.md
- [ ] If new database migrations were created, run them before running tests or creating a PR
- [ ] Run existing tests that hit robots, sitemap, or assert head meta
- [ ] Fix any failing assertions
