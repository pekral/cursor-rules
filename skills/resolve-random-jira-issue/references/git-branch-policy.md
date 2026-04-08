# Git Branch Policy

## Pre-resolution branch state

Before starting work on any JIRA issue:

1. **Switch to the main branch** — ensure you are on the default branch (e.g., `main` or `master`)
2. **Pull latest changes** — run `git pull` to ensure the local copy is up to date with the remote
3. **Verify clean state** — the working tree should be clean; stash or commit any uncommitted changes before proceeding

## Why this matters

- Prevents conflicts caused by working on a stale branch
- Ensures the new feature branch is based on the latest code
- Avoids accidentally including unrelated changes in the PR

## Branch creation

- The feature branch is created by the delegated `resolve-jira-issue` skill
- Branch naming should follow the project convention (typically `feature/<issue-key>` or `fix/<issue-key>`)
