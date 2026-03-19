---
name: resolve-jira-issue
description: "Use when resolving JIRA issues. Fixes bugs, refactors code, performs code and security reviews, ensures 100% test coverage, runs CI checks, and creates pull requests. Links PRs to JIRA issues and updates issue status."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- Before resolving a task, always switch to the main branch, download the latest changes, and make sure you have the latest code in the main branch.
- I want the texts to be in the language in which the assignment was written.
- All comments or outputs posted to GitHub (issues, pull requests, review comments, and PR descriptions) must be written in English.
- If you are not on the main git branch in the project, switch to it.
- Analyze all comments in the issue and create a list of tasks from the assignment and comments so that you can resolve all issues, if they have not already been resolved.
- For comments posted to JIRA, always use JIRA Wiki Markup (not Markdown). Never use fenced code blocks (```), markdown headings (#), or markdown tables.

**Universal JIRA Comment Formatting**
- Use this structure for every JIRA comment type (status update, testing recommendation, CR summary, implementation summary):
- Title: `h3. <short title>`
- Short context paragraph (1-3 lines, plain text)
- Optional sections with `h4. <section title>`
- Bullets with `* item`
- Inline code/paths/endpoints in monospace using `{{...}}`
- Code examples only via `{code[:language]}` blocks
- Tables only with JIRA table syntax (`||` header row, `|` data row)
- Keep one empty line between blocks for readability

Example of the final JIRA comment body:
```text
h3. Implementation summary

Feature is ready for review. Main behavior was validated locally.

h4. What changed
* Added validation for {{subscriber_data}} payload.
* Added guard for {{allow_resubscribe}} transition from {{2 -> 1}}.

h4. API example
{code:json}
{
  "allow_resubscribe": true,
  "subscriber_data": [
    {"email": "user@example.com", "status": 1}
  ]
}
{code}

h4. Testing recommendations
* Verify update for an existing contact.
* Verify skipped unknown contact appears in response {{errors}}.
* Verify rate-limit handling (HTTP {{429}} with {{Retry-After}}).
```

**Steps:**
- Read project.mdc file
- Analyze all comments in the issue and create a list of tasks from the assignment and comments so that you can resolve all issues, if they have not already been resolved.
- I want you to fix the bug from JIRA (you have either the ID or a link to JIRA). Use the acli tool or MCP server to get all the information you need about the bug so you can fix it. If you have other resources available that you could use to understand the problem, load them and analyze them. If you cannot load the issue, find out the available tools in the system and choose the most suitable tool to download the information.
- Resolve this issue (the generated code must be according to @.cursor/skills/class-refactoring/SKILL.md), then review the code according to @.cursor/skills/code-review/SKILL.md and @.cursor/skills/security-review/SKILL.md for current changes. If you find any critical issues in the new changes, resolve them and perform further iterations of the defined code review (repeat until the bug is fixed).
- Find the attachments for the assignment and analyze them. Again, use the available MCP servers or CLI tools for the specific issue tracker.
- For all changes in the current branch, analyze code coverage and ensure that all changes are covered by tests. Add any missing tests to ensure 100% coverage.
- If there are any automatic fixers in the project that are called through another layer, such as Phing or composer scripts, run them and ensure automatic error correction (find and load local configs for tools if exists). If there are any CI (or local) checkers, run them (never run all tests for the entire codebase, only for the current changes). Fix any errors, run the fixers again, and keep fixing until all errors are fixed. Never try to format PHP code outside of these fixers yourself.
- If everything is OK, create a pull request according to the pr.mdc rules.
- If there is no link to the issue tracker, add a link to the issue tracker entry to the CR summary and, if possible, link it directly according to the issue tracker recommendations. Be sure to include an HTTP link.
- I want you to post a comment on the core revision on GitHub, but I want you to post only critical or medium-severity issues, ideally including the lines of code that are affected. If there are none, don't post anything! If possible, mark the issue as ready for review.
- After completing all tasks for GitHub, link the created PR in the JIRA issue, change the status of the JIRA issue to ready for review.
- Run the tests and let me know if the current changes meet the requirements.  If so, add a new comment to the issue with a recommendation on what to test (briefly). If the requirements are not met or you have found critical errors, just list them for me.
- Write missing tests for current changes and ensure 100% coverage, fix dry and try to simplify the code base so that it is easy to read for humans, but also as simple as possible. These changes will be in a separate commit.
- I want you to post a comment into the pull request on GitHub regarding the core review, but I want you to only post critical or moderately serious issues, ideally including the lines of code that are affected. If there are none, don't post anything! If possible, mark the issue with the label ready for review.

- **After completing the tasks**
- Once you have finished your work and pushed the changes to pr, perform a code review according to your skill level @.cursor/skills/code-review-jira/SKILL.md
- If according to @.cursor/skills/test-like-human/SKILL.md the changes can be tested, do it!
- If work id done do @.cursor/skills/code-review-jira/SKILL.md for actual issue
