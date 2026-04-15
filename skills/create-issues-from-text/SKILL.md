---
name: create-issues-from-text
description: Use when create-issues-from-text should be selected for a task
  based on explicit user intent and clear context.
---

---

# ✂️ Optimalizovaný `create-issues-from-text`

```md
---
name: create-issues-from-text
description: Break down assignment into multiple structured issues
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

```markdown
## Goal
<Business value>

## Context
<Link or short reference>

## Technical Solution
- key decisions
- files / modules
- constraints

## Acceptance Criteria
- [ ] ...

## Testing Scenarios
### Happy Path
### Edge Cases
### Regression

## Dependencies
- ...

## Notes
- Source: <link>
```
