---
name: jira-pr-code-review
description: Runs a code review for a PR linked to Jira. Activate when the user asks for "Jira issue review", "tasks waiting for code review", "load PR from Jira and review", or when they want to pick a Jira issue and run a review using the code-review skill.
---

# Code review for a Jira-linked PR

This skill describes the workflow: **find a Jira issue (e.g. in Code Review status) → get the GitHub PR link → load branch and changes → run a code review** according to `.cursor/skills/code-review/SKILL.md`. Do not modify any code; output only the review.

---

## When to use this skill

- The user wants a **code review** and references **Jira** (issue, task, "my issues in code review").
- The user wants to **load a PR from Jira** and review it.
- The user says "run a code review" and the context involves Jira/PR.

---

## Prerequisites

- **Atlassian CLI (acli):** installed (`brew install acli`), authenticated via `acli jira auth login` or token in the environment.
- **Jira API token:** in the project `.env` as `JIRA_API_TOKEN=...` (agent loads it with `export $(grep -v '^#' .env | grep -E '^JIRA_API_TOKEN=' | xargs)` before calling acli).
- **GitHub CLI (gh):** installed and authenticated (`gh auth status`), with access to the repo (e.g. Tlapi/ecomailapp).
- **Code-review skill:** `.cursor/skills/code-review/SKILL.md` exists and applies.

---

## Workflow (steps for the agent)

### 1. Load the Jira issue

- **All issues waiting for code review (assigned to the user):**
  ```bash
  export $(grep -v '^#' .env | grep -E '^JIRA_API_TOKEN=' | xargs)
  acli jira workitem search --jql "assignee = currentUser() AND status = 'Code Review'" --paginate
  ```
- **A specific issue (when you have the KEY, e.g. ECOMAIL-6185):**
  ```bash
  acli jira workitem view <KEY> --fields '*all' --json
  ```
  From the output (or comments) extract the **GitHub PR URL** (e.g. "PR:" + inline link in a comment, or `attrs.url` with `https://github.com/.../pull/...`).

### 2. Get PR number and branch

- From the PR URL (e.g. `https://github.com/Tlapi/ecomailapp/pull/23541`) take the **PR number** (23541) and **repo** (Tlapi/ecomailapp).
- Load branch and metadata:
  ```bash
  gh pr view <NUMBER> --repo <OWNER/REPO> --json headRefName,baseRefName,title,url
  ```
  Use **headRefName** as the PR branch.

### 3. Load changes vs master

- In the project repo:
  ```bash
  git fetch origin <HEAD_REF_NAME>
  git diff origin/master...origin/<HEAD_REF_NAME> --name-only
  git diff origin/master...origin/<HEAD_REF_NAME> -- <file paths>
  ```
  You now have the list of changed files and the diff to review.

### 4. Load Jira context for the review

- From the Jira issue (step 1) use:
    - **Summary** and **Description** – what was to be implemented (e.g. min/max, percentiles, backward compatibility).
    - **All comments** – often contain the PR link, extra requirements, or links to Bugsnag.
- Use this context only to scope the review (what the implementation was supposed to do). Do not repeat it as praise.

### 5. Run the code review

- Follow the **full** `.cursor/skills/code-review/SKILL.md` (General, Git Analysis, Large Data, Database/SQL, Architecture, Stability, Security, Tests).
- Review **only the diff and changed files** (not the whole repo). Look for:
    - mismatches with the Jira spec (including edge cases and comments),
    - SQL issues (non-SARGable conditions, functions on indexed columns, types),
    - architecture violations (e.g. Repository read-only, DTO usage, `.cursor/rules`),
    - test gaps (missing coverage for changes, missing min/max variants).
- **Output:** report **only errors, defects, and shortcomings**. Do **not** include praise, "what was done well", or positive highlights. Structure the output as: brief context (issue/PR), then a list of findings (critical first, then recommendations). **Do not modify code.**

---

## Output rules (strict)

- **Include:** bugs, type/contract violations, security or SQL issues, missing tests or edge cases, deviations from Jira or from `.cursor/rules`.
- **Exclude:** any positive assessment, "done well" sections, or compliments. If there are no findings, say only: "No errors or deficiencies found."

---

## Agent checklist

1. [ ] Load the Jira issue (search or view by KEY).
2. [ ] From the issue or comments, get the GitHub PR URL.
3. [ ] `gh pr view <number> --repo <owner/repo>` → branch (headRefName).
4. [ ] `git fetch` + `git diff origin/master...origin/<branch>` → changes.
5. [ ] Use Jira summary, description, and comments only as context for what to check.
6. [ ] Run the code review per `.cursor/skills/code-review/SKILL.md` and output **only errors and deficiencies** (no praise).

---

## Example triggers

- "Load my tasks waiting for code review and run a code review on one of them."
- "Run a code review for Jira issue ECOMAIL-6185."
- "I have a Jira issue with a PR link – load that PR and review the changes."
