---
name: package-review
description: "Use when reviewing composer.json packages. Validates structure, checks required fields, verifies links, and ensures proper configuration of autoloading, dependencies, and metadata."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/skills/base-constraints.mdc
- All messages formatted as markdown for output.
- If you are not on the main git branch in the project, switch to it.

**Steps:**
- Find all links in the documentation.
- Verify that each link is functional.
- Check the quality of the `composer.json` content.
- Determine whether all important keys are set.
- Validate that values are correct and complete.
- Refresh readme.md file for current changes, don`t rewrite all, just only merge or delete file content.

**Check presence and correctness:**
- [ ] `name` — package name in `vendor/package` format
- [ ] `description` — clear, concise description
- [ ] `type` — package type (e.g. `library`, `project`)
- [ ] `license` — valid SPDX license identifier
- [ ] `authors` — author information
- [ ] `require` — dependencies with proper version constraints
- [ ] `autoload` — PSR-4 autoloading configuration

**Check presence and usefulness:**
- [ ] `keywords` — searchable keywords
- [ ] `homepage` — project homepage URL
- [ ] `support` — support channels (issues, source, docs)
- [ ] `require-dev` — development dependencies
- [ ] `scripts` — useful composer scripts
