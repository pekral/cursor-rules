---
name: seo
description: "Use when auditing, planning, or implementing SEO in a Laravel app — crawlability, indexability, JSON-LD structured data in Blade, Core Web Vitals, on-page tags, and keyword mapping."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/laravel/laravel.mdc` for routing, Blade, and asset conventions.
- Apply `@rules/security/frontend.md` — never inject unsanitized user content into JSON-LD, meta tags, or `<head>`; escape every dynamic value.
- Fix technical blockers before content optimization.
- One page maps to one clear primary search intent.
- Every recommendation must be page-specific and implementable. No generic "improve SEO" output.
- Mobile-first: indexing is mobile-first, so audit the mobile render.

## Use when
- Auditing crawlability, indexability, canonicals, or redirects.
- Improving title tags, meta descriptions, or heading structure.
- Adding or validating structured data (JSON-LD).
- Improving Core Web Vitals (LCP, INP, CLS).
- Doing keyword research and mapping keywords to URLs.
- Planning internal linking, sitemap, or robots changes.

## Technical SEO

### Crawlability
- `robots.txt` allows important pages and blocks low-value surfaces (e.g. `/admin`, filtered listing permutations). In Laravel, serve a static `public/robots.txt` or a route that renders one per environment (block everything on staging).
- No important page is unintentionally `noindex`.
- Important pages are reachable within a shallow click depth (3 clicks from the homepage).
- Avoid redirect chains longer than two hops. Audit Laravel `Redirect::` rules and web-server rewrites for stacked redirects.
- Canonical tags are self-consistent and non-looping.

### Indexability
- Preferred URL format is consistent (trailing slash, casing, `www` vs apex). Enforce with a single canonical host and HTTPS redirect.
- Generate canonical URLs from named routes, not hand-built strings, so they stay consistent: `route('post.show', $post)`.
- Multilingual pages need correct `hreflang` when locales exist.
- Sitemaps reflect the intended public surface only. Build a sitemap from Laravel routes/models, or use the optional `spatie/laravel-sitemap` package to crawl and emit `sitemap.xml`.
- No duplicate URLs compete without canonical control (e.g. pagination, query-string facets).

### Canonical + meta in a Blade layout
```blade
{{-- resources/views/layouts/app.blade.php (inside <head>) --}}
<title>@yield('title', config('app.name'))</title>
<meta name="description" content="@yield('meta_description', '')">
<link rel="canonical" href="{{ $canonical ?? url()->current() }}">
@hasSection('noindex')
    <meta name="robots" content="noindex,nofollow">
@endif
@stack('schema')
```

```blade
{{-- a page view --}}
@section('title', $post->title . ' | ' . config('app.name'))
@section('meta_description', Str::limit(strip_tags($post->excerpt), 155))
@php($canonical = route('post.show', $post))
```

## Core Web Vitals
Targets: LCP < 2.5s, INP < 200ms, CLS < 0.1.

- LCP: preload the hero image and critical fonts; ship hashed assets through Vite (`@vite`) so they cache long-term. Use `loading="eager"` and `fetchpriority="high"` on the LCP image; `loading="lazy"` on below-the-fold images.
- INP: trim heavy JS. Keep Alpine.js components small and defer non-critical Livewire polling. Avoid long synchronous work in event handlers.
- CLS: reserve layout space — set explicit `width`/`height` on images, and avoid injecting content above existing content. Tailwind `aspect-*` utilities help reserve space.
- Reduce render-blocking work: let Vite split CSS/JS, defer non-critical scripts, and self-host fonts with `font-display: swap`.

## Structured data (JSON-LD)
Emit JSON-LD in the layout via a pushed stack so each page contributes its own schema. Match schema to content that is actually present.

- Homepage: `Organization` or local business schema where appropriate.
- Editorial pages: `Article` / `BlogPosting`.
- Product pages: `Product` and `Offer`.
- Interior pages: `BreadcrumbList`.
- Q&A sections: `FAQPage` only when the content truly matches.

```blade
{{-- a page view --}}
@push('schema')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'Article',
    'headline' => $post->title,
    'author'   => ['@type' => 'Person', 'name' => $post->author->name],
    'publisher'=> ['@type' => 'Organization', 'name' => config('app.name')],
    'datePublished' => $post->published_at->toIso8601String(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush
```

Build the array with `json_encode` (never string-concatenate user input) so values are escaped and the document stays valid.

## On-page rules

### Title tags
- Aim for roughly 50–60 characters.
- Put the primary keyword or concept near the front.
- Write for humans, not stuffed for bots.

### Meta descriptions
- Aim for roughly 120–160 characters.
- Describe the page honestly and include the main topic naturally.

### Headings
- Exactly one `H1` per page.
- `H2`/`H3` reflect real content hierarchy; do not pick heading levels for visual styling.

### Formulas
```text
Title:  Primary Topic - Specific Modifier | Brand
Meta:   Action + topic + value proposition + one supporting detail
```

## Keyword mapping
1. Define the search intent.
2. Gather realistic keyword variants.
3. Prioritize by intent match, likely value, and competition.
4. Map one primary keyword/theme to one URL.
5. Detect and avoid cannibalization (two URLs targeting the same intent).

## Internal linking
- Link from strong pages to pages you want to rank.
- Use descriptive anchor text; avoid generic anchors when a specific one fits.
- Backfill links from new pages to relevant existing ones.

## Audit output shape
```text
[HIGH] Duplicate title tags on product pages
Location: resources/views/products/show.blade.php
Issue: @section('title') falls back to the app name for every product, weakening relevance and creating duplicate signals.
Fix: Render a unique title from the product name and primary category.
```

## Anti-patterns

| Anti-pattern | Fix |
| --- | --- |
| keyword stuffing | write for users first |
| thin near-duplicate pages | consolidate or differentiate them |
| schema for content that is not present | match schema to reality |
| advice without checking the actual page | read the real Blade view first |
| generic "improve SEO" output | tie every recommendation to a page or asset |
| hand-built canonical URLs | derive from named routes |

## Done when
- robots.txt, canonicals, and redirects are correct with no unintended `noindex`.
- Sitemap reflects the public surface and important pages are shallow-depth.
- Each audited page has one H1, a 50–60 char title, and a 120–160 char meta description.
- Valid JSON-LD matches the page's real content.
- LCP/INP/CLS targets are met or have a concrete remediation plan.
- Every finding cites a specific file or URL and a concrete fix.
