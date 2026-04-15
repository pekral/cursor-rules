---
name: create-issue
description: Use when create a single issue from provided text without modifying its content
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Create Issue

## Purpose
Create a well-formatted issue while preserving the original content exactly.

---

## Constraints
- Preserve original text exactly (no rewriting or summarizing)
- Improve formatting only (headings, lists, spacing)
- Assign issue to current user
- Use available CLI tools

---

## Execution

### 1. Prepare Content
- Use original text as-is
- Apply readable formatting (headings, lists)

### 2. Generate Title
- Use first line of input
- Remove formatting only

### 3. Create Issue
- Use available CLI tool
- Set title and formatted description
- Assign to current user

### 4. Output
- Return direct link to created issue

---

## Output Format

```markdown
# Task

<original text>

---

# Notes

Formatted automatically. Content unchanged.
```
