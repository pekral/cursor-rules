# Technical Audit Checklist

## HTML Meta Verification

- [ ] `<title>` tag present and descriptive
- [ ] `<meta name="description">` present (~150-160 characters)
- [ ] Open Graph tags (`og:title`, `og:description`, `og:image`) present
- [ ] `application/ld+json` structured data present where applicable

## Robots.txt Verification

Fetch `/robots.txt` and verify that the following user-agents are **not blocked** for public content:

| User-Agent | Platform |
|---|---|
| `Googlebot` | Google Search |
| `Bingbot` | Bing / Copilot |
| `Perplexity-User` | Perplexity |
| `ChatGPT-User` | ChatGPT |
| `GPTBot` | OpenAI (training + search) |
| `ClaudeBot` / `anthropic-ai` | Claude |

Blocking must align with the product's AI-access policy.

## Sitemap Verification

- [ ] `/sitemap.xml` (or app sitemap route) is accessible
- [ ] All important public URLs appear under `<loc>` elements
- [ ] Sitemap is referenced in `robots.txt`
- [ ] Sitemap entries use canonical URLs

## Additional Signals

- [ ] Page speed is reasonable (check project context or public signals)
- [ ] Mobile usability is acceptable
- [ ] No mixed-content warnings (HTTP resources on HTTPS pages)
