---
name: answer-pr-questions
description: "Analyze issue and pull request discussions, find unanswered current questions, and prepare concise unified answers for project managers or clients in plain language. Use when stakeholders need non-technical answers from GitHub issue/PR threads."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Answer PR Questions

**Constraint:**
- Apply @rules/skills/base-constraints.mdc
- Apply @rules/skills/github-operations.mdc
- Analyze both issue and pull request discussions from the provided link.
- Work only with questions relevant to the current PR and current issue state.
- Ignore questions that were already clearly answered in the same issue/PR context.
- Output must be understandable for non-programmers (project manager or client).

**Steps:**
- Open the provided issue link and identify its current branch/related PR.
- Collect all comments from:
  - the issue timeline,
  - the related PR timeline,
  - PR review comments and review threads.
- Build a list of all current stakeholder questions.
- Filter out questions that are already answered.
- Keep only unanswered and still relevant questions for the current PR.
- For each remaining question, prepare one short unified final answer:
  - no technical jargon,
  - clear business meaning,
  - maximum 2-4 short sentences.
- If a question cannot be fully answered from available information, explicitly state what is still missing and who should provide it.

**Output format (markdown):**
- Use this structure so answers can be copied easily:

```markdown
## Nezodpovězené otázky a sjednocené odpovědi

### 1) <Krátká formulace otázky>
**Sjednocená odpověď:** <Krátká finální odpověď pro PM/klienta>

### 2) <Krátká formulace otázky>
**Sjednocená odpověď:** <Krátká finální odpověď pro PM/klienta>
```

- If there are no unanswered relevant questions, return:

```markdown
## Nezodpovězené otázky a sjednocené odpovědi

V aktuálním issue a souvisejícím PR nejsou žádné nezodpovězené otázky.
```

## Output Humanization
- Use [blader/humanizer](https://github.com/blader/humanizer) for all skill outputs to keep the text natural and human-friendly.
