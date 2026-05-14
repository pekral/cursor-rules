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
- Walk through the Analysis Framework below in order — do not skip steps.
- Separate facts from assumptions and from hypotheses.
- Identify the most probable root cause and how to validate it.
- Recommend the smallest safe solution and explain rejected alternatives.

---

## Analysis Framework

Apply these 10 steps in order. Each step feeds the next — never jump ahead to a solution before evidence and root cause are settled.

1. **Context extraction** — what we actually know from the assignment, comments, attachments, and surrounding code.
2. **Problem statement** — one precise sentence describing the real problem.
3. **Expected vs actual behavior** — what should happen, and what is happening instead.
4. **Evidence** — logs, screenshots, issue comments, files, reproduction steps. Verified facts only.
5. **Root cause hypothesis** — the most likely cause, clearly separated from facts. State certainty.
6. **Impact / risk** — who and what is affected (users, business, technical, risk areas).
7. **Smallest safe solution** — the smallest, lowest-risk fix that addresses the root cause.
8. **Alternatives rejected** — competing solutions considered and why they were not chosen.
9. **Verification plan** — manual checks, automated tests, edge cases, and regression checks.
10. **Non-technical summary** — plain-language explanation for PM, support, or business stakeholders.

---

## Output Structure

The output uses the template at `templates/analysis-report.md`. The template has 11 sections that map onto the framework above:

1. **Summary** — short summary (covers steps 1–2)
2. **Problem Definition** — problem statement, expected/actual behavior, affected area, problem type (steps 2–3)
3. **Verified Facts** — verified facts only (step 4)
4. **Assumptions and Missing Information** — assumptions and unknowns (supports step 5)
5. **Probable Root Cause** — root cause, certainty, alternative causes (step 5)
6. **Problem Impact** — user/business impact, technical impact, risk areas (step 6)
7. **Recommended Solution** — recommended solution, things to avoid, side effects (steps 7–8)
8. **Implementation Outline** — likely change locations, recommended steps, architecture notes (step 7)
9. **Solution Verification** — manual checks, automated tests, edge cases, regression checks (step 9)
10. **Non-Technical Explanation** — explanation for non-technical stakeholders (step 10)
11. **Final Recommendation** — final recommendation, priority, next step

Fill every section. If a section has nothing to report, write a short explicit note (e.g. `No missing information.`) instead of leaving placeholders.

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
- references/analysis-good.md
- references/analysis-missing-context.md
- references/analysis-multiple-hypotheses.md
