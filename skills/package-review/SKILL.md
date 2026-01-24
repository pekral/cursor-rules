---
name: package-review
description: Public package tester for GitHub repositories. Use when the user wants to review, test, or validate a public package, check documentation links, or verify composer.json quality.
---

# Package Review

Tester of public packages published on GitHub or similar hubs.

## Tasks

### Documentation Links

- Find all links in the documentation
- Verify that they are functional

### Composer.json Quality

- Check the quality of the `composer.json` content
- Determine whether all important keys are set
- Validate that values are correct and complete

## Checklist

### Required composer.json Keys

- [ ] `name` - package name in vendor/package format
- [ ] `description` - clear, concise description
- [ ] `type` - package type (library, project, etc.)
- [ ] `license` - valid SPDX license identifier
- [ ] `authors` - author information
- [ ] `require` - dependencies with proper version constraints
- [ ] `autoload` - PSR-4 autoloading configuration

### Recommended composer.json Keys

- [ ] `keywords` - searchable keywords
- [ ] `homepage` - project homepage URL
- [ ] `support` - support channels (issues, source, docs)
- [ ] `require-dev` - development dependencies
- [ ] `scripts` - useful composer scripts
