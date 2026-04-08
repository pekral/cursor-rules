---
name: analyze-problem
description: This skill should be used when analyzing a problem, debugging unexpected behavior, identifying root causes, or breaking down complex issues before proposing or implementing solutions.
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Analyze Problem

## Purpose

Perform structured problem analysis before any implementation.

This skill enforces deep reasoning, prevents premature solutions, and ensures that the root cause is correctly identified.

---

## Constraint

- Apply @rules/base-constraints.mdc
- Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
- NEVER CHANGE THE CODE! Generate the output only.
- All messages formatted as markdown for output.

---

## Workflow

Follow these steps strictly. Do not skip any.

### 1. Problem Summary
- Restate the problem clearly and precisely
- Avoid copying blindly — interpret the problem

### 2. Known Facts
- List only verified information
- No assumptions

### 3. Assumptions
- List what is inferred but not confirmed
- Clearly separate from facts

### 4. Missing Information
- Identify what is unknown but necessary
- Ask for clarification if critical

### 5. Hypotheses
Generate multiple plausible explanations:
- At least 2–3 hypotheses
- Group by type if useful (code, infra, config, data)

### 6. Hypothesis Evaluation
For each hypothesis:
- Why it could be true
- Why it might be false
- Estimated likelihood (high / medium / low)

### 7. Root Cause
- Identify the most probable cause
- Explain why it is more likely than others
- If uncertain, explicitly state uncertainty

### 8. Validation Plan
Define how to confirm the root cause:
- logs to check
- commands to run
- code to inspect
- experiments to perform

### 9. Next Steps
- Suggest next actions (not full implementation)
- Keep it actionable and minimal

---

## Steps

- Analyze the assignment and go through all the attached resources (download their contents via CLI or MCP). There are specific console CLI tools available for issue trackers, so use them. Never use a web browser! If you cannot load the issue, find out the available tools in the system and choose the most suitable tool to download the information.
- Analyze all comments in the issue and create a list of tasks from the assignment and comments so that you can resolve all issues, if they have not already been resolved.
- Find the attachments for the assignment and analyze them. Again, use the available MCP servers or CLI tools for the specific issue tracker.
- Analyze the error as accurately as possible and produce output following the Workflow above.
- Write how to effectively and simply solve the problem without side effects and disruption to the application's operation.
- Produce one technical output and another for explaining to people who are not programmers, but perhaps product managers.

---

## Rules

- Do NOT jump directly to solutions
- Do NOT assume a single cause without alternatives
- Do NOT ignore uncertainty
- ALWAYS show reasoning
- ALWAYS provide multiple hypotheses

---

## Output Format

```
## Problem Summary

...

## Known Facts

* ...

## Assumptions

* ...

## Missing Information

* ...

## Hypotheses

1. ...
2. ...

## Hypothesis Evaluation

### Hypothesis 1

* Why true:
* Why false:
* Likelihood:

### Hypothesis 2

...

## Most Probable Root Cause

...

## Validation Plan

* ...

## Next Steps

* ...
```

---

## Additional Resources

See:
- references/debugging-strategies.md
- references/hypothesis-generation.md
- references/root-cause-analysis.md

See examples:
- examples/analysis-good.md
- examples/analysis-missing-context.md
- examples/analysis-multiple-hypotheses.md
