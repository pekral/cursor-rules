---
name: composer-update
description: "Use when the user ran composer update and wants to check for
  package conflicts and get a summary of changelogs from updated packages."
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Composer Update

## Purpose

Run `composer update && composer bump`, detect package conflicts, and summarize changelogs for every updated dependency.

---

## Constraint

- Apply @rules/base-constraints.mdc
- All messages formatted as markdown for output.

---

## Workflow

Follow these steps strictly. Do not skip any.

### 1. Run Update

- Execute `composer update && composer bump`.
- If the user already ran the update manually, skip execution and use the provided output or inspect `composer.lock` vs the last committed version.

### 2. Detect Updated Packages

- From the update output or lock diff, list every package that was added or changed (name and old -> new version).

### 3. Check for Conflicts

- **From update output**: Look for Composer messages containing "conflict", "Conflict", "requires", "your requirement" or "cannot be installed".
- **Explicit check**: For each updated or important dependency, optionally run `composer why-not vendor/package version` to see if the requested version is blocked.
- **Lock vs require**: Compare `composer.json` constraints with resolved versions in `composer.lock`; note any package that was downgraded or could not be satisfied at the requested version.
- Summarize: either "No conflicts detected" or list each conflict with the involved packages and the reason (e.g. "X requires Y ^2.0 but Z requires Y ^1.0").

### 4. Changelog Summary

For each updated package (from step 2):

- **CHANGELOG in project**: Check `vendor/<vendor>/<package>/CHANGELOG.md` or `CHANGELOG`, `CHANGES.md`, `HISTORY.md` (or similar) and extract entries for the **new** version (and optionally the range from previous to new).
- **GitHub/GitLab releases**: If the package is from GitHub/GitLab and no CHANGELOG is in vendor, use the repository URL from `composer show vendor/package` and fetch release notes for the new version (e.g. GitHub Releases API or project's releases page).
- **Packagist / package homepage**: If available, use the "source" or "homepage" link from Packagist or `composer show` to find release notes.

### 5. Final Output

- **Conflicts**: Short section with the result of step 3.
- **Changelogs**: One subsection per updated package with a brief bullet list of notable changes (breaking changes, new features, bug fixes). If no changelog is found, state "No changelog found in vendor or linked repository."
- Optionally: suggest follow-up (e.g. run tests, run `composer validate`, check for security advisories with `composer audit`).

---

## Rules

- Do NOT skip conflict detection
- Do NOT omit packages from the changelog summary
- ALWAYS check vendor CHANGELOG files before falling back to remote sources
- ALWAYS separate conflicts from changelogs in the output

---

## Output Format

```
## Conflicts

No conflicts detected.
— or —
* vendor/package: reason

## Changelogs

### vendor/package (old -> new)

* Breaking: ...
* New: ...
* Fix: ...

### vendor/package-2 (old -> new)

* ...

## Suggested Follow-up

* ...
```
