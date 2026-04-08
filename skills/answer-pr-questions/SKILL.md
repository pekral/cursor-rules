---
name: answer-pr-questions
description: "Responds to GitHub pull request comments, answers reviewer questions, explains code decisions, and addresses review feedback in a precise and professional manner. Use when answering PR comments, responding to review, replying to GitHub comments, explaining code in PR, or addressing reviewer feedback."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Answer PR Questions

## Purpose

Analyze GitHub pull request and issue discussions, identify unanswered questions or unresolved feedback, and produce precise, technically correct responses that a senior developer would write. Each answer must directly address the reviewer's intent and respect the PR context.

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/github-operations.mdc
- Work only with questions and feedback from the provided PR/issue context.
- Ignore questions that were already clearly answered in the same thread.
- Do not hallucinate missing context — if information is unavailable, state what is missing.
- All answers must be written in the language of the original question.

## Context Understanding

Before answering any question, the agent MUST:

- Read the full PR diff to understand what changed and why.
- Read the issue description to understand the original intent.
- Read the full comment thread to understand the conversation flow.
- Identify the reviewer's actual intent behind each comment — distinguish between:
  - **Question** — reviewer needs information
  - **Suggestion** — reviewer proposes an alternative
  - **Criticism** — reviewer identifies a problem
  - **Misunderstanding** — reviewer misreads the code or intent

## Question Analysis

For each unanswered question or unresolved feedback:

1. Identify the exact scope (which file, line, or behavior is referenced).
2. Determine what the reviewer actually needs — not what the words literally say.
3. Classify the required response style (see @references/communication-style.md).
4. Gather evidence from code, diff, or issue to support the answer.

## Response Styles

Choose the appropriate style for each answer:

- **Explanation** — explain why something works a certain way. See @examples/answer-explanation.md.
- **Justification** — explain a design decision with reasoning. See @examples/answer-explanation.md.
- **Correction** — acknowledge a mistake and describe the fix. See @examples/answer-correction.md.
- **Clarification** — resolve a misunderstanding with evidence. See @examples/answer-clarification.md.
- **Agreement** — accept a suggestion and confirm the change.
- **Disagreement** — politely explain why not, with reasoning. See @examples/answer-disagreement.md.

## Answer Construction Rules

Each answer MUST:

1. **Lead with the direct answer** — no preamble, no filler.
2. **Provide reasoning** — only when it adds value, keep it brief.
3. **Reference code** — cite specific file/line when relevant.
4. **Suggest next step** — only if action is needed.

Each answer MUST NOT:

- Be generic ("looks good", "fixed", "will do").
- Ignore the actual question or change the topic.
- Hallucinate context that is not in the PR/issue.
- Be defensive, emotional, or passive-aggressive.
- Over-explain trivial things.
- Generate code unless explicitly needed to illustrate a point.
- Produce long essays — prefer clarity over completeness.

See @references/communication-style.md for tone rules and @references/handling-feedback.md for handling criticism.

## Steps

1. Open the provided issue/PR link and load all context (description, diff, comments, review threads).
2. Collect all comments from:
   - the issue timeline,
   - the related PR timeline,
   - PR review comments and review threads.
3. Build a list of all current questions and unresolved feedback.
4. Filter out questions that are already answered.
5. For each remaining item:
   - classify the reviewer intent (question / suggestion / criticism / misunderstanding),
   - choose the response style,
   - gather evidence from code and diff,
   - construct the answer following the rules above.
6. If a question cannot be fully answered, explicitly state what is missing and who should provide it.

See @references/code-explanation.md for guidelines on explaining code clearly.

## Output Contract

**Format for each answer:**

```
### <Short question summary>

**Answer:** <Direct answer — 1-3 sentences>

**Reasoning:** <Brief explanation if needed — 1-3 sentences>

**Code reference:** <file:line if relevant>

**Next step:** <Action needed, if any>
```

Keep responses:
- Short (under 150 words per answer unless complexity requires more)
- Precise (no filler, no hedging)
- Useful (every sentence must add value)

**When no unanswered questions exist:**

```markdown
## Unanswered Questions

No unanswered questions found in the current PR/issue context.
```

**Confidence notes:** If the answer relies on assumptions or incomplete information, append a brief confidence note explaining what is uncertain and why.

## Output Humanization
- Use [blader/humanizer](https://github.com/blader/humanizer) for all skill outputs to keep the text natural and human-friendly.
