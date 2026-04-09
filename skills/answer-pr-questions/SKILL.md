---
name: answer-pr-questions
description: "Analyze issue and pull request discussions, find unanswered
  current questions, and prepare concise unified answers for project managers
  or clients in plain language. Use when stakeholders need non-technical
  answers from GitHub issue/PR threads."
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Answer PR Questions

## Purpose

Find unanswered stakeholder questions in GitHub issue and PR discussions, then prepare concise, non-technical answers that a project manager or client can use directly.

---

## Constraint

- Apply @rules/base-constraints.mdc
- Apply @rules/github-operations.mdc
- Analyze both issue and pull request discussions from the provided link.
- Work only with questions relevant to the current PR and current issue state.
- Ignore questions that were already clearly answered in the same issue/PR context.
- Output must be understandable for non-programmers (project manager or client).

---

## Workflow

Follow these steps strictly. Do not skip any.

### 1. Load Context

- Open the provided issue link and identify its current branch/related PR.

### 2. Collect Comments

- Collect all comments from:
  - the issue timeline,
  - the related PR timeline,
  - PR review comments and review threads.

### 3. Identify Questions

- Build a list of all current stakeholder questions.
- Filter out questions that are already answered.
- Keep only unanswered and still relevant questions for the current PR.

### 4. Prepare Answers

- For each remaining question, prepare one short unified final answer:
  - no technical jargon,
  - clear business meaning,
  - maximum 2-4 short sentences.
- If a question cannot be fully answered from available information, explicitly state what is still missing and who should provide it.

---

## Rules

- Do NOT include technical jargon in answers
- Do NOT answer questions that are already resolved in the thread
- ALWAYS keep answers short (2-4 sentences max)
- ALWAYS state missing information explicitly when an answer is incomplete

---

## Output Format

```
## Nezodpovezene otazky a sjednocene odpovedi

### 1) <Kratka formulace otazky>
**Sjednocena odpoved:** <Kratka finalni odpoved pro PM/klienta>

### 2) <Kratka formulace otazky>
**Sjednocena odpoved:** <Kratka finalni odpoved pro PM/klienta>
```

If there are no unanswered relevant questions, return:

```
## Nezodpovezene otazky a sjednocene odpovedi

V aktualnim issue a souvisejicim PR nejsou zadne nezodpovezene otazky.
```
