---
name: analyze-problem
description: "Analyzes problems from issue trackers. Downloads and reviews attachments, provides technical analysis and solutions, and creates human-readable explanations for technical and non-technical audiences. Use when the user shares an issue link or ID and wants root-cause analysis or fix recommendations. Do not use for implementing fixes, writing code changes, or for non-issue-tracker debugging."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraints**
- Load all rules from `.cursor/rules/**/*.mdc` before starting.
- Use the same language as the assignment.
- Do not change code; produce analysis output only.
- Format all output as markdown.

**Steps**
1. Load all rules for the cursor editor from `.cursor/rules/**/*.mdc`.
2. Analyze the assignment and process all attached resources (download contents via CLI or MCP). Use issue-tracker console CLI tools; do not use a web browser.
3. Find and analyze attachments for the assignment using MCP servers or CLI tools for the specific issue tracker.
4. Analyze the error as accurately as possible and write an analysis of how to fix it and where the problem lies. Prepare the output for quick, readable orientation for humans.
5. Describe how to solve the problem effectively and simply, without side effects or disruption to the application.
6. Produce two outputs: one technical, and one for non-programmers (e.g. product managers).
