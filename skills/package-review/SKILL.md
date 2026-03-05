---
name: package-review
description: "Reviews composer.json packages by validating structure, required fields, links, autoloading, dependencies, and metadata. Use when the user wants a package or composer.json review. Do not use for application-level code review or non-Composer projects."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraints**
- Load all rules from `.cursor/rules/**/*.mdc` before starting.
- Format all output as markdown.
- If not on the main git branch, switch to it.

**Steps**
1. Load all rules for the cursor editor from `.cursor/rules/**/*.mdc`.
2. Find all links in the documentation and verify that each link is functional.
3. Assess the quality of the `composer.json` content. Determine whether all important keys are set and validate that values are correct and complete.
4. Refresh the readme.md file for current changes; do not rewrite the whole file — only merge or delete content as needed.

**Check presence and correctness**
- [ ] `name` — package name in `vendor/package` format
- [ ] `description` — clear, concise description
- [ ] `type` — package type (e.g. `library`, `project`)
- [ ] `license` — valid SPDX license identifier
- [ ] `authors` — author information
- [ ] `require` — dependencies with proper version constraints
- [ ] `autoload` — PSR-4 autoloading configuration

**Check presence and usefulness**
- [ ] `keywords` — searchable keywords
- [ ] `homepage` — project homepage URL
- [ ] `support` — support channels (issues, source, docs)
- [ ] `require-dev` — development dependencies
- [ ] `scripts` — useful composer scripts
