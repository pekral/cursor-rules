# Context Gathering

## Branch and Base Detection

- Identify the current branch and its base branch (usually `master` or `main`).
- Use `git log <base>..HEAD` to load all commits since divergence.

## Commit Analysis

- For each commit, read the commit message **and** the diff to understand what changed and why.
- Do not rely solely on commit messages — verify against the actual diff.

## PR and Issue Context

- If a PR already exists for this branch, load the PR description and linked issue(s) for additional context.
- PR descriptions and issue titles often contain the business rationale that commit messages lack.
- Use linked issues to understand the broader feature or bug being addressed.

## Handling Large PRs

- For branches with many commits, look for patterns (e.g., a series of commits all touching the same module).
- Summarize repetitive changes together rather than listing each commit individually.
