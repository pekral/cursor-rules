---
name: composer-update
description: "Use when the user ran composer update and wants to check for package conflicts and get a summary of changelogs from updated packages."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Read project.md file.
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- Output in the same language as the user's request.
- All messages formatted as markdown.

**Trigger:**
- User says they ran `composer update` and asks to check for conflicts and/or summarize changelogs of updated packages.

**Steps:**

## 1. Detect updated packages

- If the user did not paste the `composer update` output, run `composer update --dry-run` (or inspect `composer.lock` vs last committed version) to see which packages would be or were updated.
- From the update output or lock diff, list every package that was added or changed (name and old → new version).

## 2. Check for conflicts

- **From update output**: Look for Composer messages containing "conflict", "Conflict", "requires", "your requirement" or "cannot be installed".
- **Explicit check**: For each updated or important dependency, optionally run `composer why-not vendor/package version` to see if the requested version is blocked.
- **Lock vs require**: Compare `composer.json` constraints with resolved versions in `composer.lock`; note any package that was downgraded or could not be satisfied at the requested version.
- Summarize: either "No conflicts detected" or list each conflict with the involved packages and the reason (e.g. "X requires Y ^2.0 but Z requires Y ^1.0").

## 3. Changelog summary for updated packages

For each updated package (from step 1):

- **CHANGELOG in project**: Check `vendor/<vendor>/<package>/CHANGELOG.md` or `CHANGELOG`, `CHANGES.md`, `HISTORY.md` (or similar) and extract entries for the **new** version (and optionally the range from previous to new).
- **GitHub/GitLab releases**: If the package is from GitHub/GitLab and no CHANGELOG is in vendor, use the repository URL from `composer show vendor/package` and fetch release notes for the new version (e.g. GitHub Releases API or project’s releases page).
- **Packagist / package homepage**: If available, use the "source" or "homepage" link from Packagist or `composer show` to find release notes.

**Summary format (per package):**

- **vendor/package** (old → new): brief bullet list of notable changes (breaking changes, new features, bug fixes) from the changelog or release notes. If no changelog is found, state "No changelog found in vendor or linked repository."

## 4. Final output

- **Conflicts**: Short section with the result of step 2.
- **Changelogs**: One subsection per updated package with the summary from step 3.
- Optionally: suggest follow-up (e.g. run tests, run `composer validate`, check for security advisories with `composer audit`).
