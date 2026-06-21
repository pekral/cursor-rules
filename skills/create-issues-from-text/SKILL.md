---
name: create-issues-from-text
description: Use when break down assignment into multiple structured issues
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Create Issues from Text

## Purpose
Split a complex assignment into multiple clear, structured issues.

---

## Constraints
- Preserve original assignment (store in parent or first issue)
- Do not implement code
- Assign all issues to current user
- Use CLI tools

---

## Execution

### 1. Analyze Assignment
- Understand scope and dependencies
- Identify logical implementation steps

### 2. Propose Breakdown
- List steps with short descriptions
- Proceed directly to step 3; do not gate on a confirmation round when the breakdown is unambiguous

### 3. Create Issues
- One issue per step
- Ensure each is independently deliverable

### 4. Output
- Return list of created issues with URLs

---

## Issue Structure

Use the template defined in `templates/issue-structure.md`.

---

## EPIC parent & sub-issues

Use this when the assignment is a **cross-cutting mix of requirements spanning multiple parts of the application** (e.g. backend + frontend + mobile, or schema + API + UI). Instead of a flat list of peer issues, build a parent → children tree so the whole effort is trackable from one place.

1. **Pick (or create) the parent.** When the assignment already has a tracker item, that item becomes the EPIC parent. When the assignment is a described task with no tracker item, create the parent issue first, carrying the full original assignment in its body.
2. **Label the parent `EPIC`.** Apply the `EPIC` label to the parent. Create the label once if the repository does not have it yet (`gh label create EPIC --description "Tracks a cross-cutting effort split into sub-issues" --color 5319e7`), then ignore the "already exists" outcome on subsequent runs.
3. **Create one sub-issue per application area.** Each sub-issue is an independently deliverable assignment (same `templates/issue-structure.md` structure) scoped to a single area; do not bundle two areas into one sub-issue.
4. **Link every sub-issue back to the parent (both directions).**
   - In each sub-issue body, reference the parent with `Part of #<parent>` so the relationship is visible from the child.
   - In the parent body, keep a checkable task list of the children — `- [ ] #<child>` per sub-issue — under a `## Sub-issues` heading, so the parent shows the full breakdown and progress.
5. **Order.** Fill each sub-issue's `## Dependencies` so a resolving run (e.g. `daidalos`) can pick a dependency-aware order — dependencies before dependents.
6. **Output.** Return the `EPIC`-labelled parent URL plus the list of linked sub-issue URLs and the planned resolve order.
