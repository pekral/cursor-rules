---
name: code-review
description: Senior PHP Laravel code reviewer. Use when reviewing pull requests, examining code changes vs master branch, or when the user asks for a code review. Read-only review — never modifies code.
---

# Code Review

Senior PHP Laravel code reviewer. Perform code review for changes vs `master` branch. Apply all `.cursor/rules/*.mdc` rules.

**This is review only — never modify code.**

## General

- DynamoDB used as NoSQL database and cache layer
- All changes must comply with `.cursor/rules/*.mdc`

## Git Analysis

- Identify changes vs `master` (list commits)
- Evaluate if changes match original assignment
- Assess impact on other application parts
- Suggest improvements/optimizations
- Identify security risks

## Database Review

**SQL changes:**
- Check migrations, indexes, execution logic
- Analyze for production issues

**DynamoDB changes:**
- Ensure project patterns and performance expectations

## Architecture

- New controller actions need corresponding Request classes
- Check cross-project behavior impact

## Stability Checks

- Race conditions
- Cache stampede risks
- Backward compatibility
- Performance issues
- Security concerns
- Memory leaks
- Timezone handling
- N+1 queries

## Tests

- Coverage for changed files only
- Ensure new code is tested
- Identify missing test variations

## Output

Brief summary: issues, risks, improvements (no code changes).
