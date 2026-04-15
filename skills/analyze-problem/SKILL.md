---
name: analyze-problem
description: Use when structured problem analysis for debugging, root cause
  identification, and breaking down complex issues before proposing solutions
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Analyze Problem

## Purpose
Perform structured problem analysis before proposing or implementing any changes.

Focus on:
- verified facts
- multiple hypotheses
- root cause identification
- validation strategy

---

## Constraints
- Apply @rules/php/core-standards.mdc
- Never modify code
- Output Markdown only
- Use one language only
- Do not jump directly to solutions
- Do not assume a single cause
- Be explicit about uncertainty

---

## Execution

- Analyze the problem and all available context.
- If relevant, load issue, comments, and attachments using available CLI or MCP tools.
- Prefer issue-tracker-specific tools over generic browsing.
- Separate facts from assumptions.
- Generate multiple plausible hypotheses.
- Identify the most probable root cause.
- Define how to validate it.
- Suggest minimal, low-risk next steps.

---

## Analysis Structure

### 1. Problem Summary
- Clearly restate the problem in your own words
- Interpret, do not copy

### 2. Known Facts
- Only verified information
- No assumptions

### 3. Assumptions
- Inferred but not confirmed

### 4. Missing Information
- Unknowns that affect confidence or validation

### 5. Hypotheses
- At least 2 plausible explanations
- Prefer competing explanations
- Group when useful:
    - code
    - configuration
    - infrastructure
    - data
    - external dependencies

### 6. Hypothesis Evaluation
For each hypothesis:
- why it could be true
- why it might be false
- likelihood: high / medium / low

### 7. Most Probable Root Cause
- Select the most likely explanation
- Explain why it is stronger than others
- Explicitly state uncertainty if present

### 8. Validation Plan
Define how to confirm or reject the root cause:
- logs to inspect
- commands to run
- code paths to check
- experiments to perform

### 9. Next Steps
- Minimal, actionable steps
- Do not provide full implementation

### 10. Stakeholder Explanation
- Explain the issue in simple terms
- Focus on impact, cause, and next step

---

## Output Format

```markdown
## Technical Analysis

### Problem Summary
...

### Known Facts
- ...

### Assumptions
- ...

### Missing Information
- ...

### Hypotheses
1. ...
2. ...

### Hypothesis Evaluation

#### Hypothesis 1
- Why true:
- Why false:
- Likelihood:

#### Hypothesis 2
- Why true:
- Why false:
- Likelihood:

### Most Probable Root Cause
...

### Validation Plan
- ...

### Next Steps
- ...

## Stakeholder Explanation
...

```

---

## Principles

- Focus on root cause, not symptoms
- Prefer evidence over assumptions
- Avoid confirmation bias
- Keep analysis structured and concise
- Prefer simple explanations over complex ones

---

## References

- references/debugging-strategies.md
- references/hypothesis-generation.md
- references/root-cause-analysis.md

---

## Examples

- examples/analysis-good.md
- examples/analysis-missing-context.md
- examples/analysis-multiple-hypotheses.md
