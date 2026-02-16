First, load all rules for the cursor editor (.cursor/rules/.*mdc).

I want you to fix the bug from JIRA (you have either the ID or a link to JIRA). Use the acli tool or MCP server to get all the information you need about the bug so you can fix it. If you have other resources available that you could use to understand the problem, load them and analyze them.

Resolve this issue (the generated code must be according to @.cursor/skills/class-refacforing/SKILL.md), then review the code according to @.cursor/skills/code-review/SKILL.md and @.cursor/skills/security-review/SKILL.md for current changes. If you find any critical issues in the new changes, resolve them and perform further iterations of the defined code review (repeat until the bug is fixed).

Ensure 100% code coverage for the current changes.

If there are any automatic fixers in the project that are called through another layer, such as Phing or composer scripts, run them and ensure automatic error correction. If there are any CI (or local) checkers, run them (never run all tests for the entire codebase, only for the current changes). Fix any errors, run the fixers again, and keep fixing until all errors are fixed. Never try to format PHP code outside of these fixers yourself.

If everything is OK, create a pull request according to the pr.mdc rules.

I want you to post a comment on the core revision on GitHub, but I want you to post only critical or medium-severity issues, ideally including the lines of code that are affected. If there are none, don't post anything! If possible, mark the issue as ready for review.

After completing all tasks for GitHub, link the created PR in the JIRA issue, post it as a comment in the JIRA issue, and change the status of the JIRA issue to ready for review.

If you are not on the main git branch in the project, switch to it.