---
name: analyze-problem
description: "Use when analyzing problems from issue trackers. Downloads and reviews attachments, provides technical analysis and solutions, and creates human-readable explanations for both technical and non-technical audiences."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
- NEVER CHANGE THE CODE! Generate the output only.
- All messages formatted as markdown for output.

**Steps:**
- Analyze the assignment and go through all the attached resources (download their contents via CLI or MCP). There are specific console CLI tools available for issue trackers, so use them. Never use a web browser! If you cannot load the issue, find out the available tools in the system and choose the most suitable tool to download the information.
- Analyze all comments in the issue and create a list of tasks from the assignment and comments so that you can resolve all issues, if they have not already been resolved.
- Find the attachments for the assignment and analyze them. Again, use the available MCP servers or CLI tools for the specific issue tracker.
- I want to analyze the error as accurately as possible and write an analysis of how to fix this error and where the problem lies. The output will be prepared for quick and readable orientation for humans.
- Write to me how I can effectively and simply solve the problem without side effects and disruption to the application's operation.
- I want one technical output and another for explaining to people who are not programmers, but perhaps product managers.
