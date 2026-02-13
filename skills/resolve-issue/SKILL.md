---
name: resolve-issue
description: End-to-end workflow for GitHub issues: run auto-fix-bug from issue URL, refactor, run fixers, code review, test, then branch, commit, and open PR. Use when the user provides GitHub issue URL(s) to resolve.
---

# Resolve Issue

**Role:** Resolve GitHub issues end-to-end by running the auto-fix-bug workflow, then refactoring, reviewing, testing, and submitting via branch, commit, and PR.

**Constraint:** If the issue is closed or not ready for processing, do nothing.

---

## 1. Run Auto-Fix-Bug

**Do:**
- For each given issue URL, run the workflow from `.cursor/skills/auto-fix-bug/SKILL.md`.
- If the problem is closed or not ready for processing, do nothing and stop.
- After resolve issues do `.cursor/skills/security-review/SKILL.md` and for sql do `.cursor/skills/database-optimizer/SKILL.md` if needed.

---

## 2. Refactor

**Do:**
- After writing the code, refactor according to `.cursor/skills/class-refactoring/SKILL.md` for all new PHP classes.
- Fix DRY, apply SOLID principles where appropriate, and enforce SRP.

---

## 3. Run Fixers

**Do:**
- Analyze `composer.json` and run the most suitable fix script(s) (e.g. fix, pint-fix, phpcs-fix), if available.

---

## 4. Code Review

**Do:**
- Perform the code review defined in `.cursor/skills/code-review/SKILL.md`.
- If any critical issues are found, ask the user and let them choose what to fix.
- If they choose something, modify the code and iterate again from step 2.

---

## 5. Test and Examples

**Do:**
- Test the functionality according to the assignment.
- If there are example files, analyze them and update them for the current changes or create new ones.
- If none exist, skip this point.

---

## 6. Submit

**Do:**
- If the code is ready for submission: create a new branch according to conventions, create a commit according to conventions, push the branch to the repository.
- After a successful push, switch to the main branch and send the user a link to the PR.
