---
name: test-like-human
description: "Use when testing the current pull request. Find the 'Testing Recommendations' section in the PR conversation and test the application like a senior web application tester. Follow the described scenarios, use tools when needed, and produce a human-readable report without technical notes."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**

- Apply @rules/base-constraints.mdc
- Apply @rules/github-operations.mdc
- Apply @rules/jira-operations.mdc
- Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
- **Before starting to test**, analyze all comments and discussions in the issue so that you fully understand what the final state should be and what logic should have been created. Only then begin testing.
- Work only with the **current pull request**. Testing instructions must be taken only from the PR conversation.
- Test the application like a **senior tester of web applications who is not a programmer but works in a dev team and has access to developer tools**. See `references/report-writing-rules.md` for the full tester persona and report constraints.
- Do not invent additional requirements outside the PR instructions unless needed to verify suspicious behavior.
- The final output must be written for humans: no technical notes, terminal logs, stack details, or developer commentary.

**Scripts:** Use the pre-built scripts in `@skills/test-like-human/scripts/` to gather PR data. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/load-pr.sh <PR>` | Load full PR detail: body, reviews, comments, CI checks |
| `scripts/pr-comments.sh <PR>` | Load PR conversation: description, review comments, discussion threads |

**References:**
- `references/testing-approach-rules.md` — decision criteria for choosing UI vs API vs backend vs CLI testing, scenario design, scope rules
- `references/backend-testing-guide.md` — when and how to use `php artisan tinker` or equivalent CLI clients for backend verification
- `references/api-testing-guide.md` — API documentation loading order, endpoint discovery, curl usage rules
- `references/report-writing-rules.md` — tester persona, language rules, what to include/exclude, UI and CLI scenario guidelines

**Examples:** See `examples/` for expected output format:
- `examples/report-all-passed.md` — all scenarios passed
- `examples/report-with-failures.md` — mixed results with failures
- `examples/report-blocked.md` — blocked by errors preventing testing

**Steps:**

1. Load the current pull request using `scripts/load-pr.sh <PR>` first. If `gh` is not available, use a GitHub MCP server. If neither is available, stop and return a failed result about missing GitHub tools.
2. Read the PR conversation using `scripts/pr-comments.sh <PR>`: PR description, review comments, and discussion threads.
3. Locate the **"Doporučení k testování" / "Testing Recommendations"** section and extract all testing instructions. Prefer recommendations that include direct in-app links (full URLs) for fast click-through testing.
4. If at least one extracted instruction requires API testing, first try to load the project's API documentation per `references/api-testing-guide.md`.
5. Determine the **testing approach** for each instruction per `references/testing-approach-rules.md`.
6. Convert instructions into realistic **user scenarios** per the scenario design rules in `references/testing-approach-rules.md`.
7. Execute each scenario using the appropriate approach:
   - **UI scenario** — use browser MCP tools (navigation, snapshot, click, fill, wait, assert). Simulate realistic user actions per `references/report-writing-rules.md`.
   - **API scenario** — use `curl` or equivalent per `references/api-testing-guide.md`. For testing API endpoints follow steps defined in `project.mdc` section "## Testing API endpoints like human". Never run automatic tests from codebase.
   - **Backend / code execution scenario** — use `php artisan tinker` or the project's equivalent per `references/backend-testing-guide.md`.
   - **CLI scenario** — run the required terminal command. Use the results only to support conclusions.
8. Produce the test report per the Output contract below and the formatting rules in `references/report-writing-rules.md`.

**Output contract:** For each tested scenario, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Scenario title | Yes | Short descriptive title of the scenario |
| What was tested | Yes | Short description of the user goal |
| Expected result | Yes | What a normal user would expect |
| Observed result | Yes | What actually happened |
| Status | Yes | `Passed` / `Failed` / `Blocked` / `Unclear` |
| Comment | Yes | Human-readable note focused on user experience |

The full report must also include:

| Section | Required | Description |
|---|---|---|
| PR reference | Yes | Pull request number and title |
| Tested scenarios | Yes | All scenarios with individual results |
| Overall summary | Yes | High-level outcome |
| Failed / blocked / unclear list | If any exist | List of non-passing behaviors |
| Recommendation | Yes | Whether the change appears ready from a user perspective |
| Confidence notes | If applicable | Caveats or assumptions (e.g., environment limitations, untestable scenarios) |

**After completing the tasks:**

- Post the final human-readable test report as a comment to the **related issue** in the issue tracker (GitHub issue, JIRA ticket, etc.) using the preferred tool for the tracker (see @rules/github-operations.mdc and @rules/jira-operations.mdc). The comment must be written in the language of the task assignment.
- Summarize which scenarios failed or were unclear (with technical info for the developer).
