# Changelog Lookup Strategy

For each updated package, attempt to find changelog information using the following sources in priority order.

## 1. CHANGELOG in Vendor Directory

Check the following file paths inside `vendor/<vendor>/<package>/`:
- `CHANGELOG.md`
- `CHANGELOG`
- `CHANGES.md`
- `HISTORY.md`

Extract entries for the **new** version and optionally the range from the previous to the new version.

## 2. GitHub / GitLab Releases

If the package is hosted on GitHub or GitLab and no CHANGELOG file is found in vendor:
1. Get the repository URL from `composer show vendor/package`.
2. Fetch release notes for the new version using:
   - GitHub Releases API: `gh api repos/{owner}/{repo}/releases/tags/{tag}`
   - Or the project's releases page.

## 3. Packagist / Package Homepage

If neither of the above yields results:
- Use the `source` or `homepage` link from Packagist or `composer show` output.
- Look for release notes on the package homepage.

## Fallback

If no changelog is found through any source, report:
> "No changelog found in vendor or linked repository."

## Summary Format Per Package

```
- **vendor/package** (old -> new): brief bullet list of notable changes
  (breaking changes, new features, bug fixes)
```
