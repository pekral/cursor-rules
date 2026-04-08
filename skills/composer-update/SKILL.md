---
name: composer-update
description: "Use when the user ran composer update and wants to check for package conflicts and get a summary of changelogs from updated packages."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- All messages formatted as markdown.

**Trigger:**
- User says they ran `composer update` and asks to check for conflicts and/or summarize changelogs of updated packages.

**Scripts:** Use the pre-built scripts in `@skills/composer-update/scripts/` to gather data. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/detect-updates.sh` | Dry-run composer update and diff composer.lock to list changed packages |
| `scripts/check-conflicts.sh` | Validate dependencies and check why-not for specific packages |
| `scripts/package-changelog.sh <pkg>` | Look up changelog files in vendor and show package source info |

**References:**
- `references/conflict-detection.md` — rules for detecting conflicts from output, why-not checks, and lock vs require comparison
- `references/changelog-lookup-strategy.md` — priority order for finding changelogs: vendor files, GitHub/GitLab releases, Packagist

**Examples:** See `examples/` for expected output format:
- `examples/report-no-conflicts.md` — clean update with changelog summaries
- `examples/report-with-conflicts.md` — update with dependency conflicts

**Steps:**

## 1. Detect updated packages

- If the user did not paste the `composer update` output, run `scripts/detect-updates.sh` to see which packages would be or were updated.
- From the update output or lock diff, list every package that was added or changed (name and old -> new version).

## 2. Check for conflicts

- Analyze the update output and run `scripts/check-conflicts.sh` per `references/conflict-detection.md`.
- For each updated or important dependency, optionally run `scripts/check-conflicts.sh vendor/package version` to see if the requested version is blocked.
- Compare `composer.json` constraints with resolved versions in `composer.lock`; note any package that was downgraded or could not be satisfied at the requested version.
- Summarize: either "No conflicts detected" or list each conflict with the involved packages and the reason (e.g. "X requires Y ^2.0 but Z requires Y ^1.0").

## 3. Changelog summary for updated packages

For each updated package (from step 1):

- Run `scripts/package-changelog.sh vendor/package` and follow the lookup strategy in `references/changelog-lookup-strategy.md`.
- If a CHANGELOG file is found in vendor, extract entries for the **new** version (and optionally the range from previous to new).
- If no CHANGELOG is in vendor, use the repository URL from `composer show` and fetch release notes (e.g. GitHub Releases API).
- If no changelog is found through any source, state "No changelog found in vendor or linked repository."

## 4. Final output

- **Conflicts**: Short section with the result of step 2.
- **Changelogs**: One subsection per updated package with the summary from step 3.
- Optionally: suggest follow-up (e.g. run tests, run `composer validate`, check for security advisories with `composer audit`).

**Output contract:** For each composer update evaluation, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Conflicts summary | Yes | "No conflicts detected" or list of conflicts with reasons |
| Updated packages | Yes | List of packages with old -> new versions |
| Changelog per package | Yes | Bullet list of notable changes (breaking, features, fixes) or "No changelog found" |
| Suggested follow-up | Yes | Next actions (run tests, validate, audit) |
| Confidence notes | If applicable | Caveats such as missing changelogs, unverified conflicts, or dry-run vs actual update |
