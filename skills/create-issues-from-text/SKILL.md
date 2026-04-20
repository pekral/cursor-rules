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
- Wait for confirmation (if not explicitly skipped)

### 3. Create Issues
- One issue per step
- Ensure each is independently deliverable

### 4. Output
- Return list of created issues with URLs

---

## Issue Structure

Use the template defined in `templates/issue-structure.md`.
