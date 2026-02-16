Perform a code review according to .cursor/skills/jira-code-review/SKILL.md, including .cursor/skills/security-review/SKILL.md and .cursor/skills/code-review/SKILL.md

**Exact order of steps (important - follow the order!):**

1. **Load the assignment from JIRA**
    - Use `acli jira workitem view <KEY> --fields '*all' --json
    - Find the summary, description, and all comments
    - Find the GitHub PR link if it exists

2. **Switch to the git branch with the changes**
    - Check the current branch: `git branch --show-current`
    - If you are not in the correct branch, switch: `git checkout <branch-name>`
    - Verify the changes: `git diff origin/master...HEAD --name-only`
    - Display change statistics: `git diff origin/master...HEAD --stat`

3. **Read all necessary skills and rules**
    - Read `.cursor/skills/jira-code-review/SKILL.md`
    - Read `.cursor/skills/security-review/SKILL.md`
    - Read `.cursor/skills/code-review/SKILL.md`
    - Read the relevant security rules from `.cursor/rules/security/`

4. **Perform a code review of the changes**
    - Go through all changed files using `git diff origin/master...HEAD`
    - Apply all rules from `.cursor/rules/**/*.mdc`
    - Check architecture, SQL optimization, security, tests
    - Identify issues by priority: large data → security → SQL → performance
    - Check tests: `php artisan test --compact --filter="<relevant-filter>"`

5. **Click through the application in the browser**
    - Load APP_URL from the configuration or use the `get-absolute-url` tool
    - Log in to the application:
        * Use test credentials from tests or `.env`
        * Or create a test account using the `tinker` tool
    - Find endpoints related to code changes:
        * Search routes using `grep` or `codebase_search`
        * Identify all endpoints that have been changed or affected
    - Test all changed functions:
        * Happy path scenarios (correct inputs)
        * Edge cases (invalid inputs, boundary values, null values)
        * Various parameter combinations
    - Verify that everything works correctly
    - Record all issues found in a report

6. **Test the application's security**
    - SQL Injection: Verify that all queries use ORM/parameterized queries
    - XSS: Check output escaping (no `{!! !!}` without sanitization)
    - CSRF: Verify CSRF protection on state-changing operations
    - Input Validation: Test invalid inputs
    - Authorization: Verify that endpoints check authorization
    - Business Logic: Test edge cases and race conditions

7. **Perform penetration tests**
    - Test invalid inputs on all endpoints related to changes:
        * SQL injection payloads: `' OR '1'='1`, `'; DROP TABLE--`
        * XSS payloads: `<script>alert('XSS')</script>`, `<img src=x onerror=alert(1)>`
        * Boundary values: negative numbers, very large numbers, null values
    - Test CSRF: Try sending a request without a CSRF token
    - Test authorization: Try accessing endpoints without authentication or with a different user
    - Test all possible parameter combinations
    - Record all security issues in a report

8. **Run tests**
    - Run all tests related to changes: `php artisan test --compact --filter="<relevant-filter>"`
    - Verify that all tests pass
    - Check test coverage for changed files
    - Record test results in a report

9. **Generate a complete report**
    - Create a markdown file named `code-review-<JIRA-KEY>.md`
    - Save it to the `.logs/` folder (which is not versioned - create it if it does not exist)
    - The report must contain all of the following sections:
        * Overview of changes (commits, changed files, statistics)
        * Critical issues (sorted by severity: critical → high → medium → low)
        * Security review (all security issues found with categories)
        * SQL optimization (issues found with queries)
        * Architecture (design issues found)
        * Tests (coverage, test quality, test results)
        * Performance (issues found)
        * **Section on browser testing** (what was tested, what scenarios, issues found)
        * **Section on penetration testing** (what tests were performed, what was found, tested payloads)
        * **Section on edge cases** (what edge cases were tested, what was found)
        * Recommendations for improvement (specific suggestions with code examples)
        * Summary (positives, issues to be addressed sorted by priority, statistics)
        * Conclusion with recommendation (whether the code is ready to be merged)
          **Important notes:**
- The application is available at locale (e.g., `https://petrkral.ecomailapp.test`)
- For testing, you can use the `tinker` tool to create test accounts
- Use browser tools (`browser_navigate`, `browser_snapshot`, `browser_click`, `browser_type`, etc.) to click through the application
- For security tests, you can use the `database-query` tool to verify SQL queries
- All steps must be performed in the order listed
- The report must be complete and contain all sections, including browser testing and penetration tests
- If any step fails (e.g., JIRA cannot be loaded), note this in the report and continue

**Example report structure:**
```markdown
# Code Review Report - <JIRA-KEY>
- Overview of changes
- Commits
- Critical issues
- Security Review
- SQL Optimization
- Architecture
- Tests
- Performance
- Browser testing  ← MANDATORY
- Penetration tests and Security  ← MANDATORY
- Edge Cases Testing  ← MANDATORY
- Recommendations for improvement
- Summary
- Conclusion
```

