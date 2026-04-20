---
name: composer-update
description: Use when analyze composer update results, detect conflicts, and
  summarize changelogs of updated dependencies
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Composer Update

## Purpose
Analyze dependency updates after `composer update`, detect conflicts, and summarize relevant changes.

---

## Constraints
- Apply @rules/php/core-standards.mdc
- Output Markdown only

---

## Execution

### 1. Update Context
- Use output from `composer update` if available
- Otherwise compare current `composer.lock` with the previous version

### 2. Detect Updated Packages
- List all added or changed packages
- Include version changes: `old → new`

### 3. Conflict Detection
- Identify dependency conflicts from:
    - composer output
    - version constraint mismatches (`composer.json` vs `composer.lock`)
- Summarize conflicts clearly (package → reason)
- If none: state "No conflicts detected"

### 4. Changelog Extraction
For each updated package:

- Prefer:
    - `vendor/<package>/CHANGELOG*`
- Fallback:
    - repository releases (GitHub/GitLab)
    - package homepage

- Extract:
    - breaking changes
    - new features
    - important fixes

- If unavailable:
    - state "No changelog found"

### 5. Suggested Follow-up
- Recommend relevant checks:
    - run tests
    - `composer validate`
    - `composer audit`

---

## Output Format

Use the template defined in `templates/update-report.md`.
---

## Principles

- Focus on impactful changes, not noise
- Highlight breaking changes first
- Prefer local sources over remote
- Be concise and actionable
- Highlight security-related changes when present
