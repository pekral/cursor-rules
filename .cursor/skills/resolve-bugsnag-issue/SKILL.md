---
name: resolve-bugsnag-issue
description: "Use when resolving Bugsnag issues. Fixes bugs, refactors code, performs code and security reviews, ensures 100% test coverage, runs CI checks, and creates pull requests. Updates GitHub issues with review results."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Read project.mdc file
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- Before resolving a task, always switch to the main branch, download the latest changes, and make sure you have the latest code in the main branch.
- I want the texts to be in the language in which the task was assigned. Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
- If you are not on the main git branch in the project, switch to it.
- Analyze all comments in the issue and create a list of tasks from the assignment and comments so that you can resolve all issues, if they have not already been resolved.

**Steps:**
- Analyze all comments in the issue tracker and check what needs to be done accordingly. Stick strictly to the assignment and comments!
- I want you to fix the bug from Bugsnag (you either got an ID or a link to Bugsnag). Use the MCP server to get all the necessary information about the bug so you can fix it. If you have other resources available that you could use to understand the problem, load them and analyze them. Use the available CLI tools or MCP servers to load them. If you cannot load the issue, find out the available tools in the system and choose the most suitable tool to download the information.
- Bugsnag issues always represent runtime errors or exceptions and are therefore always treated as bugs. Follow strict TDD:
  1. Write a test that reproduces the reported failure (the test must fail before any fix is applied).
  2. Run the test and confirm it fails — do not proceed until you see the red failure.
  3. Implement the minimal fix that makes the test pass.
  4. Run the test again and confirm it is green.
- Resolve this issue (the generated code must be according to @.cursor/skills/class-refactoring/SKILL.md), then review the code according to @.cursor/skills/code-review/SKILL.md and @.cursor/skills/security-review/SKILL.md for current changes. If you find any critical issues in the new changes, resolve them and perform further iterations of the defined code review (repeat until the bug is fixed).
- Find the attachments for the assignment and analyze them. Again, use the available MCP servers or CLI tools for the specific issue tracker.
- For all changes in the current branch, analyze code coverage and ensure that all changes are covered by tests. Add any missing tests to ensure 100% coverage.
- If new database migrations were created during the changes, run them (`php artisan migrate`) before running tests or creating a PR.
- If there are any automatic fixers in the project that are called through another layer, such as Phing or composer scripts, run them and ensure automatic error correction (find and load local configs for tools if exists). If there are any CI (or local) checkers, run them (never run all tests for the entire codebase, only for the current changes). Fix any errors, run the fixers again, and keep fixing until all errors are fixed. Never try to format PHP code outside of these fixers yourself.
- If everything is OK create a pull request, create it according to the pr.mdc rules.
- If there is no link to the issue tracker, add a link to the issue tracker entry to the CR summary and, if possible, link it directly according to the issue tracker recommendations. Be sure to include an HTTP link.
- I want you to post a comment into the pull request on GitHub regarding the core review, but I want you to only post critical or moderately serious issues, ideally including the lines of code that are affected. If there are none, don't post anything! If possible, mark the issue with the label ready for review.
- Run the tests and let me know if the current changes meet the requirements. If so, add a new comment to the issue with brief testing recommendations and include direct in-app links (full URLs) for each recommendation so testers can click through immediately. If the requirements are not met or you have found critical errors, just list them for me.
- Write missing tests for current changes and ensure 100% coverage, fix dry and try to simplify the code base so that it is easy to read for humans, but also as simple as possible. These changes will be in a separate commit.
- After generating or modifying tests, verify that all new tests comply with the testing rules in `@.cursor/rules/php/standards.mdc`. Check mock usage specifically: mock only external services (HTTP clients) or to simulate exceptions; remove any constructor mocks, unnecessary mocks, or mocks that can be replaced with real service logic.
- After creating the PR, perform a code review @.cursor/skills/code-review/SKILL.md for the current task.
- If you are not on the main git branch in the project, switch to it.

**After completing the tasks**
- Once you have finished your work and pushed the changes to pr, perform a code review according to your skill level @.cursor/skills/code-review/SKILL.md
- If according to @.cursor/skills/test-like-human/SKILL.md the changes can be tested, do it!
- If work is done do @.cursor/skills/code-review/SKILL.md for actual issue
