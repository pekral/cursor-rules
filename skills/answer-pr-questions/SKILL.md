---
name: answer-pr-questions
description: "Analyze issue and pull request discussions, find unanswered current questions, and prepare concise unified answers for project managers or clients in plain language. Use when stakeholders need non-technical answers from GitHub issue/PR threads."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Answer PR Questions

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/github-operations.mdc
- Analyze both issue and pull request discussions from the provided link.
- Work only with questions relevant to the current PR and current issue state.
- Ignore questions that were already clearly answered in the same issue/PR context.
- Output must be understandable for non-programmers (project manager or client).

**Scripts:** Use the pre-built scripts in `@skills/answer-pr-questions/scripts/` to gather data. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/collect-issue-comments.sh <ISSUE>` | Collect all comments from the issue timeline |
| `scripts/collect-pr-comments.sh <PR>` | Collect all timeline comments from the related PR |
| `scripts/collect-review-threads.sh <PR>` | Collect all inline code review comments and threads |

**References:**
- `references/question-filtering-criteria.md` — rules for identifying current vs outdated questions, answered vs unanswered, source priority, and edge cases
- `references/answer-guidelines.md` — audience definition, language rules, handling incomplete information, answer quality checklist

**Examples:** See `examples/` for expected output format:
- `examples/report-with-answers.md` — report containing unanswered questions with unified answers
- `examples/report-no-questions.md` — report when no unanswered questions exist

**Steps:**
1. Open the provided issue link and identify its current branch/related PR.
2. Run `scripts/collect-issue-comments.sh <ISSUE>` to collect all issue comments.
3. Run `scripts/collect-pr-comments.sh <PR>` and `scripts/collect-review-threads.sh <PR>` to collect all PR comments and review threads.
4. Build a list of all current stakeholder questions per `references/question-filtering-criteria.md`.
5. Filter out questions that are already answered per the criteria in `references/question-filtering-criteria.md`.
6. Keep only unanswered and still relevant questions for the current PR.
7. For each remaining question, prepare one short unified final answer per `references/answer-guidelines.md`:
   - no technical jargon,
   - clear business meaning,
   - maximum 2-4 short sentences.
8. If a question cannot be fully answered from available information, explicitly state what is still missing and who should provide it.

**Output contract:** For each analyzed issue/PR, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Section heading | Yes | "Unanswered Questions and Unified Answers" |
| Question number and formulation | Per question | Short clear formulation of the unanswered question |
| Unified answer | Per question | Concise non-technical answer (2-4 sentences) |
| Missing information note | If applicable | What is unknown and who should provide it |
| No-questions statement | If none found | Explicit statement that no unanswered questions exist |
| Confidence notes | If applicable | Caveats about partial answers or ambiguous sources |

**Output format (markdown):**
- Use this structure so answers can be copied easily:

```markdown
## Unanswered Questions and Unified Answers

### 1) <Short question formulation>
**Unified answer:** <Short final answer for PM/client>

### 2) <Short question formulation>
**Unified answer:** <Short final answer for PM/client>
```

- If there are no unanswered relevant questions, return:

```markdown
## Unanswered Questions and Unified Answers

There are no unanswered questions in the current issue and related PR.
```

## Output Humanization
- Use [blader/humanizer](https://github.com/blader/humanizer) for all skill outputs to keep the text natural and human-friendly.
