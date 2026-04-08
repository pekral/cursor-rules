# Review Extraction

## What to Extract

From the existing PR code review, identify all recommendations related to:
- Missing tests or test scenarios
- Edge cases that lack coverage
- Regression coverage gaps
- Coverage gaps for new or changed code paths
- Specific test patterns or approaches suggested by reviewers

## Extraction Process

1. Load the PR context using `scripts/pr-review-comments.sh <PR>`.
2. Read through all review comments (both top-level and inline thread comments).
3. Create a checklist of every testing-related recommendation.
4. Group recommendations by file or component for easier verification.

## Classifying Recommendations

- **Direct test request** — reviewer explicitly asks for a test ("add a test for X").
- **Implied coverage gap** — reviewer points out an untested scenario or edge case.
- **Regression concern** — reviewer flags a change that could break existing behavior.

## Verification Against Codebase

For each extracted recommendation:
1. Search the codebase for an existing test covering the scenario.
2. If a test exists and fully covers the recommendation, mark it as **already covered**.
3. If a test partially covers it, note what is missing.
4. If no test exists, mark it as **needs implementation**.
